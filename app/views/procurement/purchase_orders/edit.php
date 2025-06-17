<?php
// $title is set by controller
// $title = 'Modifier le bon de commande';
?>

<h2>Modifier le bon de commande #BC-<?php echo htmlspecialchars($purchaseOrder['id']); ?></h2>

<?php if (empty($purchaseOrder)): ?>
    <p>Données du bon de commande non trouvées pour la modification.</p>
    <a href="index.php?url=purchaseorder/index" class="button-info">Retour à la liste</a>
    <?php return; ?>
<?php endif; ?>


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

<form action="index.php?url=purchaseorder/update/<?php echo $purchaseOrder['id']; ?>" method="POST" id="poForm">
    <fieldset>
        <legend>Détails de la commande</legend>
        <div class="form-group">
            <label for="supplier_id">Fournisseur *</label>
            <select name="supplier_id" id="supplier_id" required>
                <option value="">Sélectionnez le fournisseur</option>
                <?php foreach ($suppliers as $supplier): ?>
                    <option value="<?php echo htmlspecialchars($supplier['id']); ?>" <?php echo ($purchaseOrder['supplier_id'] == $supplier['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($supplier['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="order_date">Date de commande *</label>
            <input type="date" name="order_date" id="order_date" value="<?php echo htmlspecialchars($purchaseOrder['order_date']); ?>" required>
        </div>
        <div class="form-group">
            <label for="expected_delivery_date">Date de livraison prévue</label>
            <input type="date" name="expected_delivery_date" id="expected_delivery_date" value="<?php echo htmlspecialchars($purchaseOrder['expected_delivery_date'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="status">Statut</label>
            <select name="status" id="status">
                <?php foreach ($allowedStatuses as $statusVal): ?>
                    <option value="<?php echo htmlspecialchars($statusVal); ?>" <?php echo ($purchaseOrder['status'] == $statusVal) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $statusVal))); ?>
                    </option>
                <?php endforeach; ?>
            </select>
             <small>Note : Le statut peut être automatiquement mis à jour par les livraisons.</small>
        </div>
        <div class="form-group">
            <label for="notes">Remarques</label>
            <textarea name="notes" id="notes" rows="3"><?php echo htmlspecialchars($purchaseOrder['notes'] ?? ''); ?></textarea>
        </div>
    </fieldset>

    <fieldset>
        <legend>Articles de la commande *</legend>
        <table class="table" id="poItemsTable">
            <thead>
                <tr>
                    <th>Produit *</th>
                    <th>Unité *</th>
                    <th>Quantité *</th>
                    <th>Prix unitaire *</th>
                    <th>Sous-total</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="poItemsTbody">
                <?php
                // $itemsToDisplay should come from $purchaseOrder['items'] if available, or $itemsData_form if repopulating after error
                $itemsToDisplay = $itemsData_form ?? ($purchaseOrder['items'] ?? []);
                if (empty($itemsToDisplay)) { // Ensure at least one row for JS if PO was empty or no items submitted
                    $itemsToDisplay = [['product_id' => '', 'unit_id' => '', 'quantity_ordered' => 1, 'unit_price' => '0.00']];
                }

                foreach ($itemsToDisplay as $idx => $item):
                    $currentProductId = $item['product_id'] ?? null;
                    $currentUnitId = $item['unit_id'] ?? null; // This should be populated from model now
                ?>
                <tr class="item-row">
                    <td>
                        <select name="items[<?php echo $idx; ?>][product_id]" class="product-select" required data-index="<?php echo $idx; ?>">
                            <option value="">Sélectionnez le produit</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo htmlspecialchars($product['id']); ?>"
                                        data-price="<?php echo htmlspecialchars($product['purchase_price'] ?? '0.00'); ?>"
                                        data-base-unit-id="<?php echo htmlspecialchars($product['base_unit_id']); ?>"
                                        data-base-unit-name="<?php echo htmlspecialchars(($product['base_unit_name'] ?? 'N/A') . ' (' . ($product['base_unit_symbol'] ?? '') . ')'); ?>"
                                        <?php echo ($currentProductId == $product['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($product['name']); ?> (Stock: <?php echo htmlspecialchars($product['quantity_in_stock'] ?? 0); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <select name="items[<?php echo $idx; ?>][unit_id]" class="unit-select" required data-index="<?php echo $idx; ?>" data-selected-unit-id="<?php echo htmlspecialchars($currentUnitId); ?>">
                            <option value="">Sélectionnez d'abord un produit</option>
                            <?php /* Options will be populated by JavaScript based on product selection */ ?>
                            <?php /* For repopulation: if $currentProductId and $currentUnitId exist, JS will handle selection. */ ?>
                            <?php /* If $currentProductId is set, JS's 'change' event on product select will populate this. */?>
                        </select>
                    </td>
                    <td><input type="number" name="items[<?php echo $idx; ?>][quantity_ordered]" class="quantity-input" value="<?php echo htmlspecialchars($item['quantity_ordered'] ?? '1'); ?>" min="1" required data-index="<?php echo $idx; ?>"></td>
                    <td><input type="number" name="items[<?php echo $idx; ?>][unit_price]" class="price-input" value="<?php echo htmlspecialchars($item['unit_price'] ?? '0.00'); ?>" min="0" step="0.01" required data-index="<?php echo $idx; ?>"></td>
                    <td><input type="text" class="subtotal-display" value="<?php echo htmlspecialchars(isset($item['sub_total']) ? number_format((float)$item['sub_total'], 2, '.', '') : '0.00'); ?>" readonly tabindex="-1"></td>
                    <td><button type="button" class="remove-item-btn button-danger">Supprimer</button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <button type="button" id="addItemBtn" class="button">Ajouter un article</button>
        <div class="form-group" style="text-align:right; margin-top:10px;">
            <strong>Montant total de la commande: <span id="totalAmountDisplay"><?php echo htmlspecialchars(number_format($purchaseOrder['total_amount'], 2)); ?></span></strong>
        </div>
    </fieldset>

    <div class="form-group" style="margin-top: 20px;">
        <button type="submit" class="button">Mettre à jour le bon de commande</button>
        <a href="index.php?url=purchaseorder/show/<?php echo $purchaseOrder['id']; ?>" class="button-info">Annuler</a>
    </div>
</form>

<script>
// Same JavaScript as in create.php for dynamic item rows, with minor adjustments for edit
document.addEventListener('DOMContentLoaded', function () {
    const itemsTbody = document.getElementById('poItemsTbody');
    const addItemBtn = document.getElementById('addItemBtn');

    const productsData = <?php echo json_encode(array_map(function($p){
        return [
            'id' => $p['id'],
            'name' => $p['name'],
            'purchase_price' => $p['purchase_price'] ?? '0.00',
            'quantity_in_stock' => $p['quantity_in_stock'] ?? 0,
            'base_unit_id' => $p['base_unit_id'] ?? null,
            'base_unit_name' => ($p['base_unit_name'] ?? 'N/A') . ' (' . ($p['base_unit_symbol'] ?? '') . ')'
        ];
    }, $products)); ?>;

    const productUnitsMap = <?php echo json_encode($productUnitsMap ?? []); ?>;
    // const allUnitsData = <?php echo json_encode(array_map(function($u){ return ['id'=>$u['id'], 'name'=>$u['name'], 'symbol'=>$u['symbol']]; }, $units)); ?>; // Fallback
    let itemIndex = itemsTbody.querySelectorAll('.item-row').length;

    function calculateRowSubtotal(row) {
        const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
        const price = parseFloat(row.querySelector('.price-input').value) || 0;
        const subtotal = quantity * price;
        row.querySelector('.subtotal-display').value = subtotal.toFixed(2);
        return subtotal;
    }

    function calculateTotalAmount() {
        let total = 0;
        itemsTbody.querySelectorAll('.item-row').forEach(row => {
            total += calculateRowSubtotal(row);
        });
        document.getElementById('totalAmountDisplay').textContent = total.toFixed(2);
    }

    function addRowEventListeners(row) {
        row.querySelector('.quantity-input').addEventListener('input', () => calculateTotalAmount());
        row.querySelector('.price-input').addEventListener('input', () => calculateTotalAmount());

        const productSelect = row.querySelector('.product-select');
        const unitSelect = row.querySelector('.unit-select');
        const initiallySelectedUnitId = unitSelect.dataset.selectedUnitId; // Get pre-selected unit_id from PHP

        productSelect.addEventListener('change', function(event) {
            const selectedOption = this.options[this.selectedIndex];
            const price = selectedOption.dataset.price || '0.00';
            const baseUnitId = selectedOption.dataset.baseUnitId;

            this.closest('.item-row').querySelector('.price-input').value = price;

            unitSelect.innerHTML = '';

            const productId = this.value;
            const productEntry = productsData.find(p => p.id == productId);
            const unitsForProduct = productUnitsMap[productId] || [];

            if (productEntry) {
                if (unitsForProduct.length > 0) {
                    unitsForProduct.forEach(pu => {
                        const option = document.createElement('option');
                        option.value = pu.unit_id;
                        option.textContent = `${pu.name} (${pu.symbol})`;
                        unitSelect.appendChild(option);
                    });

                    // Pre-selection logic
                    if (event && event.type === 'change' && event.isTrusted) { // User changed the product
                        unitSelect.value = productEntry.base_unit_id || unitsForProduct[0].unit_id; // Default to base or first
                    } else if (initiallySelectedUnitId && unitsForProduct.some(u => u.unit_id == initiallySelectedUnitId)) {
                        unitSelect.value = initiallySelectedUnitId; // Pre-select saved/form_repopulation value
                    } else if (productEntry.base_unit_id) { // Fallback to base unit if no specific preselection matches
                        unitSelect.value = productEntry.base_unit_id;
                    } else if (unitsForProduct.length > 0) { // Fallback to the first available unit
                        unitSelect.value = unitsForProduct[0].unit_id;
                    }

                } else {
                     if (productEntry.base_unit_id && productEntry.base_unit_name) {
                         const option = document.createElement('option');
                         option.value = productEntry.base_unit_id;
                         option.textContent = productEntry.base_unit_name;
                         option.selected = true;
                         unitSelect.appendChild(option);
                    } else {
                        unitSelect.innerHTML = '<option value="">Aucune unité configurée</option>';
                    }
                }
            } else {
                 unitSelect.innerHTML = '<option value="">Sélectionnez un produit</option>';
            }
            if (event && event.type === 'change' && event.isTrusted) {
                 calculateTotalAmount();
            }
        });

        row.querySelector('.remove-item-btn').addEventListener('click', function() {
            if (itemsTbody.querySelectorAll('.item-row').length > 1) {
                this.closest('.item-row').remove();
                calculateTotalAmount();
                updateItemIndices();
            } else {
                alert('Un bon de commande doit contenir au moins un article.');
            }
        });
    }

    function updateItemIndices() {
        let currentIdx = 0;
        itemsTbody.querySelectorAll('.item-row').forEach(row => {
            row.querySelector('.product-select').name = `items[${currentIdx}][product_id]`;
            row.querySelector('.unit-select').name = `items[${currentIdx}][unit_id]`;
            row.querySelector('.quantity-input').name = `items[${currentIdx}][quantity_ordered]`;
            row.querySelector('.price-input').name = `items[${currentIdx}][unit_price]`;
            row.querySelectorAll('[data-index]').forEach(el => el.dataset.index = currentIdx);
            currentIdx++;
        });
        itemIndex = currentIdx;
    }

    addItemBtn.addEventListener('click', function() {
        const newRow = document.createElement('tr');
        newRow.classList.add('item-row');
        let productOptionsHTML = productsData.map(p =>
            `<option value="${p.id}"
                     data-price="${p.purchase_price}"
                     data-base-unit-id="${p.base_unit_id}"
                     data-base-unit-name="${p.base_unit_name}">
                 ${p.name} (Stock: ${p.quantity_in_stock})
             </option>`
        ).join('');

        newRow.innerHTML = `
            <td>
                <select name="items[${itemIndex}][product_id]" class="product-select" required data-index="${itemIndex}">
                    <option value="">Sélectionnez le produit</option>
                    ${productOptionsHTML}
                </select>
            </td>
            <td>
                <select name="items[${itemIndex}][unit_id]" class="unit-select" required data-index="${itemIndex}" data-selected-unit-id="">
                    <option value="">Sélectionnez d'abord un produit</option>
                </select>
            </td>
            <td><input type="number" name="items[${itemIndex}][quantity_ordered]" class="quantity-input" value="1" min="1" required data-index="${itemIndex}"></td>
            <td><input type="number" name="items[${itemIndex}][unit_price]" class="price-input" value="0.00" min="0" step="0.01" required data-index="${itemIndex}"></td>
            <td><input type="text" class="subtotal-display" value="0.00" readonly tabindex="-1"></td>
            <td><button type="button" class="remove-item-btn button-danger">Supprimer</button></td>
        `;
        itemsTbody.appendChild(newRow);
        addRowEventListeners(newRow);
        itemIndex++;
    });

    // Initial setup for existing rows
    itemsTbody.querySelectorAll('.item-row').forEach(row => {
        addRowEventListeners(row);
        const productSelect = row.querySelector('.product-select');
        if (productSelect.value) { // If a product is already selected
            productSelect.dispatchEvent(new Event('change', { bubbles: false })); // Trigger change to populate units, 'bubbles:false' for some scenarios or 'isTrusted:false' might be implied.
        }
    });
    // Total amount is pre-filled by PHP, JS recalculates on interaction.
});
</script>
