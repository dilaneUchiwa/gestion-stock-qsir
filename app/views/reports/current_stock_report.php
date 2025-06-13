<?php
// $title set by controller
?>

<h2><?php echo htmlspecialchars($title); ?></h2>

<div style="margin-bottom: 20px;">
    <a href="index.php?url=report/index" class="button-info">Back to Reports Index</a>
</div>

<form method="GET" action="index.php">
    <input type="hidden" name="url" value="report/current_stock">
    <fieldset style="margin-bottom: 20px;">
        <legend>Filters</legend>
        <div class="form-group" style="display:inline-block; margin-right:10px;">
            <label for="low_stock_threshold">Show products with stock at or below:</label>
            <input type="number" name="low_stock_threshold" id="low_stock_threshold" value="<?php echo htmlspecialchars($low_stock_threshold ?? ''); ?>" min="0" placeholder="e.g., 10">
        </div>
        <button type="submit" class="button">Apply Filter</button>
        <a href="index.php?url=report/current_stock" class="button-info">Clear Filter</a>
    </fieldset>
</form>

<?php if (empty($products)): ?>
    <p>No products found<?php echo $low_stock_threshold !== null ? ' matching the filter criteria' : ''; ?>.</p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Product Name</th>
                <th>Unit of Measure</th>
                <th style="text-align: right;">Current Stock</th>
                <th>Purchase Price</th>
                <th>Selling Price</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
            <tr class="<?php echo ($low_stock_threshold !== null && $product['quantity_in_stock'] <= $low_stock_threshold) ? 'low-stock-alert' : '';
                         echo ($product['quantity_in_stock'] < 0) ? 'negative-stock-alert' : ''; ?>">
                <td><?php echo htmlspecialchars($product['id']); ?></td>
                <td><a href="index.php?url=stock/history/<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['name']); ?></a></td>
                <td><?php echo htmlspecialchars($product['unit_of_measure']); ?></td>
                <td style="text-align: right; font-weight: bold;"><?php echo htmlspecialchars($product['quantity_in_stock']); ?></td>
                <td style="text-align: right;"><?php echo htmlspecialchars(number_format($product['purchase_price'] ?? 0, 2)); ?></td>
                <td style="text-align: right;"><?php echo htmlspecialchars(number_format($product['selling_price'] ?? 0, 2)); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <style>
        .low-stock-alert { background-color: #fff3cd; /* Light yellow */ }
        .negative-stock-alert { background-color: #f8d7da; color: #721c24; /* Light red with dark text */ }
        .negative-stock-alert a { color: #721c24; }
    </style>
    <p><small>Click on product name to view its stock movement history.</small></p>
<?php endif; ?>

<div style="margin-top: 20px;">
    <button onclick="window.print();" class="button">Print Report</button>
</div>
