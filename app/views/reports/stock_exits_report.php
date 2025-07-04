<?php
// $title set by controller
?>

<h2><?php echo htmlspecialchars($title); ?></h2>

<div style="margin-bottom: 20px;">
    <a href="index.php?url=report/index" class="button-info">Retour à l'index des rapports</a>
</div>

<form method="GET" action="index.php">
    <input type="hidden" name="url" value="report/stock_exits">
    <fieldset style="margin-bottom: 20px;">
        <legend>Filtres</legend>
        <div class="form-group" style="display:inline-block; margin-right:10px;">
            <label for="period">Période :</label>
            <select name="period" id="period">
                <option value="custom" <?php echo ($filters['period'] == 'custom') ? 'selected' : ''; ?>>Plage personnalisée</option>
                <option value="today" <?php echo ($filters['period'] == 'today') ? 'selected' : ''; ?>>Aujourd'hui</option>
                <option value="yesterday" <?php echo ($filters['period'] == 'yesterday') ? 'selected' : ''; ?>>Hier</option>
                <option value="last7days" <?php echo ($filters['period'] == 'last7days') ? 'selected' : ''; ?>>7 derniers jours</option>
                <option value="last30days" <?php echo ($filters['period'] == 'last30days') ? 'selected' : ''; ?>>30 derniers jours</option>
                <option value="this_month" <?php echo ($filters['period'] == 'this_month') ? 'selected' : ''; ?>>Ce mois-ci</option>
                <option value="last_month" <?php echo ($filters['period'] == 'last_month') ? 'selected' : ''; ?>>Le mois dernier</option>
            </select>
        </div>
        <div class="form-group" style="display:inline-block; margin-right:10px;">
            <label for="start_date">De :</label>
            <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($filters['start_date']); ?>">
        </div>
        <div class="form-group" style="display:inline-block; margin-right:10px;">
            <label for="end_date">À :</label>
            <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($filters['end_date']); ?>">
        </div>
        <div class="form-group" style="display:inline-block; margin-right:10px;">
            <label for="product_id">Produit :</label>
            <select name="product_id" id="product_id">
                <option value="">Tous les produits</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?php echo htmlspecialchars($product['id']); ?>" <?php echo ($filters['product_id'] == $product['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($product['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="button">Appliquer les filtres</button>
        <a href="index.php?url=report/stock_exits" class="button-info">Effacer les filtres</a>
    </fieldset>
</form>

<?php if (empty($movements)): ?>
    <p>Aucune sortie de stock trouvée pour les critères sélectionnés.</p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>Date et heure</th>
                <th>Nom du produit</th>
                <th>Type</th>
                <th style="text-align: right;">Quantité sortie</th>
                <th>Document lié</th>
                <th>Remarques</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $totalQuantityOut = 0;
            foreach ($movements as $movement):
                 $totalQuantityOut += $movement['quantity'];
            ?>
            <tr>
                <td><?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($movement['movement_date']))); ?></td>
                 <td><a href="index.php?url=stock/history/<?php echo $movement['product_id']; ?>"><?php echo htmlspecialchars($movement['product_name']); ?></a></td>
                <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $movement['type']))); ?></td>
                <td style="text-align: right; color: red; font-weight:bold;">-<?php echo htmlspecialchars($movement['quantity']); ?></td>
                <td>
                    <?php
                    if ($movement['related_document_id'] && $movement['related_document_type']) {
                        $docTypeDisplay = ucfirst(str_replace(['_', 'items'], [' ', ''], $movement['related_document_type']));
                        $docId = htmlspecialchars($movement['related_document_id']);
                        $link = '#'; // Default link
                        if ($movement['related_document_type'] === 'sale_items') {
                            // Need sale_id from sale_item_id. This is not directly available.
                             echo "Article de vente ID : {$docId}";
                        } elseif ($movement['related_document_type'] === 'delivery_items' && $movement['type'] === 'delivery_reversal') {
                            echo "Article de livraison ID : {$docId} (Annulation)";
                        } else {
                             echo "{$docTypeDisplay} ID : {$docId}";
                        }
                    } else {
                        echo 'N/A';
                    }
                    ?>
                </td>
                <td><?php echo nl2br(htmlspecialchars($movement['notes'] ?? '')); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
         <tfoot>
            <tr>
                <th colspan="3" style="text-align: right;">Quantité totale sortie :</th>
                <th style="text-align: right; color: red; font-weight:bold;">-<?php echo htmlspecialchars($totalQuantityOut); ?></th>
                <th colspan="2"></th>
            </tr>
        </tfoot>
    </table>
<?php endif; ?>

<div style="margin-top: 20px;">
    <button onclick="window.print();" class="button">Imprimer le rapport</button>
</div>
<script>
    // JS to disable custom date inputs if a predefined period is selected.
    document.getElementById('period').addEventListener('change', function() {
        var isCustom = this.value === 'custom';
        document.getElementById('start_date').disabled = !isCustom;
        document.getElementById('end_date').disabled = !isCustom;
    });
    // Trigger on load
     document.addEventListener('DOMContentLoaded', function() {
        var isCustom = document.getElementById('period').value === 'custom';
        document.getElementById('start_date').disabled = !isCustom;
        document.getElementById('end_date').disabled = !isCustom;
    });
</script>
