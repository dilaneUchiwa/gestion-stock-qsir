<?php

require_once ROOT_PATH . '/core/Controller.php';

class ReportController extends Controller {

    public function __construct() {
        parent::__construct();
        // Models will be loaded as needed within each report method
    }

    /**
     * Displays the main reports index page.
     */
    public function index() {
        $this->renderView('reports/index', [
            'title' => 'Rapports disponibles'
        ]);
    }

    // Methods for specific reports will be added here
    // current_stock(), stock_entries(), stock_exits(), sales_report(), purchases_report()

    /**
     * Report: Current Stock Levels
     */
    public function current_stock() {
        $productModel = $this->loadModel('Product');
        $allProducts = $productModel->getAll(); // Fetches products with base unit and category info

        $enrichedProducts = [];
        foreach ($allProducts as $product) {
            $product['configured_units'] = $productModel->getUnitsForProduct($product['id']);
            $enrichedProducts[] = $product;
        }

        // Potential filters (example: low stock) - applied on enriched data if needed, or before enrichment
        $filter_low_stock_threshold = isset($_GET['low_stock_threshold']) ? (int)$_GET['low_stock_threshold'] : null;
        if ($filter_low_stock_threshold !== null) {
            // This filter still works on quantity_in_stock which is in base unit.
            $enrichedProducts = array_filter($enrichedProducts, function($product) use ($filter_low_stock_threshold) {
                return $product['quantity_in_stock'] <= $filter_low_stock_threshold;
            });
        }

        $this->renderView('reports/stock_status_report', [ // Changed view name
            'products' => $enrichedProducts,
            'low_stock_threshold' => $filter_low_stock_threshold,
            'title' => 'Rapport d\'État du Stock Actuel' // Changed title
        ]);
    }

    /**
     * Report: Stock Entries
     */
    public function stock_entries() {
        $stockMovementModel = $this->loadModel('StockMovement');
        $productModel = $this->loadModel('Product'); // For product filter
        $products = $productModel->getAll();


        $today = date('Y-m-d');
        $defaultStartDate = date('Y-m-01'); // First day of current month

        $filters = [
            'start_date' => $_GET['start_date'] ?? $defaultStartDate,
            'end_date' => $_GET['end_date'] ?? $today,
            'product_id' => isset($_GET['product_id']) && $_GET['product_id'] !== '' ? (int)$_GET['product_id'] : null,
            'period' => $_GET['period'] ?? 'custom' // 'today', 'yesterday', 'last7days', 'last30days', 'this_month', 'last_month'
        ];

        // Adjust dates based on predefined period
        if ($filters['period'] !== 'custom') {
            switch ($filters['period']) {
                case 'today':
                    $filters['start_date'] = $today;
                    $filters['end_date'] = $today;
                    break;
                case 'yesterday':
                    $filters['start_date'] = date('Y-m-d', strtotime('-1 day'));
                    $filters['end_date'] = date('Y-m-d', strtotime('-1 day'));
                    break;
                case 'last7days':
                    $filters['start_date'] = date('Y-m-d', strtotime('-6 days')); // includes today
                    $filters['end_date'] = $today;
                    break;
                case 'last30days':
                    $filters['start_date'] = date('Y-m-d', strtotime('-29 days')); // includes today
                    $filters['end_date'] = $today;
                    break;
                case 'this_month':
                    $filters['start_date'] = date('Y-m-01');
                    $filters['end_date'] = $today; // Or date('Y-m-t') for full month if not current month
                    break;
                case 'last_month':
                    $filters['start_date'] = date('Y-m-01', strtotime('first day of last month'));
                    $filters['end_date'] = date('Y-m-t', strtotime('last day of last month'));
                    break;
            }
        }


        $entryTypes = ['in_delivery', 'adjustment_in', 'split_in', 'initial_stock', 'sale_reversal'];
        $dateRange = ['start_date' => $filters['start_date'], 'end_date' => $filters['end_date']];

        $movements = $stockMovementModel->getMovementsByTypeAndDateRange($entryTypes, $dateRange, $filters['product_id']);

        $this->renderView('reports/stock_entries_report', [
            'movements' => $movements,
            'filters' => $filters,
            'products' => $products,
            'title' => 'Rapport des entrées en stock'
        ]);
    }

