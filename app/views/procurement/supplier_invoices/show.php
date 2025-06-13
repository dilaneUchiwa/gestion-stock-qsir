<?php
// $title set by controller
// $title = 'Supplier Invoice Details';
?>

<?php if (empty($invoice)): ?>
    <p>Supplier invoice not found.</p>
    <a href="index.php?url=supplierinvoice/index" class="button-info">Back to List</a>
    <?php return; ?>
<?php endif; ?>

<?php
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    if ($status == 'created_success') echo '<div class="alert alert-success">Supplier Invoice successfully created.</div>';
    if ($status == 'updated_success') echo '<div class="alert alert-success">Supplier Invoice successfully updated.</div>';
    if ($status == 'paid_success') echo '<div class="alert alert-success">Invoice marked as paid.</div>';
    if ($status == 'paid_error') echo '<div class="alert alert-danger">Error marking invoice as paid.</div>';
}
?>

<h2>Supplier Invoice #INV-<?php echo htmlspecialchars($invoice['id']); ?> (<?php echo htmlspecialchars($invoice['invoice_number']); ?>)</h2>
<div style="margin-bottom: 20px;">
    <a href="index.php?url=supplierinvoice/index" class="button-info">Back to List</a>
    <?php if ($invoice['status'] !== 'paid'): ?>
        <a href="index.php?url=supplierinvoice/edit/<?php echo $invoice['id']; ?>" class="button">Edit Invoice</a>
    <?php endif; ?>
    <?php if ($invoice['status'] === 'unpaid' || $invoice['status'] === 'partially_paid'): ?>
        <form action="index.php?url=supplierinvoice/markAsPaid/<?php echo $invoice['id']; ?>" method="POST" style="display:inline; margin-left: 10px;" onsubmit="return confirm('Are you sure you want to mark this invoice as PAID?');">
            <button type="submit" class="button" style="background-color: #28a745;">Mark as Paid</button>
        </form>
    <?php endif; ?>
     <form action="index.php?url=supplierinvoice/destroy/<?php echo $invoice['id']; ?>" method="POST" style="display:inline; margin-left: 10px;" onsubmit="return confirm('Are you sure you want to delete this invoice? This action cannot be undone easily.');">
        <button type="submit" class="button-danger">Delete Invoice</button>
    </form>
</div>

<h3>Invoice Details</h3>
<table class="table" style="width:60%; margin-bottom:20px;">
    <tr><th>Supplier:</th><td><a href="index.php?url=suppliers/show/<?php echo $invoice['supplier_id']; ?>"><?php echo htmlspecialchars($invoice['supplier_name']); ?></a></td></tr>
    <tr><th>Invoice Number:</th><td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td></tr>
    <tr><th>Invoice Date:</th><td><?php echo htmlspecialchars($invoice['invoice_date']); ?></td></tr>
    <tr><th>Due Date:</th><td><?php echo htmlspecialchars($invoice['due_date'] ?? 'N/A'); ?></td></tr>
    <tr><th>Total Amount:</th><td style="font-weight:bold;"><?php echo htmlspecialchars(number_format($invoice['total_amount'], 2)); ?></td></tr>
    <tr><th>Status:</th><td><span class="status-<?php echo htmlspecialchars(strtolower(str_replace('_', '-', $invoice['status']))); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $invoice['status']))); ?></span></td></tr>
    <tr><th>Payment Date:</th><td><?php echo htmlspecialchars($invoice['payment_date'] ?? 'N/A'); ?></td></tr>

    <tr><th>Linked Delivery:</th>
        <td>
            <?php if ($invoice['delivery_id']): ?>
                <a href="index.php?url=delivery/show/<?php echo $invoice['delivery_id']; ?>">DEL-<?php echo htmlspecialchars($invoice['delivery_id']); ?></a>
            <?php else: echo 'N/A'; endif; ?>
        </td>
    </tr>
    <tr><th>Linked Purchase Order:</th>
        <td>
            <?php if ($invoice['purchase_order_id']): ?>
                <a href="index.php?url=purchaseorder/show/<?php echo $invoice['purchase_order_id']; ?>">PO-<?php echo htmlspecialchars($invoice['purchase_order_id']); ?></a>
            <?php else: echo 'N/A'; endif; ?>
        </td>
    </tr>

    <tr><th>Notes:</th><td><?php echo nl2br(htmlspecialchars($invoice['notes'] ?? 'N/A')); ?></td></tr>
    <tr><th>Recorded At:</th><td><?php echo htmlspecialchars($invoice['created_at']); ?></td></tr>
    <tr><th>Last Updated:</th><td><?php echo htmlspecialchars($invoice['updated_at']); ?></td></tr>
</table>

<style>
    .status-unpaid { color: orange; font-weight: bold; }
    .status-paid { color: green; font-weight: bold; }
    .status-partially-paid { color: darkgoldenrod; font-weight: bold; }
    .status-cancelled { color: red; font-weight: bold; }
</style>
