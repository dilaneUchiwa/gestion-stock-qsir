<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/Controller.php';

class DeliveryController extends Controller {

    private $deliveryModel;
    private $purchaseOrderModel;
    private $supplierModel;
    private $productModel;

    public function __construct() {
        parent::__construct();
        $this->deliveryModel = $this->loadModel('Delivery');
        $this->purchaseOrderModel = $this->loadModel('PurchaseOrder');
        $this->supplierModel = $this->loadModel('Supplier');
        $this->productModel = $this->loadModel('Product');
    }

    /**
     * Displays a list of all deliveries.
     */
    public function index() {
        $deliveries = $this->deliveryModel->getAllWithDetails();
        $this->renderView('procurement/deliveries/index', [
            'deliveries' => $deliveries,
            'title' => 'Deliveries / Receptions'
        ]);
    }

    /**
     * Displays a single delivery by its ID.
     * @param int $id The ID of the delivery.
     */
    public function show($id) {
        $delivery = $this->deliveryModel->getByIdWithDetails($id);
        if ($delivery) {
            $this->renderView('procurement/deliveries/show', [
                'delivery' => $delivery,
                'title' => 'Delivery Details'
            ]);
        } else {
            $this->renderView('errors/404', ['message' => "Delivery with ID {$id} not found."]);
        }
    }

    /**
     * Shows the form for creating a new delivery.
     * Can be pre-filled if a purchase_order_id is provided via GET.
     */
    public function create() {
        $poId = $_GET['po_id'] ?? null;
        $purchaseOrder = null;
        $poItems = [];
        $suppliers = $this->supplierModel->getAll();
        $products = $this->productModel->getAll(); // For direct delivery or adding unplanned items

        if ($poId) {
            $purchaseOrder = $this->purchaseOrderModel->getByIdWithItems($poId);
            if ($purchaseOrder) {
                // Filter items that are not fully received yet
                // This requires knowing total received quantity for each PO item so far.
                $poItems = $this->getPendingPoItems($poId, $purchaseOrder['items']);
                if(empty($poItems) && $purchaseOrder['status'] === 'received'){
                     $this->renderView('errors/400', [ // Bad request
                        'message' => "Purchase Order PO-{$poId} is already fully received.",
                        'title' => 'Error Creating Delivery'
                    ]);
                    return;
                }
            } else {
                 $this->renderView('errors/404', ['message' => "Purchase Order with ID {$poId} not found for creating delivery."]);
                 return;
            }
        }

        $this->renderView('procurement/deliveries/create', [
            'purchaseOrder' => $purchaseOrder, // Full PO object if poId is valid
            'poItems' => $poItems, // Items from PO, possibly filtered for pending receipt
            'suppliers' => $suppliers, // For direct delivery
            'products' => $products,  // For adding items not on PO / direct delivery
            'allowedDeliveryTypes' => $this->deliveryModel->allowedTypes,
            'title' => $poId ? "Create Delivery for PO-{$poId}" : 'Create Direct Delivery'
        ]);
    }

    /**
     * Helper to get PO items with pending quantities for delivery form.
     */
    private function getPendingPoItems($poId, $allPoItems) {
        $pendingItems = [];
        // Get total already received for each item on this PO
        $sqlReceivedQty = "SELECT di.purchase_order_item_id, SUM(di.quantity_received) as total_received
                           FROM delivery_items di
                           JOIN deliveries d ON di.delivery_id = d.id
                           WHERE d.purchase_order_id = :po_id AND di.purchase_order_item_id IS NOT NULL
                           GROUP BY di.purchase_order_item_id";
        $alreadyReceivedData = $this->deliveryModel->db->select($sqlReceivedQty, [':po_id' => $poId]);

        $alreadyReceivedMap = [];
        foreach($alreadyReceivedData as $received) {
            $alreadyReceivedMap[$received['purchase_order_item_id']] = (int)$received['total_received'];
        }

        foreach ($allPoItems as $poItem) {
            $alreadyReceived = $alreadyReceivedMap[$poItem['id']] ?? 0;
            $pendingQty = $poItem['quantity_ordered'] - $alreadyReceived;
            if ($pendingQty > 0) {
                $itemWithPending = $poItem;
                $itemWithPending['quantity_pending'] = $pendingQty;
                $itemWithPending['quantity_already_received'] = $alreadyReceived;
                $pendingItems[] = $itemWithPending;
            }
        }
        return $pendingItems;
    }


