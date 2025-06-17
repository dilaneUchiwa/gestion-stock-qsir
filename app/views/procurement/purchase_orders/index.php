<?php
// $title is already set by the controller
// $title = 'Bons de commande';
?>

<h2>Bons de commande</h2>

<?php
// Display status messages from GET params
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    if ($status == 'created_success') echo '<div class="alert alert-success">Bon de commande créé avec succès.</div>';
    if ($status == 'updated_success') echo '<div class="alert alert-success">Bon de commande mis à jour avec succès.</div>';
    if ($status == 'cancelled_success') echo '<div class="alert alert-success">Bon de commande annulé avec succès.</div>';
    if ($status == 'cancelled_error') echo '<div class="alert alert-danger">Erreur lors de l\'annulation du bon de commande.</div>';
    if ($status == 'cancel_failed_status') echo '<div class="alert alert-warning">Impossible d\'annuler : le statut de la commande ne permet pas l\'annulation.</div>';
    if ($status == 'deleted_success') echo '<div class="alert alert-success">Bon de commande supprimé avec succès.</div>';
    if ($status == 'delete_error') echo '<div class="alert alert-danger">Erreur lors de la suppression du bon de commande. Il est peut-être référencé.</div>';
    if ($status == 'delete_failed_status') echo '<div class="alert alert-warning">Impossible de supprimer : le statut de la commande ne permet pas la suppression.</div>';
}
?>

<p><a href="index.php?url=purchaseorder/create" class="button">Créer un nouveau bon de commande</a></p>

<?php if (empty($purchaseOrders)): ?>
    <p>Aucun bon de commande trouvé.</p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Fournisseur</th>
                <th>Date de commande</th>
                <th>Livraison prévue</th>
                <th>Statut</th>
                <th>Montant total</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($purchaseOrders as $po): ?>
            <tr>
                <td>BC-<?php echo htmlspecialchars($po['id']); ?></td>
                <td><?php echo htmlspecialchars($po['supplier_name']); ?></td>
                <td><?php echo htmlspecialchars($po['order_date']); ?></td>
                <td><?php echo htmlspecialchars($po['expected_delivery_date'] ?? 'N/A'); ?></td>
                <td><span class="status-<?php echo htmlspecialchars(strtolower(str_replace('_', '-', $po['status']))); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $po['status']))); ?></span></td>
                <td style="text-align: right;"><?php echo htmlspecialchars(number_format((float)$po['total_amount'], 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?></td>
                <td>
                    <a href="index.php?url=purchaseorder/show/<?php echo $po['id']; ?>" class="button-info">Voir</a>
                    <?php if (in_array($po['status'], ['pending', 'partially_received'])): // Allow edit only for certain statuses ?>
                        <a href="index.php?url=purchaseorder/edit/<?php echo $po['id']; ?>" class="button">Modifier</a>
                    <?php endif; ?>
                    <?php if (in_array($po['status'], ['pending', 'partially_received'])): ?>
                         <form action="index.php?url=purchaseorder/cancel/<?php echo $po['id']; ?>" method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler ce BC ?');">
                            <button type="submit" class="button-danger">Annuler</button>
                        </form>
                    <?php endif; ?>
                     <?php if (in_array($po['status'], ['pending', 'cancelled'])): // Example: Allow deletion only for pending or cancelled orders ?>
                        <!-- <form action="index.php?url=purchaseorder/destroy/<?php echo $po['id']; ?>" method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer définitivement ce BC ? Cette action est irréversible.');">
                            <button type="submit" class="button-danger">Supprimer</button>
                        </form> -->
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <style>
        .status-pending { color: orange; }
        .status-received { color: green; }
        .status-partially-received { color: darkgoldenrod; }
        .status-cancelled { color: red; }
    </style>
<?php endif; ?>
