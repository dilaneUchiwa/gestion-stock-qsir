<?php
// $title set by controller
// $title = 'Détails de la vente';
?>

<?php if (empty($sale)): ?>
    <p>Vente non trouvée.</p>
    <a href="index.php?url=sale/index" class="button-info">Retour à la liste</a>
    <?php return; ?>
<?php endif; ?>

<?php
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    if ($status == 'created_success') echo '<div class="alert alert-success">Vente enregistrée avec succès. Stock mis à jour.</div>';
    if ($status == 'payment_updated') echo '<div class="alert alert-success">Statut de paiement mis à jour avec succès.</div>';
}
?>

<h2>Vente <?php echo htmlspecialchars($sale['invoice_number'] ?? ('#VE-' . $sale['id'])); ?></h2>
<div style="margin-bottom: 20px;">
    <a href="index.php?url=sale/index" class="button-info">Retour à la liste</a>
    <a href="index.php?url=sale/print_invoice/<?php echo $sale['id']; ?>" class="button" target="_blank" style="background-color: #6c757d; color:white;">Imprimer la Facture</a>
    <?php if ($sale['payment_type'] === 'deferred' && !in_array($sale['payment_status'], ['paid', 'cancelled', 'refunded'])): ?>
        <a href="index.php?url=sale/manage_payments/<?php echo $sale['id']; ?>" class="button" style="background-color: #ffc107; color: black;">Gérer les Paiements</a>
    <?php endif; ?>
    <?php // Edit/Delete: Add conditions for when these actions are allowed (e.g., not if paid)?>
        <!-- <a href="index.php?url=sale/edit/<?php echo $sale['id']; ?>" class="button">Modifier la vente</a> -->
        <?php if (!in_array($sale['payment_status'], ['paid', 'partially_paid']) || in_array($sale['payment_status'], ['cancelled', 'pending'])):
    ?>
    <form action="index.php?url=sale/destroy/<?php echo $sale['id']; ?>" method="POST" style="display:inline; margin-left:10px;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette vente ? Cela annulera les quantités en stock.');">
        <button type="submit" class="button-danger">Supprimer la vente</button>
    </form>
    <?php endif; ?>
</div>

