<?php $title = isset($product['name']) ? 'Product Details: ' . htmlspecialchars($product['name']) : 'Product Details'; ?>

<?php if ($product): ?>
    <h2><?php echo htmlspecialchars($product['name']); ?></h2>
    <p><strong>ID:</strong> <?php echo htmlspecialchars($product['id']); ?></p>
    <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($product['description'] ?? 'N/A')); ?></p>
    <p><strong>Unit of Measure:</strong> <?php echo htmlspecialchars($product['unit_of_measure'] ?? 'N/A'); ?></p>
    <p><strong>Quantity in Stock:</strong> <?php echo htmlspecialchars($product['quantity_in_stock'] ?? '0'); ?></p>
    <p><strong>Purchase Price:</strong> <?php echo htmlspecialchars($product['purchase_price'] ?? '0.00'); ?></p>
    <p><strong>Selling Price:</strong> <?php echo htmlspecialchars($product['selling_price'] ?? '0.00'); ?></p>
    <p><strong>Created At:</strong> <?php echo htmlspecialchars($product['created_at']); ?></p>
    <p><strong>Updated At:</strong> <?php echo htmlspecialchars($product['updated_at']); ?></p>

    <?php if ($parent): ?>
        <h3>Parent Product</h3>
        <p><a href="index.php?url=products/show/<?php echo $parent['id']; ?>"><?php echo htmlspecialchars($parent['name']); ?></a></p>
    <?php elseif ($product['parent_id']): ?>
        <p><strong>Parent Product ID:</strong> <?php echo htmlspecialchars($product['parent_id']); ?> (Parent details not found or parent is hidden)</p>
    <?php else: ?>
        <p>This product has no parent.</p>
    <?php endif; ?>

    <?php if (!empty($children)): ?>
        <h3>zChild Products/Components</h3>
        <ul>
            <?php foreach ($children as $child): ?>
                <li><a href="index.php?url=products/show/<?php echo $child['id']; ?>"><?php echo htmlspecialchars($child['name']); ?></a> (Qty: <?php echo htmlspecialchars($child['quantity_in_stock']); ?>)</li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>This product has no child components listed.</p>
    <?php endif; ?>

    <p style="margin-top: 20px;">
        <a href="index.php?url=products/edit/<?php echo $product['id']; ?>" class="button">Edit Product</a>
        <a href="index.php?url=products" class="button-info">Back to List</a>
    </p>
<?php else: ?>
    <p>Product not found.</p>
    <a href="index.php?url=products" class="button-info">Back to List</a>
<?php endif; ?>
