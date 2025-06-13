<?php
// $title is set by controller
// $title = 'Livraisons / Réceptions';
?>

<h2>Livraisons / Réceptions</h2>

<?php
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    if ($status == 'created_success') echo '<div class="alert alert-success">Livraison enregistrée avec succès.</div>';
    if ($status == 'deleted_success') echo '<div class="alert alert-success">Livraison supprimée avec succès et stock annulé.</div>';
    if ($status == 'delete_error') echo '<div class="alert alert-danger">Erreur lors de la suppression de la livraison. La mise à jour du statut du stock ou du BC peut avoir échoué.</div>';
    if ($status == 'delete_not_found') echo '<div class="alert alert-danger">Erreur : Livraison à supprimer non trouvée.</div>';
}
?>

<p>
    <a href="index.php?url=delivery/create" class="button">Enregistrer une livraison directe</a>
    <span style="margin: 0 10px;">ou sélectionnez un bon de commande pour recevoir des articles :</span>
    <a href="index.php?url=purchaseorder/index" class="button-info">Voir les bons de commande</a>
</p>


<?php if (empty($deliveries)): ?>
    <p>Aucune livraison trouvée.</p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Réf BC</th>
                <th>Fournisseur</th>
                <th>Type</th>
                <th>Partielle</th>
                <th>Statut (BC)</th> <!-- This might be delivery status if deliveries have their own workflow -->
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($deliveries as $delivery): ?>
            <tr>
                <td>LIV-<?php echo htmlspecialchars($delivery['id']); ?></td>
                <td><?php echo htmlspecialchars($delivery['delivery_date']); ?></td>
                <td><?php echo htmlspecialchars($delivery['purchase_order_display']); ?></td>
                <td><?php echo htmlspecialchars($delivery['supplier_name']); ?></td>
                <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $delivery['type']))); ?></td>
                <td><?php echo $delivery['is_partial'] ? 'Oui' : 'Non'; ?></td>
                <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $delivery['status'] ?? 'N/A' ))); ?></td> <!-- Assuming 'status' comes from PO if joined -->
                <td>
                    <a href="index.php?url=delivery/show/<?php echo $delivery['id']; ?>" class="button-info">Voir</a>
                    <?php // Edit/Delete for deliveries are complex due to stock; often handled by reverse transactions. Add with caution. ?>
                    <!--
                    <form action="index.php?url=delivery/destroy/<?php echo $delivery['id']; ?>" method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette livraison ? Cela tentera d\'annuler les modifications de stock.');">
                        <button type="submit" class="button-danger">Supprimer</button>
                    </form>
                    -->
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
