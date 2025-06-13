<?php
// $title set by controller
// $title = 'Créer une facture fournisseur';
?>

<h2>Créer une facture fournisseur</h2>

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

<?php if (isset($data['warning_message'])): ?>
    <div class="alert alert-warning"><?php echo htmlspecialchars($data['warning_message']); ?></div>
<?php endif; ?>


<form action="index.php?url=supplierinvoice/store" method="POST">
    <fieldset>
        <legend>Détails de la facture</legend>

        <div class="form-group">
            <label for="supplier_id">Fournisseur *</label>
            <select name="supplier_id" id="supplier_id" required>
                <option value="">Sélectionnez le fournisseur</option>
                <?php foreach ($suppliers as $supplier): ?>
                    <option value="<?php echo htmlspecialchars($supplier['id']); ?>" <?php echo (isset($data['supplier_id']) && $data['supplier_id'] == $supplier['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($supplier['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="invoice_number">Numéro de facture *</label>
            <input type="text" name="invoice_number" id="invoice_number" value="<?php echo htmlspecialchars($data['invoice_number'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
            <label for="invoice_date">Date de la facture *</label>
            <input type="date" name="invoice_date" id="invoice_date" value="<?php echo htmlspecialchars($data['invoice_date'] ?? date('Y-m-d')); ?>" required>
        </div>

        <div class="form-group">
            <label for="due_date">Date d'échéance</label>
            <input type="date" name="due_date" id="due_date" value="<?php echo htmlspecialchars($data['due_date'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="total_amount">Montant total *</label>
            <input type="number" name="total_amount" id="total_amount" value="<?php echo htmlspecialchars($data['total_amount'] ?? '0.00'); ?>" min="0" step="0.01" required>
        </div>

        <div class="form-group">
            <label for="status">Statut</label>
            <select name="status" id="status">
                <?php
                $currentStatus = $data['status'] ?? 'unpaid';
                foreach ($allowedStatuses as $statusVal): ?>
                    <option value="<?php echo htmlspecialchars($statusVal); ?>" <?php echo ($currentStatus == $statusVal) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $statusVal))); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="delivery_id">Lien vers la livraison (Optionnel)</label>
            <input type="number" name="delivery_id" id="delivery_id" value="<?php echo htmlspecialchars($data['delivery_id'] ?? ''); ?>" placeholder="Entrez l'ID de livraison (LIV-...)">
             <small>Si cette facture correspond à une livraison spécifique précédemment enregistrée.</small>
        </div>

        <div class="form-group">
            <label for="purchase_order_id">Lien vers le bon de commande (Optionnel)</label>
            <input type="number" name="purchase_order_id" id="purchase_order_id" value="<?php echo htmlspecialchars($data['purchase_order_id'] ?? ''); ?>" placeholder="Entrez l'ID du bon de commande (BC-...)">
            <small>Si cette facture correspond à un bon de commande général non lié à une seule livraison.</small>
        </div>

        <div class="form-group">
            <label for="notes">Remarques</label>
            <textarea name="notes" id="notes" rows="3"><?php echo htmlspecialchars($data['notes'] ?? ''); ?></textarea>
        </div>
    </fieldset>

    <div class="form-group" style="margin-top: 20px;">
        <button type="submit" class="button">Créer la facture</button>
        <a href="index.php?url=supplierinvoice/index" class="button-info">Annuler</a>
    </div>
</form>
