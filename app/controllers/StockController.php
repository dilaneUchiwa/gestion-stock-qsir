<?php

require_once ROOT_PATH . '/core/Controller.php';

class StockController extends Controller {

    private $productModel;
    private $stockMovementModel;
    private $unitModel;

    public function __construct() {
        parent::__construct();
        require_once ROOT_PATH . '/app/models/Unit.php'; // Ensure Unit model is available
        $this->productModel = $this->loadModel('Product');
        $this->stockMovementModel = $this->loadModel('StockMovement');
        $this->unitModel = $this->loadModel('Unit');
    }

    /**
     * Displays an overview of product stocks.
     * Reuses product listing logic for now.
     */
    public function index() {
        $products = $this->productModel->getAll(); // This already gets quantity_in_stock
        // Optionally, for each product, you could also show calculated stock
        // foreach($products as &$product) {
        //    $product['calculated_stock'] = $this->stockMovementModel->getCurrentStockCalculated($product['id']);
        // }

        $productUnitsMap = [];
        if ($products) { // Ensure $products is not empty before iterating
            foreach ($products as $product) {
                $productUnitsMap[$product['id']] = $this->productModel->getUnitsForProduct($product['id']);
            }
        }

        $this->renderView('stock/index', [
            'products' => $products,
            'productUnitsMap' => $productUnitsMap,
            'title' => 'Aperçu du stock'
        ]);
    }

    /**
     * Displays the stock movement history for a specific product.
     * @param int $productId
     */
    public function history($productId) {
        $product = $this->productModel->getById($productId);
        if (!$product) {
            $this->renderView('errors/404', ['message' => "Produit avec l'ID {$productId} non trouvé."]);
            return;
        }

        // Date range filtering (example, can be extended with form inputs)
        $dateRange = null;
        if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
            $dateRange = [
                'start_date' => $_GET['start_date'],
                'end_date' => $_GET['end_date']
            ];
        }

        $movements = $this->stockMovementModel->getMovementsByProduct($productId, $dateRange);
        $calculatedStock = $this->stockMovementModel->getCurrentStockCalculated($productId);

