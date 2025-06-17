<?php
// $title set by controller
// $title = 'Détails de la facture fournisseur';
?>

<?php if (empty($invoice)): ?>
    <p>Facture fournisseur non trouvée.</p>
    <a href="index.php?url=supplierinvoice/index" class="button-info">Retour à la liste</a>
    <?php return; ?>
<?php endif; ?>

<?php
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    if ($status == 'created_success') echo '<div class="alert alert-success">Facture fournisseur créée avec succès.</div>';
    if ($status == 'updated_success') echo '<div class="alert alert-success">Facture fournisseur mise à jour avec succès.</div>';
    if ($status == 'paid_success') echo '<div class="alert alert-success">Facture marquée comme payée.</div>';
    if ($status == 'paid_error') echo '<div class="alert alert-danger">Erreur lors du marquage de la facture comme payée.</div>';
}
?>

<h2>Facture fournisseur #FACT-<?php echo htmlspecialchars($invoice['id']); ?> (<?php echo htmlspecialchars($invoice['invoice_number']); ?>)</h2>
<div style="margin-bottom: 20px;">
    <a href="index.php?url=supplierinvoice/index" class="button-info">Retour à la liste</a>
    <?php if ($invoice['status'] !== 'paid'): ?>
        <a href="index.php?url=supplierinvoice/edit/<?php echo $invoice['id']; ?>" class="button">Modifier la facture</a>
    <?php endif; ?>
    <?php if ($invoice['status'] === 'unpaid' || $invoice['status'] === 'partially_paid'): ?>
        <form action="index.php?url=supplierinvoice/markAsPaid/<?php echo $invoice['id']; ?>" method="POST" style="display:inline; margin-left: 10px;" onsubmit="return confirm('Êtes-vous sûr de vouloir marquer cette facture comme PAYÉE ?');">
            <button type="submit" class="button" style="background-color: #28a745;">Marquer comme payée</button>
        </form>
    <?php endif; ?>
     <form action="index.php?url=supplierinvoice/destroy/<?php echo $invoice['id']; ?>" method="POST" style="display:inline; margin-left: 10px;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette facture ? Cette action ne peut pas être annulée facilement.');">
        <button type="submit" class="button-danger">Supprimer la facture</button>
    </form>
</div>

<h3>Détails de la facture</h3>
<table class="table" style="width:60%; margin-bottom:20px;">
    <tr><th>Fournisseur :</th><td><a href="index.php?url=suppliers/show/<?php echo $invoice['supplier_id']; ?>"><?php echo htmlspecialchars($invoice['supplier_name']); ?></a></td></tr>
    <tr><th>Numéro de facture :</th><td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td></tr>
    <tr><th>Date de la facture :</th><td><?php echo htmlspecialchars($invoice['invoice_date']); ?></td></tr>
    <tr><th>Date d'échéance :</th><td><?php echo htmlspecialchars($invoice['due_date'] ?? 'N/A'); ?></td></tr>
    <tr class="total-amount-row"><th>Montant total :</th><td style="font-weight:bold;"><?php echo htmlspecialchars(number_format((float)$invoice['total_amount'], 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?></td></tr>
    <tr><th>Statut :</th><td><span class="status-<?php echo htmlspecialchars(strtolower(str_replace('_', '-', $invoice['status']))); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $invoice['status']))); ?></span></td></tr>
    <tr><th>Date de paiement :</th><td><?php echo htmlspecialchars($invoice['payment_date'] ?? 'N/A'); ?></td></tr>

    <tr><th>Livraison liée :</th>
        <td>
            <?php if ($invoice['delivery_id']): ?>
                <a href="index.php?url=delivery/show/<?php echo $invoice['delivery_id']; ?>">LIV-<?php echo htmlspecialchars($invoice['delivery_id']); ?></a>
            <?php else: echo 'N/A'; endif; ?>
        </td>
    </tr>
    <tr><th>Bon de commande lié :</th>
        <td>
            <?php if ($invoice['purchase_order_id']): ?>
                <a href="index.php?url=purchaseorder/show/<?php echo $invoice['purchase_order_id']; ?>">BC-<?php echo htmlspecialchars($invoice['purchase_order_id']); ?></a>
            <?php else: echo 'N/A'; endif; ?>
        </td>
    </tr>

    <tr><th>Remarques :</th><td><?php echo nl2br(htmlspecialchars($invoice['notes'] ?? 'N/A')); ?></td></tr>
    <tr><th>Enregistré le :</th><td><?php echo htmlspecialchars($invoice['created_at']); ?></td></tr>
    <tr><th>Dernière mise à jour :</th><td><?php echo htmlspecialchars($invoice['updated_at']); ?></td></tr>
</table>

<style>
    .status-unpaid { color: orange; font-weight: bold; }
    .status-paid { color: green; font-weight: bold; }
    .status-partially-paid { color: darkgoldenrod; font-weight: bold; }
    .status-cancelled { color: red; font-weight: bold; }
</style>
