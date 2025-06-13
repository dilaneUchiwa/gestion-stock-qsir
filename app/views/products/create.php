<?php $title = 'Add New Product'; ?>

<h2>Add New Product</h2>

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

<form action="index.php?url=products/store" method="POST">
    <fieldset>
        <legend>Product Information</legend>
        <div class="form-group">
            <label for="name">Product Name *</label>
            <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($data['name'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" id="description" rows="4"><?php echo htmlspecialchars($data['description'] ?? ''); ?></textarea>
        </div>
        <div class="form-group">
            <label for="parent_id">Parent Product (if applicable)</label>
            <select name="parent_id" id="parent_id">
                <option value="">None</option>
                <?php if (!empty($products)): // This $products variable is for parent selection, passed by ProductsController@create ?>
                    <?php foreach ($products as $p): ?>
                        <option value="<?php echo htmlspecialchars($p['id']); ?>" <?php echo (isset($data['parent_id']) && $data['parent_id'] == $p['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($p['name']); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="unit_of_measure">Unit of Measure</label>
            <input type="text" name="unit_of_measure" id="unit_of_measure" value="<?php echo htmlspecialchars($data['unit_of_measure'] ?? ''); ?>" placeholder="e.g., piece, kg, liter">
        </div>
    </fieldset>

    <fieldset>
        <legend>Pricing & Stock</legend>
        <div class="form-group">
            <label for="quantity_in_stock">Initial Quantity in Stock</label>
            <input type="number" name="quantity_in_stock" id="quantity_in_stock" value="<?php echo htmlspecialchars($data['quantity_in_stock'] ?? '0'); ?>" min="0">
            <small>This will create an 'initial_stock' movement. Further stock changes should be done via Deliveries, Sales, or Adjustments.</small>
        </div>
        <div class="form-group">
            <label for="purchase_price">Purchase Price</label>
            <input type="number" name="purchase_price" id="purchase_price" value="<?php echo htmlspecialchars($data['purchase_price'] ?? '0.00'); ?>" step="0.01" min="0">
        </div>
        <div class="form-group">
            <label for="selling_price">Selling Price</label>
            <input type="number" name="selling_price" id="selling_price" value="<?php echo htmlspecialchars($data['selling_price'] ?? '0.00'); ?>" step="0.01" min="0">
        </div>
    </fieldset>

    <div class="form-group mt-3">
        <button type="submit" class="button button-success">Add Product</button>
        <a href="index.php?url=products" class="button button-info">Cancel</a>
    </div>
</form>
