<?php

require_once ROOT_PATH . '/core/Model.php';

class ClientCategory extends Model {

    protected $tableName = 'client_categories';

    public function __construct(Database $dbInstance) {
        parent::__construct($dbInstance);
    }

    /**
     * Retrieves all client categories.
     * @return array An array of all client categories.
     */
    public function getAll() {
        $sql = "SELECT * FROM {$this->tableName} ORDER BY name ASC";
        try {
            return $this->db->select($sql);
        } catch (PDOException $e) {
            error_log("Error fetching all client categories: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves a single client category by its ID.
     * @param int $id The ID of the client category.
     * @return mixed The category data or false if not found.
     */
    public function getById($id) {
        $sql = "SELECT * FROM {$this->tableName} WHERE id = :id";
        try {
            $result = $this->db->select($sql, [':id' => $id]);
            return $result ? $result[0] : false;
        } catch (PDOException $e) {
            error_log("Error fetching client category by ID {$id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Creates a new client category.
     * @param array $data Category data (name, description).
     * @return string|false The ID of the newly created category or false on failure.
     */
    public function create(array $data) {
        if (empty($data['name'])) {
            error_log("Client category name is required.");
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
            error_log("Error creating client category: " . $e->getMessage());
            // Check for unique constraint violation (code 23505 for PostgreSQL)
            if ($e->getCode() == '23505') {
                // Consider throwing a specific exception or returning a specific error indicator
                return false;
            }
            return false;
        }
    }

    /**
     * Updates an existing client category.
     * @param int $id The ID of the category to update.
     * @param array $data Category data to update.
     * @return int|false The number of affected rows or false on failure.
     */
    public function update($id, array $data) {
        if (empty($data['name'])) {
            error_log("Client category name is required for update.");
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
            error_log("Error updating client category ID {$id}: " . $e->getMessage());
            if ($e->getCode() == '23505') {
                return false;
            }
            return false;
        }
    }

    /**
     * Deletes a client category by its ID.
     * Relies on ON DELETE SET NULL for clients.client_category_id.
     * @param int $id The ID of the category to delete.
     * @return int|false The number of affected rows or false on failure.
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->tableName} WHERE id = :id";
        try {
            return $this->db->delete($sql, [':id' => $id]);
        } catch (PDOException $e) {
            error_log("Error deleting client category ID {$id}: " . $e->getMessage());
            // If foreign key constraint (not ON DELETE SET NULL) was in place, it would error here.
            // With ON DELETE SET NULL, this should generally succeed unless other issues.
            return false;
        }
    }
}
?>
