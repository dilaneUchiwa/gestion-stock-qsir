<?php
// $title is set by controller
// $title = 'Aperçu du stock';
?>

<h2>Aperçu du stock</h2>

<p>
    <a href="index.php?url=stock/create_adjustment" class="button">Créer un ajustement manuel</a>
</p>

<?php if (empty($products)): ?>
    <p>Aucun produit trouvé pour afficher le stock.</p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom du produit</th>
                <th>Unité</th>
                <th>Stock actuel (en cache)</th>
                <!-- <th>Stock calculé (en direct)</th> -->
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
            <tr>
                <td><?php echo htmlspecialchars($product['id']); ?></td>
                <td><?php echo htmlspecialchars($product['name']); ?></td>
                <td><?php echo htmlspecialchars($product['unit_of_measure']); ?></td>
                <td style="text-align: right;"><?php echo htmlspecialchars($product['quantity_in_stock']); ?></td>
                <!-- <td style="text-align: right;"><?php // echo htmlspecialchars($product['calculated_stock'] ?? 'N/A'); ?></td> -->
                <td>
                    <a href="index.php?url=stock/history/<?php echo $product['id']; ?>" class="button-info">Voir l'historique</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<p><small>Note : "Stock actuel (en cache)" est la valeur stockée directement avec le produit, mise à jour par les transactions. Idéalement, elle devrait correspondre à un calcul en direct à partir des mouvements.</small></p>
