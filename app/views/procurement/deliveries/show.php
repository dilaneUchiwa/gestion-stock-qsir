<?php
// $title set by controller
// $title = 'Détails de la livraison';
?>

<?php if (empty($delivery)): ?>
    <p>Livraison non trouvée.</p>
    <a href="index.php?url=delivery/index" class="button-info">Retour à la liste</a>
    <?php return; ?>
<?php endif; ?>

<?php
if (isset($_GET['status']) && $_GET['status'] == 'created_success') {
    echo '<div class="alert alert-success">Livraison enregistrée avec succès. Le statut du stock et du BC a été mis à jour.</div>';
}
?>

<h2>Livraison #LIV-<?php echo htmlspecialchars($delivery['id']); ?></h2>
<div style="margin-bottom: 20px;" class="action-buttons no-print">
    <a href="index.php?url=delivery/index" class="button-info">Retour à la liste</a>
    <a href="index.php?url=delivery/print_delivery_note/<?php echo $delivery['id']; ?>" class="button" target="_blank" style="background-color: #6c757d; color:white;">Imprimer le BL</a>
    <?php if ($delivery['purchase_order_id']): ?>
        <a href="index.php?url=purchaseorder/show/<?php echo $delivery['purchase_order_id']; ?>" class="button">Voir le BC lié</a>
    <?php endif; ?>
     <!-- Delete button - use with extreme caution or specific roles -->
    <form action="index.php?url=delivery/destroy/<?php echo $delivery['id']; ?>" method="POST" style="display:inline; margin-left: 10px;" onsubmit="return confirm('DANGER ! La suppression de cette livraison tentera d\'ANNULER les quantités en stock et peut affecter le statut du bon de commande. Cette action n\'est généralement pas recommandée. Êtes-vous absolument sûr ?');">
        <button type="submit" class="button-danger">Supprimer la livraison (Annuler le stock)</button>
    </form>
</div>

<h3>Détails de la livraison</h3>
<table class="table" style="width:50%; margin-bottom:20px;">
    <tr><th>Date de livraison :</th><td><?php echo htmlspecialchars($delivery['delivery_date']); ?></td></tr>
    <tr><th>Bon de commande lié :</th><td><?php echo htmlspecialchars($delivery['purchase_order_number']); ?></td></tr>
    <tr><th>Fournisseur :</th><td><?php echo htmlspecialchars($delivery['supplier_name']); ?></td></tr>
    <tr><th>Type :</th><td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $delivery['type']))); ?></td></tr>
    <tr><th>Est partielle :</th><td><?php echo $delivery['is_partial'] ? 'Oui' : 'Non'; ?></td></tr>
    <tr><th>Remarques :</th><td><?php echo nl2br(htmlspecialchars($delivery['notes'] ?? 'N/A')); ?></td></tr>
    <tr><th>Enregistré le :</th><td><?php echo htmlspecialchars($delivery['created_at']); ?></td></tr>
    <tr><th>Dernière mise à jour :</th><td><?php echo htmlspecialchars($delivery['updated_at']); ?></td></tr>
</table>

<h3>Articles reçus</h3>
<?php if (empty($delivery['items'])): ?>
    <p>Aucun article trouvé pour cette livraison.</p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>ID Produit</th>
                <th>Nom du produit</th>
                <th>Quantité reçue</th>
                <th>Unité (Reçue)</th>
                <th>Depuis l'article de BC ID</th>
                <th>Commandé à l'origine (Qté et Unité du BC)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($delivery['items'] as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['product_id']); ?></td>
                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                <td style="text-align: right;"><?php echo htmlspecialchars($item['quantity_received']); ?></td>
                <td><?php echo htmlspecialchars($item['unit_name'] . ' (' . $item['unit_symbol'] . ')'); ?></td>
                <td><?php echo htmlspecialchars($item['purchase_order_item_id'] ?? 'N/A (Direct)'); ?></td>
                <td style="text-align: right;">
                    <?php
                    if (isset($item['original_quantity_ordered'])) {
                        echo htmlspecialchars($item['original_quantity_ordered']);
                        if (isset($item['po_unit_name'])) {
                            echo ' ' . htmlspecialchars($item['po_unit_name'] . ($item['po_unit_symbol'] ? ' ('.$item['po_unit_symbol'].')' : ''));
                        }
                    } else {
                        echo 'N/A';
                    }
                    ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
