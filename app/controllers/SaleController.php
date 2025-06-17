<?php

require_once ROOT_PATH . '/core/Controller.php';

class SaleController extends Controller {

    private $saleModel;
    private $clientModel;
    private $productModel;
    private $unitModel;
    private $salePaymentModel; // Added SalePaymentModel

    public function __construct() {
        parent::__construct();
        $this->saleModel = $this->loadModel('Sale');
        $this->clientModel = $this->loadModel('Client');
        $this->productModel = $this->loadModel('Product');
        $this->unitModel = $this->loadModel('Unit');
        $this->salePaymentModel = $this->loadModel('SalePayment');
    }

    public function index() {
        $sales = $this->saleModel->getAllWithClientDetails();
        $this->renderView('sales/index', [
            'sales' => $sales,
            'title' => 'Historique des ventes'
        ]);
    }

    public function show($id) {
        $sale = $this->saleModel->getByIdWithDetails($id); // This now includes 'paid_amount'
        if ($sale) {
            $paymentsHistory = [];
            // Fetch payment history only if it's relevant (e.g., for deferred or if payments exist)
            // or always, and let the view decide whether to display it.
            // For consistency with manage_payments, fetch if deferred or has payments.
            if ($sale['payment_type'] === 'deferred' || (float)$sale['paid_amount'] > 0) {
                $paymentsHistory = $this->salePaymentModel->getPaymentsForSale($id);
            }

            $this->renderView('sales/show', [
                'sale' => $sale,
                'payments_history' => $paymentsHistory,
                'title' => 'Détails de la vente'
            ]);
        } else {
            $this->renderView('errors/404', ['message' => "Vente avec l'ID {$id} non trouvée."]);
        }
    }

