<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/Model.php';

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
                if (empty($item['product_id']) || empty($item['quantity_sold']) || $item['quantity_sold'] <= 0 || !isset($item['unit_price']) || $item['unit_price'] < 0) {
                    $this->pdo->rollBack();
                    error_log("Invalid item data: product_id, positive quantity_sold, and non-negative unit_price are required.");
                    return false;
                }
                $product = $productModel->getById($item['product_id']);
                if (!$product || $product['quantity_in_stock'] < $item['quantity_sold']) {
                    $this->pdo->rollBack();
                    error_log("Insufficient stock for product ID {$item['product_id']} (Name: {$product['name']}). Requested: {$item['quantity_sold']}, Available: {$product['quantity_in_stock']}.");
                    return "Insufficient stock for product: " . ($product ? $product['name'] : "ID ".$item['product_id']); // Return specific error message
                }
            }

            // Calculate total_amount from items
            $totalAmount = 0;
            foreach($itemsData as $item){
                $totalAmount += $item['quantity_sold'] * $item['unit_price'];
            }
            $data['total_amount'] = $totalAmount;

            // Prepare Sale header fields
            $saleFields = ['client_id', 'client_name_occasional', 'sale_date', 'total_amount',
                           'payment_status', 'payment_type', 'due_date', 'notes'];
            $saleParams = [];
            $saleColumns = [];
            $salePlaceholders = [];

            foreach ($saleFields as $field) {
                if (isset($data[$field])) {
                    $saleColumns[] = $field;
                    $salePlaceholders[] = ':' . $field;
                    $saleParams[':' . $field] = ($data[$field] === '' && in_array($field, ['client_id', 'client_name_occasional', 'due_date', 'notes'])) ? null : $data[$field];
                }
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
            $sqlItem = "INSERT INTO sale_items (sale_id, product_id, quantity_sold, unit_price)
                        VALUES (:sale_id, :product_id, :quantity_sold, :unit_price)";

            foreach ($itemsData as $item) {
                $itemInsertParams = [
                    ':sale_id' => $saleId,
                    ':product_id' => $item['product_id'],
                    ':quantity_sold' => $item['quantity_sold'],
                    ':unit_price' => $item['unit_price']
                ];
                // $this->db->executeQuery($sqlItem, $itemParams); // Old way
                $saleItemId = $this->db->insert($sqlItem, $itemInsertParams);

                if (!$saleItemId) {
                    $this->pdo->rollBack();
                    error_log("Failed to create sale item for product ID {$item['product_id']}.");
                    return false;
                }

                // Decrease product stock and create stock movement
                $notes = "Sold via SA-{$saleId}";
                if (!$productModel->updateStock(
                    $item['product_id'],
                    -$item['quantity_sold'], // Negative for decrease
                    'out_sale',
                    $saleItemId,
                    'sale_items',
                    $notes
                )) {
                    $this->pdo->rollBack();
                    error_log("Failed to update stock or create movement for product ID {$item['product_id']} during sale.");
                    // Consider if a more specific error message should be returned to controller
                    return false;
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
        $sql = "SELECT si.*, p.name as product_name, p.unit_of_measure
                FROM sale_items si
                JOIN products p ON si.product_id = p.id
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
                if (!$productModel->updateStock(
                    $item['product_id'],
                    $item['quantity_sold'], // Positive to add back
                    'sale_reversal',
                    $item['id'], // Reference original sale_item_id
                    'sale_items', // Document type that was reversed
                    $reversalNotes
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
