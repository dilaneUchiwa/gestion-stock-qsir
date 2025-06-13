<?php $title = 'Liste des produits'; ?>

<h2>Liste des produits</h2>

<?php if (isset($_GET['status']) && $_GET['status'] == 'deleted'): ?>
    <div class="alert alert-success">Produit supprimé avec succès.</div>
<?php endif; ?>
<?php if (isset($_GET['status']) && $_GET['status'] == 'error_deleting'): ?>
    <div class="alert alert-danger">Erreur lors de la suppression du produit.</div>
<?php endif; ?>

<p><a href="index.php?url=products/create" class="button button-success">Ajouter un nouveau produit</a></p>

<?php if (empty($products)): ?>
    <p>Aucun produit trouvé.</p>
<?php else: ?>
<div class="table-responsive-container">
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Unité</th>
                <th>Qté en stock</th>
                <th>Prix d'achat</th>
                <th class="text-right">Prix de vente</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
            <tr class="<?php if ($product['quantity_in_stock'] <= 0) echo 'negative-stock-alert'; // Example: highlight low/no stock ?>">
                <td><?php echo htmlspecialchars($product['id']); ?></td>
                <td><a href="index.php?url=stock/history/<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['name']); ?></a></td>
                <td><?php echo htmlspecialchars($product['unit_of_measure']); ?></td>
                <td class="text-right <?php if ($product['quantity_in_stock'] <= 0) echo 'font-bold'; ?>"><?php echo htmlspecialchars($product['quantity_in_stock']); ?></td>
                <td class="text-right"><?php echo htmlspecialchars(number_format($product['purchase_price'] ?? 0, 2)); ?></td>
                <td class="text-right"><?php echo htmlspecialchars(number_format($product['selling_price'] ?? 0, 2)); ?></td>
                <td>
                    <a href="index.php?url=products/show/<?php echo $product['id']; ?>" class="button button-info btn-sm">Voir</a>
                    <a href="index.php?url=products/edit/<?php echo $product['id']; ?>" class="button btn-sm">Modifier</a>
                    <form action="index.php?url=products/destroy/<?php echo $product['id']; ?>" method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce produit ? Cette action est irréversible et pourrait affecter les enregistrements associés.');">
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
.btn-sm { /* Example for smaller buttons in tables */
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}
</style>
