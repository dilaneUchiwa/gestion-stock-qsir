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

            $unitSellingPrices = [];
            $unitPurchasePrices = [];

            foreach ($productConfiguredUnits as $unit) {
                if (isset($unit['id'])) { // Ensure unit_id exists
                    $sellingPrice = $this->productModel->getSellingPrice($id, $unit['id']);
                    $unitSellingPrices[$unit['id']] = $sellingPrice;

                    $purchasePrice = $this->productModel->getPurchasePrice($id, $unit['id']);
                    $unitPurchasePrices[$unit['id']] = $purchasePrice;
                }
            }

            $this->renderView('products/show', [
                'product' => $product, // Contains base unit name, symbol, and quantity_in_stock (base)
                'productConfiguredUnits' => $productConfiguredUnits, // Contains all units for this product with factors
                'unitSellingPrices' => $unitSellingPrices,
                'unitPurchasePrices' => $unitPurchasePrices,
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

            // Data for core product fields
            $productCoreData = [
                'name' => $_POST['name'] ?? '',
                'description' => $_POST['description'] ?? '',
                'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
                'purchase_price' => isset($_POST['purchase_price']) && $_POST['purchase_price'] !== '' ? (float)$_POST['purchase_price'] : null,
                'selling_price' => isset($_POST['selling_price']) && $_POST['selling_price'] !== '' ? (float)$_POST['selling_price'] : null,
            ];

            // Validation for core data
            if (empty($productCoreData['name'])) {
                $errors['name'] = 'Le nom du produit est requis.';
            }
            if ($productCoreData['category_id'] && !$this->productCategoryModel->getById($productCoreData['category_id'])) {
                $errors['category_id'] = "La catégorie sélectionnée n'est pas valide.";
            }

            // Basic validation for alternative units structure if submitted
            $alternativeUnitsDataFromPost = $_POST['alternative_units'] ?? [];
            if (!is_array($alternativeUnitsDataFromPost)) {
                $errors['alternative_units'] = "Format des unités alternatives invalide.";
            } else {
                foreach ($alternativeUnitsDataFromPost as $index => $altUnit) {
                    if (!isset($altUnit['unit_id']) || !isset($altUnit['conversion_factor'])) {
                        $errors["alternative_units_{$index}"] = "Unité alternative manquante ID ou facteur de conversion.";
                    } elseif (empty($altUnit['unit_id']) && !empty($altUnit['conversion_factor']) && $altUnit['conversion_factor'] != '') {
                         // If a conversion factor is provided but unit_id is empty, it's an error (allow empty factor if unit_id is also empty for a blank row)
                        $errors["alternative_units_{$index}_unit_id"] = "L'ID d'unité est requis pour l'unité alternative #".($index+1).".";
                    } elseif (!empty($altUnit['unit_id']) && ( !is_numeric($altUnit['conversion_factor']) || (float)$altUnit['conversion_factor'] <= 0) ) {
                        $errors["alternative_units_{$index}_factor"] = "Facteur de conversion invalide (doit être numérique et > 0) pour l'unité alternative #".($index+1).".";
                    } elseif (!empty($altUnit['unit_id']) && $altUnit['unit_id'] == $originalProduct['base_unit_id'] ) {
                         if ((float)$altUnit['conversion_factor'] != 1) {
                            $errors["alternative_units_{$index}_base"] = "Le facteur de conversion pour l'unité de base doit être 1.";
                         }
                         // Allow base unit to be in the list if factor is 1, it will be skipped by model's addUnit.
                    }
                }
            }


            if (!empty($errors)) {
                $categories = $this->productCategoryModel->getAll();
                $units = $this->unitModel->getAll(); // Needed for formatAlternativeUnitsForView
                $productUnits = $this->productModel->getUnits($id); // Original configured units

                $this->renderView('products/edit', [
                    'errors' => $errors,
                    'product' => array_merge($originalProduct, $productCoreData),
                    'categories' => $categories,
                    'units' => $units, // Pass all system units for dropdowns
                    'product_units' => $productUnits, // Original full list of product's units for reference
                                                      // For submitted alternative units, format them for view consistency
                    'alternative_units_details' => $this->formatAlternativeUnitsForView($alternativeUnitsDataFromPost, $units, $originalProduct['base_unit_id']),
                ]);
                return;
            }

            // Call the new transactional method
            $updateSuccess = $this->productModel->updateProductWithUnits($id, $productCoreData, $alternativeUnitsDataFromPost);

            if ($updateSuccess) {
                header("Location: /index.php?url=products/show/{$id}&status=updated_success");
                exit;
            } else {
                // Handle failure of the transactional update
                $categories = $this->productCategoryModel->getAll();
                $units = $this->unitModel->getAll(); // Needed for formatAlternativeUnitsForView
                // Re-fetch product data as the transaction might have rolled back to original state or partially changed then rolled back.
                $currentProductState = $this->productModel->getById($id) ?: $originalProduct; // Fallback to original if fetch fails
                $currentProductUnits = $this->productModel->getUnits($id);


                $this->renderView('products/edit', [
                    'errors' => array_merge($errors, ['general' => 'Échec de la mise à jour du produit et de ses unités.']),
                    'product' => array_merge($currentProductState, $productCoreData), // Show submitted core data again
                    'categories' => $categories,
                    'units' => $units, // Pass all system units for dropdowns
                    'product_units' => $currentProductUnits, // Show current state of all units
                                                              // For submitted alternative units, format them for view consistency
                    'alternative_units_details' => $this->formatAlternativeUnitsForView($alternativeUnitsDataFromPost, $units, $originalProduct['base_unit_id']),
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

    private function formatAlternativeUnitsForView(array $submittedUnits, array $allSystemUnits, $baseUnitId) {
        $details = [];
        $unitMap = array_column($allSystemUnits, null, 'id'); // Map unit id to unit details

        foreach ($submittedUnits as $subUnit) {
            // Ensure basic structure and that unit_id is present.
            if (empty($subUnit['unit_id']) || !isset($subUnit['conversion_factor'])) {
                continue;
            }
            // Skip if it's the base unit or conversion factor is invalid for an alternative unit
            if ($subUnit['unit_id'] == $baseUnitId) {
                continue;
            }
            if (!is_numeric($subUnit['conversion_factor']) || (float)$subUnit['conversion_factor'] <= 0) {
                 // Optionally log this invalid data submission attempt
                error_log("Invalid conversion factor for unit ID {$subUnit['unit_id']} during view formatting.");
                continue;
            }


            $details[] = [
                'id' => $subUnit['unit_id'], // This is unit_id, matching structure of getUnits
                'unit_id' => $subUnit['unit_id'],
                'name' => $unitMap[$subUnit['unit_id']]['name'] ?? 'Inconnu',
                'symbol' => $unitMap[$subUnit['unit_id']]['symbol'] ?? 'N/A',
                'conversion_factor_to_base_unit' => $subUnit['conversion_factor'],
                'is_base_unit' => false // It's an alternative unit
            ];
        }
        return $details;
    }
}
?>
