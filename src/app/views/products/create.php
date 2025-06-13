<?php $title = 'Ajouter un nouveau produit'; ?>

<h2>Ajouter un nouveau produit</h2>

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

<form action="index.php?url=products/store" method="POST">
    <fieldset>
        <legend>Informations sur le produit</legend>
        <div class="form-group">
            <label for="name">Nom du produit *</label>
            <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($data['name'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" id="description" rows="4"><?php echo htmlspecialchars($data['description'] ?? ''); ?></textarea>
        </div>
        <div class="form-group">
            <label for="parent_id">Produit parent (le cas échéant)</label>
            <select name="parent_id" id="parent_id">
                <option value="">Aucun</option>
                <?php if (!empty($products)): // This $products variable is for parent selection, passed by ProductsController@create ?>
                    <?php foreach ($products as $p): ?>
                        <option value="<?php echo htmlspecialchars($p['id']); ?>" <?php echo (isset($data['parent_id']) && $data['parent_id'] == $p['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($p['name']); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="unit_of_measure">Unité de mesure</label>
            <input type="text" name="unit_of_measure" id="unit_of_measure" value="<?php echo htmlspecialchars($data['unit_of_measure'] ?? ''); ?>" placeholder="ex: pièce, kg, litre">
        </div>
    </fieldset>

    <fieldset>
        <legend>Tarification et stock</legend>
        <div class="form-group">
            <label for="quantity_in_stock">Quantité initiale en stock</label>
            <input type="number" name="quantity_in_stock" id="quantity_in_stock" value="<?php echo htmlspecialchars($data['quantity_in_stock'] ?? '0'); ?>" min="0">
            <small>Cela créera un mouvement de 'stock_initial'. Les modifications ultérieures du stock doivent être effectuées via les livraisons, les ventes ou les ajustements.</small>
        </div>
        <div class="form-group">
            <label for="purchase_price">Prix d'achat</label>
            <input type="number" name="purchase_price" id="purchase_price" value="<?php echo htmlspecialchars($data['purchase_price'] ?? '0.00'); ?>" step="0.01" min="0">
        </div>
        <div class="form-group">
            <label for="selling_price">Prix de vente</label>
            <input type="number" name="selling_price" id="selling_price" value="<?php echo htmlspecialchars($data['selling_price'] ?? '0.00'); ?>" step="0.01" min="0">
        </div>
    </fieldset>

    <div class="form-group mt-3">
        <button type="submit" class="button button-success">Ajouter le produit</button>
        <a href="index.php?url=products" class="button button-info">Annuler</a>
    </div>
</form>
