<?php
// $title set by controller
// $title = 'Nouvelle vente (paiement immédiat)';
?>

<h2>Nouvelle vente (paiement immédiat)</h2>

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

<form action="index.php?url=sale/store" method="POST" id="saleForm">
    <input type="hidden" name="payment_type" value="immediate">
    <input type="hidden" name="payment_status" value="paid"> <!-- For immediate, default to paid -->

    <fieldset>
        <legend>Client et date</legend>
        <div class="form-group">
            <label for="client_id">Client enregistré (facultatif)</label>
            <select name="client_id" id="client_id">
                <option value="">Sélectionner un client enregistré</option>
                <?php foreach ($clients as $client): ?>
                    <option value="<?php echo htmlspecialchars($client['id']); ?>" <?php echo (isset($data['client_id']) && $data['client_id'] == $client['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($client['name']); ?> (<?php echo htmlspecialchars($client['email'] ?? 'Pas d\'email'); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <small>Si non sélectionné, saisissez le nom du client occasionnel ci-dessous.</small>
        </div>
        <div class="form-group">
            <label for="client_name_occasional">Nom du client occasionnel</label>
            <input type="text" name="client_name_occasional" id="client_name_occasional" value="<?php echo htmlspecialchars($data['client_name_occasional'] ?? ''); ?>">
            <small>Requis si aucun client enregistré n'est sélectionné.</small>
        </div>
        <div class="form-group">
            <label for="sale_date">Date de la vente *</label>
            <input type="date" name="sale_date" id="sale_date" value="<?php echo htmlspecialchars($data['sale_date'] ?? date('Y-m-d')); ?>" required>
        </div>
    </fieldset>

    <fieldset>
        <legend>Articles de la vente *</legend>
        <table class="table" id="saleItemsTable">
            <thead>
                <tr>
                    <th>Produit *</th>
                    <th>Quantité *</th>
                    <th>Prix unitaire *</th>
                    <th>Sous-total</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="saleItemsTbody">
                <?php
                $itemsToDisplay = $formItemsData ?? [['product_id' => '', 'quantity_sold' => 1, 'unit_price' => '0.00']];
                foreach ($itemsToDisplay as $idx => $item):
                ?>
                <tr class="item-row">
                    <td>
                        <select name="items[<?php echo $idx; ?>][product_id]" class="product-select" required data-index="<?php echo $idx; ?>">
                            <option value="">Sélectionner un produit</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo htmlspecialchars($product['id']); ?>"
                                        data-price="<?php echo htmlspecialchars($product['selling_price'] ?? '0.00'); ?>"
                                        data-stock="<?php echo htmlspecialchars($product['quantity_in_stock'] ?? '0'); ?>"
                                        <?php echo (isset($item['product_id']) && $item['product_id'] == $product['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($product['name']); ?> (Stock : <?php echo htmlspecialchars($product['quantity_in_stock'] ?? 0); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input type="number" name="items[<?php echo $idx; ?>][quantity_sold]" class="quantity-input" value="<?php echo htmlspecialchars($item['quantity_sold'] ?? '1'); ?>" min="1" required data-index="<?php echo $idx; ?>"></td>
                    <td><input type="number" name="items[<?php echo $idx; ?>][unit_price]" class="price-input" value="<?php echo htmlspecialchars($item['unit_price'] ?? '0.00'); ?>" min="0" step="0.01" required data-index="<?php echo $idx; ?>"></td>
                    <td><input type="text" class="subtotal-display" value="0.00" readonly tabindex="-1"></td>
                    <td><button type="button" class="remove-item-btn button-danger">Retirer</button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <button type="button" id="addItemBtn" class="button">Ajouter un article</button>
        <div class="form-group" style="text-align:right; margin-top:10px;">
            <strong>Montant total de la vente : <span id="totalAmountDisplay">0.00</span></strong>
        </div>
    </fieldset>

    <div class="form-group">
        <label for="notes">Remarques</label>
        <textarea name="notes" id="notes" rows="3"><?php echo htmlspecialchars($data['notes'] ?? ''); ?></textarea>
    </div>

    <div class="form-group" style="margin-top: 20px;">
        <button type="submit" class="button">Enregistrer la vente (payée)</button>
        <a href="index.php?url=sale/index" class="button-info">Annuler</a>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const itemsTbody = document.getElementById('saleItemsTbody');
    const addItemBtn = document.getElementById('addItemBtn');
    const productsData = <?php echo json_encode(array_map(function($p){ return ['id'=>$p['id'], 'name'=>$p['name'], 'selling_price'=>$p['selling_price'] ?? '0.00', 'quantity_in_stock'=>$p['quantity_in_stock'] ?? 0]; }, $products)); ?>;
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
        const quantityInput = row.querySelector('.quantity-input');
        const productSelect = row.querySelector('.product-select');

        quantityInput.addEventListener('input', () => calculateTotalAmount());
        row.querySelector('.price-input').addEventListener('input', () => calculateTotalAmount());

        productSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const price = selectedOption.dataset.price || '0.00';
            const stock = parseInt(selectedOption.dataset.stock || '0');

            const priceInput = this.closest('.item-row').querySelector('.price-input');
            priceInput.value = price;

            // Set max for quantity input based on stock
            quantityInput.max = stock;
            if (parseInt(quantityInput.value) > stock) {
                quantityInput.value = stock; // Adjust if current value exceeds new max
                if(stock === 0) quantityInput.value = 0; // or 1 if min is 1 and stock is 0
            }
            if (stock === 0 && quantityInput.value === "0"){
                 alert(`Le produit "${selectedOption.text.split(' (Stock:')[0]}" est en rupture de stock.`);
            }

            calculateTotalAmount();
        });
        row.querySelector('.remove-item-btn').addEventListener('click', function() {
            if (itemsTbody.querySelectorAll('.item-row').length > 1) {
                this.closest('.item-row').remove();
                calculateTotalAmount();
                updateItemIndices();
            } else {
                alert('Une vente doit contenir au moins un article.');
            }
        });
        // Trigger change on load for pre-filled rows to set price and max stock
        if (productSelect.value) {
            productSelect.dispatchEvent(new Event('change'));
        }
    }

    function updateItemIndices() {
        let currentIdx = 0;
        itemsTbody.querySelectorAll('.item-row').forEach(row => {
            row.querySelector('.product-select').name = `items[${currentIdx}][product_id]`;
            row.querySelector('.quantity-input').name = `items[${currentIdx}][quantity_sold]`;
            row.querySelector('.price-input').name = `items[${currentIdx}][unit_price]`;
            currentIdx++;
        });
        itemIndex = currentIdx;
    }

    addItemBtn.addEventListener('click', function() {
        const newRow = document.createElement('tr');
        newRow.classList.add('item-row');
        newRow.innerHTML = `
            <td>
                <select name="items[${itemIndex}][product_id]" class="product-select" required data-index="${itemIndex}">
                    <option value="">Sélectionner un produit</option>
                    ${productsData.map(p => `<option value="${p.id}" data-price="${p.selling_price}" data-stock="${p.quantity_in_stock}">${p.name} (Stock : ${p.quantity_in_stock})</option>`).join('')}
                </select>
            </td>
            <td><input type="number" name="items[${itemIndex}][quantity_sold]" class="quantity-input" value="1" min="1" required data-index="${itemIndex}"></td>
            <td><input type="number" name="items[${itemIndex}][unit_price]" class="price-input" value="0.00" min="0" step="0.01" required data-index="${itemIndex}"></td>
            <td><input type="text" class="subtotal-display" value="0.00" readonly tabindex="-1"></td>
            <td><button type="button" class="remove-item-btn button-danger">Retirer</button></td>
        `;
        itemsTbody.appendChild(newRow);
        addRowEventListeners(newRow);
        itemIndex++;
        // calculateTotalAmount(); // Not needed here as new rows are empty initially
    });

    itemsTbody.querySelectorAll('.item-row').forEach(row => {
        addRowEventListeners(row);
    });
    calculateTotalAmount(); // Initial calculation for pre-filled or error-repopulated forms
});
</script>
