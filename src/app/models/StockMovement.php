<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/Model.php';

class StockMovement extends Model {

    protected $tableName = 'stock_movements';
    public $allowedTypes = ['in_delivery', 'out_sale', 'adjustment_in', 'adjustment_out', 'split_in', 'split_out', 'initial_stock', 'delivery_reversal', 'sale_reversal'];


    public function __construct(Database $dbInstance) {
        parent::__construct($dbInstance);
    }

    /**
     * Creates a new stock movement.
     * @param array $data Movement data (product_id, type, quantity, etc.)
     * @return string|false The ID of the newly created movement or false on failure.
     */
    public function createMovement(array $data) {
        if (empty($data['product_id']) || empty($data['type']) || !isset($data['quantity']) || $data['quantity'] <= 0) {
            error_log("Product ID, type, and a positive quantity are required for stock movement.");
            return false;
        }
        if (!in_array($data['type'], $this->allowedTypes)) {
            error_log("Invalid stock movement type: {$data['type']}.");
            return false;
        }

        $fields = ['product_id', 'type', 'quantity', 'movement_date',
                   'related_document_id', 'related_document_type', 'notes'];
        $params = [];
        $columns = [];
        $placeholders = [];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $columns[] = $field;
                $placeholders[] = ':' . $field;
                 $params[':' . $field] = ($data[$field] === '' && in_array($field, ['related_document_id', 'related_document_type', 'notes', 'movement_date'])) ? null : $data[$field];
            }
        }

        if (!isset($data['movement_date'])) {
            $columns[] = 'movement_date';
            $placeholders[] = ':movement_date';
            $params[':movement_date'] = date('Y-m-d H:i:s'); // Default to now if not provided
        }


        $sql = "INSERT INTO {$this->tableName} (" . implode(', ', $columns) . ")
                VALUES (" . implode(', ', $placeholders) . ")"; // created_at is auto by DB

        try {
            return $this->db->insert($sql, $params);
        } catch (PDOException $e) {
            error_log("Error creating stock movement: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieves movements for a specific product, optionally within a date range.
     * @param int $productId
     * @param array|null $dateRange ['start_date' => YYYY-MM-DD, 'end_date' => YYYY-MM-DD]
     * @return array An array of movements.
     */
    public function getMovementsByProduct($productId, $dateRange = null) {
        $sql = "SELECT sm.*, p.name as product_name
                FROM {$this->tableName} sm
                JOIN products p ON sm.product_id = p.id
                WHERE sm.product_id = :product_id";
        $params = [':product_id' => $productId];

        if ($dateRange && isset($dateRange['start_date']) && isset($dateRange['end_date'])) {
            $sql .= " AND sm.movement_date >= :start_date AND sm.movement_date <= :end_date";
            $params[':start_date'] = $dateRange['start_date'] . ' 00:00:00';
            $params[':end_date'] = $dateRange['end_date'] . ' 23:59:59';
        }
        $sql .= " ORDER BY sm.movement_date DESC, sm.id DESC";

        try {
            return $this->db->select($sql, $params);
        } catch (PDOException $e) {
            error_log("Error fetching movements for product ID {$productId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Calculates the current stock for a product based on its movements.
     * This is an alternative to reading products.quantity_in_stock.
     * @param int $productId
     * @return int Current calculated stock.
     */
    public function getCurrentStockCalculated($productId) {
        $sql = "SELECT type, SUM(quantity) as total_quantity
                FROM {$this->tableName}
                WHERE product_id = :product_id
                GROUP BY type";
        $params = [':product_id' => $productId];

        $currentStock = 0;
        try {
            $movementsSummary = $this->db->select($sql, $params);
            foreach ($movementsSummary as $mov) {
                if (in_array($mov['type'], ['in_delivery', 'adjustment_in', 'split_in', 'initial_stock', 'sale_reversal'])) {
                    $currentStock += (int)$mov['total_quantity'];
                } elseif (in_array($mov['type'], ['out_sale', 'adjustment_out', 'split_out', 'delivery_reversal'])) {
                    $currentStock -= (int)$mov['total_quantity'];
                }
            }
            return $currentStock;
        } catch (PDOException $e) {
            error_log("Error calculating stock for product ID {$productId}: " . $e->getMessage());
            return 0; // Or throw exception
        }
    }

    /**
     * Deletes movements based on related document ID and type.
     * Used when a source document (like a delivery or sale) is deleted.
     * @param int $relatedDocumentId
     * @param string $relatedDocumentType
     * @return bool True on success, false on failure.
     */
    public function deleteMovementsByRelatedDocument($relatedDocumentId, $relatedDocumentType) {
        if (empty($relatedDocumentId) || empty($relatedDocumentType)) {
            error_log("Related document ID and type are required to delete movements.");
            return false;
        }
        $sql = "DELETE FROM {$this->tableName}
                WHERE related_document_id = :related_document_id
                AND related_document_type = :related_document_type";
        try {
            $this->db->delete($sql, [
                ':related_document_id' => $relatedDocumentId,
                ':related_document_type' => $relatedDocumentType
            ]);
            return true;
        } catch (PDOException $e) {
            error_log("Error deleting stock movements for {$relatedDocumentType} ID {$relatedDocumentId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieves movements by type(s) and optionally within a date range.
     * @param array $types Array of movement types (e.g., ['in_delivery', 'adjustment_in'])
     * @param array|null $dateRange ['start_date' => YYYY-MM-DD, 'end_date' => YYYY-MM-DD]
     * @param int|null $productId Optional product ID to filter by.
     * @return array An array of movements.
     */
    public function getMovementsByTypeAndDateRange(array $types, $dateRange = null, $productId = null) {
        if (empty($types)) {
            return [];
        }
        // Ensure types are safe for IN clause
        $placeholders = implode(',', array_fill(0, count($types), '?'));
        $sql = "SELECT sm.*, p.name as product_name
                FROM {$this->tableName} sm
                JOIN products p ON sm.product_id = p.id
                WHERE sm.type IN ({$placeholders})";

        $params = $types;

        if ($productId !== null) {
            $sql .= " AND sm.product_id = ?";
            $params[] = $productId;
        }

        if ($dateRange && !empty($dateRange['start_date']) && !empty($dateRange['end_date'])) {
            $sql .= " AND sm.movement_date >= ? AND sm.movement_date <= ?";
            $params[] = $dateRange['start_date'] . ' 00:00:00';
            $params[] = $dateRange['end_date'] . ' 23:59:59';
        }
        $sql .= " ORDER BY sm.movement_date DESC, sm.id DESC";

        try {
            // Need to use a generic query execution method that can handle varying param counts for IN clause.
            // For simplicity, if db->select can handle array of params for IN directly, great.
            // Otherwise, this might need adjustment based on how db->select prepares statements.
            // Assuming db->select can take the $params array as is for PDO execute.
            return $this->db->select($sql, $params);
        } catch (PDOException $e) {
            error_log("Error fetching movements by type and date range: " . $e->getMessage());
            return [];
        }
    }
}
?>
