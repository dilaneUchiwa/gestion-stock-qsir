<?php
// $title is set by controller
// $title = 'Factures fournisseurs';
?>

<h2>Factures fournisseurs</h2>

<?php
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    if ($status == 'created_success') echo '<div class="alert alert-success">Facture fournisseur créée avec succès.</div>';
    if ($status == 'updated_success') echo '<div class="alert alert-success">Facture fournisseur mise à jour avec succès.</div>';
    if ($status == 'paid_success') echo '<div class="alert alert-success">Facture marquée comme payée.</div>';
    if ($status == 'paid_error') echo '<div class="alert alert-danger">Erreur lors du marquage de la facture comme payée.</div>';
    if ($status == 'deleted_success') echo '<div class="alert alert-success">Facture fournisseur supprimée avec succès.</div>';
    if ($status == 'delete_error') echo '<div class="alert alert-danger">Erreur lors de la suppression de la facture fournisseur.</div>';
    if ($status == 'delete_not_found') echo '<div class="alert alert-danger">Erreur : Facture à supprimer non trouvée.</div>';
}
?>

<p>
    <a href="index.php?url=supplierinvoice/create" class="button">Créer une nouvelle facture</a>
    <span style="margin: 0 10px;">ou lier depuis :</span>
    <a href="index.php?url=delivery/index" class="button-info">Livraisons</a>
    <a href="index.php?url=purchaseorder/index" class="button-info" style="margin-left:5px;">Bons de commande</a>

</p>

<?php if (empty($invoices)): ?>
    <p>Aucune facture fournisseur trouvée.</p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>N° de facture</th>
                <th>Fournisseur</th>
                <th>Date de la facture</th>
                <th>Date d'échéance</th>
                <th>Montant total</th>
                <th>Statut</th>
                <th>Date de paiement</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($invoices as $invoice): ?>
            <tr>
                <td>FACT-<?php echo htmlspecialchars($invoice['id']); ?></td>
                <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                <td><?php echo htmlspecialchars($invoice['supplier_name']); ?></td>
                <td><?php echo htmlspecialchars($invoice['invoice_date']); ?></td>
                <td><?php echo htmlspecialchars($invoice['due_date'] ?? 'N/A'); ?></td>
                <td style="text-align: right;"><?php echo htmlspecialchars(number_format($invoice['total_amount'], 2)); ?></td>
                <td><span class="status-<?php echo htmlspecialchars(strtolower(str_replace('_', '-', $invoice['status']))); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $invoice['status']))); ?></span></td>
                <td><?php echo htmlspecialchars($invoice['payment_date'] ?? 'N/A'); ?></td>
                <td>
                    <a href="index.php?url=supplierinvoice/show/<?php echo $invoice['id']; ?>" class="button-info">Voir</a>
                    <?php if ($invoice['status'] !== 'paid'): // Example: Allow edit if not paid ?>
                        <a href="index.php?url=supplierinvoice/edit/<?php echo $invoice['id']; ?>" class="button">Modifier</a>
                    <?php endif; ?>
                     <?php if ($invoice['status'] === 'unpaid' || $invoice['status'] === 'partially_paid'): ?>
                        <form action="index.php?url=supplierinvoice/markAsPaid/<?php echo $invoice['id']; ?>" method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir marquer cette facture comme PAYÉE ?');">
                            <button type="submit" class="button" style="background-color: #28a745;">Marquer comme payée</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <style>
        .status-unpaid { color: orange; font-weight: bold; }
        .status-paid { color: green; font-weight: bold; }
        .status-partially-paid { color: darkgoldenrod; font-weight: bold; }
        .status-cancelled { color: red; font-weight: bold; }
    </style>
<?php endif; ?>
