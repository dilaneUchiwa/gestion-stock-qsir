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
                    <th>Quantité *</th>
                    <th>Prix unitaire *</th>
                    <th>Sous-total</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="poItemsTbody">
                <?php
                $itemsToDisplay = $itemsData_form ?? [['product_id' => '', 'quantity_ordered' => 1, 'unit_price' => '0.00']]; // Start with one empty row if no form data
                foreach ($itemsToDisplay as $idx => $item):
                ?>
                <tr class="item-row">
                    <td>
                        <select name="items[<?php echo $idx; ?>][product_id]" class="product-select" required data-index="<?php echo $idx; ?>">
                            <option value="">Sélectionnez le produit</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo htmlspecialchars($product['id']); ?>"
                                        data-price="<?php echo htmlspecialchars($product['purchase_price'] ?? '0.00'); ?>"
                                        <?php echo (isset($item['product_id']) && $item['product_id'] == $product['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($product['name']); ?> (Stock: <?php echo htmlspecialchars($product['quantity_in_stock'] ?? 0); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input type="number" name="items[<?php echo $idx; ?>][quantity_ordered]" class="quantity-input" value="<?php echo htmlspecialchars($item['quantity_ordered'] ?? '1'); ?>" min="1" required data-index="<?php echo $idx; ?>"></td>
                    <td><input type="number" name="items[<?php echo $idx; ?>][unit_price]" class="price-input" value="<?php echo htmlspecialchars($item['unit_price'] ?? '0.00'); ?>" min="0" step="0.01" required data-index="<?php echo $idx; ?>"></td>
                    <td><input type="text" class="subtotal-display" value="0.00" readonly tabindex="-1"></td>
                    <td><button type="button" class="remove-item-btn button-danger">Supprimer</button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <button type="button" id="addItemBtn" class="button">Ajouter un article</button>
        <div class="form-group" style="text-align:right; margin-top:10px;">
            <strong>Montant total de la commande: <span id="totalAmountDisplay">0.00</span></strong>
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
    const productsData = <?php echo json_encode(array_map(function($p){ return ['id'=>$p['id'], 'name'=>$p['name'], 'purchase_price'=>$p['purchase_price'] ?? '0.00', 'quantity_in_stock'=>$p['quantity_in_stock'] ?? 0]; }, $products)); ?>;
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
        row.querySelector('.product-select').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const price = selectedOption.dataset.price || '0.00';
            const priceInput = this.closest('.item-row').querySelector('.price-input');
            priceInput.value = price;
            calculateTotalAmount();
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
            row.querySelector('.quantity-input').name = `items[${currentIdx}][quantity_ordered]`;
            row.querySelector('.price-input').name = `items[${currentIdx}][unit_price]`;
            currentIdx++;
        });
        itemIndex = currentIdx; // Reset global itemIndex
    }


    addItemBtn.addEventListener('click', function() {
        const newRow = document.createElement('tr');
        newRow.classList.add('item-row');
        newRow.innerHTML = `
            <td>
                <select name="items[${itemIndex}][product_id]" class="product-select" required data-index="${itemIndex}">
                    <option value="">Sélectionnez le produit</option>
                    ${productsData.map(p => `<option value="${p.id}" data-price="${p.purchase_price}">${p.name} (Stock: ${p.quantity_in_stock})</option>`).join('')}
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
        calculateTotalAmount(); // Recalculate when a new empty row is added
    });

    // Initial setup for existing rows
    itemsTbody.querySelectorAll('.item-row').forEach(row => {
        addRowEventListeners(row);
    });
    calculateTotalAmount(); // Initial calculation
});
</script>
