<?php

require_once ROOT_PATH . '/core/Controller.php';

class FractioningController extends Controller {

    private $productModel;
    private $unitModel;
    // StockMovementModel is used by ProductModel->updateStock

    public function __construct() {
        parent::__construct();
        $this->productModel = $this->loadModel('Product');
        $this->unitModel = $this->loadModel('Unit');
    }

    /**
     * Displays the form for product fractioning.
     * Also serves as the index page for this module.
     */
    public function index() {
        $products = $this->productModel->getAll(); // Contains base unit info
        $allUnits = $this->unitModel->getAll(); // For general unit reference if needed by JS

        $productUnitsMap = [];
        foreach ($products as $product) {
            // getUnitsForProduct returns array of units including id, name, symbol, conversion_factor
            $productUnitsMap[$product['id']] = $this->productModel->getUnitsForProduct($product['id']);
        }

        $this->renderView('fractioning/create', [
            'title' => 'Fractionner un Produit',
            'products' => $products,
            'allUnits' => $allUnits, // Could be useful for JS if a product somehow has no units in map
            'productUnitsMap' => $productUnitsMap, // Key data for dynamic dropdowns
            'data' => [], // For form repopulation on error
            'errors' => []
        ]);
    }

    /**
     * Processes the product fractioning request.
     */
    public function process() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /index.php?url=fractioning/index");
            exit;
        }

        $sourceProductId = $_POST['source_product_id'] ?? null;
        $sourceUnitId = $_POST['source_unit_id'] ?? null;
        $sourceQuantityToFraction = isset($_POST['source_quantity_to_fraction']) && is_numeric($_POST['source_quantity_to_fraction']) ? (float)$_POST['source_quantity_to_fraction'] : 0;

        // For V1, target_product_id is the same as source_product_id
        $targetProductId = $sourceProductId;
        $targetUnitId = $_POST['target_unit_id'] ?? null;

        // This field determines how many target units are created from ONE source unit.
        // Ex: 1 Carton (source_unit_id) of Product X gives 10 Boxes (target_unit_id) of Product X.
        // $targetQuantityPerSourceUnit = isset($_POST['target_quantity_per_source_unit']) && is_numeric($_POST['target_quantity_per_source_unit']) ? (float)$_POST['target_quantity_per_source_unit'] : 0;
        // For now, we will calculate the target quantity based on conversion factors only.
        // The form will effectively define "I take X of source_unit, and I want to get Y of target_unit".
        // The system then calculates how many target_units this corresponds to.

        $errors = [];

        // Validation
        if (empty($sourceProductId)) $errors['source_product_id'] = "Produit source requis.";
        if (empty($sourceUnitId)) $errors['source_unit_id'] = "Unité source requise.";
        if ($sourceQuantityToFraction <= 0) $errors['source_quantity_to_fraction'] = "Quantité à fractionner doit être positive.";
        if (empty($targetUnitId)) $errors['target_unit_id'] = "Unité cible requise.";
        // if ($targetQuantityPerSourceUnit <= 0) $errors['target_quantity_per_source_unit'] = "Quantité cible par unité source doit être positive.";

        $sourceProduct = null;
        if ($sourceProductId) {
            $sourceProduct = $this->productModel->getById($sourceProductId);
            if (!$sourceProduct) $errors['source_product_id'] = "Produit source non trouvé.";
            else if (!$this->productModel->isUnitValidForProduct($sourceProductId, $sourceUnitId)) {
                 $errors['source_unit_id'] = "Unité source invalide pour le produit source.";
            }
        }

        $targetProduct = $sourceProduct; // V1: target is same as source
        if ($targetProductId && !$this->productModel->isUnitValidForProduct($targetProductId, $targetUnitId)) {
            $errors['target_unit_id'] = "Unité cible invalide pour le produit cible.";
        }

        // Check stock availability
        $sourceQtyInBaseUnit = 0;
        if ($sourceProduct && $sourceUnitId && $sourceQuantityToFraction > 0) {
            $sourceProductUnits = $this->productModel->getUnitsForProduct($sourceProductId);
            $sourceConversionFactor = null;
            foreach($sourceProductUnits as $pu) {
                if ($pu['unit_id'] == $sourceUnitId) {
                    $sourceConversionFactor = (float)$pu['conversion_factor_to_base_unit'];
                    break;
                }
            }
            if ($sourceConversionFactor === null) {
                $errors['source_unit_id'] = "Facteur de conversion introuvable pour l'unité source.";
            } else {
                $sourceQtyInBaseUnit = $sourceQuantityToFraction * $sourceConversionFactor;
                if ($sourceProduct['quantity_in_stock'] < $sourceQtyInBaseUnit) {
                    $errors['source_quantity_to_fraction'] = "Stock insuffisant. Stock actuel: " . ($sourceProduct['quantity_in_stock'] / $sourceConversionFactor) . " " . ($sourceProductUnits[array_search($sourceUnitId, array_column($sourceProductUnits, 'unit_id'))]['name'] ?? 'unité(s) source') . ". Nécessaire: " . $sourceQuantityToFraction;
                }
            }
        }

        // Calculate target quantity
        $calculatedTargetQuantityInTargetUnit = 0;
        if(empty($errors) && $targetProduct && $targetUnitId) {
            $targetProductUnits = $this->productModel->getUnitsForProduct($targetProductId);
            $targetConversionFactor = null;
            foreach($targetProductUnits as $pu){
                if($pu['unit_id'] == $targetUnitId){
                    $targetConversionFactor = (float)$pu['conversion_factor_to_base_unit'];
                    break;
                }
            }
            if($targetConversionFactor === null || $targetConversionFactor == 0){
                $errors['target_unit_id'] = "Facteur de conversion introuvable ou invalide pour l'unité cible.";
            } else {
                // Quantity to decrease from source (in base unit) is $sourceQtyInBaseUnit
                // This same quantity in base unit will be added to target (since target product is same as source)
                // So, we need to convert this $sourceQtyInBaseUnit to the target_unit_id
                $calculatedTargetQuantityInTargetUnit = $sourceQtyInBaseUnit / $targetConversionFactor;
            }
        }


        if (!empty($errors)) {
            $products = $this->productModel->getAll();
            $allUnits = $this->unitModel->getAll();
            $productUnitsMap = [];
            foreach ($products as $product) {
                $productUnitsMap[$product['id']] = $this->productModel->getUnitsForProduct($product['id']);
            }
            $this->renderView('fractioning/create', [
                'title' => 'Fractionner un Produit',
                'products' => $products,
                'allUnits' => $allUnits,
                'productUnitsMap' => $productUnitsMap,
                'data' => $_POST, // Repopulate form with submitted data
                'errors' => $errors
            ]);
            return;
        }

        // Start Transaction
        $this->productModel->getPdo()->beginTransaction(); // Access PDO instance from one of the models

        try {
            // 1. Decrease stock of source product
            $notesSource = "Fractionnement: sortie de {$sourceQuantityToFraction} " . ($this->unitModel->getById($sourceUnitId)['symbol'] ?? '');
            $stockUpdateSource = $this->productModel->updateStock(
                $sourceProductId,
                'split_out', // movementType
                -$sourceQuantityToFraction, // NEGATIVE quantityInTransactionUnit
                $sourceUnitId,
                null, null, $notesSource
            );

            if (!$stockUpdateSource) {
                throw new Exception("Échec de la mise à jour du stock source.");
            }

            // 2. Increase stock of target product
            $notesTarget = "Fractionnement: entrée de {$calculatedTargetQuantityInTargetUnit} " . ($this->unitModel->getById($targetUnitId)['symbol'] ?? '');
            $stockUpdateTarget = $this->productModel->updateStock(
                $targetProductId,
                'split_in', // movementType
                $calculatedTargetQuantityInTargetUnit, // POSITIVE quantityInTransactionUnit
                $targetUnitId,
                null, null, $notesTarget
            );

            if (!$stockUpdateTarget) {
                throw new Exception("Échec de la mise à jour du stock cible.");
            }

            $this->productModel->getPdo()->commit();
            header("Location: /index.php?url=fractioning/index&status=success&from_qty={$sourceQuantityToFraction}&from_unit={$sourceUnitId}&to_qty={$calculatedTargetQuantityInTargetUnit}&to_unit={$targetUnitId}&prod_id={$sourceProductId}");
            exit;

        } catch (Exception $e) {
            $this->productModel->getPdo()->rollBack();
            error_log("Fractioning error: " . $e->getMessage());

            $products = $this->productModel->getAll();
            $allUnits = $this->unitModel->getAll();
            $productUnitsMap = [];
            foreach ($products as $product) {
                $productUnitsMap[$product['id']] = $this->productModel->getUnitsForProduct($product['id']);
            }
            $this->renderView('fractioning/create', [
                'title' => 'Fractionner un Produit',
                'products' => $products,
                'allUnits' => $allUnits,
                'productUnitsMap' => $productUnitsMap,
                'data' => $_POST,
                'errors' => ['general' => "Erreur lors du fractionnement: " . $e->getMessage()]
            ]);
        }
    }
}

?>
