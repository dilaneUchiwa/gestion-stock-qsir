<?php $title = isset($supplier['name']) ? 'Edit Supplier: ' . htmlspecialchars($supplier['name']) : 'Edit Supplier'; ?>

<h2>Edit Supplier: <?php echo htmlspecialchars($supplier['name'] ?? 'N/A'); ?></h2>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <p><strong>Please correct the following errors:</strong></p>
        <ul>
            <?php foreach ($errors as $field => $error): ?>
                <li><?php echo htmlspecialchars($field); ?>: <?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (empty($supplier)): ?>
    <p>Supplier data not found for editing.</p>
    <a href="index.php?url=suppliers" class="button-info">Back to List</a>
<?php else: ?>
<form action="index.php?url=suppliers/update/<?php echo htmlspecialchars($supplier['id']); ?>" method="POST">
    <div class="form-group">
        <label for="name">Supplier Name *</label>
        <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($supplier['name'] ?? ''); ?>" required>
    </div>
    <div class="form-group">
        <label for="contact_person">Contact Person</label>
        <input type="text" name="contact_person" id="contact_person" value="<?php echo htmlspecialchars($supplier['contact_person'] ?? ''); ?>">
    </div>
    <div class="form-group">
        <label for="email">Email Address *</label>
        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($supplier['email'] ?? ''); ?>" required>
    </div>
    <div class="form-group">
        <label for="phone">Phone Number</label>
        <input type="tel" name="phone" id="phone" value="<?php echo htmlspecialchars($supplier['phone'] ?? ''); ?>">
    </div>
    <div class="form-group">
        <label for="address">Address</label>
        <textarea name="address" id="address" rows="4"><?php echo htmlspecialchars($supplier['address'] ?? ''); ?></textarea>
    </div>
    <div class="form-group">
        <button type="submit" class="button">Update Supplier</button>
        <a href="index.php?url=suppliers/show/<?php echo htmlspecialchars($supplier['id']); ?>" class="button-info">Cancel</a>
    </div>
</form>
<?php endif; ?>
