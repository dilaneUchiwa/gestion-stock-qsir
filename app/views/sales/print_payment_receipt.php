<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu de Paiement #P<?php echo htmlspecialchars($payment['id'] ?? ''); ?></title>
    <link rel="stylesheet" href="/public/css/print_style.css"> <!-- Added link -->
    <style>
        /* View-specific styles */
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; font-size: 12px; }
        .container { width: 80mm; margin: 10px auto; padding: 10px; /* border: 1px solid #ccc; */ }
        h1, h2, h3 { text-align: center; margin-top: 5px; margin-bottom: 10px; }
        p { margin: 5px 0; }
        .header-section, .footer-section { text-align: center; margin-bottom: 15px; }
        .company-details p, .client-details p, .receipt-details p, .payment-details p, .balance-details p {
            margin: 3px 0;
        }
        .label { font-weight: bold; }
        hr { border: none; border-top: 1px dashed #ccc; margin: 10px 0; }

        @media print {
            body { margin: 0; padding: 0; font-size: 10pt; background-color: #fff; } /* Ensure white background */
            .container { width: 100%; /* Use full printable width */ margin: 0; padding: 5mm; border: none; box-shadow: none; }
            .no-print { display: none !important; }
            /* Additional print-specific styling can go here */
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-section">
            <h3>ENTREPRISE XYZ</h3>
            <p>123 Rue de la Facture<br>75000 Paris, France<br>Tél : 01 23 45 67 89</p>
        </div>

        <h2>Reçu de Paiement</h2>

        <div class="receipt-details">
            <p><span class="label">Numéro de Reçu :</span> #P<?php echo htmlspecialchars($payment['id'] ?? 'N/A'); ?></p>
            <p><span class="label">Date du Paiement :</span> <?php echo htmlspecialchars(date('d/m/Y', strtotime($payment['payment_date'] ?? time()))); ?></p>
            <?php if (!empty($sale) && !empty($sale['id'])): ?>
            <p><span class="label">Pour Facture :</span> #VE-<?php echo htmlspecialchars($sale['id']); ?></p>
            <?php endif; ?>
        </div>
        <hr>

        <?php if (!empty($sale) && !empty($sale['client_display_name'])): ?>
        <div class="client-details">
            <p><span class="label">Reçu de :</span> <?php echo htmlspecialchars($sale['client_display_name']); ?></p>
            <?php /* Add more client details if needed and available */ ?>
        </div>
        <hr>
        <?php endif; ?>

        <div class="payment-details">
            <p><span class="label">Montant Payé :</span> <span style="font-size: 1.2em; font-weight: bold;"><?php echo htmlspecialchars(number_format((float)($payment['amount_paid'] ?? 0), 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?></span></p>
            <p><span class="label">Méthode de Paiement :</span> <?php echo htmlspecialchars($payment['payment_method'] ?? 'N/A'); ?></p>
            <?php if (!empty($payment['notes'])): ?>
                <p><span class="label">Remarques :</span> <?php echo nl2br(htmlspecialchars($payment['notes'])); ?></p>
            <?php endif; ?>
        </div>
        <hr>

        <?php if (!empty($sale)): ?>
        <div class="balance-details">
            <p><span class="label">Montant Total de la Facture :</span> <?php echo htmlspecialchars(number_format((float)($sale['total_amount'] ?? 0), 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?></p>
            <p><span class="label">Total Payé (après ce reçu) :</span> <?php echo htmlspecialchars(number_format((float)($totalPaidUpToThisReceipt ?? 0), 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?></p>
            <p><span class="label">Solde Restant Dû (après ce reçu) :</span> <span style="font-weight: bold; color: <?php echo ($balanceDueAfterThisReceipt > 0.009) ? 'red' : 'green'; ?>;"><?php echo htmlspecialchars(number_format((float)($balanceDueAfterThisReceipt ?? 0), 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?></span></p>
            <p><span class="label">Statut Global de la Facture :</span> <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $sale['payment_status']))); ?></p>
        </div>
        <hr>
        <?php endif; ?>

        <div class="footer-section">
            <p>Merci !</p>
        </div>

        <div class="no-priwnt" style="text-align:center; margin-top:20px;">
            <button onclick="window.print();">Imprimer ce reçu</button>
            <button onclick="window.close();">Fermer</button>
        </div>
    </div>
</body>
</html>
