<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/Controller.php';

class SupplierinvoiceController extends Controller {

    private $supplierInvoiceModel;
    private $supplierModel;
    private $purchaseOrderModel;
    private $deliveryModel;

    public function __construct() {
        parent::__construct();
        $this->supplierInvoiceModel = $this->loadModel('SupplierInvoice');
        $this->supplierModel = $this->loadModel('Supplier');
        $this->purchaseOrderModel = $this->loadModel('PurchaseOrder');
        $this->deliveryModel = $this->loadModel('Delivery');
    }

    public function index() {
        $invoices = $this->supplierInvoiceModel->getAllWithDetails();
        $this->renderView('procurement/supplier_invoices/index', [
            'invoices' => $invoices,
            'title' => 'Factures fournisseurs'
        ]);
    }

    public function show($id) {
        $invoice = $this->supplierInvoiceModel->getByIdWithDetails($id);
        if ($invoice) {
            $this->renderView('procurement/supplier_invoices/show', [
                'invoice' => $invoice,
                'title' => 'Détails de la facture fournisseur'
            ]);
        } else {
            $this->renderView('errors/404', ['message' => "Facture fournisseur avec l'ID {$id} non trouvée."]);
        }
    }

    public function create() {
        $deliveryId = $_GET['delivery_id'] ?? null;
        $purchaseOrderId = $_GET['po_id'] ?? null;

        $defaults = ['supplier_id' => null, 'total_amount' => '0.00', 'delivery_id' => $deliveryId, 'purchase_order_id' => $purchaseOrderId];
        $delivery = null;
        $purchaseOrder = null;

        if ($deliveryId) {
            $delivery = $this->deliveryModel->getByIdWithDetails($deliveryId);
            if ($delivery) {
                $defaults['supplier_id'] = $delivery['supplier_id'] ?? ($delivery['purchase_order_id'] ? $this->purchaseOrderModel->getById($delivery['purchase_order_id'])['supplier_id'] : null);
                $defaults['delivery_id'] = $delivery['id'];
                // Auto-fill amount from delivery items might be complex if not all items are invoiced.
                // For now, user enters total amount.
                if ($delivery['type'] === 'free_sample') {
                     $defaults['warning_message'] = "Avertissement : La livraison liée (LIV-{$deliveryId}) est marquée comme 'Échantillon gratuit'. Typiquement, les échantillons gratuits ne génèrent pas de factures à payer.";
                }
            } else {
                 $this->renderView('errors/404', ['message' => "Livraison avec l'ID {$deliveryId} non trouvée pour la création de la facture."]); return;
            }
        } elseif ($purchaseOrderId) {
            $purchaseOrder = $this->purchaseOrderModel->getById($purchaseOrderId);
            if ($purchaseOrder) {
                $defaults['supplier_id'] = $purchaseOrder['supplier_id'];
                $defaults['total_amount'] = $purchaseOrder['total_amount']; // Default to PO total
                $defaults['purchase_order_id'] = $purchaseOrder['id'];
            } else {
                 $this->renderView('errors/404', ['message' => "Bon de commande avec l'ID {$purchaseOrderId} non trouvé pour la création de la facture."]); return;
            }
        }

        $suppliers = $this->supplierModel->getAll();
        $this->renderView('procurement/supplier_invoices/create', [
            'suppliers' => $suppliers,
            'data' => $defaults, // Pre-fill data
            'allowedStatuses' => $this->supplierInvoiceModel->allowedStatuses,
            'title' => 'Créer une facture fournisseur'
        ]);
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'delivery_id' => !empty($_POST['delivery_id']) ? (int)$_POST['delivery_id'] : null,
                'purchase_order_id' => !empty($_POST['purchase_order_id']) ? (int)$_POST['purchase_order_id'] : null,
                'supplier_id' => (int)$_POST['supplier_id'],
                'invoice_number' => trim($_POST['invoice_number']),
                'invoice_date' => $_POST['invoice_date'],
                'due_date' => !empty($_POST['due_date']) ? $_POST['due_date'] : null,
                'total_amount' => (float)$_POST['total_amount'],
                'status' => $_POST['status'] ?? 'unpaid',
                'notes' => trim($_POST['notes'] ?? ''),
            ];

            $errors = [];
            if (empty($data['supplier_id'])) $errors['supplier_id'] = "Le fournisseur est requis.";
            if (empty($data['invoice_number'])) $errors['invoice_number'] = "Le numéro de facture est requis.";
            if (empty($data['invoice_date'])) $errors['invoice_date'] = "La date de facturation est requise.";
            if (!is_numeric($data['total_amount']) || $data['total_amount'] < 0) $errors['total_amount'] = "Le montant total doit être un nombre non négatif.";
            if (!in_array($data['status'], $this->supplierInvoiceModel->allowedStatuses)) $errors['status'] = "Statut non valide.";


            if (!empty($errors)) {
                $suppliers = $this->supplierModel->getAll();
                $this->renderView('procurement/supplier_invoices/create', [
                    'errors' => $errors,
                    'data' => $data,
                    'suppliers' => $suppliers,
                    'allowedStatuses' => $this->supplierInvoiceModel->allowedStatuses,
                    'title' => 'Créer une facture fournisseur'
                ]);
                return;
            }

            $invoiceId = $this->supplierInvoiceModel->createInvoice($data);

