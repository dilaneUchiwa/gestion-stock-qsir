<?php

require_once ROOT_PATH . '/config/database.php';

class ProductCategory extends Model {
    private $tableName = "product_categories";

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
        if (!isset($data['name'])) {
            // Basic validation
            return false;
        }
        $description = isset($data['description']) ? $data['description'] : null;

        try {
            $stmt = $this->db->prepare("INSERT INTO " . $this->tableName . " (name, description) VALUES (:name, :description)");
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':description', $description);
            return $stmt->execute();
        } catch (PDOException $e) {
            // Handle potential errors, e.g., unique constraint violation for name
            error_log("Error creating product category: " . $e->getMessage());
            return false;
        }
    }

    public function update($id, array $data) {
        if (!isset($data['name'])) {
            // Basic validation
            return false;
        }
        $description = isset($data['description']) ? $data['description'] : null;

        try {
            $stmt = $this->db->prepare("UPDATE " . $this->tableName . " SET name = :name, description = :description WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':description', $description);
            return $stmt->execute();
        } catch (PDOException $e) {
            // Handle potential errors
            error_log("Error updating product category " . $id . ": " . $e->getMessage());
            return false;
        }
    }

    public function delete($id) {
        try {
            // Relies on ON DELETE SET NULL for products.category_id
            $stmt = $this->db->prepare("DELETE FROM " . $this->tableName . " WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error deleting product category " . $id . ": " . $e->getMessage());
            // A more specific error could be returned to the controller
            if ($e->getCode() == '23503') { // Foreign key violation (should not happen with ON DELETE SET NULL)
                 throw new Exception("Cannot delete category: it might still be referenced unexpectedly.");
            }
            return false;
        }
    }
}

?>
