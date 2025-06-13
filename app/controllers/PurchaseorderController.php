<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/Controller.php';
// Models will be loaded via $this->loadModel()

class PurchaseorderController extends Controller {

    private $purchaseOrderModel;
    private $supplierModel;
    private $productModel;

    public function __construct() {
        parent::__construct();
        $this->purchaseOrderModel = $this->loadModel('PurchaseOrder');
        $this->supplierModel = $this->loadModel('Supplier'); // For supplier selection
        $this->productModel = $this->loadModel('Product');   // For product selection in items
    }

    /**
     * Displays a list of all purchase orders.
     */
    public function index() {
        $purchaseOrders = $this->purchaseOrderModel->getAllWithSupplier();
        $this->renderView('procurement/purchase_orders/index', ['purchaseOrders' => $purchaseOrders, 'title' => 'Purchase Orders']);
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
                'title' => 'Purchase Order Details'
            ]);
        } else {
            $this->renderView('errors/404', ['message' => "Purchase Order with ID {$id} not found."]);
        }
    }

    /**
     * Shows the form for creating a new purchase order.
     */
    public function create() {
        $suppliers = $this->supplierModel->getAll();
        $products = $this->productModel->getAll(); // For item selection
        $this->renderView('procurement/purchase_orders/create', [
            'suppliers' => $suppliers,
            'products' => $products,
            'allowedStatuses' => $this->purchaseOrderModel->allowedStatuses,
            'title' => 'Create Purchase Order'
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
                    if (!empty($item['product_id']) && isset($item['quantity_ordered']) && isset($item['unit_price'])) {
                        $itemsData[] = [
                            'product_id' => $item['product_id'],
                            'quantity_ordered' => (int)$item['quantity_ordered'],
                            'unit_price' => (float)$item['unit_price'],
                        ];
                    }
                }
            }

            // Validation
            $errors = [];
            if (empty($data['supplier_id'])) $errors['supplier_id'] = "Supplier is required.";
            if (empty($data['order_date'])) $errors['order_date'] = "Order date is required.";
            if (empty($itemsData)) $errors['items'] = "At least one item is required.";
            foreach($itemsData as $idx => $item) {
                if($item['quantity_ordered'] <= 0) $errors["item_{$idx}_qty"] = "Item quantity must be positive.";
                if($item['unit_price'] < 0) $errors["item_{$idx}_price"] = "Item unit price cannot be negative.";
            }


            if (!empty($errors)) {
                $suppliers = $this->supplierModel->getAll();
                $products = $this->productModel->getAll();
                $this->renderView('procurement/purchase_orders/create', [
                    'errors' => $errors,
                    'data' => $data,
                    'itemsData_form' => $itemsData, // Pass back item data for repopulation
                    'suppliers' => $suppliers,
                    'products' => $products,
                    'allowedStatuses' => $this->purchaseOrderModel->allowedStatuses,
                    'title' => 'Create Purchase Order'
                ]);
                return;
            }

            $poId = $this->purchaseOrderModel->createOrder($data, $itemsData);

            if ($poId) {
                header("Location: /index.php?url=purchaseorder/show/{$poId}&status=created_success");
                exit;
            } else {
                $errors['general'] = 'Failed to create purchase order.';
                $suppliers = $this->supplierModel->getAll();
                $products = $this->productModel->getAll();
                $this->renderView('procurement/purchase_orders/create', [
                    'errors' => $errors,
                    'data' => $data,
                    'itemsData_form' => $itemsData,
                    'suppliers' => $suppliers,
                    'products' => $products,
                    'allowedStatuses' => $this->purchaseOrderModel->allowedStatuses,
                    'title' => 'Create Purchase Order'
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
            $products = $this->productModel->getAll();
            // Prevent editing of POs that are fully received or cancelled
            if (in_array($purchaseOrder['status'], ['received', 'cancelled'])) {
                 $this->renderView('errors/403', [ // 403 Forbidden
                    'message' => "Purchase Order with status '{$purchaseOrder['status']}' cannot be edited.",
                    'title' => 'Edit Forbidden'
                ]);
                return;
            }

            $this->renderView('procurement/purchase_orders/edit', [
                'purchaseOrder' => $purchaseOrder,
                'suppliers' => $suppliers,
                'products' => $products,
                'allowedStatuses' => $this->purchaseOrderModel->allowedStatuses,
                'title' => 'Edit Purchase Order'
            ]);
        } else {
            $this->renderView('errors/404', ['message' => "Purchase Order with ID {$id} not found for editing."]);
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
                 $this->renderView('errors/403', ['message' => "Cannot update PO that is received, cancelled or not found."]);
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
                    if (!empty($item['product_id']) && isset($item['quantity_ordered']) && isset($item['unit_price'])) {
                        $itemsData[] = [
                            // 'id' => $item['id'] ?? null, // For updating existing items vs new ones
                            'product_id' => $item['product_id'],
                            'quantity_ordered' => (int)$item['quantity_ordered'],
                            'unit_price' => (float)$item['unit_price'],
                        ];
                    }
                }
            }

            $errors = [];
            // Add validation similar to store()
            if (empty($data['supplier_id'])) $errors['supplier_id'] = "Supplier is required.";
            if (empty($data['order_date'])) $errors['order_date'] = "Order date is required.";
            if (empty($itemsData) && $currentPo['status'] == 'pending') $errors['items'] = "At least one item is required for pending orders.";


            if (!empty($errors)) {
                // Repopulate form with errors and submitted data
                $purchaseOrder = $this->purchaseOrderModel->getByIdWithItems($id); // Get fresh full PO data
                $purchaseOrder = array_merge($purchaseOrder, $data); // Override with submitted data
                $purchaseOrder['items'] = $itemsData; // Use submitted items data for form repopulation

                $suppliers = $this->supplierModel->getAll();
                $products = $this->productModel->getAll();
                $this->renderView('procurement/purchase_orders/edit', [
                    'errors' => $errors,
                    'purchaseOrder' => $purchaseOrder,
                    'suppliers' => $suppliers,
                    'products' => $products,
                    'allowedStatuses' => $this->purchaseOrderModel->allowedStatuses,
                    'title' => 'Edit Purchase Order'
                ]);
                return;
            }

            // The updateOrder method in model handles replacing items and recalculating total
            $success = $this->purchaseOrderModel->updateOrder($id, $data, $itemsData);

            if ($success) {
                header("Location: /index.php?url=purchaseorder/show/{$id}&status=updated_success");
                exit;
            } else {
                $errors['general'] = 'Failed to update purchase order.';
                $purchaseOrder = $this->purchaseOrderModel->getByIdWithItems($id); // Get fresh full PO data
                $purchaseOrder = array_merge($purchaseOrder, $data);
                $purchaseOrder['items'] = $itemsData;

                $suppliers = $this->supplierModel->getAll();
                $products = $this->productModel->getAll();
                $this->renderView('procurement/purchase_orders/edit', [
                    'errors' => $errors,
                    'purchaseOrder' => $purchaseOrder,
                    'suppliers' => $suppliers,
                    'products' => $products,
                    'allowedStatuses' => $this->purchaseOrderModel->allowedStatuses,
                    'title' => 'Edit Purchase Order'
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
            $this->renderView('errors/404', ['message' => "Purchase Order with ID {$id} not found."]);
            return;
        }
        // Only pending or partially received orders can be cancelled (example logic)
        if (!in_array($po['status'], ['pending', 'partially_received'])) {
            header("Location: /index.php?url=purchaseorder/show/{$id}&status=cancel_failed_status");
            exit;
        }

        if ($this->purchaseOrderModel->updateStatus($id, 'cancelled')) {
            header("Location: /index.php?url=purchaseorder/show/{$id}&status=cancelled_success");
            exit;
        } else {
            header("Location: /index.php?url=purchaseorder/show/{$id}&status=cancelled_error");
            exit;
        }
    }

    /**
     * Deletes a purchase order. (Use with caution, prefer 'cancel' status)
     * @param int $id The ID of the purchase order to delete.
     */
    public function destroy($id) {
        // Ensure only certain statuses can be deleted, or add other checks
        $po = $this->purchaseOrderModel->getById($id);
        if ($po && $po['status'] !== 'cancelled' && $po['status'] !== 'pending') {
             // For example, only allow deletion of 'cancelled' or 'pending' orders
            header("Location: /index.php?url=purchaseorder/index&status=delete_failed_status");
            exit;
        }

        if ($this->purchaseOrderModel->deleteOrder($id)) {
            header("Location: /index.php?url=purchaseorder/index&status=deleted_success");
            exit;
        } else {
            header("Location: /index.php?url=purchaseorder/index&status=delete_error");
            exit;
        }
    }
}
?>
