<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/Model.php';

class Supplier extends Model {

    protected $tableName = 'suppliers';

    public function __construct(Database $dbInstance) {
        parent::__construct($dbInstance);
    }

    /**
     * Creates a new supplier.
     * @param array $data Supplier data (name, contact_person, email, phone, address)
     * @return string|false The ID of the newly created supplier or false on failure.
     */
    public function create(array $data) {
        // Basic validation
        if (empty($data['name']) || empty($data['email'])) {
            error_log("Supplier name and email are required.");
            return false;
        }
        // Validate email format (basic)
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            error_log("Invalid email format for supplier.");
            return false;
        }


        $fields = ['name', 'contact_person', 'email', 'phone', 'address'];
        $params = [];
        $columns = [];
        $placeholders = [];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $columns[] = $field;
                $placeholders[] = ':' . $field;
                $params[':' . $field] = $data[$field] ?: null; // Store empty strings as NULL for optional fields
            }
        }

        if (empty($columns)) return false;

        $sql = "INSERT INTO {$this->tableName} (" . implode(', ', $columns) . ", created_at, updated_at)
                VALUES (" . implode(', ', $placeholders) . ", CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";

        try {
            return $this->db->insert($sql, $params);
        } catch (PDOException $e) {
            // Check for unique constraint violation (e.g., email)
            if ($e->getCode() == '23505') { // Standard SQLSTATE for unique violation
                error_log("Error creating supplier: Email already exists. " . $e->getMessage());
            } else {
                error_log("Error creating supplier: " . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Retrieves all suppliers.
     * @return array An array of all suppliers.
     */
    public function getAll() {
        $sql = "SELECT * FROM {$this->tableName} ORDER BY name ASC";
        try {
            return $this->db->select($sql);
        } catch (PDOException $e) {
            error_log("Error fetching all suppliers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves a single supplier by its ID.
     * @param int $id The ID of the supplier.
     * @return mixed The supplier data as an associative array or false if not found.
     */
    public function getById($id) {
        $sql = "SELECT * FROM {$this->tableName} WHERE id = :id";
        try {
            $result = $this->db->select($sql, [':id' => $id]);
            return $result ? $result[0] : false;
        } catch (PDOException $e) {
            error_log("Error fetching supplier by ID {$id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Updates an existing supplier.
     * @param int $id The ID of the supplier to update.
     * @param array $data Supplier data to update.
     * @return int|false The number of affected rows or false on failure.
     */
    public function update($id, array $data) {
        if (empty($data)) {
            error_log("No data provided for supplier update.");
            return false;
        }
         // Validate email format if provided
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            error_log("Invalid email format for supplier update.");
            return false;
        }

        $fields = ['name', 'contact_person', 'email', 'phone', 'address'];
        $params = [':id' => $id];
        $setParts = [];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $setParts[] = "{$field} = :{$field}";
                $params[':' . $field] = $data[$field] ?: null;
            }
        }

        // Ensure updated_at is always updated
        $setParts[] = "updated_at = CURRENT_TIMESTAMP";

        if (count($setParts) <= 1) return false; // Only updated_at, no actual field to update

        $sql = "UPDATE {$this->tableName} SET " . implode(', ', $setParts) . " WHERE id = :id";

        try {
            return $this->db->update($sql, $params);
        } catch (PDOException $e) {
             if ($e->getCode() == '23505') {
                error_log("Error updating supplier: Email already exists. " . $e->getMessage());
            } else {
                error_log("Error updating supplier ID {$id}: " . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Deletes a supplier by its ID.
     * @param int $id The ID of the supplier to delete.
     * @return int|false The number of affected rows or false on failure.
     */
    public function delete($id) {
        // Consider checking for related records (e.g., purchase orders) before deleting
        $sql = "DELETE FROM {$this->tableName} WHERE id = :id";
        try {
            return $this->db->delete($sql, [':id' => $id]);
        } catch (PDOException $e) {
            error_log("Error deleting supplier ID {$id}: " . $e->getMessage());
            return false;
        }
    }
}
?>
