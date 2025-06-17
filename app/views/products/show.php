<?php $title = isset($product['name']) ? 'Détails du produit : ' . htmlspecialchars($product['name']) : 'Détails du produit'; ?>

<?php if ($product): ?>
    <h2><?php echo htmlspecialchars($product['name']); ?></h2>
    <p><strong>ID :</strong> <?php echo htmlspecialchars($product['id']); ?></p>
    <p><strong>Description :</strong> <?php echo nl2br(htmlspecialchars($product['description'] ?? 'N/A')); ?></p>
    <p><strong>Catégorie :</strong> <?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></p>
    <p><strong>Unité de base :</strong> <?php echo htmlspecialchars($product['base_unit_name'] ?? 'N/A'); ?> (<?php echo htmlspecialchars($product['base_unit_symbol'] ?? ''); ?>)</p>
    <p><strong>Quantité en stock (en unité de base) :</strong> <?php echo htmlspecialchars($product['quantity_in_stock'] ?? '0'); ?></p>
    <p><strong>Prix d'achat :</strong> <?php echo htmlspecialchars(number_format((float)($product['purchase_price'] ?? 0), 2, ',', ' ')); ?> €</p>
    <p><strong>Prix de vente :</strong> <?php echo htmlspecialchars(number_format((float)($product['selling_price'] ?? 0), 2, ',', ' ')); ?> €</p>
    <p><strong>Créé le :</strong> <?php echo htmlspecialchars($product['created_at']); ?></p>
    <p><strong>Mis à jour le :</strong> <?php echo htmlspecialchars($product['updated_at']); ?></p>

    <h3>Stock dans d'autres unités</h3>
    <?php
    $stockInBaseUnit = (float)($product['quantity_in_stock'] ?? 0);
    $hasAlternativeUnitsForStockDisplay = false;
    if (!empty($productConfiguredUnits)): ?>
        <ul>
            <?php foreach ($productConfiguredUnits as $unit): ?>
                <?php if (!$unit['is_base_unit'] && isset($unit['conversion_factor_to_base_unit']) && (float)$unit['conversion_factor_to_base_unit'] != 0):
                    $hasAlternativeUnitsForStockDisplay = true;
                    $stockInThisUnit = $stockInBaseUnit / (float)$unit['conversion_factor_to_base_unit'];
                ?>
                <li>
                    <strong><?php echo htmlspecialchars(number_format($stockInThisUnit, 2, ',', ' ')); ?></strong>
                    <?php echo htmlspecialchars($unit['name'] . ' (' . $unit['symbol'] . ')'); ?>
                </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
        <?php if (!$hasAlternativeUnitsForStockDisplay): ?>
             <p>Aucune unité alternative avec facteur de conversion valide n'est configurée pour afficher le stock différemment.</p>
        <?php endif; ?>
    <?php else: ?>
        <p>Aucune unité alternative configurée pour ce produit.</p>
    <?php endif; ?>


    <h3>Configurations d'unités de mesure</h3>
    <?php if (!empty($productConfiguredUnits)): ?>
        <ul>
            <?php foreach ($productConfiguredUnits as $pu_detail): ?>
                <li>
                    <strong><?php echo htmlspecialchars($pu_detail['name'] . ' (' . $pu_detail['symbol'] . ')'); ?></strong>:
                    Facteur de conversion vers l'unité de base (<?php echo htmlspecialchars($product['base_unit_name']); ?>) :
                    <?php echo htmlspecialchars(rtrim(rtrim(number_format((float)$pu_detail['conversion_factor_to_base_unit'], 5, ',', ' '), '0'), ',')); ?>
                    <?php if ($pu_detail['is_base_unit']): ?>
                        <strong>(Unité de base)</strong>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Aucune unité alternative définie pour ce produit. L'unité de base est la seule utilisable.</p>
    <?php endif; ?>

    <p style="margin-top: 20px;">
        <a href="index.php?url=products/edit/<?php echo $product['id']; ?>" class="button button-primary">Modifier le produit</a>
        <a href="index.php?url=products" class="button-info">Retour à la liste</a>
    </p>
<?php else: ?>
    <p>Produit non trouvé.</p>
    <a href="index.php?url=products" class="button-info">Retour à la liste</a>
<?php endif; ?>
