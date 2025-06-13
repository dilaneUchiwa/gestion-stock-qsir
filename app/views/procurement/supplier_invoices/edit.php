<?php
// $title set by controller
// $title = 'Modifier la facture fournisseur';
?>

<h2>Modifier la facture fournisseur #FACT-<?php echo htmlspecialchars($invoice['id']); ?> (<?php echo htmlspecialchars($invoice['invoice_number']); ?>)</h2>

<?php if (empty($invoice)): ?>
    <p>Données de la facture non trouvées pour la modification.</p>
    <a href="index.php?url=supplierinvoice/index" class="button-info">Retour à la liste</a>
    <?php return; ?>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <p><strong>Veuillez corriger les erreurs suivantes :</strong></p>
        <ul>
            <?php foreach ($errors as $field => $error): ?>
                <li><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $field))); ?>: <?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form action="index.php?url=supplierinvoice/update/<?php echo $invoice['id']; ?>" method="POST">
    <fieldset>
        <legend>Détails de la facture</legend>

        <div class="form-group">
            <label for="supplier_id">Fournisseur *</label>
            <select name="supplier_id" id="supplier_id" required>
                <option value="">Sélectionnez le fournisseur</option>
                <?php foreach ($suppliers as $supplier): ?>
                    <option value="<?php echo htmlspecialchars($supplier['id']); ?>" <?php echo ($invoice['supplier_id'] == $supplier['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($supplier['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="invoice_number">Numéro de facture *</label>
            <input type="text" name="invoice_number" id="invoice_number" value="<?php echo htmlspecialchars($invoice['invoice_number']); ?>" required>
        </div>

        <div class="form-group">
            <label for="invoice_date">Date de la facture *</label>
            <input type="date" name="invoice_date" id="invoice_date" value="<?php echo htmlspecialchars($invoice['invoice_date']); ?>" required>
        </div>

        <div class="form-group">
            <label for="due_date">Date d'échéance</label>
            <input type="date" name="due_date" id="due_date" value="<?php echo htmlspecialchars($invoice['due_date'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="total_amount">Montant total *</label>
            <input type="number" name="total_amount" id="total_amount" value="<?php echo htmlspecialchars(number_format($invoice['total_amount'], 2, '.', '')); ?>" min="0" step="0.01" required>
        </div>

        <div class="form-group">
            <label for="status">Statut</label>
            <select name="status" id="status">
                <?php foreach ($allowedStatuses as $statusVal): ?>
                    <option value="<?php echo htmlspecialchars($statusVal); ?>" <?php echo ($invoice['status'] == $statusVal) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $statusVal))); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="payment_date">Date de paiement</label>
            <input type="date" name="payment_date" id="payment_date" value="<?php echo htmlspecialchars($invoice['payment_date'] ?? ''); ?>">
            <small>Si le statut est 'Payé' ou 'Partiellement payé'. Sera automatiquement réglé à aujourd'hui si marqué comme 'Payé' et laissé vide.</small>
        </div>

        <div class="form-group">
            <label for="delivery_id">Lien vers la livraison (Optionnel)</label>
            <input type="number" name="delivery_id" id="delivery_id" value="<?php echo htmlspecialchars($invoice['delivery_id'] ?? ''); ?>" placeholder="Entrez l'ID de livraison (LIV-...)">
        </div>

        <div class="form-group">
            <label for="purchase_order_id">Lien vers le bon de commande (Optionnel)</label>
            <input type="number" name="purchase_order_id" id="purchase_order_id" value="<?php echo htmlspecialchars($invoice['purchase_order_id'] ?? ''); ?>" placeholder="Entrez l'ID du bon de commande (BC-...)">
        </div>

        <div class="form-group">
            <label for="notes">Remarques</label>
            <textarea name="notes" id="notes" rows="3"><?php echo htmlspecialchars($invoice['notes'] ?? ''); ?></textarea>
        </div>
    </fieldset>

    <div class="form-group" style="margin-top: 20px;">
        <button type="submit" class="button">Mettre à jour la facture</button>
        <a href="index.php?url=supplierinvoice/show/<?php echo $invoice['id']; ?>" class="button-info">Annuler</a>
    </div>
</form>
