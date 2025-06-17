<?php
$pageTitle = $title ?? "Rapport des Mouvements de Stock";
$currentFilters = $currentFilters ?? [];
$productsForFilter = $productsForFilter ?? [];
$allMovementTypes = $allMovementTypes ?? [];
$movements = $movements ?? [];

// Helper for translating movement types (can be moved to a helper class/function later)
function translateMovementType($type) {
    $translations = [
        'in_delivery' => 'Entrée (Livraison Fournisseur)',
        'out_sale' => 'Sortie (Vente)',
        'adjustment_in' => 'Ajustement (Entrée)',
        'adjustment_out' => 'Ajustement (Sortie)',
        'split_in' => 'Fractionnement (Entrée)',
        'split_out' => 'Fractionnement (Sortie)',
        'initial_stock' => 'Stock Initial',
        'delivery_reversal' => 'Annulation Livraison',
        'sale_reversal' => 'Annulation Vente'
    ];
    return $translations[$type] ?? ucfirst(str_replace('_', ' ', $type));
}
?>

<h2><?php echo htmlspecialchars($pageTitle); ?></h2>

<form action="index.php" method="GET" class="filter-form">
    <input type="hidden" name="url" value="report/stock_movements_report">

    <div class="form-row">
        <div class="form-group col">
            <label for="start_date">Date de début</label>
            <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($currentFilters['start_date'] ?? ''); ?>" class="form-control">
        </div>
        <div class="form-group col">
            <label for="end_date">Date de fin</label>
            <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($currentFilters['end_date'] ?? ''); ?>" class="form-control">
        </div>
        <div class="form-group col">
            <label for="product_id">Produit</label>
            <select name="product_id" id="product_id" class="form-control">
                <option value="">Tous les produits</option>
                <?php foreach ($productsForFilter as $product): ?>
                    <option value="<?php echo htmlspecialchars($product['id']); ?>" <?php echo (isset($currentFilters['product_id']) && $currentFilters['product_id'] == $product['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($product['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group col">
            <label for="movement_type">Type de Mouvement</label>
            <select name="movement_type" id="movement_type" class="form-control">
                <option value="">Tous les types</option>
                <?php foreach ($allMovementTypes as $type): ?>
                    <option value="<?php echo htmlspecialchars($type); ?>" <?php echo (isset($currentFilters['movement_type']) && $currentFilters['movement_type'] == $type) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(translateMovementType($type)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="form-group">
        <button type="submit" class="button">Filtrer</button>
        <a href="index.php?url=report/stock_movements_report" class="button-info">Réinitialiser</a>
    </div>
</form>

<div class="table-responsive-container" style="margin-top: 20px;">
    <?php if (empty($movements)): ?>
        <p>Aucun mouvement de stock trouvé pour les filtres sélectionnés.</p>
    <?php else: ?>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Date</th>
                <th>Produit</th>
                <th>Type</th>
                <th>Description / Document Lié / Notes</th>
                <th style="text-align: right;">Qté (Unit. Orig.)</th>
                <th>Unit. Orig.</th>
                <th style="text-align: right;">Qté (Unit. Base)</th>
                <th>Unit. Base</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($movements as $mov): ?>
            <tr>
                <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($mov['movement_date']))); ?></td>
                <td>
                    <a href="index.php?url=products/show/<?php echo $mov['product_id']; ?>">
                        <?php echo htmlspecialchars($mov['product_name']); ?>
                    </a>
                </td>
                <td><?php echo htmlspecialchars(translateMovementType($mov['type'])); ?></td>
                <td>
                    <?php
                        $description = '';
                        if (!empty($mov['related_document_type']) && !empty($mov['related_document_id'])) {
                            $docType = ucfirst(str_replace('_', ' ', $mov['related_document_type']));
                            // Basic linking (can be expanded)
                            $link = '#'; // Default link
                            if ($mov['related_document_type'] == 'sale_items' || $mov['related_document_type'] == 'sales') {
                                // To link to sale, need to fetch sale_id from sale_item_id if that's what is stored
                                // For now, just display.
                                $link = 'index.php?url=sale/show/' . $mov['related_document_id']; // This might be item ID, not sale ID
                                // A better approach would be to store sale_id directly or fetch it in the model.
                                // For now, we assume related_document_id for sales related movements IS the sale_id for simplicity of linking.
                                // If it's sale_items id, this link will be wrong.
                                // Based on current ProductModel->updateStock, it's the sale_item_id.
                                // For this report, a more complex lookup or different related_document_id storage strategy would be needed for direct links.
                                // For now, displaying type and ID.
                                $description .= "{$docType} #{$mov['related_document_id']}";
                            } elseif ($mov['related_document_type'] == 'delivery_items' || $mov['related_document_type'] == 'deliveries') {
                                // Similar logic for deliveries
                                $description .= "{$docType} #{$mov['related_document_id']}";
                            } else {
                                 $description .= "{$docType} #{$mov['related_document_id']}";
                            }
                        }
                        if (!empty($mov['notes'])) {
                            $description .= ($description ? ' - ' : '') . htmlspecialchars($mov['notes']);
                        }
                        echo $description ?: 'N/A';
                    ?>
                </td>
                <td style="text-align: right;">
                    <?php
                    if ($mov['original_unit_id'] && $mov['original_unit_id'] != $mov['base_unit_id'] && isset($mov['original_quantity'])) {
                        echo htmlspecialchars(number_format((float)$mov['original_quantity'], 3, ',', ' '));
                    } else {
                        echo '-'; // Was already in base unit or no original specified
                    }
                    ?>
                </td>
                <td>
                     <?php
                    if ($mov['original_unit_id'] && $mov['original_unit_id'] != $mov['base_unit_id'] && isset($mov['original_unit_symbol'])) {
                        echo htmlspecialchars($mov['original_unit_symbol']);
                    } else {
                        echo '-';
                    }
                    ?>
                </td>
                <td style="text-align: right; font-weight:bold;">
                    <?php echo htmlspecialchars(number_format((float)$mov['quantity'], 3, ',', ' ')); ?>
                </td>
                <td><?php echo htmlspecialchars($mov['base_unit_symbol']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<style>
    .filter-form .form-row { display: flex; flex-wrap: wrap; margin-right: -5px; margin-left: -5px; }
    .filter-form .form-group.col { flex-basis: 0; flex-grow: 1; max-width: 100%; padding-right: 5px; padding-left: 5px; }
    .filter-form .form-control { display: block; width: 100%; padding: 0.375rem 0.75rem; font-size: 1rem; line-height: 1.5; color: #495057; background-color: #fff; background-clip: padding-box; border: 1px solid #ced4da; border-radius: 0.25rem; }
    .filter-form .form-group { margin-bottom: 1rem; }
    /* Basic styling for the report table, can be moved to a global CSS */
    .table-responsive-container { overflow-x: auto; }
    .table { width: 100%; margin-bottom: 1rem; color: #212529; border-collapse: collapse; }
    .table th, .table td { padding: 0.75rem; vertical-align: top; border-top: 1px solid #dee2e6; }
    .table thead th { vertical-align: bottom; border-bottom: 2px solid #dee2e6; background-color: #f8f9fa; }
    .table tbody + tbody { border-top: 2px solid #dee2e6; }
    .table-bordered { border: 1px solid #dee2e6; }
    .table-bordered th, .table-bordered td { border: 1px solid #dee2e6; }
    .table-striped tbody tr:nth-of-type(odd) { background-color: rgba(0, 0, 0, 0.05); }
</style>
