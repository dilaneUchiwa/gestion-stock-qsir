<?php $title = 'Client List'; ?>

<h2>Client List</h2>

<?php
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'deleted_success') {
        echo '<div class="alert alert-success">Client successfully deleted.</div>';
    } elseif ($_GET['status'] == 'deleted_error') {
        echo '<div class="alert alert-danger">Error deleting client. It might be associated with other records (e.g., sales).</div>';
    } elseif ($_GET['status'] == 'created_success') {
         echo '<div class="alert alert-success">Client successfully created.</div>';
    } elseif (isset($_GET['updated_success'])) { // From update redirect
         echo '<div class="alert alert-success">Client successfully updated.</div>';
    }
}
?>

<p><a href="index.php?url=clients/create" class="button">Add New Client</a></p>

<?php if (empty($clients)): ?>
    <p>No clients found.</p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Type</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($clients as $client): ?>
            <tr>
                <td><?php echo htmlspecialchars($client['id']); ?></td>
                <td><?php echo htmlspecialchars($client['name']); ?></td>
                <td><?php echo htmlspecialchars(ucfirst($client['client_type'])); ?></td>
                <td><?php echo htmlspecialchars($client['email'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($client['phone'] ?? 'N/A'); ?></td>
                <td>
                    <a href="index.php?url=clients/show/<?php echo $client['id']; ?>" class="button-info">View</a>
                    <a href="index.php?url=clients/edit/<?php echo $client['id']; ?>" class="button">Edit</a>
                    <form action="index.php?url=clients/destroy/<?php echo $client['id']; ?>" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this client? This might fail if the client is linked to other records.');">
                        <button type="submit" class="button-danger">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
