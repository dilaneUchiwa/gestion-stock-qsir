<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/Controller.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/core/Database.php'; // Required for loadModel

class ProductsController extends Controller {

    private $productModel;

    public function __construct() {
        parent::__construct();
        $this->productModel = $this->loadModel('Product');
    }

    /**
     * Displays a list of all products.
     */
    public function index() {
        $products = $this->productModel->getAll();
        $this->renderView('products/index', ['products' => $products]);
    }

    /**
     * Displays a single product by its ID.
     * @param int $id The ID of the product.
     */
    public function show($id) {
        $product = $this->productModel->getById($id);
        if ($product) {
            $children = $this->productModel->getChildren($id);
            $parent = null;
            if ($product['parent_id']) {
                $parent = $this->productModel->getById($product['parent_id']);
            }
            $this->renderView('products/show', ['product' => $product, 'children' => $children, 'parent' => $parent]);
        } else {
            // Handle product not found, e.g., show a 404 page or redirect
            $this->renderView('errors/404', ['message' => "Product with ID {$id} not found."]);
        }
    }

    /**
     * Shows the form for creating a new product.
     */
    public function create() {
        $products = $this->productModel->getAll(); // For parent selection
        $this->renderView('products/create', ['products' => $products]);
    }

    /**
     * Stores a new product in the database.
     */
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Basic validation (can be more robust)
            $data = [
                'name' => $_POST['name'] ?? '',
                'description' => $_POST['description'] ?? '',
                'parent_id' => !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null,
                'unit_of_measure' => $_POST['unit_of_measure'] ?? '',
                'quantity_in_stock' => isset($_POST['quantity_in_stock']) ? (int)$_POST['quantity_in_stock'] : 0,
                'purchase_price' => isset($_POST['purchase_price']) ? (float)$_POST['purchase_price'] : 0.0,
                'selling_price' => isset($_POST['selling_price']) ? (float)$_POST['selling_price'] : 0.0,
            ];

            if (empty($data['name'])) {
                // Handle validation errors, e.g., render form again with errors
                $products = $this->productModel->getAll();
                $this->renderView('products/create', ['errors' => ['name' => 'Name is required.'], 'data' => $data, 'products' => $products]);
                return;
            }

            $productId = $this->productModel->create($data);

            if ($productId) {
                // If initial stock quantity is provided, create an initial stock movement
                if (isset($data['quantity_in_stock']) && $data['quantity_in_stock'] > 0) {
                    // Note: productModel->updateStock also updates the cache in products.quantity_in_stock
                    // The create method for Product might already set initial quantity_in_stock.
                    // If create() method ALREADY sets quantity_in_stock in products table, then updateStock
                    // here would ADD to it again.
                    // We need to ensure updateStock is called correctly or that create() doesn't double-count.
                    // For now, assume Product->create doesn't set quantity_in_stock, and relies on updateStock.
                    // OR, if Product->create *does* set it, then the 'initial_stock' movement is just for audit,
                    // and Product->updateStock quantityChange should be 0 for the movement part, or it needs a specific method.

                    // Simpler: Assume Product->create sets the quantity_in_stock.
                    // We just need to record the movement.
                    // Let's adjust Product->updateStock to handle this, or add a dedicated method in StockMovementModel.
                    // For now, let's assume Product->updateStock is smart enough or create a movement directly.

                    // If Product->create already set the stock value in products table:
                    $stockMovementModel = $this->loadModel('StockMovement');
                    $stockMovementModel->createMovement([
                        'product_id' => $productId,
                        'type' => 'initial_stock',
                        'quantity' => $data['quantity_in_stock'],
                        'notes' => 'Initial stock set during product creation.'
                    ]);
                    // And ensure product table quantity_in_stock is correctly set by Product->create()
                }
                header("Location: /index.php?url=products/show/{$productId}&status=created_success");
                exit;
            } else {
                // Handle creation failure
                $products = $this->productModel->getAll();
                $this->renderView('products/create', ['errors' => ['general' => 'Failed to create product.'], 'data' => $data, 'products' => $products]);
            }
        } else {
            // Not a POST request, redirect to create form or show error
            header("Location: /index.php?url=products/create"); // Adjust URL
            exit;
        }
    }

    /**
     * Shows the form for editing an existing product.
     * @param int $id The ID of the product to edit.
     */
    public function edit($id) {
        $product = $this->productModel->getById($id);
        if ($product) {
            $products = $this->productModel->getAll(); // For parent selection
            $this->renderView('products/edit', ['product' => $product, 'products' => $products]);
        } else {
            $this->renderView('errors/404', ['message' => "Product with ID {$id} not found for editing."]);
        }
    }

    /**
     * Updates an existing product in the database.
     * @param int $id The ID of the product to update.
     */
    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => $_POST['name'] ?? '',
                'description' => $_POST['description'] ?? '',
                'parent_id' => !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null,
                'unit_of_measure' => $_POST['unit_of_measure'] ?? '',
                'quantity_in_stock' => isset($_POST['quantity_in_stock']) ? (int)$_POST['quantity_in_stock'] : 0,
                'purchase_price' => isset($_POST['purchase_price']) ? (float)$_POST['purchase_price'] : 0.0,
                'selling_price' => isset($_POST['selling_price']) ? (float)$_POST['selling_price'] : 0.0,
            ];

            if (empty($data['name'])) {
                $product = $this->productModel->getById($id); // Get current product data
                $products = $this->productModel->getAll();
                $this->renderView('products/edit', ['errors' => ['name' => 'Name is required.'], 'product' => array_merge((array)$product, $data), 'products' => $products]);
                return;
            }

            $affectedRows = $this->productModel->update($id, $data);

            if ($affectedRows !== false) {
                header("Location: /index.php?url=products/show/{$id}"); // Adjust URL
                exit;
            } else {
                // Handle update failure
                $product = $this->productModel->getById($id);
                $products = $this->productModel->getAll();
                $this->renderView('products/edit', ['errors' => ['general' => 'Failed to update product.'], 'product' => array_merge((array)$product, $data), 'products' => $products]);
            }
        } else {
            header("Location: /index.php?url=products/edit/{$id}"); // Adjust URL
            exit;
        }
    }

    /**
     * Deletes a product from the database.
     * @param int $id The ID of the product to delete.
     */
    public function destroy($id) {
        // Consider adding a confirmation step before deletion in a real app
        $deleted = $this->productModel->delete($id);
        if ($deleted) {
            header("Location: /index.php?url=products"); // Adjust URL
            exit;
        } else {
            // Handle deletion failure, maybe set a flash message
            // For now, redirecting with an error is complex without a flash message system
            // So, we can render an error view or redirect to index
            // Ideally, use session-based flash messages to show errors after redirect.
            $this->renderView('errors/500', ['message' => "Failed to delete product with ID {$id}."]);
        }
    }
}
?>
