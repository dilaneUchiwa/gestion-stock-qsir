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

<h2>Vente #VE-<?php echo htmlspecialchars($sale['id']); ?></h2>
<div style="margin-bottom: 20px;">
    <a href="index.php?url=sale/index" class="button-info">Retour à la liste</a>
    <?php if ($sale['payment_type'] === 'deferred' && $sale['payment_status'] !== 'paid' && $sale['payment_status'] !== 'cancelled'): ?>
        <a href="index.php?url=sale/record_payment/<?php echo $sale['id']; ?>" class="button" style="background-color: #ffc107; color: black;">Enregistrer le paiement</a>
    <?php endif; ?>
    <?php // Edit/Delete: Add conditions for when these actions are allowed (e.g., not if paid)
        // <a href="index.php?url=sale/edit/<?php echo $sale['id']; ?>" class="button">Modifier la vente</a>
        if (!in_array($sale['payment_status'], ['paid', 'partially_paid']) || in_array($sale['payment_status'], ['cancelled', 'pending'])):
    ?>
    <form action="index.php?url=sale/destroy/<?php echo $sale['id']; ?>" method="POST" style="display:inline; margin-left:10px;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette vente ? Cela annulera les quantités en stock.');">
        <button type="submit" class="button-danger">Supprimer la vente</button>
    </form>
    <?php endif; ?>
</div>

<h3>Détails de la vente</h3>
<table class="table" style="width:60%; margin-bottom:20px;">
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
    <tr><th>Montant total :</th><td style="font-weight:bold;"><?php echo htmlspecialchars(number_format($sale['total_amount'], 2)); ?></td></tr>
    <tr><th>Type de paiement :</th><td><?php echo htmlspecialchars(ucfirst($sale['payment_type'])); ?></td></tr>
    <tr><th>Statut du paiement :</th><td><span class="status-<?php echo htmlspecialchars(strtolower(str_replace('_', '-', $sale['payment_status']))); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $sale['payment_status']))); ?></span></td></tr>
    <?php if ($sale['payment_type'] === 'deferred'): ?>
        <tr><th>Date d'échéance :</th><td><?php echo htmlspecialchars($sale['due_date'] ?? 'N/A'); ?></td></tr>
    <?php endif; ?>
     <tr><th>Date de paiement :</th><td><?php echo htmlspecialchars($sale['payment_date'] ?? 'N/A'); ?></td></tr>
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
                <th>Quantité vendue</th>
                <th>Prix unitaire</th>
                <th>Sous-total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sale['items'] as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['product_id']); ?></td>
                <td><?php echo htmlspecialchars($item['product_name']); ?> (<?php echo htmlspecialchars($item['unit_of_measure']); ?>)</td>
                <td style="text-align: right;"><?php echo htmlspecialchars($item['quantity_sold']); ?></td>
                <td style="text-align: right;"><?php echo htmlspecialchars(number_format($item['unit_price'], 2)); ?></td>
                <td style="text-align: right;"><?php echo htmlspecialchars(number_format($item['sub_total'], 2)); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" style="text-align:right;">Total général :</th>
                <td style="text-align: right;"><strong><?php echo htmlspecialchars(number_format($sale['total_amount'], 2)); ?></strong></td>
            </tr>
        </tfoot>
    </table>
<?php endif; ?>

<style>
    .status-pending { color: orange; font-weight: bold; }
    .status-paid { color: green; font-weight: bold; }
    .status-partially-paid { color: darkgoldenrod; font-weight: bold; }
    .status-refunded { color: purple; font-weight: bold; }
    .status-cancelled { color: red; font-weight: bold; }
</style>
