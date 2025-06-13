<?php
// $title is already set by the controller
// $title = 'Purchase Orders';
?>

<h2>Purchase Orders</h2>

<?php
// Display status messages from GET params
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    if ($status == 'created_success') echo '<div class="alert alert-success">Purchase Order successfully created.</div>';
    if ($status == 'updated_success') echo '<div class="alert alert-success">Purchase Order successfully updated.</div>';
    if ($status == 'cancelled_success') echo '<div class="alert alert-success">Purchase Order successfully cancelled.</div>';
    if ($status == 'cancelled_error') echo '<div class="alert alert-danger">Error cancelling purchase order.</div>';
    if ($status == 'cancel_failed_status') echo '<div class="alert alert-warning">Could not cancel: Order status does not allow cancellation.</div>';
    if ($status == 'deleted_success') echo '<div class="alert alert-success">Purchase Order successfully deleted.</div>';
    if ($status == 'delete_error') echo '<div class="alert alert-danger">Error deleting purchase order. It might be referenced.</div>';
    if ($status == 'delete_failed_status') echo '<div class="alert alert-warning">Could not delete: Order status does not allow deletion.</div>';
}
?>

<p><a href="index.php?url=purchaseorder/create" class="button">Create New Purchase Order</a></p>

<?php if (empty($purchaseOrders)): ?>
    <p>No purchase orders found.</p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Supplier</th>
                <th>Order Date</th>
                <th>Expected Delivery</th>
                <th>Status</th>
                <th>Total Amount</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($purchaseOrders as $po): ?>
            <tr>
                <td>PO-<?php echo htmlspecialchars($po['id']); ?></td>
                <td><?php echo htmlspecialchars($po['supplier_name']); ?></td>
                <td><?php echo htmlspecialchars($po['order_date']); ?></td>
                <td><?php echo htmlspecialchars($po['expected_delivery_date'] ?? 'N/A'); ?></td>
                <td><span class="status-<?php echo htmlspecialchars(strtolower(str_replace('_', '-', $po['status']))); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $po['status']))); ?></span></td>
                <td style="text-align: right;"><?php echo htmlspecialchars(number_format($po['total_amount'], 2)); ?></td>
                <td>
                    <a href="index.php?url=purchaseorder/show/<?php echo $po['id']; ?>" class="button-info">View</a>
                    <?php if (in_array($po['status'], ['pending', 'partially_received'])): // Allow edit only for certain statuses ?>
                        <a href="index.php?url=purchaseorder/edit/<?php echo $po['id']; ?>" class="button">Edit</a>
                    <?php endif; ?>
                    <?php if (in_array($po['status'], ['pending', 'partially_received'])): ?>
                         <form action="index.php?url=purchaseorder/cancel/<?php echo $po['id']; ?>" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to cancel this PO?');">
                            <button type="submit" class="button-danger">Cancel</button>
                        </form>
                    <?php endif; ?>
                     <?php if (in_array($po['status'], ['pending', 'cancelled'])): // Example: Allow deletion only for pending or cancelled orders ?>
                        <!-- <form action="index.php?url=purchaseorder/destroy/<?php echo $po['id']; ?>" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to permanently delete this PO? This action cannot be undone.');">
                            <button type="submit" class="button-danger">Delete</button>
                        </form> -->
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <style>
        .status-pending { color: orange; }
        .status-received { color: green; }
        .status-partially-received { color: darkgoldenrod; }
        .status-cancelled { color: red; }
    </style>
<?php endif; ?>
