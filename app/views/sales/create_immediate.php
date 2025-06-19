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
                    <th>Unité *</th>
                    <th>Quantité *</th>
                    <th>Prix unitaire *</th>
                    <th>Sous-total</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="saleItemsTbody">
                <?php
                $itemsToDisplay = $formItemsData ?? [['product_id' => '', 'unit_id' => '', 'quantity_sold' => 1, 'unit_price' => '0.00']];
                foreach ($itemsToDisplay as $idx => $item):
                    $currentProductId = $item['product_id'] ?? null;
                    $currentUnitId = $item['unit_id'] ?? null;
                ?>
                <tr class="item-row">
                    <td>
                        <select name="items[<?php echo $idx; ?>][product_id]" class="product-select" required data-index="<?php echo $idx; ?>">
                            <option value="">Sélectionner un produit</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo htmlspecialchars($product['id']); ?>"
                                        data-price="<?php echo htmlspecialchars($product['selling_price'] ?? '0.00'); ?>"
                                        data-stock-base="<?php echo htmlspecialchars($product['quantity_in_stock'] ?? '0'); ?>"
                                        data-base-unit-id="<?php echo htmlspecialchars($product['base_unit_id']); ?>"
                                        data-base-unit-symbol="<?php echo htmlspecialchars($product['base_unit_symbol'] ?? ''); ?>"
                                        <?php echo ($currentProductId == $product['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="stock-display" style="display: block; margin-top: 5px;">Stock: -</small>
                    </td>
                    <td>
                        <select name="items[<?php echo $idx; ?>][unit_id]" class="unit-select" required data-index="<?php echo $idx; ?>" data-selected-unit-id="<?php echo htmlspecialchars($currentUnitId); ?>">
                            <option value="">Sélectionnez produit</option>
                            <?php /* JS populates this */ ?>
                        </select>
                    </td>
                    <td><input type="number" name="items[<?php echo $idx; ?>][quantity_sold]" class="quantity-input" value="<?php echo htmlspecialchars($item['quantity_sold'] ?? '1'); ?>" min="0.001" step="any" required data-index="<?php echo $idx; ?>"></td>
                    <td><input type="number" name="items[<?php echo $idx; ?>][unit_price]" class="price-input" value="<?php echo htmlspecialchars($item['unit_price'] ?? '0.00'); ?>" min="0" step="0.01" required data-index="<?php echo $idx; ?>" readonly></td>
                    <td><input type="text" class="subtotal-display" value="0.00" readonly tabindex="-1"></td>
                    <td><button type="button" class="remove-item-btn button-danger btn-sm">Retirer</button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <button type="button" id="addItemBtn" class="button">Ajouter un article</button>

        <div style="margin-top: 20px; padding-top:10px; border-top: 1px solid #ccc;">
            <div class="form-group">
                <label for="grossTotalDisplayLabel" style="font-weight:bold;">Sous-Total Brut des Articles :</label>
                <span id="grossTotalDisplay" style="font-weight:bold;">0.00</span> <?php echo APP_CURRENCY_SYMBOL; ?>
            </div>
            <div class="form-group">
                <label for="discount_amount">Montant de la Réduction :</label>
                <input type="number" name="discount_amount" id="discount_amount" value="<?php echo htmlspecialchars($data['discount_amount'] ?? '0.00'); ?>" min="0" step="0.01" style="width:100px; text-align:right;"> <?php echo APP_CURRENCY_SYMBOL; ?>
            </div>
            <div class="form-group">
                <label for="netTotalDisplayLabel" style="font-weight:bold; color: #28a745;">Total Net à Payer :</label>
                <span id="netTotalDisplay" style="font-weight:bold; color: #28a745; font-size: 1.2em;">0.00</span> <?php echo APP_CURRENCY_SYMBOL; ?>
            </div>
             <hr>
            <div class="form-group">
                <label for="amount_tendered">Montant Versé par le Client * :</label>
                <input type="number" name="amount_tendered" id="amount_tendered" value="<?php echo htmlspecialchars($data['amount_tendered'] ?? ''); ?>" min="0" step="0.01" required style="width:100px; text-align:right;"> <?php echo APP_CURRENCY_SYMBOL; ?>
            </div>
            <div class="form-group">
                <label for="changeDueDisplayLabel" style="font-weight:bold;">Monnaie à Rendre :</label>
                <span id="changeDueDisplay" style="font-weight:bold; font-size: 1.1em;">0.00</span> <?php echo APP_CURRENCY_SYMBOL; ?>
            </div>
        </div>
    </fieldset>

    <div class="form-group">
        <label for="notes">Remarques</label>
        <textarea name="notes" id="notes" rows="3"><?php echo htmlspecialchars($data['notes'] ?? ''); ?></textarea>
    </div>

    <div class="form-group" style="margin-top: 20px;">
        <button type="submit" class="button button-success">Enregistrer la vente (payée)</button>
        <a href="index.php?url=sale/index" class="button-info">Annuler</a>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const itemsTbody = document.getElementById('saleItemsTbody');
    const addItemBtn = document.getElementById('addItemBtn');
    // productsData already includes base_unit_id, base_unit_symbol, quantity_in_stock (base)
    const productsData = <?php echo json_encode($products); ?>;
    const productUnitsMap = <?php echo json_encode($productUnitsMap ?? []); ?>;

    const discountInput = document.getElementById('discount_amount');
    const amountTenderedInput = document.getElementById('amount_tendered');
    const grossTotalDisplaySpan = document.getElementById('grossTotalDisplay');
    const netTotalDisplaySpan = document.getElementById('netTotalDisplay');
    const changeDueDisplaySpan = document.getElementById('changeDueDisplay');
    // const totalAmountDisplaySpan = document.getElementById('totalAmountDisplay'); // This was the old total, now netTotalDisplay is primary

    let itemIndex = itemsTbody.querySelectorAll('.item-row').length;

    function calculateRowSubtotal(row) {
        const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
        const price = parseFloat(row.querySelector('.price-input').value) || 0;
        const subtotal = quantity * price;
        row.querySelector('.subtotal-display').value = subtotal.toFixed(2);
        return subtotal;
    }

    function calculateAllTotals() {
        let grossTotal = 0;
        itemsTbody.querySelectorAll('.item-row').forEach(row => {
            grossTotal += calculateRowSubtotal(row); // Ensure row subtotal is calculated first
        });
        grossTotalDisplaySpan.textContent = grossTotal.toFixed(2);

        const discount = parseFloat(discountInput.value) || 0;
        if (discount < 0) { // Prevent negative discount in display calculation
            discountInput.value = '0.00';
        }

        let netTotal = grossTotal - discount;
        if (netTotal < 0) netTotal = 0; // Amount to pay cannot be negative
        netTotalDisplaySpan.textContent = netTotal.toFixed(2);
        // totalAmountDisplaySpan.textContent = netTotal.toFixed(2); // Update old total if still used, or remove it

        const amountTendered = parseFloat(amountTenderedInput.value) || 0;
        let changeDue = amountTendered - netTotal;

        if (amountTendered < netTotal && amountTendered > 0) { // only show as negative if some amount is tendered but not enough
             changeDueDisplaySpan.innerHTML = `<span style="color:red;">Montant insuffisant: ${changeDue.toFixed(2)}</span>`;
        } else if (amountTendered >= netTotal) {
             changeDueDisplaySpan.innerHTML = `<span style="color:green;">${changeDue.toFixed(2)}</span>`;
        } else { // amount tendered is 0 or invalid
            changeDueDisplaySpan.textContent = '0.00';
        }
    }

    // Initial calculation on page load
    calculateAllTotals();

    discountInput.addEventListener('input', calculateAllTotals);
    amountTenderedInput.addEventListener('input', calculateAllTotals);


    function addRowEventListeners(row) {
        const quantityInput = row.querySelector('.quantity-input');
        const productSelect = row.querySelector('.product-select');
        const unitSelect = row.querySelector('.unit-select');
        const stockDisplay = row.querySelector('.stock-display');
        const priceInput = row.querySelector('.price-input');

        function updateStockDisplayAndMaxQty() {
            const selectedProductId = productSelect.value;
            const selectedUnitId = unitSelect.value;
            const product = productsData.find(p => p.id == selectedProductId);

            if (product && selectedUnitId) {
                const stockBase = parseFloat(product.quantity_in_stock);
                const unitsForProd = productUnitsMap[selectedProductId] || [];
                const selectedUnitInfo = unitsForProd.find(u => u.unit_id == selectedUnitId);

                if (selectedUnitInfo && selectedUnitInfo.conversion_factor_to_base_unit && parseFloat(selectedUnitInfo.conversion_factor_to_base_unit) > 0) {
                    const factor = parseFloat(selectedUnitInfo.conversion_factor_to_base_unit);
                    const stockInSelectedUnit = stockBase / factor;
                    stockDisplay.textContent = `Stock: ${stockInSelectedUnit.toFixed(3)} ${selectedUnitInfo.symbol}`;
                    quantityInput.max = stockInSelectedUnit.toFixed(3); // Allow fractional quantities
                     if (parseFloat(quantityInput.value) > stockInSelectedUnit) {
                        // quantityInput.value = stockInSelectedUnit.toFixed(3); // Adjust if current value exceeds new max
                    }
                    if (stockInSelectedUnit <= 0) {
                         // alert(`Le produit "${product.name}" est en rupture de stock dans l'unité "${selectedUnitInfo.name}".`);
                    }
                } else {
                    stockDisplay.textContent = `Stock: N/A (err conv.)`;
                    quantityInput.removeAttribute('max');
                }
            } else {
                stockDisplay.textContent = 'Stock: -';
                quantityInput.removeAttribute('max');
            }
        }

        quantityInput.addEventListener('input', () => {
            updateStockDisplayAndMaxQty();
            calculateAllTotals(); // Use new central calculation
        });
        priceInput.addEventListener('input', () => calculateAllTotals()); // Use new central calculation

        productSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const price = selectedOption.dataset.price || '0.00';
            priceInput.value = price;

            const productId = this.value;
            const productEntry = productsData.find(p => p.id == productId);
            const unitsForProduct = productUnitsMap[productId] || [];

            unitSelect.innerHTML = '<option value="">Choisir unité</option>';
            if (productEntry) {
                if (unitsForProduct.length > 0) {
                    unitsForProduct.forEach(pu => {
                        const option = document.createElement('option');
                        option.value = pu.unit_id;
                        option.textContent = `${pu.name} (${pu.symbol})`;
                        option.dataset.conversionFactor = pu.conversion_factor_to_base_unit; // Ensure this is correctly assigned
                        if (productEntry.base_unit_id == pu.unit_id) {
                            option.selected = true;
                        }
                        unitSelect.appendChild(option);
                    });
                } else if (productEntry.base_unit_id && productEntry.base_unit_symbol) { // Check base_unit_symbol for consistency
                     const option = document.createElement('option');
                     option.value = productEntry.base_unit_id;
                     // Use base_unit_name and base_unit_symbol from product data if available, forming consistent text
                     let baseUnitText = productEntry.base_unit_name || 'Unité de Base'; // Fallback name
                     if (productEntry.base_unit_symbol) {
                         baseUnitText += ` (${productEntry.base_unit_symbol})`;
                     }
                     option.textContent = baseUnitText;
                     option.dataset.conversionFactor = "1.00000"; // Base unit factor is always 1
                     option.selected = true;
                     unitSelect.appendChild(option);
                } else {
                     unitSelect.innerHTML = '<option value="">Pas d\'unités</option>';
                }
            }
            unitSelect.dispatchEvent(new Event('change'));
            calculateAllTotals(); // Use new central calculation
        });

        unitSelect.addEventListener('change', function() {
            updateStockDisplayAndMaxQty();

            const selectedProductOption = productSelect.options[productSelect.selectedIndex];
            const baseProductPrice = parseFloat(selectedProductOption.dataset.price);
            const selectedUnitOption = this.options[this.selectedIndex];

            if (selectedUnitOption && selectedUnitOption.dataset.conversionFactor) { // Check if selectedUnitOption and its dataset exist
                const conversionFactor = parseFloat(selectedUnitOption.dataset.conversionFactor);

                if (!isNaN(baseProductPrice) && !isNaN(conversionFactor) && conversionFactor > 0) {
                    const newUnitPrice = baseProductPrice * conversionFactor;
                    priceInput.value = newUnitPrice.toFixed(2);
                } else if (!isNaN(baseProductPrice) && selectedUnitOption.value == selectedProductOption.dataset.baseUnitId) {
                    // Fallback for base unit if conversion factor somehow missing or invalid, price should be base price
                    priceInput.value = baseProductPrice.toFixed(2);
                }
                // If conversionFactor is invalid or not found, price might remain as set by product change (base price)
                // or from a previous valid unit selection. This behavior might need refinement if strict error handling is required.
            } else if (selectedProductOption.dataset.baseUnitId === this.value && !isNaN(baseProductPrice)) {
                 // Handles case where selected unit is the base unit, and its specific option might be missing conversion factor
                 // (e.g. if populated by the fallback `else if (productEntry.base_unit_id && productEntry.base_unit_name)` block)
                 priceInput.value = baseProductPrice.toFixed(2);
            }


            calculateAllTotals(); // Recalculate totals after price change
        });

        row.querySelector('.remove-item-btn').addEventListener('click', function() {
            if (itemsTbody.querySelectorAll('.item-row').length > 1) {
                this.closest('.item-row').remove();
            } else {
                productSelect.value = "";
                unitSelect.innerHTML = '<option value="">Sélectionnez produit</option>';
                quantityInput.value = "1";
                priceInput.value = "0.00";
                stockDisplay.textContent = "Stock: -";
            }
            calculateAllTotals(); // Use new central calculation
            updateItemIndices();
        });

        // Initial population for existing rows (e.g., form repopulation)
        if (productSelect.value) {
            productSelect.dispatchEvent(new Event('change')); // Populate units
            // If unit was also pre-selected (e.g. from $formItemsData), set it and trigger its change
            const preSelectedUnitId = unitSelect.dataset.selectedUnitId;
            if(preSelectedUnitId){
                // Wait for unitSelect to be populated by productSelect change event
                setTimeout(() => {
                    unitSelect.value = preSelectedUnitId;
                    unitSelect.dispatchEvent(new Event('change'));
                }, 0);
            }
        } else {
             stockDisplay.textContent = "Stock: -"; // Default for empty row
        }
    }

    function updateItemIndices() {
        let currentIdx = 0;
        itemsTbody.querySelectorAll('.item-row').forEach(row => {
            row.querySelector('.product-select').name = `items[${currentIdx}][product_id]`;
            row.querySelector('.unit-select').name = `items[${currentIdx}][unit_id]`;
            row.querySelector('.quantity-input').name = `items[${currentIdx}][quantity_sold]`;
            row.querySelector('.price-input').name = `items[${currentIdx}][unit_price]`;
            currentIdx++;
        });
        itemIndex = currentIdx;
    }

    addItemBtn.addEventListener('click', function() {
        const newRow = document.createElement('tr');
        newRow.classList.add('item-row');
        let productOptionsHTML = productsData.map(p =>
            `<option value="${p.id}" data-price="${p.selling_price}" data-stock-base="${p.quantity_in_stock}" data-base-unit-id="${p.base_unit_id}" data-base-unit-symbol="${p.base_unit_symbol}">${p.name}</option>`
        ).join('');

        newRow.innerHTML = `
            <td>
                <select name="items[${itemIndex}][product_id]" class="product-select" required data-index="${itemIndex}">
                    <option value="">Sélectionner un produit</option>
                    ${productOptionsHTML}
                </select>
                <small class="stock-display" style="display: block; margin-top: 5px;">Stock: -</small>
            </td>
            <td>
                <select name="items[${itemIndex}][unit_id]" class="unit-select" required data-index="${itemIndex}">
                    <option value="">Sélectionnez produit</option>
                </select>
            </td>
            <td><input type="number" name="items[${itemIndex}][quantity_sold]" class="quantity-input" value="1" min="0.001" step="any" required data-index="${itemIndex}"></td>
            <td><input type="number" name="items[${itemIndex}][unit_price]" class="price-input" value="0.00" min="0" step="0.01" required data-index="${itemIndex}" readonly></td>
            <td><input type="text" class="subtotal-display" value="0.00" readonly tabindex="-1"></td>
            <td><button type="button" class="remove-item-btn button-danger btn-sm">Retirer</button></td>
        `;
        itemsTbody.appendChild(newRow);
        addRowEventListeners(newRow);
        itemIndex++;
    });

    itemsTbody.querySelectorAll('.item-row').forEach(row => {
        addRowEventListeners(row);
    });
    // calculateAllTotals(); // Already called at the end of DOMContentLoaded

    // Client selection logic
    const clientIdSelect = document.getElementById('client_id');
    const clientNameOccasionalInput = document.getElementById('client_name_occasional');

    function toggleClientNameOccasional() {
        if (clientIdSelect.value) {
            // A registered client is selected
            clientNameOccasionalInput.disabled = true;
            clientNameOccasionalInput.value = ''; // Optionally clear the value
            // Consider adding a class to visually indicate disabled state if not default browser styling
        } else {
            // No registered client selected ("Sélectionner un client enregistré")
            clientNameOccasionalInput.disabled = false;
        }
    }

    // Initial state on page load
    toggleClientNameOccasional();

    // Event listener for changes
    clientIdSelect.addEventListener('change', toggleClientNameOccasional);
});
</script>
