<?php
// $title is set by controller
// $title = 'Purchase Order Details';
?>

<?php if (empty($purchaseOrder)): ?>
    <p>Purchase order not found.</p>
    <a href="index.php?url=purchaseorder/index" class="button-info">Back to List</a>
    <?php return; ?>
<?php endif; ?>

<?php
// Display status messages
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    if ($status == 'created_success') echo '<div class="alert alert-success">Purchase Order successfully created.</div>';
    if ($status == 'updated_success') echo '<div class="alert alert-success">Purchase Order successfully updated.</div>';
    if ($status == 'cancelled_success') echo '<div class="alert alert-success">Purchase Order successfully cancelled.</div>';
    if ($status == 'cancelled_error') echo '<div class="alert alert-danger">Error cancelling purchase order.</div>';
    if ($status == 'cancel_failed_status') echo '<div class="alert alert-warning">Could not cancel: Order status does not allow cancellation.</div>';
}
?>

<h2>Purchase Order #PO-<?php echo htmlspecialchars($purchaseOrder['id']); ?></h2>
<div style="margin-bottom: 20px;">
    <a href="index.php?url=purchaseorder/index" class="button-info">Back to List</a>
    <?php if (in_array($purchaseOrder['status'], ['pending', 'partially_received'])): ?>
        <a href="index.php?url=purchaseorder/edit/<?php echo $purchaseOrder['id']; ?>" class="button">Edit Order</a>
    <?php endif; ?>
     <?php if (in_array($purchaseOrder['status'], ['pending', 'partially_received'])): ?>
        <form action="index.php?url=purchaseorder/cancel/<?php echo $purchaseOrder['id']; ?>" method="POST" style="display:inline; margin-left: 10px;" onsubmit="return confirm('Are you sure you want to cancel this PO?');">
            <button type="submit" class="button-danger">Cancel Order</button>
        </form>
    <?php endif; ?>
    <!-- Link to create delivery for this PO -->
    <?php if (in_array($purchaseOrder['status'], ['pending', 'partially_received'])): ?>
        <a href="index.php?url=delivery/create&po_id=<?php echo $purchaseOrder['id']; ?>" class="button" style="background-color: #007bff; margin-left:10px;">Receive Items</a>
    <?php endif; ?>
</div>

<h3>Order Details</h3>
<table class="table" style="width:50%; margin-bottom:20px;">
    <tr><th>Supplier:</th><td><?php echo htmlspecialchars($purchaseOrder['supplier_name']); ?> (ID: <?php echo htmlspecialchars($purchaseOrder['supplier_id']); ?>)</td></tr>
    <tr><th>Order Date:</th><td><?php echo htmlspecialchars($purchaseOrder['order_date']); ?></td></tr>
    <tr><th>Expected Delivery Date:</th><td><?php echo htmlspecialchars($purchaseOrder['expected_delivery_date'] ?? 'N/A'); ?></td></tr>
    <tr><th>Status:</th><td><span class="status-<?php echo htmlspecialchars(strtolower(str_replace('_', '-', $purchaseOrder['status']))); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $purchaseOrder['status']))); ?></span></td></tr>
    <tr><th>Notes:</th><td><?php echo nl2br(htmlspecialchars($purchaseOrder['notes'] ?? 'N/A')); ?></td></tr>
    <tr><th>Created At:</th><td><?php echo htmlspecialchars($purchaseOrder['created_at']); ?></td></tr>
    <tr><th>Last Updated:</th><td><?php echo htmlspecialchars($purchaseOrder['updated_at']); ?></td></tr>
</table>

<h3>Order Items</h3>
<?php if (empty($purchaseOrder['items'])): ?>
    <p>No items found for this purchase order.</p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>Product ID</th>
                <th>Product Name</th>
                <th>Quantity Ordered</th>
                <th>Unit Price</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $calculatedTotal = 0;
            foreach ($purchaseOrder['items'] as $item):
                // $subtotal = $item['quantity_ordered'] * $item['unit_price']; // sub_total is now a generated field
                $subtotal = $item['sub_total'];
                $calculatedTotal += $subtotal;
            ?>
            <tr>
                <td><?php echo htmlspecialchars($item['product_id']); ?></td>
                <td><?php echo htmlspecialchars($item['product_name']); ?> (<?php echo htmlspecialchars($item['unit_of_measure']); ?>)</td>
                <td style="text-align: right;"><?php echo htmlspecialchars($item['quantity_ordered']); ?></td>
                <td style="text-align: right;"><?php echo htmlspecialchars(number_format($item['unit_price'], 2)); ?></td>
                <td style="text-align: right;"><?php echo htmlspecialchars(number_format($subtotal, 2)); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" style="text-align:right;">Order Total (from DB):</th>
                <td style="text-align: right;"><strong><?php echo htmlspecialchars(number_format($purchaseOrder['total_amount'], 2)); ?></strong></td>
            </tr>
            <?php if (abs($calculatedTotal - $purchaseOrder['total_amount']) > 0.001) : // Check if calculated total matches stored total ?>
            <tr>
                <th colspan="4" style="text-align:right; color: orange;">Calculated Total (from items):</th>
                <td style="text-align: right; color: orange;"><strong><?php echo htmlspecialchars(number_format($calculatedTotal, 2)); ?></strong></td>
            </tr>
            <?php endif; ?>
        </tfoot>
    </table>
<?php endif; ?>

<style>
    .status-pending { color: orange; font-weight: bold; }
    .status-received { color: green; font-weight: bold; }
    .status-partially-received { color: darkgoldenrod; font-weight: bold; }
    .status-cancelled { color: red; font-weight: bold; }
</style>