<h3>Détails de la vente</h3>
<table class="table" style="width:60%; margin-bottom:20px;">
    <tr><th>Numéro de Facture :</th><td><strong><?php echo htmlspecialchars($sale['invoice_number'] ?? 'N/A'); ?></strong></td></tr>
    <tr><th>Date de la vente :</th><td><?php echo htmlspecialchars($sale['sale_date']); ?></td></tr>
    <tr><th>Client :</th>
        <td>
            <?php if ($sale['client_id']): ?>
                <a href="index.php?url=clients/show/<?php echo $sale['client_id']; ?>"><?php echo htmlspecialchars($sale['client_display_name']); ?></a>
            <?php else: ?>
                <?php echo htmlspecialchars($sale['client_display_name']); ?>
            <?php endif; ?>
        </td>
    </tr>
    <tr><th>Sous-Total Articles :</th><td><?php echo htmlspecialchars(number_format((float)($sale['total_amount'] + ($sale['discount_amount'] ?? 0)), 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?></td></tr>
    <tr><th>Réduction :</th><td><?php echo htmlspecialchars(number_format((float)($sale['discount_amount'] ?? 0), 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?></td></tr>
    <tr><th>Montant Net :</th><td style="font-weight:bold;"><?php echo htmlspecialchars(number_format((float)$sale['total_amount'], 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?></td></tr>
    <tr><th>Type de paiement :</th><td><?php echo htmlspecialchars(ucfirst($sale['payment_type'])); ?></td></tr>
    <tr><th>Statut du paiement :</th><td><span class="status-<?php echo htmlspecialchars(strtolower(str_replace('_', '-', $sale['payment_status']))); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $sale['payment_status']))); ?></span></td></tr>
    <?php if ($sale['payment_type'] === 'deferred'): ?>
        <tr><th>Montant Payé :</th><td style="color: green;"><?php echo htmlspecialchars(number_format((float)($sale['paid_amount'] ?? 0), 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?></td></tr>
        <tr><th>Solde Restant :</th><td style="font-weight: bold; color: <?php echo (((float)$sale['total_amount'] - (float)($sale['paid_amount'] ?? 0)) > 0) ? 'red' : 'green'; ?>;">
            <?php echo htmlspecialchars(number_format((float)$sale['total_amount'] - (float)($sale['paid_amount'] ?? 0), 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?>
        </td></tr>
        <tr><th>Date d'échéance :</th><td><?php echo htmlspecialchars($sale['due_date'] ?? 'N/A'); ?></td></tr>
    <?php elseif ($sale['payment_type'] === 'immediate'): ?>
        <tr><th>Montant Versé :</th><td><?php echo htmlspecialchars(number_format((float)($sale['amount_tendered'] ?? 0), 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?></td></tr>
        <tr><th>Monnaie Rendue :</th><td><?php echo htmlspecialchars(number_format((float)($sale['change_due'] ?? 0), 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?></td></tr>
    <?php endif; ?>
    <tr><th>Date de dernier paiement :</th><td><?php echo htmlspecialchars($sale['payment_date'] ?? 'N/A'); ?></td></tr>
    <tr><th>Remarques :</th><td><?php echo nl2br(htmlspecialchars($sale['notes'] ?? 'N/A')); ?></td></tr>
    <tr><th>Enregistré le :</th><td><?php echo htmlspecialchars($sale['created_at']); ?></td></tr>
    <tr><th>Dernière mise à jour :</th><td><?php echo htmlspecialchars($sale['updated_at']); ?></td></tr>
</table>

<h3>Articles vendus</h3>
<?php if (empty($sale['items'])): ?>
    <p>Aucun article trouvé pour cette vente.</p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>ID Produit</th>
                <th>Nom du produit</th>
                <th>Unité</th>
                <th>Quantité vendue</th>
                <th>Prix unitaire</th>
                <th>Sous-total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sale['items'] as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['product_id']); ?></td>
                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                <td><?php echo htmlspecialchars($item['unit_name'] . ' (' . $item['unit_symbol'] . ')'); ?></td>
                <td style="text-align: right;"><?php echo htmlspecialchars(number_format((float)$item['quantity_sold'], 3, ',', ' ')); ?></td>
                <td style="text-align: right;"><?php echo htmlspecialchars(number_format((float)$item['unit_price'], 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?></td>
                <td style="text-align: right;"><?php echo htmlspecialchars(number_format((float)$item['sub_total'], 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="5" style="text-align:right;">Total général :</th>
                <td style="text-align: right;"><strong><?php echo htmlspecialchars(number_format((float)$sale['total_amount'], 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?></strong></td>
            </tr>
        </tfoot>
    </table>
<?php endif; ?>

<?php if ($sale['payment_type'] === 'deferred'): ?>
<h3>Historique des Paiements</h3>
<?php
// TODO: Controller (SaleController@show) needs to fetch and pass $payments_history
// $payments_history = $this->salePaymentModel->getPaymentsForSale($sale['id']);
if (!empty($payments_history)):
?>
    <table class="table">
        <thead>
            <tr>
                <th>Date Paiement</th>
                <th>Montant Payé</th>
                <th>Méthode</th>
                <th>Remarques</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($payments_history as $payment): ?>
            <tr>
                <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($payment['payment_date']))); ?></td>
                <td style="text-align: right;"><?php echo htmlspecialchars(number_format((float)$payment['amount_paid'], 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?></td>
                <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                <td><?php echo nl2br(htmlspecialchars($payment['notes'] ?? '')); ?></td>
                <td>
                    <a href="index.php?url=sale/print_payment_receipt/<?php echo $payment['id']; ?>" class="button-info btn-sm" target="_blank">Imprimer Reçu</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>Aucun paiement enregistré pour cette vente.</p>
<?php endif; ?>
<?php endif; // End if payment_type is deferred ?>


<style>
    .status-pending { color: orange; font-weight: bold; }
    .status-paid { color: green; font-weight: bold; }
    .status-partially-paid { color: darkgoldenrod; font-weight: bold; }
    .status-refunded { color: purple; font-weight: bold; }
    .status-cancelled { color: red; font-weight: bold; }
</style>
