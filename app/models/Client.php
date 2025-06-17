<?php

require_once ROOT_PATH . '/core/Model.php';

class Client extends Model {

    protected $tableName = 'clients';
    public $allowedClientTypes = ['connu', 'occasionnel'];

    public function __construct(Database $dbInstance) {
        parent::__construct($dbInstance);
    }

    /**
     * Creates a new client.
     * @param array $data Client data (name, client_type, email, phone, address)
     * @return string|false The ID of the newly created client or false on failure.
     */
    public function create(array $data) {
        // Basic validation
        if (empty($data['name'])) {
            error_log("Client name is required.");
            return false;
        }
        if (isset($data['email']) && !empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            error_log("Invalid email format for client.");
            return false;
        }
        if (isset($data['client_type']) && !in_array($data['client_type'], $this->allowedClientTypes)) {
            error_log("Invalid client type specified.");
            return false;
        }

        $fields = ['name', 'client_type', 'email', 'phone', 'address'];
        $params = [];
        $columns = [];
        $placeholders = [];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $columns[] = $field;
                $placeholders[] = ':' . $field;
                // Ensure empty email or phone is stored as NULL, not empty string, if DB allows NULL
                if (($field === 'email' || $field === 'phone' || $field === 'address' || $field === 'contact_person') && $data[$field] === '') {
                    $params[':' . $field] = null;
                } else {
                    $params[':' . $field] = $data[$field];
                }
            }
        }

        // Default client_type if not provided
        if (!isset($data['client_type'])) {
            $columns[] = 'client_type';
            $placeholders[] = ':client_type';
            $params[':client_type'] = 'connu'; // Default value
        }


        if (empty($columns)) return false;

        $sql = "INSERT INTO {$this->tableName} (" . implode(', ', $columns) . ", created_at, updated_at)
                VALUES (" . implode(', ', $placeholders) . ", CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";

        try {
            return $this->db->insert($sql, $params);
        } catch (PDOException $e) {
            if ($e->getCode() == '23505') { // Unique constraint violation (e.g., email)
                error_log("Error creating client: Email already exists. " . $e->getMessage());
            } else {
                error_log("Error creating client: " . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Retrieves all clients.
     * @return array An array of all clients.
     */
    public function getAll() {
        $sql = "SELECT * FROM {$this->tableName} ORDER BY name ASC";
        try {
            return $this->db->select($sql);
        } catch (PDOException $e) {
            error_log("Error fetching all clients: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves a single client by its ID.
     * @param int $id The ID of the client.
     * @return mixed The client data as an associative array or false if not found.
     */
    public function getById($id) {
        $sql = "SELECT * FROM {$this->tableName} WHERE id = :id";
        try {
            $result = $this->db->select($sql, [':id' => $id]);
            return $result ? $result[0] : false;
        } catch (PDOException $e) {
            error_log("Error fetching client by ID {$id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Updates an existing client.
     * @param int $id The ID of the client to update.
     * @param array $data Client data to update.
     * @return int|false The number of affected rows or false on failure.
     */
    public function update($id, array $data) {
        if (empty($data)) {
            error_log("No data provided for client update.");
            return false;
        }
        if (isset($data['email']) && !empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            error_log("Invalid email format for client update.");
            return false;
        }
        if (isset($data['client_type']) && !in_array($data['client_type'], $this->allowedClientTypes)) {
            error_log("Invalid client type specified for update.");
            return false;
        }

        $fields = ['name', 'client_type', 'email', 'phone', 'address'];
        $params = [':id' => $id];
        $setParts = [];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $setParts[] = "{$field} = :{$field}";
                 if (($field === 'email' || $field === 'phone' || $field === 'address' || $field === 'contact_person') && $data[$field] === '') {
                    $params[':' . $field] = null;
                } else {
                    $params[':' . $field] = $data[$field];
                }
            }
        }

        $setParts[] = "updated_at = CURRENT_TIMESTAMP";

        if (count($setParts) <= 1) return false; // Only updated_at, no actual field to update

        $sql = "UPDATE {$this->tableName} SET " . implode(', ', $setParts) . " WHERE id = :id";

        try {
            return $this->db->update($sql, $params);
        } catch (PDOException $e) {
             if ($e->getCode() == '23505') {
                error_log("Error updating client: Email already exists. " . $e->getMessage());
            } else {
                error_log("Error updating client ID {$id}: " . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Deletes a client by its ID.
     * @param int $id The ID of the client to delete.
     * @return int|false The number of affected rows or false on failure.
     */
    public function delete($id) {
        // Consider potential foreign key constraints if clients are linked to other tables (e.g., sales)
        $sql = "DELETE FROM {$this->tableName} WHERE id = :id";
        try {
            return $this->db->delete($sql, [':id' => $id]);
        } catch (PDOException $e) {
            // Handle potential foreign key constraint violations (e.g., SQLSTATE 23503)
            if ($e->getCode() == '23503') {
                 error_log("Error deleting client ID {$id}: Client is associated with other records (e.g., sales) and cannot be deleted. " . $e->getMessage());
            } else {
                error_log("Error deleting client ID {$id}: " . $e->getMessage());
            }
            return false;
        }
    }
}
?>
