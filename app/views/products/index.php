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
                <th>Catégorie</th>
                <th>Unité de base</th>
                <th>Disponibilité Stock</th>
                <th>Prix d'achat</th>
                <th class="text-right">Prix de vente</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
            <tr>
                <td><?php echo htmlspecialchars($product['id']); ?></td>
                <td><a href="index.php?url=stock/history/<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['name']); ?></a></td>
                <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($product['base_unit_name'] ?? 'N/A'); ?> (<?php echo htmlspecialchars($product['base_unit_symbol'] ?? 'N/A'); ?>)</td>
                <td class="text-left">
                    <?php if (empty($product['detailed_stock'])): ?>
                        Aucune information de stock.
                    <?php else: ?>
                        <?php foreach ($product['detailed_stock'] as $stock_detail): ?>
                            <?php
                                echo htmlspecialchars(number_format($stock_detail['quantity_in_stock'], 2, ',', ' ')) . ' ' . htmlspecialchars($stock_detail['unit_symbol']);
                                // Optionally, show equivalent in base unit if not the base unit itself and factor is not 1
                                if ($stock_detail['unit_id'] != $product['base_unit_id'] && (float)$stock_detail['conversion_factor_to_base_unit'] != 1.0) {
                                    $equivalentInBase = $stock_detail['quantity_in_stock'] * (float)$stock_detail['conversion_factor_to_base_unit'];
                                    if ($equivalentInBase > 0 || $stock_detail['quantity_in_stock'] != 0) { // Show if non-zero quantity or if equivalent is meaningful
                                        echo ' <small>(équiv. ' . htmlspecialchars(number_format($equivalentInBase, 2, ',', ' ')) . ' ' . htmlspecialchars($product['base_unit_symbol']) . ')</small>';
                                    }
                                }
                                echo '<br>';
                            ?>
                        <?php endforeach; ?>
                        --- <br>
                        <strong>Total estimé: <?php echo htmlspecialchars(number_format($product['total_stock_in_base_unit'], 2, ',', ' ')); ?> <?php echo htmlspecialchars($product['base_unit_symbol']); ?></strong>
                    <?php endif; ?>
                </td>
                <td class="text-right"><?php echo htmlspecialchars(number_format((float)($product['purchase_price'] ?? 0), 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?></td>
                <td class="text-right"><?php echo htmlspecialchars(number_format((float)($product['selling_price'] ?? 0), 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?></td>
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
