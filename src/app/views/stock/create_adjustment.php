<?php
// $title is set by controller
// $title = 'Créer un ajustement de stock';
?>

<h2>Créer un ajustement de stock manuel</h2>

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

<form action="index.php?url=stock/store_adjustment" method="POST">
    <fieldset>
        <legend>Détails de l'ajustement</legend>
        <div class="form-group">
            <label for="product_id">Produit *</label>
            <select name="product_id" id="product_id" required>
                <option value="">Sélectionner un produit</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?php echo htmlspecialchars($product['id']); ?>" <?php echo (isset($data['product_id']) && $data['product_id'] == $product['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($product['name']); ?> (Stock actuel : <?php echo htmlspecialchars($product['quantity_in_stock']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="adjustment_type">Type d'ajustement *</label>
            <select name="adjustment_type" id="adjustment_type" required>
                <option value="">Sélectionner le type</option>
                <?php foreach ($adjustmentTypes as $type): ?>
                     <option value="<?php echo htmlspecialchars($type); ?>" <?php echo (isset($data['adjustment_type']) && $data['adjustment_type'] == $type) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $type))); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="quantity">Quantité *</label>
            <input type="number" name="quantity" id="quantity" value="<?php echo htmlspecialchars($data['quantity'] ?? '1'); ?>" min="1" required>
            <small>Saisissez une quantité positive. Le type sélectionné ci-dessus détermine s'il s'agit d'une augmentation ou d'une diminution.</small>
        </div>

        <div class="form-group">
            <label for="notes">Raison / Remarques</label>
            <textarea name="notes" id="notes" rows="3"><?php echo htmlspecialchars($data['notes'] ?? ''); ?></textarea>
        </div>
    </fieldset>

    <div class="form-group" style="margin-top: 20px;">
        <button type="submit" class="button">Soumettre l'ajustement</button>
        <a href="index.php?url=stock/index" class="button-info">Annuler</a>
    </div>
</form>
