<?php

require_once ROOT_PATH . '/core/Model.php';

class Sale extends Model {

    protected $tableName = 'sales';
    public $allowedPaymentStatuses = ['pending', 'paid', 'partially_paid', 'refunded', 'cancelled'];
    public $allowedPaymentTypes = ['immediate', 'deferred'];

    public function __construct(Database $dbInstance) {
        parent::__construct($dbInstance);
    }

    /**
     * Creates a new sale along with its items and updates product stock.
     * @param array $data Sale header data
     * @param array $itemsData Array of items (product_id, quantity_sold, unit_price)
     * @return string|false The ID of the newly created sale or false on failure.
     */
    public function createSale(array $data, array $itemsData) {
        // Validate Sale data
        if (empty($data['sale_date']) || empty($data['payment_type'])) {
            error_log("Sale Date and Payment Type are required.");
            return false;
        }
        if ((empty($data['client_id']) && empty($data['client_name_occasional'])) || (!empty($data['client_id']) && !empty($data['client_name_occasional']))) {
            error_log("Either a Client ID or an Occasional Client Name must be provided, but not both.");
            return false;
        }
        if (!in_array($data['payment_type'], $this->allowedPaymentTypes)) {
            error_log("Invalid payment type."); return false;
        }
        if ($data['payment_type'] === 'deferred' && empty($data['due_date'])) {
            error_log("Due date is required for deferred payments."); return false;
        }
        if (isset($data['payment_status']) && !in_array($data['payment_status'], $this->allowedPaymentStatuses)) {
            error_log("Invalid payment status."); return false;
        }
         if (empty($itemsData)) {
            error_log("Sale must have at least one item."); return false;
        }

        $this->pdo->beginTransaction();
        try {
            $productModel = new Product($this->db);

            // Check stock availability for all items first
            foreach ($itemsData as $item) {
                if (empty($item['product_id']) || empty($item['unit_id']) || empty($item['quantity_sold']) ||
                    $item['quantity_sold'] <= 0 || !isset($item['unit_price']) || $item['unit_price'] < 0) {
                    $this->pdo->rollBack();
                    error_log("Invalid item data: product_id, unit_id, positive quantity_sold, and non-negative unit_price are required.");
                    return false;
                }
                $product = $productModel->getById($item['product_id']);
                if (!$product) {
                    $this->pdo->rollBack();
                    error_log("Product ID {$item['product_id']} not found for stock check.");
                    return "Produit ID {$item['product_id']} non trouvé.";
                }

                $quantitySoldInBaseUnit = (float)$item['quantity_sold'];
                if ($item['unit_id'] != $product['base_unit_id']) {
                    $productUnits = $productModel->getUnitsForProduct($item['product_id']);
                    $conversionFactor = null;
                    foreach ($productUnits as $pu) {
                        if ($pu['unit_id'] == $item['unit_id']) {
                            $conversionFactor = (float)$pu['conversion_factor_to_base_unit'];
                            break;
                        }
                    }
                    if ($conversionFactor === null || $conversionFactor == 0) {
                        $this->pdo->rollBack();
                        error_log("Conversion factor not found or invalid for product ID {$item['product_id']}, unit ID {$item['unit_id']}.");
                        return "Erreur de configuration d'unité pour le produit: " . $product['name'];
                    }
                    $quantitySoldInBaseUnit = (float)$item['quantity_sold'] * $conversionFactor;
                }

                if ($product['quantity_in_stock'] < $quantitySoldInBaseUnit) {
                    $this->pdo->rollBack();
                    $unitInfo = (new Unit($this->db))->getById($item['unit_id']);
                    $unitNameForMsg = $unitInfo ? $unitInfo['name'] : "ID Unité ".$item['unit_id'];
                    error_log("Insufficient stock for product ID {$item['product_id']} (Name: {$product['name']}). Requested: {$item['quantity_sold']} {$unitNameForMsg} (équiv. {$quantitySoldInBaseUnit} {$product['base_unit_name']}). Available: {$product['quantity_in_stock']} {$product['base_unit_name']}.");
                    return "Stock insuffisant pour: " . $product['name'] . ". Demandé: " . $item['quantity_sold'] . " " . $unitNameForMsg . ". Disponible (en unité de base): " . $product['quantity_in_stock'] . " " . $product['base_unit_name'] . ".";
                }
            }

            // Calculate gross total from items
            $grossTotal = 0;
            foreach($itemsData as $item){
                $grossTotal += (float)$item['quantity_sold'] * (float)$item['unit_price'];
            }

            // Calculate net total_amount (after discount)
            // This total_amount is what gets stored in the sales table.
            $discountAmount = (float)($data['discount_amount'] ?? 0.00);
            $calculatedTotalAmount = $grossTotal - $discountAmount;
            $data['total_amount'] = $calculatedTotalAmount; // Override/set total_amount to be net amount

            // Prepare Sale header fields
            // Added 'discount_amount', 'paid_amount', 'amount_tendered', 'change_due'
            $saleFields = ['client_id', 'client_name_occasional', 'sale_date', 'total_amount',
                           'discount_amount', 'paid_amount', 'amount_tendered', 'change_due',
                           'payment_status', 'payment_type', 'due_date', 'notes'];
            $saleParams = [];
            $saleColumns = [];
            $salePlaceholders = [];

            // Set paid_amount based on payment type and status before building params
            if ($data['payment_type'] === 'immediate' && ($data['payment_status'] ?? (($data['payment_type'] === 'immediate') ? 'paid' : 'pending')) === 'paid') {
                $data['paid_amount'] = $data['total_amount']; // total_amount is already net
            } else {
                // For deferred or non-paid immediate, or if status is not 'paid', initial paid_amount is 0
                // unless explicitly provided (e.g. an initial deposit on a deferred sale, though not handled by current form)
                $data['paid_amount'] = $data['paid_amount'] ?? 0.00;
            }
            // If amount_tendered is not set for an immediate paid sale, assume it was exact amount
            if ($data['payment_type'] === 'immediate' && ($data['payment_status'] ?? 'paid') === 'paid') {
                if (!isset($data['amount_tendered']) || $data['amount_tendered'] === null) {
                    $data['amount_tendered'] = $data['total_amount']; // Assume tendered was exact net amount
                }
                if (!isset($data['change_due']) || $data['change_due'] === null) {
                     // This assumes 'total_amount' is net. Change is tendered - net.
                    $data['change_due'] = (float)($data['amount_tendered'] ?? $data['total_amount']) - (float)$data['total_amount'];
                    if ($data['change_due'] < 0) $data['change_due'] = 0; // Should not happen if controller validation is correct
                }
            }


            foreach ($saleFields as $field) {
                if (array_key_exists($field, $data)) {
                    $saleColumns[] = $field;
                    $salePlaceholders[] = ':' . $field;
                    if ($data[$field] === '' && in_array($field, ['client_id', 'client_name_occasional', 'due_date', 'notes', 'amount_tendered', 'change_due', 'paid_amount'])) {
                        $saleParams[':' . $field] = null;
                    } elseif ($field === 'discount_amount' && $data[$field] === '') {
                        $saleParams[':' . $field] = 0.00;
                    }
                    else {
                        $saleParams[':' . $field] = $data[$field];
                    }
                }
            }

            // Ensure discount_amount is set if not provided in $data but column is added
            if (!in_array('discount_amount', $saleColumns) && in_array('discount_amount', $saleFields)) {
                 $saleColumns[] = 'discount_amount';
                 $salePlaceholders[] = ':discount_amount';
                 $saleParams[':discount_amount'] = 0.00;
            }


            if (!isset($data['payment_status'])) { // Default payment status
                $saleColumns[] = 'payment_status';
                $salePlaceholders[] = ':payment_status';
                $saleParams[':payment_status'] = ($data['payment_type'] === 'immediate') ? 'paid' : 'pending';
            }
            if ($data['payment_type'] === 'immediate') { // Clear due_date for immediate payments
                $saleParams[':due_date'] = null;
            }


            $sqlSale = "INSERT INTO {$this->tableName} (" . implode(', ', $saleColumns) . ", created_at, updated_at)
                        VALUES (" . implode(', ', $salePlaceholders) . ", CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
            $saleId = $this->db->insert($sqlSale, $saleParams);

            if (!$saleId) {
                $this->pdo->rollBack();
                error_log("Failed to create sale header.");
                return false;
            }

            // Insert Sale Items & Update Product Stock
            $sqlItem = "INSERT INTO sale_items (sale_id, product_id, unit_id, quantity_sold, unit_price)
                        VALUES (:sale_id, :product_id, :unit_id, :quantity_sold, :unit_price)";

            foreach ($itemsData as $item) {
                $itemInsertParams = [
                    ':sale_id' => $saleId,
                    ':product_id' => (int)$item['product_id'],
                    ':unit_id' => (int)$item['unit_id'],
                    ':quantity_sold' => (float)$item['quantity_sold'],
                    ':unit_price' => (float)$item['unit_price']
                ];
                $saleItemId = $this->db->insert($sqlItem, $itemInsertParams);

                if (!$saleItemId) {
                    $this->pdo->rollBack();
                    error_log("Failed to create sale item for product ID {$item['product_id']}.");
                    return false;
                }

                // Decrease product stock and create stock movement using new updateStock signature
                $notes = "Sold via SA-{$saleId} (Item {$saleItemId})";
                if (!$productModel->updateStock(
                    (int)$item['product_id'],                // productId
                    'out_sale',                             // movementType
                    -(float)$item['quantity_sold'],         // NEGATIVE quantityInTransactionUnit
                    (int)$item['unit_id'],                  // transactionUnitId
                    $saleItemId,                            // relatedDocumentId
                    'sale_items',                           // relatedDocumentType
                    $notes                                  // notes
                )) {
                    $this->pdo->rollBack();
                    // The error from updateStock (e.g. "Conversion factor not found") might be more specific
                    // than just "Failed to update stock". Consider how to bubble this up.
                    error_log("Failed to update stock or create movement for product ID {$item['product_id']} during sale SA-{$saleId}.");
                    return "Échec de la mise à jour du stock pour le produit ID {$item['product_id']}."; // More specific error
                }
            }

            $this->pdo->commit();
            return $saleId;

        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error creating sale with items: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Updates the payment status and paid_amount of a sale based on its payments.
     * @param int $saleId The ID of the sale to update.
     * @return bool True on success, false on failure.
     */
    public function updateSalePaymentStatus(int $saleId): bool {
        if (!class_exists('SalePayment')) {
            require_once ROOT_PATH . '/app/models/SalePayment.php';
        }
        $salePaymentModel = new SalePayment($this->db);

        $totalPaid = $salePaymentModel->getTotalPaidForSale($saleId);
        $sale = $this->getById($saleId); // This method already exists and fetches the sale header

        if (!$sale) {
            error_log("Sale not found (ID: {$saleId}) when trying to update payment status.");
            return false;
        }

        $saleTotalAmount = (float)$sale['total_amount']; // Net amount after discount
        $newStatus = $sale['payment_status']; // Default to current status

        // Determine new status based on payments
        if ($totalPaid <= 0 && $sale['payment_status'] !== 'refunded' && $sale['payment_status'] !== 'cancelled') {
            $newStatus = 'pending';
        } elseif ($totalPaid > 0 && $totalPaid < $saleTotalAmount && $sale['payment_status'] !== 'refunded' && $sale['payment_status'] !== 'cancelled') {
            $newStatus = 'partially_paid';
        } elseif ($totalPaid >= $saleTotalAmount && $sale['payment_status'] !== 'refunded' && $sale['payment_status'] !== 'cancelled') {
            $newStatus = 'paid';
        }
        // Note: Business logic for transitioning *from* refunded/cancelled upon new payments is not handled here.
        // Such transitions might require more specific actions.

        $sql = "UPDATE {$this->tableName}
                SET payment_status = :payment_status,
                    paid_amount = :paid_amount,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :sale_id";
        try {
            $this->db->update($sql, [
                ':payment_status' => $newStatus,
                ':paid_amount' => $totalPaid,
                ':sale_id' => $saleId
            ]);
            return true;
        } catch (PDOException $e) {
            error_log("Error updating sale payment status for sale ID {$saleId}: " . $e->getMessage());
            return false;
        }
    }

    public function getByIdWithDetails($id) {
        $sale = $this->getById($id);
        if (!$sale) return false;

        $sale['items'] = $this->getItemsForSale($id);
        if ($sale['client_id']) {
            $clientModel = new Client($this->db);
            $client = $clientModel->getById($sale['client_id']);
            $sale['client_display_name'] = $client ? $client['name'] . " (Reg.)" : "Unknown Client (ID: {$sale['client_id']})";
        } else {
            $sale['client_display_name'] = $sale['client_name_occasional'] . " (Occ.)";
        }
        return $sale;
    }

    public function getById($id){
         $sql = "SELECT * FROM {$this->tableName} WHERE id = :id";
        try {
            $result = $this->db->select($sql, [':id' => $id]);
            return $result ? $result[0] : false;
        } catch (PDOException $e) {
            error_log("Error fetching sale by ID {$id}: " . $e->getMessage());
            return false;
        }
    }

    public function getItemsForSale($saleId) {
        $sql = "SELECT si.*, p.name as product_name, u.name as unit_name, u.symbol as unit_symbol
                FROM sale_items si
                JOIN products p ON si.product_id = p.id
                JOIN units u ON si.unit_id = u.id
                WHERE si.sale_id = :sale_id";
        try {
            return $this->db->select($sql, [':sale_id' => $saleId]);
        } catch (PDOException $e) {
            error_log("Error fetching items for Sale ID {$saleId}: " . $e->getMessage());
            return [];
        }
    }

    public function getAllWithClientDetails() {
        $sql = "SELECT s.*, c.name as registered_client_name
                FROM {$this->tableName} s
                LEFT JOIN clients c ON s.client_id = c.id
                ORDER BY s.sale_date DESC, s.id DESC";
        try {
            $sales = $this->db->select($sql);
            foreach($sales as &$sale) {
                $sale['client_display_name'] = $sale['client_id'] ? ($sale['registered_client_name'] . " (Reg.)") : ($sale['client_name_occasional'] . " (Occ.)");
            }
            return $sales;
        } catch (PDOException $e) {
            error_log("Error fetching all sales: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Updates payment status and related fields for a sale.
     * @param int $id Sale ID.
     * @param string $newStatus New payment status.
     * @param string|null $paymentDate Date of payment, if applicable.
     * @return bool True on success, false on failure.
     */
    public function updatePayment($id, $newStatus, $paymentDate = null) {
        if (!in_array($newStatus, $this->allowedPaymentStatuses)) {
            error_log("Invalid payment status '{$newStatus}' for sale ID {$id}.");
            return false;
        }

        $params = [':id' => $id, ':status' => $newStatus];
        $setParts = ["payment_status = :status"];

        if ($newStatus === 'paid' || $newStatus === 'partially_paid') {
            $setParts[] = "payment_date = :payment_date";
            $params[':payment_date'] = $paymentDate ?? date('Y-m-d');
        } else {
            // If status is not 'paid' or 'partially_paid', clear payment_date
            $setParts[] = "payment_date = NULL";
        }

        $setParts[] = "updated_at = CURRENT_TIMESTAMP";
        $sql = "UPDATE {$this->tableName} SET " . implode(', ', $setParts) . " WHERE id = :id";

        try {
            $this->db->update($sql, $params);
            return true;
        } catch (PDOException $e) {
            error_log("Error updating payment status for sale ID {$id}: " . $e->getMessage());
            return false;
        }
    }

    // Deletion of sales is complex due to stock & potential financial records.
    // Usually, a "return" or "cancellation" flow is preferred, which might create reverse transactions.
    // A simple delete that reverts stock:
    public function deleteSale($saleId) {
        $this->pdo->beginTransaction();
        try {
            $saleItems = $this->getItemsForSale($saleId);
            $saleHeader = $this->getById($saleId);

            if (!$saleHeader) {
                $this->pdo->rollBack(); return false; // Not found
            }
            // Cannot delete if already paid, unless specific logic allows (e.g. refund status first)
            if (in_array($saleHeader['payment_status'], ['paid', 'partially_paid']) && $saleHeader['payment_status'] !== 'cancelled' && $saleHeader['payment_status'] !== 'refunded') {
                 $this->pdo->rollBack();
                 error_log("Cannot delete sale ID {$saleId} because it is marked as '{$saleHeader['payment_status']}'. Consider cancelling or refunding first.");
                 return "Cannot delete '{$saleHeader['payment_status']}' sale.";
            }


            // Revert stock quantities (add back to stock) and create reversal movements
            $productModel = new Product($this->db);
            foreach ($saleItems as $item) {
                $reversalNotes = "Reversal for deleted/cancelled SA-{$saleId}, Item ID: {$item['id']}";
                // Ensure item['unit_id'] is available. If getItemsForSale was not updated yet, this would fail.
                // Assuming getItemsForSale now provides unit_id for each item.
                if (empty($item['unit_id'])) {
                     $this->pdo->rollBack();
                     error_log("Unit ID missing for sale item ID {$item['id']} during sale deletion stock reversal. Cannot determine original unit.");
                     return "Erreur : unité manquante pour l'article ID {$item['id']} lors de l'annulation de la vente.";
                }

                if (!$productModel->updateStock(
                    (int)$item['product_id'],      // productId
                    'sale_reversal',              // movementType
                    (float)$item['quantity_sold'], // POSITIVE quantityInTransactionUnit
                    (int)$item['unit_id'],         // transactionUnitId (original unit from sale_items)
                    $item['id'],                  // relatedDocumentId (original sale_item_id)
                    'sale_items',                 // relatedDocumentType
                    $reversalNotes                // notes
                    )) {
                    $this->pdo->rollBack();
                    error_log("Failed to revert stock (create reversal movement) for product ID {$item['product_id']} during sale deletion.");
                    return false;
                }
            }

            // Delete sale items (ON DELETE CASCADE should handle this from schema)
            // If not: $this->db->delete("DELETE FROM sale_items WHERE sale_id = :sale_id", [':sale_id' => $saleId]);

            // Delete sale header
            $rowCount = $this->db->delete("DELETE FROM {$this->tableName} WHERE id = :id", [':id' => $saleId]);

            $this->pdo->commit();
            return $rowCount > 0;

        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error deleting sale ID {$saleId}: " . $e->getMessage());
            return false;
        }
    }

    // updateTotalAmount might be useful if sales can be edited post-creation.
    // For now, total_amount is calculated at creation.

    /**
     * Retrieves sales based on date range and other filters.
     * @param array $filters Filters: start_date, end_date, client_id, payment_status
     * @return array An array of sales.
     */
    public function getSalesByDateRangeAndFilters(array $filters) {
        $sql = "SELECT s.*, c.name as registered_client_name
                FROM {$this->tableName} s
                LEFT JOIN clients c ON s.client_id = c.id
                WHERE 1=1"; // Start with a true condition to easily append AND clauses

        $params = [];

        if (!empty($filters['start_date'])) {
            $sql .= " AND s.sale_date >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sql .= " AND s.sale_date <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }
        if (!empty($filters['client_id'])) {
            $sql .= " AND s.client_id = :client_id";
            $params[':client_id'] = $filters['client_id'];
        }
        if (!empty($filters['payment_status'])) {
            $sql .= " AND s.payment_status = :payment_status";
            $params[':payment_status'] = $filters['payment_status'];
        }
        // Could add filter for payment_type if needed

        $sql .= " ORDER BY s.sale_date DESC, s.id DESC";

        try {
            $sales = $this->db->select($sql, $params);
            foreach($sales as &$sale) { // Add display name logic
                $sale['client_display_name'] = $sale['client_id'] ? ($sale['registered_client_name'] . " (Reg.)") : ($sale['client_name_occasional'] . " (Occ.)");
            }
            return $sales;
        } catch (PDOException $e) {
            error_log("Error fetching sales by date range and filters: " . $e->getMessage());
            return [];
        }
    }
}
?>
