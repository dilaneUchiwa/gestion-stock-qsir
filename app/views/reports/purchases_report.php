<?php
// $title set by controller
?>

<h2><?php echo htmlspecialchars($title); ?></h2>

<div style="margin-bottom: 20px;">
    <a href="index.php?url=report/index" class="button-info">Retour à l'index des rapports</a>
</div>

<form method="GET" action="index.php">
    <input type="hidden" name="url" value="report/purchases_report">
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
            <label for="supplier_id">Fournisseur :</label>
            <select name="supplier_id" id="supplier_id">
                <option value="">Tous les fournisseurs</option>
                <?php foreach ($suppliers as $supplier): ?>
                    <option value="<?php echo htmlspecialchars($supplier['id']); ?>" <?php echo ($filters['supplier_id'] == $supplier['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($supplier['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="display:inline-block; margin-right:10px;">
            <label for="status">Statut du BC :</label>
            <select name="status" id="status">
                <option value="">Tous les statuts</option>
                <?php foreach ($allowedStatuses as $status): ?>
                    <option value="<?php echo htmlspecialchars($status); ?>" <?php echo ($filters['status'] == $status) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $status))); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="button">Appliquer les filtres</button>
        <a href="index.php?url=report/purchases_report" class="button-info">Effacer les filtres</a>
    </fieldset>
</form>

<?php if (empty($purchaseOrders)): ?>
    <p>Aucun bon de commande trouvé pour les critères sélectionnés.</p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>ID BC</th>
                <th>Date de commande</th>
                <th>Fournisseur</th>
                <th>Livraison prévue</th>
                <th>Statut</th>
                <th style="text-align: right;">Montant total</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $grandTotalAmount = 0;
            foreach ($purchaseOrders as $po):
                $grandTotalAmount += $po['total_amount'];
            ?>
            <tr>
                <td><a href="index.php?url=purchaseorder/show/<?php echo $po['id']; ?>">BC-<?php echo htmlspecialchars($po['id']); ?></a></td>
                <td><?php echo htmlspecialchars($po['order_date']); ?></td>
                <td><?php echo htmlspecialchars($po['supplier_name']); ?></td>
                <td><?php echo htmlspecialchars($po['expected_delivery_date'] ?? 'N/A'); ?></td>
                <td><span class="status-<?php echo htmlspecialchars(strtolower(str_replace('_', '-', $po['status']))); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $po['status']))); ?></span></td>
                <td style="text-align: right;"><?php echo htmlspecialchars(number_format((float)$po['total_amount'], 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?></td>
                <td><a href="index.php?url=purchaseorder/show/<?php echo $po['id']; ?>" class="button-info">Voir les détails</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="5" style="text-align: right;">Total général pour la période :</th>
                <th style="text-align: right;"><?php echo htmlspecialchars(number_format((float)$grandTotalAmount, 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?></th>
                <th></th>
            </tr>
        </tfoot>
    </table>
    <style>
        .status-pending { color: orange; }
        .status-received { color: green; }
        .status-partially-received { color: darkgoldenrod; }
        .status-cancelled { color: red; }
    </style>
<?php endif; ?>

<div style="margin-top: 20px;">
    <button onclick="window.print();" class="button">Imprimer le rapport</button>
</div>
<script>
    document.getElementById('period').addEventListener('change', function() {
        var isCustom = this.value === 'custom';
        document.getElementById('start_date').disabled = !isCustom;
        document.getElementById('end_date').disabled = !isCustom;
    });
    document.addEventListener('DOMContentLoaded', function() {
        var isCustom = document.getElementById('period').value === 'custom';
        document.getElementById('start_date').disabled = !isCustom;
        document.getElementById('end_date').disabled = !isCustom;
    });
</script>
