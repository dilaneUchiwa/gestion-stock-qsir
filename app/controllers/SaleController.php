<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/Controller.php';

class SaleController extends Controller {

    private $saleModel;
    private $clientModel;
    private $productModel;

    public function __construct() {
        parent::__construct();
        $this->saleModel = $this->loadModel('Sale');
        $this->clientModel = $this->loadModel('Client');
        $this->productModel = $this->loadModel('Product');
    }

    public function index() {
        $sales = $this->saleModel->getAllWithClientDetails();
        $this->renderView('sales/index', [
            'sales' => $sales,
            'title' => 'Sales History'
        ]);
    }

    public function show($id) {
        $sale = $this->saleModel->getByIdWithDetails($id);
        if ($sale) {
            $this->renderView('sales/show', [
                'sale' => $sale,
                'title' => 'Sale Details'
            ]);
        } else {
            $this->renderView('errors/404', ['message' => "Sale with ID {$id} not found."]);
        }
    }

    private function _renderCreateForm(string $paymentType, array $data = [], array $errors = [], array $formItemsData = []) {
        $clients = $this->clientModel->getAll();
        $products = $this->productModel->getAll(); // For product selection, include selling_price

        $viewName = ($paymentType === 'immediate') ? 'sales/create_immediate' : 'sales/create_deferred';
        $title = ($paymentType === 'immediate') ? 'New Sale (Immediate Payment)' : 'New Sale (Deferred Payment)';

        $this->renderView($viewName, [
            'clients' => $clients,
            'products' => $products,
            'data' => $data, // For repopulating form
            'errors' => $errors,
            'formItemsData' => $formItemsData, // For repopulating items
            'allowedPaymentTypes' => $this->saleModel->allowedPaymentTypes, // Though specific to form type
            'allowedPaymentStatuses' => $this->saleModel->allowedPaymentStatuses,
            'title' => $title
        ]);
    }

    public function create_immediate_payment() {
        $this->_renderCreateForm('immediate');
    }