    private function _renderCreateForm(string $paymentType, array $data = [], array $errors = [], array $formItemsData = []) {
        $clients = $this->clientModel->getAll();
        $products = $this->productModel->getAll();
        $allUnits = $this->unitModel->getAll(); // Fetch all units for general reference

        $productUnitsMap = [];
        foreach ($products as $product) {
            $productUnitsMap[$product['id']] = $this->productModel->getUnitsForProduct($product['id']);
        }

        $viewName = ($paymentType === 'immediate') ? 'sales/create_immediate' : 'sales/create_deferred';
        $title = ($paymentType === 'immediate') ? 'Nouvelle vente (paiement immédiat)' : 'Nouvelle vente (paiement différé)';

        $this->renderView($viewName, [
            'clients' => $clients,
            'products' => $products, // Basic product data
            'allUnits' => $allUnits, // All units for fallback/general purpose
            'productUnitsMap' => $productUnitsMap, // Specific units for each product
            'data' => $data,
            'errors' => $errors,
            'formItemsData' => $formItemsData,
            'allowedPaymentTypes' => $this->saleModel->allowedPaymentTypes,
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
                // New fields for immediate payment
                'discount_amount' => isset($_POST['discount_amount']) && is_numeric($_POST['discount_amount']) ? (float)$_POST['discount_amount'] : 0.00,
                'amount_tendered' => isset($_POST['amount_tendered']) && is_numeric($_POST['amount_tendered']) ? (float)$_POST['amount_tendered'] : null,
            ];

            $itemsData = [];
            if (isset($_POST['items']) && is_array($_POST['items'])) {
                foreach ($_POST['items'] as $item) {
                    if (!empty($item['product_id']) && !empty($item['unit_id']) &&
                        isset($item['quantity_sold']) && (float)$item['quantity_sold'] > 0 &&
                        isset($item['unit_price']) && is_numeric($item['unit_price'])) { // Ensure unit_price is numeric
                        $itemsData[] = [
                            'product_id' => (int)$item['product_id'],
                            'unit_id' => (int)$item['unit_id'],
                            'quantity_sold' => (float)$item['quantity_sold'],
                            'unit_price' => (float)$item['unit_price'],
                        ];
                    }
                }
            }

            // Validation
            $errors = [];
            // Gross total calculation (server-side)
            $grossTotal = 0;
            foreach ($itemsData as $item) {
                $grossTotal += $item['quantity_sold'] * $item['unit_price'];
            }

            if ($data['discount_amount'] < 0) {
                $errors['discount_amount'] = "Le montant de la réduction ne peut pas être négatif.";
            }
            if ($data['discount_amount'] > $grossTotal) {
                $errors['discount_amount'] = "La réduction ne peut pas excéder le total brut des articles (" . number_format($grossTotal, 2) . ").";
            }

            $netTotalPayable = $grossTotal - $data['discount_amount'];
            $data['total_amount'] = $netTotalPayable; // This is what will be stored in sales.total_amount

            if ($paymentType === 'immediate') {
                if ($data['amount_tendered'] === null || $data['amount_tendered'] < $netTotalPayable) {
                    $errors['amount_tendered'] = "Montant versé insuffisant. Doit être au moins " . number_format($netTotalPayable, 2) . ".";
                } else {
                    $data['change_due'] = $data['amount_tendered'] - $netTotalPayable;
                    if ($data['change_due'] < 0) { // Should be caught by previous check, but good for safety
                        $errors['change_due'] = "Erreur dans le calcul de la monnaie (montant versé inférieur au net à payer).";
                    }
                }
            } else { // For deferred payment, clear these fields
                $data['amount_tendered'] = null;
                $data['change_due'] = null;
            }

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
                $errors['items'] = "Au moins un produit doit être ajouté à la vente (avec produit, unité, quantité et prix).";
            }

            // Item specific validation (unit and stock)
            foreach ($itemsData as $idx => $item) {
                if (empty($item['unit_id'])) { // Should have been caught by itemsData population check, but defensive
                    $errors["item_{$idx}_unit_id"] = "L'unité pour l'article est requise.";
                    continue;
                }
                if (!$this->productModel->isUnitValidForProduct($item['product_id'], $item['unit_id'])) {
                    $productInfo = $this->productModel->getById($item['product_id']);
                    $unitInfo = $this->unitModel->getById($item['unit_id']);
                    $errors["item_{$idx}_unit_id"] = "L'unité '".($unitInfo['name'] ?? $item['unit_id'])."' n'est pas valide pour le produit '".($productInfo['name'] ?? $item['product_id'])."'.";
                    continue; // Skip stock check if unit is invalid
                }

                // Stock availability check (pre-validation before calling model's createSale)
                $productDetails = $this->productModel->getById($item['product_id']); // Fetches quantity_in_stock in base unit
                $quantitySoldInBaseUnit = (float)$item['quantity_sold'];
                if ($item['unit_id'] != $productDetails['base_unit_id']) {
                    $productUnits = $this->productModel->getUnitsForProduct($item['product_id']);
                    $conversionFactor = null;
                    foreach ($productUnits as $pu) {
                        if ($pu['unit_id'] == $item['unit_id']) {
                            $conversionFactor = (float)$pu['conversion_factor_to_base_unit'];
                            break;
                        }
                    }
                    if ($conversionFactor === null || $conversionFactor == 0) {
                        $errors["item_{$idx}_unit_conversion"] = "Erreur de conversion d'unité pour le produit {$productDetails['name']}.";
                        continue; // Skip stock check
                    }
                    $quantitySoldInBaseUnit *= $conversionFactor;
                }

                if ($productDetails['quantity_in_stock'] < $quantitySoldInBaseUnit) {
                    $unitInfo = $this->unitModel->getById($item['unit_id']);
                    $errors["item_{$idx}_stock"] = "Stock insuffisant pour {$productDetails['name']}. Demandé (en unité de base): {$quantitySoldInBaseUnit}, Disponible: {$productDetails['quantity_in_stock']}.";
                }
            }


            if (!empty($errors)) {
                // Repass $_POST directly for $data to preserve all originally submitted fields
                $this->_renderCreateForm($paymentType, $_POST, $errors, $itemsData);
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

    /**
     * Displays the form to manage payments for a deferred sale.
     * Shows payment history and form to add new payment.
     * @param int $saleId The ID of the sale.
     */
    public function manage_payments($saleId) {
        $sale = $this->saleModel->getByIdWithDetails($saleId); // Includes items, client details, and now paid_amount
        if (!$sale) {
            $this->renderView('errors/404', ['message' => "Vente avec l'ID {$saleId} non trouvée."]);
            return;
        }

        if ($sale['payment_type'] !== 'deferred' && $sale['payment_status'] === 'paid') {
             // For immediate paid sales, typically no further payments are managed this way
             // unless it's to correct/edit the initial payment (which is more complex).
             // For now, redirect or show a message.
            $this->renderView('errors/400', [
                'message' => "La gestion des paiements multiples n'est généralement pas applicable aux ventes immédiates déjà payées.",
                'title' => 'Gestion des paiements'
            ]);
            return;
        }

        $paymentsHistory = $this->salePaymentModel->getPaymentsForSale($saleId);
        // $totalPaid is now part of $sale['paid_amount'] after SaleModel->updateSalePaymentStatus is called.
        // For display consistency, we use $sale['paid_amount'].
        $remainingBalance = (float)$sale['total_amount'] - (float)$sale['paid_amount'];

        $this->renderView('sales/manage_payments', [
            'sale' => $sale,
            'payments_history' => $paymentsHistory,
            'remaining_balance' => $remainingBalance,
            'allowedPaymentMethods' => $this->salePaymentModel->allowedPaymentMethods, // Pass for dropdown
            'title' => "Gérer les paiements pour la Vente #VE-{$saleId}",
            'data' => [], // For form repopulation on error
            'errors' => []
        ]);
    }

    // Note: process_payment_update and record_payment methods are effectively replaced by manage_payments and store_payment.
    // They can be removed or commented out once the new flow is confirmed.
    /*
    public function record_payment($saleId) { ... }
    public function process_payment_update($saleId) { ... }
    */

    /**
     * Stores a new payment for a sale.
     */
    public function store_payment() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            // Or redirect to a sensible default, e.g., sales index
            $this->renderView('errors/405', ['message' => 'Méthode non autorisée.']);
            return;
        }

        $saleId = $_POST['sale_id'] ?? null;
        $paymentData = [
            'sale_id' => (int)$saleId,
            'payment_date' => $_POST['payment_date'] ?? date('Y-m-d'),
            'amount_paid' => isset($_POST['amount_paid']) && is_numeric($_POST['amount_paid']) ? (float)$_POST['amount_paid'] : 0,
            'payment_method' => trim($_POST['payment_method'] ?? ''),
            'notes' => trim($_POST['notes'] ?? '')
        ];

        $errors = [];
        if (empty($paymentData['sale_id'])) {
            $errors['sale_id'] = "ID de la vente manquant.";
        }
        if (empty($paymentData['payment_date'])) { // Basic date validation, can be improved
            $errors['payment_date'] = "Date de paiement requise.";
        }
        if ($paymentData['amount_paid'] <= 0) {
            $errors['amount_paid'] = "Le montant payé doit être positif.";
        }
        if (empty($paymentData['payment_method'])) {
            $errors['payment_method'] = "Méthode de paiement requise.";
        }
        // Optional: Validate payment_method against $this->salePaymentModel->allowedPaymentMethods

        $sale = null;
        if ($saleId) {
            $sale = $this->saleModel->getById($saleId);
            if (!$sale) {
                $errors['sale_id'] = "Vente avec l'ID {$saleId} non trouvée.";
            } else {
                // Check for overpayment (basic check)
                $remainingBalance = (float)$sale['total_amount'] - (float)$sale['paid_amount'];
                if ($paymentData['amount_paid'] > $remainingBalance + 0.001) { // Add small tolerance for float comparison
                    $errors['amount_paid'] = "Le montant payé (".number_format($paymentData['amount_paid'],2).") ne peut pas excéder le solde restant (".number_format($remainingBalance,2).").";
                }
            }
        }

        if (!empty($errors)) {
            // Re-render the manage_payments form with errors and data
            $paymentsHistory = $saleId ? $this->salePaymentModel->getPaymentsForSale($saleId) : [];
            $currentSaleDetails = $saleId ? $this->saleModel->getByIdWithDetails($saleId) : null; // For display
            $this->renderView('sales/manage_payments', [
                'sale' => $currentSaleDetails, // Pass full sale details if available
                'payments_history' => $paymentsHistory,
                'remaining_balance' => $sale ? ((float)$sale['total_amount'] - (float)$sale['paid_amount']) : 0,
                'allowedPaymentMethods' => $this->salePaymentModel->allowedPaymentMethods,
                'title' => $saleId ? "Gérer les paiements pour la Vente #VE-{$saleId}" : "Erreur de paiement",
                'data' => $paymentData, // Submitted payment data for repopulation
                'errors' => $errors
            ]);
            return;
        }

        $paymentId = $this->salePaymentModel->createPayment($paymentData);

        if ($paymentId) {
            // Update sale's overall payment status and paid_amount
            $statusUpdated = $this->saleModel->updateSalePaymentStatus($paymentData['sale_id']);
            if (!$statusUpdated) {
                // Log this error, but the payment itself was created.
                // This might leave data in an inconsistent state if not handled well.
                // For now, redirect with success for payment, but admin should be aware.
                error_log("Payment ID {$paymentId} created for sale ID {$paymentData['sale_id']}, but failed to update overall sale payment status.");
                // Consider adding a more specific error/warning to the user.
            }
            header("Location: /index.php?url=sale/manage_payments/{$paymentData['sale_id']}&status=payment_success");
            exit;
        } else {
            $errors['general'] = "Échec de l'enregistrement du paiement.";
            $paymentsHistory = $this->salePaymentModel->getPaymentsForSale($saleId);
            $currentSaleDetails = $this->saleModel->getByIdWithDetails($saleId);
             $this->renderView('sales/manage_payments', [
                'sale' => $currentSaleDetails,
                'payments_history' => $paymentsHistory,
                'remaining_balance' => $sale ? ((float)$sale['total_amount'] - (float)$sale['paid_amount']) : 0,
                'allowedPaymentMethods' => $this->salePaymentModel->allowedPaymentMethods,
                'title' => "Gérer les paiements pour la Vente #VE-{$saleId}",
                'data' => $paymentData,
                'errors' => $errors
            ]);
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

    /**
     * Generates a printable invoice for a sale.
     * @param int $saleId The ID of the sale.
     */
    public function print_invoice($saleId) {
        $sale = $this->saleModel->getByIdWithDetails($saleId); // This fetches items and client info
        if (!$sale) {
            $this->renderView('errors/404', ['message' => "Facture de vente avec l'ID {$saleId} non trouvée."]);
            return;
        }

        // Data for the view
        $data = [
            'sale' => $sale,
            'title' => "Facture #VE-" . $sale['id']
            // Add company details here if they are dynamic, e.g., from a config or DB
            // 'company_name' => 'ENTREPRISE XYZ',
            // 'company_address' => '123 Rue de la Facture, 75000 Paris', etc.
        ];
        $this->renderPrintView('sales/print_invoice', $data);
    }

    /**
     * Generates a printable payment receipt.
     * @param int $paymentId The ID of the payment.
     */
    public function print_payment_receipt($paymentId) {
        $payment = $this->salePaymentModel->getById($paymentId);
        if (!$payment) {
            $this->renderView('errors/404', ['message' => "Reçu de paiement avec l'ID {$paymentId} non trouvé."]);
            return;
        }

        $sale = $this->saleModel->getByIdWithDetails($payment['sale_id']);
        if (!$sale) {
            $this->renderView('errors/404', ['message' => "Vente associée (ID {$payment['sale_id']}) non trouvée pour le reçu."]);
            return;
        }

        // Calculate total paid up to this specific receipt
        $totalPaidUpToThisReceipt = $this->salePaymentModel->getTotalPaidUpTo(
            $payment['sale_id'],
            $payment['payment_date'],
            $payment['id']
        );
        $balanceDueAfterThisReceipt = (float)$sale['total_amount'] - $totalPaidUpToThisReceipt;

        $data = [
            'payment' => $payment,
            'sale' => $sale,
            'totalPaidUpToThisReceipt' => $totalPaidUpToThisReceipt,
            'balanceDueAfterThisReceipt' => $balanceDueAfterThisReceipt,
            'title' => "Reçu de Paiement #P" . $payment['id']
        ];
        $this->renderPrintView('sales/print_payment_receipt', $data);
    }
}
?>
