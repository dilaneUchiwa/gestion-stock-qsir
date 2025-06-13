<?php

class Model {
    protected $db; // Holds the Database class instance
    protected $pdo; // Holds the PDO connection object

    /**
     * Constructor for the base Model.
     * Expects an instance of the Database class.
     *
     * @param Database $dbInstance An instance of the Database class.
     */
    public function __construct(Database $dbInstance) {
        $this->db = $dbInstance;
        $this->pdo = $this->db->getConnection(); // Get the PDO connection from Database instance
    }

    /**
     * Example common method that could be in a base model.
     * For instance, a method to find a record by ID, assuming a common 'id' field.
     * This is just a placeholder and might need adjustment based on actual table structures.
     *
     * @param string $tableName The name of the table.
     * @param int $id The ID of the record to find.
     * @return mixed The record if found, or false otherwise.
     */
    public function findById($tableName, $id) {
        // Ensure table name is safe to use in a query (basic sanitization)
        $safeTableName = preg_replace('/[^a-zA-Z0-9_]/', '', $tableName);

        $sql = "SELECT * FROM {$safeTableName} WHERE id = :id";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Log or handle error appropriately
            // For now, re-throw or return false
            error_log("Error in findById: " . $e->getMessage());
            return false;
        }
    }

    // Other common database operations can be added here,
    // for example, findAll, save (for insert/update), deleteById, etc.
}
?>
