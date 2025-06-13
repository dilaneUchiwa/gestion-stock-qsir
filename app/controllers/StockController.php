<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/Controller.php';

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
            'title' => 'Stock Overview'
        ]);
    }

    /**
     * Displays the stock movement history for a specific product.
     * @param int $productId
     */
    public function history($productId) {
        $product = $this->productModel->getById($productId);
        if (!$product) {
            $this->renderView('errors/404', ['message' => "Product with ID {$productId} not found."]);
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
            'title' => 'Stock Movement History for ' . htmlspecialchars($product['name'])
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
            'title' => 'Create Stock Adjustment'
        ]);
    }

    public function store_adjustment() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $productId = $_POST['product_id'] ?? null;
            $adjustmentType = $_POST['adjustment_type'] ?? null; // 'adjustment_in' or 'adjustment_out'
            $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
            $notes = $_POST['notes'] ?? '';

            $errors = [];
            if (empty($productId)) $errors['product_id'] = "Product is required.";
            if (empty($adjustmentType) || !in_array($adjustmentType, ['adjustment_in', 'adjustment_out'])) {
                $errors['adjustment_type'] = "Valid adjustment type is required.";
            }
            if ($quantity <= 0) $errors['quantity'] = "Quantity must be a positive number.";

            $product = $this->productModel->getById($productId);
            if (!$product) $errors['product_id'] = "Selected product not found.";

            if ($adjustmentType === 'adjustment_out' && $product && $product['quantity_in_stock'] < $quantity) {
                $errors['quantity'] = "Adjustment quantity ({$quantity}) exceeds current stock ({$product['quantity_in_stock']}) for product '{$product['name']}'.";
            }

            if (!empty($errors)) {
                $products = $this->productModel->getAll();
                $this->renderView('stock/create_adjustment', [
                    'products' => $products,
                    'adjustmentTypes' => ['adjustment_in', 'adjustment_out'],
                    'errors' => $errors,
                    'data' => $_POST,
                    'title' => 'Create Stock Adjustment'
                ]);
                return;
            }

            $quantityChange = ($adjustmentType === 'adjustment_in') ? $quantity : -$quantity;

            if ($this->productModel->updateStock($productId, $quantityChange, $adjustmentType, null, 'stock_adjustments', $notes)) {
                header("Location: /index.php?url=stock/history/{$productId}&status=adjustment_success");
                exit;
            } else {
                $errors['general'] = "Failed to create stock adjustment.";
                 $products = $this->productModel->getAll();
                $this->renderView('stock/create_adjustment', [
                    'products' => $products,
                    'adjustmentTypes' => ['adjustment_in', 'adjustment_out'],
                    'errors' => $errors,
                    'data' => $_POST,
                    'title' => 'Create Stock Adjustment'
                ]);
            }
        } else {
            header("Location: /index.php?url=stock/create_adjustment");
            exit;
        }
    }

}
?>
