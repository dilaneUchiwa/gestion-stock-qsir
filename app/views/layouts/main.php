<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'ERPNextClone'; ?></title>
    <link rel="stylesheet" href="public/css/style.css"> <!-- Link to the new stylesheet -->
    <!-- Any other head elements like favicons, etc. -->
</head>
<body>
    <header>
        <h1><a>MALIKA</a></h1> <!-- Make header title a link to a potential dashboard -->
        <nav>
            <ul>
                <!-- The base URL needs to be defined or dynamically generated. Assuming index.php is the entry point. -->
                <li><a href="index.php?url=products">Products</a></li>
                <li><a href="index.php?url=suppliers">Suppliers</a></li>
                <li><a href="index.php?url=clients">Clients</a></li>
                <li class="dropdown">
                    <a href="javascript:void(0)" class="dropbtn">Procurement</a>
                    <div class="dropdown-content">
                        <a href="index.php?url=purchaseorder/index">Purchase Orders</a>
                        <a href="index.php?url=delivery/index">Deliveries</a>
                        <a href="index.php?url=supplierinvoice/index">Supplier Invoices</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="javascript:void(0)" class="dropbtn">Sales</a>
                    <div class="dropdown-content">
                        <a href="index.php?url=sale/create_immediate_payment">New Sale (Immediate)</a>
                        <a href="index.php?url=sale/create_deferred_payment">New Sale (Deferred)</a>
                        <a href="index.php?url=sale/index">Sales History</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="javascript:void(0)" class="dropbtn">Stock</a>
                    <div class="dropdown-content">
                        <a href="index.php?url=stock/index">Stock Overview</a>
                        <a href="index.php?url=stock/create_adjustment">New Adjustment</a>
                    </div>
                </li>
                 <li class="dropdown">
                    <a href="javascript:void(0)" class="dropbtn">Reports</a>
                    <div class="dropdown-content">
                        <a href="index.php?url=report/index">Reports Home</a>
                        <hr style="margin: 2px 0; border-color: #555;">
                        <a href="index.php?url=report/current_stock">Current Stock</a>
                        <a href="index.php?url=report/stock_entries">Stock Entries</a>
                        <a href="index.php?url=report/stock_exits">Stock Exits</a>
                        <a href="index.php?url=report/sales_report">Sales Report</a>
                        <a href="index.php?url=report/purchases_report">Purchases Report</a>
                    </div>
                </li>
                <!-- Add more global navigation links here -->
            </ul>
        </nav>
    </header>
    <!-- Inline styles for dropdown were moved to style.css -->

    <main class="container"> <!-- Use main for the primary content area -->
        <div class="content">
            <?php echo $content; // This is where the view content will be injected ?>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> ERPNextClone. All rights reserved.</p>
    </footer>
</body>
</html>
