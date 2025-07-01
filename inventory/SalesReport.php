<?php
session_start();
include '../includes/database.php';

if (!isset($_SESSION['staff_id'])) {
    echo "<script>
            alert('Please log in first');
            window.location.href = '../index.php';
        </script>";
    exit();
}

?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link href="../img/bau.jpg" rel="icon">
    <style>
        .main-content {
            margin-left: 250px; /* or padding-left: 250px; */
            transition: all 0.3s ease;
        }
        .sidebar.collapsed ~ .main-content {
            margin-left: 70px;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 70px;
            }

            .sidebar.expanded ~ .main-content {
                margin-left: 250px;
            }
        }
        .sales-report-heading {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;         
            margin-bottom: 24px;
            letter-spacing: 1px;
            border-left: 6px solid #3498db;
            padding-left: 16px;
            background: #f4f8fb;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(52,152,219,0.07);
        }
        /* Cool Table Styles */
        #completedOrdersTable {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(44, 62, 80, 0.08), 0 1.5px 4px rgba(52, 152, 219, 0.07);
            overflow: hidden;
        }
        #completedOrdersTable thead th {
            background: linear-gradient(90deg,rgb(15, 127, 22) 0%,rgb(101, 219, 142) 100%);
            color: #fff;
            font-size: 1.1rem;
            font-weight: 600;
            border: none;
            letter-spacing: 0.5px;
        }
        #completedOrdersTable tbody tr {
            transition: background 0.2s, box-shadow 0.2s;
        }
        #completedOrdersTable tbody tr:hover {
            background: #eaf6ff;
            box-shadow: 0 2px 8px rgba(52,152,219,0.08);
        }
        #completedOrdersTable td, #completedOrdersTable th {
            vertical-align: middle;
            border: none;
        }
        #completedOrdersTable td {
            font-size: 1rem;
            color: #34495e;
        }
        .action-buttons .btn-primary {
            background: linear-gradient(90deg, #6dd5fa 0%, #3498db 100%);
            border: none;
            color: #fff;
            box-shadow: 0 2px 8px rgba(52,152,219,0.08);
            transition: background 0.2s, box-shadow 0.2s;
        }
        .action-buttons .btn-primary:hover {
            background: linear-gradient(90deg, #3498db 0%, #6dd5fa 100%);
            color: #fff;
            box-shadow: 0 4px 16px rgba(52,152,219,0.12);
        }
        .action-buttons .btn-danger {
            background: linear-gradient(90deg, #ff5858 0%, #f09819 100%);
            border: none;
            color: #fff;
            box-shadow: 0 2px 8px rgba(241, 196, 15, 0.08);
            transition: background 0.2s, box-shadow 0.2s;
        }
        .action-buttons .btn-danger:hover {
            background: linear-gradient(90deg, #f09819 0%, #ff5858 100%);
            color: #fff;
            box-shadow: 0 4px 16px rgba(241, 196, 15, 0.12);
        }
        
        #completedOrdersTable tbody tr td {
            padding: 0.75rem 1rem;
        }
        #completedOrdersTable thead th {
            padding: 1rem 1rem;
        }
        
        #cancelledOrdersTable {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(44, 62, 80, 0.08), 0 1.5px 4px rgba(52, 152, 219, 0.07);
            overflow: hidden;
        }
        #cancelledOrdersTable thead th {
            background: linear-gradient(90deg,rgb(15, 127, 22) 0%,rgb(101, 219, 142) 100%);
            color: #fff;
            font-size: 1.1rem;
            font-weight: 600;
            border: none;
            letter-spacing: 0.5px;
        }
        #cancelledOrdersTable tbody tr {
            transition: background 0.2s, box-shadow 0.2s;
        }
        #cancelledOrdersTable tbody tr:hover {
            background: #eaf6ff;
            box-shadow: 0 2px 8px rgba(52,152,219,0.08);
        }
        #cancelledOrdersTable td, #cancelledOrdersTable th {
            vertical-align: middle;
            border: none;
        }
        #cancelledOrdersTable td {
            font-size: 1rem;
            color: #34495e;
        }
        .action-buttons .btn-primary {
            background: linear-gradient(90deg, #6dd5fa 0%, #3498db 100%);
            border: none;
            color: #fff;
            box-shadow: 0 2px 8px rgba(52,152,219,0.08);
            transition: background 0.2s, box-shadow 0.2s;
        }
        .action-buttons .btn-primary:hover {
            background: linear-gradient(90deg, #3498db 0%, #6dd5fa 100%);
            color: #fff;
            box-shadow: 0 4px 16px rgba(52,152,219,0.12);
        }
        .action-buttons .btn-danger {
            background: linear-gradient(90deg, #ff5858 0%, #f09819 100%);
            border: none;
            color: #fff;
            box-shadow: 0 2px 8px rgba(241, 196, 15, 0.08);
            transition: background 0.2s, box-shadow 0.2s;
        }
        .action-buttons .btn-danger:hover {
            background: linear-gradient(90deg, #f09819 0%, #ff5858 100%);
            color: #fff;
            box-shadow: 0 4px 16px rgba(241, 196, 15, 0.12);
        }
        
        #cancelledOrdersTable tbody tr td {
            padding: 0.75rem 1rem;
        }
        #cancelledOrdersTable thead th {
            padding: 1rem 1rem;
        }
        
        .custom-date-input {
            border: 1px solid #3498db;
            border-radius: 6px;
            padding: 8px 14px;
            font-size: 1rem;
            color: #34495e;
            background: #f4f8fb;
            transition: border 0.2s, box-shadow 0.2s;
            margin-right: 10px;
        }
        .custom-date-input:focus {
            border: 1.5px solid #2feb54;
            outline: none;
            box-shadow: 0 0 6px #2feb5433;
        }
        .custom-filter-btn {
            background: linear-gradient(90deg, #2feb54 0%, #3498db 100%);
            border: none;
            color: #fff;
            font-weight: 600;
            border-radius: 6px;
            padding: 8px 22px;
            font-size: 1rem;
            box-shadow: 0 2px 8px rgba(52,152,219,0.08);
            transition: background 0.2s, box-shadow 0.2s;
        }
        .custom-filter-btn:hover {
            background: linear-gradient(90deg, #3498db 0%, #2feb54 100%);
            color: #fff;
            box-shadow: 0 4px 16px rgba(52,152,219,0.12);
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/topbar.php'; ?>
    <div style="height: 80px;"></div>

    <div class="main-content">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col">
                    <div class="sales-report-heading">Sales Report</div>
                </div>
            </div>
            
        <form method="GET" class="form-inline" style="margin-bottom: 20px;">
            <label for="start_date">Start Date:</label>
            <input type="date" name="start_date" id="start_date" class="form-control custom-date-input" value="<?php echo isset($_GET['start_date']) ? htmlspecialchars($_GET['start_date']) : ''; ?>">
            <label for="end_date">End Date:</label>
            <input type="date" name="end_date" id="end_date" class="form-control custom-date-input" value="<?php echo isset($_GET['end_date']) ? htmlspecialchars($_GET['end_date']) : ''; ?>">
            <button type="submit" class="btn btn-primary custom-filter-btn">Filter</button>
        </form>
        

        <div class="card">
                <div class="card-body">
                <div class="row mb-4">
                <div class="col">
                    <div class="sales-report-heading">Completed Orders</div>
                </div>
            </div>
                <button class="btn btn-success mb-3" onclick="exportTableToExcel('completedOrdersTable', 'Completed_Orders')">Export Completed Orders to Excel</button>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="completedOrdersTable">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Order Date</th>
                                    <th>Product Name</th>
                                    <th>Discount Type</th>
                                    <th>Total Price</th>
                                    <th>Total Discounted Price</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            // Build filter conditions
                            $conditions = [];
                            if (!empty($_GET['year'])) {
                                $year = intval($_GET['year']);
                                $conditions[] = "YEAR(o.order_date) = $year";
                            }
                            if (!empty($_GET['month'])) {
                                $month = intval($_GET['month']);
                                $conditions[] = "MONTH(o.order_date) = $month";
                            }
                            if (!empty($_GET['date'])) {
                                $date = $conn->real_escape_string($_GET['date']);
                                $conditions[] = "DATE(o.order_date) = '$date'";
                            }
                            if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
                                $start_date = $conn->real_escape_string($_GET['start_date']);
                                $end_date = $conn->real_escape_string($_GET['end_date']);
                                $conditions[] = "DATE(o.order_date) BETWEEN '$start_date' AND '$end_date'";
                            } elseif (!empty($_GET['start_date'])) {
                                $start_date = $conn->real_escape_string($_GET['start_date']);
                                $conditions[] = "DATE(o.order_date) >= '$start_date'";
                            } elseif (!empty($_GET['end_date'])) {
                                $end_date = $conn->real_escape_string($_GET['end_date']);
                                $conditions[] = "DATE(o.order_date) <= '$end_date'";
                            }
                            // Always filter for completed status
                            $conditions[] = "o.status = 'completed'";
                            $where = count($conditions) ? ('WHERE ' . implode(' AND ', $conditions)) : '';

                            $sql = "SELECT o.order_id, o.order_date, p.product_name, o.discount_type, o.status,
                                    SUM(oi.price) AS total_price, 
                                    SUM(oi.discounted_price) AS total_discounted_price
                                    FROM orders o
                                    JOIN order_items oi ON o.order_id = oi.order_id
                                    JOIN products p ON oi.product_id = p.product_id
                                    $where
                                    GROUP BY o.order_id, o.order_date, p.product_name, o.discount_type, o.status
                                    ORDER BY o.order_date DESC";
                            $result = $conn->query($sql);

                            $grandTotal = 0;
                            $grandDiscounted = 0;
                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['order_id']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['order_date']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['discount_type']) . "</td>";
                                    echo "<td>₱" . number_format($row['total_price'], 2) . "</td>";
                                    echo "<td>₱" . number_format($row['total_discounted_price'], 2) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['status']) . "</td>";
                                    echo "</tr>";
                                    $grandTotal += $row['total_price'];
                                    $grandDiscounted += $row['total_discounted_price'];
                                }
                            } else {
                                echo '<tr><td colspan="7">No sales found for the selected filter.</td></tr>';
                            }
                            ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="4">Grand Total</th>
                                    <th>₱<?php echo number_format($grandTotal, 2); ?></th>
                                    <th>₱<?php echo number_format($grandDiscounted, 2); ?></th>
                                    <th>₱<?php echo number_format($grandTotal - $grandDiscounted, 2); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cancelled Orders Table -->
        <div class="card mt-5">
            <div class="card-body">
                <div class="row mb-4">
                <div class="col">
                    <div class="sales-report-heading">Cancelled Orders</div>
                </div>
            </div>
                <button class="btn btn-danger mb-3" onclick="exportTableToExcel('cancelledOrdersTable', 'Cancelled_Orders')">Export Cancelled Orders to Excel</button>
                <div class="table-responsive">
                    <table class="table table-bordered" id="cancelledOrdersTable">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Order Date</th>
                                <th>Product Name</th>
                                <th>Discount Type</th>
                                <th>Total Price</th>
                                <th>Total Discounted Price</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        // Build filter conditions for cancelled orders
                        $cancel_conditions = [];
                        if (!empty($_GET['year'])) {
                            $year = intval($_GET['year']);
                            $cancel_conditions[] = "YEAR(o.order_date) = $year";
                        }
                        if (!empty($_GET['month'])) {
                            $month = intval($_GET['month']);
                            $cancel_conditions[] = "MONTH(o.order_date) = $month";
                        }
                        if (!empty($_GET['date'])) {
                            $date = $conn->real_escape_string($_GET['date']);
                            $cancel_conditions[] = "DATE(o.order_date) = '$date'";
                        }
                        if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
                            $start_date = $conn->real_escape_string($_GET['start_date']);
                            $end_date = $conn->real_escape_string($_GET['end_date']);
                            $cancel_conditions[] = "DATE(o.order_date) BETWEEN '$start_date' AND '$end_date'";
                        } elseif (!empty($_GET['start_date'])) {
                            $start_date = $conn->real_escape_string($_GET['start_date']);
                            $cancel_conditions[] = "DATE(o.order_date) >= '$start_date'";
                        } elseif (!empty($_GET['end_date'])) {
                            $end_date = $conn->real_escape_string($_GET['end_date']);
                            $cancel_conditions[] = "DATE(o.order_date) <= '$end_date'";
                        }
                        $cancel_conditions[] = "o.status = 'cancelled'";
                        $cancel_where = count($cancel_conditions) ? ('WHERE ' . implode(' AND ', $cancel_conditions)) : '';

                        $cancel_sql = "SELECT o.order_id, o.order_date, p.product_name, o.discount_type, o.status,
                                SUM(oi.price) AS total_price, 
                                SUM(oi.discounted_price) AS total_discounted_price
                                FROM orders o
                                JOIN order_items oi ON o.order_id = oi.order_id
                                JOIN products p ON oi.product_id = p.product_id
                                $cancel_where
                                GROUP BY o.order_id, o.order_date, p.product_name, o.discount_type, o.status
                                ORDER BY o.order_date DESC";
                        $cancel_result = $conn->query($cancel_sql);

                        $cancelGrandTotal = 0;
                        $cancelGrandDiscounted = 0;
                        if ($cancel_result && $cancel_result->num_rows > 0) {
                            while ($row = $cancel_result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['order_id']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['order_date']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['discount_type']) . "</td>";
                                echo "<td>₱" . number_format($row['total_price'], 2) . "</td>";
                                echo "<td>₱" . number_format($row['total_discounted_price'], 2) . "</td>";
                                echo "<td>" . htmlspecialchars($row['status']) . "</td>";
                                echo "</tr>";
                                $cancelGrandTotal += $row['total_price'];
                                $cancelGrandDiscounted += $row['total_discounted_price'];
                            }
                        } else {
                            echo '<tr><td colspan="7">No cancelled orders found for the selected filter.</td></tr>';
                        }
                        ?>
                        </tbody>
                        <tfoot>
                            
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

</body>
</html>

<script>
function exportTableToExcel(tableID, filename = '') {
    var downloadLink;
    var dataType = 'application/vnd.ms-excel';
    var tableSelect = document.getElementById(tableID);
    var tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');
    filename = filename ? filename + '.xls' : 'excel_data.xls';
    downloadLink = document.createElement('a');
    document.body.appendChild(downloadLink);
    if (navigator.msSaveOrOpenBlob) {
        var blob = new Blob(['\ufeff', tableHTML], { type: dataType });
        navigator.msSaveOrOpenBlob(blob, filename);
    } else {
        downloadLink.href = 'data:' + dataType + ', ' + tableHTML;
        downloadLink.download = filename;
        downloadLink.click();
    }
    document.body.removeChild(downloadLink);
}
</script>