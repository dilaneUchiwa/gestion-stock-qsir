<?php

require_once ROOT_PATH . '/core/Model.php';

class SupplierInvoice extends Model {

    protected $tableName = 'supplier_invoices';
    public $allowedStatuses = ['unpaid', 'paid', 'partially_paid', 'cancelled'];

    public function __construct(Database $dbInstance) {
        parent::__construct($dbInstance);
    }

    /**
     * Creates a new supplier invoice.
     * @param array $data Invoice data
     * @return string|false The ID of the newly created invoice or false on failure.
     */
    public function createInvoice(array $data) {
        // Basic validation
        if (empty($data['supplier_id']) || empty($data['invoice_number']) || empty($data['invoice_date']) || !isset($data['total_amount'])) {
            error_log("Supplier, Invoice Number, Invoice Date, and Total Amount are required.");
            return false;
        }
        if (isset($data['status']) && !in_array($data['status'], $this->allowedStatuses)) {
            error_log("Invalid status for supplier invoice.");
            return false;
        }
        if (!is_numeric($data['total_amount']) || $data['total_amount'] < 0) {
            error_log("Total amount must be a non-negative number.");
            return false;
        }


        $fields = ['delivery_id', 'purchase_order_id', 'supplier_id', 'invoice_number', 'invoice_date',
                   'due_date', 'total_amount', 'status', 'payment_date', 'notes'];
        $params = [];
        $columns = [];
        $placeholders = [];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $columns[] = $field;
                $placeholders[] = ':' . $field;
                // Handle empty optional fields as NULL
                if ($data[$field] === '' && in_array($field, ['delivery_id', 'purchase_order_id', 'due_date', 'payment_date', 'notes'])) {
                     $params[':' . $field] = null;
                } else {
                    $params[':' . $field] = $data[$field];
                }
            }
        }
         if (!isset($data['status'])) { // Default status
            $columns[] = 'status';
            $placeholders[] = ':status';
            $params[':status'] = 'unpaid';
        }


        $sql = "INSERT INTO {$this->tableName} (" . implode(', ', $columns) . ", created_at, updated_at)
                VALUES (" . implode(', ', $placeholders) . ", CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";

        try {
            return $this->db->insert($sql, $params);
        } catch (PDOException $e) {
            if ($e->getCode() == '23505') { // Unique constraint (supplier_id, invoice_number)
                error_log("Error creating supplier invoice: Duplicate invoice number for this supplier. " . $e->getMessage());
            } else {
                error_log("Error creating supplier invoice: " . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Retrieves a single supplier invoice by its ID with supplier name.
     * @param int $id The ID of the invoice.
     * @return mixed The invoice data or false if not found.
     */
    public function getByIdWithDetails($id) {
        $sql = "SELECT si.*, s.name as supplier_name,
                       po.id as po_ref_id, del.id as del_ref_id
                FROM {$this->tableName} si
                JOIN suppliers s ON si.supplier_id = s.id
                LEFT JOIN purchase_orders po ON si.purchase_order_id = po.id
                LEFT JOIN deliveries del ON si.delivery_id = del.id
                WHERE si.id = :id";
        try {
            $result = $this->db->select($sql, [':id' => $id]);
            return $result ? $result[0] : false;
        } catch (PDOException $e) {
            error_log("Error fetching supplier invoice by ID {$id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieves all supplier invoices with supplier names.
     * @return array An array of all supplier invoices.
     */
    public function getAllWithDetails() {
        $sql = "SELECT si.*, s.name as supplier_name
                FROM {$this->tableName} si
                JOIN suppliers s ON si.supplier_id = s.id
                ORDER BY si.invoice_date DESC, si.id DESC";
        try {
            return $this->db->select($sql);
        } catch (PDOException $e) {
            error_log("Error fetching all supplier invoices: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Updates an existing supplier invoice.
     * @param int $id The ID of the invoice to update.
     * @param array $data Data to update.
     * @return bool True on success, false on failure.
     */
    public function updateInvoice($id, array $data) {
        if (empty($data)) {
            return false;
        }
        if (isset($data['status']) && !in_array($data['status'], $this->allowedStatuses)) {
            error_log("Invalid status for supplier invoice update.");
            return false;
        }
         if (isset($data['total_amount']) && (!is_numeric($data['total_amount']) || $data['total_amount'] < 0)) {
            error_log("Total amount must be a non-negative number for update.");
            return false;
        }

        $fields = ['delivery_id', 'purchase_order_id', 'supplier_id', 'invoice_number', 'invoice_date',
                   'due_date', 'total_amount', 'status', 'payment_date', 'notes']; // Does not include paid_amount directly
        $params = [':id' => $id];
        $setParts = [];
        $totalAmountChanged = false;

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) { // Use array_key_exists for nullable fields
                $setParts[] = "{$field} = :{$field}";
                 if ($data[$field] === '' && in_array($field, ['delivery_id', 'purchase_order_id', 'due_date', 'payment_date', 'notes'])) {
                     $params[':' . $field] = null;
                } else {
                    $params[':' . $field] = $data[$field];
                }
                if ($field === 'total_amount') {
                    $totalAmountChanged = true;
                }
            }
        }

        if (empty($setParts)) {
            return true; // Nothing to update
        }

        $setParts[] = "updated_at = CURRENT_TIMESTAMP";
        $sql = "UPDATE {$this->tableName} SET " . implode(', ', $setParts) . " WHERE id = :id";

        try {
            $this->db->update($sql, $params);
            if ($totalAmountChanged) {
                $this->updateInvoicePaymentStatus($id); // Re-evaluate status if total amount changed
            }
            return true;
        } catch (PDOException $e) {
            if ($e->getCode() == '23505') {
                error_log("Error updating supplier invoice: Duplicate invoice number for this supplier. " . $e->getMessage());
            } else {
                error_log("Error updating supplier invoice ID {$id}: " . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Adds a payment record for a supplier invoice.
     * @param array $paymentData Payment details.
     * @return string|false ID of the new payment record or false on failure.
     */
    public function addPayment(array $paymentData) {
        // Validation
        if (empty($paymentData['supplier_invoice_id']) || empty($paymentData['payment_date']) || !isset($paymentData['amount_paid'])) {
            error_log("Supplier Invoice ID, Payment Date, and Amount Paid are required for payment.");
            return false;
        }
        if (!is_numeric($paymentData['amount_paid']) || $paymentData['amount_paid'] <= 0) {
            error_log("Amount paid must be a positive number.");
            return false;
        }

        $invoice = $this->getById((int)$paymentData['supplier_invoice_id']);
        if (!$invoice) {
            error_log("Supplier invoice not found for payment (ID: {$paymentData['supplier_invoice_id']}).");
            return false;
        }

        $currentPaidAmount = (float)($invoice['paid_amount'] ?? 0.00);
        $invoiceTotal = (float)$invoice['total_amount'];
        if (((float)$paymentData['amount_paid'] + $currentPaidAmount) > ($invoiceTotal + 0.001)) { // Add tolerance for float comparison
            error_log("Overpayment attempt on supplier invoice ID {$paymentData['supplier_invoice_id']}. Amount: {$paymentData['amount_paid']}, Current Paid: {$currentPaidAmount}, Total: {$invoiceTotal}");
            return "Le montant du paiement dépasse le solde dû de la facture.";
        }

        $fields = ['supplier_invoice_id', 'payment_date', 'amount_paid', 'payment_method', 'notes'];
        $params = [];
        $columns = [];
        $placeholders = [];

        foreach ($fields as $field) {
            if (isset($paymentData[$field])) {
                $columns[] = $field;
                $placeholders[] = ':' . $field;
                $params[':' . $field] = ($paymentData[$field] === '' && in_array($field, ['payment_method', 'notes'])) ? null : $paymentData[$field];
            }
        }

        if (empty($columns)) return false; // Should not happen if required fields are present

        $sql = "INSERT INTO supplier_invoice_payments (" . implode(', ', $columns) . ", created_at, updated_at)
                VALUES (" . implode(', ', $placeholders) . ", CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";

        try {
            $paymentId = $this->db->insert($sql, $params);
            if ($paymentId) {
                // After adding payment, update the invoice status and paid_amount
                $this->updateInvoicePaymentStatus((int)$paymentData['supplier_invoice_id']);
            }
            return $paymentId;
        } catch (PDOException $e) {
            error_log("Error adding payment for supplier invoice ID {$paymentData['supplier_invoice_id']}: " . $e->getMessage());
            return false;
        }
    }


    /**
     * Updates the status and paid_amount of a supplier invoice based on its payments.
     * @param int $invoiceId The ID of the invoice to update.
     * @return bool True on success, false on failure.
     */
    public function updateInvoicePaymentStatus(int $invoiceId) {
        $invoice = $this->getById($invoiceId);
        if (!$invoice) {
            error_log("Invoice not found (ID: {$invoiceId}) for status update.");
            return false;
        }

        $sqlSum = "SELECT SUM(amount_paid) as total_payments FROM supplier_invoice_payments WHERE supplier_invoice_id = :invoice_id";
        $sumResult = $this->db->select($sqlSum, [':invoice_id' => $invoiceId]);
        $currentTotalPaid = ($sumResult && $sumResult[0] && $sumResult[0]['total_payments'] !== null) ? (float)$sumResult[0]['total_payments'] : 0.00;

        $newStatus = $invoice['status']; // Default to current status
        $invoiceTotal = (float)$invoice['total_amount'];

        if ($invoice['status'] !== 'cancelled') { // Do not change status if already cancelled
            if ($currentTotalPaid <= 0) {
                $newStatus = 'unpaid';
            } elseif ($currentTotalPaid > 0 && $currentTotalPaid < $invoiceTotal) {
                $newStatus = 'partially_paid';
            } elseif ($currentTotalPaid >= $invoiceTotal) {
                $newStatus = 'paid';
            }
        }

        $paymentDateToSet = $invoice['payment_date']; // Keep existing by default
        if ($newStatus === 'paid' && $invoice['payment_date'] === null) {
            // Set payment_date to the date of the latest payment if invoice is now fully paid and had no payment_date
            $sqlLatestPayment = "SELECT MAX(payment_date) as latest_payment_date FROM supplier_invoice_payments WHERE supplier_invoice_id = :invoice_id";
            $latestPaymentResult = $this->db->select($sqlLatestPayment, [':invoice_id' => $invoiceId]);
            if ($latestPaymentResult && $latestPaymentResult[0] && $latestPaymentResult[0]['latest_payment_date']) {
                $paymentDateToSet = $latestPaymentResult[0]['latest_payment_date'];
            } else {
                $paymentDateToSet = date('Y-m-d'); // Fallback to today if no payments found (should not happen if paid)
            }
        } elseif ($newStatus === 'unpaid' || $newStatus === 'partially_paid') {
             // Clear payment_date if it becomes unpaid or partially_paid, unless it was already set (e.g. first partial payment date)
             // For simplicity, let's clear it if not fully paid. Business rule might differ.
             // Or, keep the date of the first payment for 'partially_paid'. For now, let's keep it simple:
             // If it's 'partially_paid' and payment_date is null, set it to the first payment date.
             // If it's 'unpaid', payment_date should be null.
            if ($newStatus === 'unpaid') {
                $paymentDateToSet = null;
            } elseif ($newStatus === 'partially_paid' && $invoice['payment_date'] === null) {
                 $sqlFirstPayment = "SELECT MIN(payment_date) as first_payment_date FROM supplier_invoice_payments WHERE supplier_invoice_id = :invoice_id";
                 $firstPaymentResult = $this->db->select($sqlFirstPayment, [':invoice_id' => $invoiceId]);
                 if ($firstPaymentResult && $firstPaymentResult[0] && $firstPaymentResult[0]['first_payment_date']) {
                     $paymentDateToSet = $firstPaymentResult[0]['first_payment_date'];
                 }
            }
        }


        $updateSql = "UPDATE {$this->tableName}
                      SET paid_amount = :currentTotalPaid,
                          status = :newStatus,
                          payment_date = :payment_date_to_set,
                          updated_at = CURRENT_TIMESTAMP
                      WHERE id = :invoiceId";
        try {
            $this->db->update($updateSql, [
                ':currentTotalPaid' => $currentTotalPaid,
                ':newStatus' => $newStatus,
                ':payment_date_to_set' => $paymentDateToSet,
                ':invoiceId' => $invoiceId
            ]);
            return true;
        } catch (PDOException $e) {
            error_log("Error updating supplier invoice (ID: {$invoiceId}) payment status and paid amount: " . $e->getMessage());
            return false;
        }
    }


    /**
     * Deletes a supplier invoice.
     * @param int $id The ID of the invoice to delete.
     * @return bool True on success, false on failure.
     */
    public function deleteInvoice($id) {
        // Add checks here if payments are linked or if it's an archived record.
        // For now, direct delete. `supplier_invoice_payments` has ON DELETE CASCADE.
        $sql = "DELETE FROM {$this->tableName} WHERE id = :id";
        try {
            $rowCount = $this->db->delete($sql, [':id' => $id]);
            return $rowCount > 0;
        } catch (PDOException $e) {
            error_log("Error deleting supplier invoice ID {$id}: " . $e->getMessage());
            return false;
        }
    }

     /**
     * Retrieves a single supplier invoice by its ID (basic info).
     * @param int $id The ID of the invoice.
     * @return mixed The invoice data or false if not found.
     */
    public function getById($id) {
        $sql = "SELECT * FROM {$this->tableName} WHERE id = :id";
        try {
            $result = $this->db->select($sql, [':id' => $id]);
            return $result ? $result[0] : false;
        } catch (PDOException $e) {
            error_log("Error fetching supplier invoice by ID {$id}: " . $e->getMessage());
            return false;
        }
    }
}
?>
