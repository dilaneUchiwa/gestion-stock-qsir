<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture #VE-<?php echo htmlspecialchars($sale['id'] ?? ''); ?></title>
    <link rel="stylesheet" href="/public/css/print_style.css"> <!-- Added link to print_style.css -->
    <style>
        /* View-specific styles can still go here, or be moved to print_style.css */
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; font-size: 12px; }
        .container { width: 90%; margin: 20px auto; padding: 20px; /* border: 1px solid #ccc; (handled by print_style) */ }
        h1, h2, h3 { text-align: center; margin-top: 5px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .header-section, .footer-section { margin-bottom: 30px; }
        .company-details, .client-details { width: 48%; display: inline-block; vertical-align: top; }
        .company-details { text-align: left; }
        .client-details { text-align: right; }
        .invoice-details { margin-top: 20px; margin-bottom: 30px; text-align: center; }
        .invoice-details p { margin: 2px 0; }
        .totals { text-align: right; margin-top: 20px; }
        .totals th, .totals td { border: none; text-align: right; padding: 4px 0; }
        .notes-section { margin-top: 30px; border-top: 1px solid #eee; padding-top: 10px; font-size: 10px; }
        @media print {
            body { margin: 0; padding: 0; font-size: 10pt; } /* Adjust font size for print */
            .container { width: 100%; margin: 0; padding: 10px; border: none; }
            .no-print { display: none !important; }
            table { font-size: 9pt; }
             th, td { padding: 5px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-section">
            <div class="company-details">
                <h2>ENTREPRISE XYZ (Vendeur)</h2>
                <p>123 Rue de la Facture<br>75000 Paris, France<br>Tél : 01 23 45 67 89<br>Email : contact@entreprise.xyz</p>
            </div>
            <div class="client-details">
                <h3>Client (Acheteur)</h3>
                <p>
                    <?php echo htmlspecialchars($sale['client_display_name'] ?? 'N/A'); ?><br>
                    <?php // Assuming client address details would be part of $sale if fetched by getByIdWithDetails
                          // Or if $sale['client_id'] is present, one could fetch client details.
                          // For now, this is a placeholder if address isn't directly in $sale.
                          echo htmlspecialchars($sale['client_address'] ?? 'Adresse non fournie'); ?><br>
                    <?php echo htmlspecialchars($sale['client_phone'] ?? ''); ?><br>
                    <?php echo htmlspecialchars($sale['client_email'] ?? ''); ?>
                </p>
            </div>
        </div>

        <div class="invoice-details">
            <h1>FACTURE</h1>
            <p><strong>Numéro de Facture :</strong> #VE-<?php echo htmlspecialchars($sale['id']); ?></p>
            <p><strong>Date de Vente :</strong> <?php echo htmlspecialchars(date('d/m/Y', strtotime($sale['sale_date']))); ?></p>
            <?php if ($sale['payment_type'] === 'deferred' && !empty($sale['due_date'])): ?>
                <p><strong>Date d'Échéance :</strong> <?php echo htmlspecialchars(date('d/m/Y', strtotime($sale['due_date']))); ?></p>
            <?php endif; ?>
            <p><strong>Statut du Paiement :</strong> <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $sale['payment_status']))); ?></p>
        </div>

        <h3>Articles Vendus</h3>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Désignation</th>
                    <th style="text-align:right;">Quantité</th>
                    <th>Unité</th>
                    <th style="text-align:right;">Prix Unitaire</th>
                    <th style="text-align:right;">Sous-Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $grossTotalFromItems = 0;
                if (!empty($sale['items'])):
                    $itemNumber = 1;
                    foreach ($sale['items'] as $item):
                        $itemSubTotal = (float)$item['quantity_sold'] * (float)$item['unit_price'];
                        $grossTotalFromItems += $itemSubTotal;
                ?>
                <tr>
                    <td><?php echo $itemNumber++; ?></td>
                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                    <td style="text-align:right;"><?php echo htmlspecialchars(number_format((float)$item['quantity_sold'], 2, ',', ' ')); ?></td>
                    <td><?php echo htmlspecialchars($item['unit_symbol'] ?? $item['unit_name'] ?? ''); ?></td>
                    <td style="text-align:right;"><?php echo htmlspecialchars(number_format((float)$item['unit_price'], 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?></td>
                    <td style="text-align:right;"><?php echo htmlspecialchars(number_format($itemSubTotal, 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?></td>
                </tr>
                <?php
                    endforeach;
                endif;
                ?>
            </tbody>
        </table>

        <div class="totals">
            <table>
                <tr>
                    <th>Sous-Total Brut :</th>
                    <td><?php echo htmlspecialchars(number_format($grossTotalFromItems, 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?></td>
                </tr>
                <tr>
                    <th>Réduction :</th>
                    <td><?php echo htmlspecialchars(number_format((float)($sale['discount_amount'] ?? 0), 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?></td>
                </tr>
                <tr>
                    <th style="font-size: 1.1em;">Total Net à Payer :</th>
                    <td style="font-size: 1.1em; font-weight: bold;"><?php echo htmlspecialchars(number_format((float)$sale['total_amount'], 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?></td>
                </tr>
                <tr>
                    <th>Montant Déjà Payé :</th>
                    <td style="color: green;"><?php echo htmlspecialchars(number_format((float)($sale['paid_amount'] ?? 0), 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?></td>
                </tr>
                <tr>
                    <th style="font-weight: bold; color: <?php echo (((float)$sale['total_amount'] - (float)($sale['paid_amount'] ?? 0)) > 0.009) ? 'red' : 'green'; ?>;">
                        Solde Restant Dû :
                    </th>
                    <td style="font-weight: bold; color: <?php echo (((float)$sale['total_amount'] - (float)($sale['paid_amount'] ?? 0)) > 0.009) ? 'red' : 'green'; ?>;">
                        <?php echo htmlspecialchars(number_format((float)$sale['total_amount'] - (float)($sale['paid_amount'] ?? 0), 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?>
                    </td>
                </tr>
                <?php if ($sale['payment_type'] === 'immediate' && $sale['payment_status'] === 'paid'): ?>
                    <?php if (isset($sale['amount_tendered']) && $sale['amount_tendered'] !== null): ?>
                    <tr>
                        <th>Montant Versé :</th>
                        <td><?php echo htmlspecialchars(number_format((float)$sale['amount_tendered'], 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (isset($sale['change_due']) && $sale['change_due'] !== null): ?>
                    <tr>
                        <th>Monnaie Rendue :</th>
                        <td><?php echo htmlspecialchars(number_format((float)$sale['change_due'], 2, ',', ' ')) . ' ' . APP_CURRENCY_SYMBOL; ?></td>
                    </tr>
                    <?php endif; ?>
                <?php endif; ?>
            </table>
        </div>

        <div class="notes-section">
            <?php if (!empty($sale['notes'])): ?>
                <p><strong>Remarques :</strong><br><?php echo nl2br(htmlspecialchars($sale['notes'])); ?></p>
            <?php endif; ?>
            <p>Merci de votre achat !</p>
            <p>Conditions de paiement : ...</p>
        </div>
         <div class="no-print" style="text-align:center; margin-top:20px;">
            <button onclick="window.print();">Imprimer cette facture</button>
            <button onclick="window.close();">Fermer</button>
        </div>
    </div>
</body>
</html>
