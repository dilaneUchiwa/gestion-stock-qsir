<?php

require_once ROOT_PATH . '/config/database.php';

class Unit extends Model{
    private $tableName = "units";

     public function __construct(Database $dbInstance) {
        parent::__construct($dbInstance);
    }

    public function getAll() {
        $stmt = $this->db->prepare("SELECT * FROM " . $this->tableName . " ORDER BY name ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM " . $this->tableName . " WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $data) {
        if (!isset($data['name']) || !isset($data['symbol'])) {
            // Basic validation
            return false;
        }
        try {
            $stmt = $this->db->prepare("INSERT INTO " . $this->tableName . " (name, symbol) VALUES (:name, :symbol)");
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':symbol', $data['symbol']);
            return $stmt->execute();
        } catch (PDOException $e) {
            // Handle potential errors, e.g., unique constraint violation
            error_log("Error creating unit: " . $e->getMessage());
            return false;
        }
    }

    public function update($id, array $data) {
        if (!isset($data['name']) || !isset($data['symbol'])) {
            // Basic validation
            return false;
        }
        try {
            $stmt = $this->db->prepare("UPDATE " . $this->tableName . " SET name = :name, symbol = :symbol WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':symbol', $data['symbol']);
            return $stmt->execute();
        } catch (PDOException $e) {
            // Handle potential errors
            error_log("Error updating unit " . $id . ": " . $e->getMessage());
            return false;
        }
    }

    public function delete($id) {
        try {
            // Check if the unit is used in products.base_unit_id or product_units.unit_id
            // This is a simple check; more robust checks might be needed depending on ON DELETE constraints
            // For now, we rely on DB constraints (ON DELETE RESTRICT for base_unit_id, ON DELETE CASCADE for product_units)
            // A specific check here could provide a more user-friendly error.

            $stmt = $this->db->prepare("DELETE FROM " . $this->tableName . " WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            // Handle potential errors, e.g., foreign key constraint violation
            error_log("Error deleting unit " . $id . ": " . $e->getMessage());
            // A more specific error could be returned to the controller
            if ($e->getCode() == '23503') { // Foreign key violation
                throw new Exception("Cannot delete unit: it is currently in use by one or more products or product units.");
            }
            return false;
        }
    }
}

?>
