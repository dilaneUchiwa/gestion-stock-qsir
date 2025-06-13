<?php
// $title set by controller
// $title = 'New Sale (Deferred Payment)';
?>

<h2>New Sale (Deferred Payment)</h2>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <p><strong>Please correct the following errors:</strong></p>
        <ul>
            <?php foreach ($errors as $field => $error): ?>
                <li><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $field))); ?>: <?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form action="index.php?url=sale/store" method="POST" id="saleForm">
    <input type="hidden" name="payment_type" value="deferred">
    <!-- payment_status will default to 'pending' or can be set explicitly -->

    <fieldset>
        <legend>Client & Date</legend>
        <div class="form-group">
            <label for="client_id">Registered Client (Optional)</label>
            <select name="client_id" id="client_id">
                <option value="">Select Registered Client</option>
                <?php foreach ($clients as $client): ?>
                    <option value="<?php echo htmlspecialchars($client['id']); ?>" <?php echo (isset($data['client_id']) && $data['client_id'] == $client['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($client['name']); ?> (<?php echo htmlspecialchars($client['email'] ?? 'No Email'); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="client_name_occasional">Occasional Client Name</label>
            <input type="text" name="client_name_occasional" id="client_name_occasional" value="<?php echo htmlspecialchars($data['client_name_occasional'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="sale_date">Sale Date *</label>
            <input type="date" name="sale_date" id="sale_date" value="<?php echo htmlspecialchars($data['sale_date'] ?? date('Y-m-d')); ?>" required>
        </div>
        <div class="form-group">
            <label for="due_date">Payment Due Date *</label>
            <input type="date" name="due_date" id="due_date" value="<?php echo htmlspecialchars($data['due_date'] ?? ''); ?>" required>
        </div>
         <div class="form-group">
            <label for="payment_status">Initial Payment Status</label>
            <select name="payment_status" id="payment_status">
                <?php
                $currentStatus = $data['payment_status'] ?? 'pending'; // Default to pending for deferred
                // Allow setting to pending or partially_paid initially for deferred. 'paid' would be unusual for deferred at creation.
                $deferredInitialStatuses = array_filter($allowedPaymentStatuses, fn($s) => in_array($s, ['pending', 'partially_paid']));
                foreach ($deferredInitialStatuses as $statusVal): ?>
                    <option value="<?php echo htmlspecialchars($statusVal); ?>" <?php echo ($currentStatus == $statusVal) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $statusVal))); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </fieldset>

    <fieldset>
        <legend>Sale Items *</legend>
        <table class="table" id="saleItemsTable">
            <thead>
                <tr>
                    <th>Product *</th>
                    <th>Quantity *</th>
                    <th>Unit Price *</th>
                    <th>Subtotal</th>
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
                            <option value="">Select Product</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo htmlspecialchars($product['id']); ?>"
                                        data-price="<?php echo htmlspecialchars($product['selling_price'] ?? '0.00'); ?>"
                                        data-stock="<?php echo htmlspecialchars($product['quantity_in_stock'] ?? '0'); ?>"
                                        <?php echo (isset($item['product_id']) && $item['product_id'] == $product['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($product['name']); ?> (Stock: <?php echo htmlspecialchars($product['quantity_in_stock'] ?? 0); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input type="number" name="items[<?php echo $idx; ?>][quantity_sold]" class="quantity-input" value="<?php echo htmlspecialchars($item['quantity_sold'] ?? '1'); ?>" min="1" required data-index="<?php echo $idx; ?>"></td>
                    <td><input type="number" name="items[<?php echo $idx; ?>][unit_price]" class="price-input" value="<?php echo htmlspecialchars($item['unit_price'] ?? '0.00'); ?>" min="0" step="0.01" required data-index="<?php echo $idx; ?>"></td>
                    <td><input type="text" class="subtotal-display" value="0.00" readonly tabindex="-1"></td>
                    <td><button type="button" class="remove-item-btn button-danger">Remove</button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <button type="button" id="addItemBtn" class="button">Add Item</button>
        <div class="form-group" style="text-align:right; margin-top:10px;">
            <strong>Total Sale Amount: <span id="totalAmountDisplay">0.00</span></strong>
        </div>
    </fieldset>

    <div class="form-group">
        <label for="notes">Notes</label>
        <textarea name="notes" id="notes" rows="3"><?php echo htmlspecialchars($data['notes'] ?? ''); ?></textarea>
    </div>

    <div class="form-group" style="margin-top: 20px;">
        <button type="submit" class="button">Create Sale (Deferred Payment)</button>
        <a href="index.php?url=sale/index" class="button-info">Cancel</a>
    </div>
</form>

<script>
// Same JavaScript as in create_immediate.php for dynamic item rows
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

            quantityInput.max = stock;
             if (parseInt(quantityInput.value) > stock) {
                quantityInput.value = stock;
                 if(stock === 0) quantityInput.value = 0;
            }
             if (stock === 0 && quantityInput.value === "0"){
                 alert(`Product "${selectedOption.text.split(' (Stock:')[0]}" is out of stock.`);
            }
            calculateTotalAmount();
        });
        row.querySelector('.remove-item-btn').addEventListener('click', function() {
            if (itemsTbody.querySelectorAll('.item-row').length > 1) {
                this.closest('.item-row').remove();
                calculateTotalAmount();
                updateItemIndices();
            } else {
                alert('A sale must have at least one item.');
            }
        });
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
                    <option value="">Select Product</option>
                    ${productsData.map(p => `<option value="${p.id}" data-price="${p.selling_price}" data-stock="${p.quantity_in_stock}">${p.name} (Stock: ${p.quantity_in_stock})</option>`).join('')}
                </select>
            </td>
            <td><input type="number" name="items[${itemIndex}][quantity_sold]" class="quantity-input" value="1" min="1" required data-index="${itemIndex}"></td>
            <td><input type="number" name="items[${itemIndex}][unit_price]" class="price-input" value="0.00" min="0" step="0.01" required data-index="${itemIndex}"></td>
            <td><input type="text" class="subtotal-display" value="0.00" readonly tabindex="-1"></td>
            <td><button type="button" class="remove-item-btn button-danger">Remove</button></td>
        `;
        itemsTbody.appendChild(newRow);
        addRowEventListeners(newRow);
        itemIndex++;
    });

    itemsTbody.querySelectorAll('.item-row').forEach(row => {
        addRowEventListeners(row);
    });
    calculateTotalAmount();
});
</script>
