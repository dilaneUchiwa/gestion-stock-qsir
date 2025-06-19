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

        // Basic Validation
        if (empty($sourceProductId)) $errors['source_product_id'] = "Produit source requis.";
        if (empty($sourceUnitId)) $errors['source_unit_id'] = "Unité source requise.";
        if ($sourceQuantityToFraction <= 0) $errors['source_quantity_to_fraction'] = "Quantité à fractionner doit être positive.";
        if (empty($targetUnitId)) $errors['target_unit_id'] = "Unité cible requise.";

        // For success message, we still need to calculate the target quantity.
        // This calculation is now duplicated here temporarily for the message.
        // Ideally, fractionProduct could return this, or it's accepted that the message might not have it
        // if the controller is to be fully relieved of this calculation.
        // For now, let's keep the calculation for the success message.
        $calculatedTargetQuantityInTargetUnit = 0;
        if (empty($errors)) {
            $sourceProduct = $this->productModel->getById($sourceProductId);
            $sourceProductUnits = $this->productModel->getUnitsForProduct($sourceProductId);
            $targetProductUnits = $this->productModel->getUnitsForProduct($targetProductId); // Assuming targetProductId = sourceProductId

            $sourceConversionFactor = null;
            foreach($sourceProductUnits as $pu) {
                if ($pu['unit_id'] == $sourceUnitId) {
                    $sourceConversionFactor = (float)$pu['conversion_factor_to_base_unit'];
                    break;
                }
            }

            $targetConversionFactor = null;
            foreach($targetProductUnits as $pu){
                if($pu['unit_id'] == $targetUnitId){
                    $targetConversionFactor = (float)$pu['conversion_factor_to_base_unit'];
                    break;
                }
            }

            if ($sourceConversionFactor === null || $sourceConversionFactor <= 0) {
                $errors['source_unit_id'] = "Facteur de conversion invalide ou introuvable pour l'unité source.";
            }
            if ($targetConversionFactor === null || $targetConversionFactor <= 0) {
                $errors['target_unit_id'] = "Facteur de conversion invalide ou introuvable pour l'unité cible.";
            }

            if (empty($errors) && $sourceProduct) {
                 $sourceQtyInBaseUnit = $sourceQuantityToFraction * $sourceConversionFactor;
                 $calculatedTargetQuantityInTargetUnit = $sourceQtyInBaseUnit / $targetConversionFactor;
                 if ($calculatedTargetQuantityInTargetUnit <= 0) {
                     $errors['general'] = "La quantité cible calculée est nulle ou négative. Vérifiez les unités et facteurs.";
                 }
            } elseif (!$sourceProduct && empty($errors['source_product_id'])) {
                 $errors['source_product_id'] = "Produit source non trouvé (pour calcul quantité cible).";
            }
        }

        if (!empty($errors)) {
            // Common setup for rendering form with errors
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
                'errors' => $errors
            ]);
            return;
        }

        // Call the new model method
        $result = $this->productModel->fractionProduct(
            (int)$sourceProductId,
            (int)$sourceUnitId,
            (float)$sourceQuantityToFraction,
            (int)$targetUnitId
        );

        if ($result === true) {
            // Fractionation was successful.
            // $calculatedTargetQuantityInTargetUnit should be available from the calculation block above.
            // Ensure source_unit_id and target_unit_id in POST are the IDs, not names/symbols for the message.
            // Fetch names/symbols if needed for a more descriptive message, or ensure they are passed if required.
            $sourceUnitName = $this->unitModel->getById($sourceUnitId)['name'] ?? $sourceUnitId;
            $targetUnitName = $this->unitModel->getById($targetUnitId)['name'] ?? $targetUnitId;

            header("Location: /index.php?url=fractioning/index&status=success&from_qty=" . urlencode($_POST['source_quantity_to_fraction']) . "&from_unit_name=" . urlencode($sourceUnitName) . "&to_qty=" . urlencode(number_format($calculatedTargetQuantityInTargetUnit, 3, '.', '')) . "&to_unit_name=" . urlencode($targetUnitName) . "&prod_id=" . urlencode($_POST['source_product_id']));
            exit;
        } else {
            // An error message string was returned from fractionProduct
            $errors['general'] = $result;

            // Common setup for rendering form with errors
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
                'errors' => $errors
            ]);
        }
    }
}
?>
