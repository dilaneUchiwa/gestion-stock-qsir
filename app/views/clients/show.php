<?php $title = isset($client['name']) ? 'Client Details: ' . htmlspecialchars($client['name']) : 'Client Details'; ?>

<?php if (isset($_GET['status']) && $_GET['status'] == 'updated_success'): ?>
    <div class="alert alert-success">Client successfully updated.</div>
<?php endif; ?>

<?php if ($client): ?>
    <h2><?php echo htmlspecialchars($client['name']); ?></h2>
    <p><strong>ID:</strong> <?php echo htmlspecialchars($client['id']); ?></p>
    <p><strong>Client Type:</strong> <?php echo htmlspecialchars(ucfirst($client['client_type'])); ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($client['email'] ?? 'N/A'); ?></p>
    <p><strong>Phone:</strong> <?php echo htmlspecialchars($client['phone'] ?? 'N/A'); ?></p>
    <p><strong>Address:</strong> <?php echo nl2br(htmlspecialchars($client['address'] ?? 'N/A')); ?></p>
    <p><strong>Registered On:</strong> <?php echo htmlspecialchars($client['created_at']); ?></p>
    <p><strong>Last Updated:</strong> <?php echo htmlspecialchars($client['updated_at']); ?></p>

    <p style="margin-top: 20px;">
        <a href="index.php?url=clients/edit/<?php echo $client['id']; ?>" class="button">Edit Client</a>
        <a href="index.php?url=clients" class="button-info">Back to List</a>
    </p>
<?php else: ?>
    <p>Client not found.</p>
    <a href="index.php?url=clients" class="button-info">Back to List</a>
<?php endif; ?>
