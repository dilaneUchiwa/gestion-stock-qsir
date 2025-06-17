<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($title ?? "Bon de Commande"); ?></title>
    <link rel="stylesheet" href="/public/css/print_style.css"> <!-- Added link -->
    <style>
        /* View-specific styles */
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; font-size: 12px; }
        .container { width: 90%; margin: 20px auto; padding: 20px; }
        h1, h2, h3 { text-align: center; margin-top: 5px; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #666; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .header-section { margin-bottom: 30px; overflow: auto; /* Clearfix */ }
        .company-details, .supplier-details { width: 48%; float: left; }
        .supplier-details { float: right; text-align: right; }
        .po-details-main { margin-top: 20px; margin-bottom: 30px; text-align: center; }
        .po-details-main p { margin: 3px 0; }
        .totals { text-align: right; margin-top: 20px; }
        .totals th, .totals td { border: none; text-align: right; padding: 4px 0; }
        .notes-section, .terms-section { margin-top: 30px; border-top: 1px solid #eee; padding-top: 10px; font-size: 10px; }
        .footer { text-align: center; font-size: 9px; color: #777; margin-top: 30px; border-top: 1px solid #ccc; padding-top: 10px;}

        @media print {
            body { margin: 0; padding: 0; font-size: 10pt; background-color: #fff; }
            .container { width: 100%; margin: 0; padding: 10px; border: none; box-shadow: none; }
            .no-print { display: none !important; }
            table { font-size: 9pt; }
            th, td { padding: 5px; border: 1px solid #333; /* Darker borders for print */ }
            .header-section { page-break-after: avoid; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-section">
            <div class="company-details">
                <h2>ENTREPRISE XYZ (Demandeur)</h2>
                <p>123 Rue de la Facture<br>75000 Paris, France<br>Tél : 01 23 45 67 89<br>Email : achats@entreprise.xyz</p>
                <p>SIRET: 123 456 789 00010</p>
            </div>
            <div class="supplier-details">
                <h3>Fournisseur</h3>
                <p>
                    <strong><?php echo htmlspecialchars($purchaseOrder['supplier_name'] ?? 'N/A'); ?></strong><br>
                    <?php // Assuming supplier address details would be part of $purchaseOrder if fetched
                          // For now, this is a placeholder.
                          echo htmlspecialchars($purchaseOrder['supplier_address'] ?? 'Adresse du fournisseur non fournie'); ?><br>
                    <?php echo htmlspecialchars($purchaseOrder['supplier_contact'] ?? ''); ?>
                </p>
            </div>
        </div>

        <div class="po-details-main">
            <h1>BON DE COMMANDE</h1>
            <p><strong>Numéro de Commande :</strong> #BC-<?php echo htmlspecialchars($purchaseOrder['id']); ?></p>
            <p><strong>Date de Commande :</strong> <?php echo htmlspecialchars(date('d/m/Y', strtotime($purchaseOrder['order_date']))); ?></p>
            <p><strong>Date de Livraison Prévue :</strong> <?php echo htmlspecialchars(!empty($purchaseOrder['expected_delivery_date']) ? date('d/m/Y', strtotime($purchaseOrder['expected_delivery_date'])) : 'N/A'); ?></p>
            <p><strong>Statut :</strong> <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $purchaseOrder['status']))); ?></p>
        </div>

        <h3>Articles Commandés</h3>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Référence Produit</th>
                    <th>Désignation</th>
                    <th style="text-align:right;">Quantité</th>
                    <th>Unité</th>
                    <th style="text-align:right;">Prix Unitaire HT</th>
                    <th style="text-align:right;">Sous-Total HT</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $itemNumber = 1;
                if (!empty($purchaseOrder['items'])):
                    foreach ($purchaseOrder['items'] as $item):
                ?>
                <tr>
                    <td><?php echo $itemNumber++; ?></td>
                    <td>PROD-<?php echo htmlspecialchars($item['product_id']); ?></td>
                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                    <td style="text-align:right;"><?php echo htmlspecialchars(number_format((float)$item['quantity_ordered'], 2, ',', ' ')); ?></td>
                    <td><?php echo htmlspecialchars($item['unit_symbol'] ?? $item['unit_name'] ?? ''); ?></td>
                    <td style="text-align:right;"><?php echo htmlspecialchars(number_format((float)$item['unit_price'], 2, ',', ' ')); ?> <?php echo APP_CURRENCY_SYMBOL; ?></td>
                    <td style="text-align:right;"><?php echo htmlspecialchars(number_format((float)$item['sub_total'], 2, ',', ' ')); ?> <?php echo APP_CURRENCY_SYMBOL; ?></td>
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
                    <th style="font-size: 1.2em;">Total Général HT :</th>
                    <td style="font-size: 1.2em; font-weight: bold;"><?php echo htmlspecialchars(number_format((float)($purchaseOrder['total_amount'] ?? 0), 2, ',', ' ')); ?> <?php echo APP_CURRENCY_SYMBOL; ?></td>
                </tr>
                <?php // Add VAT, Total TTC etc. if applicable and data is available ?>
            </table>
        </div>

        <?php if (!empty($purchaseOrder['notes'])): ?>
        <div class="notes-section">
            <p><strong>Remarques :</strong><br><?php echo nl2br(htmlspecialchars($purchaseOrder['notes'])); ?></p>
        </div>
        <?php endif; ?>

        <div class="terms-section">
            <p><strong>Conditions :</strong> Paiement à 30 jours nets. ...</p>
        </div>

        <div class="footer">
            <p>Bon de commande généré le <?php echo date('d/m/Y H:i'); ?> par le système ERP.</p>
        </div>

         <div class="no-print" style="text-align:center; margin-top:20px;">
            <button onclick="window.print();">Imprimer ce Bon de Commande</button>
            <button onclick="window.close();">Fermer</button>
        </div>
    </div>
</body>
</html>
