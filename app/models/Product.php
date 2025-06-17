<?php

require_once ROOT_PATH . '/core/Model.php';

class Product extends Model {

    protected $tableName = 'products';

    public function __construct(Database $dbInstance) {
        parent::__construct($dbInstance);
    }

    /**
     * Creates a new product.
     * @param array $data Product data (name, description, parent_id, etc.)
     * @return string|false The ID of the newly created product or false on failure.
     */
    public function create(array $data) {
        // Basic validation (can be expanded)
        if (empty($data['name'])) {
            // error_log("Product name is required.");
            return false;
        }

        $fields = ['name', 'description', 'parent_id', 'unit_of_measure', 'quantity_in_stock', 'purchase_price', 'selling_price'];
        $params = [];
        $columns = [];
        $placeholders = [];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $columns[] = $field;
                $placeholders[] = ':' . $field;
                $params[':' . $field] = ($data[$field] === '' && $field === 'parent_id') ? null : $data[$field]; // Allow empty string for parent_id to be NULL
            }
        }

        if (empty($columns)) return false; // No data to insert

        $sql = "INSERT INTO {$this->tableName} (" . implode(', ', $columns) . ", created_at, updated_at)
                VALUES (" . implode(', ', $placeholders) . ", CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";

        try {
            return $this->db->insert($sql, $params);
        } catch (PDOException $e) {
            error_log("Error creating product: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieves all products.
     * @return array An array of all products.
     */
    public function getAll() {
        $sql = "SELECT * FROM {$this->tableName} ORDER BY created_at DESC";
        try {
            return $this->db->select($sql);
        } catch (PDOException $e) {
            error_log("Error fetching all products: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves a single product by its ID.
     * @param int $id The ID of the product.
     * @return mixed The product data as an associative array or false if not found.
     */
    public function getById($id) {
        $sql = "SELECT * FROM {$this->tableName} WHERE id = :id";
        try {
            $result = $this->db->select($sql, [':id' => $id]);
            return $result ? $result[0] : false;
        } catch (PDOException $e) {
            error_log("Error fetching product by ID {$id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Updates an existing product.
     * @param int $id The ID of the product to update.
     * @param array $data Product data to update.
     * @return int|false The number of affected rows or false on failure.
     */
    public function update($id, array $data) {
        if (empty($data)) {
            // error_log("No data provided for product update.");
            return false;
        }

        // quantity_in_stock should not be updated directly through this method.
        // It's managed by updateStock() which creates movements.
        $fields = ['name', 'description', 'parent_id', 'unit_of_measure', /* 'quantity_in_stock', */ 'purchase_price', 'selling_price'];
        $params = [':id' => $id];
        $setParts = [];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $setParts[] = "{$field} = :{$field}";
                 $params[':' . $field] = ($data[$field] === '' && $field === 'parent_id') ? null : $data[$field];
            }
        }

        // Ensure updated_at is always updated
        $setParts[] = "updated_at = CURRENT_TIMESTAMP";

        if (empty($setParts)) return false; // No fields to update

        $sql = "UPDATE {$this->tableName} SET " . implode(', ', $setParts) . " WHERE id = :id";

        try {
            return $this->db->update($sql, $params);
        } catch (PDOException $e) {
            error_log("Error updating product ID {$id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deletes a product by its ID.
     * @param int $id The ID of the product to delete.
     * @return int|false The number of affected rows or false on failure.
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->tableName} WHERE id = :id";
        try {
            return $this->db->delete($sql, [':id' => $id]);
        } catch (PDOException $e) {
            error_log("Error deleting product ID {$id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieves all child products for a given parent ID.
     * @param int $parentId The ID of the parent product.
     * @return array An array of child products.
     */
    public function getChildren($parentId) {
        $sql = "SELECT * FROM {$this->tableName} WHERE parent_id = :parent_id ORDER BY name ASC";
        try {
            return $this->db->select($sql, [':parent_id' => $parentId]);
        } catch (PDOException $e) {
            error_log("Error fetching children for product ID {$parentId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves the parent product for a given product ID.
     * @param int $productId The ID of the product whose parent is to be fetched.
     * @return mixed The parent product data or false if no parent or product not found.
     */
    public function getParent($productId) {
        $product = $this->getById($productId);
        if ($product && $product['parent_id']) {
            return $this->getById($product['parent_id']);
        }
        return false;
    }

    /**
     * Updates the stock quantity for a product.
     * @param int $productId The ID of the product.
     * @param int $quantityChange The amount to change stock by (positive for increase, negative for decrease).
     * @param string $movementType Type of stock movement (e.g., 'in_delivery', 'out_sale').
     * @param int|null $relatedDocumentId Optional ID of the document causing the change.
     * @param string|null $relatedDocumentType Optional type of the related document.
     * @param string|null $notes Optional notes for the movement.
     * @return bool True on success, false on failure.
     */
    public function updateStock($productId, $quantityChange, $movementType, $relatedDocumentId = null, $relatedDocumentType = null, $notes = null) {

        $this->pdo->beginTransaction();
        try {
            // 1. Update products.quantity_in_stock (cache field)
            $sql = "UPDATE {$this->tableName}
                    SET quantity_in_stock = quantity_in_stock + :quantity_change,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :product_id";

            $updateParams = [
                ':quantity_change' => $quantityChange,
                ':product_id' => $productId
            ];
            $this->db->update($sql, $updateParams); // Using existing DB class instance

            // 2. Create Stock Movement record
            $stockMovementModel = new StockMovement($this->db); // Pass the same DB connection
            $movementData = [
                'product_id' => $productId,
                'type' => $movementType,
                'quantity' => abs($quantityChange), // Movement quantity is always positive
                'movement_date' => date('Y-m-d H:i:s'),
                'related_document_id' => $relatedDocumentId,
                'related_document_type' => $relatedDocumentType,
                'notes' => $notes
            ];

            // Infer direction for movement type if a generic type like 'adjustment' is passed with signed quantityChange
            if ($movementType === 'adjustment') { // Example of inferring
                $movementData['type'] = ($quantityChange > 0) ? 'adjustment_in' : 'adjustment_out';
            }


            if (!$stockMovementModel->createMovement($movementData)) {
                $this->pdo->rollBack();
                error_log("Failed to create stock movement record for product ID {$productId}. Stock cache update rolled back.");
                return false;
            }

            $this->pdo->commit();
            return true;

        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error updating stock for product ID {$productId} (transaction rolled back): " . $e->getMessage());
            if ($e->getCode() == '23514') { // CHECK constraint violation (e.g. stock < 0)
                 error_log("Check constraint violated during stock update for product ID {$productId}. Quantity might be going negative if not handled before call.");
            }
            return false;
        }
    }
}
?>
