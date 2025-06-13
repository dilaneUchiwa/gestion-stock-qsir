<?php
// $title is set by controller
// $title = 'Supplier Invoices';
?>

<h2>Supplier Invoices</h2>

<?php
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    if ($status == 'created_success') echo '<div class="alert alert-success">Supplier Invoice successfully created.</div>';
    if ($status == 'updated_success') echo '<div class="alert alert-success">Supplier Invoice successfully updated.</div>';
    if ($status == 'paid_success') echo '<div class="alert alert-success">Invoice marked as paid.</div>';
    if ($status == 'paid_error') echo '<div class="alert alert-danger">Error marking invoice as paid.</div>';
    if ($status == 'deleted_success') echo '<div class="alert alert-success">Supplier Invoice successfully deleted.</div>';
    if ($status == 'delete_error') echo '<div class="alert alert-danger">Error deleting supplier invoice.</div>';
    if ($status == 'delete_not_found') echo '<div class="alert alert-danger">Error: Invoice to delete not found.</div>';
}
?>

<p>
    <a href="index.php?url=supplierinvoice/create" class="button">Create New Invoice</a>
    <span style="margin: 0 10px;">or link from:</span>
    <a href="index.php?url=delivery/index" class="button-info">Deliveries</a>
    <a href="index.php?url=purchaseorder/index" class="button-info" style="margin-left:5px;">Purchase Orders</a>

</p>

<?php if (empty($invoices)): ?>
    <p>No supplier invoices found.</p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Invoice #</th>
                <th>Supplier</th>
                <th>Invoice Date</th>
                <th>Due Date</th>
                <th>Total Amount</th>
                <th>Status</th>
                <th>Payment Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($invoices as $invoice): ?>
            <tr>
                <td>INV-<?php echo htmlspecialchars($invoice['id']); ?></td>
                <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                <td><?php echo htmlspecialchars($invoice['supplier_name']); ?></td>
                <td><?php echo htmlspecialchars($invoice['invoice_date']); ?></td>
                <td><?php echo htmlspecialchars($invoice['due_date'] ?? 'N/A'); ?></td>
                <td style="text-align: right;"><?php echo htmlspecialchars(number_format($invoice['total_amount'], 2)); ?></td>
                <td><span class="status-<?php echo htmlspecialchars(strtolower(str_replace('_', '-', $invoice['status']))); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $invoice['status']))); ?></span></td>
                <td><?php echo htmlspecialchars($invoice['payment_date'] ?? 'N/A'); ?></td>
                <td>
                    <a href="index.php?url=supplierinvoice/show/<?php echo $invoice['id']; ?>" class="button-info">View</a>
                    <?php if ($invoice['status'] !== 'paid'): // Example: Allow edit if not paid ?>
                        <a href="index.php?url=supplierinvoice/edit/<?php echo $invoice['id']; ?>" class="button">Edit</a>
                    <?php endif; ?>
                     <?php if ($invoice['status'] === 'unpaid' || $invoice['status'] === 'partially_paid'): ?>
                        <form action="index.php?url=supplierinvoice/markAsPaid/<?php echo $invoice['id']; ?>" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to mark this invoice as PAID?');">
                            <button type="submit" class="button" style="background-color: #28a745;">Mark Paid</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <style>
        .status-unpaid { color: orange; font-weight: bold; }
        .status-paid { color: green; font-weight: bold; }
        .status-partially-paid { color: darkgoldenrod; font-weight: bold; }
        .status-cancelled { color: red; font-weight: bold; }
    </style>
<?php endif; ?>
