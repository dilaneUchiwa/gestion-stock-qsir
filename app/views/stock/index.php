<?php
// $title is set by controller
// $title = 'Stock Overview';
?>

<h2>Stock Overview</h2>

<p>
    <a href="index.php?url=stock/create_adjustment" class="button">Create Manual Adjustment</a>
</p>

<?php if (empty($products)): ?>
    <p>No products found to display stock.</p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Product Name</th>
                <th>Unit</th>
                <th>Current Stock (Cached)</th>
                <!-- <th>Calculated Stock (Live)</th> -->
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
            <tr>
                <td><?php echo htmlspecialchars($product['id']); ?></td>
                <td><?php echo htmlspecialchars($product['name']); ?></td>
                <td><?php echo htmlspecialchars($product['unit_of_measure']); ?></td>
                <td style="text-align: right;"><?php echo htmlspecialchars($product['quantity_in_stock']); ?></td>
                <!-- <td style="text-align: right;"><?php // echo htmlspecialchars($product['calculated_stock'] ?? 'N/A'); ?></td> -->
                <td>
                    <a href="index.php?url=stock/history/<?php echo $product['id']; ?>" class="button-info">View History</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<p><small>Note: "Current Stock (Cached)" is the value stored directly with the product, updated by transactions. It should ideally match a live calculation from movements.</small></p>