    public function create_deferred_payment() {
        $this->_renderCreateForm('deferred');
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $paymentType = $_POST['payment_type'] ?? null;
            if (!in_array($paymentType, $this->saleModel->allowedPaymentTypes)) {
                // Should not happen if form is correct, but good to check.
                $this->_renderCreateForm('immediate', $_POST, ['payment_type' => 'Invalid payment type specified.'], $_POST['items'] ?? []);
                return;
            }

            $data = [
                'client_id' => !empty($_POST['client_id']) ? (int)$_POST['client_id'] : null,
                'client_name_occasional' => empty($_POST['client_id']) && !empty($_POST['client_name_occasional']) ? trim($_POST['client_name_occasional']) : null,
                'sale_date' => $_POST['sale_date'] ?? date('Y-m-d'),
                'payment_type' => $paymentType,
                'payment_status' => $_POST['payment_status'] ?? (($paymentType === 'immediate') ? 'paid' : 'pending'),
                'due_date' => ($paymentType === 'deferred' && !empty($_POST['due_date'])) ? $_POST['due_date'] : null,
                'notes' => trim($_POST['notes'] ?? ''),
            ];

            $itemsData = [];
            if (isset($_POST['items']) && is_array($_POST['items'])) {
                foreach ($_POST['items'] as $item) {
                    if (!empty($item['product_id']) && isset($item['quantity_sold']) && (int)$item['quantity_sold'] > 0 && isset($item['unit_price'])) {
                        $itemsData[] = [
                            'product_id' => (int)$item['product_id'],
                            'quantity_sold' => (int)$item['quantity_sold'],
                            'unit_price' => (float)$item['unit_price'], // Price might be overridden from product's default
                        ];
                    }
                }
            }

            // Validation
            $errors = [];
            if (empty($data['client_id']) && empty($data['client_name_occasional'])) {
                $errors['client'] = "A client (registered or occasional) must be specified.";
            }
            if (!empty($data['client_id']) && !empty($data['client_name_occasional'])) {
                 $errors['client'] = "Specify either a registered client or an occasional client name, not both.";
            }
            if ($paymentType === 'deferred' && empty($data['due_date'])) {
                $errors['due_date'] = "Due date is required for deferred payments.";
            }
            if (empty($itemsData)) {
                $errors['items'] = "At least one product must be added to the sale.";
            }
            // Additional validation for unit price, quantity > 0 already handled in itemsData loop.


            if (!empty($errors)) {
                $this->_renderCreateForm($paymentType, array_merge($_POST, $data), $errors, $itemsData); // Pass original POST for other fields
                return;
            }

            $saleIdOrError = $this->saleModel->createSale($data, $itemsData);

            if (is_numeric($saleIdOrError)) {
                header("Location: /index.php?url=sale/show/{$saleIdOrError}&status=created_success");
                exit;
            } else {
                // An error message string was returned (e.g. "Insufficient stock...")
                $errors['general'] = $saleIdOrError ?: 'Failed to create sale. Check stock or other details.';
                $this->_renderCreateForm($paymentType, array_merge($_POST, $data), $errors, $itemsData);
            }
        } else {
            // Redirect to a default creation form if accessed via GET or wrong method
            header("Location: /index.php?url=sale/create_immediate_payment");
            exit;
        }
    }

    public function record_payment($saleId) {
        $sale = $this->saleModel->getByIdWithDetails($saleId);
        if (!$sale) {
            $this->renderView('errors/404', ['message' => "Sale with ID {$saleId} not found."]);
            return;
        }
        if ($sale['payment_status'] === 'paid') {
             $this->renderView('errors/400', ['message' => "Sale with ID {$saleId} is already marked as paid."]);
            return;
        }

        $this->renderView('sales/record_payment', [
            'sale' => $sale,
            'allowedPaymentStatuses' => array_filter($this->saleModel->allowedPaymentStatuses, fn($s) => in_array($s, ['paid', 'partially_paid', 'pending'])), // Limit options
            'title' => "Record Payment for Sale #SA-{$saleId}"
        ]);
    }

    public function process_payment_update($saleId) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $sale = $this->saleModel->getById($saleId);
            if (!$sale) {
                $this->renderView('errors/404', ['message' => "Sale with ID {$saleId} not found."]); return;
            }

            $newStatus = $_POST['payment_status'] ?? null;
            $paymentDate = $_POST['payment_date'] ?? date('Y-m-d'); // Default to today if not provided

            $errors = [];
            if (!in_array($newStatus, ['paid', 'partially_paid', 'pending'])) { // Valid statuses for this action
                $errors['payment_status'] = "Invalid payment status selected.";
            }
            if (empty($paymentDate) && ($newStatus === 'paid' || $newStatus === 'partially_paid')) {
                 $errors['payment_date'] = "Payment date is required if status is Paid or Partially Paid.";
            }


            if (!empty($errors)) {
                 $saleDetails = $this->saleModel->getByIdWithDetails($saleId); // For repopulating form
                 $this->renderView('sales/record_payment', [
                    'sale' => $saleDetails,
                    'errors' => $errors,
                    'data' => $_POST, // Submitted data
                    'allowedPaymentStatuses' => array_filter($this->saleModel->allowedPaymentStatuses, fn($s) => in_array($s, ['paid', 'partially_paid', 'pending'])),
                    'title' => "Record Payment for Sale #SA-{$saleId}"
                ]);
                return;
            }

            if ($this->saleModel->updatePayment($saleId, $newStatus, $paymentDate)) {
                header("Location: /index.php?url=sale/show/{$saleId}&status=payment_updated");
                exit;
            } else {
                $errors['general'] = "Failed to update payment status for Sale #SA-{$saleId}.";
                $saleDetails = $this->saleModel->getByIdWithDetails($saleId);
                 $this->renderView('sales/record_payment', [
                    'sale' => $saleDetails,
                    'errors' => $errors,
                    'data' => $_POST,
                    'allowedPaymentStatuses' => array_filter($this->saleModel->allowedPaymentStatuses, fn($s) => in_array($s, ['paid', 'partially_paid', 'pending'])),
                    'title' => "Record Payment for Sale #SA-{$saleId}"
                ]);
            }
        } else {
            header("Location: /index.php?url=sale/record_payment/{$saleId}");
            exit;
        }
    }

    // Optional: destroy method (with stock reversion logic)
    public function destroy($saleId) {
        // Add role/permission check if necessary
        $result = $this->saleModel->deleteSale($saleId);
        if ($result === true) {
            header("Location: /index.php?url=sale/index&status=deleted_success");
        } elseif (is_string($result)) { // Error message string
            header("Location: /index.php?url=sale/index&status=delete_failed&reason=" . urlencode($result));
        }
        else { // General false
            header("Location: /index.php?url=sale/index&status=delete_error");
        }
        exit;
    }

}
?>
