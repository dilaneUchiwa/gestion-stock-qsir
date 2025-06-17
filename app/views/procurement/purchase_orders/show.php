<?php
// $title is set by controller
// $title = 'Détails du bon de commande';
?>

<?php if (empty($purchaseOrder)): ?>
    <p>Bon de commande non trouvé.</p>
    <a href="index.php?url=purchaseorder/index" class="button-info">Retour à la liste</a>
    <?php return; ?>
<?php endif; ?>

<?php
// Display status messages
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    if ($status == 'created_success') echo '<div class="alert alert-success">Bon de commande créé avec succès.</div>';
    if ($status == 'updated_success') echo '<div class="alert alert-success">Bon de commande mis à jour avec succès.</div>';
    if ($status == 'cancelled_success') echo '<div class="alert alert-success">Bon de commande annulé avec succès.</div>';
    if ($status == 'cancelled_error') echo '<div class="alert alert-danger">Erreur lors de l\'annulation du bon de commande.</div>';
    if ($status == 'cancel_failed_status') echo '<div class="alert alert-warning">Impossible d\'annuler : le statut de la commande ne permet pas l\'annulation.</div>';
}
?>

<h2>Bon de commande #BC-<?php echo htmlspecialchars($purchaseOrder['id']); ?></h2>
<div style="margin-bottom: 20px;" class="action-buttons no-print">
    <a href="index.php?url=purchaseorder/index" class="button-info">Retour à la liste</a>
    <a href="index.php?url=purchaseorder/print_po/<?php echo $purchaseOrder['id']; ?>" class="button" target="_blank" style="background-color: #6c757d; color:white;">Imprimer le BC</a>
    <?php if (in_array($purchaseOrder['status'], ['pending', 'partially_received'])): ?>
        <a href="index.php?url=purchaseorder/edit/<?php echo $purchaseOrder['id']; ?>" class="button">Modifier la commande</a>
    <?php endif; ?>
     <?php if (in_array($purchaseOrder['status'], ['pending', 'partially_received'])): ?>
        <form action="index.php?url=purchaseorder/cancel/<?php echo $purchaseOrder['id']; ?>" method="POST" style="display:inline; margin-left: 10px;" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler ce BC ?');">
            <button type="submit" class="button-danger">Annuler la commande</button>
        </form>
    <?php endif; ?>
    <!-- Link to create delivery for this PO -->
    <?php if (in_array($purchaseOrder['status'], ['pending', 'partially_received'])): ?>
        <a href="index.php?url=delivery/create&po_id=<?php echo $purchaseOrder['id']; ?>" class="button" style="background-color: #007bff; margin-left:10px;">Recevoir les articles</a>
    <?php endif; ?>
</div>

<h3>Détails de la commande</h3>
<table class="table" style="width:50%; margin-bottom:20px;">
    <tr><th>Fournisseur :</th><td><?php echo htmlspecialchars($purchaseOrder['supplier_name']); ?> (ID: <?php echo htmlspecialchars($purchaseOrder['supplier_id']); ?>)</td></tr>
    <tr><th>Date de commande :</th><td><?php echo htmlspecialchars($purchaseOrder['order_date']); ?></td></tr>
    <tr><th>Date de livraison prévue :</th><td><?php echo htmlspecialchars($purchaseOrder['expected_delivery_date'] ?? 'N/A'); ?></td></tr>
    <tr><th>Statut :</th><td><span class="status-<?php echo htmlspecialchars(strtolower(str_replace('_', '-', $purchaseOrder['status']))); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $purchaseOrder['status']))); ?></span></td></tr>
    <tr><th>Remarques :</th><td><?php echo nl2br(htmlspecialchars($purchaseOrder['notes'] ?? 'N/A')); ?></td></tr>
    <tr><th>Créé le :</th><td><?php echo htmlspecialchars($purchaseOrder['created_at']); ?></td></tr>
    <tr><th>Dernière mise à jour :</th><td><?php echo htmlspecialchars($purchaseOrder['updated_at']); ?></td></tr>
</table>

<h3>Articles de la commande</h3>
<?php if (empty($purchaseOrder['items'])): ?>
    <p>Aucun article trouvé pour ce bon de commande.</p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>ID Produit</th>
                <th>Nom du produit</th>
                <th>Unité</th>
                <th>Quantité commandée</th>
                <th>Prix unitaire</th>
                <th>Sous-total</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $calculatedTotal = 0;
            foreach ($purchaseOrder['items'] as $item):
                // $subtotal = $item['quantity_ordered'] * $item['unit_price']; // sub_total is now a generated field
                $subtotal = $item['sub_total']; // This is a generated column: quantity_ordered * unit_price
                $calculatedTotal += $subtotal;
            ?>
            <tr>
                <td><?php echo htmlspecialchars($item['product_id']); ?></td>
                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                <td><?php echo htmlspecialchars($item['unit_name'] . ' (' . $item['unit_symbol'] . ')'); ?></td>
                <td style="text-align: right;"><?php echo htmlspecialchars($item['quantity_ordered']); ?></td>
                <td style="text-align: right;"><?php echo htmlspecialchars(number_format((float)$item['unit_price'], 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?></td>
                <td style="text-align: right;"><?php echo htmlspecialchars(number_format((float)$subtotal, 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="5" style="text-align:right;">Total de la commande (BD) :</th>
                <td style="text-align: right;"><strong><?php echo htmlspecialchars(number_format((float)$purchaseOrder['total_amount'], 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?></strong></td>
            </tr>
            <?php if (abs((float)$calculatedTotal - (float)$purchaseOrder['total_amount']) > 0.001) : // Check if calculated total matches stored total ?>
            <tr>
                <th colspan="5" style="text-align:right; color: orange;">Total calculé (articles) :</th>
                <td style="text-align: right; color: orange;"><strong><?php echo htmlspecialchars(number_format((float)$calculatedTotal, 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?></strong></td>
            </tr>
            <?php endif; ?>
        </tfoot>
    </table>
<?php endif; ?>

<style>
    .status-pending { color: orange; font-weight: bold; }
    .status-received { color: green; font-weight: bold; }
    .status-partially-received { color: darkgoldenrod; font-weight: bold; }
    .status-cancelled { color: red; font-weight: bold; }
</style>