            if ($invoiceId) {
                header("Location: /index.php?url=supplierinvoice/show/{$invoiceId}&status=created_success");
                exit;
            } else {
                $errors['general'] = 'Échec de la création de la facture fournisseur. Numéro de facture en double pour ce fournisseur ?';
                $suppliers = $this->supplierModel->getAll();
                $this->renderView('procurement/supplier_invoices/create', [
                    'errors' => $errors,
                    'data' => $data,
                    'suppliers' => $suppliers,
                    'allowedStatuses' => $this->supplierInvoiceModel->allowedStatuses,
                    'title' => 'Créer une facture fournisseur'
                ]);
            }
        } else {
            header("Location: /index.php?url=supplierinvoice/create");
            exit;
        }
    }

    public function edit($id) {
        $invoice = $this->supplierInvoiceModel->getByIdWithDetails($id);
        if ($invoice) {
            // Prevent editing if fully paid? Business rule.
            // if ($invoice['status'] === 'paid') {
            //    $this->renderView('errors/403', ['message' => 'Les factures payées ne peuvent pas être modifiées.']); return;
            // }
            $suppliers = $this->supplierModel->getAll();
            $this->renderView('procurement/supplier_invoices/edit', [
                'invoice' => $invoice,
                'suppliers' => $suppliers,
                'allowedStatuses' => $this->supplierInvoiceModel->allowedStatuses,
                'title' => 'Modifier la facture fournisseur'
            ]);
        } else {
            $this->renderView('errors/404', ['message' => "Facture fournisseur avec l'ID {$id} non trouvée pour modification."]);
        }
    }

    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Fetch current invoice to prevent status override if not provided in form, or for validation.
            $currentInvoice = $this->supplierInvoiceModel->getByIdWithDetails($id);
            if (!$currentInvoice) {
                $this->renderView('errors/404', ['message' => "Facture non trouvée pour la mise à jour."]); return;
            }

            $data = [
                'delivery_id' => !empty($_POST['delivery_id']) ? (int)$_POST['delivery_id'] : null,
                'purchase_order_id' => !empty($_POST['purchase_order_id']) ? (int)$_POST['purchase_order_id'] : null,
                'supplier_id' => (int)$_POST['supplier_id'],
                'invoice_number' => trim($_POST['invoice_number']),
                'invoice_date' => $_POST['invoice_date'],
                'due_date' => !empty($_POST['due_date']) ? $_POST['due_date'] : null,
                'total_amount' => (float)$_POST['total_amount'],
                'status' => $_POST['status'] ?? $currentInvoice['status'], // Keep current if not set
                'payment_date' => !empty($_POST['payment_date']) ? $_POST['payment_date'] : null,
                'notes' => trim($_POST['notes'] ?? ''),
            ];

            // If status is changing to 'paid' and payment_date is not set, set it.
            if ($data['status'] === 'paid' && empty($data['payment_date'])) {
                $data['payment_date'] = date('Y-m-d');
            } elseif ($data['status'] !== 'paid' && $data['status'] !== 'partially_paid') {
                $data['payment_date'] = null; // Clear payment date if not paid/partially_paid
            }


            $errors = [];
            // Add validation similar to store()
             if (empty($data['supplier_id'])) $errors['supplier_id'] = "Le fournisseur est requis.";
            if (empty($data['invoice_number'])) $errors['invoice_number'] = "Le numéro de facture est requis.";
            // ... more validation

            if (!empty($errors)) {
                $suppliers = $this->supplierModel->getAll();
                // Merge $data into $currentInvoice for form repopulation to keep fields not in $data
                $invoiceForForm = array_merge($currentInvoice, $data);
                $this->renderView('procurement/supplier_invoices/edit', [
                    'errors' => $errors,
                    'invoice' => $invoiceForForm,
                    'suppliers' => $suppliers,
                    'allowedStatuses' => $this->supplierInvoiceModel->allowedStatuses,
                    'title' => 'Modifier la facture fournisseur'
                ]);
                return;
            }

            if ($this->supplierInvoiceModel->updateInvoice($id, $data)) {
                header("Location: /index.php?url=supplierinvoice/show/{$id}&status=updated_success");
                exit;
            } else {
                $errors['general'] = 'Échec de la mise à jour de la facture fournisseur. Numéro de facture en double ?';
                $suppliers = $this->supplierModel->getAll();
                $invoiceForForm = array_merge($currentInvoice, $data);
                $this->renderView('procurement/supplier_invoices/edit', [
                    'errors' => $errors,
                    'invoice' => $invoiceForForm,
                    'suppliers' => $suppliers,
                    'allowedStatuses' => $this->supplierInvoiceModel->allowedStatuses,
                    'title' => 'Modifier la facture fournisseur'
                ]);
            }
        } else {
            header("Location: /index.php?url=supplierinvoice/edit/{$id}");
            exit;
        }
    }

    public function markAsPaid($id) {
        $paymentDate = $_POST['payment_date'] ?? date('Y-m-d'); // Allow setting specific payment date or default to today
        if ($this->supplierInvoiceModel->updatePaymentStatus($id, 'paid', $paymentDate)) {
            header("Location: /index.php?url=supplierinvoice/show/{$id}&status=paid_success");
        } else {
            header("Location: /index.php?url=supplierinvoice/show/{$id}&status=paid_error");
        }
        exit;
    }


    public function destroy($id) {
        // Add checks: e.g., cannot delete 'paid' invoices in a strict system.
        $invoice = $this->supplierInvoiceModel->getByIdWithDetails($id);
        if (!$invoice) {
             header("Location: /index.php?url=supplierinvoice/index&status=delete_not_found"); exit;
        }
        // if ($invoice['status'] === 'paid') {
        //    header("Location: /index.php?url=supplierinvoice/show/{$id}&status=delete_failed_paid"); exit;
        // }

        if ($this->supplierInvoiceModel->deleteInvoice($id)) {
            header("Location: /index.php?url=supplierinvoice/index&status=deleted_success");
        } else {
            header("Location: /index.php?url=supplierinvoice/index&status=delete_error");
        }
        exit;
    }
}
?>