    /**
     * Stores a new delivery in the database.
     */
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'purchase_order_id' => !empty($_POST['purchase_order_id']) ? (int)$_POST['purchase_order_id'] : null,
                'supplier_id' => !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null,
                'delivery_date' => $_POST['delivery_date'] ?? null,
                'is_partial' => isset($_POST['is_partial']) ? (bool)$_POST['is_partial'] : false, // Checkbox
                'notes' => $_POST['notes'] ?? '',
                'type' => $_POST['type'] ?? 'purchase',
            ];

            $itemsData = [];
            if (isset($_POST['items']) && is_array($_POST['items'])) {
                foreach ($_POST['items'] as $item) {
                    if (!empty($item['product_id']) && isset($item['quantity_received']) && (int)$item['quantity_received'] > 0) {
                        $itemsData[] = [
                            'product_id' => (int)$item['product_id'],
                            'quantity_received' => (int)$item['quantity_received'],
                            'purchase_order_item_id' => !empty($item['purchase_order_item_id']) ? (int)$item['purchase_order_item_id'] : null,
                        ];
                    }
                }
            }

            $errors = [];
            if (empty($data['delivery_date'])) $errors['delivery_date'] = "Delivery date is required.";
            if (empty($data['purchase_order_id']) && empty($data['supplier_id'])) $errors['supplier_id'] = "Supplier or linked Purchase Order is required.";
            if (empty($itemsData)) $errors['items'] = "At least one item must be received.";
             if (!in_array($data['type'], $this->deliveryModel->allowedTypes)) $errors['type'] = "Invalid delivery type.";

            // Further validation for quantities if linked to PO
            if ($data['purchase_order_id']) {
                $po = $this->purchaseOrderModel->getByIdWithItems($data['purchase_order_id']);
                if ($po) {
                    $pendingPoItems = $this->getPendingPoItems($data['purchase_order_id'], $po['items']);
                    $mapPendingPoItems = array_column($pendingPoItems, null, 'id'); // Map by po_item_id

                    foreach ($itemsData as $receivedItem) {
                        if ($receivedItem['purchase_order_item_id']) {
                            $poItemId = $receivedItem['purchase_order_item_id'];
                            if (isset($mapPendingPoItems[$poItemId])) {
                                $pendingItemDetails = $mapPendingPoItems[$poItemId];
                                if ($receivedItem['quantity_received'] > $pendingItemDetails['quantity_pending']) {
                                    $errors['item_'.$poItemId] = "Quantity received for product '{$pendingItemDetails['product_name']}' ({$receivedItem['quantity_received']}) exceeds pending quantity ({$pendingItemDetails['quantity_pending']}).";
                                }
                            } else {
                                 // Item from PO, but not in pending list (already fully received or invalid PO item ID)
                                 $originalPoItem = array_values(array_filter($po['items'], fn($p) => $p['id'] == $poItemId ))[0] ?? null;
                                 if($originalPoItem && $originalPoItem['quantity_ordered'] == ($alreadyReceivedMap[$poItemId] ?? 0) ){
                                      $errors['item_'.$poItemId] = "Product '{$originalPoItem['product_name']}' from PO is already fully received.";
                                 } else {
                                      $errors['item_'.$poItemId] = "Invalid PO item ID {$poItemId} or item not pending receipt.";
                                 }
                            }
                        }
                    }
                } else {
                    $errors['purchase_order_id'] = "Invalid Purchase Order ID specified.";
                }
            }


            if (!empty($errors)) {
                // Repopulate form with errors and submitted data
                $purchaseOrder = null; $poItems = [];
                if ($data['purchase_order_id']) {
                    $purchaseOrder = $this->purchaseOrderModel->getByIdWithItems($data['purchase_order_id']);
                    if($purchaseOrder) $poItems = $this->getPendingPoItems($data['purchase_order_id'], $purchaseOrder['items']);
                }
                $suppliers = $this->supplierModel->getAll();
                $products = $this->productModel->getAll();
                $this->renderView('procurement/deliveries/create', [
                    'errors' => $errors,
                    'data' => $data,
                    'formItemsData' => $itemsData, // Submitted items for repopulation
                    'purchaseOrder' => $purchaseOrder,
                    'poItems' => $poItems,
                    'suppliers' => $suppliers,
                    'products' => $products,
                    'allowedDeliveryTypes' => $this->deliveryModel->allowedTypes,
                    'title' => $data['purchase_order_id'] ? "Create Delivery for PO-{$data['purchase_order_id']}" : 'Create Direct Delivery'
                ]);
                return;
            }

            $deliveryId = $this->deliveryModel->createDelivery($data, $itemsData);

            if ($deliveryId) {
                header("Location: /index.php?url=delivery/show/{$deliveryId}&status=created_success");
                exit;
            } else {
                $errors['general'] = 'Failed to create delivery. Stock update or PO status update might have failed.';
                 $purchaseOrder = null; $poItems = [];
                if ($data['purchase_order_id']) {
                    $purchaseOrder = $this->purchaseOrderModel->getByIdWithItems($data['purchase_order_id']);
                     if($purchaseOrder) $poItems = $this->getPendingPoItems($data['purchase_order_id'], $purchaseOrder['items']);
                }
                $suppliers = $this->supplierModel->getAll();
                $products = $this->productModel->getAll();
                $this->renderView('procurement/deliveries/create', [
                    'errors' => $errors,
                    'data' => $data,
                    'formItemsData' => $itemsData,
                    'purchaseOrder' => $purchaseOrder,
                    'poItems' => $poItems,
                    'suppliers' => $suppliers,
                    'products' => $products,
                    'allowedDeliveryTypes' => $this->deliveryModel->allowedTypes,
                    'title' => $data['purchase_order_id'] ? "Create Delivery for PO-{$data['purchase_order_id']}" : 'Create Direct Delivery'
                ]);
            }
        } else {
            header("Location: /index.php?url=delivery/create");
            exit;
        }
    }

    /**
     * Deletes a delivery. (Caution: Reverts stock, re-evaluates PO status)
     * @param int $id The ID of the delivery to delete.
     */
    public function destroy($id) {
        // Add proper authorization checks here if needed
        $delivery = $this->deliveryModel->getById($id);
        if (!$delivery) {
            header("Location: /index.php?url=delivery/index&status=delete_not_found");
            exit;
        }

        if ($this->deliveryModel->deleteDelivery($id)) {
            header("Location: /index.php?url=delivery/index&status=deleted_success");
            exit;
        } else {
            header("Location: /index.php?url=delivery/index&status=delete_error");
            exit;
        }
    }

    // Edit functionality for deliveries is often complex and risky due to stock implications.
    // Usually, a "return to supplier" or stock adjustment is done instead of editing a past delivery.
    // Thus, edit/update methods are omitted for now unless specifically requested.
}
?>
