<?php $title = 'Liste des clients'; ?>

<h2>Liste des clients</h2>

<?php
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'deleted_success') {
        echo '<div class="alert alert-success">Client supprimé avec succès.</div>';
    } elseif ($_GET['status'] == 'deleted_error') {
        echo '<div class="alert alert-danger">Erreur lors de la suppression du client. Il est peut-être associé à d\'autres enregistrements (par exemple, des ventes).</div>';
    } elseif ($_GET['status'] == 'created_success') {
         echo '<div class="alert alert-success">Client créé avec succès.</div>';
    } elseif (isset($_GET['updated_success'])) { // From update redirect
         echo '<div class="alert alert-success">Client mis à jour avec succès.</div>';
    }
}
?>

<p><a href="index.php?url=clients/create" class="button">Ajouter un nouveau client</a></p>

<?php if (empty($clients)): ?>
    <p>Aucun client trouvé.</p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Type</th>
                <th>Catégorie</th>
                <th>E-mail</th>
                <th>Téléphone</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($clients as $client): ?>
            <tr>
                <td><?php echo htmlspecialchars($client['id']); ?></td>
                <td><?php echo htmlspecialchars($client['name']); ?></td>
                <td><?php echo htmlspecialchars(ucfirst($client['client_type'])); ?></td>
                <td><?php echo htmlspecialchars($client['client_category_name'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($client['email'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($client['phone'] ?? 'N/A'); ?></td>
                <td>
                    <a href="index.php?url=clients/show/<?php echo $client['id']; ?>" class="button-info btn-sm">Voir</a>
                    <a href="index.php?url=clients/edit/<?php echo $client['id']; ?>" class="button btn-sm">Modifier</a>
                    <form action="index.php?url=clients/destroy/<?php echo $client['id']; ?>" method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce client ? Cela pourrait échouer si le client est lié à d\'autres enregistrements.');">
                        <button type="submit" class="button-danger btn-sm">Supprimer</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <style> /* TODO: Move to a global CSS file */
        .btn-sm {
            padding: 0.2rem 0.4rem;
            font-size: 0.8rem;
            margin: 0 2px;
        }
    </style>
<?php endif; ?>
