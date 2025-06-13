<?php
// $title is set by controller
// $title = 'Deliveries / Receptions';
?>

<h2>Deliveries / Receptions</h2>

<?php
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    if ($status == 'created_success') echo '<div class="alert alert-success">Delivery successfully recorded.</div>';
    if ($status == 'deleted_success') echo '<div class="alert alert-success">Delivery successfully deleted and stock reverted.</div>';
    if ($status == 'delete_error') echo '<div class="alert alert-danger">Error deleting delivery. Stock or PO status update might have failed.</div>';
    if ($status == 'delete_not_found') echo '<div class="alert alert-danger">Error: Delivery to delete not found.</div>';
}
?>

<p>
    <a href="index.php?url=delivery/create" class="button">Record Direct Delivery</a>
    <span style="margin: 0 10px;">or select a Purchase Order to receive items:</span>
    <a href="index.php?url=purchaseorder/index" class="button-info">View Purchase Orders</a>
</p>


<?php if (empty($deliveries)): ?>
    <p>No deliveries found.</p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Date</th>
                <th>PO Ref</th>
                <th>Supplier</th>
                <th>Type</th>
                <th>Partial</th>
                <th>Status (PO)</th> <!-- This might be delivery status if deliveries have their own workflow -->
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($deliveries as $delivery): ?>
            <tr>
                <td>DEL-<?php echo htmlspecialchars($delivery['id']); ?></td>
                <td><?php echo htmlspecialchars($delivery['delivery_date']); ?></td>
                <td><?php echo htmlspecialchars($delivery['purchase_order_display']); ?></td>
                <td><?php echo htmlspecialchars($delivery['supplier_name']); ?></td>
                <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $delivery['type']))); ?></td>
                <td><?php echo $delivery['is_partial'] ? 'Yes' : 'No'; ?></td>
                <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $delivery['status'] ?? 'N/A' ))); ?></td> <!-- Assuming 'status' comes from PO if joined -->
                <td>
                    <a href="index.php?url=delivery/show/<?php echo $delivery['id']; ?>" class="button-info">View</a>
                    <?php // Edit/Delete for deliveries are complex due to stock; often handled by reverse transactions. Add with caution. ?>
                    <!--
                    <form action="index.php?url=delivery/destroy/<?php echo $delivery['id']; ?>" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this delivery? This will attempt to revert stock changes.');">
                        <button type="submit" class="button-danger">Delete</button>
                    </form>
                    -->
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
