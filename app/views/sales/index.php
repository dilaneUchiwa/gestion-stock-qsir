<?php
// $title set by controller
// $title = 'Sales History';
?>

<h2>Sales History</h2>

<?php
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    if ($status == 'created_success') echo '<div class="alert alert-success">Sale successfully recorded. Stock updated.</div>';
    if ($status == 'payment_updated') echo '<div class="alert alert-success">Payment status successfully updated.</div>';
    if ($status == 'deleted_success') echo '<div class="alert alert-success">Sale successfully deleted and stock reverted.</div>';
    if ($status == 'delete_error') echo '<div class="alert alert-danger">Error deleting sale.</div>';
    if ($status == 'delete_failed') echo '<div class="alert alert-warning">Could not delete sale: '.htmlspecialchars($_GET['reason'] ?? 'General error').'.</div>';
}
?>

<p>
    <a href="index.php?url=sale/create_immediate_payment" class="button">New Sale (Immediate Payment)</a>
    <a href="index.php?url=sale/create_deferred_payment" class="button" style="margin-left:10px;">New Sale (Deferred Payment)</a>
</p>

<?php if (empty($sales)): ?>
    <p>No sales found.</p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Client</th>
                <th>Total Amount</th>
                <th>Payment Type</th>
                <th>Payment Status</th>
                <th>Due Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sales as $sale): ?>
            <tr>
                <td>SA-<?php echo htmlspecialchars($sale['id']); ?></td>
                <td><?php echo htmlspecialchars($sale['sale_date']); ?></td>
                <td><?php echo htmlspecialchars($sale['client_display_name']); ?></td>
                <td style="text-align: right;"><?php echo htmlspecialchars(number_format($sale['total_amount'], 2)); ?></td>
                <td><?php echo htmlspecialchars(ucfirst($sale['payment_type'])); ?></td>
                <td><span class="status-<?php echo htmlspecialchars(strtolower(str_replace('_', '-', $sale['payment_status']))); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $sale['payment_status']))); ?></span></td>
                <td><?php echo htmlspecialchars($sale['due_date'] ?? 'N/A'); ?></td>
                <td>
                    <a href="index.php?url=sale/show/<?php echo $sale['id']; ?>" class="button-info">View</a>
                    <?php if ($sale['payment_type'] === 'deferred' && $sale['payment_status'] !== 'paid' && $sale['payment_status'] !== 'cancelled'): ?>
                        <a href="index.php?url=sale/record_payment/<?php echo $sale['id']; ?>" class="button" style="background-color: #ffc107; color: black;">Record Payment</a>
                    <?php endif; ?>
                     <?php // Delete only if not paid or if it's 'cancelled' or 'pending'
                        if (!in_array($sale['payment_status'], ['paid', 'partially_paid']) || in_array($sale['payment_status'], ['cancelled', 'pending'])): ?>
                        <form action="index.php?url=sale/destroy/<?php echo $sale['id']; ?>" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this sale? This will revert stock quantities.');">
                            <button type="submit" class="button-danger">Delete</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <style>
        .status-pending { color: orange; }
        .status-paid { color: green; }
        .status-partially-paid { color: darkgoldenrod; }
        .status-refunded { color: purple; }
        .status-cancelled { color: red; }
    </style>
<?php endif; ?>
