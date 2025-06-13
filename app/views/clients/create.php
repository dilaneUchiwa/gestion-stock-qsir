<?php $title = 'Add New Client'; ?>

<h2>Add New Client</h2>

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

<form action="index.php?url=clients/store" method="POST">
    <div class="form-group">
        <label for="name">Client Name *</label>
        <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($data['name'] ?? ''); ?>" required>
    </div>
    <div class="form-group">
        <label for="client_type">Client Type *</label>
        <select name="client_type" id="client_type" required>
            <?php
            $currentType = $data['client_type'] ?? 'connu'; // Default to 'connu'
            foreach ($allowedClientTypes as $type): ?>
                <option value="<?php echo htmlspecialchars($type); ?>" <?php echo ($currentType == $type) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars(ucfirst($type)); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($data['email'] ?? ''); ?>">
        <small>Required if client type is 'connu', unique if provided.</small>
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
        <button type="submit" class="button">Add Client</button>
        <a href="index.php?url=clients" class="button-info">Cancel</a>
    </div>
</form>
