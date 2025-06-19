<?php

require_once ROOT_PATH . '/core/Model.php';

class Delivery extends Model {

    public $tableName = 'deliveries';
    public $allowedTypes = ['purchase', 'free_sample', 'return', 'other']; // 'return' for customer returns if used here

    public function __construct(Database $dbInstance) {
        parent::__construct($dbInstance);
    }

    /**
     * Creates a new delivery along with its items.
     * Updates product stock and PO status.
     * @param array $data Delivery header data
     * @param array $itemsData Array of items (product_id, quantity_received, purchase_order_item_id)
     * @return string|false The ID of the newly created delivery or false on failure.
     */
    public function createDelivery(array $data, array $itemsData) {
        if (isset($data['is_partial'])) {
            if ($data['is_partial'] === '' || $data['is_partial'] === null) {
                $data['is_partial'] = false;
            } else {
                $data['is_partial'] = filter_var($data['is_partial'], FILTER_VALIDATE_BOOLEAN);
            }
        }else{
            $data['is_partial'] = false;
        }


        // Validate Delivery data
        if (empty($data['delivery_date'])) {
            error_log("Delivery Date is required.");
            return false;
        }
        if (empty($data['purchase_order_id']) && empty($data['supplier_id'])) {
            error_log("Either Purchase Order ID or Supplier ID is required for a delivery.");
            return false;
        }
        if (isset($data['type']) && !in_array($data['type'], $this->allowedTypes)) {
            error_log("Invalid delivery type specified.");
            return false;
        }
        if (empty($itemsData)) {
            error_log("Delivery must have at least one item.");
            return false;
        }
        foreach ($itemsData as $item) {
            if (empty($item['product_id']) || empty($item['unit_id']) || empty($item['quantity_received']) || $item['quantity_received'] <= 0) {
                error_log("Invalid item data: product_id, unit_id, and positive quantity_received are required.");
                return false;
            }
            // TODO: Validate that unit_id is a valid unit for the product_id from product_units table
        }

        // $this->pdo->beginTransaction();
        try {
            // Insert Delivery Header
            $deliveryFields = ['purchase_order_id', 'supplier_id', 'delivery_date', 'is_partial', 'notes', 'type'];
            $deliveryParams = [];
            $deliveryColumns = [];
            $deliveryPlaceholders = [];

// Validation sécurisée pour les champs booléens, en particulier 'is_partial'
foreach ($deliveryFields as $field) {
    if (isset($data[$field])) {
        $deliveryColumns[] = $field;
        $deliveryPlaceholders[] = ':' . $field;

        // Traitement spécifique pour le champ 'is_partial'
        if ($field === 'is_partial') {
            // Si 'is_partial' est une chaîne vide ou null, on la remplace par false
            if ($data[$field] === '' || $data[$field] === null) {
                $deliveryParams[':' . $field] = false;
            } else {
                // Sinon, on convertit en booléen
                $deliveryParams[':' . $field] = filter_var($data[$field], FILTER_VALIDATE_BOOLEAN);
            }
        } elseif (in_array($field, ['purchase_order_id', 'supplier_id', 'notes'])) {
            // Pour ces champs, on met null si la valeur est une chaîne vide
            $deliveryParams[':' . $field] = ($data[$field] === '') ? null : $data[$field];
        } else {
            // Sinon, on passe la valeur telle quelle
            $deliveryParams[':' . $field] = $data[$field];
        }
    }
}
             if (!isset($data['type'])) { // Default type
                $deliveryColumns[] = 'type';
                $deliveryPlaceholders[] = ':type';
                $deliveryParams[':type'] = 'purchase';
            }
            if (!isset($data['is_partial']) && isset($data['purchase_order_id'])) { // Default is_partial based on items later
                 $deliveryColumns[] = 'is_partial';
                 $deliveryPlaceholders[] = ':is_partial';
                 $deliveryParams[':is_partial'] = false; // Will be updated after checking items against PO
            }

            if($deliveryParams[':is_partial']){
                $deliveryParams[':is_partial'] = 1;
            }else{
                $deliveryParams[':is_partial'] = 0;
            }

            $sqlDelivery = "INSERT INTO {$this->tableName} (" . implode(', ', $deliveryColumns) . ", created_at, updated_at)
                            VALUES (" . implode(', ', $deliveryPlaceholders) . ", CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
            $deliveryId = $this->db->insert($sqlDelivery, $deliveryParams);

            if (!$deliveryId) {
                // $this->pdo->rollBack();
                error_log("Failed to create delivery header.");
                return false;
            }

            // Insert Delivery Items & Update Product Stock
            $sqlItem = "INSERT INTO delivery_items (delivery_id, product_id, unit_id, quantity_received, purchase_order_item_id)
                        VALUES (:delivery_id, :product_id, :unit_id, :quantity_received, :purchase_order_item_id)";
            $productModel = new Product($this->db);

            foreach ($itemsData as $item) {
                // TODO: Validate that unit_id is a valid unit for the product_id from product_units table
                // This should ideally be done in the controller or at the start of this method.

                $itemInsertParams = [
                    ':delivery_id' => $deliveryId,
                    ':product_id' => $item['product_id'],
                    ':unit_id' => $item['unit_id'],
                    ':quantity_received' => $item['quantity_received'],
                    ':purchase_order_item_id' => $item['purchase_order_item_id'] ?? null,
                ];
                $deliveryItemId = $this->db->insert($sqlItem, $itemInsertParams);

                if (!$deliveryItemId) {
                    // $this->pdo->rollBack();
                    error_log("Failed to create delivery item for product ID {$item['product_id']}.");
                    return false;
                }

                // Update product stock (only for types that increase stock)
                if (in_array($data['type'] ?? 'purchase', ['purchase', 'free_sample', 'other'])) {
                    // $productModel->updateStock will handle the conversion from $item['unit_id'] to base unit
                    $notes = "Received via DEL-{$deliveryId} (Item {$deliveryItemId}) in unit ID {$item['unit_id']}. Qty: {$item['quantity_received']}.";
                    if(isset($item['purchase_order_item_id'])) {
                        $notes .= " (Ref PO Item ID: {$item['purchase_order_item_id']})";
                    }

                    if (!$productModel->updateStock(
                        $item['product_id'], // productId
                        'in_delivery',       // movementType
                        (float)$item['quantity_received'], // quantityInTransactionUnit (positive for increase)
                        (int)$item['unit_id'],             // transactionUnitId
                        $deliveryItemId,     // relatedDocumentId
                        'delivery_items',    // relatedDocumentType
                        $notes               // notes
                    )) {
                        // $this->pdo->rollBack();
                        error_log("Failed to update stock or create movement for product ID {$item['product_id']}.");
                        return false;
                    }
                }
            }

            // Update Purchase Order Status (if linked to a PO)
            $isFullyReceived = true; // Assume fully received initially
            if (!empty($data['purchase_order_id'])) {
                $poModel = new PurchaseOrder($this->db); // Assuming PurchaseOrder model
                $poItems = $poModel->getItemsForPo($data['purchase_order_id']);
                $receivedQuantities = []; // [po_item_id => total_received_for_this_po_item_across_all_deliveries]

                // Calculate total received quantities for each PO item across ALL deliveries for this PO
                $sqlReceivedQty = "SELECT di.purchase_order_item_id, SUM(di.quantity_received) as total_received
                                   FROM delivery_items di
                                   JOIN deliveries d ON di.delivery_id = d.id
                                   WHERE d.purchase_order_id = :po_id AND di.purchase_order_item_id IS NOT NULL
                                   GROUP BY di.purchase_order_item_id";
                $allReceivedForPo = $this->db->select($sqlReceivedQty, [':po_id' => $data['purchase_order_id']]);

                foreach($allReceivedForPo as $receivedItem) {
                    $receivedQuantities[$receivedItem['purchase_order_item_id']] = $receivedItem['total_received'];
                }

                foreach ($poItems as $poItem) {
                    $totalReceivedForPoItem = $receivedQuantities[$poItem['id']] ?? 0;
                    if ($totalReceivedForPoItem < $poItem['quantity_ordered']) {
                        $isFullyReceived = false;
                        break;
                    }
                }

                $newPoStatus = $isFullyReceived ? 'received' : 'partially_received';
                if (!$poModel->updateStatus($data['purchase_order_id'], $newPoStatus)) {
                     // $this->pdo->rollBack();
                     error_log("Failed to update PO status for PO ID {$data['purchase_order_id']}.");
                     return false;
                }
                // Update the is_partial flag for the current delivery
                if (!$isFullyReceived && !$data['is_partial']){ // if not fully received overall, this delivery might contribute to partial state.
                    // More complex logic: if this specific delivery itself only contains a subset of what was pending for those lines.
                    // For now, if the PO is partially_received overall, we can mark this delivery as partial too, or base it on user input.
                    // The $data['is_partial'] from user input is probably more reliable here.
                    // If not set by user, and PO is now partially_received, we can set this delivery's is_partial to true.
                    if(!isset($data['is_partial']) || $data['is_partial'] == false){
                         $this->db->update("UPDATE {$this->tableName} SET is_partial = TRUE WHERE id = :delivery_id", [':delivery_id' => $deliveryId]);
                    }
                }

            }

            // $this->pdo->commit();
            return $deliveryId;

        } catch (PDOException $e) {
            // $this->pdo->rollBack();
            // var_dump($e);
            error_log("Error creating delivery with items: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieves a single delivery by its ID, including its items and related info.
     * @param int $id The ID of the delivery.
     * @return mixed The Delivery data with items, or false if not found.
     */
    public function getByIdWithDetails($id) {
        $delivery = $this->getById($id);
        if (!$delivery) {
            return false;
        }
        $delivery['items'] = $this->getItemsForDelivery($id);

        // Fetch PO number if available
        if ($delivery['purchase_order_id']) {
            $poModel = new PurchaseOrder($this->db);
            $poHeader = $poModel->getById($delivery['purchase_order_id']);
            $delivery['purchase_order_number'] = $poHeader ? 'PO-' . $poHeader['id'] : 'N/A';
        } else {
            $delivery['purchase_order_number'] = 'N/A (Direct)';
        }

        // Fetch Supplier name if available (especially for direct deliveries)
        if ($delivery['supplier_id']) {
            $supplierModel = new Supplier($this->db);
            $supplier = $supplierModel->getById($delivery['supplier_id']);
            $delivery['supplier_name'] = $supplier ? $supplier['name'] : 'N/A';
        } elseif ($delivery['purchase_order_id'] && isset($poHeader['supplier_name'])) {
             $delivery['supplier_name'] = $poHeader['supplier_name']; // From PO
        }
        else {
            $delivery['supplier_name'] = 'N/A';
        }


        return $delivery;
    }

    /**
     * Retrieves a single delivery header by its ID.
     */
    public function getById($id) {
        $sql = "SELECT d.*, s.name as supplier_name_direct
                FROM {$this->tableName} d
                LEFT JOIN suppliers s ON d.supplier_id = s.id
                WHERE d.id = :id";
        try {
            $result = $this->db->select($sql, [':id' => $id]);
            return $result ? $result[0] : false;
        } catch (PDOException $e) {
            error_log("Error fetching delivery by ID {$id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieves all items for a given delivery ID.
     * @param int $deliveryId The Delivery ID.
     * @return array Array of items.
     */
    public function getItemsForDelivery($deliveryId) {
        $sql = "SELECT di.*, p.name as product_name,
                       u.name as unit_name, u.symbol as unit_symbol,
                       poi.quantity_ordered as original_quantity_ordered,
                       po_unit.name as po_unit_name, po_unit.symbol as po_unit_symbol
                FROM delivery_items di
                JOIN products p ON di.product_id = p.id
                JOIN units u ON di.unit_id = u.id
                LEFT JOIN purchase_order_items poi ON di.purchase_order_item_id = poi.id
                LEFT JOIN units po_unit ON poi.unit_id = po_unit.id
                WHERE di.delivery_id = :delivery_id";
        try {
            return $this->db->select($sql, [':delivery_id' => $deliveryId]);
        } catch (PDOException $e) {
            error_log("Error fetching items for Delivery ID {$deliveryId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves all deliveries with basic related info.
     * @return array An array of all deliveries.
     */
    public function getAllWithDetails() {
        // This query can become complex; adjust based on necessary displayed fields in index view
        $sql = "SELECT d.id, d.delivery_date, d.status, d.type, d.is_partial,
                       po.id as po_id_num, s.name as supplier_name_direct, s_po.name as supplier_name_po
                FROM {$this->tableName} d
                LEFT JOIN purchase_orders po ON d.purchase_order_id = po.id
                LEFT JOIN suppliers s ON d.supplier_id = s.id -- Direct supplier
                LEFT JOIN suppliers s_po ON po.supplier_id = s_po.id -- Supplier from PO
                ORDER BY d.delivery_date DESC, d.id DESC";
        try {
            $results = $this->db->select($sql);
            // Process to get one supplier name
            foreach($results as &$row) {
                $row['supplier_name'] = $row['supplier_name_direct'] ?? $row['supplier_name_po'] ?? 'N/A';
                $row['purchase_order_display'] = $row['po_id_num'] ? 'PO-'.$row['po_id_num'] : 'Direct';
            }
            return $results;
        } catch (PDOException $e) {
            error_log("Error fetching all deliveries: " . $e->getMessage());
            return [];
        }
    }

    // Deletion of deliveries can be very tricky due to stock updates.
    // Generally, a reversing transaction (stock adjustment) is preferred over hard delete.
    // If deletion is allowed, stock quantities must be reverted. This is complex.
    // For this subtask, a simple delete is implemented, but in a real system, this would need more thought.
    public function deleteDelivery($deliveryId) {
        // $this->pdo->beginTransaction();
        try {
            $deliveryItems = $this->getItemsForDelivery($deliveryId);
            $deliveryHeader = $this->getById($deliveryId);

            if (!$deliveryHeader) {
                // $this->pdo->rollBack();
                return false; // Delivery not found
            }

            // Revert stock quantities (if it was a stock-increasing type)
            if (in_array($deliveryHeader['type'], ['purchase', 'free_sample', 'other'])) {
                $productModel = new Product($this->db);
                $stockMovementModel = new StockMovement($this->db); // For explicit movement deletion or reversal movement creation

                foreach ($deliveryItems as $item) {
                    $productDetails = $productModel->getById($item['product_id']);
                    if (!$productDetails) {
                        // $this->pdo->rollBack();
                        error_log("Product ID {$item['product_id']} not found for stock reversal during delivery deletion.");
                        return false;
                    }

                    $quantityInBaseUnitToRevert = $item['quantity_received'];
                    if ($item['unit_id'] != $productDetails['base_unit_id']) {
                        $productUnits = $productModel->getUnits($item['product_id']);
                        $conversionFactor = null;
                        foreach ($productUnits as $pu) {
                            if ($pu['id'] == $item['unit_id']) {
                                $conversionFactor = (float)$pu['conversion_factor_to_base_unit'];
                                break;
                            }
                        }
                        if ($conversionFactor === null || $conversionFactor == 0) {
                            // $this->pdo->rollBack();
                            error_log("Conversion factor not found or invalid for stock reversal: product ID {$item['product_id']}, unit ID {$item['unit_id']}.");
                            return false;
                        }
                        $quantityInBaseUnitToRevert = $item['quantity_received'] * $conversionFactor;
                    }

                    $reversalNotes = "Reversal for deleted DEL-{$deliveryId}, Item ID: {$item['id']}. Original qty {$item['quantity_received']} in unit ID {$item['unit_id']}.";

                    // Pass negative quantity in transaction unit to updateStock
                    if (!$productModel->updateStock(
                        $item['product_id'],      // productId
                        'delivery_reversal',      // movementType
                        -(float)$item['quantity_received'], // NEGATIVE quantityInTransactionUnit
                        (int)$item['unit_id'],               // transactionUnitId (unit of the original delivery item)
                        $item['id'],              // relatedDocumentId (original delivery_item_id)
                        'delivery_items',         // relatedDocumentType
                        $reversalNotes            // notes
                    )) {
                        // $this->pdo->rollBack();
                        error_log("Failed to revert stock (create reversal movement) for product ID {$item['product_id']} during delivery deletion.");
                        return false;
                    }
                    // Option 2: Delete the original stock movement (less ideal for auditing)
                    // $stockMovementModel->deleteMovementsByRelatedDocument($item['id'], 'delivery_items');
                    // Then call a simpler product stock recalculation or direct decrement without creating new movement.
                }
            }

            // Delete delivery items (ON DELETE CASCADE should handle this for delivery_items if schema is set)
            // If not, uncomment: $this->db->delete("DELETE FROM delivery_items WHERE delivery_id = :delivery_id", [':delivery_id' => $deliveryId]);

            // Delete delivery header
            $rowCount = $this->db->delete("DELETE FROM {$this->tableName} WHERE id = :id", [':id' => $deliveryId]);

            // Potentially re-evaluate PO status if this delivery was linked to a PO
            // This part needs to run AFTER stock changes and movement creation/deletion is committed if it relies on them.
            // However, since PO status update is just a flag, it can be part of the same transaction.
            if ($deliveryHeader['purchase_order_id']) {
                // This logic is similar to createDelivery's PO status update part.
                // It needs to re-check all remaining deliveries for that PO.
                $poModel = new PurchaseOrder($this->db);
                $poItems = $poModel->getItemsForPo($deliveryHeader['purchase_order_id']);
                $isFullyReceived = true;
                $hasAnyReceipts = false;

                $sqlReceivedQty = "SELECT di.purchase_order_item_id, SUM(di.quantity_received) as total_received
                                   FROM delivery_items di
                                   JOIN deliveries d ON di.delivery_id = d.id
                                   WHERE d.purchase_order_id = :po_id AND di.purchase_order_item_id IS NOT NULL
                                   GROUP BY di.purchase_order_item_id";
                $allReceivedForPo = $this->db->select($sqlReceivedQty, [':po_id' => $deliveryHeader['purchase_order_id']]);

                $receivedQuantities = [];
                foreach($allReceivedForPo as $receivedItem) {
                    $receivedQuantities[$receivedItem['purchase_order_item_id']] = $receivedItem['total_received'];
                    if ($receivedItem['total_received'] > 0) $hasAnyReceipts = true;
                }

                foreach ($poItems as $poItem) {
                    $totalReceivedForPoItem = $receivedQuantities[$poItem['id']] ?? 0;
                    if ($totalReceivedForPoItem < $poItem['quantity_ordered']) {
                        $isFullyReceived = false;
                        // No break here, need to check all items to see if any receipts exist
                    }
                }

                $newPoStatus = 'pending'; // Default if no receipts or not fully received
                if ($hasAnyReceipts) {
                    $newPoStatus = $isFullyReceived ? 'received' : 'partially_received';
                }

                if (!$poModel->updateStatus($deliveryHeader['purchase_order_id'], $newPoStatus)) {
                     // $this->pdo->rollBack();
                     error_log("Failed to update PO status after deleting delivery for PO ID {$deliveryHeader['purchase_order_id']}.");
                     return false;
                }
            }

            // $this->pdo->commit();
            return $rowCount > 0;

        } catch (PDOException $e) {
            // $this->pdo->rollBack();
            error_log("Error deleting delivery ID {$deliveryId}: " . $e->getMessage());
            return false;
        }
    }
}

// Add to Product.php model:
// public function updateStock($productId, $quantityChange) {
//    $sql = "UPDATE products SET quantity_in_stock = quantity_in_stock + :quantity_change, updated_at = CURRENT_TIMESTAMP
//            WHERE id = :product_id";
//    try {
//        $this->db->update($sql, [':quantity_change' => $quantityChange, ':product_id' => $productId]);
//        return true;
//    } catch (PDOException $e) {
//        error_log("Error updating stock for product ID {$productId}: " . $e->getMessage());
//        return false;
//    }
// }

?>
