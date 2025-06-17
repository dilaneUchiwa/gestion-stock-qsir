<?php

require_once ROOT_PATH . '/core/Controller.php';
// Models will be loaded via $this->loadModel()

class PurchaseorderController extends Controller {

    private $purchaseOrderModel;
    private $supplierModel;
    private $productModel;
    private $unitModel; // Added UnitModel

    public function __construct() {
        parent::__construct();
        $this->purchaseOrderModel = $this->loadModel('PurchaseOrder');
        $this->supplierModel = $this->loadModel('Supplier'); // For supplier selection
        $this->productModel = $this->loadModel('Product');   // For product selection in items
        $this->unitModel = $this->loadModel('Unit');       // Load UnitModel
    }

    /**
     * Displays a list of all purchase orders.
     */
    public function index() {
        $purchaseOrders = $this->purchaseOrderModel->getAllWithSupplier();
        $this->renderView('procurement/purchase_orders/index', ['purchaseOrders' => $purchaseOrders, 'title' => 'Bons de commande']);
    }

    /**
     * Displays a single purchase order by its ID, including items.
     * @param int $id The ID of the purchase order.
     */
    public function show($id) {
        $purchaseOrder = $this->purchaseOrderModel->getByIdWithItems($id);
        if ($purchaseOrder) {
            $this->renderView('procurement/purchase_orders/show', [
                'purchaseOrder' => $purchaseOrder,
                'title' => 'Détails du bon de commande'
            ]);
        } else {
            $this->renderView('errors/404', ['message' => "Bon de commande avec l'ID {$id} non trouvé."]);
        }
    }

    /**
     * Shows the form for creating a new purchase order.
     */
    public function create() {
        $suppliers = $this->supplierModel->getAll();
        $products = $this->productModel->getAll();
        $units = $this->unitModel->getAll();

        $productUnitsMap = [];
        foreach ($products as $product) {
            $productUnitsMap[$product['id']] = $this->productModel->getUnitsForProduct($product['id']);
        }

        $this->renderView('procurement/purchase_orders/create', [
            'suppliers' => $suppliers,
            'products' => $products, // Contains base_unit_id, base_unit_name etc.
            'units' => $units, // All units for fallback or general purpose
            'productUnitsMap' => $productUnitsMap, // Specific units for each product
            'allowedStatuses' => $this->purchaseOrderModel->allowedStatuses,
            'title' => 'Créer un bon de commande'
        ]);
    }

