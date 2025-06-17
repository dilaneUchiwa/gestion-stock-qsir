<?php

require_once ROOT_PATH . '/core/Model.php';

class SupplierCategory extends Model {

    protected $tableName = 'supplier_categories';

    public function __construct(Database $dbInstance) {
        parent::__construct($dbInstance);
    }

    /**
     * Retrieves all supplier categories.
     * @return array An array of all supplier categories.
     */
    public function getAll() {
        $sql = "SELECT * FROM {$this->tableName} ORDER BY name ASC";
        try {
            return $this->db->select($sql);
        } catch (PDOException $e) {
            error_log("Error fetching all supplier categories: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves a single supplier category by its ID.
     * @param int $id The ID of the supplier category.
     * @return mixed The category data or false if not found.
     */
    public function getById($id) {
        $sql = "SELECT * FROM {$this->tableName} WHERE id = :id";
        try {
            $result = $this->db->select($sql, [':id' => $id]);
            return $result ? $result[0] : false;
        } catch (PDOException $e) {
            error_log("Error fetching supplier category by ID {$id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Creates a new supplier category.
     * @param array $data Category data (name, description).
     * @return string|false The ID of the newly created category or false on failure.
     */
    public function create(array $data) {
        if (empty($data['name'])) {
            error_log("Supplier category name is required.");
            return false;
        }

        $sql = "INSERT INTO {$this->tableName} (name, description, created_at, updated_at)
                VALUES (:name, :description, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";

        $params = [
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null
        ];

        try {
            return $this->db->insert($sql, $params);
        } catch (PDOException $e) {
            error_log("Error creating supplier category: " . $e->getMessage());
            if ($e->getCode() == '23505') { // Unique constraint violation
                return false;
            }
            return false;
        }
    }

    /**
     * Updates an existing supplier category.
     * @param int $id The ID of the category to update.
     * @param array $data Category data to update.
     * @return int|false The number of affected rows or false on failure.
     */
    public function update($id, array $data) {
        if (empty($data['name'])) {
            error_log("Supplier category name is required for update.");
            return false;
        }

        $sql = "UPDATE {$this->tableName}
                SET name = :name, description = :description, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";

        $params = [
            ':id' => $id,
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null
        ];

        try {
            return $this->db->update($sql, $params);
        } catch (PDOException $e) {
            error_log("Error updating supplier category ID {$id}: " . $e->getMessage());
             if ($e->getCode() == '23505') {
                return false;
            }
            return false;
        }
    }

    /**
     * Deletes a supplier category by its ID.
     * Relies on ON DELETE SET NULL for suppliers.supplier_category_id.
     * @param int $id The ID of the category to delete.
     * @return int|false The number of affected rows or false on failure.
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->tableName} WHERE id = :id";
        try {
            return $this->db->delete($sql, [':id' => $id]);
        } catch (PDOException $e) {
            error_log("Error deleting supplier category ID {$id}: " . $e->getMessage());
            return false;
        }
    }
}
?>
