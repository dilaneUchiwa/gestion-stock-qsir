<?php

require_once ROOT_PATH . '/core/Controller.php';

class StockController extends Controller {

    private $productModel;
    private $stockMovementModel;

    public function __construct() {
        parent::__construct();
        $this->productModel = $this->loadModel('Product');
        $this->stockMovementModel = $this->loadModel('StockMovement');
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
        $this->renderView('stock/index', [
            'products' => $products,
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
        // This would typically involve:
        // 1. A form to select product, type of adjustment (in/out), quantity, notes.
        // 2. Validation.
        // 3. Calling ProductModel->updateStock() with appropriate parameters, e.g.:
        //    $productModel->updateStock($productId, $quantity, 'adjustment_in', null, 'stock_adjustments', $notes);
        // For now, just a placeholder view or redirect.
        $products = $this->productModel->getAll();
        $this->renderView('stock/create_adjustment', [
            'products' => $products,
            'adjustmentTypes' => ['adjustment_in', 'adjustment_out'], // Example types
            'title' => 'Créer un ajustement de stock'
        ]);
    }

    public function store_adjustment() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $productId = $_POST['product_id'] ?? null;
            $adjustmentType = $_POST['adjustment_type'] ?? null; // 'adjustment_in' or 'adjustment_out'
            $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
            $notes = $_POST['notes'] ?? '';

            $errors = [];
            if (empty($productId)) $errors['product_id'] = "Le produit est requis.";
            if (empty($adjustmentType) || !in_array($adjustmentType, ['adjustment_in', 'adjustment_out'])) {
                $errors['adjustment_type'] = "Un type d'ajustement valide est requis.";
            }
            if ($quantity <= 0) $errors['quantity'] = "La quantité doit être un nombre positif.";

            $product = $this->productModel->getById($productId);
            if (!$product) $errors['product_id'] = "Le produit sélectionné n'a pas été trouvé.";

            if ($adjustmentType === 'adjustment_out' && $product && $product['quantity_in_stock'] < $quantity) {
                $errors['quantity'] = "La quantité d'ajustement ({$quantity}) dépasse le stock actuel ({$product['quantity_in_stock']}) pour le produit '{$product['name']}'.";
            }

            if (!empty($errors)) {
                $products = $this->productModel->getAll();
                $this->renderView('stock/create_adjustment', [
                    'products' => $products,
                    'adjustmentTypes' => ['adjustment_in', 'adjustment_out'],
                    'errors' => $errors,
                    'data' => $_POST,
                    'title' => 'Créer un ajustement de stock'
                ]);
                return;
            }

            $quantityChange = ($adjustmentType === 'adjustment_in') ? $quantity : -$quantity;

            if ($this->productModel->updateStock($productId, $quantityChange, $adjustmentType, null, 'stock_adjustments', $notes)) {
                header("Location: /index.php?url=stock/history/{$productId}&status=adjustment_success");
                exit;
            } else {
                $errors['general'] = "Échec de la création de l'ajustement de stock.";
                 $products = $this->productModel->getAll();
                $this->renderView('stock/create_adjustment', [
                    'products' => $products,
                    'adjustmentTypes' => ['adjustment_in', 'adjustment_out'],
                    'errors' => $errors,
                    'data' => $_POST,
                    'title' => 'Créer un ajustement de stock'
                ]);
            }
        } else {
            header("Location: /index.php?url=stock/create_adjustment");
            exit;
        }
    }

}
?>
