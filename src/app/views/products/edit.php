<?php $title = isset($product['name']) ? 'Modifier le produit : ' . htmlspecialchars($product['name']) : 'Modifier le produit'; ?>

<h2>Modifier le produit : <?php echo htmlspecialchars($product['name'] ?? 'N/A'); ?></h2>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <p><strong>Veuillez corriger les erreurs suivantes :</strong></p>
        <ul>
            <?php foreach ($errors as $field => $error): ?>
                <li><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $field))); ?>: <?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (empty($product)): ?>
    <p>Produit non trouvé.</p>
    <a href="index.php?url=products" class="button button-info">Retour à la liste</a>
<?php else: ?>
<form action="index.php?url=products/update/<?php echo htmlspecialchars($product['id']); ?>" method="POST">
    <fieldset>
        <legend>Informations sur le produit</legend>
        <div class="form-group">
            <label for="name">Nom du produit *</label>
            <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" id="description" rows="4"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
        </div>
        <div class="form-group">
            <label for="parent_id">Produit parent (le cas échéant)</label>
            <select name="parent_id" id="parent_id">
                <option value="">Aucun</option>
                <?php if (!empty($products)): // This $products variable is for parent selection, passed by ProductsController@edit ?>
                    <?php foreach ($products as $p): ?>
                        <?php if ($p['id'] == $product['id']) continue; // Prevent self-assignment ?>
                        <option value="<?php echo htmlspecialchars($p['id']); ?>" <?php echo (isset($product['parent_id']) && $product['parent_id'] == $p['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($p['name']); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="unit_of_measure">Unité de mesure</label>
            <input type="text" name="unit_of_measure" id="unit_of_measure" value="<?php echo htmlspecialchars($product['unit_of_measure'] ?? ''); ?>" placeholder="ex: pièce, kg, litre">
        </div>
    </fieldset>

    <fieldset>
        <legend>Tarification et stock</legend>
        <div class="form-group">
            <label for="quantity_in_stock">Quantité en stock (en cache)</label>
            <input type="number" name="quantity_in_stock" id="quantity_in_stock" value="<?php echo htmlspecialchars($product['quantity_in_stock'] ?? '0'); ?>" readonly>
            <small>Le stock est géré via les livraisons, les ventes ou les ajustements de stock. <a href="index.php?url=stock/history/<?php echo $product['id']; ?>">Voir l'historique</a> ou <a href="index.php?url=stock/create_adjustment&product_id=<?php echo $product['id']; ?>">créer un ajustement</a>.</small>
        </div>
        <div class="form-group">
            <label for="purchase_price">Prix d'achat</label>
            <input type="number" name="purchase_price" id="purchase_price" value="<?php echo htmlspecialchars($product['purchase_price'] ?? '0.00'); ?>" step="0.01" min="0">
        </div>
        <div class="form-group">
            <label for="selling_price">Prix de vente</label>
            <input type="number" name="selling_price" id="selling_price" value="<?php echo htmlspecialchars($product['selling_price'] ?? '0.00'); ?>" step="0.01" min="0">
        </div>
    </fieldset>

    <div class="form-group mt-3">
        <button type="submit" class="button button-success">Mettre à jour le produit</button>
        <a href="index.php?url=products/show/<?php echo htmlspecialchars($product['id']); ?>" class="button button-info">Annuler</a>
    </div>
</form>
<?php endif; ?>
