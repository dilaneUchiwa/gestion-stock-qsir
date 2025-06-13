<?php $title = isset($product['name']) ? 'Détails du produit : ' . htmlspecialchars($product['name']) : 'Détails du produit'; ?>

<?php if ($product): ?>
    <h2><?php echo htmlspecialchars($product['name']); ?></h2>
    <p><strong>ID :</strong> <?php echo htmlspecialchars($product['id']); ?></p>
    <p><strong>Description :</strong> <?php echo nl2br(htmlspecialchars($product['description'] ?? 'N/A')); ?></p>
    <p><strong>Unité de mesure :</strong> <?php echo htmlspecialchars($product['unit_of_measure'] ?? 'N/A'); ?></p>
    <p><strong>Quantité en stock :</strong> <?php echo htmlspecialchars($product['quantity_in_stock'] ?? '0'); ?></p>
    <p><strong>Prix d'achat :</strong> <?php echo htmlspecialchars($product['purchase_price'] ?? '0.00'); ?></p>
    <p><strong>Prix de vente :</strong> <?php echo htmlspecialchars($product['selling_price'] ?? '0.00'); ?></p>
    <p><strong>Créé le :</strong> <?php echo htmlspecialchars($product['created_at']); ?></p>
    <p><strong>Mis à jour le :</strong> <?php echo htmlspecialchars($product['updated_at']); ?></p>

    <?php if ($parent): ?>
        <h3>Produit parent</h3>
        <p><a href="index.php?url=products/show/<?php echo $parent['id']; ?>"><?php echo htmlspecialchars($parent['name']); ?></a></p>
    <?php elseif ($product['parent_id']): ?>
        <p><strong>ID du produit parent :</strong> <?php echo htmlspecialchars($product['parent_id']); ?> (Détails du parent non trouvés ou parent masqué)</p>
    <?php else: ?>
        <p>Ce produit n'a pas de parent.</p>
    <?php endif; ?>

    <?php if (!empty($children)): ?>
        <h3>Produits/Composants enfants</h3>
        <ul>
            <?php foreach ($children as $child): ?>
                <li><a href="index.php?url=products/show/<?php echo $child['id']; ?>"><?php echo htmlspecialchars($child['name']); ?></a> (Qté : <?php echo htmlspecialchars($child['quantity_in_stock']); ?>)</li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Ce produit n'a pas de composants enfants listés.</p>
    <?php endif; ?>

    <p style="margin-top: 20px;">
        <a href="index.php?url=products/edit/<?php echo $product['id']; ?>" class="button">Modifier le produit</a>
        <a href="index.php?url=products" class="button-info">Retour à la liste</a>
    </p>
<?php else: ?>
    <p>Produit non trouvé.</p>
    <a href="index.php?url=products" class="button-info">Retour à la liste</a>
<?php endif; ?>
