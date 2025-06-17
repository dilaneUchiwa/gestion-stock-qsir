<?php
$pageTitle = $title ?? "Rapport de Caisse Journalier";
$currentFilters = $currentFilters ?? [];
$daily_cash_summary = $daily_cash_summary ?? [];

$grandTotalPeriod = 0;
$immediateSalesPeriodTotal = 0;
$deferredPaymentsPeriodTotal = 0;
?>

<h2><?php echo htmlspecialchars($pageTitle); ?></h2>

<form action="index.php" method="GET" class="filter-form">
    <input type="hidden" name="url" value="report/daily_cash_flow">

    <div class="form-row">
        <div class="form-group col">
            <label for="start_date">Date de début</label>
            <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($currentFilters['start_date'] ?? ''); ?>" class="form-control">
        </div>
        <div class="form-group col">
            <label for="end_date">Date de fin</label>
            <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($currentFilters['end_date'] ?? ''); ?>" class="form-control">
        </div>
    </div>
    <div class="form-group">
        <button type="submit" class="button">Afficher</button>
        <a href="index.php?url=report/daily_cash_flow" class="button-info">Mois en cours</a>
    </div>
</form>

<div class="table-responsive-container" style="margin-top: 20px;">
    <?php if (empty($daily_cash_summary)): ?>
        <p>Aucune donnée d'encaissement trouvée pour la période sélectionnée.</p>
    <?php else: ?>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Date</th>
                <th style="text-align: right;">Total Ventes Immédiates Payées</th>
                <th style="text-align: right;">Total Paiements sur Ventes Différées</th>
                <th style="text-align: right;">Total Encaissé du Jour</th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($daily_cash_summary as $summary):
                $immediateSalesPeriodTotal += (float)($summary['immediate_sales_total'] ?? 0);
                $deferredPaymentsPeriodTotal += (float)($summary['deferred_payments_total'] ?? 0);
                $grandTotalPeriod += (float)($summary['grand_total'] ?? 0);
            ?>
            <tr>
                <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($summary['date']))); ?></td>
                <td style="text-align: right;"><?php echo htmlspecialchars(number_format((float)($summary['immediate_sales_total'] ?? 0), 2, ',', ' ')); ?> €</td>
                <td style="text-align: right;"><?php echo htmlspecialchars(number_format((float)($summary['deferred_payments_total'] ?? 0), 2, ',', ' ')); ?> €</td>
                <td style="text-align: right; font-weight: bold;"><?php echo htmlspecialchars(number_format((float)($summary['grand_total'] ?? 0), 2, ',', ' ')); ?> €</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background-color: #f0f0f0; font-weight: bold;">
                <td style="text-align: right;">Total pour la période :</td>
                <td style="text-align: right;"><?php echo htmlspecialchars(number_format($immediateSalesPeriodTotal, 2, ',', ' ')); ?> €</td>
                <td style="text-align: right;"><?php echo htmlspecialchars(number_format($deferredPaymentsPeriodTotal, 2, ',', ' ')); ?> €</td>
                <td style="text-align: right;"><?php echo htmlspecialchars(number_format($grandTotalPeriod, 2, ',', ' ')); ?> €</td>
            </tr>
        </tfoot>
    </table>
    <?php endif; ?>
</div>

<style>
    .filter-form .form-row { display: flex; flex-wrap: wrap; margin-right: -5px; margin-left: -5px; align-items: flex-end; }
    .filter-form .form-group.col { flex-basis: 0; flex-grow: 1; max-width: 100%; padding-right: 5px; padding-left: 5px; }
    .filter-form .form-control { display: block; width: 100%; padding: 0.375rem 0.75rem; font-size: 1rem; line-height: 1.5; color: #495057; background-color: #fff; background-clip: padding-box; border: 1px solid #ced4da; border-radius: 0.25rem; }
    .filter-form .form-group { margin-bottom: 1rem; }
    .table-responsive-container { overflow-x: auto; }
    .table { width: 100%; margin-bottom: 1rem; color: #212529; border-collapse: collapse; }
    .table th, .table td { padding: 0.75rem; vertical-align: top; border-top: 1px solid #dee2e6; }
    .table thead th { vertical-align: bottom; border-bottom: 2px solid #dee2e6; background-color: #f8f9fa; }
    .table tbody + tbody { border-top: 2px solid #dee2e6; }
    .table-bordered { border: 1px solid #dee2e6; }
    .table-bordered th, .table-bordered td { border: 1px solid #dee2e6; }
    .table-striped tbody tr:nth-of-type(odd) { background-color: rgba(0, 0, 0, 0.05); }
</style>
