<?php
// $title is set by controller
// $title = 'Aperçu du stock';
?>

<h2>Aperçu du stock</h2>

<p>
    <a href="index.php?url=stock/create_adjustment" class="button">Créer un ajustement manuel</a>
</p>

<?php if (empty($products)): ?>
    <p>Aucun produit trouvé pour afficher le stock.</p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom du produit</th>
                <th style="text-align: right;">Stock (Unité de Base)</th>
                <th>Afficher en Unité</th>
                <th style="text-align: right;">Quantité Convertie</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
            <tr class="stock-row" data-product-id="<?php echo htmlspecialchars($product['id']); ?>">
                <td><?php echo htmlspecialchars($product['id']); ?></td>
                <td><?php echo htmlspecialchars($product['name']); ?></td>
                <td style="text-align: right;" class="base-stock-display" data-base-quantity="<?php echo htmlspecialchars($product['quantity_in_stock']); ?>">
                    <?php echo htmlspecialchars($product['quantity_in_stock']); ?> <?php echo htmlspecialchars($product['base_unit_symbol'] ?? ''); ?>
                </td>
                <td>
                    <?php $unitsForThisProduct = $productUnitsMap[$product['id']] ?? []; ?>
                    <?php if (!empty($unitsForThisProduct)): ?>
                    <select class="unit-select-stock form-control" style="width: auto; display: inline-block; padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                        <?php foreach ($unitsForThisProduct as $unit): ?>
                            <option value="<?php echo htmlspecialchars($unit['unit_id']); ?>"
                                    data-conversion-factor="<?php echo htmlspecialchars((float)$unit['conversion_factor_to_base_unit']); ?>"
                                    data-symbol="<?php echo htmlspecialchars($unit['symbol']); ?>"
                                    <?php echo ($unit['unit_id'] == $product['base_unit_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($unit['name'] . ' (' . $unit['symbol'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php else: echo 'N/A'; endif; ?>
                </td>
                <td style="text-align: right;" class="converted-stock-display">-</td>
                <td>
                    <a href="index.php?url=stock/history/<?php echo $product['id']; ?>" class="button-info">Voir l'historique</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<p><small>Note : "Stock (Unité de Base)" est la valeur stockée directement avec le produit, mise à jour par les transactions.</small></p>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.stock-row').forEach(function(row) {
        const baseStockDisplay = row.querySelector('.base-stock-display');
        const unitSelect = row.querySelector('.unit-select-stock');
        const convertedStockDisplay = row.querySelector('.converted-stock-display');

        if (!baseStockDisplay || !unitSelect || !convertedStockDisplay) {
            // console.warn('Missing elements in a stock row for product ID:', row.dataset.productId);
            return;
        }

        const baseQuantity = parseFloat(baseStockDisplay.dataset.baseQuantity);

        function updateConvertedStockForRow() {
            const selectedOption = unitSelect.options[unitSelect.selectedIndex];
            if (!selectedOption) {
                convertedStockDisplay.textContent = 'N/A';
                return;
            }
            const conversionFactor = parseFloat(selectedOption.dataset.conversionFactor);
            const unitSymbol = selectedOption.dataset.symbol;

            if (isNaN(baseQuantity) || isNaN(conversionFactor)) {
                convertedStockDisplay.textContent = 'Données invalides';
                return;
            }

            if (conversionFactor === 0) {
                convertedStockDisplay.textContent = 'Facteur invalide (0)';
                return;
            }

            const convertedQuantity = baseQuantity / conversionFactor;
            convertedStockDisplay.textContent = convertedQuantity.toFixed(3).replace(/\.?0+$/, '') + ' ' + unitSymbol;
        }

        // Initial display for each row
        updateConvertedStockForRow();

        // Update on change for each row
        unitSelect.addEventListener('change', updateConvertedStockForRow);
    });
});
</script>
