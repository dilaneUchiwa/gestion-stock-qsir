<?php
// $title and $products (enriched) are passed from ReportController@current_stock
$pageTitle = $title ?? "Rapport d'État du Stock Actuel";
?>

<h2><?php echo htmlspecialchars($pageTitle); ?></h2>

<div class="table-responsive-container" style="margin-top: 20px;">
    <?php if (empty($products)): ?>
        <p>Aucun produit trouvé correspondant aux critères actuels.</p>
    <?php else: ?>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID Produit</th>
                <th>Nom du Produit</th>
                <th>Catégorie</th>
                <th style="text-align: right;">Stock (Unité de Base)</th>
                <th>Unité de Base</th>
                <th>Stock (Autres Unités Configurées)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
            <tr>
                <td><?php echo htmlspecialchars($product['id']); ?></td>
                <td>
                    <a href="index.php?url=products/show/<?php echo $product['id']; ?>">
                        <?php echo htmlspecialchars($product['name']); ?>
                    </a>
                </td>
                <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                <td style="text-align: right; font-weight: bold;">
                    <?php
                    $baseUnitStock = '0.00'; // Default
                    if (isset($product['stock_levels']) && is_array($product['stock_levels'])) {
                        foreach ($product['stock_levels'] as $stock_level) {
                            if ($stock_level['unit_id'] == $product['base_unit_id']) {
                                $baseUnitStock = number_format((float)($stock_level['quantity'] ?? 0), 2, ',', ' ');
                                break;
                            }
                        }
                    }
                    echo htmlspecialchars($baseUnitStock);
                    ?>
                </td>
                <td><?php echo htmlspecialchars($product['base_unit_symbol'] ?? $product['base_unit_name'] ?? 'N/A'); ?></td>
                <td>
                    <?php if (!empty($product['stock_levels']) && count($product['stock_levels']) > 0): ?>
                        <ul>
                            <?php foreach ($product['stock_levels'] as $stock_level): ?>
                                <?php if ($stock_level['unit_id'] != $product['base_unit_id']): ?>
                                    <li>
                                        <?php echo htmlspecialchars(number_format((float)($stock_level['quantity'] ?? 0), 2, ',', ' ')); ?>
                                        <?php echo htmlspecialchars($stock_level['unit_symbol'] ?? $stock_level['unit_name']); ?>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                        <?php
                        // Check if only base unit has stock or is configured among stock_levels
                        $onlyBaseUnitInStockLevels = true;
                        if (is_array($product['stock_levels'])) {
                            foreach ($product['stock_levels'] as $sl) {
                                if ($sl['unit_id'] != $product['base_unit_id'] && (float)($sl['quantity'] ?? 0) > 0) { // Check if any stock > 0 for alt units
                                    $onlyBaseUnitInStockLevels = false;
                                    break;
                                }
                                 // If an alt unit exists in stock_levels (even with 0 qty) it means it's configured for stock.
                                 if ($sl['unit_id'] != $product['base_unit_id']) {
                                    $onlyBaseUnitInStockLevels = false; // Found an alternative unit configured for stock
                                    break;
                                 }
                            }
                        }
                        if ($onlyBaseUnitInStockLevels && count($product['stock_levels']) <=1 && !empty($product['base_unit_name']) ): ?>
                         <small><em>Uniquement l'unité de base a du stock ou est configurée pour le stock.</em></small>
                        <?php elseif (empty($product['stock_levels'])): ?>
                             <small><em>Aucun stock enregistré pour ce produit.</em></small>
                        <?php endif; ?>
                    <?php else: ?>
                        <small><em>Aucun stock enregistré pour ce produit.</em></small>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<style>
    /* Basic styling for the report table, can be moved to a global CSS */
    .table-responsive-container {
        overflow-x: auto;
    }
    .table {
        width: 100%;
        margin-bottom: 1rem;
        color: #212529;
        border-collapse: collapse;
    }
    .table th,
    .table td {
        padding: 0.75rem;
        vertical-align: top;
        border-top: 1px solid #dee2e6;
    }
    .table thead th {
        vertical-align: bottom;
        border-bottom: 2px solid #dee2e6;
        background-color: #f8f9fa;
    }
    .table tbody + tbody {
        border-top: 2px solid #dee2e6;
    }
    .table-bordered {
        border: 1px solid #dee2e6;
    }
    .table-bordered th,
    .table-bordered td {
        border: 1px solid #dee2e6;
    }
    .table-striped tbody tr:nth-of-type(odd) {
        background-color: rgba(0, 0, 0, 0.05);
    }
    .table td ul {
        padding-left: 15px;
        margin-bottom: 0;
    }
     .alert {
        padding: 0.75rem 1.25rem;
        margin-bottom: 1rem;
        border: 1px solid transparent;
        border-radius: 0.25rem;
    }
    .alert-info {
        color: #0c5460;
        background-color: #d1ecf1;
        border-color: #bee5eb;
    }
</style>
