<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/Model.php';
// Placeholder for PurchaseOrderItem, might be expanded or kept simple
// require_once $_SERVER['DOCUMENT_ROOT'] . '/PurchaseOrderItem.php';

class PurchaseOrder extends Model {

    protected $tableName = 'purchase_orders';
    public $allowedStatuses = ['pending', 'partially_received', 'received', 'cancelled'];

    public function __construct(Database $dbInstance) {
        parent::__construct($dbInstance);
    }

    /**
     * Creates a new purchase order along with its items.
     * @param array $data Purchase order data (supplier_id, order_date, etc.)
     * @param array $itemsData Array of items (product_id, quantity_ordered, unit_price)
     * @return string|false The ID of the newly created purchase order or false on failure.
     */
    public function createOrder(array $data, array $itemsData) {
        // Validate PO data
        if (empty($data['supplier_id']) || empty($data['order_date'])) {
            error_log("Supplier ID and Order Date are required for Purchase Order.");
            return false;
        }
        if (isset($data['status']) && !in_array($data['status'], $this->allowedStatuses)) {
            error_log("Invalid status for Purchase Order.");
            return false;
        }
        if (empty($itemsData)) {
            error_log("Purchase Order must have at least one item.");
            return false;
        }

        // Calculate initial total_amount (can be 0 and updated by a trigger or later logic too)
        $totalAmount = 0;
        foreach ($itemsData as $item) {
            if (!isset($item['quantity_ordered']) || !isset($item['unit_price']) || $item['quantity_ordered'] <= 0 || $item['unit_price'] < 0) {
                error_log("Invalid item data: quantity and unit price must be positive.");
                return false;
            }
            $totalAmount += $item['quantity_ordered'] * $item['unit_price'];
        }
        $data['total_amount'] = $totalAmount;

        // Prepare PO fields
        $poFields = ['supplier_id', 'order_date', 'expected_delivery_date', 'status', 'total_amount', 'notes'];
        $poParams = [];
        $poColumns = [];
        $poPlaceholders = [];

        foreach ($poFields as $field) {
            if (isset($data[$field])) {
                $poColumns[] = $field;
                $poPlaceholders[] = ':' . $field;
                $poParams[':' . $field] = ($data[$field] === '' && ($field === 'expected_delivery_date' || $field === 'notes')) ? null : $data[$field];
            }
        }
        if (!isset($data['status'])) { // Default status
            $poColumns[] = 'status';
            $poPlaceholders[] = ':status';
            $poParams[':status'] = 'pending';
        }


        $this->pdo->beginTransaction();

        try {
            // Insert Purchase Order
            $sqlPo = "INSERT INTO {$this->tableName} (" . implode(', ', $poColumns) . ", created_at, updated_at)
                      VALUES (" . implode(', ', $poPlaceholders) . ", CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
            $poId = $this->db->insert($sqlPo, $poParams);

            if (!$poId) {
                $this->pdo->rollBack();
                error_log("Failed to create purchase order header.");
                return false;
            }

            // Insert Purchase Order Items
            $sqlItem = "INSERT INTO purchase_order_items (purchase_order_id, product_id, quantity_ordered, unit_price)
                        VALUES (:purchase_order_id, :product_id, :quantity_ordered, :unit_price)";

            foreach ($itemsData as $item) {
                if (empty($item['product_id'])) { // Basic item validation
                     $this->pdo->rollBack();
                     error_log("Product ID is missing for an item.");
                     return false;
                }
                $itemParams = [
                    ':purchase_order_id' => $poId,
                    ':product_id' => $item['product_id'],
                    ':quantity_ordered' => $item['quantity_ordered'],
                    ':unit_price' => $item['unit_price']
                ];
                $this->db->executeQuery($sqlItem, $itemParams);
            }

            $this->pdo->commit();
            return $poId;

        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error creating purchase order with items: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieves a single purchase order by its ID, including its items.
     * @param int $id The ID of the purchase order.
     * @return mixed The PO data with items, or false if not found.
     */
    public function getByIdWithItems($id) {
        $po = $this->getById($id);
        if (!$po) {
            return false;
        }
        $po['items'] = $this->getItemsForPo($id);
        return $po;
    }

    /**
     * Retrieves a single purchase order by its ID (header only).
     * @param int $id The ID of the purchase order.
     * @return mixed The PO data or false if not found.
     */
    public function getById($id) {
        $sql = "SELECT po.*, s.name as supplier_name
                FROM {$this->tableName} po
                JOIN suppliers s ON po.supplier_id = s.id
                WHERE po.id = :id";
        try {
            $result = $this->db->select($sql, [':id' => $id]);
            return $result ? $result[0] : false;
        } catch (PDOException $e) {
            error_log("Error fetching PO by ID {$id}: " . $e->getMessage());
            return false;
        }
    }


    /**
     * Retrieves all items for a given purchase order ID.
     * @param int $poId The Purchase Order ID.
     * @return array Array of items.
     */
    public function getItemsForPo($poId) {
        $sql = "SELECT poi.*, p.name as product_name, p.unit_of_measure
                FROM purchase_order_items poi
                JOIN products p ON poi.product_id = p.id
                WHERE poi.purchase_order_id = :po_id";
        try {
            return $this->db->select($sql, [':po_id' => $poId]);
        } catch (PDOException $e) {
            error_log("Error fetching items for PO ID {$poId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves all purchase orders with basic supplier info.
     * @return array An array of all purchase orders.
     */
    public function getAllWithSupplier() {
        $sql = "SELECT po.*, s.name as supplier_name
                FROM {$this->tableName} po
                JOIN suppliers s ON po.supplier_id = s.id
                ORDER BY po.order_date DESC, po.id DESC";
        try {
            return $this->db->select($sql);
        } catch (PDOException $e) {
            error_log("Error fetching all purchase orders: " . $e->getMessage());
            return [];
        }
    }


    /**
     * Updates an existing purchase order and its items.
     * This can be complex: handling added, removed, or updated items.
     * For simplicity, this example focuses on updating PO header fields and recalculating total.
     * A more robust version would manage item changes individually.
     * @param int $id The ID of the PO to update.
     * @param array $data PO header data.
     * @param array $itemsData (Optional) New set of items. If provided, old items are replaced.
     * @return bool True on success, false on failure.
     */
    public function updateOrder($id, array $data, array $itemsData = null) {
        if (empty($data) && $itemsData === null) {
            error_log("No data provided for PO update.");
            return false;
        }

        if (isset($data['status']) && !in_array($data['status'], $this->allowedStatuses)) {
            error_log("Invalid status for Purchase Order update.");
            return false;
        }

        $this->pdo->beginTransaction();
        try {
            // Update PO Header
            if (!empty($data)) {
                $poFields = ['supplier_id', 'order_date', 'expected_delivery_date', 'status', 'notes'];
                $poParams = [':id' => $id];
                $setParts = [];

                foreach ($poFields as $field) {
                    if (array_key_exists($field, $data)) { // Use array_key_exists for nullable fields
                        $setParts[] = "{$field} = :{$field}";
                        $poParams[':' . $field] = ($data[$field] === '' && ($field === 'expected_delivery_date' || $field === 'notes')) ? null : $data[$field];
                    }
                }

                if (!empty($setParts)) {
                    // total_amount will be recalculated if items change or can be set explicitly if needed
                    if (isset($data['total_amount'])) { // Allow explicit total_amount override if necessary
                         $setParts[] = "total_amount = :total_amount";
                         $poParams[':total_amount'] = $data['total_amount'];
                    }

                    $sqlPoUpdate = "UPDATE {$this->tableName} SET " . implode(', ', $setParts) . ", updated_at = CURRENT_TIMESTAMP WHERE id = :id";
                    $this->db->update($sqlPoUpdate, $poParams);
                }
            }

            // If itemsData is provided, replace existing items
            if ($itemsData !== null) {
                // Delete existing items for this PO
                $this->db->delete("DELETE FROM purchase_order_items WHERE purchase_order_id = :po_id", [':po_id' => $id]);

                // Add new items
                $newTotalAmount = 0;
                $sqlItem = "INSERT INTO purchase_order_items (purchase_order_id, product_id, quantity_ordered, unit_price)
                            VALUES (:purchase_order_id, :product_id, :quantity_ordered, :unit_price)";
                foreach ($itemsData as $item) {
                     if (empty($item['product_id']) || !isset($item['quantity_ordered']) || !isset($item['unit_price']) || $item['quantity_ordered'] <= 0 || $item['unit_price'] < 0) {
                        $this->pdo->rollBack();
                        error_log("Invalid item data during update: product_id, quantity and unit price must be valid.");
                        return false;
                    }
                    $itemParams = [
                        ':purchase_order_id' => $id,
                        ':product_id' => $item['product_id'],
                        ':quantity_ordered' => $item['quantity_ordered'],
                        ':unit_price' => $item['unit_price']
                    ];
                    $this->db->executeQuery($sqlItem, $itemParams);
                    $newTotalAmount += $item['quantity_ordered'] * $item['unit_price'];
                }
                // Update PO total_amount based on new items
                $this->db->update("UPDATE {$this->tableName} SET total_amount = :total_amount, updated_at = CURRENT_TIMESTAMP WHERE id = :id", [':total_amount' => $newTotalAmount, ':id' => $id]);
            } elseif (empty($data) && $itemsData === null) { // No header data and no items means nothing to update
                 $this->pdo->rollBack(); // Nothing was changed but to be safe.
                 return true; // Or false, depending on desired behavior for no-op.
            }


            $this->pdo->commit();
            return true;

        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error updating purchase order ID {$id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deletes a purchase order (which also deletes its items due to ON DELETE CASCADE).
     * @param int $id The ID of the PO to delete.
     * @return bool True on success, false on failure.
     */
    public function deleteOrder($id) {
        // Check if PO is cancellable (e.g., not already received or partially received if strict)
        $po = $this->getById($id);
        if (!$po) return false; // Not found

        // Potentially add more business logic here, e.g., cannot delete 'received' orders.
        // For now, we allow deletion, relying on ON DELETE CASCADE for items.
        // ON DELETE RESTRICT for supplier/product links will prevent deletion if referenced elsewhere in a way that matters.

        try {
            $rowCount = $this->db->delete("DELETE FROM {$this->tableName} WHERE id = :id", [':id' => $id]);
            return $rowCount > 0;
        } catch (PDOException $e) {
            error_log("Error deleting purchase order ID {$id}: " . $e->getMessage());
            // Check for FK constraint if not handled by ON DELETE RESTRICT/CASCADE as expected
            if ($e->getCode() == '23503') {
                 error_log("Cannot delete PO ID {$id} as it's referenced by other records (e.g. deliveries, invoices not set to NULL).");
            }
            return false;
        }
    }

    /**
     * Updates the total amount for a purchase order.
     * Typically called after items are added/removed/updated.
     * @param int $poId The Purchase Order ID.
     * @return bool True on success, false on failure.
     */
    public function updateTotalAmount($poId) {
        $sql = "SELECT SUM(sub_total) as new_total
                FROM purchase_order_items
                WHERE purchase_order_id = :po_id";
        try {
            $result = $this->db->select($sql, [':po_id' => $poId]);
            $newTotal = $result && isset($result[0]['new_total']) ? $result[0]['new_total'] : 0.00;

            $updateSql = "UPDATE {$this->tableName} SET total_amount = :total_amount, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
            $this->db->update($updateSql, [':total_amount' => $newTotal, ':id' => $poId]);
            return true;
        } catch (PDOException $e) {
            error_log("Error updating total amount for PO ID {$poId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Updates the status of a purchase order.
     * @param int $poId The Purchase Order ID.
     * @param string $newStatus The new status.
     * @return bool True on success, false on failure.
     */
    public function updateStatus($poId, $newStatus) {
        if (!in_array($newStatus, $this->allowedStatuses)) {
            error_log("Invalid status '{$newStatus}' for PO ID {$poId}.");
            return false;
        }
        $sql = "UPDATE {$this->tableName} SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        try {
            $this->db->update($sql, [':status' => $newStatus, ':id' => $poId]);
            return true;
        } catch (PDOException $e) {
            error_log("Error updating status for PO ID {$poId}: " . $e->getMessage());
            return false;
        }
    }


    /**
     * Retrieves purchase orders based on date range and other filters.
     * @param array $filters Filters: start_date, end_date, supplier_id, status
     * @return array An array of purchase orders.
     */
    public function getPurchaseOrdersByDateRangeAndFilters(array $filters) {
        $sql = "SELECT po.*, s.name as supplier_name
                FROM {$this->tableName} po
                JOIN suppliers s ON po.supplier_id = s.id
                WHERE 1=1"; // Start with a true condition

        $params = [];

        if (!empty($filters['start_date'])) {
            $sql .= " AND po.order_date >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sql .= " AND po.order_date <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }
        if (!empty($filters['supplier_id'])) {
            $sql .= " AND po.supplier_id = :supplier_id";
            $params[':supplier_id'] = $filters['supplier_id'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND po.status = :status";
            $params[':status'] = $filters['status'];
        }

        $sql .= " ORDER BY po.order_date DESC, po.id DESC";

        try {
            return $this->db->select($sql, $params);
        } catch (PDOException $e) {
            error_log("Error fetching purchase orders by date range and filters: " . $e->getMessage());
            return [];
        }
    }
}
?>
