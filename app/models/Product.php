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
        // Basic validation
        if (empty($data['name']) || empty($data['base_unit_id'])) {
            error_log("Product name and base_unit_id are required.");
            return false;
        }

        $fields = ['name', 'description', 'category_id', 'base_unit_id', 'quantity_in_stock', 'purchase_price', 'selling_price'];
        $params = [];
        $columns = [];
        $placeholders = [];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) { // Use array_key_exists to allow null for optional fields like description, category_id
                $columns[] = $field;
                $placeholders[] = ':' . $field;
                if ($field === 'category_id' && $data[$field] === '') {
                    $params[':' . $field] = null;
                } else {
                    $params[':' . $field] = $data[$field];
                }
            }
        }

        // Ensure quantity_in_stock defaults to 0 if not provided or empty
        if (!in_array('quantity_in_stock', $columns) || (isset($data['quantity_in_stock']) && $data['quantity_in_stock'] === '')) {
            if (!in_array('quantity_in_stock', $columns)) {
                $columns[] = 'quantity_in_stock';
                $placeholders[] = ':quantity_in_stock';
            }
            $params[':quantity_in_stock'] = 0;
        }


        if (empty($columns)) return false;

        $sql = "INSERT INTO {$this->tableName} (" . implode(', ', $columns) . ", created_at, updated_at)
                VALUES (" . implode(', ', $placeholders) . ", CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";

        $this->pdo->beginTransaction();
        try {
            $productId = $this->db->insert($sql, $params);
            if ($productId) {
                // Automatically add the base unit to product_units table
                $productUnitData = [
                    'product_id' => $productId,
                    'unit_id' => $data['base_unit_id'],
                    'conversion_factor_to_base_unit' => 1.00000
                ];
                if (!$this->addUnit($productId, $productUnitData['unit_id'], $productUnitData['conversion_factor_to_base_unit'], true)) {
                    $this->pdo->rollBack();
                    error_log("Failed to add base unit entry for new product ID {$productId}. Product creation rolled back.");
                    return false;
                }
                $this->pdo->commit();
                return $productId;
            }
            $this->pdo->rollBack();
            return false;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error creating product: " . $e->getMessage());
            // Check for specific constraint violations if needed, e.g., base_unit_id FK
            if (strpos($e->getMessage(), 'violates foreign key constraint "products_base_unit_id_fkey"') !== false) {
                 error_log("Invalid base_unit_id provided.");
                 // Could throw a more specific exception or return a message
            }
            return false;
        }
    }

    // Methods for Product Units

    /**
     * Adds a unit to a product, including the base unit.
     * @param int $productId The ID of the product.
     * @param int $unitId The ID of the unit.
     * @param float $conversionFactor The conversion factor to the base unit.
     * @param bool $isBaseUnitInternalFlag Internal flag to bypass base unit check during product creation.
     * @return string|false The ID of the newly created product_units entry or false on failure.
     */
    public function addUnit(int $productId, int $unitId, float $conversionFactor, bool $isBaseUnitInternalFlag = false) {
        // Ensure product and unit exist
        if (!$this->getById($productId) || !(new Unit($this->db))->getById($unitId)) {
            error_log("Product or Unit does not exist. Cannot add unit to product.");
            return false;
        }

        // If adding a regular unit (not the initial base unit setup),
        // ensure it's not the same as the base unit already defined for the product.
        if (!$isBaseUnitInternalFlag) {
            $product = $this->getById($productId); // Re-fetch product to get base_unit_id
            if ($product && $product['base_unit_id'] == $unitId) {
                error_log("Cannot add unit: This unit is already the base unit for the product.");
                return false; // Or handle as an update if conversion factor is 1 and it's missing.
                               // For now, strictly prevent re-adding base unit via this public method part.
            }
        }

        $sql = "INSERT INTO product_units (product_id, unit_id, conversion_factor_to_base_unit, created_at, updated_at)
                VALUES (:product_id, :unit_id, :conversion_factor, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
        $params = [
            ':product_id' => $productId,
            ':unit_id' => $unitId,
            ':conversion_factor' => $conversionFactor
        ];
        try {
            return $this->db->insert($sql, $params);
        } catch (PDOException $e) {
            error_log("Error adding unit to product ID {$productId}: " . $e->getMessage());
            if ($e->getCode() == '23505') { // Unique constraint violation uq_product_unit
                 error_log("This unit is already associated with this product.");
            }
            return false;
        }
    }

    /**
     * Updates the conversion factor for a product's associated unit.
     * Cannot be used to update the base unit's factor (which must remain 1).
     * @param int $productId The ID of the product.
     * @param int $unitId The ID of the unit to update.
     * @param float $conversionFactor The new conversion factor.
     * @return int|false Number of affected rows or false on failure.
     */
    public function updateUnit(int $productId, int $unitId, float $conversionFactor) {
        $product = $this->getById($productId);
        if (!$product) {
            error_log("Product not found. Cannot update unit.");
            return false;
        }
        if ($product['base_unit_id'] == $unitId) {
            error_log("Cannot update conversion factor for the base unit. It must always be 1.");
            return false;
        }

        $sql = "UPDATE product_units
                SET conversion_factor_to_base_unit = :conversion_factor, updated_at = CURRENT_TIMESTAMP
                WHERE product_id = :product_id AND unit_id = :unit_id";
        $params = [
            ':product_id' => $productId,
            ':unit_id' => $unitId,
            ':conversion_factor' => $conversionFactor
        ];
        try {
            $affectedRows = $this->db->update($sql, $params);
            if ($affectedRows === 0) {
                error_log("No product unit found for product ID {$productId} and unit ID {$unitId}, or factor was the same.");
                // Optionally, you could check if the entry exists before attempting update
                // or treat 0 affected rows as "not found" or "no change needed".
            }
            return $affectedRows;
        } catch (PDOException $e) {
            error_log("Error updating unit for product ID {$productId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Removes an alternative unit associated with a product.
     * Cannot remove the base unit.
     * @param int $productId The ID of the product.
     * @param int $unitId The ID of the unit to remove.
     * @return int|false Number of affected rows or false on failure.
     */
    public function removeUnit(int $productId, int $unitId) {
        $product = $this->getById($productId);
        if (!$product) {
            error_log("Product not found. Cannot remove unit.");
            return false;
        }
        if ($product['base_unit_id'] == $unitId) {
            error_log("Cannot remove the base unit from a product.");
            return false;
        }

        $sql = "DELETE FROM product_units WHERE product_id = :product_id AND unit_id = :unit_id";
        $params = [
            ':product_id' => $productId,
            ':unit_id' => $unitId
        ];
        try {
            return $this->db->delete($sql, $params);
        } catch (PDOException $e) {
            error_log("Error removing unit from product ID {$productId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieves all units (including base unit) associated with a product, along with their details.
     * This is typically for display purposes.
     * @param int $productId The ID of the product.
     * @return array An array of unit data.
     */
    public function getUnits(int $productId) {
        $sql = "SELECT u.id, u.name, u.symbol, pu.conversion_factor_to_base_unit,
                       CASE WHEN p.base_unit_id = u.id THEN TRUE ELSE FALSE END as is_base_unit
                FROM product_units pu
                JOIN units u ON pu.unit_id = u.id
                JOIN products p ON pu.product_id = p.id
                WHERE pu.product_id = :product_id
                ORDER BY is_base_unit DESC, u.name ASC"; // Show base unit first
        try {
            return $this->db->select($sql, [':product_id' => $productId]);
        } catch (PDOException $e) {
            error_log("Error fetching units for product ID {$productId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves all configured units for a specific product, suitable for populating select dropdowns.
     * Includes the base unit (which always has a factor of 1).
     * @param int $productId The ID of the product.
     * @return array An array of unit data: [ ['unit_id', 'name', 'symbol', 'conversion_factor_to_base_unit'], ... ]
     */
    public function getUnitsForProduct(int $productId) {
        $sql = "SELECT u.id as unit_id, u.name, u.symbol, pu.conversion_factor_to_base_unit
                FROM product_units pu
                JOIN units u ON pu.unit_id = u.id
                WHERE pu.product_id = :product_id
                ORDER BY u.name ASC";
        try {
            return $this->db->select($sql, [':product_id' => $productId]);
        } catch (PDOException $e) {
            error_log("Error fetching units for product ID {$productId} (for form): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Checks if a specific unit is valid (configured in product_units) for a given product.
     * @param int $productId The ID of the product.
     * @param int $unitId The ID of the unit to check.
     * @return bool True if the unit is valid for the product, false otherwise.
     */
    public function isUnitValidForProduct(int $productId, int $unitId): bool {
        $sql = "SELECT 1 FROM product_units WHERE product_id = :product_id AND unit_id = :unit_id";
        try {
            $result = $this->db->select($sql, [':product_id' => $productId, ':unit_id' => $unitId]);
            return !empty($result);
        } catch (PDOException $e) {
            error_log("Error checking unit validity for product ID {$productId}, unit ID {$unitId}: " . $e->getMessage());
            return false; // Fail safe
        }
    }

    /**
     * Retrieves the base unit details for a product.
     * @param int $productId The ID of the product.
     * @return mixed The base unit data as an associative array or false if not found.
     */
    public function getBaseUnit(int $productId) {
        $sql = "SELECT u.id, u.name, u.symbol, pu.conversion_factor_to_base_unit
                FROM products p
                JOIN product_units pu ON p.id = pu.product_id AND p.base_unit_id = pu.unit_id
                JOIN units u ON p.base_unit_id = u.id
                WHERE p.id = :product_id AND pu.conversion_factor_to_base_unit = 1.00000"; // Ensure it's the true base unit entry
        try {
            $result = $this->db->select($sql, [':product_id' => $productId]);
            return $result ? $result[0] : false;
        } catch (PDOException $e) {
            error_log("Error fetching base unit for product ID {$productId}: " . $e->getMessage());
            return false;
        }
    }


    /**
     * Retrieves all products.
     * @return array An array of all products.
     */
    public function getAll() {
        $sql = "SELECT
                    p.id, p.name, p.description, p.category_id, p.base_unit_id,
                    p.quantity_in_stock, p.purchase_price, p.selling_price,
                    p.created_at, p.updated_at,
                    pc.name as category_name,
                    u.name as base_unit_name,
                    u.symbol as base_unit_symbol
                FROM {$this->tableName} p
                LEFT JOIN product_categories pc ON p.category_id = pc.id
                JOIN units u ON p.base_unit_id = u.id -- base_unit_id is NOT NULL, so JOIN is appropriate
                ORDER BY p.name ASC"; // Changed order to name ASC for better display
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
        $sql = "SELECT
                    p.id, p.name, p.description, p.category_id, p.base_unit_id,
                    p.quantity_in_stock, p.purchase_price, p.selling_price,
                    p.created_at, p.updated_at,
                    pc.name as category_name,
                    u.name as base_unit_name,
                    u.symbol as base_unit_symbol
                FROM {$this->tableName} p
                LEFT JOIN product_categories pc ON p.category_id = pc.id
                JOIN units u ON p.base_unit_id = u.id -- base_unit_id is NOT NULL
                WHERE p.id = :id";
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
            error_log("No data provided for product update ID {$id}.");
            return false;
        }

        $currentProduct = $this->getById($id);
        if (!$currentProduct) {
            error_log("Product not found for update ID {$id}.");
            return false;
        }

        // Forbid changing base_unit_id after creation for simplicity in this step
        // TODO: Allow changing base_unit_id, which would require complex logic:
        // 1. Check if new base_unit_id is already an alternative unit.
        // 2. Update all conversion factors in product_units relative to the new base unit.
        // 3. Update the old base unit entry in product_units to be an alternative unit or remove it.
        // 4. Update the new base unit entry in product_units to have factor 1.
        if (isset($data['base_unit_id']) && $data['base_unit_id'] != $currentProduct['base_unit_id']) {
            error_log("Attempt to change base_unit_id for product ID {$id} was ignored. This operation is not currently supported.");
            unset($data['base_unit_id']); // Remove from data to prevent accidental update by subsequent logic
        }

        // quantity_in_stock should not be updated directly through this method.
        // It's managed by updateStock() which creates movements.
        $fields = ['name', 'description', 'category_id', /* 'base_unit_id', */ 'purchase_price', 'selling_price'];
        $params = [':id' => $id];
        $setParts = [];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) { // Use array_key_exists to allow null for optional fields
                $setParts[] = "{$field} = :{$field}";
                if ($field === 'category_id' && $data[$field] === '') {
                    $params[':' . $field] = null;
                } else {
                    $params[':' . $field] = $data[$field];
                }
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
     * Updates the stock quantity for a product and records the movement.
     * The quantity change in products.quantity_in_stock is ALREADY in base unit.
     * The stock movement record will store the original transaction quantity and unit.
     *
     * @param int $productId The ID of the product.
     * @param string $movementType Type of stock movement (e.g., 'in_delivery', 'out_sale').
     * @param float $quantityInTransactionUnit The quantity of the transaction in transaction_unit_id.
     *                                         This value is positive for increases (e.g. delivery) and
     *                                         negative for decreases (e.g. sale).
     * @param int $transactionUnitId The unit ID of the quantityInTransactionUnit.
     * @param int|null $relatedDocumentId Optional ID of the document causing the change.
     * @param string|null $relatedDocumentType Optional type of the related document.
     * @param string|null $notes Optional notes for the movement.
     * @return bool True on success, false on failure.
     */
    public function updateStock(int $productId, string $movementType, float $quantityInTransactionUnit, int $transactionUnitId, ?int $relatedDocumentId = null, ?string $relatedDocumentType = null, ?string $notes = null): bool {

        $product = $this->getById($productId); // This already fetches base_unit_id, base_unit_name etc.
        if (!$product) {
            error_log("Product not found (ID: {$productId}) for stock update.");
            return false;
        }
        $baseUnitId = $product['base_unit_id'];
        $quantityChangeInBaseUnit = 0;

        if ($transactionUnitId == $baseUnitId) {
            $quantityChangeInBaseUnit = $quantityInTransactionUnit;
        } else {
            $productUnits = $this->getUnitsForProduct($productId); // Use the new method
            $conversionFactor = null;
            foreach ($productUnits as $pu) {
                if ($pu['unit_id'] == $transactionUnitId) {
                    $conversionFactor = (float)$pu['conversion_factor_to_base_unit'];
                    break;
                }
            }
            if ($conversionFactor === null || $conversionFactor == 0) {
                error_log("Conversion factor not found or invalid for product ID {$productId}, unit ID {$transactionUnitId}. Cannot update stock.");
                return false;
            }
            $quantityChangeInBaseUnit = $quantityInTransactionUnit * $conversionFactor;
        }

        $this->pdo->beginTransaction();
        try {
            // 1. Update products.quantity_in_stock (cache field)
            // $quantityChangeInBaseUnit is already signed (positive for in, negative for out)
            $sqlStockUpdate = "UPDATE {$this->tableName}
                               SET quantity_in_stock = quantity_in_stock + :quantity_change_in_base_unit,
                                   updated_at = CURRENT_TIMESTAMP
                               WHERE id = :product_id";
            $updateParams = [
                ':quantity_change_in_base_unit' => $quantityChangeInBaseUnit,
                ':product_id' => $productId
            ];
            $this->db->update($sqlStockUpdate, $updateParams);

            // 2. Create Stock Movement record
            // StockMovementModel->createMovement expects a positive quantity for its 'quantity_in_transaction_unit'
            // and the 'type' field determines the nature (in/out).
            // The $movementType passed to updateStock already reflects this (e.g. 'out_sale').
            $stockMovementModel = new StockMovement($this->db);
            $movementData = [
                'product_id' => $productId,
                'type' => $movementType,
                'quantity_in_transaction_unit' => abs($quantityInTransactionUnit), // Must be positive for the movement record itself
                'transaction_unit_id' => $transactionUnitId,
                'movement_date' => date('Y-m-d H:i:s'),
                'related_document_id' => $relatedDocumentId,
                'related_document_type' => $relatedDocumentType,
                'notes' => $notes
            ];

            // The StockMovementModel->createMovement will handle storing original_quantity/unit
            // and calculating the 'quantity' field in base units (which should match abs($quantityChangeInBaseUnit)).

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
            if ($e->getCode() == '23514') { // CHECK constraint violation (e.g. stock < 0 if not allowed by DB)
                 error_log("Check constraint violated during stock update for product ID {$productId}.");
            }
            return false;
        }
    }
}
?>