        $this->renderView('stock/history', [
            'product' => $product,
            'movements' => $movements,
            'calculatedStock' => $calculatedStock, // To compare with product.quantity_in_stock
            'dateRange' => $dateRange, // Pass to view for display or form repopulation
            'title' => 'Historique des mouvements de stock pour ' . htmlspecialchars($product['name'])
        ]);
    }

    /**
     * Placeholder for stock adjustment functionality.
     * For creating manual stock adjustments (adjustment_in, adjustment_out).
     */
    public function create_adjustment() {
        $products = $this->productModel->getAll();
        $allUnits = $this->unitModel->getAll(); // Fetch all units
        $adjustmentTypes = [
            'initial_stock' => 'Stock Initial',
            'adjustment_in' => 'Ajustement Positif (Entrée)',
            'adjustment_out' => 'Ajustement Négatif (Sortie)'
        ];

        $this->renderView('stock/create_adjustment', [
            'products' => $products,
            'allUnits' => $allUnits,
            'adjustmentTypes' => $adjustmentTypes,
            'title' => 'Effectuer un ajustement de stock / Stock Initial'
        ]);
    }

    public function store_adjustment() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $productId = $_POST['product_id'] ?? null;
            $unitId = $_POST['unit_id'] ?? null;
            $adjustmentTypeKey = $_POST['adjustment_type'] ?? null;
            $quantityInput = isset($_POST['quantity']) ? (float)$_POST['quantity'] : 0;
            $notes = $_POST['notes'] ?? '';

            $errors = [];
            $adjustmentTypes = [
                'initial_stock' => 'Stock Initial',
                'adjustment_in' => 'Ajustement Positif (Entrée)',
                'adjustment_out' => 'Ajustement Négatif (Sortie)'
            ];

            if (empty($productId)) $errors['product_id'] = "Le produit est requis.";
            if (empty($unitId)) $errors['unit_id'] = "L'unité de mesure est requise.";
            if (empty($adjustmentTypeKey) || !array_key_exists($adjustmentTypeKey, $adjustmentTypes)) {
                $errors['adjustment_type'] = "Un type d'ajustement valide est requis.";
            }
            if ($quantityInput <= 0) $errors['quantity'] = "La quantité doit être un nombre positif.";

            $product = null;
            if ($productId) {
                $product = $this->productModel->getById($productId);
                if (!$product) {
                    $errors['product_id'] = "Le produit sélectionné n'a pas été trouvé.";
                }
            }

            if ($productId && $unitId && !$this->productModel->isUnitValidForProduct((int)$productId, (int)$unitId)) {
                $errors['unit_id'] = "L'unité sélectionnée n'est pas valide pour le produit choisi.";
            }

            $movementType = '';
            $quantityChange = 0;

            if (empty($errors)) { // Proceed only if basic validations pass
                switch ($adjustmentTypeKey) {
                    case 'initial_stock':
                        // For initial stock, check if stock already exists for this product/unit.
                        // This check is inside the `if (empty($errors))` block, so $productId and $unitId are valid.
                        $existingStock = $this->productModel->getStock((int)$productId, (int)$unitId);
                        if ($existingStock > 0) {
                           $errors['initial_stock'] = "Le stock initial pour ce produit et cette unité a déjà été défini (quantité: {$existingStock}). Utilisez un ajustement si vous souhaitez modifier le stock existant.";
                        } else {
                            // Only set movement type and quantity if no error from above check
                            $movementType = 'initial_stock';
                            $quantityChange = $quantityInput;
                        }
                        break;
                    case 'adjustment_in':
                        $movementType = 'adjustment_in';
                        $quantityChange = $quantityInput;
                        break;
                    case 'adjustment_out':
                        $movementType = 'adjustment_out';
                        $quantityChange = -$quantityInput; // Negative for outgoing stock

                        $currentStockInUnit = $this->productModel->getStock((int)$productId, (int)$unitId);
                        if ($currentStockInUnit < $quantityInput) {
                             $errors['quantity'] = "Quantité à sortir ({$quantityInput}) insuffisante. Stock actuel pour cette unité: {$currentStockInUnit}.";
                        }
                        break;
                    default: // Should be caught by array_key_exists, but as a safeguard
                        $errors['adjustment_type'] = "Type d'ajustement non reconnu.";
                }
            }


            if (!empty($errors)) {
                $products = $this->productModel->getAll();
                $allUnits = $this->unitModel->getAll();
                $this->renderView('stock/create_adjustment', [
                    'products' => $products,
                    'allUnits' => $allUnits,
                    'adjustmentTypes' => $adjustmentTypes,
                    'errors' => $errors,
                    'data' => $_POST, // Repopulate form with submitted data
                    'title' => 'Effectuer un ajustement de stock / Stock Initial'
                ]);
                return;
            }

            // Call the new updateStockQuantity method
            $success = $this->productModel->updateStockQuantity(
                (int)$productId,
                (int)$unitId,
                $quantityChange, // This is already signed correctly
                $movementType,
                null, // related_document_id for adjustments is null
                'stock_adjustments', // related_document_type
                $notes
            );

            if ($success) {
                header("Location: /index.php?url=stock/history/{$productId}&status=adjustment_success");
                exit;
            } else {
                $errors['general'] = "Échec de la création de l'ajustement de stock. Vérifiez les logs pour plus de détails.";
                $products = $this->productModel->getAll();
                $allUnits = $this->unitModel->getAll();
                $this->renderView('stock/create_adjustment', [
                    'products' => $products,
                    'allUnits' => $allUnits,
                    'adjustmentTypes' => $adjustmentTypes,
                    'errors' => $errors,
                    'data' => $_POST,
                    'title' => 'Effectuer un ajustement de stock / Stock Initial'
                ]);
            }
        } else {
            header("Location: /index.php?url=stock/create_adjustment");
            exit;
        }
    }

    /**
     * Fetches configured units for a given product and returns them as JSON.
     * Expected to be called via AJAX.
     * @param int $productId
     */
    public function get_product_units_json($productId) {
        header('Content-Type: application/json');
        if (empty($productId) || !is_numeric($productId)) {
            // It's better to set HTTP status code for errors
            http_response_code(400); // Bad Request
            echo json_encode(['error' => 'Invalid or missing product ID']);
            return;
        }

        $product = $this->productModel->getById((int)$productId);
        if (!$product) {
            http_response_code(404); // Not Found
            echo json_encode(['error' => 'Product not found']);
            return;
        }

        // getUnitsForProduct returns an array of units: [ ['unit_id', 'name', 'symbol', 'conversion_factor_to_base_unit'], ... ]
        $units = $this->productModel->getUnitsForProduct((int)$productId);
        if ($units === false || $units === null) { // Model might return false on error
            http_response_code(500); // Internal Server Error
            echo json_encode(['error' => 'Failed to retrieve units for product']);
            return;
        }

        echo json_encode($units);
    }
}
?>
