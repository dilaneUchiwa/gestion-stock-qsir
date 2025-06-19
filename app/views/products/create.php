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
            <label for="category_id">Catégorie</label>
            <select name="category_id" id="category_id">
                <option value="">Aucune</option>
                <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category['id']); ?>" <?php echo (isset($data['category_id']) && $data['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="base_unit_id">Unité de base *</label>
            <select name="base_unit_id" id="base_unit_id" required>
                <option value="">Sélectionnez une unité</option>
                <?php if (!empty($units)): ?>
                    <?php foreach ($units as $unit): ?>
                        <option value="<?php echo htmlspecialchars($unit['id']); ?>" <?php echo (isset($data['base_unit_id']) && $data['base_unit_id'] == $unit['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($unit['name'] . ' (' . $unit['symbol'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
    </fieldset>

    <fieldset>
        <legend>Tarification et stock</legend>
        <div class="form-group">
            <label for="purchase_price">Prix d'achat</label> <small>(pour l'unité de base sélectionnée)</small>
            <input type="number" name="purchase_price" id="purchase_price" value="<?php echo htmlspecialchars($data['purchase_price'] ?? '0.00'); ?>" step="0.01" min="0">
        </div>
        <div class="form-group">
            <label for="selling_price">Prix de vente</label> <small>(pour l'unité de base sélectionnée)</small>
            <input type="number" name="selling_price" id="selling_price" value="<?php echo htmlspecialchars($data['selling_price'] ?? ''); ?>" step="0.01" min="0" placeholder="ex: 19.99">
        </div>
    </fieldset>

    <fieldset>
        <legend>Unités alternatives</legend>
        <div id="alternative-units-container">
            <?php
            $alternativeUnitsData = $alternative_units_data ?? [];
            if (empty($alternativeUnitsData)) { // Provide at least one empty block if none submitted (e.g. first load)
                $alternativeUnitsData = [['unit_id' => '', 'conversion_factor' => '']];
            }
            foreach ($alternativeUnitsData as $index => $altUnitData):
            ?>
            <div class="alternative-unit-group" style="border: 1px solid #eee; padding: 10px; margin-bottom: 10px;">
                <div class="form-group">
                    <label for="alt_unit_id_<?php echo $index; ?>">Unité alternative</label>
                    <select name="alternative_units[<?php echo $index; ?>][unit_id]" id="alt_unit_id_<?php echo $index; ?>">
                        <option value="">Sélectionnez une unité</option>
                        <?php if (!empty($units)): ?>
                            <?php foreach ($units as $unit): ?>
                                <option value="<?php echo htmlspecialchars($unit['id']); ?>" <?php echo (isset($altUnitData['unit_id']) && $altUnitData['unit_id'] == $unit['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($unit['name'] . ' (' . $unit['symbol'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="alt_conversion_factor_<?php echo $index; ?>">Facteur de conversion vers l'unité de base</label>
                    <input type="number" step="any" name="alternative_units[<?php echo $index; ?>][conversion_factor]" id="alt_conversion_factor_<?php echo $index; ?>" value="<?php echo htmlspecialchars($altUnitData['conversion_factor'] ?? ''); ?>" placeholder="ex: 12.0">
                    <small>Ex: Si l'unité de base est "Pièce" et cette unité est "Carton de 12", le facteur est 12.</small>
                </div>
                <?php if ($index > 0): // Simple remove for statically added ones, JS will handle dynamic ones better ?>
                <!-- <button type="button" onclick="this.parentElement.remove()">Supprimer cette unité</button> -->
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" id="add-alternative-unit-button">Ajouter une autre unité alternative</button>
        <p><small>L'unité de base sera automatiquement ajoutée avec un facteur de 1. N'ajoutez pas l'unité de base ici.</small></p>
    </fieldset>

    <div class="form-group mt-3">
        <button type="submit" class="button button-success">Ajouter le produit</button>
        <a href="index.php?url=products" class="button button-info">Annuler</a>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addAlternativeUnitButton = document.getElementById('add-alternative-unit-button');
    const allUnitsData = <?php echo json_encode($units ?? []); ?>; // All units passed from controller

    addAlternativeUnitButton.addEventListener('click', function() {
        const container = document.getElementById('alternative-units-container');
        const index = container.getElementsByClassName('alternative-unit-group').length;
        const baseUnitIdSelected = document.getElementById('base_unit_id').value;

        let optionsHTML = '<option value="">Sélectionnez une unité</option>';
        allUnitsData.forEach(function(unit) {
            if (unit.id !== baseUnitIdSelected) { // Exclude the selected base unit
                optionsHTML += `<option value="${escapeHTML(unit.id)}">${escapeHTML(unit.name)} (${escapeHTML(unit.symbol)})</option>`;
            }
        });

        const newUnitHTML = `
        <div class="alternative-unit-group" style="border: 1px solid #eee; padding: 10px; margin-bottom: 10px;">
            <div class="form-group">
                <label for="alt_unit_id_${index}">Unité alternative</label>
                <select name="alternative_units[${index}][unit_id]" id="alt_unit_id_${index}" required>
                    ${optionsHTML}
                </select>
            </div>
            <div class="form-group">
            <label for="alt_conversion_factor_${index}">Facteur de conversion vers l'unité de base *</label>
            <input type="number" step="any" name="alternative_units[${index}][conversion_factor]" id="alt_conversion_factor_${index}" placeholder="ex: 12.0" required>
            <small>Ex: Si l'unité de base est "Pièce" et cette unité est "Carton de 12", le facteur est 12.</small>
        </div>
        <button type="button" class="button button-danger" onclick="this.parentElement.remove()">Supprimer</button>
    </div>`;
        container.insertAdjacentHTML('beforeend', newUnitHTML);
    });

    // Helper function to escape HTML, to prevent XSS if unit names somehow contain it
    function escapeHTML(str) {
        var p = document.createElement("p");
        p.appendChild(document.createTextNode(str));
        return p.innerHTML;
    }
});
</script>
