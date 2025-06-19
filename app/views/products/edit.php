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
            <label for="category_id">Catégorie</label>
            <select name="category_id" id="category_id">
                <option value="">Aucune</option>
                <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category['id']); ?>" <?php echo (isset($product['category_id']) && $product['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="base_unit_id">Unité de base *</label>
            <select name="base_unit_id" id="base_unit_id" required disabled>
                <option value="">Sélectionnez une unité</option>
                <?php if (!empty($units)): ?>
                    <?php foreach ($units as $unit): ?>
                        <option value="<?php echo htmlspecialchars($unit['id']); ?>" <?php echo (isset($product['base_unit_id']) && $product['base_unit_id'] == $unit['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($unit['name'] . ' (' . $unit['symbol'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <small>L'unité de base ne peut pas être modifiée après la création du produit (pour l'instant).</small>
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
            <label for="purchase_price">Prix d'achat</label> <small>(pour l'unité de base)</small>
            <input type="number" name="purchase_price" id="purchase_price" value="<?php echo htmlspecialchars($product['purchase_price'] ?? '0.00'); ?>" step="0.01" min="0">
        </div>
        <div class="form-group">
            <label for="selling_price">Prix de vente</label> <small>(pour l'unité de base)</small>
            <input type="number" name="selling_price" id="selling_price" value="<?php echo htmlspecialchars($product['selling_price'] ?? ''); ?>" step="0.01" min="0" placeholder="ex: 19.99">
        </div>
    </fieldset>

    <fieldset>
        <legend>Unités alternatives</legend>
        <div id="alternative-units-container">
            <?php
            // Errors might repopulate $_POST['alternative_units'] which should be used if available
            // Otherwise, use $alternative_units_details from the controller
            $currentAlternativeUnits = isset($_POST['alternative_units']) ? $_POST['alternative_units'] : ($alternative_units_details ?? []);

            if (empty($currentAlternativeUnits)) {
                 // No existing alternatives, and none submitted (e.g. after validation error on other fields)
                 // Provide one empty block for adding a new one.
                $currentAlternativeUnits = [['unit_id' => '', 'conversion_factor' => '']];
            }

            foreach ($currentAlternativeUnits as $index => $altUnit):
                $altUnitId = $altUnit['unit_id'] ?? ($altUnit['id'] ?? null); // Handle both POST data and model data structure
                $altUnitFactor = $altUnit['conversion_factor'] ?? ($altUnit['conversion_factor_to_base_unit'] ?? '');
            ?>
            <div class="alternative-unit-group" style="border: 1px solid #eee; padding: 10px; margin-bottom: 10px;">
                <input type="hidden" name="alternative_units[<?php echo $index; ?>][original_unit_id_for_identification_if_needed]" value="<?php echo htmlspecialchars($altUnitId); ?>">
                <div class="form-group">
                    <label for="alt_unit_id_<?php echo $index; ?>">Unité alternative</label>
                    <select name="alternative_units[<?php echo $index; ?>][unit_id]" id="alt_unit_id_<?php echo $index; ?>">
                        <option value="">Sélectionnez une unité</option>
                        <?php if (!empty($units)): ?>
                            <?php foreach ($units as $unitOption): ?>
                                <?php if ($unitOption['id'] == $product['base_unit_id']) continue; // Don't list base unit as an option here ?>
                                <option value="<?php echo htmlspecialchars($unitOption['id']); ?>" <?php echo ($altUnitId == $unitOption['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($unitOption['name'] . ' (' . $unitOption['symbol'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="alt_conversion_factor_<?php echo $index; ?>">Facteur de conversion vers l'unité de base</label>
                    <input type="number" step="any" name="alternative_units[<?php echo $index; ?>][conversion_factor]" id="alt_conversion_factor_<?php echo $index; ?>" value="<?php echo htmlspecialchars($altUnitFactor); ?>" placeholder="ex: 12.0">
                    <small>Ex: Si l'unité de base est "Pièce" et cette unité est "Carton de 12", le facteur est 12.</small>
                </div>
                <button type="button" class="button button-danger" onclick="removeAlternativeUnitGroup(this)">Supprimer cette unité</button>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" id="add-alternative-unit-button">Ajouter une unité alternative</button>
         <p><small>L'unité de base (<?php echo htmlspecialchars($product['base_unit_name'] ?? ''); ?>) ne peut pas être ajoutée ici. Son facteur est toujours 1.</small></p>
    </fieldset>

    <div class="form-group mt-3">
        <button type="submit" class="button button-success">Mettre à jour le produit</button>
        <a href="index.php?url=products/show/<?php echo htmlspecialchars($product['id']); ?>" class="button button-info">Annuler</a>
    </div>
</form>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const unitsData = <?php echo json_encode($units ?? []); ?>;
    const baseUnitId = <?php echo json_encode($product['base_unit_id'] ?? null); ?>;

    document.getElementById('add-alternative-unit-button').addEventListener('click', function() {
        const container = document.getElementById('alternative-units-container');
        const index = container.getElementsByClassName('alternative-unit-group').length;

        let optionsHTML = '<option value="">Sélectionnez une unité</option>';
        unitsData.forEach(function(unit) {
            if (unit.id != baseUnitId) { // Exclude base unit from options
                optionsHTML += `<option value="${escapeHTML(unit.id)}">${escapeHTML(unit.name)} (${escapeHTML(unit.symbol)})</option>`;
            }
        });

        const newUnitHTML = `
        <div class="alternative-unit-group" style="border: 1px solid #eee; padding: 10px; margin-bottom: 10px;">
            <input type="hidden" name="alternative_units[${index}][original_unit_id_for_identification_if_needed]" value="">
            <div class="form-group">
                <label for="alt_unit_id_${index}">Unité alternative</label>
                <select name="alternative_units[${index}][unit_id]" id="alt_unit_id_${index}">
                    ${optionsHTML}
                </select>
            </div>
            <div class="form-group">
                <label for="alt_conversion_factor_${index}">Facteur de conversion vers l'unité de base</label>
                <input type="number" step="any" name="alternative_units[${index}][conversion_factor]" id="alt_conversion_factor_${index}" placeholder="ex: 12.0">
                <small>Ex: Si l'unité de base est "Pièce" et cette unité est "Carton de 12", le facteur est 12.</small>
            </div>
            <button type="button" class="button button-danger" onclick="removeAlternativeUnitGroup(this)">Supprimer cette unité</button>
        </div>`;
        container.insertAdjacentHTML('beforeend', newUnitHTML);
    });
});

function removeAlternativeUnitGroup(button) {
    button.parentElement.remove();
    // Note: This simple removal might cause index gaps if server-side expects continuous indices.
    // The current controller logic (delete all then re-add) is robust to this.
    // For more granular updates, index re-management or unique IDs per row would be needed.
}

function escapeHTML(str) {
    var p = document.createElement("p");
    p.appendChild(document.createTextNode(str));
    return p.innerHTML;
}
</script>
