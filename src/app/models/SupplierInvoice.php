<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/Model.php';

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
                   'due_date', 'total_amount', 'status', 'payment_date', 'notes'];
        $params = [':id' => $id];
        $setParts = [];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) { // Use array_key_exists for nullable fields
                $setParts[] = "{$field} = :{$field}";
                 if ($data[$field] === '' && in_array($field, ['delivery_id', 'purchase_order_id', 'due_date', 'payment_date', 'notes'])) {
                     $params[':' . $field] = null;
                } else {
                    $params[':' . $field] = $data[$field];
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
     * Updates the status of a supplier invoice.
     * @param int $id Invoice ID.
     * @param string $newStatus New status.
     * @param string|null $paymentDate Optional payment date, defaults to today if status is 'paid'.
     * @return bool True on success, false on failure.
     */
    public function updatePaymentStatus($id, $newStatus, $paymentDate = null) {
        if (!in_array($newStatus, $this->allowedStatuses)) {
            error_log("Invalid status '{$newStatus}' for invoice ID {$id}.");
            return false;
        }

        $data = ['status' => $newStatus];
        if ($newStatus === 'paid' && $paymentDate === null) {
            $data['payment_date'] = date('Y-m-d'); // Default payment date to today if marking as paid
        } elseif ($paymentDate !== null) {
            $data['payment_date'] = $paymentDate;
        } elseif ($newStatus !== 'paid' && $newStatus !== 'partially_paid') {
            // If status is not paid/partially_paid, clear payment_date
            $data['payment_date'] = null;
        }

        return $this->updateInvoice($id, $data);
    }

    /**
     * Deletes a supplier invoice.
     * @param int $id The ID of the invoice to delete.
     * @return bool True on success, false on failure.
     */
    public function deleteInvoice($id) {
        // Add checks here if payments are linked or if it's an archived record.
        // For now, direct delete.
        $sql = "DELETE FROM {$this->tableName} WHERE id = :id";
        try {
            $rowCount = $this->db->delete($sql, [':id' => $id]);
            return $rowCount > 0;
        } catch (PDOException $e) {
            error_log("Error deleting supplier invoice ID {$id}: " . $e->getMessage());
            return false;
        }
    }
}
?>
