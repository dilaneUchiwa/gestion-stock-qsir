<?php

require_once ROOT_PATH . '/core/Controller.php';

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
        $invoice = $this->supplierInvoiceModel->getByIdWithDetails($id); // This now includes payments
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
                $defaults['total_amount'] = $purchaseOrder['total_amount'];
                $defaults['purchase_order_id'] = $purchaseOrder['id'];
            } else {
                 $this->renderView('errors/404', ['message' => "Bon de commande avec l'ID {$purchaseOrderId} non trouvé pour la création de la facture."]); return;
            }
        }

        $suppliers = $this->supplierModel->getAll();
        $this->renderView('procurement/supplier_invoices/create', [
            'suppliers' => $suppliers,
            'data' => $defaults,
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
                'status' => $_POST['status'] ?? $currentInvoice['status'],
                'payment_date' => !empty($_POST['payment_date']) ? $_POST['payment_date'] : null, // This might be better handled by addPayment logic
                'notes' => trim($_POST['notes'] ?? ''),
            ];
             // Logic for payment_date related to status is now primarily in SupplierInvoiceModel->updateInvoicePaymentStatus
            // However, if status is directly set here and it's 'paid' without a payment date, model might set it.
            // If status is changed away from 'paid', model should handle clearing payment_date.

            $errors = [];
            if (empty($data['supplier_id'])) $errors['supplier_id'] = "Le fournisseur est requis.";
            if (empty($data['invoice_number'])) $errors['invoice_number'] = "Le numéro de facture est requis.";
            // ... more validation

            if (!empty($errors)) {
                $suppliers = $this->supplierModel->getAll();
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

            if ($this->supplierInvoiceModel->updateInvoice($id, $data)) { // updateInvoice now calls updateInvoicePaymentStatus if total_amount changes
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

    public function addPayment($invoiceId) {
        $invoice = $this->supplierInvoiceModel->getByIdWithDetails((int)$invoiceId);
        if (!$invoice) {
            $this->renderView('errors/404', ['message' => "Facture fournisseur avec l'ID {$invoiceId} non trouvée."]);
            return;
        }

        $remainingBalance = (float)$invoice['total_amount'] - (float)($invoice['paid_amount'] ?? 0.00);

        $this->renderView('procurement/supplier_invoices/add_payment', [
            'invoice' => $invoice,
            'remainingBalance' => $remainingBalance,
            'data' => [],
            'errors' => [],
            'title' => "Ajouter un paiement pour Facture Fournisseur #" . htmlspecialchars($invoice['invoice_number'])
        ]);
    }

    public function storePayment() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /index.php?url=supplierinvoice/index");
            exit;
        }

        $invoiceId = $_POST['supplier_invoice_id'] ?? null;
        $paymentData = [
            'supplier_invoice_id' => $invoiceId ? (int)$invoiceId : null,
            'payment_date' => $_POST['payment_date'] ?? null,
            'amount_paid' => isset($_POST['amount_paid']) && is_numeric($_POST['amount_paid']) ? (float)$_POST['amount_paid'] : null,
            'payment_method' => trim($_POST['payment_method'] ?? ''),
            'notes' => trim($_POST['notes'] ?? '')
        ];

        $errors = [];
        if (empty($paymentData['supplier_invoice_id'])) $errors['supplier_invoice_id'] = "ID de la facture manquant.";
        if (empty($paymentData['payment_date'])) $errors['payment_date'] = "Date de paiement requise.";
        if ($paymentData['amount_paid'] === null || $paymentData['amount_paid'] <= 0) {
            $errors['amount_paid'] = "Le montant payé doit être un nombre positif.";
        }

        $invoice = null;
        if ($invoiceId) {
            $invoice = $this->supplierInvoiceModel->getByIdWithDetails((int)$invoiceId);
            if (!$invoice) {
                if (!isset($errors['supplier_invoice_id'])) $errors['supplier_invoice_id'] = "Facture fournisseur avec l'ID {$invoiceId} non trouvée.";
            } else {
                $remainingBalance = (float)$invoice['total_amount'] - (float)($invoice['paid_amount'] ?? 0.00);
                if ($paymentData['amount_paid'] !== null && $paymentData['amount_paid'] > ($remainingBalance + 0.001)) {
                    $errors['amount_paid'] = "Le montant payé (".number_format($paymentData['amount_paid'],2).") ne peut pas excéder le solde restant (".number_format($remainingBalance,2).").";
                }
            }
        } else {
             if (!isset($errors['supplier_invoice_id'])) $errors['supplier_invoice_id'] = "ID de la facture non fourni.";
        }

        if (!empty($errors)) {
            $remainingBalanceForView = 0;
            if ($invoice) {
                $remainingBalanceForView = (float)$invoice['total_amount'] - (float)($invoice['paid_amount'] ?? 0.00);
            }
            $this->renderView('procurement/supplier_invoices/add_payment', [
                'invoice' => $invoice,
                'remainingBalance' => $remainingBalanceForView,
                'data' => $_POST,
                'errors' => $errors,
                'title' => $invoice ? "Ajouter un paiement pour Facture Fournisseur #" . htmlspecialchars($invoice['invoice_number']) : "Erreur d'ajout de paiement"
            ]);
            return;
        }

        $paymentIdOrError = $this->supplierInvoiceModel->addPayment($paymentData);

        if (is_numeric($paymentIdOrError)) {
            header("Location: /index.php?url=supplierinvoice/show/" . $paymentData['supplier_invoice_id'] . "&status=payment_success");
            exit;
        } else {
            $errors['general'] = is_string($paymentIdOrError) ? $paymentIdOrError : "Échec de l'ajout du paiement.";
            $invoice = $this->supplierInvoiceModel->getByIdWithDetails($paymentData['supplier_invoice_id']); // Re-fetch for updated paid_amount
            $remainingBalance = $invoice ? (float)$invoice['total_amount'] - (float)($invoice['paid_amount'] ?? 0.00) : 0;

            $this->renderView('procurement/supplier_invoices/add_payment', [
                'invoice' => $invoice,
                'remainingBalance' => $remainingBalance,
                'data' => $_POST,
                'errors' => $errors,
                'title' => $invoice ? "Ajouter un paiement pour Facture Fournisseur #" . htmlspecialchars($invoice['invoice_number']) : "Erreur d'ajout de paiement"
            ]);
        }
    }

    public function destroy($id) {
        $invoice = $this->supplierInvoiceModel->getByIdWithDetails($id);
        if (!$invoice) {
             header("Location: /index.php?url=supplierinvoice/index&status=delete_not_found"); exit;
        }
        // Optional: Add checks, e.g., cannot delete 'paid' invoices in a strict system.
        // if ($invoice['status'] === 'paid' || $invoice['status'] === 'partially_paid') {
        //    header("Location: /index.php?url=supplierinvoice/show/{$id}&status=delete_failed_has_payments"); exit;
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
