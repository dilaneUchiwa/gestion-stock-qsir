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
        $products = $productModel->getAll(); // This method should fetch all products with their quantity_in_stock

        // Potential filters (example: low stock)
        $filter_low_stock_threshold = isset($_GET['low_stock_threshold']) ? (int)$_GET['low_stock_threshold'] : null;
        if ($filter_low_stock_threshold !== null) {
            $products = array_filter($products, function($product) use ($filter_low_stock_threshold) {
                return $product['quantity_in_stock'] <= $filter_low_stock_threshold;
            });
        }

        $this->renderView('reports/current_stock_report', [
            'products' => $products,
            'low_stock_threshold' => $filter_low_stock_threshold,
            'title' => 'Rapport de stock actuel'
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
}
?>
