<?php
// $title is set by controller
// $title = 'Available Reports';
?>

<h2>Available Reports</h2>

<p>Select a report from the list below to view its details.</p>

<ul class="report-list">
    <li>
        <h4>Stock Reports</h4>
        <ul>
            <li><a href="index.php?url=report/current_stock">Current Stock Report</a> - View current stock levels for all products.</li>
            <li><a href="index.php?url=report/stock_entries">Stock Entries Report</a> - Track incoming stock movements (deliveries, adjustments in).</li>
            <li><a href="index.php?url=report/stock_exits">Stock Exits Report</a> - Track outgoing stock movements (sales, adjustments out).</li>
        </ul>
    </li>
    <li>
        <h4>Sales & Purchases Reports</h4>
        <ul>
            <li><a href="index.php?url=report/sales_report">Sales Report</a> - Analyze sales over periods, by client, or payment status.</li>
            <li><a href="index.php?url=report/purchases_report">Purchases Report</a> - Analyze purchase orders or deliveries over periods or by supplier.</li>
        </ul>
    </li>
    <!-- Add more report categories or individual reports here -->
</ul>

<style>
    .report-list {
        list-style-type: none;
        padding-left: 0;
    }
    .report-list h4 {
        margin-top: 20px;
        margin-bottom: 10px;
        border-bottom: 1px solid #eee;
        padding-bottom: 5px;
    }
    .report-list ul {
        list-style-type: disc;
        margin-left: 20px;
    }
    .report-list li a {
        text-decoration: none;
        color: #007bff;
    }
    .report-list li a:hover {
        text-decoration: underline;
    }
</style>