    /**
     * Report: Stock Exits
     */
    public function stock_exits() {
        $stockMovementModel = $this->loadModel('StockMovement');
        $productModel = $this->loadModel('Product');
        $products = $productModel->getAll();

        $today = date('Y-m-d');
        $defaultStartDate = date('Y-m-01');

        $filters = [
            'start_date' => $_GET['start_date'] ?? $defaultStartDate,
            'end_date' => $_GET['end_date'] ?? $today,
            'product_id' => isset($_GET['product_id']) && $_GET['product_id'] !== '' ? (int)$_GET['product_id'] : null,
            'period' => $_GET['period'] ?? 'custom'
        ];

        if ($filters['period'] !== 'custom') {
            // Same period logic as stock_entries
             switch ($filters['period']) {
                case 'today': $filters['start_date'] = $today; $filters['end_date'] = $today; break;
                case 'yesterday': $filters['start_date'] = date('Y-m-d', strtotime('-1 day')); $filters['end_date'] = date('Y-m-d', strtotime('-1 day')); break;
                case 'last7days': $filters['start_date'] = date('Y-m-d', strtotime('-6 days')); $filters['end_date'] = $today; break;
                case 'last30days': $filters['start_date'] = date('Y-m-d', strtotime('-29 days')); $filters['end_date'] = $today; break;
                case 'this_month': $filters['start_date'] = date('Y-m-01'); $filters['end_date'] = $today; break;
                case 'last_month': $filters['start_date'] = date('Y-m-01', strtotime('first day of last month')); $filters['end_date'] = date('Y-m-t', strtotime('last day of last month')); break;
            }
        }

        $exitTypes = ['out_sale', 'adjustment_out', 'split_out', 'delivery_reversal'];
        $dateRange = ['start_date' => $filters['start_date'], 'end_date' => $filters['end_date']];

        $movements = $stockMovementModel->getMovementsByTypeAndDateRange($exitTypes, $dateRange, $filters['product_id']);

        $this->renderView('reports/stock_exits_report', [
            'movements' => $movements,
            'filters' => $filters,
            'products' => $products,
            'title' => 'Rapport des sorties de stock'
        ]);
    }

    /**
     * Report: Sales
     */
    public function sales_report() {
        $saleModel = $this->loadModel('Sale');
        $clientModel = $this->loadModel('Client');
        $clients = $clientModel->getAll(); // For client filter dropdown

        $today = date('Y-m-d');
        $defaultStartDate = date('Y-m-01');

        $filters = [
            'start_date' => $_GET['start_date'] ?? $defaultStartDate,
            'end_date' => $_GET['end_date'] ?? $today,
            'client_id' => isset($_GET['client_id']) && $_GET['client_id'] !== '' ? (int)$_GET['client_id'] : null,
            'payment_status' => isset($_GET['payment_status']) && $_GET['payment_status'] !== '' ? $_GET['payment_status'] : null,
            'period' => $_GET['period'] ?? 'custom'
        ];

        if ($filters['period'] !== 'custom') {
            // Same period logic as stock_entries/exits
            switch ($filters['period']) {
                case 'today': $filters['start_date'] = $today; $filters['end_date'] = $today; break;
                case 'yesterday': $filters['start_date'] = date('Y-m-d', strtotime('-1 day')); $filters['end_date'] = date('Y-m-d', strtotime('-1 day')); break;
                case 'last7days': $filters['start_date'] = date('Y-m-d', strtotime('-6 days')); $filters['end_date'] = $today; break;
                case 'last30days': $filters['start_date'] = date('Y-m-d', strtotime('-29 days')); $filters['end_date'] = $today; break;
                case 'this_month': $filters['start_date'] = date('Y-m-01'); $filters['end_date'] = $today; break;
                case 'last_month': $filters['start_date'] = date('Y-m-01', strtotime('first day of last month')); $filters['end_date'] = date('Y-m-t', strtotime('last day of last month')); break;
            }
        }

        $sales = $saleModel->getSalesByDateRangeAndFilters($filters);

        $this->renderView('reports/sales_report', [
            'sales' => $sales,
            'filters' => $filters,
            'clients' => $clients,
            'allowedPaymentStatuses' => $saleModel->allowedPaymentStatuses,
            'title' => 'Rapport des ventes'
        ]);
    }

