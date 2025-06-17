<?php
// $title is passed from controller
$pageTitle = $title ?? 'Catégories de Produits';
?>

<h2><?php echo htmlspecialchars($pageTitle); ?></h2>

<?php if (isset($_GET['status'])): ?>
    <?php if ($_GET['status'] == 'created_success'): ?>
        <div class="alert alert-success">Catégorie créée avec succès.</div>
    <?php elseif ($_GET['status'] == 'updated_success'): ?>
        <div class="alert alert-success">Catégorie mise à jour avec succès.</div>
    <?php elseif ($_GET['status'] == 'deleted_success'): ?>
        <div class="alert alert-success">Catégorie supprimée avec succès.</div>
    <?php elseif ($_GET['status'] == 'delete_failed'): ?>
        <div class="alert alert-danger">Échec de la suppression de la catégorie.</div>
    <?php elseif ($_GET['status'] == 'delete_not_found'): ?>
        <div class="alert alert-warning">Catégorie à supprimer non trouvée.</div>
    <?php elseif ($_GET['status'] == 'delete_error'): ?>
        <div class="alert alert-danger">Erreur lors de la suppression : <?php echo htmlspecialchars($_GET['message'] ?? ''); ?></div>
    <?php endif; ?>
<?php endif; ?>

<p><a href="index.php?url=productcategories/create" class="button button-success">Créer une nouvelle catégorie</a></p>

<?php if (empty($categories)): ?>
    <p>Aucune catégorie de produits n'a été trouvée.</p>
<?php else: ?>
<div class="table-responsive-container">
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $category): ?>
            <tr>
                <td><?php echo htmlspecialchars($category['id']); ?></td>
                <td><?php echo htmlspecialchars($category['name']); ?></td>
                <td><?php echo nl2br(htmlspecialchars($category['description'] ?? 'N/A')); ?></td>
                <td>
                    <a href="index.php?url=productcategories/edit/<?php echo $category['id']; ?>" class="button btn-sm">Modifier</a>
                    <form action="index.php?url=productcategories/destroy/<?php echo $category['id']; ?>" method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ? Tous les produits de cette catégorie verront leur catégorie réinitialisée.');">
                        <button type="submit" class="button button-danger btn-sm">Supprimer</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<style>
/* Specific styles for this view if needed, or move to main style.css */
.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}
</style>
