<?php
// $title set by controller
// $title = 'Historique des ventes';
?>

<h2>Historique des ventes</h2>

<?php
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    if ($status == 'created_success') echo '<div class="alert alert-success">Vente enregistrée avec succès. Stock mis à jour.</div>';
    if ($status == 'payment_updated') echo '<div class="alert alert-success">Statut de paiement mis à jour avec succès.</div>';
    if ($status == 'deleted_success') echo '<div class="alert alert-success">Vente supprimée avec succès et stock annulé.</div>';
    if ($status == 'delete_error') echo '<div class="alert alert-danger">Erreur lors de la suppression de la vente.</div>';
    if ($status == 'delete_failed') echo '<div class="alert alert-warning">Impossible de supprimer la vente : '.htmlspecialchars($_GET['reason'] ?? 'Erreur générale').'.</div>';
}
?>

<p>
    <a href="index.php?url=sale/create_immediate_payment" class="button">Nouvelle vente (paiement immédiat)</a>
    <a href="index.php?url=sale/create_deferred_payment" class="button" style="margin-left:10px;">Nouvelle vente (paiement différé)</a>
</p>

<?php if (empty($sales)): ?>
    <p>Aucune vente trouvée.</p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Client</th>
                <th>Montant Net</th>
                <th>Montant Payé</th>
                <th>Solde Restant</th>
                <th>Type Pmt.</th>
                <th>Statut Pmt.</th>
                <th>Échéance</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sales as $sale):
                $totalAmount = (float)($sale['total_amount'] ?? 0);
                $paidAmount = (float)($sale['paid_amount'] ?? 0);
                $remainingBalance = $totalAmount - $paidAmount;
            ?>
            <tr>
                <td>VE-<?php echo htmlspecialchars($sale['id']); ?></td>
                <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($sale['sale_date']))); ?></td>
                <td><?php echo htmlspecialchars($sale['client_display_name']); ?></td>
                <td style="text-align: right;"><?php echo htmlspecialchars(number_format($totalAmount, 2, ',', ' ')); ?> €</td>
                <td style="text-align: right; color: green;"><?php echo htmlspecialchars(number_format($paidAmount, 2, ',', ' ')); ?> €</td>
                <td style="text-align: right; font-weight: bold; color: <?php echo ($remainingBalance > 0.009) ? 'red' : 'green'; ?>;">
                    <?php echo htmlspecialchars(number_format($remainingBalance, 2, ',', ' ')); ?> €
                </td>
                <td><?php echo htmlspecialchars(ucfirst($sale['payment_type'])); ?></td>
                <td><span class="status-<?php echo htmlspecialchars(strtolower(str_replace('_', '-', $sale['payment_status']))); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $sale['payment_status']))); ?></span></td>
                <td><?php echo $sale['due_date'] ? htmlspecialchars(date('d/m/Y', strtotime($sale['due_date']))) : 'N/A'; ?></td>
                <td>
                    <a href="index.php?url=sale/show/<?php echo $sale['id']; ?>" class="button-info btn-sm">Voir</a>
                    <?php if ($sale['payment_type'] === 'deferred' && !in_array($sale['payment_status'], ['paid', 'cancelled', 'refunded'])): ?>
                        <a href="index.php?url=sale/manage_payments/<?php echo $sale['id']; ?>" class="button btn-sm" style="background-color: #ffc107; color: black;">Paiements</a>
                    <?php endif; ?>
                     <?php // Delete only if not paid or if it's 'cancelled' or 'pending' (adjust logic as needed)
                        if (in_array($sale['payment_status'], ['pending', 'cancelled'])): ?>
                        <form action="index.php?url=sale/destroy/<?php echo $sale['id']; ?>" method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette vente ? Cela annulera les quantités en stock.');">
                            <button type="submit" class="button-danger">Supprimer</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <style>
        .status-pending { color: orange; }
        .status-paid { color: green; }
        .status-partially-paid { color: darkgoldenrod; }
        .status-refunded { color: purple; }
        .status-cancelled { color: red; }
    </style>
<?php endif; ?>
