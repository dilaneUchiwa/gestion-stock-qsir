<?php

require_once ROOT_PATH . '/core/Model.php';

class SalePayment extends Model {

    protected $tableName = 'sale_payments';
    // Example: Define allowed payment methods if you want to validate against a predefined list
    public $allowedPaymentMethods = ["Espèces", "Carte Bancaire", "Virement", "Chèque", "Autre"];


    public function __construct(Database $dbInstance) {
        parent::__construct($dbInstance);
    }

    /**
     * Creates a new sale payment record.
     * @param array $data Payment data: sale_id, payment_date, amount_paid, payment_method, notes
     * @return string|false The ID of the newly created payment or false on failure.
     */
    public function createPayment(array $data) {
        if (empty($data['sale_id']) || empty($data['payment_date']) ||
            !isset($data['amount_paid']) || (float)$data['amount_paid'] <= 0 ||
            empty($data['payment_method'])) {
            error_log("Sale ID, payment date, positive amount paid, and payment method are required.");
            return false;
        }

        // Optional: Validate payment_method against allowed list
        // if (!in_array($data['payment_method'], $this->allowedPaymentMethods)) {
        //     error_log("Invalid payment method specified: " . $data['payment_method']);
        //     return false;
        // }

        $fields = ['sale_id', 'payment_date', 'amount_paid', 'payment_method', 'notes'];
        $params = [];
        $columns = [];
        $placeholders = [];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $columns[] = $field;
                $placeholders[] = ':' . $field;
                // Ensure notes is null if empty string, and numeric types are correctly cast if needed (PDO usually handles it)
                $params[':' . $field] = ($data[$field] === '' && $field === 'notes') ? null : $data[$field];
            }
        }

        // Ensure required fields were in $data
        if (!in_array('sale_id', $columns) || !in_array('payment_date', $columns) || !in_array('amount_paid', $columns) || !in_array('payment_method', $columns)) {
            error_log("One or more required fields are missing from data array for SalePayment creation.");
            return false;
        }


        $sql = "INSERT INTO {$this->tableName} (" . implode(', ', $columns) . ", created_at, updated_at)
                VALUES (" . implode(', ', $placeholders) . ", CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";

        try {
            return $this->db->insert($sql, $params);
        } catch (PDOException $e) {
            error_log("Error creating sale payment: " . $e->getMessage());
            // Check for specific FK violation if sale_id is invalid
            if (strpos($e->getMessage(), 'violates foreign key constraint "sale_payments_sale_id_fkey"') !== false) {
                 error_log("Invalid sale_id provided for sale payment.");
            }
            return false;
        }
    }

    /**
     * Retrieves all payments for a given sale ID.
     * @param int $saleId The ID of the sale.
     * @return array An array of payment records, ordered by payment_date.
     */
    public function getPaymentsForSale(int $saleId): array {
        $sql = "SELECT * FROM {$this->tableName} WHERE sale_id = :sale_id ORDER BY payment_date ASC, id ASC";
        try {
            return $this->db->select($sql, [':sale_id' => $saleId]);
        } catch (PDOException $e) {
            error_log("Error fetching payments for sale ID {$saleId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Calculates the total amount paid for a given sale ID.
     * @param int $saleId The ID of the sale.
     * @return float The total amount paid.
     */
    public function getTotalPaidForSale(int $saleId): float {
        $sql = "SELECT SUM(amount_paid) as total_paid FROM {$this->tableName} WHERE sale_id = :sale_id";
        try {
            $result = $this->db->select($sql, [':sale_id' => $saleId]);
            return $result && isset($result[0]['total_paid']) ? (float)$result[0]['total_paid'] : 0.00;
        } catch (PDOException $e) {
            error_log("Error calculating total paid for sale ID {$saleId}: " . $e->getMessage());
            return 0.00;
        }
    }

    /**
     * Deletes a specific payment record.
     * Note: This should usually be followed by updating the sale's payment status.
     * @param int $paymentId The ID of the payment to delete.
     * @return bool True on success, false on failure.
     */
    public function deletePayment(int $paymentId): bool {
        $sql = "DELETE FROM {$this->tableName} WHERE id = :id";
        try {
            $rowCount = $this->db->delete($sql, [':id' => $paymentId]);
            return $rowCount > 0;
        } catch (PDOException $e) {
            error_log("Error deleting sale payment ID {$paymentId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieves a specific payment record by its ID.
     * @param int $paymentId The ID of the payment.
     * @return mixed Associative array of payment data or false if not found.
     */
    public function getById(int $paymentId) {
        $sql = "SELECT * FROM {$this->tableName} WHERE id = :id";
        try {
            $result = $this->db->select($sql, [':id' => $paymentId]);
            return $result ? $result[0] : false;
        } catch (PDOException $e) {
            error_log("Error fetching sale payment by ID {$paymentId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculates the total amount paid for a sale up to and including a specific payment.
     * @param int $saleId The ID of the sale.
     * @param string $paymentDate The date of the specific payment (YYYY-MM-DD).
     * @param int $paymentId The ID of the specific payment.
     * @return float The total amount paid up to this payment.
     */
    public function getTotalPaidUpTo(int $saleId, string $paymentDate, int $paymentId): float {
        $sql = "SELECT SUM(amount_paid) as total_paid
                FROM {$this->tableName}
                WHERE sale_id = :sale_id
                  AND (payment_date < :payment_date OR (payment_date = :payment_date AND id <= :payment_id))";
        try {
            $result = $this->db->select($sql, [
                ':sale_id' => $saleId,
                ':payment_date' => $paymentDate,
                ':payment_id' => $paymentId
            ]);
            return $result && isset($result[0]['total_paid']) ? (float)$result[0]['total_paid'] : 0.00;
        } catch (PDOException $e) {
            error_log("Error calculating total paid up to payment ID {$paymentId} for sale ID {$saleId}: " . $e->getMessage());
            return 0.00;
        }
    }
}

?>
