<?php
$pageTitle = $title ?? 'Fractionner un Produit';
$formData = $data ?? [];
$formErrors = $errors ?? [];

// Success message from GET params
if (isset($_GET['status']) && $_GET['status'] == 'success') {
    $fromQty = htmlspecialchars($_GET['from_qty'] ?? '');
    $fromUnitSym = ''; // We need to fetch this based on $_GET['from_unit']
    $toQty = htmlspecialchars($_GET['to_qty'] ?? '');
    $toUnitSym = '';   // We need to fetch this based on $_GET['to_unit']
    $prodName = '';    // We need to fetch this based on $_GET['prod_id']

    // This is a simplified way to get names for the message.
    // In a real app, you might pass these names directly or have a helper.
    if(isset($_GET['from_unit']) && !empty($allUnits)){
        foreach($allUnits as $u) { if($u['id'] == $_GET['from_unit']) $fromUnitSym = $u['symbol']; break;}
    }
    if(isset($_GET['to_unit']) && !empty($allUnits)){
        foreach($allUnits as $u) { if($u['id'] == $_GET['to_unit']) $toUnitSym = $u['symbol']; break;}
    }
    if(isset($_GET['prod_id']) && !empty($products)){
         foreach($products as $p) { if($p['id'] == $_GET['prod_id']) $prodName = $p['name']; break;}
    }

    echo '<div class="alert alert-success">Fractionnement réussi : '.$fromQty.' '.$fromUnitSym.' de '.$prodName.' transformé en '.$toQty.' '.$toUnitSym.'.</div>';
}
?>

<h2><?php echo htmlspecialchars($pageTitle); ?></h2>

