<?php
// $title set by controller
// $title = 'Sale Details';
?>

<?php if (empty($sale)): ?>
    <p>Sale not found.</p>
    <a href="index.php?url=sale/index" class="button-info">Back to List</a>
    <?php return; ?>
<?php endif; ?>

<?php
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    if ($status == 'created_success') echo '<div class="alert alert-success">Sale successfully recorded. Stock updated.</div>';
    if ($status == 'payment_updated') echo '<div class="alert alert-success">Payment status successfully updated.</div>';
}
?>

<h2>Sale #SA-<?php echo htmlspecialchars($sale['id']); ?></h2>
<div style="margin-bottom: 20px;">
    <a href="index.php?url=sale/index" class="button-info">Back to List</a>
    <?php if ($sale['payment_type'] === 'deferred' && $sale['payment_status'] !== 'paid' && $sale['payment_status'] !== 'cancelled'): ?>
        <a href="index.php?url=sale/record_payment/<?php echo $sale['id']; ?>" class="button" style="background-color: #ffc107; color: black;">Record Payment</a>
    <?php endif; ?>
    <?php // Edit/Delete: Add conditions for when these actions are allowed (e.g., not if paid)
        // <a href="index.php?url=sale/edit/<?php echo $sale['id']; ?>" class="button">Edit Sale</a>
        if (!in_array($sale['payment_status'], ['paid', 'partially_paid']) || in_array($sale['payment_status'], ['cancelled', 'pending'])):
    ?>
    <form action="index.php?url=sale/destroy/<?php echo $sale['id']; ?>" method="POST" style="display:inline; margin-left:10px;" onsubmit="return confirm('Are you sure you want to delete this sale? This will revert stock quantities.');">
        <button type="submit" class="button-danger">Delete Sale</button>
    </form>
    <?php endif; ?>
</div>

<h3>Sale Details</h3>
<table class="table" style="width:60%; margin-bottom:20px;">
    <tr><th>Sale Date:</th><td><?php echo htmlspecialchars($sale['sale_date']); ?></td></tr>
    <tr><th>Client:</th>
        <td>
            <?php if ($sale['client_id']): ?>
                <a href="index.php?url=clients/show/<?php echo $sale['client_id']; ?>"><?php echo htmlspecialchars($sale['client_display_name']); ?></a>
            <?php else: ?>
                <?php echo htmlspecialchars($sale['client_display_name']); ?>
            <?php endif; ?>
        </td>
    </tr>
    <tr><th>Total Amount:</th><td style="font-weight:bold;"><?php echo htmlspecialchars(number_format($sale['total_amount'], 2)); ?></td></tr>
    <tr><th>Payment Type:</th><td><?php echo htmlspecialchars(ucfirst($sale['payment_type'])); ?></td></tr>
    <tr><th>Payment Status:</th><td><span class="status-<?php echo htmlspecialchars(strtolower(str_replace('_', '-', $sale['payment_status']))); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $sale['payment_status']))); ?></span></td></tr>
    <?php if ($sale['payment_type'] === 'deferred'): ?>
        <tr><th>Due Date:</th><td><?php echo htmlspecialchars($sale['due_date'] ?? 'N/A'); ?></td></tr>
    <?php endif; ?>
     <tr><th>Payment Date:</th><td><?php echo htmlspecialchars($sale['payment_date'] ?? 'N/A'); ?></td></tr>
    <tr><th>Notes:</th><td><?php echo nl2br(htmlspecialchars($sale['notes'] ?? 'N/A')); ?></td></tr>
    <tr><th>Recorded At:</th><td><?php echo htmlspecialchars($sale['created_at']); ?></td></tr>
    <tr><th>Last Updated:</th><td><?php echo htmlspecialchars($sale['updated_at']); ?></td></tr>
</table>

<h3>Sold Items</h3>
<?php if (empty($sale['items'])): ?>
    <p>No items found for this sale.</p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>Product ID</th>
                <th>Product Name</th>
                <th>Quantity Sold</th>
                <th>Unit Price</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sale['items'] as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['product_id']); ?></td>
                <td><?php echo htmlspecialchars($item['product_name']); ?> (<?php echo htmlspecialchars($item['unit_of_measure']); ?>)</td>
                <td style="text-align: right;"><?php echo htmlspecialchars($item['quantity_sold']); ?></td>
                <td style="text-align: right;"><?php echo htmlspecialchars(number_format($item['unit_price'], 2)); ?></td>
                <td style="text-align: right;"><?php echo htmlspecialchars(number_format($item['sub_total'], 2)); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" style="text-align:right;">Grand Total:</th>
                <td style="text-align: right;"><strong><?php echo htmlspecialchars(number_format($sale['total_amount'], 2)); ?></strong></td>
            </tr>
        </tfoot>
    </table>
<?php endif; ?>

<style>
    .status-pending { color: orange; font-weight: bold; }
    .status-paid { color: green; font-weight: bold; }
    .status-partially-paid { color: darkgoldenrod; font-weight: bold; }
    .status-refunded { color: purple; font-weight: bold; }
    .status-cancelled { color: red; font-weight: bold; }
</style>
