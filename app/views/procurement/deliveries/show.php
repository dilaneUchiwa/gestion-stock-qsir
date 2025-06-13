<?php
// $title set by controller
// $title = 'Delivery Details';
?>

<?php if (empty($delivery)): ?>
    <p>Delivery not found.</p>
    <a href="index.php?url=delivery/index" class="button-info">Back to List</a>
    <?php return; ?>
<?php endif; ?>

<?php
if (isset($_GET['status']) && $_GET['status'] == 'created_success') {
    echo '<div class="alert alert-success">Delivery successfully recorded. Stock and PO status updated.</div>';
}
?>

<h2>Delivery #DEL-<?php echo htmlspecialchars($delivery['id']); ?></h2>
<div style="margin-bottom: 20px;">
    <a href="index.php?url=delivery/index" class="button-info">Back to List</a>
    <?php if ($delivery['purchase_order_id']): ?>
        <a href="index.php?url=purchaseorder/show/<?php echo $delivery['purchase_order_id']; ?>" class="button">View Linked PO</a>
    <?php endif; ?>
     <!-- Delete button - use with extreme caution or specific roles -->
    <form action="index.php?url=delivery/destroy/<?php echo $delivery['id']; ?>" method="POST" style="display:inline; margin-left: 10px;" onsubmit="return confirm('DANGER! Deleting this delivery will attempt to REVERT stock quantities and may affect Purchase Order status. This action is generally not recommended. Are you absolutely sure?');">
        <button type="submit" class="button-danger">Delete Delivery (Revert Stock)</button>
    </form>
</div>

<h3>Delivery Details</h3>
<table class="table" style="width:50%; margin-bottom:20px;">
    <tr><th>Delivery Date:</th><td><?php echo htmlspecialchars($delivery['delivery_date']); ?></td></tr>
    <tr><th>Linked Purchase Order:</th><td><?php echo htmlspecialchars($delivery['purchase_order_number']); ?></td></tr>
    <tr><th>Supplier:</th><td><?php echo htmlspecialchars($delivery['supplier_name']); ?></td></tr>
    <tr><th>Type:</th><td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $delivery['type']))); ?></td></tr>
    <tr><th>Is Partial:</th><td><?php echo $delivery['is_partial'] ? 'Yes' : 'No'; ?></td></tr>
    <tr><th>Notes:</th><td><?php echo nl2br(htmlspecialchars($delivery['notes'] ?? 'N/A')); ?></td></tr>
    <tr><th>Recorded At:</th><td><?php echo htmlspecialchars($delivery['created_at']); ?></td></tr>
    <tr><th>Last Updated:</th><td><?php echo htmlspecialchars($delivery['updated_at']); ?></td></tr>
</table>

<h3>Received Items</h3>
<?php if (empty($delivery['items'])): ?>
    <p>No items found for this delivery.</p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>Product ID</th>
                <th>Product Name</th>
                <th>Quantity Received</th>
                <th>Unit of Measure</th>
                <th>From PO Item ID</th>
                <th>Originally Ordered (on PO)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($delivery['items'] as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['product_id']); ?></td>
                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                <td style="text-align: right;"><?php echo htmlspecialchars($item['quantity_received']); ?></td>
                <td><?php echo htmlspecialchars($item['unit_of_measure']); ?></td>
                <td><?php echo htmlspecialchars($item['purchase_order_item_id'] ?? 'N/A (Direct)'); ?></td>
                <td style="text-align: right;"><?php echo htmlspecialchars($item['original_quantity_ordered'] ?? 'N/A'); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
