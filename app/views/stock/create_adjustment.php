<?php
// $title is set by controller
// $title = 'Create Stock Adjustment';
?>

<h2>Create Manual Stock Adjustment</h2>

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

<form action="index.php?url=stock/store_adjustment" method="POST">
    <fieldset>
        <legend>Adjustment Details</legend>
        <div class="form-group">
            <label for="product_id">Product *</label>
            <select name="product_id" id="product_id" required>
                <option value="">Select Product</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?php echo htmlspecialchars($product['id']); ?>" <?php echo (isset($data['product_id']) && $data['product_id'] == $product['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($product['name']); ?> (Current Stock: <?php echo htmlspecialchars($product['quantity_in_stock']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="adjustment_type">Adjustment Type *</label>
            <select name="adjustment_type" id="adjustment_type" required>
                <option value="">Select Type</option>
                <?php foreach ($adjustmentTypes as $type): ?>
                     <option value="<?php echo htmlspecialchars($type); ?>" <?php echo (isset($data['adjustment_type']) && $data['adjustment_type'] == $type) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $type))); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="quantity">Quantity *</label>
            <input type="number" name="quantity" id="quantity" value="<?php echo htmlspecialchars($data['quantity'] ?? '1'); ?>" min="1" required>
            <small>Enter a positive quantity. The type selected above determines if it's an increase or decrease.</small>
        </div>

        <div class="form-group">
            <label for="notes">Reason / Notes</label>
            <textarea name="notes" id="notes" rows="3"><?php echo htmlspecialchars($data['notes'] ?? ''); ?></textarea>
        </div>
    </fieldset>

    <div class="form-group" style="margin-top: 20px;">
        <button type="submit" class="button">Submit Adjustment</button>
        <a href="index.php?url=stock/index" class="button-info">Cancel</a>
    </div>
</form>
