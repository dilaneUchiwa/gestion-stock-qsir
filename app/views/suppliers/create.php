<?php $title = 'Add New Supplier'; ?>

<h2>Add New Supplier</h2>

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

<form action="index.php?url=suppliers/store" method="POST">
    <div class="form-group">
        <label for="name">Supplier Name *</label>
        <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($data['name'] ?? ''); ?>" required>
    </div>
    <div class="form-group">
        <label for="contact_person">Contact Person</label>
        <input type="text" name="contact_person" id="contact_person" value="<?php echo htmlspecialchars($data['contact_person'] ?? ''); ?>">
    </div>
    <div class="form-group">
        <label for="email">Email Address *</label>
        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($data['email'] ?? ''); ?>" required>
    </div>
    <div class="form-group">
        <label for="phone">Phone Number</label>
        <input type="tel" name="phone" id="phone" value="<?php echo htmlspecialchars($data['phone'] ?? ''); ?>">
    </div>
    <div class="form-group">
        <label for="address">Address</label>
        <textarea name="address" id="address" rows="4"><?php echo htmlspecialchars($data['address'] ?? ''); ?></textarea>
    </div>
    <div class="form-group">
        <button type="submit" class="button">Add Supplier</button>
        <a href="index.php?url=suppliers" class="button-info">Cancel</a>
    </div>
</form>
