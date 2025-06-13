<?php $title = isset($supplier['name']) ? 'Supplier Details: ' . htmlspecialchars($supplier['name']) : 'Supplier Details'; ?>

<?php if ($supplier): ?>
    <h2><?php echo htmlspecialchars($supplier['name']); ?></h2>
    <p><strong>ID:</strong> <?php echo htmlspecialchars($supplier['id']); ?></p>
    <p><strong>Contact Person:</strong> <?php echo htmlspecialchars($supplier['contact_person'] ?? 'N/A'); ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($supplier['email']); ?></p>
    <p><strong>Phone:</strong> <?php echo htmlspecialchars($supplier['phone'] ?? 'N/A'); ?></p>
    <p><strong>Address:</strong> <?php echo nl2br(htmlspecialchars($supplier['address'] ?? 'N/A')); ?></p>
    <p><strong>Registered On:</strong> <?php echo htmlspecialchars($supplier['created_at']); ?></p>
    <p><strong>Last Updated:</strong> <?php echo htmlspecialchars($supplier['updated_at']); ?></p>

    <p style="margin-top: 20px;">
        <a href="index.php?url=suppliers/edit/<?php echo $supplier['id']; ?>" class="button">Edit Supplier</a>
        <a href="index.php?url=suppliers" class="button-info">Back to List</a>
    </p>
<?php else: ?>
    <p>Supplier not found.</p>
    <a href="index.php?url=suppliers" class="button-info">Back to List</a>
<?php endif; ?>
