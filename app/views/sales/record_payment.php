<?php
// $title set by controller
// $title = "Enregistrer le paiement pour la vente #VE-{$sale['id']}";
?>

<h2>Enregistrer le paiement pour la vente #VE-<?php echo htmlspecialchars($sale['id']); ?></h2>

<?php if (empty($sale)): ?>
    <p>Vente non trouvée.</p>
    <a href="index.php?url=sale/index" class="button-info">Retour à la liste</a>
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

<div style="margin-bottom: 20px;">
    <h4>Résumé de la vente</h4>
    <p><strong>Client :</strong> <?php echo htmlspecialchars($sale['client_display_name']); ?></p>
    <p><strong>Date de la vente :</strong> <?php echo htmlspecialchars($sale['sale_date']); ?></p>
    <p><strong>Montant total :</strong> <?php echo htmlspecialchars(number_format($sale['total_amount'], 2)); ?></p>
    <p><strong>Statut de paiement actuel :</strong> <span class="status-<?php echo htmlspecialchars(strtolower(str_replace('_', '-', $sale['payment_status']))); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $sale['payment_status']))); ?></span></p>
    <?php if($sale['payment_type'] === 'deferred'): ?>
    <p><strong>Date d'échéance :</strong> <?php echo htmlspecialchars($sale['due_date']); ?></p>
    <?php endif; ?>
</div>

<form action="index.php?url=sale/process_payment_update/<?php echo $sale['id']; ?>" method="POST">
    <fieldset>
        <legend>Mise à jour du paiement</legend>
        <div class="form-group">
            <label for="payment_status">Nouveau statut de paiement *</label>
            <select name="payment_status" id="payment_status" required>
                <?php
                $currentFormStatus = $data['payment_status'] ?? $sale['payment_status'];
                foreach ($allowedPaymentStatuses as $statusVal): ?>
                    <option value="<?php echo htmlspecialchars($statusVal); ?>" <?php echo ($currentFormStatus == $statusVal) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $statusVal))); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="payment_date">Date de paiement * (si payé/partiellement payé)</label>
            <input type="date" name="payment_date" id="payment_date" value="<?php echo htmlspecialchars($data['payment_date'] ?? ($sale['payment_date'] ?? date('Y-m-d'))); ?>" required>
        </div>
        <!-- Could add amount paid if implementing partial payments tracking -->
    </fieldset>

    <div class="form-group" style="margin-top: 20px;">
        <button type="submit" class="button">Mettre à jour le statut du paiement</button>
        <a href="index.php?url=sale/show/<?php echo $sale['id']; ?>" class="button-info">Annuler</a>
    </div>
</form>
<style>
    .status-pending { color: orange; font-weight: bold; }
    .status-paid { color: green; font-weight: bold; }
    .status-partially-paid { color: darkgoldenrod; font-weight: bold; }
</style>
