<?php

require_once ROOT_PATH . '/core/Model.php';

class PurchaseOrderItem extends Model {

    protected $tableName = 'purchase_order_items';

    // Note: The sub_total is a generated column in PostgreSQL based on the schema provided.
    // So, direct insert/update of sub_total is not needed/allowed.
    // It will be automatically calculated by the database.

    public function __construct(Database $dbInstance) {
        parent::__construct($dbInstance);
    }

    /**
     * Creates a new purchase order item.
     * Typically called from PurchaseOrder model's createOrder or updateOrder methods.
     * @param array $data Item data (purchase_order_id, product_id, quantity_ordered, unit_price)
     * @return string|false The ID of the newly created item or false on failure.
     */
    public function createItem(array $data) {
        if (empty($data['purchase_order_id']) || empty($data['product_id']) ||
            !isset($data['quantity_ordered']) || !isset($data['unit_price']) ||
            $data['quantity_ordered'] <= 0 || $data['unit_price'] < 0) {
            error_log("Missing required data or invalid values for purchase order item.");
            return false;
        }

        $sql = "INSERT INTO {$this->tableName} (purchase_order_id, product_id, quantity_ordered, unit_price)
                VALUES (:purchase_order_id, :product_id, :quantity_ordered, :unit_price)";

        $params = [
            ':purchase_order_id' => $data['purchase_order_id'],
            ':product_id' => $data['product_id'],
            ':quantity_ordered' => $data['quantity_ordered'],
            ':unit_price' => $data['unit_price']
        ];

        try {
            return $this->db->insert($sql, $params);
        } catch (PDOException $e) {
            error_log("Error creating purchase order item: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieves items for a specific purchase order.
     * @param int $purchaseOrderId The ID of the purchase order.
     * @return array An array of items.
     */
    public function getByPoId($purchaseOrderId) {
        $sql = "SELECT poi.*, p.name as product_name
                FROM {$this->tableName} poi
                JOIN products p ON poi.product_id = p.id
                WHERE poi.purchase_order_id = :purchase_order_id";
        try {
            return $this->db->select($sql, [':purchase_order_id' => $purchaseOrderId]);
        } catch (PDOException $e) {
            error_log("Error fetching items for PO ID {$purchaseOrderId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves a single purchase order item by its ID.
     * @param int $id The ID of the item.
     * @return mixed Item data or false if not found.
     */
    public function getItemById($id) {
        $sql = "SELECT poi.*, p.name as product_name
                FROM {$this->tableName} poi
                JOIN products p ON poi.product_id = p.id
                WHERE poi.id = :id";
        try {
            $result = $this->db->select($sql, [':id' => $id]);
            return $result ? $result[0] : false;
        } catch (PDOException $e) {
            error_log("Error fetching PO item by ID {$id}: " . $e->getMessage());
            return false;
        }
    }


    /**
     * Updates a purchase order item.
     * @param int $id Item ID.
     * @param array $data Data to update (quantity_ordered, unit_price).
     * @return int|false Number of affected rows or false on failure.
     */
    public function updateItem($id, array $data) {
        $fields = [];
        $params = [':id' => $id];

        if (isset($data['quantity_ordered'])) {
            if ($data['quantity_ordered'] <= 0) {
                error_log("Quantity ordered must be positive."); return false;
            }
            $fields[] = "quantity_ordered = :quantity_ordered";
            $params[':quantity_ordered'] = $data['quantity_ordered'];
        }
        if (isset($data['unit_price'])) {
            if ($data['unit_price'] < 0) {
                 error_log("Unit price cannot be negative."); return false;
            }
            $fields[] = "unit_price = :unit_price";
            $params[':unit_price'] = $data['unit_price'];
        }
        // product_id could also be updatable if business logic allows

        if (empty($fields)) {
            return false; // Nothing to update
        }

        $sql = "UPDATE {$this->tableName} SET " . implode(', ', $fields) . " WHERE id = :id";
        try {
            $affectedRows = $this->db->update($sql, $params);
            // After item update, the parent PO's total_amount should be recalculated.
            // This logic is better placed in the PurchaseOrder model or triggered.
            // For now, assume PurchaseOrder model handles calling its updateTotalAmount.
            return $affectedRows;
        } catch (PDOException $e) {
            error_log("Error updating PO item ID {$id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deletes a purchase order item.
     * @param int $id Item ID.
     * @return int|false Number of affected rows or false on failure.
     */
    public function deleteItem($id) {
         // After item deletion, the parent PO's total_amount should be recalculated.
         // This logic is better placed in the PurchaseOrder model.
        $sql = "DELETE FROM {$this->tableName} WHERE id = :id";
        try {
            return $this->db->delete($sql, [':id' => $id]);
        } catch (PDOException $e) {
            error_log("Error deleting PO item ID {$id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deletes all items for a given purchase order ID.
     * Typically used when replacing all items of a PO.
     * @param int $purchaseOrderId The Purchase Order ID.
     * @return int|false Number of affected rows or false on failure.
     */
    public function deleteItemsByPoId($purchaseOrderId) {
        $sql = "DELETE FROM {$this->tableName} WHERE purchase_order_id = :purchase_order_id";
        try {
            return $this->db->delete($sql, [':purchase_order_id' => $purchaseOrderId]);
        } catch (PDOException $e) {
            error_log("Error deleting items for PO ID {$purchaseOrderId}: " . $e->getMessage());
            return false;
        }
    }
}
?>
