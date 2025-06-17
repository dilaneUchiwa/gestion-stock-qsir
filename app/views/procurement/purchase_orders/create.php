<?php
// $title is set by controller
// $title = 'Créer un bon de commande';
?>

<h2>Créer un nouveau bon de commande</h2>

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

<form action="index.php?url=purchaseorder/store" method="POST" id="poForm">
    <fieldset>
        <legend>Détails de la commande</legend>
        <div class="form-group">
            <label for="supplier_id">Fournisseur *</label>
            <select name="supplier_id" id="supplier_id" required>
                <option value="">Sélectionnez le fournisseur</option>
                <?php foreach ($suppliers as $supplier): ?>
                    <option value="<?php echo htmlspecialchars($supplier['id']); ?>" <?php echo (isset($data['supplier_id']) && $data['supplier_id'] == $supplier['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($supplier['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="order_date">Date de commande *</label>
            <input type="date" name="order_date" id="order_date" value="<?php echo htmlspecialchars($data['order_date'] ?? date('Y-m-d')); ?>" required>
        </div>
        <div class="form-group">
            <label for="expected_delivery_date">Date de livraison prévue</label>
            <input type="date" name="expected_delivery_date" id="expected_delivery_date" value="<?php echo htmlspecialchars($data['expected_delivery_date'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="status">Statut</label>
            <select name="status" id="status">
                <?php
                $currentStatus = $data['status'] ?? 'pending';
                foreach ($allowedStatuses as $statusVal): ?>
                    <option value="<?php echo htmlspecialchars($statusVal); ?>" <?php echo ($currentStatus == $statusVal) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $statusVal))); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="notes">Remarques</label>
            <textarea name="notes" id="notes" rows="3"><?php echo htmlspecialchars($data['notes'] ?? ''); ?></textarea>
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
                // TODO: Controller needs to pass detailed product unit info for repopulation
                // For now, $units (all units) is used, and selected unit might not be perfectly repopulated if it's not the base unit
                $itemsToDisplay = $itemsData_form ?? [['product_id' => '', 'unit_id' => '', 'quantity_ordered' => 1, 'unit_price' => '0.00']];
                foreach ($itemsToDisplay as $idx => $item):
                ?>
                <tr class="item-row">
                    <td>
                        <select name="items[<?php echo $idx; ?>][product_id]" class="product-select" required data-index="<?php echo $idx; ?>">
                            <option value="">Sélectionnez le produit</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo htmlspecialchars($product['id']); ?>"
                                        data-price="<?php echo htmlspecialchars($product['purchase_price'] ?? '0.00'); ?>"
                                        data-base-unit-id="<?php echo htmlspecialchars($product['base_unit_id']); ?>"
                                        data-base-unit-name="<?php echo htmlspecialchars($product['base_unit_name'] . ' (' . $product['base_unit_symbol'] . ')'); ?>"
                                        <?php echo (isset($item['product_id']) && $item['product_id'] == $product['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($product['name']); ?> (Stock: <?php echo htmlspecialchars($product['quantity_in_stock'] ?? 0); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <select name="items[<?php echo $idx; ?>][unit_id]" class="unit-select" required data-index="<?php echo $idx; ?>" data-selected-unit-id="<?php echo htmlspecialchars($item['unit_id'] ?? ''); ?>">
                            <option value="">Sélectionnez un produit d'abord</option>
                            <?php
                            // Repopulation logic for unit_id if form is re-displayed with errors
                            // The actual population of options is handled by JS based on product selection.
                            // However, if a unit_id was previously selected for this item,
                            // the JS will use data-selected-unit-id to re-select it after populating options.
                            // So, we just need to ensure the select tag itself is here.
                            // If productUnitsMap and $item['product_id'] are available, we could pre-render options,
                            // but the JS will overwrite anyway. Simpler to let JS handle all option population.
                            ?>
                        </select>
                    </td>
                    <td><input type="number" name="items[<?php echo $idx; ?>][quantity_ordered]" class="quantity-input" value="<?php echo htmlspecialchars($item['quantity_ordered'] ?? '1'); ?>" min="1" step="any" required data-index="<?php echo $idx; ?>"></td>
                    <td><input type="number" name="items[<?php echo $idx; ?>][unit_price]" class="price-input" value="<?php echo htmlspecialchars($item['unit_price'] ?? '0.00'); ?>" min="0" step="0.01" required data-index="<?php echo $idx; ?>"></td>
                    <td><input type="text" class="subtotal-display" value="0.00" readonly tabindex="-1"></td>
                    <td><button type="button" class="remove-item-btn button-danger">Supprimer</button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <button type="button" id="addItemBtn" class="button">Ajouter un article</button>
        <div class="form-group" style="text-align:right; margin-top:10px;">
            <strong>Montant total de la commande: <span id="totalAmountDisplay">0.00</span> <?php echo APP_CURRENCY_SYMBOL; ?></strong>
        </div>
    </fieldset>

    <div class="form-group" style="margin-top: 20px;">
        <button type="submit" class="button">Créer le bon de commande</button>
        <a href="index.php?url=purchaseorder/index" class="button-info">Annuler</a>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const itemsTbody = document.getElementById('poItemsTbody');
    const addItemBtn = document.getElementById('addItemBtn');

    // productsData still contains basic product info like id, name, purchase_price, base_unit_id etc.
    // The new productUnitsMap provides the detailed units for each product.
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
    // const allUnitsData = <?php echo json_encode(array_map(function($u){ return ['id'=>$u['id'], 'name'=>$u['name'], 'symbol'=>$u['symbol']]; }, $units)); ?>; // Keep if needed as fallback

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

        productSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const price = selectedOption.dataset.price || '0.00';
            const baseUnitId = selectedOption.dataset.baseUnitId;
            const baseUnitName = selectedOption.dataset.baseUnitName;

            this.closest('.item-row').querySelector('.price-input').value = price;

            // Populate unit select
            unitSelect.innerHTML = ''; // Clear existing options

            const productId = this.value;
            const productEntry = productsData.find(p => p.id == productId);
            const unitsForProduct = productUnitsMap[productId] || [];
            const preSelectedUnitId = unitSelect.dataset.selectedUnitId; // Get value from data attribute

            unitSelect.innerHTML = ''; // Clear existing options

            if (productEntry && unitsForProduct.length > 0) {
                unitsForProduct.forEach(pu => {
                    const option = document.createElement('option');
                    option.value = pu.unit_id;
                    option.textContent = `${pu.name} (${pu.symbol})`;
                    unitSelect.appendChild(option);
                });

                // Attempt to re-select the previously selected unit, or default to base unit
                if (preSelectedUnitId && unitsForProduct.some(pu => pu.unit_id == preSelectedUnitId)) {
                    unitSelect.value = preSelectedUnitId;
                } else if (productEntry.base_unit_id && unitsForProduct.some(pu => pu.unit_id == productEntry.base_unit_id)) {
                    unitSelect.value = productEntry.base_unit_id;
                } else if (unitsForProduct.length > 0) {
                    // If base_unit_id isn't in the list for some reason, select the first one.
                    // This might happen if base_unit is not explicitly in product_units for that product (which it should be)
                    unitSelect.value = unitsForProduct[0].unit_id;
                }

            } else if (productEntry && productEntry.base_unit_id && productEntry.base_unit_name) {
                // Fallback if unitsForProduct is empty but product has a base unit defined
                const option = document.createElement('option');
                option.value = productEntry.base_unit_id;
                option.textContent = productEntry.base_unit_name;
                unitSelect.appendChild(option);
                unitSelect.value = productEntry.base_unit_id;
            } else {
                unitSelect.innerHTML = '<option value="">Aucune unité disponible</option>';
            }
            calculateTotalAmount();
        });

        // Trigger change on existing product selects to populate units and re-select if applicable
        // This is crucial for form repopulation on validation errors
        if (productSelect.value) {
            productSelect.dispatchEvent(new Event('change'));
        } else {
            // If no product is selected (e.g. an empty row from server-side for initial display)
            // ensure unit select is cleared or has a default placeholder
            unitSelect.innerHTML = '<option value="">Sélectionnez un produit d\'abord</option>';
        }


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
            // Update data-index as well if used by other parts of the script
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
                <select name="items[${itemIndex}][unit_id]" class="unit-select" required data-index="${itemIndex}">
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
        // calculateTotalAmount(); // Not strictly needed here as new row has 0 subtotal initially
    });

    // Initial setup for existing rows (e.g. on validation error)
    itemsTbody.querySelectorAll('.item-row').forEach(row => {
        addRowEventListeners(row);
        // Ensure unit dropdown is populated if product is already selected
        const productSelect = row.querySelector('.product-select');
        if (productSelect.value) {
            productSelect.dispatchEvent(new Event('change'));
        }
    });
    calculateTotalAmount(); // Initial calculation
});
</script>