<?php if (!empty($formErrors)): ?>
    <div class="alert alert-danger">
        <p><strong>Veuillez corriger les erreurs suivantes :</strong></p>
        <ul>
            <?php foreach ($formErrors as $field => $error): ?>
                <li><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $field))); ?>: <?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form action="index.php?url=fractioning/process" method="POST" id="fractioningForm">
    <fieldset>
        <legend>Produit Source (à fractionner)</legend>
        <div class="form-group">
            <label for="source_product_id">Produit *</label>
            <select name="source_product_id" id="source_product_id" required>
                <option value="">Sélectionnez un produit</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?php echo htmlspecialchars($product['id']); ?>" <?php echo (isset($formData['source_product_id']) && $formData['source_product_id'] == $product['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($product['name']); ?> (Stock: <?php echo htmlspecialchars($product['quantity_in_stock'] . ' ' . $product['base_unit_symbol']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="source_unit_id">Unité du produit source *</label>
            <select name="source_unit_id" id="source_unit_id" required>
                <option value="">Sélectionnez d'abord un produit</option>
                <?php /* Populated by JS */ ?>
            </select>
        </div>
        <div class="form-group">
            <label for="source_quantity_to_fraction">Quantité à fractionner (en unité source) *</label>
            <input type="number" name="source_quantity_to_fraction" id="source_quantity_to_fraction" value="<?php echo htmlspecialchars($formData['source_quantity_to_fraction'] ?? '1'); ?>" min="0.001" step="any" required>
        </div>
    </fieldset>

    <fieldset>
        <legend>Produit Cible (résultat du fractionnement)</legend>
        <div class="form-group">
            <label for="target_product_id">Produit cible *</label>
            <select name="target_product_id" id="target_product_id" required>
                <option value="">Sélectionnez un produit</option>
                 <?php foreach ($products as $product): ?>
                    <option value="<?php echo htmlspecialchars($product['id']); ?>" <?php echo (isset($formData['target_product_id']) && $formData['target_product_id'] == $product['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($product['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small>Pour cette version, le produit cible doit être le même que le produit source.</small>
        </div>
        <div class="form-group">
            <label for="target_unit_id">Unité du produit cible *</label>
            <select name="target_unit_id" id="target_unit_id" required>
                <option value="">Sélectionnez d'abord un produit cible</option>
                <?php /* Populated by JS */ ?>
            </select>
        </div>
        <div class="form-group">
            <label>Quantité cible estimée :</label>
            <p id="estimated_target_quantity_display" style="font-weight:bold;">-</p>
            <small>Ceci est une estimation basée sur les facteurs de conversion. La quantité réelle sera calculée lors du traitement.</small>
        </div>
    </fieldset>

    <div class="form-group" style="margin-top: 20px;">
        <button type="submit" class="button button-success">Exécuter le Fractionnement</button>
        <a href="index.php?url=fractioning/index" class="button button-info">Annuler</a>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const sourceProductSelect = document.getElementById('source_product_id');
    const sourceUnitSelect = document.getElementById('source_unit_id');
    const targetProductSelect = document.getElementById('target_product_id');
    const targetUnitSelect = document.getElementById('target_unit_id');
    const sourceQuantityInput = document.getElementById('source_quantity_to_fraction');
    const estimatedTargetQtyDisplay = document.getElementById('estimated_target_quantity_display');

    const productUnitsMap = <?php echo json_encode($productUnitsMap ?? []); ?>;
    const productsData = <?php echo json_encode($products ?? []); ?>; // Basic product data for base_unit_id

    function populateUnitSelect(productSelectElement, unitSelectElement, selectedUnitId = null) {
        const productId = productSelectElement.value;
        unitSelectElement.innerHTML = '<option value="">Chargement...</option>';

        const unitsForProduct = productUnitsMap[productId] || [];
        const productInfo = productsData.find(p => p.id == productId);

        unitSelectElement.innerHTML = ''; // Clear
        if (unitsForProduct.length > 0) {
            unitsForProduct.forEach(pu => {
                const option = document.createElement('option');
                option.value = pu.unit_id;
                option.textContent = `${pu.name} (${pu.symbol}) - Fact: ${pu.conversion_factor_to_base_unit}`;
                option.dataset.conversionFactor = pu.conversion_factor_to_base_unit;
                if (selectedUnitId && pu.unit_id == selectedUnitId) {
                    option.selected = true;
                } else if (!selectedUnitId && productInfo && pu.unit_id == productInfo.base_unit_id) {
                     // option.selected = true; // Optionally select base unit by default
                }
                unitSelectElement.appendChild(option);
            });
             if (!selectedUnitId && unitSelectElement.options.length > 0 && !productInfo.base_unit_id) {
                // if no specific selection and no clear base_unit to preselect, select first one.
                // unitSelectElement.options[0].selected = true;
            }
        } else if (productInfo && productInfo.base_unit_id && productInfo.base_unit_name) { // Fallback to product's base unit if no map entry
            const option = document.createElement('option');
            option.value = productInfo.base_unit_id;
            option.textContent = `${productInfo.base_unit_name} (${productInfo.base_unit_symbol}) - Fact: 1`;
            option.dataset.conversionFactor = "1.00000";
            option.selected = true;
            unitSelectElement.appendChild(option);
        } else {
            unitSelectElement.innerHTML = '<option value="">Aucune unité configurée</option>';
        }
        unitSelectElement.dispatchEvent(new Event('change')); // Trigger change for potential dependent calculations
    }

    function calculateEstimatedTargetQuantity() {
        const sourceProdId = sourceProductSelect.value;
        const sourceUnitId = sourceUnitSelect.value;
        const sourceQty = parseFloat(sourceQuantityInput.value);
        const targetProdId = targetProductSelect.value; // V1: same as source
        const targetUnitId = targetUnitSelect.value;

        if (!sourceProdId || !sourceUnitId || !sourceQty || !targetProdId || !targetUnitId || sourceQty <= 0) {
            estimatedTargetQtyDisplay.textContent = '-';
            return;
        }

        const sourceUnitOption = sourceUnitSelect.querySelector(`option[value="${sourceUnitId}"]`);
        const targetUnitOption = targetUnitSelect.querySelector(`option[value="${targetUnitId}"]`);

        if (!sourceUnitOption || !sourceUnitOption.dataset.conversionFactor ||
            !targetUnitOption || !targetUnitOption.dataset.conversionFactor) {
            estimatedTargetQtyDisplay.textContent = 'Facteur de conversion manquant';
            return;
        }

        const sourceFactor = parseFloat(sourceUnitOption.dataset.conversionFactor);
        const targetFactor = parseFloat(targetUnitOption.dataset.conversionFactor);

        if (targetFactor === 0) {
            estimatedTargetQtyDisplay.textContent = 'Facteur cible invalide (0)';
            return;
        }

        const quantityInBase = sourceQty * sourceFactor;
        const estimatedTargetQty = quantityInBase / targetFactor;

        estimatedTargetQtyDisplay.textContent = `${estimatedTargetQty.toFixed(3)} ${targetUnitOption.text.split(' -')[0]}`;
    }

    sourceProductSelect.addEventListener('change', function() {
        populateUnitSelect(this, sourceUnitSelect, '<?php echo htmlspecialchars($formData['source_unit_id'] ?? ''); ?>');
        // V1: Force target product to be same as source
        targetProductSelect.value = this.value;
        targetProductSelect.dispatchEvent(new Event('change')); // Trigger change for target unit select
    });

    targetProductSelect.addEventListener('change', function() { // Although forced, good to have if restriction is lifted
        populateUnitSelect(this, targetUnitSelect, '<?php echo htmlspecialchars($formData['target_unit_id'] ?? ''); ?>');
    });

    sourceUnitSelect.addEventListener('change', calculateEstimatedTargetQuantity);
    targetUnitSelect.addEventListener('change', calculateEstimatedTargetQuantity);
    sourceQuantityInput.addEventListener('input', calculateEstimatedTargetQuantity);


    // Initial population if form is re-rendered with data
    if (sourceProductSelect.value) {
        populateUnitSelect(sourceProductSelect, sourceUnitSelect, '<?php echo htmlspecialchars($formData['source_unit_id'] ?? ''); ?>');
    }
    if (targetProductSelect.value) { // Should be same as source for V1
        populateUnitSelect(targetProductSelect, targetUnitSelect, '<?php echo htmlspecialchars($formData['target_unit_id'] ?? ''); ?>');
    }

    // Ensure target product is synced if source is set on load
    if (sourceProductSelect.value && !targetProductSelect.value) {
        targetProductSelect.value = sourceProductSelect.value;
        targetProductSelect.dispatchEvent(new Event('change'));
    } else if (sourceProductSelect.value && targetProductSelect.value && sourceProductSelect.value != targetProductSelect.value){
        // If somehow they are different on load (e.g. error + old data), sync them.
         targetProductSelect.value = sourceProductSelect.value;
         targetProductSelect.dispatchEvent(new Event('change'));
    }

    calculateEstimatedTargetQuantity(); // Calculate on load if all fields are set

});
</script>

<style>
    fieldset { margin-bottom: 20px; border: 1px solid #ccc; padding: 15px; }
    legend { font-weight: bold; }
</style>
