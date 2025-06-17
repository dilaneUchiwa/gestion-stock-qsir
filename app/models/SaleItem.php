<?php

require_once ROOT_PATH . '/core/Model.php';

class SaleItem extends Model {

    protected $tableName = 'sale_items';
    // sub_total is a generated column in PostgreSQL.

    public function __construct(Database $dbInstance) {
        parent::__construct($dbInstance);
    }

    /**
     * Creates a new sale item.
     * Typically called from Sale model's createSale.
     * @param array $data Item data (sale_id, product_id, quantity_sold, unit_price)
     * @return string|false The ID of the newly created item or false on failure.
     */
    public function createItem(array $data) {
        if (empty($data['sale_id']) || empty($data['product_id']) ||
            !isset($data['quantity_sold']) || $data['quantity_sold'] <= 0 ||
            !isset($data['unit_price']) || $data['unit_price'] < 0) {
            error_log("Missing required data or invalid values for sale item.");
            return false;
        }

        $sql = "INSERT INTO {$this->tableName} (sale_id, product_id, quantity_sold, unit_price)
                VALUES (:sale_id, :product_id, :quantity_sold, :unit_price)";

        $params = [
            ':sale_id' => $data['sale_id'],
            ':product_id' => $data['product_id'],
            ':quantity_sold' => $data['quantity_sold'],
            ':unit_price' => $data['unit_price']
        ];

        try {
            return $this->db->insert($sql, $params);
        } catch (PDOException $e) {
            error_log("Error creating sale item: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieves items for a specific sale.
     * @param int $saleId The ID of the sale.
     * @return array An array of items.
     */
    public function getBySaleId($saleId) {
        $sql = "SELECT si.*, p.name as product_name, p.unit_of_measure
                FROM {$this->tableName} si
                JOIN products p ON si.product_id = p.id
                WHERE si.sale_id = :sale_id";
        try {
            return $this->db->select($sql, [':sale_id' => $saleId]);
        } catch (PDOException $e) {
            error_log("Error fetching items for Sale ID {$saleId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves a single sale item by its ID.
     * @param int $id The ID of the item.
     * @return mixed Item data or false if not found.
     */
    public function getItemById($id) {
        $sql = "SELECT si.*, p.name as product_name
                FROM {$this->tableName} si
                JOIN products p ON si.product_id = p.id
                WHERE si.id = :id";
        try {
            $result = $this->db->select($sql, [':id' => $id]);
            return $result ? $result[0] : false;
        } catch (PDOException $e) {
            error_log("Error fetching Sale Item by ID {$id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deletes all items for a given sale ID.
     * @param int $saleId The Sale ID.
     * @return int|false Number of affected rows or false on failure.
     */
    public function deleteItemsBySaleId($saleId) {
        $sql = "DELETE FROM {$this->tableName} WHERE sale_id = :sale_id";
        try {
            return $this->db->delete($sql, [':sale_id' => $saleId]);
        } catch (PDOException $e) {
            error_log("Error deleting items for Sale ID {$saleId}: " . $e->getMessage());
            return false;
        }
    }

    // Update and individual delete methods might not be strictly necessary if sales are immutable once created.
    // If sales are editable (e.g., before final validation/payment), these would be needed,
    // and would also need to trigger stock recalculations/adjustments and Sale total_amount updates.
}
?>