    /**
     * Report: Purchases (based on Purchase Orders)
     */
    public function purchases_report() {
        $purchaseOrderModel = $this->loadModel('PurchaseOrder');
        $supplierModel = $this->loadModel('Supplier');
        $suppliers = $supplierModel->getAll(); // For supplier filter

        $today = date('Y-m-d');
        $defaultStartDate = date('Y-m-01');

        $filters = [
            'start_date' => $_GET['start_date'] ?? $defaultStartDate,
            'end_date' => $_GET['end_date'] ?? $today,
            'supplier_id' => isset($_GET['supplier_id']) && $_GET['supplier_id'] !== '' ? (int)$_GET['supplier_id'] : null,
            'status' => isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : null,
            'period' => $_GET['period'] ?? 'custom'
        ];

        if ($filters['period'] !== 'custom') {
            // Same period logic
            switch ($filters['period']) {
                case 'today': $filters['start_date'] = $today; $filters['end_date'] = $today; break;
                case 'yesterday': $filters['start_date'] = date('Y-m-d', strtotime('-1 day')); $filters['end_date'] = date('Y-m-d', strtotime('-1 day')); break;
                case 'last7days': $filters['start_date'] = date('Y-m-d', strtotime('-6 days')); $filters['end_date'] = $today; break;
                case 'last30days': $filters['start_date'] = date('Y-m-d', strtotime('-29 days')); $filters['end_date'] = $today; break;
                case 'this_month': $filters['start_date'] = date('Y-m-01'); $filters['end_date'] = $today; break;
                case 'last_month': $filters['start_date'] = date('Y-m-01', strtotime('first day of last month')); $filters['end_date'] = date('Y-m-t', strtotime('last day of last month')); break;
            }
        }

        $purchaseOrders = $purchaseOrderModel->getPurchaseOrdersByDateRangeAndFilters($filters);

        $this->renderView('reports/purchases_report', [
            'purchaseOrders' => $purchaseOrders,
            'filters' => $filters,
            'suppliers' => $suppliers,
            'allowedStatuses' => $purchaseOrderModel->allowedStatuses, // from PurchaseOrder model
            'title' => 'Rapport des achats (basé sur les BC)'
        ]);
    }

    /**
     * Report: Detailed Stock Movements
     */
    public function stock_movements_report() {
        $productModel = $this->loadModel('Product');
        $stockMovementModel = $this->loadModel('StockMovement');

        $productsForFilter = $productModel->getAll(); // For product filter dropdown
        $allMovementTypes = $stockMovementModel->allowedTypes; // For type filter dropdown

        // Define default date range (e.g., this month)
        $today = date('Y-m-d');
        $defaultStartDate = date('Y-m-01');

        $filters = [
            'start_date' => trim($_GET['start_date'] ?? $defaultStartDate),
            'end_date' => trim($_GET['end_date'] ?? $today),
            'product_id' => isset($_GET['product_id']) && $_GET['product_id'] !== '' ? (int)$_GET['product_id'] : null,
            'movement_type' => trim($_GET['movement_type'] ?? ''),
            // 'related_document_type' => trim($_GET['related_document_type'] ?? '') // Example if adding more filters
        ];

        // Prepare filters for the model, removing empty string values so they are not applied
        $modelFilters = [];
        if (!empty($filters['start_date'])) $modelFilters['start_date'] = $filters['start_date'];
        if (!empty($filters['end_date'])) $modelFilters['end_date'] = $filters['end_date'];
        if ($filters['product_id'] !== null) $modelFilters['product_id'] = $filters['product_id'];
        if (!empty($filters['movement_type'])) $modelFilters['movement_type'] = $filters['movement_type'];
        // if (!empty($filters['related_document_type'])) $modelFilters['related_document_type'] = $filters['related_document_type'];


        $detailedMovements = $stockMovementModel->getDetailedStockMovements($modelFilters);

        $this->renderView('reports/stock_movements_report', [
            'movements' => $detailedMovements,
            'productsForFilter' => $productsForFilter,
            'allMovementTypes' => $allMovementTypes,
            'currentFilters' => $filters, // For repopulating filter form
            'title' => 'Rapport des Mouvements de Stock Détaillés'
        ]);
    }

