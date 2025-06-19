<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($title ?? "Bon de Livraison"); ?></title>
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
        .delivery-details-main { margin-top: 20px; margin-bottom: 30px; text-align: center; }
        .delivery-details-main p { margin: 3px 0; }
        .notes-section { margin-top: 30px; font-size: 10px; }
        .signatures-section { margin-top: 50px; overflow: auto; }
        .signature { float: left; width: 45%; text-align: center; padding-top: 30px; border-top: 1px solid #ccc; margin: 0 2.5%; }
        .footer { text-align: center; font-size: 9px; color: #777; margin-top: 30px; border-top: 1px solid #ccc; padding-top: 10px;}

        @media print {
            body { margin: 0; padding: 0; font-size: 10pt; background-color: #fff; }
            .container { width: 100%; margin: 0; padding: 10px; border: none; box-shadow: none; }
            .no-print { display: none !important; }
            table { font-size: 9pt; }
            th, td { padding: 5px; border: 1px solid #333; }
            .header-section, .signatures-section { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-section">
            <div class="company-details">
                <h2>ENTREPRISE XYZ (Réceptionnaire)</h2>
                <p>123 Rue de la Facture<br>75000 Paris, France<br>Tél : 01 23 45 67 89</p>
            </div>
            <div class="supplier-details">
                <h3>Fournisseur</h3>
                <p>
                    <strong><?php echo htmlspecialchars($delivery['supplier_name'] ?? 'N/A'); ?></strong><br>
                    <?php // Assuming supplier address details would be part of $delivery if fetched
                          echo htmlspecialchars($delivery['supplier_address'] ?? 'Adresse du fournisseur non fournie'); ?>
                </p>
            </div>
        </div>

        <div class="delivery-details-main">
            <h1>BON DE LIVRAISON</h1>
            <p><strong>Numéro de Livraison :</strong> #BL-<?php echo htmlspecialchars($delivery['id']); ?></p>
            <p><strong>Date de Livraison :</strong> <?php echo htmlspecialchars(date('d/m/Y', strtotime($delivery['delivery_date']))); ?></p>
            <?php if (!empty($delivery['purchase_order_id'])): ?>
                <p><strong>Référence Bon de Commande :</strong> #BC-<?php echo htmlspecialchars($delivery['purchase_order_id']); ?></p>
            <?php endif; ?>
            <p><strong>Type :</strong> <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $delivery['type']))); ?></p>
        </div>

        <h3>Articles Reçus</h3>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Référence Produit</th>
                    <th>Désignation</th>
                    <th style="text-align:right;">Quantité Reçue</th>
                    <th>Unité</th>
                    <?php if (!empty($delivery['items']) && isset($delivery['items'][0]['original_quantity_ordered'])): // Show PO Qty if available ?>
                        <th style="text-align:right;">Qté Commandée (BC)</th>
                        <th>Unité (BC)</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $itemNumber = 1;
                if (!empty($delivery['items'])):
                    foreach ($delivery['items'] as $item):
                ?>
                <tr>
                    <td><?php echo $itemNumber++; ?></td>
                    <td>PROD-<?php echo htmlspecialchars($item['product_id']); ?></td>
                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                    <td style="text-align:right;"><?php echo htmlspecialchars(number_format((float)$item['quantity_received'], 2, ',', ' ')); ?></td>
                    <td><?php echo htmlspecialchars($item['unit_symbol'] ?? $item['unit_name'] ?? ''); ?></td>
                    <?php if (isset($item['original_quantity_ordered'])): ?>
                        <td style="text-align:right;"><?php echo htmlspecialchars(number_format((float)$item['original_quantity_ordered'], 2, ',', ' ')); ?></td>
                        <td><?php echo htmlspecialchars($item['po_unit_symbol'] ?? $item['po_unit_name'] ?? ''); ?></td>
                    <?php endif; ?>
                </tr>
                <?php
                    endforeach;
                endif;
                ?>
            </tbody>
        </table>

        <?php if (!empty($delivery['notes'])): ?>
        <div class="notes-section">
            <p><strong>Remarques :</strong><br><?php echo nl2br(htmlspecialchars($delivery['notes'])); ?></p>
        </div>
        <?php endif; ?>

        <div class="signatures-section">
            <div class="signature">
                <p>Signature Fournisseur / Transporteur :</p>
                <br><br><br>
                <p>_________________________</p>
            </div>
            <div class="signature">
                <p>Signature Réceptionnaire (Entreprise XYZ) :</p>
                <br><br><br>
                <p>_________________________</p>
            </div>
        </div>

        <div class="footer">
            <p>Bon de livraison généré le <?php echo date('d/m/Y H:i'); ?>.</p>
        </div>

         <div class="no-prinWt" style="text-align:center; margin-top:20px;">
            <button onclick="window.print();">Imprimer ce Bon de Livraison</button>
            <button onclick="window.close();">Fermer</button>
        </div>
    </div>
</body>
</html>
