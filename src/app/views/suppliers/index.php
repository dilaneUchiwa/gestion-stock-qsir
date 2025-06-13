<?php $title = 'Liste des fournisseurs'; ?>

<h2>Liste des fournisseurs</h2>

<?php
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'deleted_success') {
        echo '<div class="alert alert-success">Fournisseur supprimé avec succès.</div>';
    } elseif ($_GET['status'] == 'deleted_error') {
        echo '<div class="alert alert-danger">Erreur lors de la suppression du fournisseur. Il est peut-être associé à d\'autres enregistrements.</div>';
    } elseif ($_GET['status'] == 'created_success') { // Assuming we might add this from store redirect
         echo '<div class="alert alert-success">Fournisseur créé avec succès.</div>';
    }
}
?>

<p><a href="index.php?url=suppliers/create" class="button">Ajouter un nouveau fournisseur</a></p>

<?php if (empty($suppliers)): ?>
    <p>Aucun fournisseur trouvé.</p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Personne de contact</th>
                <th>Email</th>
                <th>Téléphone</th>
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
                    <a href="index.php?url=suppliers/show/<?php echo $supplier['id']; ?>" class="button-info">Voir</a>
                    <a href="index.php?url=suppliers/edit/<?php echo $supplier['id']; ?>" class="button">Modifier</a>
                    <form action="index.php?url=suppliers/destroy/<?php echo $supplier['id']; ?>" method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce fournisseur ? Cette opération peut échouer si le fournisseur est lié à d\'autres enregistrements.');">
                        <button type="submit" class="button-danger">Supprimer</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
