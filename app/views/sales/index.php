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
                <th>Montant total</th>
                <th>Type de paiement</th>
                <th>Statut du paiement</th>
                <th>Date d'échéance</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sales as $sale): ?>
            <tr>
                <td>VE-<?php echo htmlspecialchars($sale['id']); ?></td>
                <td><?php echo htmlspecialchars($sale['sale_date']); ?></td>
                <td><?php echo htmlspecialchars($sale['client_display_name']); ?></td>
                <td style="text-align: right;"><?php echo htmlspecialchars(number_format($sale['total_amount'], 2)); ?></td>
                <td><?php echo htmlspecialchars(ucfirst($sale['payment_type'])); ?></td>
                <td><span class="status-<?php echo htmlspecialchars(strtolower(str_replace('_', '-', $sale['payment_status']))); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $sale['payment_status']))); ?></span></td>
                <td><?php echo htmlspecialchars($sale['due_date'] ?? 'N/A'); ?></td>
                <td>
                    <a href="index.php?url=sale/show/<?php echo $sale['id']; ?>" class="button-info">Voir</a>
                    <?php if ($sale['payment_type'] === 'deferred' && $sale['payment_status'] !== 'paid' && $sale['payment_status'] !== 'cancelled'): ?>
                        <a href="index.php?url=sale/record_payment/<?php echo $sale['id']; ?>" class="button" style="background-color: #ffc107; color: black;">Enregistrer le paiement</a>
                    <?php endif; ?>
                     <?php // Delete only if not paid or if it's 'cancelled' or 'pending'
                        if (!in_array($sale['payment_status'], ['paid', 'partially_paid']) || in_array($sale['payment_status'], ['cancelled', 'pending'])): ?>
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
