<?php
// $title set by controller
// $title = 'Edit Supplier Invoice';
?>

<h2>Edit Supplier Invoice #INV-<?php echo htmlspecialchars($invoice['id']); ?> (<?php echo htmlspecialchars($invoice['invoice_number']); ?>)</h2>

<?php if (empty($invoice)): ?>
    <p>Invoice data not found for editing.</p>
    <a href="index.php?url=supplierinvoice/index" class="button-info">Back to List</a>
    <?php return; ?>
<?php endif; ?>

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

<form action="index.php?url=supplierinvoice/update/<?php echo $invoice['id']; ?>" method="POST">
    <fieldset>
        <legend>Invoice Details</legend>

        <div class="form-group">
            <label for="supplier_id">Supplier *</label>
            <select name="supplier_id" id="supplier_id" required>
                <option value="">Select Supplier</option>
                <?php foreach ($suppliers as $supplier): ?>
                    <option value="<?php echo htmlspecialchars($supplier['id']); ?>" <?php echo ($invoice['supplier_id'] == $supplier['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($supplier['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="invoice_number">Invoice Number *</label>
            <input type="text" name="invoice_number" id="invoice_number" value="<?php echo htmlspecialchars($invoice['invoice_number']); ?>" required>
        </div>

        <div class="form-group">
            <label for="invoice_date">Invoice Date *</label>
            <input type="date" name="invoice_date" id="invoice_date" value="<?php echo htmlspecialchars($invoice['invoice_date']); ?>" required>
        </div>

        <div class="form-group">
            <label for="due_date">Due Date</label>
            <input type="date" name="due_date" id="due_date" value="<?php echo htmlspecialchars($invoice['due_date'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="total_amount">Total Amount *</label>
            <input type="number" name="total_amount" id="total_amount" value="<?php echo htmlspecialchars(number_format($invoice['total_amount'], 2, '.', '')); ?>" min="0" step="0.01" required>
        </div>

        <div class="form-group">
            <label for="status">Status</label>
            <select name="status" id="status">
                <?php foreach ($allowedStatuses as $statusVal): ?>
                    <option value="<?php echo htmlspecialchars($statusVal); ?>" <?php echo ($invoice['status'] == $statusVal) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $statusVal))); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="payment_date">Payment Date</label>
            <input type="date" name="payment_date" id="payment_date" value="<?php echo htmlspecialchars($invoice['payment_date'] ?? ''); ?>">
            <small>If status is 'Paid' or 'Partially Paid'. Will be auto-set to today if marking as 'Paid' and left empty.</small>
        </div>

        <div class="form-group">
            <label for="delivery_id">Link to Delivery (Optional)</label>
            <input type="number" name="delivery_id" id="delivery_id" value="<?php echo htmlspecialchars($invoice['delivery_id'] ?? ''); ?>" placeholder="Enter Delivery ID (DEL-...)">
        </div>

        <div class="form-group">
            <label for="purchase_order_id">Link to Purchase Order (Optional)</label>
            <input type="number" name="purchase_order_id" id="purchase_order_id" value="<?php echo htmlspecialchars($invoice['purchase_order_id'] ?? ''); ?>" placeholder="Enter Purchase Order ID (PO-...)">
        </div>

        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea name="notes" id="notes" rows="3"><?php echo htmlspecialchars($invoice['notes'] ?? ''); ?></textarea>
        </div>
    </fieldset>

    <div class="form-group" style="margin-top: 20px;">
        <button type="submit" class="button">Update Invoice</button>
        <a href="index.php?url=supplierinvoice/show/<?php echo $invoice['id']; ?>" class="button-info">Cancel</a>
    </div>
</form>
