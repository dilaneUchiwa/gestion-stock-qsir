<?php

require_once ROOT_PATH . '/core/Model.php';
require_once ROOT_PATH . '/app/models/StockMovement.php';
require_once ROOT_PATH . '/app/models/Unit.php';

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

        $fields = ['name', 'description', 'category_id', 'base_unit_id', 'purchase_price', 'selling_price'];
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

        if (empty($columns)) return false;

        $sql = "INSERT INTO {$this->tableName} (" . implode(', ', $columns) . ", created_at, updated_at)
                VALUES (" . implode(', ', $placeholders) . ", CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";

        // $this->pdo->beginTransaction();
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
                    // $this->pdo->rollBack();
                    error_log("Failed to add base unit entry for new product ID {$productId}. Product creation rolled back.");
                    return false;
                }
                // $this->pdo->commit();
                return $productId;
            }
            // $this->pdo->rollBack();
            return false;
        } catch (PDOException $e) {
            // $this->pdo->rollBack();
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
                    p.purchase_price, p.selling_price,
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
                    p.purchase_price, p.selling_price,
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

        // quantity_in_stock column no longer exists in products table.
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
     * Retrieves the current stock quantity for a given product in a specific unit of measure.
     * @param int $productId The ID of the product.
     * @param int $unitId The ID of the unit of measure.
     * @return float The quantity if found, otherwise 0.0.
     */
    public function getStock(int $productId, int $unitId): float {
        $sql = "SELECT quantity FROM product_stock_per_unit WHERE product_id = :product_id AND unit_id = :unit_id";
        try {
            $result = $this->db->select($sql, [':product_id' => $productId, ':unit_id' => $unitId]);
            if ($result && count($result) > 0) {
                return (float)$result[0]['quantity'];
            }
            return 0.0;
        } catch (PDOException $e) {
            error_log("Error fetching stock for product ID {$productId}, unit ID {$unitId}: " . $e->getMessage());
            return 0.0; // Should perhaps throw or return a more specific error indicator
        }
    }

    /**
     * Retrieves all stock records for a product, showing quantity per unit.
     * @param int $productId The ID of the product.
     * @return array An array of stock data.
     */
    public function getAllStockForProduct(int $productId): array {
        $sql = "SELECT ps.unit_id, u.name as unit_name, u.symbol as unit_symbol, ps.quantity
                FROM product_stock_per_unit ps
                JOIN units u ON ps.unit_id = u.id
                WHERE ps.product_id = :product_id
                ORDER BY u.name";
        try {
            return $this->db->select($sql, [':product_id' => $productId]);
        } catch (PDOException $e) {
            error_log("Error fetching all stock for product ID {$productId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Updates the stock quantity for a product in a specific unit and records the movement.
     *
     * @param int $productId The ID of the product.
     * @param int $unitId The ID of the unit for the quantity change.
     * @param float $quantityChange The amount to add (positive) or subtract (negative) in the given unit.
     * @param string $movementType Type of stock movement (e.g., 'in_delivery', 'out_sale', 'adjustment_in', 'adjustment_out').
     * @param int|null $relatedDocumentId Optional ID of the document causing the change.
     * @param string|null $relatedDocumentType Optional type of the related document.
     * @param string|null $notes Optional notes for the movement.
     * @return bool True on success, false on failure.
     */
    public function updateStockQuantity(int $productId, int $unitId, float $quantityChange, string $movementType, ?int $relatedDocumentId = null, ?string $relatedDocumentType = null, ?string $notes = null): bool {
        // TODO: Implement transaction management if $this->db supports it and it's not handled by a service layer.
        // For now, operations are grouped. If one fails, subsequent ones might not run, but no explicit rollback.

        $currentQuantity = $this->getStock($productId, $unitId);

        if ($currentQuantity === false && $quantityChange < 0) { // Assuming getStock might return false on error, though it's typed to float
            error_log("Error fetching current stock or trying to reduce non-existent stock for product ID {$productId}, unit ID {$unitId}.");
            return false;
        }
        // If getStock returns 0.0 for a non-existent record, this logic holds.
        if ($currentQuantity == 0.0 && $quantityChange < 0 && !$this->db->select("SELECT 1 FROM product_stock_per_unit WHERE product_id = :pid AND unit_id = :uid", [':pid' => $productId, ':uid' => $unitId])) {
             error_log("Cannot reduce stock for product ID {$productId}, unit ID {$unitId} as no stock record exists.");
             return false;
        }


        $newQuantity = $currentQuantity + $quantityChange;

        if ($newQuantity < 0) {
            error_log("Insufficient stock for product ID {$productId}, unit ID {$unitId}. Required: " . abs($quantityChange) . ", available: {$currentQuantity}.");
            return false;
        }

        // Update or Insert into product_stock_per_unit
        $stockRecordExists = $this->db->select("SELECT 1 FROM product_stock_per_unit WHERE product_id = :product_id AND unit_id = :unit_id", [':product_id' => $productId, ':unit_id' => $unitId]);

        try {
            if ($stockRecordExists && count($stockRecordExists) > 0) {
                $sql = "UPDATE product_stock_per_unit SET quantity = :new_quantity, updated_at = CURRENT_TIMESTAMP WHERE product_id = :product_id AND unit_id = :unit_id";
                $this->db->update($sql, [':new_quantity' => $newQuantity, ':product_id' => $productId, ':unit_id' => $unitId]);
            } else {
                // Only insert if new quantity is non-negative.
                // If quantityChange was negative and no record existed, we would have exited earlier.
                // If quantityChange is positive and no record, this will insert.
                // If quantityChange results in zero for a new record, it might be desired to create it.
                 if ($newQuantity >= 0) {
                    $sql = "INSERT INTO product_stock_per_unit (product_id, unit_id, quantity, created_at, updated_at)
                            VALUES (:product_id, :unit_id, :new_quantity, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
                    $this->db->insert($sql, [':product_id' => $productId, ':unit_id' => $unitId, ':new_quantity' => $newQuantity]);
                 } else {
                    // This case should ideally be caught by $newQuantity < 0 check above,
                    // but as a safeguard if a new record would result in negative stock.
                    error_log("Attempted to create a new stock record with negative quantity for product ID {$productId}, unit ID {$unitId}. This should not happen.");
                    return false;
                 }
            }
        } catch (PDOException $e) {
            error_log("Error updating/inserting product_stock_per_unit for product ID {$productId}, unit ID {$unitId}: " . $e->getMessage());
            // TODO: Rollback if transactions were started
            return false;
        }

        // Record the stock movement
        $stockMovementModel = new StockMovement($this->db);
        $movementData = [
            'product_id' => $productId,
            'type' => $movementType, // e.g., 'in_delivery', 'out_sale', 'adjustment_in', 'adjustment_out'
            'quantity_in_transaction_unit' => abs($quantityChange), // Movement model expects positive quantity
            'transaction_unit_id' => $unitId,
            'movement_date' => date('Y-m-d H:i:s'),
            'related_document_id' => $relatedDocumentId,
            'related_document_type' => $relatedDocumentType,
            'notes' => $notes
        ];

        if (!$stockMovementModel->createMovement($movementData)) {
            error_log("Failed to create stock movement record for product ID {$productId}, unit ID {$unitId}. The product_stock_per_unit table might have been updated, but movement log failed. Manual reconciliation may be needed if no transaction rollback.");
            // TODO: Rollback product_stock_per_unit change if transactions were started
            return false;
        }

        // TODO: Commit transaction if started
        return true;
    }

    /**
     * Converts a quantity of a product from a source unit to a target unit, updating stock for both.
     *
     * @param int $productId The ID of the product.
     * @param int $sourceUnitId The ID of the source unit (from which quantity is taken).
     * @param float $sourceQuantity The quantity in the source unit to convert.
     * @param int $targetUnitId The ID of the target unit (to which quantity is added).
     * @return bool|string True on success, or an error message string on failure.
     */
    public function fractionProduct(int $productId, int $sourceUnitId, float $sourceQuantity, int $targetUnitId) {
        // 1. Validate Inputs
        if ($sourceUnitId === $targetUnitId) {
            return "L'unité source et l'unité cible ne peuvent pas être identiques pour le fractionnement.";
        }
        if ($sourceQuantity <= 0) {
            return "La quantité source pour le fractionnement doit être positive.";
        }

        $product = $this->getById($productId);
        if (!$product) {
            return "Produit non trouvé pour le fractionnement (ID: {$productId}).";
        }

        $configuredUnits = $this->getUnitsForProduct($productId);
        $sourceUnitInfo = null;
        $targetUnitInfo = null;

        foreach ($configuredUnits as $unit) {
            if ($unit['unit_id'] == $sourceUnitId) {
                $sourceUnitInfo = $unit;
            }
            if ($unit['unit_id'] == $targetUnitId) {
                $targetUnitInfo = $unit;
            }
        }

        if (!$sourceUnitInfo) {
            return "Unité source (ID: {$sourceUnitId}) non valide ou non configurée pour le produit (ID: {$productId}).";
        }
        if (!$targetUnitInfo) {
            return "Unité cible (ID: {$targetUnitId}) non valide ou non configurée pour le produit (ID: {$productId}).";
        }

        // 2. Check Stock
        $currentStockInSourceUnit = (float)$this->getStock($productId, $sourceUnitId);
        if ($currentStockInSourceUnit < $sourceQuantity) {
            return "Stock insuffisant dans l'unité source ({$sourceUnitInfo['name']}) pour le fractionnement. Demandé: {$sourceQuantity}, Disponible: {$currentStockInSourceUnit}.";
        }

        // 3. Calculate Target Quantity
        $factorSourceToBase = (float)$sourceUnitInfo['conversion_factor_to_base_unit'];
        $factorTargetToBase = (float)$targetUnitInfo['conversion_factor_to_base_unit'];

        if ($factorSourceToBase <= 0) {
            return "Facteur de conversion invalide pour l'unité source: {$sourceUnitInfo['name']}.";
        }
        if ($factorTargetToBase <= 0) {
            return "Facteur de conversion invalide pour l'unité cible: {$targetUnitInfo['name']}.";
        }

        $quantityInBaseUnits = $sourceQuantity * $factorSourceToBase;
        $calculatedTargetQuantity = $quantityInBaseUnits / $factorTargetToBase;

        if ($calculatedTargetQuantity <= 0) { // Should not happen if factors are positive
            return "La quantité cible calculée est nulle ou négative, vérifiez les facteurs de conversion.";
        }

        // TODO: Consider transaction management here if not handled globally by a service layer.
        // $this->db->beginTransaction(); or similar

        // 4. Update Stock (Decrease Source)
        $notesSource = "Fractionnement: sortie de {$sourceQuantity} {$sourceUnitInfo['symbol']} (vers {$targetUnitInfo['symbol']})";
        $decreaseSuccess = $this->updateStockQuantity(
            $productId,
            $sourceUnitId,
            -$sourceQuantity, // Negative quantityChange
            'split_out',
            null,
            'fractioning',
            $notesSource
        );

        if (!$decreaseSuccess) {
            // $this->db->rollBack();
            error_log("Échec de la diminution du stock source (ID Unité: {$sourceUnitId}) lors du fractionnement pour le produit ID {$productId}.");
            return "Échec de la mise à jour du stock source lors du fractionnement.";
        }

        // 5. Update Stock (Increase Target)
        $notesTarget = "Fractionnement: entrée de {$calculatedTargetQuantity} {$targetUnitInfo['symbol']} (depuis {$sourceUnitInfo['symbol']})";
        $increaseSuccess = $this->updateStockQuantity(
            $productId,
            $targetUnitId,
            $calculatedTargetQuantity, // Positive quantityChange
            'split_in',
            null,
            'fractioning',
            $notesTarget
        );

        if (!$increaseSuccess) {
            error_log("Échec de l'augmentation du stock cible (ID Unité: {$targetUnitId}) lors du fractionnement pour le produit ID {$productId}. Tentative d'annulation de la diminution source.");

            // Attempt to revert the source stock deduction
            $reversalNotesSource = "ANNULATION Fractionnement: retour de {$sourceQuantity} {$sourceUnitInfo['symbol']} suite à échec cible";
            $reversalSuccess = $this->updateStockQuantity(
                $productId,
                $sourceUnitId,
                $sourceQuantity, // Positive quantityChange to add back
                'split_out_reversal', // Distinct movement type for reversal
                null,
                'fractioning_reversal',
                $reversalNotesSource
            );

            if (!$reversalSuccess) {
                // $this->db->rollBack(); // Rollback the entire transaction if it was started
                error_log("ERREUR CRITIQUE: Échec de l'annulation de la diminution du stock source (ID Unité: {$sourceUnitId}) après l'échec de l'augmentation du stock cible pour le produit ID {$productId}. Intervention manuelle requise.");
                return "Échec de la mise à jour du stock cible ET échec de l'annulation de la modification du stock source. Intervention manuelle requise.";
            }
            // $this->db->rollBack(); // Rollback the entire transaction
            return "Échec de la mise à jour du stock cible. La modification du stock source a été annulée.";
        }

        // $this->db->commit();
        return true;
    }
}
?>
