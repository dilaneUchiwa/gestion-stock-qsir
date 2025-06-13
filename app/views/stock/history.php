<?php
// $title is set by controller, e.g. 'Stock Movement History for [Product Name]'
?>

<h2><?php echo $title; ?></h2>

<?php if (empty($product)): ?>
    <p>Product not found.</p>
    <a href="index.php?url=stock/index" class="button-info">Back to Stock Overview</a>
    <?php return; ?>
<?php endif; ?>

<div style="margin-bottom: 20px;">
    <a href="index.php?url=stock/index" class="button-info">Back to Stock Overview</a>
    <a href="index.php?url=products/show/<?php echo $product['id']; ?>" class="button">View Product Details</a>
</div>

<p><strong>Product:</strong> <?php echo htmlspecialchars($product['name']); ?> (ID: <?php echo $product['id']; ?>)</p>
<p><strong>Current Cached Stock:</strong> <?php echo htmlspecialchars($product['quantity_in_stock']); ?></p>
<p><strong>Current Calculated Stock (from movements):</strong> <?php echo htmlspecialchars($calculatedStock); ?></p>
<?php if ($product['quantity_in_stock'] != $calculatedStock): ?>
    <p class="alert alert-danger"><strong>Warning:</strong> Cached stock (<?php echo $product['quantity_in_stock']; ?>) does not match calculated stock (<?php echo $calculatedStock; ?>)! This indicates a potential data inconsistency.</p>
<?php endif; ?>


<form method="GET" action="index.php">
    <input type="hidden" name="url" value="stock/history/<?php echo $product['id']; ?>">
    <fieldset style="margin-bottom: 20px;">
        <legend>Filter History by Date</legend>
        <div class="form-group" style="display:inline-block; margin-right:10px;">
            <label for="start_date">From:</label>
            <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($dateRange['start_date'] ?? ''); ?>">
        </div>
        <div class="form-group" style="display:inline-block; margin-right:10px;">
            <label for="end_date">To:</label>
            <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($dateRange['end_date'] ?? ''); ?>">
        </div>
        <button type="submit" class="button">Filter</button>
        <a href="index.php?url=stock/history/<?php echo $product['id']; ?>" class="button-info">Clear Filter</a>
    </fieldset>
</form>

<?php
if (isset($_GET['status']) && $_GET['status'] == 'adjustment_success') {
    echo '<div class="alert alert-success">Stock adjustment successfully recorded.</div>';
}
?>

<h3>Stock Movements</h3>
<?php if (empty($movements)): ?>
    <p>No stock movements found for this product<?php echo $dateRange ? ' in the selected date range' : ''; ?>.</p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Date & Time</th>
                <th>Type</th>
                <th>Quantity</th>
                <th>Related Document</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($movements as $movement): ?>
            <tr class="<?php echo strpos($movement['type'], '_in') !== false || $movement['type'] === 'sale_reversal' || $movement['type'] === 'delivery_reversal' && strpos($movement['type'], 'out') === false ? 'stock-in' : 'stock-out'; ?>">
                <td><?php echo htmlspecialchars($movement['id']); ?></td>
                <td><?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($movement['movement_date']))); ?></td>
                <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $movement['type']))); ?></td>
                <td style="text-align: right;">
                    <?php
                        $quantityPrefix = '';
                        if (in_array($movement['type'], ['in_delivery', 'adjustment_in', 'split_in', 'initial_stock', 'sale_reversal'])) {
                            $quantityPrefix = '+';
                        } elseif (in_array($movement['type'], ['out_sale', 'adjustment_out', 'split_out', 'delivery_reversal'])) {
                             $quantityPrefix = '-';
                        }
                        echo $quantityPrefix . htmlspecialchars($movement['quantity']);
                    ?>
                </td>
                <td>
                    <?php
                    if ($movement['related_document_id'] && $movement['related_document_type']) {
                        $docTypeDisplay = ucfirst(str_replace('_', ' ', str_replace('_items', '', $movement['related_document_type'])));
                        $docId = htmlspecialchars($movement['related_document_id']);
                        // Basic linking (could be more specific if controllers existed for all doc types)
                        $url = '#';
                        if ($movement['related_document_type'] === 'delivery_items') {
                            // Need delivery_id from delivery_items_id. This is not directly available.
                            // For simplicity, just display info. A better way is to store delivery_id in movement.
                             echo "{$docTypeDisplay} Item ID: {$docId}";
                        } elseif ($movement['related_document_type'] === 'sale_items') {
                             echo "{$docTypeDisplay} Item ID: {$docId}";
                        } else {
                             echo "{$docTypeDisplay} ID: {$docId}";
                        }
                    } else {
                        echo 'N/A';
                    }
                    ?>
                </td>
                <td><?php echo nl2br(htmlspecialchars($movement['notes'] ?? '')); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <style>
        .stock-in { color: green; }
        .stock-out { color: red; }
    </style>
<?php endif; ?>
