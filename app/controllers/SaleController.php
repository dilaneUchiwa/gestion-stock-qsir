<?php

require_once ROOT_PATH . '/core/Controller.php';

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
            'title' => 'Historique des ventes'
        ]);
    }

    public function show($id) {
        $sale = $this->saleModel->getByIdWithDetails($id);
        if ($sale) {
            $this->renderView('sales/show', [
                'sale' => $sale,
                'title' => 'Détails de la vente'
            ]);
        } else {
            $this->renderView('errors/404', ['message' => "Vente avec l'ID {$id} non trouvée."]);
        }
    }

    private function _renderCreateForm(string $paymentType, array $data = [], array $errors = [], array $formItemsData = []) {
        $clients = $this->clientModel->getAll();
        $products = $this->productModel->getAll(); // For product selection, include selling_price

        $viewName = ($paymentType === 'immediate') ? 'sales/create_immediate' : 'sales/create_deferred';
        $title = ($paymentType === 'immediate') ? 'Nouvelle vente (paiement immédiat)' : 'Nouvelle vente (paiement différé)';

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
                $this->_renderCreateForm('immediate', $_POST, ['payment_type' => 'Type de paiement non valide spécifié.'], $_POST['items'] ?? []);
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
                $errors['client'] = "Un client (enregistré ou occasionnel) doit être spécifié.";
            }
            if (!empty($data['client_id']) && !empty($data['client_name_occasional'])) {
                 $errors['client'] = "Spécifiez soit un client enregistré, soit un nom de client occasionnel, mais pas les deux.";
            }
            if ($paymentType === 'deferred' && empty($data['due_date'])) {
                $errors['due_date'] = "La date d'échéance est requise pour les paiements différés.";
            }
            if (empty($itemsData)) {
                $errors['items'] = "Au moins un produit doit être ajouté à la vente.";
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
                $errors['general'] = $saleIdOrError ?: 'Échec de la création de la vente. Vérifiez le stock ou d\'autres détails.';
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
            $this->renderView('errors/404', ['message' => "Vente avec l'ID {$saleId} non trouvée."]);
            return;
        }
        if ($sale['payment_status'] === 'paid') {
             $this->renderView('errors/400', ['message' => "La vente avec l'ID {$saleId} est déjà marquée comme payée."]);
            return;
        }

        $this->renderView('sales/record_payment', [
            'sale' => $sale,
            'allowedPaymentStatuses' => array_filter($this->saleModel->allowedPaymentStatuses, fn($s) => in_array($s, ['paid', 'partially_paid', 'pending'])), // Limit options
            'title' => "Enregistrer le paiement pour la vente #VE-{$saleId}"
        ]);
    }

    public function process_payment_update($saleId) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $sale = $this->saleModel->getById($saleId);
            if (!$sale) {
                $this->renderView('errors/404', ['message' => "Vente avec l'ID {$saleId} non trouvée."]); return;
            }

            $newStatus = $_POST['payment_status'] ?? null;
            $paymentDate = $_POST['payment_date'] ?? date('Y-m-d'); // Default to today if not provided

            $errors = [];
            if (!in_array($newStatus, ['paid', 'partially_paid', 'pending'])) { // Valid statuses for this action
                $errors['payment_status'] = "Statut de paiement sélectionné non valide.";
            }
            if (empty($paymentDate) && ($newStatus === 'paid' || $newStatus === 'partially_paid')) {
                 $errors['payment_date'] = "La date de paiement est requise si le statut est Payé ou Partiellement payé.";
            }


            if (!empty($errors)) {
                 $saleDetails = $this->saleModel->getByIdWithDetails($saleId); // For repopulating form
                 $this->renderView('sales/record_payment', [
                    'sale' => $saleDetails,
                    'errors' => $errors,
                    'data' => $_POST, // Submitted data
                    'allowedPaymentStatuses' => array_filter($this->saleModel->allowedPaymentStatuses, fn($s) => in_array($s, ['paid', 'partially_paid', 'pending'])),
                    'title' => "Enregistrer le paiement pour la vente #VE-{$saleId}"
                ]);
                return;
            }

            if ($this->saleModel->updatePayment($saleId, $newStatus, $paymentDate)) {
                header("Location: /index.php?url=sale/show/{$saleId}&status=payment_updated");
                exit;
            } else {
                $errors['general'] = "Échec de la mise à jour du statut de paiement pour la vente #VE-{$saleId}.";
                $saleDetails = $this->saleModel->getByIdWithDetails($saleId);
                 $this->renderView('sales/record_payment', [
                    'sale' => $saleDetails,
                    'errors' => $errors,
                    'data' => $_POST,
                    'allowedPaymentStatuses' => array_filter($this->saleModel->allowedPaymentStatuses, fn($s) => in_array($s, ['paid', 'partially_paid', 'pending'])),
                    'title' => "Enregistrer le paiement pour la vente #VE-{$saleId}"
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
