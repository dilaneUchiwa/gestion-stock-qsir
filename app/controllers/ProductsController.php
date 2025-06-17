<?php

require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/core/Database.php'; // Required for loadModel

class ProductsController extends Controller {

    private $productModel;
    private $unitModel;
    private $productCategoryModel;

    public function __construct() {
        parent::__construct();
        $this->productModel = $this->loadModel('Product');
        $this->unitModel = $this->loadModel('Unit');
        $this->productCategoryModel = $this->loadModel('ProductCategory');
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
            // Fetch all configured units for this product, including conversion factors
            // The existing getUnits() method already provides what we need (id, name, symbol, conversion_factor, is_base_unit)
            // For clarity, let's rename the variable passed to the view to reflect its usage for stock display.
            $productConfiguredUnits = $this->productModel->getUnits($id);

            $this->renderView('products/show', [
                'product' => $product, // Contains base unit name, symbol, and quantity_in_stock (base)
                'productConfiguredUnits' => $productConfiguredUnits // Contains all units for this product with factors
            ]);
        } else {
            // Handle product not found, e.g., show a 404 page or redirect
            $this->renderView('errors/404', ['message' => "Produit avec l'ID {$id} non trouvé."]);
        }
    }

    /**
     * Shows the form for creating a new product.
     */
    public function create() {
        $categories = $this->productCategoryModel->getAll();
        $units = $this->unitModel->getAll();
        $this->renderView('products/create', [
            'categories' => $categories,
            'units' => $units
        ]);
    }

    /**
     * Stores a new product in the database.
     */
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = [];
            $data = [
                'name' => $_POST['name'] ?? '',
                'description' => $_POST['description'] ?? '',
                'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
                'base_unit_id' => !empty($_POST['base_unit_id']) ? (int)$_POST['base_unit_id'] : null,
                'quantity_in_stock' => isset($_POST['quantity_in_stock']) && $_POST['quantity_in_stock'] !== '' ? (int)$_POST['quantity_in_stock'] : 0,
                'purchase_price' => isset($_POST['purchase_price']) && $_POST['purchase_price'] !== '' ? (float)$_POST['purchase_price'] : null,
                'selling_price' => isset($_POST['selling_price']) && $_POST['selling_price'] !== '' ? (float)$_POST['selling_price'] : null,
            ];

            // Validation
            if (empty($data['name'])) {
                $errors['name'] = 'Le nom du produit est requis.';
            }
            if (empty($data['base_unit_id'])) {
                $errors['base_unit_id'] = "L'unité de base est requise.";
            } else if (!$this->unitModel->getById($data['base_unit_id'])) {
                $errors['base_unit_id'] = "L'unité de base sélectionnée n'est pas valide.";
            }
            if ($data['category_id'] && !$this->productCategoryModel->getById($data['category_id'])) {
                $errors['category_id'] = "La catégorie sélectionnée n'est pas valide.";
            }
            // TODO: Validate alternative_units structure and values if submitted

            if (!empty($errors)) {
                $categories = $this->productCategoryModel->getAll();
                $units = $this->unitModel->getAll();
                $this->renderView('products/create', [
                    'errors' => $errors,
                    'data' => $data,
                    'categories' => $categories,
                    'units' => $units,
                    'alternative_units_data' => $_POST['alternative_units'] ?? []
                ]);
                return;
            }

            $productId = $this->productModel->create($data);

            if ($productId) {
                // Handle alternative units
                if (isset($_POST['alternative_units']) && is_array($_POST['alternative_units'])) {
                    foreach ($_POST['alternative_units'] as $altUnit) {
                        if (isset($altUnit['unit_id'], $altUnit['conversion_factor']) && !empty($altUnit['unit_id']) && is_numeric($altUnit['conversion_factor'])) {
                            if ($altUnit['unit_id'] == $data['base_unit_id']) { // Skip if it's the base unit
                                continue;
                            }
                            $addUnitSuccess = $this->productModel->addUnit($productId, (int)$altUnit['unit_id'], (float)$altUnit['conversion_factor']);
                            if (!$addUnitSuccess) {
                                // Log error or collect message for user. For now, just log.
                                error_log("Failed to add alternative unit ID {$altUnit['unit_id']} for product ID {$productId}.");
                                // Potentially add a user-facing error message later.
                            }
                        }
                    }
                }

                // Initial stock movement (if quantity provided)
                // ProductModel->create now handles setting product.quantity_in_stock
                // This movement is for audit trail.
                if ($data['quantity_in_stock'] > 0) {
                    $stockMovementModel = $this->loadModel('StockMovement');
                    $stockMovementModel->createMovement([
                        'product_id' => $productId,
                        'type' => 'initial_stock',
                        'quantity' => $data['quantity_in_stock'],
                        'notes' => 'Stock initial défini lors de la création du produit.'
                    ]);
                }

                header("Location: /index.php?url=products/show/{$productId}&status=created_success");
                exit;
            } else {
                $categories = $this->productCategoryModel->getAll();
                $units = $this->unitModel->getAll();
                $this->renderView('products/create', [
                    'errors' => ['general' => 'Échec de la création du produit.'],
                    'data' => $data,
                    'categories' => $categories,
                    'units' => $units,
                    'alternative_units_data' => $_POST['alternative_units'] ?? []
                ]);
            }
        } else {
            header("Location: /index.php?url=products/create");
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
            $categories = $this->productCategoryModel->getAll();
            $units = $this->unitModel->getAll();
            $productUnits = $this->productModel->getUnits($id); // Get all units for this product

            // Separate base unit from alternative units for easier handling in the view
            $baseUnitDetails = null;
            $alternativeUnitsDetails = [];
            foreach ($productUnits as $pu) {
                if ($pu['is_base_unit']) {
                    $baseUnitDetails = $pu; // Should already be part of $product, but good to have explicitly if needed
                } else {
                    $alternativeUnitsDetails[] = $pu;
                }
            }

            $this->renderView('products/edit', [
                'product' => $product,
                'categories' => $categories,
                'units' => $units,
                'product_units' => $productUnits, // Contains all units including base, with 'is_base_unit' flag
                'alternative_units_details' => $alternativeUnitsDetails // Contains only alternative units
            ]);
        } else {
            $this->renderView('errors/404', ['message' => "Produit avec l'ID {$id} non trouvé pour la modification."]);
        }
    }

    /**
     * Updates an existing product in the database.
     * @param int $id The ID of the product to update.
     */
    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = [];
            $originalProduct = $this->productModel->getById($id);
            if (!$originalProduct) {
                $this->renderView('errors/404', ['message' => "Produit avec l'ID {$id} non trouvé pour la mise à jour."]);
                return;
            }

            $data = [
                'name' => $_POST['name'] ?? '',
                'description' => $_POST['description'] ?? '',
                'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
                // base_unit_id is not updatable via this form as per previous decision
                // quantity_in_stock is not directly updated here; managed by stock movements.
                'purchase_price' => isset($_POST['purchase_price']) && $_POST['purchase_price'] !== '' ? (float)$_POST['purchase_price'] : null,
                'selling_price' => isset($_POST['selling_price']) && $_POST['selling_price'] !== '' ? (float)$_POST['selling_price'] : null,
            ];

            // Validation
            if (empty($data['name'])) {
                $errors['name'] = 'Le nom du produit est requis.';
            }
            if ($data['category_id'] && !$this->productCategoryModel->getById($data['category_id'])) {
                $errors['category_id'] = "La catégorie sélectionnée n'est pas valide.";
            }
            // TODO: Validate alternative_units structure and values

            if (!empty($errors)) {
                // Repopulate data for the view
                $categories = $this->productCategoryModel->getAll();
                $units = $this->unitModel->getAll();
                $productUnits = $this->productModel->getUnits($id);
                $alternativeUnitsDetails = array_filter($productUnits, fn($pu) => !$pu['is_base_unit']);

                $this->renderView('products/edit', [
                    'errors' => $errors,
                    'product' => array_merge($originalProduct, $data), // Show submitted data on error
                    'categories' => $categories,
                    'units' => $units,
                    'product_units' => $productUnits,
                    'alternative_units_details' => $alternativeUnitsDetails // Show original alternatives on validation error of main fields
                                                                          // Or try to merge with POSTed alternative_units:
                                                                          // 'alternative_units_details' => $_POST['alternative_units'] ?? $alternativeUnitsDetails,
                ]);
                return;
            }

            $updateSuccess = $this->productModel->update($id, $data);

            if ($updateSuccess !== false) {
                // Manage alternative units (simplified: remove all non-base, then add submitted)
                // TODO: Implement a more granular update (compare, update, add, delete individually)
                $existingUnits = $this->productModel->getUnits($id);
                foreach ($existingUnits as $exUnit) {
                    if (!$exUnit['is_base_unit']) {
                        $this->productModel->removeUnit($id, $exUnit['id']);
                    }
                }

                if (isset($_POST['alternative_units']) && is_array($_POST['alternative_units'])) {
                    foreach ($_POST['alternative_units'] as $altUnit) {
                        if (isset($altUnit['unit_id'], $altUnit['conversion_factor']) &&
                            !empty($altUnit['unit_id']) && is_numeric($altUnit['conversion_factor']) &&
                            $altUnit['unit_id'] != $originalProduct['base_unit_id']) { // Do not re-add base unit as alternative

                            $addSuccess = $this->productModel->addUnit($id, (int)$altUnit['unit_id'], (float)$altUnit['conversion_factor']);
                            if (!$addSuccess) {
                                error_log("Failed to add/update alternative unit ID {$altUnit['unit_id']} for product ID {$id} during update.");
                                // Collect these errors to show to user if necessary
                            }
                        }
                    }
                }

                header("Location: /index.php?url=products/show/{$id}&status=updated_success");
                exit;
            } else {
                // Handle main product update failure
                $categories = $this->productCategoryModel->getAll();
                $units = $this->unitModel->getAll();
                $productUnits = $this->productModel->getUnits($id);
                $alternativeUnitsDetails = array_filter($productUnits, fn($pu) => !$pu['is_base_unit']);

                $this->renderView('products/edit', [
                    'errors' => ['general' => 'Échec de la mise à jour du produit.'],
                    'product' => array_merge($originalProduct, $data),
                    'categories' => $categories,
                    'units' => $units,
                    'product_units' => $productUnits,
                    'alternative_units_details' => $alternativeUnitsDetails
                ]);
            }
        } else {
            header("Location: /index.php?url=products/edit/{$id}");
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
            $this->renderView('errors/500', ['message' => "Échec de la suppression du produit avec l'ID {$id}."]);
        }
    }
}
?>
