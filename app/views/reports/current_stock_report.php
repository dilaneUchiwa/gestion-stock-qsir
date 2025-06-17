<?php
// $title set by controller
?>

<h2><?php echo htmlspecialchars($title); ?></h2>

<div style="margin-bottom: 20px;">
    <a href="index.php?url=report/index" class="button-info">Retour à l'index des rapports</a>
</div>

<form method="GET" action="index.php">
    <input type="hidden" name="url" value="report/current_stock">
    <fieldset style="margin-bottom: 20px;">
        <legend>Filtres</legend>
        <div class="form-group" style="display:inline-block; margin-right:10px;">
            <label for="low_stock_threshold">Afficher les produits avec un stock inférieur ou égal à :</label>
            <input type="number" name="low_stock_threshold" id="low_stock_threshold" value="<?php echo htmlspecialchars($low_stock_threshold ?? ''); ?>" min="0" placeholder="ex: 10">
        </div>
        <button type="submit" class="button">Appliquer le filtre</button>
        <a href="index.php?url=report/current_stock" class="button-info">Effacer le filtre</a>
    </fieldset>
</form>

<?php if (empty($products)): ?>
    <p>Aucun produit trouvé<?php echo $low_stock_threshold !== null ? ' correspondant aux critères du filtre' : ''; ?>.</p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom du produit</th>
                <th>Unité de mesure</th>
                <th style="text-align: right;">Stock actuel</th>
                <th>Prix d'achat</th>
                <th>Prix de vente</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
            <tr class="<?php echo ($low_stock_threshold !== null && $product['quantity_in_stock'] <= $low_stock_threshold) ? 'low-stock-alert' : '';
                         echo ($product['quantity_in_stock'] < 0) ? 'negative-stock-alert' : ''; ?>">
                <td><?php echo htmlspecialchars($product['id']); ?></td>
                <td><a href="index.php?url=stock/history/<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['name']); ?></a></td>
                <td><?php echo htmlspecialchars($product['unit_of_measure']); ?></td>
                <td style="text-align: right; font-weight: bold;"><?php echo htmlspecialchars($product['quantity_in_stock']); ?></td>
                <td style="text-align: right;"><?php echo htmlspecialchars(number_format((float)($product['purchase_price'] ?? 0), 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?></td>
                <td style="text-align: right;"><?php echo htmlspecialchars(number_format((float)($product['selling_price'] ?? 0), 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <style>
        .low-stock-alert { background-color: #fff3cd; /* Light yellow */ }
        .negative-stock-alert { background-color: #f8d7da; color: #721c24; /* Light red with dark text */ }
        .negative-stock-alert a { color: #721c24; }
    </style>
    <p><small>Cliquez sur le nom du produit pour voir l'historique de ses mouvements de stock.</small></p>
<?php endif; ?>

<div style="margin-top: 20px;">
    <button onclick="window.print();" class="button">Imprimer le rapport</button>
</div>
