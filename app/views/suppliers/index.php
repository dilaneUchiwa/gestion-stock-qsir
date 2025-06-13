<?php $title = 'Supplier List'; ?>

<h2>Supplier List</h2>

<?php
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'deleted_success') {
        echo '<div class="alert alert-success">Supplier successfully deleted.</div>';
    } elseif ($_GET['status'] == 'deleted_error') {
        echo '<div class="alert alert-danger">Error deleting supplier. It might be associated with other records.</div>';
    } elseif ($_GET['status'] == 'created_success') { // Assuming we might add this from store redirect
         echo '<div class="alert alert-success">Supplier successfully created.</div>';
    }
}
?>

<p><a href="index.php?url=suppliers/create" class="button">Add New Supplier</a></p>

<?php if (empty($suppliers)): ?>
    <p>No suppliers found.</p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Contact Person</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($suppliers as $supplier): ?>
            <tr>
                <td><?php echo htmlspecialchars($supplier['id']); ?></td>
                <td><?php echo htmlspecialchars($supplier['name']); ?></td>
                <td><?php echo htmlspecialchars($supplier['contact_person'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($supplier['email']); ?></td>
                <td><?php echo htmlspecialchars($supplier['phone'] ?? 'N/A'); ?></td>
                <td>
                    <a href="index.php?url=suppliers/show/<?php echo $supplier['id']; ?>" class="button-info">View</a>
                    <a href="index.php?url=suppliers/edit/<?php echo $supplier['id']; ?>" class="button">Edit</a>
                    <form action="index.php?url=suppliers/destroy/<?php echo $supplier['id']; ?>" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this supplier? This might fail if the supplier is linked to other records.');">
                        <button type="submit" class="button-danger">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
