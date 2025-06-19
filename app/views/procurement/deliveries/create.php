<?php
// $title set by controller
// $title = $poId ? "Créer une livraison pour le BC-{$poId}" : 'Créer une livraison directe';
?>

<h2><?php echo $title; ?></h2>

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

<form action="index.php?url=delivery/store" method="POST" id="deliveryForm">
    <fieldset>
        <legend>Détails de la livraison</legend>
        <?php if ($purchaseOrder): ?>
            <input type="hidden" name="purchase_order_id" value="<?php echo htmlspecialchars($purchaseOrder['id']); ?>">
            <p><strong>Réception pour le bon de commande : BC-<?php echo htmlspecialchars($purchaseOrder['id']); ?></strong></p>
            <p>Fournisseur : <?php echo htmlspecialchars($purchaseOrder['supplier_name']); ?></p>
             <input type="hidden" name="supplier_id" value="<?php echo htmlspecialchars($purchaseOrder['supplier_id']); ?>">
        <?php else: ?>
            <div class="form-group">
                <label for="supplier_id">Fournisseur * (pour livraison directe)</label>
                <select name="supplier_id" id="supplier_id" required>
                    <option value="">Sélectionnez le fournisseur</option>
                    <?php foreach ($suppliers as $supplier): ?>
                        <option value="<?php echo htmlspecialchars($supplier['id']); ?>" <?php echo (isset($data['supplier_id']) && $data['supplier_id'] == $supplier['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($supplier['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="delivery_date">Date de livraison *</label>
            <input type="date" name="delivery_date" id="delivery_date" value="<?php echo htmlspecialchars($data['delivery_date'] ?? date('Y-m-d')); ?>" required>
        </div>
        <div class="form-group">
            <label for="type">Type de livraison *</label>
            <select disabled name="type" id="type" required>
                <?php
                $currentType = $data['type'] ?? ($purchaseOrder ? 'purchase' : 'purchase'); // Default for PO is purchase
                foreach ($allowedDeliveryTypes as $typeVal): ?>
                    <option value="<?php echo htmlspecialchars($typeVal); ?>" <?php echo ($currentType == $typeVal) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $typeVal))); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
         <div class="form-group">
            <label for="is_partial">
                <input type="checkbox" name="is_partial" id="is_partial" value="1" <?php echo (isset($data['is_partial']) && $data['is_partial']) ? 'checked' : ''; ?>>
                Est-ce une livraison partielle ? (Marquer manuellement si connu)
            </label>
        </div>
        <div class="form-group">
            <label for="notes">Remarques</label>
            <textarea name="notes" id="notes" rows="3"><?php echo htmlspecialchars($data['notes'] ?? ''); ?></textarea>
        </div>
    </fieldset>

    <fieldset>
        <legend>Articles reçus *</legend>
        <table class="table" id="deliveryItemsTable">
            <thead>
                <tr>
                    <th>Produit *</th>
                    <?php if ($purchaseOrder): ?>
                        <th>Commandé (BC)</th>
                        <th>Déjà reçu (BC)</th>
                        <th>Restant (BC)</th>
                    <?php endif; ?>
                    <th>Unité (Réception) *</th>
                    <th>Quantité reçue *</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="deliveryItemsTbody">
                <?php
                $itemsToProcess = $formItemsData ?? $poItems ?? [];
                if (empty($itemsToProcess) && !$purchaseOrder) {
                    $itemsToProcess = [['product_id' => '', 'unit_id' => '', 'quantity_received' => 1]];
                }

                foreach ($itemsToProcess as $idx => $item):
                    $productId = $item['product_id'] ?? null;
                    $poItemId = $item['id'] ?? ($item['purchase_order_item_id'] ?? null);
                    $currentUnitId = $item['unit_id'] ?? null; // unit_id from PO item or form repopulation
                    $currentUnitName = $item['unit_name'] ?? ''; // from PO item
                    $currentUnitSymbol = $item['unit_symbol'] ?? ''; // from PO item

                    $productDetails = null; // Full product details from $products array
                    if ($productId) {
                        foreach($products as $p) { if($p['id'] == $productId) { $productDetails = $p; break; } }
                    }
                ?>
                <tr class="item-row">
                    <td>
                        <input type="hidden" name="items[<?php echo $idx; ?>][purchase_order_item_id]" value="<?php echo htmlspecialchars($poItemId); ?>">
                        <?php if ($purchaseOrder && $productId && $productDetails): ?>
                            <input type="hidden" name="items[<?php echo $idx; ?>][product_id]" value="<?php echo htmlspecialchars($productId); ?>">
                            <span><?php echo htmlspecialchars($productDetails['name']); ?></span>
                        <?php else: ?>
                            <select name="items[<?php echo $idx; ?>][product_id]" class="product-select" required data-index="<?php echo $idx; ?>">
                                <option value="">Sélectionnez le produit</option>
                                <?php foreach ($products as $p): ?>
                                    <option value="<?php echo htmlspecialchars($p['id']); ?>"
                                            data-base-unit-id="<?php echo htmlspecialchars($p['base_unit_id']); ?>"
                                            data-base-unit-name="<?php echo htmlspecialchars(($p['base_unit_name'] ?? 'N/A') . ' (' . ($p['base_unit_symbol'] ?? '') . ')'); ?>"
                                            <?php echo ($productId == $p['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($p['name']); ?> (Stock: <?php echo htmlspecialchars($p['quantity_in_stock'] ?? 0); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </td>

                    <?php if ($purchaseOrder): ?>
                        <td class="text-right"><?php echo htmlspecialchars($item['quantity_ordered'] ?? 'N/A'); ?> <?php echo htmlspecialchars($item['unit_symbol'] ?? ''); ?></td>
                        <td class="text-right"><?php echo htmlspecialchars($item['quantity_already_received'] ?? '0'); ?> <?php echo htmlspecialchars($item['unit_symbol'] ?? ''); ?></td>
                        <td class="text-right"><?php echo htmlspecialchars($item['quantity_pending'] ?? $item['quantity_ordered'] ?? 'N/A'); ?> <?php echo htmlspecialchars($item['unit_symbol'] ?? ''); ?></td>
                    <?php endif; ?>

                    <td> <!-- Unité (Réception) Column -->
                        <select name="items[<?php echo $idx; ?>][unit_id]" class="unit-select reception-unit-select" required data-index="<?php echo $idx; ?>" data-product-id="<?php echo htmlspecialchars($productId); ?>" data-selected-unit-id="<?php echo htmlspecialchars($currentUnitId); /* This is the PO's unit_id or form repopulation */ ?>">
                            <option value="">Chargement...</option>
                            <?php
                            // JavaScript will primarily handle populating this.
                            // For initial load with PO, JS will find this select, see data-product-id and data-selected-unit-id,
                            // then populate it with units from productUnitsMap[productId] and select the data-selected-unit-id.
                            // For direct delivery (non-PO), the existing product-select change handler will call populateUnitSelect.
                            ?>
                        </select>
                    </td>
                    <td>
                        <input type="number" name="items[<?php echo $idx; ?>][quantity_received]" class="quantity-input"
                               value="<?php echo htmlspecialchars($item['quantity_received'] ?? ($item['quantity_pending'] ?? 1)); ?>"
                               min="0" step="any" <?php // Allow 0 for initial state if PO item is fully received but row is still shown ?>
                               <?php if(isset($item['quantity_pending'])): ?>max="<?php echo htmlspecialchars($item['quantity_pending']); ?>"<?php endif; ?>
                               required data-index="<?php echo $idx; ?>">
                    </td>
                    <td>
                        <?php if (!$purchaseOrder || !empty($item['can_be_removed'])): // Allow removing for direct deliveries or non-PO items, or specifically flagged items ?>
                        <button type="button" class="remove-item-btn button-danger btn-sm">Supprimer</button>
                        <?php elseif ($purchaseOrder && $productId) : echo "Verrouillé (BC)"; endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (!$purchaseOrder): // Allow adding items only for direct deliveries ?>
        <button type="button" id="addItemBtn" class="button">Ajouter un autre produit</button>
        <?php endif; ?>
    </fieldset>

    <div class="form-group" style="margin-top: 20px;">
        <button type="submit" class="button">Enregistrer la livraison</button>
        <a href="index.php?url=delivery/index" class="button-info">Annuler</a>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const itemsTbody = document.getElementById('deliveryItemsTbody');
    const addItemBtn = document.getElementById('addItemBtn');
    const productsData = <?php echo json_encode(array_map(function($p){
        return [ // This is the basic product data for the product dropdown
            'id' => $p['id'],
            'name' => $p['name'],
            'base_unit_id' => $p['base_unit_id'] ?? null,
            'base_unit_name' => ($p['base_unit_name'] ?? 'N/A') . ' (' . ($p['base_unit_symbol'] ?? '') . ')',
            'quantity_in_stock' => $p['quantity_in_stock'] ?? 0
        ];
    }, $products)); ?>;
    const productUnitsMap = <?php echo json_encode($productUnitsMap ?? []); ?>; // Map of [productId => [units...]]
    let itemIndex = itemsTbody.querySelectorAll('.item-row').length;
    const isPoBased = <?php echo $purchaseOrder ? 'true' : 'false'; ?>;

    function populateUnitSelect(unitSelectElement, productId, selectedUnitId = null) {
        // console.log("Populating unit select for product:", productId, "selected unit:", selectedUnitId, "element:", unitSelectElement);
        unitSelectElement.innerHTML = '<option value="">Chargement...</option>';
        const productEntry = productsData.find(p => p.id == productId); // Find basic info
        const unitsForProduct = productUnitsMap[productId] || []; // Get specific units

        unitSelectElement.innerHTML = ''; // Clear loading/previous options

        if (unitsForProduct.length > 0) {
            unitsForProduct.forEach(pu => {
                const option = document.createElement('option');
                option.value = pu.unit_id; // unit_id from product_units join
                option.textContent = `${pu.name} (${pu.symbol})`;
                // conversion_factor_to_base_unit is in pu.conversion_factor_to_base_unit
                if (selectedUnitId && pu.unit_id == selectedUnitId) {
                    option.selected = true;
                } else if (!selectedUnitId && productEntry && pu.unit_id == productEntry.base_unit_id) {
                    option.selected = true; // Default to product's base unit if no specific preselection
                }
                unitSelectElement.appendChild(option);
            });
        } else if (productEntry && productEntry.base_unit_id && productEntry.base_unit_name) {
            // Fallback: if productUnitsMap somehow missed this product, but we know its base unit from productsData
            const option = document.createElement('option');
            option.value = productEntry.base_unit_id;
            option.textContent = productEntry.base_unit_name;
            option.selected = true;
            unitSelectElement.appendChild(option);
        } else {
            unitSelectElement.innerHTML = '<option value="">Aucune unité configurée</option>';
        }
    }

    function addRowEventListeners(row) {
        const productSelect = row.querySelector('.product-select'); // Might be null for PO-based rows if product is static text
        const unitSelect = row.querySelector('.unit-select.reception-unit-select'); // Target the specific select

        if (unitSelect) { // Ensure unitSelect exists
            if (productSelect && !isPoBased) { // For direct delivery, product can change
                productSelect.addEventListener('change', function() {
                    const newProductId = this.value;
                    populateUnitSelect(unitSelect, newProductId); // Default to base unit of new product
                });

                // Initial population for direct delivery rows if product is already selected (e.g. validation error)
                if (productSelect.value) {
                    const initialSelectedUnitId = unitSelect.dataset.selectedUnitId || null;
                    populateUnitSelect(unitSelect, productSelect.value, initialSelectedUnitId);
                }
            } else if (isPoBased) {
                // For PO-based rows, product is fixed. We already have productId from data-attribute.
                // This is handled by the initial loop over .reception-unit-select below.
                // No change listener needed for productSelect here as it's typically not there or is readonly.
            }
        }
    }

    // Initial population for all reception unit selects on page load
    // This is crucial for both PO-based items and direct delivery items with pre-filled/error data.
    document.querySelectorAll('.unit-select.reception-unit-select').forEach(select => {
        const productId = select.dataset.productId;
        const selectedUnitId = select.dataset.selectedUnitId;
        if (productId) {
            populateUnitSelect(select, productId, selectedUnitId);
        } else {
            // If no product ID (e.g. empty row for direct delivery), set a default state
            select.innerHTML = '<option value="">Sélectionnez produit d\'abord</option>';
        }
    });

    function updateItemIndices() {
        let currentIdx = 0;
        itemsTbody.querySelectorAll('.item-row').forEach(row => {
            // Update names for all input fields within the row that are part of the items array
            row.querySelectorAll('[name^="items["]').forEach(input => {
                const oldName = input.name;
                const newName = oldName.replace(/items\[\d+\]/, `items[${currentIdx}]`);
                input.name = newName;
            });
            currentIdx++;
        });
        itemIndex = currentIdx;
    }

    if (addItemBtn) { // Only add this listener if the button exists (i.e., not PO-based fixed list)
        addItemBtn.addEventListener('click', function() {
            const newRow = document.createElement('tr');
            newRow.classList.add('item-row');
            const productOptionsHTML = productsData.map(p =>
                `<option value="${p.id}"
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
                        <option value="">Sélectionnez produit</option>
                    </select>
                </td>
                <td><input type="number" name="items[${itemIndex}][quantity_received]" class="quantity-input" value="1" min="1" required data-index="${itemIndex}"></td>
                <td><button type="button" class="remove-item-btn button-danger btn-sm">Supprimer</button></td>
            `;
            itemsTbody.appendChild(newRow);
            addRowEventListeners(newRow);

            // Attach remove listener specifically for these new rows (if not PO based)
            newRow.querySelector('.remove-item-btn').addEventListener('click', function() {
                // For direct deliveries, ensure at least one item if it's the last one.
                // This logic might need refinement based on whether the form starts empty or with one row.
                // if (!isPoBased && itemsTbody.querySelectorAll('.item-row').length <= 1) {
                //    alert('Une livraison directe doit contenir au moins un article.'); return;
                // }
                this.closest('.item-row').remove();
                updateItemIndices();
            });
            itemIndex++;
        });
    }

    // Initial setup for event listeners on rows (mostly for direct delivery product changes and remove buttons)
    itemsTbody.querySelectorAll('.item-row').forEach(row => {
        addRowEventListeners(row); // Sets up product change listener for direct delivery

        // Add remove listener for any pre-existing removable rows
        const removeBtn = row.querySelector('.remove-item-btn');
        if (removeBtn) {
            // Logic for enabling/disabling or handling remove based on isPoBased or other conditions
            if (!isPoBased) { // Standard remove for direct delivery items
                 removeBtn.addEventListener('click', function() {
                    this.closest('.item-row').remove();
                    updateItemIndices();
                });
            } else {
                // For PO-based, removal might be conditional (e.g. if quantity_pending is 0)
                // For now, if button is there, it works. PHP controls presence of button.
                 if(this.closest('.item-row').querySelector('input[name*="[product_id]"]')) { // Check if it's a PO row with a product
                    // No general remove for PO items unless specifically allowed by PHP
                 }
            }
        }
    });
});
</script>