    /**
     * Stores a new purchase order in the database.
     */
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'supplier_id' => $_POST['supplier_id'] ?? null,
                'order_date' => $_POST['order_date'] ?? null,
                'expected_delivery_date' => !empty($_POST['expected_delivery_date']) ? $_POST['expected_delivery_date'] : null,
                'status' => $_POST['status'] ?? 'pending',
                'notes' => $_POST['notes'] ?? '',
            ];

            $itemsData = [];
            if (isset($_POST['items']) && is_array($_POST['items'])) {
                foreach ($_POST['items'] as $item) {
                    if (!empty($item['product_id']) && isset($item['quantity_ordered']) && isset($item['unit_price']) && !empty($item['unit_id'])) {
                        $itemsData[] = [
                            'product_id' => $item['product_id'],
                            'unit_id' => (int)$item['unit_id'], // Add unit_id
                            'quantity_ordered' => (int)$item['quantity_ordered'],
                            'unit_price' => (float)$item['unit_price'],
                        ];
                    }
                }
            }

            // Validation
            $errors = [];
            if (empty($data['supplier_id'])) $errors['supplier_id'] = "Le fournisseur est requis.";
            if (empty($data['order_date'])) $errors['order_date'] = "La date de commande est requise.";
            if (empty($itemsData)) $errors['items'] = "Au moins un article est requis.";

            foreach($itemsData as $idx => $item) {
                if (empty($item['unit_id'])) {
                    $errors["item_{$idx}_unit_id"] = "L'unité pour l'article est requise.";
                } else if (!$this->productModel->isUnitValidForProduct((int)$item['product_id'], (int)$item['unit_id'])) {
                    $productInfo = $this->productModel->getById((int)$item['product_id']);
                    $unitInfo = $this->unitModel->getById((int)$item['unit_id']);
                    $errors["item_{$idx}_unit_id"] = "L'unité '".($unitInfo ? $unitInfo['name'] : $item['unit_id'])."' n'est pas valide pour le produit '".($productInfo ? $productInfo['name'] : $item['product_id'])."'.";
                }
                if($item['quantity_ordered'] <= 0) $errors["item_{$idx}_qty"] = "La quantité de l'article doit être positive.";
                if($item['unit_price'] < 0) $errors["item_{$idx}_price"] = "Le prix unitaire de l'article ne peut pas être négatif.";
            }


            if (!empty($errors)) {
                $suppliers = $this->supplierModel->getAll();
                $products = $this->productModel->getAll();
                $units = $this->unitModel->getAll();
                // Regenerate productUnitsMap for the view when there are errors
                $productUnitsMap = [];
                foreach ($products as $product) {
                    $productUnitsMap[$product['id']] = $this->productModel->getUnitsForProduct($product['id']);
                }
                $this->renderView('procurement/purchase_orders/create', [
                    'errors' => $errors,
                    'data' => $data,
                    'itemsData_form' => $itemsData,
                    'suppliers' => $suppliers,
                    'products' => $products,
                    'units' => $units,
                    'productUnitsMap' => $productUnitsMap,
                    'allowedStatuses' => $this->purchaseOrderModel->allowedStatuses,
                    'title' => 'Créer un bon de commande'
                ]);
                return;
            }

            $poId = $this->purchaseOrderModel->createOrder($data, $itemsData);

            if ($poId) {
                header("Location: /index.php?url=purchaseorder/show/{$poId}&status=created_success");
                exit;
            } else {
                $errors['general'] = 'Échec de la création du bon de commande.';
                $suppliers = $this->supplierModel->getAll();
                $products = $this->productModel->getAll();
                $units = $this->unitModel->getAll();
                $this->renderView('procurement/purchase_orders/create', [
                    'errors' => $errors,
                    'data' => $data,
                    'itemsData_form' => $itemsData,
                    'suppliers' => $suppliers,
                    'products' => $products,
                    'units' => $units,
                    'allowedStatuses' => $this->purchaseOrderModel->allowedStatuses,
                    'title' => 'Créer un bon de commande'
                ]);
            }
        } else {
            header("Location: /index.php?url=purchaseorder/create");
            exit;
        }
    }

    /**
     * Shows the form for editing an existing purchase order.
     * @param int $id The ID of the purchase order to edit.
     */
    public function edit($id) {
        $purchaseOrder = $this->purchaseOrderModel->getByIdWithItems($id);
        if ($purchaseOrder) {
            $suppliers = $this->supplierModel->getAll();
            $products = $this->productModel->getAll(); // Base product list
            $units = $this->unitModel->getAll(); // All units list

            $productUnitsMap = [];
            // Fetch units for products already in the PO items
            if (!empty($purchaseOrder['items'])) {
                foreach ($purchaseOrder['items'] as $item) {
                    if (!isset($productUnitsMap[$item['product_id']])) {
                        $productUnitsMap[$item['product_id']] = $this->productModel->getUnitsForProduct($item['product_id']);
                    }
                }
            }
            // And also for all other products that might be added
            foreach ($products as $product) {
                if (!isset($productUnitsMap[$product['id']])) {
                     $productUnitsMap[$product['id']] = $this->productModel->getUnitsForProduct($product['id']);
                }
            }

            // Prevent editing of POs that are fully received or cancelled
            if (in_array($purchaseOrder['status'], ['received', 'cancelled'])) {
                 $this->renderView('errors/403', [ // 403 Forbidden
                    'message' => "Le bon de commande avec le statut '{$purchaseOrder['status']}' ne peut pas être modifié.",
                    'title' => 'Modification interdite'
                ]);
                return;
            }

            $this->renderView('procurement/purchase_orders/edit', [
                'purchaseOrder' => $purchaseOrder, // Contains items with unit_id, unit_name, unit_symbol
                'suppliers' => $suppliers,
                'products' => $products, // General list of all products
                'units' => $units, // General list of all units
                'productUnitsMap' => $productUnitsMap, // Specific units for each product
                'allowedStatuses' => $this->purchaseOrderModel->allowedStatuses,
                'title' => 'Modifier le bon de commande'
            ]);
        } else {
            $this->renderView('errors/404', ['message' => "Bon de commande avec l'ID {$id} non trouvé pour modification."]);
        }
    }

    /**
     * Updates an existing purchase order in the database.
     * @param int $id The ID of the purchase order to update.
     */
    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $currentPo = $this->purchaseOrderModel->getById($id);
            if (!$currentPo || in_array($currentPo['status'], ['received', 'cancelled'])) {
                 $this->renderView('errors/403', ['message' => "Impossible de mettre à jour un BC qui est reçu, annulé ou non trouvé."]);
                 return;
            }

            $data = [
                'supplier_id' => $_POST['supplier_id'] ?? null,
                'order_date' => $_POST['order_date'] ?? null,
                'expected_delivery_date' => !empty($_POST['expected_delivery_date']) ? $_POST['expected_delivery_date'] : null,
                'status' => $_POST['status'] ?? $currentPo['status'], // Keep current status if not provided
                'notes' => $_POST['notes'] ?? '',
            ];

            $itemsData = [];
            if (isset($_POST['items']) && is_array($_POST['items'])) {
                foreach ($_POST['items'] as $item) {
                    if (!empty($item['product_id']) && isset($item['quantity_ordered']) && isset($item['unit_price']) && !empty($item['unit_id'])) {
                        $itemsData[] = [
                            // 'id' => $item['id'] ?? null,
                            'product_id' => $item['product_id'],
                            'unit_id' => (int)$item['unit_id'], // Add unit_id
                            'quantity_ordered' => (int)$item['quantity_ordered'],
                            'unit_price' => (float)$item['unit_price'],
                        ];
                    }
                }
            }

            $errors = [];
            // Add validation similar to store()
            if (empty($data['supplier_id'])) $errors['supplier_id'] = "Le fournisseur est requis.";
            if (empty($data['order_date'])) $errors['order_date'] = "La date de commande est requise.";
            if (empty($itemsData) && $currentPo['status'] == 'pending') $errors['items'] = "Au moins un article est requis pour les commandes en attente.";

            foreach($itemsData as $idx => $item) {
                if (empty($item['unit_id'])) {
                    $errors["item_{$idx}_unit_id"] = "L'unité pour l'article est requise.";
                } else if (!$this->productModel->isUnitValidForProduct((int)$item['product_id'], (int)$item['unit_id'])) {
                    $productInfo = $this->productModel->getById((int)$item['product_id']);
                    $unitInfo = $this->unitModel->getById((int)$item['unit_id']);
                    $errors["item_{$idx}_unit_id"] = "L'unité '".($unitInfo ? $unitInfo['name'] : $item['unit_id'])."' n'est pas valide pour le produit '".($productInfo ? $productInfo['name'] : $item['product_id'])."'.";
                }
                if($item['quantity_ordered'] <= 0) $errors["item_{$idx}_qty"] = "La quantité de l'article doit être positive.";
                if($item['unit_price'] < 0) $errors["item_{$idx}_price"] = "Le prix unitaire de l'article ne peut pas être négatif.";
            }


            if (!empty($errors)) {
                $purchaseOrder = $this->purchaseOrderModel->getByIdWithItems($id);
                $purchaseOrder = array_merge($purchaseOrder, $data);
                $itemsDataForForm = $itemsData;

                $suppliers = $this->supplierModel->getAll();
                $productsForDropdown = $this->productModel->getAll(); // Renamed to avoid confusion
                $unitsAll = $this->unitModel->getAll(); // Renamed for clarity

                $productUnitsMap = [];
                // Fetch units for products already in the PO items (using original product_id from itemsDataForForm)
                if (!empty($itemsDataForForm)) {
                    foreach ($itemsDataForForm as $itData) {
                        if (!isset($productUnitsMap[$itData['product_id']])) {
                            $productUnitsMap[$itData['product_id']] = $this->productModel->getUnitsForProduct($itData['product_id']);
                        }
                    }
                }
                // And also for all other products that might be added
                foreach ($productsForDropdown as $p) {
                    if (!isset($productUnitsMap[$p['id']])) {
                         $productUnitsMap[$p['id']] = $this->productModel->getUnitsForProduct($p['id']);
                    }
                }

                $this->renderView('procurement/purchase_orders/edit', [
                    'errors' => $errors,
                    'purchaseOrder' => $purchaseOrder,
                    'itemsData_form' => $itemsDataForForm,
                    'suppliers' => $suppliers,
                    'products' => $productsForDropdown, // Pass the general list of products
                    'units' => $unitsAll, // Pass the general list of all units
                    'productUnitsMap' => $productUnitsMap,
                    'allowedStatuses' => $this->purchaseOrderModel->allowedStatuses,
                    'title' => 'Modifier le bon de commande'
                ]);
                return;
            }

            // The updateOrder method in model handles replacing items and recalculating total
            $success = $this->purchaseOrderModel->updateOrder($id, $data, $itemsData);

            if ($success) {
                header("Location: /index.php?url=purchaseorder/show/{$id}&status=updated_success");
                exit;
            } else {
                $errors['general'] = 'Échec de la mise à jour du bon de commande.';
                $purchaseOrder = $this->purchaseOrderModel->getByIdWithItems($id);
                $purchaseOrder = array_merge($purchaseOrder, $data);
                // $purchaseOrder['items'] = $itemsData; // This was for direct display, but itemsData_form is better for forms
                $itemsDataForForm = $itemsData;


                $suppliers = $this->supplierModel->getAll();
                $products = $this->productModel->getAll();
                $units = $this->unitModel->getAll();
                $this->renderView('procurement/purchase_orders/edit', [
                    'errors' => $errors,
                    'purchaseOrder' => $purchaseOrder,
                    'itemsData_form' => $itemsDataForForm,
                    'suppliers' => $suppliers,
                    'products' => $products,
                    'units' => $units,
                    'allowedStatuses' => $this->purchaseOrderModel->allowedStatuses,
                    'title' => 'Modifier le bon de commande'
                ]);
            }
        } else {
            header("Location: /index.php?url=purchaseorder/edit/{$id}");
            exit;
        }
    }

    /**
     * Cancels a purchase order (sets status to 'cancelled').
     * @param int $id The ID of the purchase order to cancel.
     */
    public function cancel($id) {
        $po = $this->purchaseOrderModel->getById($id);
        if (!$po) {
            $this->renderView('errors/404', ['message' => "Bon de commande avec l'ID {$id} non trouvé."]);
            return;
        }

        if ($po['status'] === 'pending' || $po['status'] === 'partially_received') {
            $success = $this->purchaseOrderModel->changeStatus($id, 'cancelled');
            if ($success) {
                header("Location: /index.php?url=purchaseorder/show/{$id}&status=cancelled_success");
            } else {
                $this->renderView('errors/500', ['message' => "Échec de l'annulation du bon de commande."]);
            }
        } else {
            $this->renderView('errors/403', ['message' => "Seuls les bons de commande en attente ou partiellement reçus peuvent être annulés."]);
        }
    }

    /**
     * Deletes a purchase order. (Use with caution, prefer 'cancel' status)
     * @param int $id The ID of the purchase order to delete.
     */
    public function destroy($id) {
        $po = $this->purchaseOrderModel->getById($id);
        if (!$po) {
            $this->renderView('errors/404', ['message' => "Bon de commande avec l'ID {$id} non trouvé pour suppression."]);
            return;
        }

        // Only allow deleting orders that are in a 'safe' state, like 'cancelled' or maybe 'pending'.
        // Deleting 'received' orders could orphans deliveries and stock movements, causing issues.
        if ($po['status'] !== 'cancelled') {
            $this->renderView('errors/403', ['message' => 'Seuls les bons de commande annulés peuvent être supprimés pour maintenir l\'intégrité des données. Annulez d\'abord la commande.']);
            return;
        }
        
        $deleted = $this->purchaseOrderModel->deleteOrder($id);

        if ($deleted) {
            header("Location: /index.php?url=purchaseorder/index&status=deleted_success");
            exit;
        } else {
            $this->renderView('errors/500', ['message' => "Échec de la suppression du bon de commande."]);
        }
    }

    /**
     * Generates a printable Purchase Order.
     * @param int $id The ID of the Purchase Order.
     */
    public function print_po($id) {
        $purchaseOrder = $this->purchaseOrderModel->getByIdWithItems($id);
        if (!$purchaseOrder) {
            $this->renderView('errors/404', ['message' => "Bon de commande avec l'ID {$id} non trouvé."]);
            return;
        }

        // Data for the view
        $data = [
            'purchaseOrder' => $purchaseOrder,
            'title' => "Bon de Commande #BC-" . $purchaseOrder['id']
            // Company details can be hardcoded in the print view or fetched from a config/DB
        ];
        $this->renderPrintView('procurement/purchase_orders/print_po', $data);
    }
}
?>
