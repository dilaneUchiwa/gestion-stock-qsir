<?php
// $title set by controller
?>

<h2><?php echo htmlspecialchars($title); ?></h2>

<div style="margin-bottom: 20px;">
    <a href="index.php?url=report/index" class="button-info">Retour à l'index des rapports</a>
</div>

<form method="GET" action="index.php">
    <input type="hidden" name="url" value="report/sales_report">
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
            <label for="client_id">Client :</label>
            <select name="client_id" id="client_id">
                <option value="">Tous les clients</option>
                <?php foreach ($clients as $client): ?>
                    <option value="<?php echo htmlspecialchars($client['id']); ?>" <?php echo ($filters['client_id'] == $client['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($client['name']); ?>
                    </option>
                <?php endforeach; ?>
                 <option value="occasional" <?php echo ($filters['client_id'] === 'occasional') ? 'selected' : ''; ?>>Clients occasionnels uniquement</option>
            </select>
        </div>
        <div class="form-group" style="display:inline-block; margin-right:10px;">
            <label for="payment_status">Statut du paiement :</label>
            <select name="payment_status" id="payment_status">
                <option value="">Tous les statuts</option>
                <?php foreach ($allowedPaymentStatuses as $status): ?>
                    <option value="<?php echo htmlspecialchars($status); ?>" <?php echo ($filters['payment_status'] == $status) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $status))); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="button">Appliquer les filtres</button>
        <a href="index.php?url=report/sales_report" class="button-info">Effacer les filtres</a>
    </fieldset>
</form>

<?php if (empty($sales)): ?>
    <p>Aucune vente trouvée pour les critères sélectionnés.</p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>ID Vente</th>
                <th>Date</th>
                <th>Client</th>
                <th>Type de paiement</th>
                <th>Statut du paiement</th>
                <th>Date d'échéance</th>
                <th style="text-align: right;">Montant total</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $grandTotalAmount = 0;
            foreach ($sales as $sale):
                $grandTotalAmount += $sale['total_amount'];
            ?>
            <tr>
                <td><a href="index.php?url=sale/show/<?php echo $sale['id']; ?>">VE-<?php echo htmlspecialchars($sale['id']); ?></a></td>
                <td><?php echo htmlspecialchars($sale['sale_date']); ?></td>
                <td><?php echo htmlspecialchars($sale['client_display_name']); ?></td>
                <td><?php echo htmlspecialchars(ucfirst($sale['payment_type'])); ?></td>
                <td><span class="status-<?php echo htmlspecialchars(strtolower(str_replace('_', '-', $sale['payment_status']))); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $sale['payment_status']))); ?></span></td>
                <td><?php echo htmlspecialchars($sale['due_date'] ?? 'N/A'); ?></td>
                <td style="text-align: right;"><?php echo htmlspecialchars(number_format($sale['total_amount'], 2)); ?></td>
                <td><a href="index.php?url=sale/show/<?php echo $sale['id']; ?>" class="button-info">Voir les détails</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="6" style="text-align: right;">Total général pour la période :</th>
                <th style="text-align: right;"><?php echo htmlspecialchars(number_format($grandTotalAmount, 2)); ?></th>
                <th></th>
            </tr>
        </tfoot>
    </table>
     <style>
        .status-pending { color: orange; }
        .status-paid { color: green; }
        .status-partially-paid { color: darkgoldenrod; }
        .status-refunded { color: purple; }
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
