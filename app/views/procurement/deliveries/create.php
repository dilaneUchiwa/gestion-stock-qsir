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
                    <th>Quantité reçue *</th>
                    <?php if ($purchaseOrder): ?>
                        <th>Commandé à l'origine</th>
                        <th>Déjà reçu</th>
                        <th>En attente</th>
                    <?php endif; ?>
                    <th>Unité de mesure</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="deliveryItemsTbody">
                <?php
                $itemsToProcess = $formItemsData ?? $poItems ?? []; // $formItemsData for repopulation, $poItems for PO load
                if (empty($itemsToProcess) && !$purchaseOrder) { // For direct delivery, start with one empty row
                    $itemsToProcess = [['product_id' => '', 'quantity_received' => 1]];
                }

                foreach ($itemsToProcess as $idx => $item):
                    $productId = $item['product_id'] ?? null;
                    $poItemId = $item['id'] ?? ($item['purchase_order_item_id'] ?? null); // if from PO, $item['id'] is po_item_id
                    $productDetails = null;
                    foreach($products as $p) { if($p['id'] == $productId) $productDetails = $p; break;}
                ?>
                <tr class="item-row">
                    <td>
                        <input type="hidden" name="items[<?php echo $idx; ?>][purchase_order_item_id]" value="<?php echo htmlspecialchars($poItemId); ?>">
                        <select name="items[<?php echo $idx; ?>][product_id]" class="product-select" required data-index="<?php echo $idx; ?>" <?php echo ($purchaseOrder && $productId) ? 'readonly' : ''; ?>>
                            <option value="">Sélectionnez le produit</option>
                            <?php foreach ($products as $p): ?>
                                <option value="<?php echo htmlspecialchars($p['id']); ?>"
                                        data-unit="<?php echo htmlspecialchars($p['unit_of_measure'] ?? ''); ?>"
                                        <?php echo ($productId == $p['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($p['name']); ?> (Stock: <?php echo htmlspecialchars($p['quantity_in_stock'] ?? 0); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                         <?php if ($purchaseOrder && $productId && $productDetails): ?>
                            <input type="hidden" name="items[<?php echo $idx; ?>][product_id]" value="<?php echo htmlspecialchars($productId); ?>">
                            <span><?php echo htmlspecialchars($productDetails['name']); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <input type="number" name="items[<?php echo $idx; ?>][quantity_received]" class="quantity-input"
                               value="<?php echo htmlspecialchars($item['quantity_received'] ?? ($item['quantity_pending'] ?? 1)); ?>"
                               min="1"
                               <?php if(isset($item['quantity_pending'])): ?>max="<?php echo htmlspecialchars($item['quantity_pending']); ?>"<?php endif; ?>
                               required data-index="<?php echo $idx; ?>">
                    </td>
                    <?php if ($purchaseOrder): ?>
                        <td><?php echo htmlspecialchars($item['quantity_ordered'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($item['quantity_already_received'] ?? '0'); ?></td>
                        <td><?php echo htmlspecialchars($item['quantity_pending'] ?? $item['quantity_ordered'] ?? 'N/A'); ?></td>
                    <?php endif; ?>
                    <td class="unit-display"><?php echo htmlspecialchars($productDetails['unit_of_measure'] ?? ($item['unit_of_measure'] ?? '')); ?></td>
                    <td>
                        <?php if (!$purchaseOrder): // Allow removing only for direct deliveries or non-PO items ?>
                        <button type="button" class="remove-item-btn button-danger">Supprimer</button>
                        <?php else: echo "Verrouillé (depuis BC)"; endif; ?>
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
    const addItemBtn = document.getElementById('addItemBtn'); // Might be null if PO based
    const productsData = <?php echo json_encode(array_map(function($p){ return ['id'=>$p['id'], 'name'=>$p['name'], 'unit_of_measure'=>$p['unit_of_measure'] ?? '', 'quantity_in_stock'=>$p['quantity_in_stock'] ?? 0]; }, $products)); ?>;
    let itemIndex = itemsTbody.querySelectorAll('.item-row').length;
    const isPoBased = <?php echo $purchaseOrder ? 'true' : 'false'; ?>;

    function addRowEventListeners(row) {
        const productSelect = row.querySelector('.product-select');
        if (productSelect) { // productSelect might be null if it's hidden for PO based items
            productSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const unit = selectedOption.dataset.unit || '';
                const unitDisplay = this.closest('.item-row').querySelector('.unit-display');
                if(unitDisplay) unitDisplay.textContent = unit;
            });
        }
    }

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
            newRow.innerHTML = `
                <td>
                    <select name="items[${itemIndex}][product_id]" class="product-select" required data-index="${itemIndex}">
                        <option value="">Sélectionnez le produit</option>
                        ${productsData.map(p => `<option value="${p.id}" data-unit="${p.unit_of_measure}">${p.name} (Stock: ${p.quantity_in_stock})</option>`).join('')}
                    </select>
                </td>
                <td><input type="number" name="items[${itemIndex}][quantity_received]" class="quantity-input" value="1" min="1" required data-index="${itemIndex}"></td>
                <td class="unit-display"></td>
                <td><button type="button" class="remove-item-btn button-danger">Supprimer</button></td>
            `;
            itemsTbody.appendChild(newRow);
            addRowEventListeners(newRow);
            newRow.querySelector('.remove-item-btn').addEventListener('click', function() {
                if (itemsTbody.querySelectorAll('.item-row').length > 1) {
                    this.closest('.item-row').remove();
                    updateItemIndices();
                } else {
                    alert('Une livraison doit contenir au moins un article.');
                }
            });
            itemIndex++;
        });
    }

    // Initial setup for existing rows
    itemsTbody.querySelectorAll('.item-row').forEach(row => {
        addRowEventListeners(row);
        // Set initial unit display for direct deliveries if product is pre-selected (e.g. form repopulation)
        if (!isPoBased) {
            const productSelect = row.querySelector('.product-select');
            if(productSelect && productSelect.value) {
                 const selectedOption = productSelect.options[productSelect.selectedIndex];
                 const unitDisplay = row.querySelector('.unit-display');
                 if (selectedOption && unitDisplay) unitDisplay.textContent = selectedOption.dataset.unit || '';
            }
        }
        // Add remove listener for direct delivery rows that might be pre-populated on error
        if (!isPoBased && row.querySelector('.remove-item-btn')) {
             row.querySelector('.remove-item-btn').addEventListener('click', function() {
                if (itemsTbody.querySelectorAll('.item-row').length > 1) {
                    this.closest('.item-row').remove();
                    updateItemIndices();
                } else {
                    alert('Une livraison doit contenir au moins un article.');
                }
            });
        }
    });
});
</script>
