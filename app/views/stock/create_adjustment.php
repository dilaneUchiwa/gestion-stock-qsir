<?php
// $title is set by controller
// $title is set by controller
?>

<h2>Effectuer un ajustement de stock / Enregistrer le Stock Initial</h2>

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
                <?php if (isset($products) && is_array($products)): ?>
                    <?php foreach ($products as $product): ?>
                        <option value="<?php echo htmlspecialchars($product['id']); ?>" <?php echo (isset($data['product_id']) && $data['product_id'] == $product['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($product['name']); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="unit_id">Unité de mesure *</label>
            <select name="unit_id" id="unit_id" required>
                <option value="">Sélectionner une unité</option>
                <?php if (isset($allUnits) && is_array($allUnits)): ?>
                    <?php foreach ($allUnits as $unit): ?>
                        <option value="<?php echo htmlspecialchars($unit['id']); ?>" <?php echo (isset($data['unit_id']) && $data['unit_id'] == $unit['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($unit['name'] . ' (' . $unit['symbol'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="adjustment_type">Type d'ajustement *</label>
            <select name="adjustment_type" id="adjustment_type" required>
                <option value="">Sélectionner le type</option>
                <?php if (isset($adjustmentTypes) && is_array($adjustmentTypes)): ?>
                    <?php foreach ($adjustmentTypes as $key => $value): ?>
                         <option value="<?php echo htmlspecialchars($key); ?>" <?php echo (isset($data['adjustment_type']) && $data['adjustment_type'] == $key) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($value); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="quantity">Quantité *</label>
            <input type="number" name="quantity" id="quantity" value="<?php echo htmlspecialchars($data['quantity'] ?? '1'); ?>" step="any" min="0.001" required>
            <small>Saisissez une quantité positive (dans l'unité sélectionnée). Le type d'ajustement détermine l'impact sur le stock.</small>
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