    /**
     * Report: Daily Cash Flow Summary
     */
    public function daily_cash_flow() {
        $today = date('Y-m-d');
        $defaultStartDate = date('Y-m-01'); // First day of current month

        $filters = [
            'start_date' => trim($_GET['start_date'] ?? $defaultStartDate),
            'end_date' => trim($_GET['end_date'] ?? $today),
        ];

        // Validate dates (basic)
        // Add more robust validation if necessary (e.g., end_date >= start_date)
        if (empty($filters['start_date']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['start_date'])) {
            $filters['start_date'] = $defaultStartDate;
        }
        if (empty($filters['end_date']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['end_date'])) {
            $filters['end_date'] = $today;
        }

        // Initialize models - It's better to load models once if they are used multiple times
        $saleModel = $this->loadModel('Sale');
        // SalePaymentModel is not directly used here if SaleModel can provide necessary payment data
        // However, the plan was to query sale_payments directly.
        $salePaymentModel = $this->loadModel('SalePayment');


        // 1. Get total from immediate paid sales
        $sqlImmediateSales = "SELECT DATE(sale_date) as transaction_day, SUM(total_amount) as total_from_immediate_sales
                              FROM sales
                              WHERE payment_type = 'immediate' AND payment_status = 'paid'
                                AND sale_date BETWEEN :start_date AND :end_date
                              GROUP BY DATE(sale_date)";
        $immediateSalesData = $saleModel->db->select($sqlImmediateSales, [
            ':start_date' => $filters['start_date'],
            ':end_date' => $filters['end_date']
        ]);

        // 2. Get total from payments on deferred sales
        $sqlDeferredPayments = "SELECT DATE(sp.payment_date) as transaction_day, SUM(sp.amount_paid) as total_from_deferred_payments
                                FROM sale_payments sp
                                WHERE sp.payment_date BETWEEN :start_date AND :end_date
                                GROUP BY DATE(sp.payment_date)";
        $deferredPaymentsData = $salePaymentModel->db->select($sqlDeferredPayments, [
            ':start_date' => $filters['start_date'],
            ':end_date' => $filters['end_date']
        ]);

        // 3. Combine results
        $dailySummary = [];

        foreach ($immediateSalesData as $row) {
            $day = $row['transaction_day'];
            if (!isset($dailySummary[$day])) {
                $dailySummary[$day] = ['date' => $day, 'immediate_sales_total' => 0, 'deferred_payments_total' => 0, 'grand_total' => 0];
            }
            $dailySummary[$day]['immediate_sales_total'] += (float)$row['total_from_immediate_sales'];
            $dailySummary[$day]['grand_total'] += (float)$row['total_from_immediate_sales'];
        }

        foreach ($deferredPaymentsData as $row) {
            $day = $row['transaction_day'];
            if (!isset($dailySummary[$day])) {
                $dailySummary[$day] = ['date' => $day, 'immediate_sales_total' => 0, 'deferred_payments_total' => 0, 'grand_total' => 0];
            }
            $dailySummary[$day]['deferred_payments_total'] += (float)$row['total_from_deferred_payments'];
            $dailySummary[$day]['grand_total'] += (float)$row['total_from_deferred_payments'];
        }

        // Sort by date
        ksort($dailySummary); // Sorts by array keys (which are dates YYYY-MM-DD)

        $this->renderView('reports/daily_cash_flow_report', [
            'daily_cash_summary' => array_values($dailySummary), // Pass as indexed array for easier looping in view
            'currentFilters' => $filters,
            'title' => 'Rapport de Caisse Journalier'
        ]);
    }
}
?>
