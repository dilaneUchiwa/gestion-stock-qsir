<?php
// $title, $sale, $payments_history, $remaining_balance, $allowedPaymentMethods, $data, $errors are passed from controller
$pageTitle = $title ?? 'Gérer les Paiements';
$currentFormData = $data ?? []; // For repopulating form on error
$formErrors = $errors ?? [];
?>

<h2><?php echo htmlspecialchars($pageTitle); ?></h2>

<?php if (empty($sale)): ?>
    <div class="alert alert-danger">Vente non trouvée.</div>
    <p><a href="index.php?url=sale/index" class="button-info">Retour à la liste des ventes</a></p>
    <?php return; ?>
<?php endif; ?>

<?php if (isset($_GET['status']) && $_GET['status'] == 'payment_success'): ?>
    <div class="alert alert-success">Paiement enregistré avec succès.</div>
<?php endif; ?>

<?php if (!empty($formErrors['general'])): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($formErrors['general']); ?></div>
<?php endif; ?>


<h3>Détails de la Vente #VE-<?php echo htmlspecialchars($sale['id']); ?></h3>
<table class="table table-bordered" style="width: auto; margin-bottom: 20px;">
    <tr><th>Client :</th><td><?php echo htmlspecialchars($sale['client_display_name']); ?></td></tr>
    <tr><th>Date de vente :</th><td><?php echo htmlspecialchars(date('d/m/Y', strtotime($sale['sale_date']))); ?></td></tr>
    <tr><th>Montant Total :</th><td><?php echo htmlspecialchars(number_format((float)$sale['total_amount'], 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?></td></tr>
    <tr><th>Montant Payé :</th><td style="color: green;"><?php echo htmlspecialchars(number_format((float)$sale['paid_amount'], 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?></td></tr>
    <tr><th>Solde Restant :</th><td style="font-weight: bold; color: <?php echo ((float)$remaining_balance > 0) ? 'red' : 'green'; ?>;">
        <?php echo htmlspecialchars(number_format((float)$remaining_balance, 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?>
    </td></tr>
    <tr><th>Statut du Paiement :</th><td><span class="status-<?php echo htmlspecialchars(strtolower(str_replace('_', '-', $sale['payment_status']))); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $sale['payment_status']))); ?></span></td></tr>
</table>

<hr>

<?php if ($sale['payment_status'] !== 'paid' && $sale['payment_status'] !== 'cancelled' && $sale['payment_status'] !== 'refunded'): ?>
    <h3>Ajouter un Paiement</h3>
    <form action="index.php?url=sale/store_payment" method="POST" style="margin-bottom: 30px;">
        <input type="hidden" name="sale_id" value="<?php echo htmlspecialchars($sale['id']); ?>">
        <div class="form-group">
            <label for="payment_date">Date du Paiement *</label>
            <input type="date" name="payment_date" id="payment_date" value="<?php echo htmlspecialchars($currentFormData['payment_date'] ?? date('Y-m-d')); ?>" required>
            <?php if (isset($formErrors['payment_date'])): ?><small class="error-text"><?php echo htmlspecialchars($formErrors['payment_date']); ?></small><?php endif; ?>
        </div>
        <div class="form-group">
            <label for="amount_paid">Montant Payé *</label>
            <input type="number" name="amount_paid" id="amount_paid" value="<?php echo htmlspecialchars($currentFormData['amount_paid'] ?? ''); ?>" min="0.01" step="0.01" required max="<?php echo htmlspecialchars(max(0.01, (float)$remaining_balance)); ?>">
             <small>Max: <?php echo htmlspecialchars(number_format((float)$remaining_balance, 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?></small>
            <?php if (isset($formErrors['amount_paid'])): ?><small class="error-text"><?php echo htmlspecialchars($formErrors['amount_paid']); ?></small><?php endif; ?>
        </div>
        <div class="form-group">
            <label for="payment_method">Méthode de Paiement *</label>
            <select name="payment_method" id="payment_method" required>
                <option value="">Sélectionnez une méthode</option>
                <?php foreach ($allowedPaymentMethods as $method): ?>
                    <option value="<?php echo htmlspecialchars($method); ?>" <?php echo (isset($currentFormData['payment_method']) && $currentFormData['payment_method'] == $method) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($method); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($formErrors['payment_method'])): ?><small class="error-text"><?php echo htmlspecialchars($formErrors['payment_method']); ?></small><?php endif; ?>
        </div>
        <div class="form-group">
            <label for="notes">Remarques</label>
            <textarea name="notes" id="notes" rows="3"><?php echo htmlspecialchars($currentFormData['notes'] ?? ''); ?></textarea>
        </div>
        <button type="submit" class="button button-success">Enregistrer le Paiement</button>
    </form>
<?php else: ?>
    <p class="alert alert-info">Cette vente est marquée comme '<?php echo htmlspecialchars($sale['payment_status']); ?>'. Aucun paiement supplémentaire ne peut être ajouté sauf si le statut est modifié.</p>
<?php endif; ?>

<h3>Historique des Paiements</h3>
<?php if (empty($payments_history)): ?>
    <p>Aucun paiement enregistré pour cette vente.</p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Montant Payé</th>
                <th>Méthode</th>
                <th>Remarques</th>
                <th>Enregistré le</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($payments_history as $payment): ?>
            <tr>
                <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($payment['payment_date']))); ?></td>
                <td style="text-align: right;"><?php echo htmlspecialchars(number_format((float)$payment['amount_paid'], 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?></td>
                <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                <td><?php echo nl2br(htmlspecialchars($payment['notes'] ?? '')); ?></td>
                <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($payment['created_at']))); ?></td>
                <td>
                    <a href="index.php?url=sale/print_payment_receipt/<?php echo $payment['id']; ?>" class="button-info btn-sm" target="_blank">Imprimer Reçu</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<p style="margin-top:20px;"><a href="index.php?url=sale/show/<?php echo $sale['id']; ?>" class="button-info">Retour aux détails de la vente</a></p>

<style>
    .error-text { color: red; font-size: 0.875em; display: block; }
    .status-pending { color: orange; font-weight: bold; }
    .status-paid { color: green; font-weight: bold; }
    .status-partially-paid { color: darkgoldenrod; font-weight: bold; }
    .status-refunded { color: purple; font-weight: bold; }
    .status-cancelled { color: red; font-weight: bold; }
</style>
