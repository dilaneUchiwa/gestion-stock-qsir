<?php $title = isset($product['name']) ? 'Détails du produit : ' . htmlspecialchars($product['name']) : 'Détails du produit'; ?>

<?php if ($product): ?>
    <h2><?php echo htmlspecialchars($product['name']); ?></h2>
    <p><strong>ID :</strong> <?php echo htmlspecialchars($product['id']); ?></p>
    <p><strong>Description :</strong> <?php echo nl2br(htmlspecialchars($product['description'] ?? 'N/A')); ?></p>
    <p><strong>Catégorie :</strong> <?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></p>
    <p><strong>Unité de base :</strong> <?php echo htmlspecialchars($product['base_unit_name'] ?? 'N/A'); ?> (<?php echo htmlspecialchars($product['base_unit_symbol'] ?? ''); ?>)</p>
    <p><strong>Prix d'achat :</strong> <?php echo htmlspecialchars(number_format((float)($product['purchase_price'] ?? 0), 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?></p>
    <p><strong>Prix de vente :</strong> <?php echo htmlspecialchars(number_format((float)($product['selling_price'] ?? 0), 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?></p>
    <p><strong>Créé le :</strong> <?php echo htmlspecialchars($product['created_at']); ?></p>
    <p><strong>Mis à jour le :</strong> <?php echo htmlspecialchars($product['updated_at']); ?></p>

    <h3>État Actuel du Stock</h3>
    <?php if (isset($product['detailed_stock']) && !empty($product['detailed_stock'])): ?>
        <ul>
            <?php foreach ($product['detailed_stock'] as $stock_detail): ?>
                <li>
                    <strong><?php echo htmlspecialchars(number_format($stock_detail['quantity_in_stock'], 2, ',', ' ')); ?></strong>
                    <?php echo htmlspecialchars($stock_detail['unit_name']); ?>
                    (<?php echo htmlspecialchars($stock_detail['unit_symbol']); ?>)
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Aucune information de stock disponible pour les unités configurées.</p>
    <?php endif; ?>
    <p>
        <strong>Total Estimé en Unité de Base :</strong>
        <?php echo htmlspecialchars(number_format((float)($product['total_stock_in_base_unit'] ?? 0), 2, ',', ' ')); ?>
        <?php echo htmlspecialchars($product['base_unit_symbol'] ?? ''); ?>
    </p>

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
