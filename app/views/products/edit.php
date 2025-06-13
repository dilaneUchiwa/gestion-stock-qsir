<?php $title = isset($product['name']) ? 'Edit Product: ' . htmlspecialchars($product['name']) : 'Edit Product'; ?>

<h2>Edit Product: <?php echo htmlspecialchars($product['name'] ?? 'N/A'); ?></h2>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <p><strong>Please correct the following errors:</strong></p>
        <ul>
            <?php foreach ($errors as $field => $error): ?>
                <li><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $field))); ?>: <?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (empty($product)): ?>
    <p>Product not found.</p>
    <a href="index.php?url=products" class="button button-info">Back to List</a>
<?php else: ?>
<form action="index.php?url=products/update/<?php echo htmlspecialchars($product['id']); ?>" method="POST">
    <fieldset>
        <legend>Product Information</legend>
        <div class="form-group">
            <label for="name">Product Name *</label>
            <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" id="description" rows="4"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
        </div>
        <div class="form-group">
            <label for="parent_id">Parent Product (if applicable)</label>
            <select name="parent_id" id="parent_id">
                <option value="">None</option>
                <?php if (!empty($products)): // This $products variable is for parent selection, passed by ProductsController@edit ?>
                    <?php foreach ($products as $p): ?>
                        <?php if ($p['id'] == $product['id']) continue; // Prevent self-assignment ?>
                        <option value="<?php echo htmlspecialchars($p['id']); ?>" <?php echo (isset($product['parent_id']) && $product['parent_id'] == $p['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($p['name']); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="unit_of_measure">Unit of Measure</label>
            <input type="text" name="unit_of_measure" id="unit_of_measure" value="<?php echo htmlspecialchars($product['unit_of_measure'] ?? ''); ?>" placeholder="e.g., piece, kg, liter">
        </div>
    </fieldset>

    <fieldset>
        <legend>Pricing & Stock</legend>
        <div class="form-group">
            <label for="quantity_in_stock">Quantity in Stock (Cached)</label>
            <input type="number" name="quantity_in_stock" id="quantity_in_stock" value="<?php echo htmlspecialchars($product['quantity_in_stock'] ?? '0'); ?>" readonly>
            <small>Stock is managed via Deliveries, Sales, or Stock Adjustments. <a href="index.php?url=stock/history/<?php echo $product['id']; ?>">View history</a> or <a href="index.php?url=stock/create_adjustment&product_id=<?php echo $product['id']; ?>">create adjustment</a>.</small>
        </div>
        <div class="form-group">
            <label for="purchase_price">Purchase Price</label>
            <input type="number" name="purchase_price" id="purchase_price" value="<?php echo htmlspecialchars($product['purchase_price'] ?? '0.00'); ?>" step="0.01" min="0">
        </div>
        <div class="form-group">
            <label for="selling_price">Selling Price</label>
            <input type="number" name="selling_price" id="selling_price" value="<?php echo htmlspecialchars($product['selling_price'] ?? '0.00'); ?>" step="0.01" min="0">
        </div>
    </fieldset>

    <div class="form-group mt-3">
        <button type="submit" class="button button-success">Update Product</button>
        <a href="index.php?url=products/show/<?php echo htmlspecialchars($product['id']); ?>" class="button button-info">Cancel</a>
    </div>
</form>
<?php endif; ?>
