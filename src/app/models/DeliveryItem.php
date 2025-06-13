<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/Model.php';

class DeliveryItem extends Model {

    protected $tableName = 'delivery_items';

    public function __construct(Database $dbInstance) {
        parent::__construct($dbInstance);
    }

    /**
     * Creates a new delivery item.
     * Typically called from Delivery model's createDelivery.
     * @param array $data Item data (delivery_id, product_id, quantity_received, purchase_order_item_id)
     * @return string|false The ID of the newly created item or false on failure.
     */
    public function createItem(array $data) {
        if (empty($data['delivery_id']) || empty($data['product_id']) ||
            !isset($data['quantity_received']) || $data['quantity_received'] <= 0) {
            error_log("Missing required data or invalid values for delivery item.");
            return false;
        }

        $sql = "INSERT INTO {$this->tableName} (delivery_id, product_id, quantity_received, purchase_order_item_id)
                VALUES (:delivery_id, :product_id, :quantity_received, :purchase_order_item_id)";

        $params = [
            ':delivery_id' => $data['delivery_id'],
            ':product_id' => $data['product_id'],
            ':quantity_received' => $data['quantity_received'],
            ':purchase_order_item_id' => $data['purchase_order_item_id'] ?? null, // Can be null
        ];

        try {
            return $this->db->insert($sql, $params);
        } catch (PDOException $e) {
            error_log("Error creating delivery item: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieves items for a specific delivery.
     * @param int $deliveryId The ID of the delivery.
     * @return array An array of items.
     */
    public function getByDeliveryId($deliveryId) {
        $sql = "SELECT di.*, p.name as product_name, p.unit_of_measure, poi.quantity_ordered as original_quantity_ordered
                FROM {$this->tableName} di
                JOIN products p ON di.product_id = p.id
                LEFT JOIN purchase_order_items poi ON di.purchase_order_item_id = poi.id
                WHERE di.delivery_id = :delivery_id";
        try {
            return $this->db->select($sql, [':delivery_id' => $deliveryId]);
        } catch (PDOException $e) {
            error_log("Error fetching items for Delivery ID {$deliveryId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves a single delivery item by its ID.
     * @param int $id The ID of the item.
     * @return mixed Item data or false if not found.
     */
    public function getItemById($id) {
        $sql = "SELECT di.*, p.name as product_name
                FROM {$this->tableName} di
                JOIN products p ON di.product_id = p.id
                WHERE di.id = :id";
        try {
            $result = $this->db->select($sql, [':id' => $id]);
            return $result ? $result[0] : false;
        } catch (PDOException $e) {
            error_log("Error fetching Delivery Item by ID {$id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deletes all items for a given delivery ID.
     * @param int $deliveryId The Delivery ID.
     * @return int|false Number of affected rows or false on failure.
     */
    public function deleteItemsByDeliveryId($deliveryId) {
        $sql = "DELETE FROM {$this->tableName} WHERE delivery_id = :delivery_id";
        try {
            return $this->db->delete($sql, [':delivery_id' => $deliveryId]);
        } catch (PDOException $e) {
            error_log("Error deleting items for Delivery ID {$deliveryId}: " . $e->getMessage());
            return false;
        }
    }

    // Update and individual delete methods might not be strictly necessary if deliveries are immutable once created.
    // If they are, they would be similar to PurchaseOrderItem, but with stock implications.
}
?>
