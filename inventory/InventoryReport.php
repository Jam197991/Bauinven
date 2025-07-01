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

// Get low stock products (less than 50 items)
$low_stock_query = "SELECT p.*, c.category_name 
                   FROM products p 
                   LEFT JOIN categories c ON p.category_id = c.category_id 
                   WHERE p.quantity < 50 
                   ORDER BY p.quantity ASC 
                   LIMIT 100";
$low_stock_result = mysqli_query($conn, $low_stock_query);

// Stock category counts
$low_stock_count_query = "SELECT COUNT(*) as count FROM products WHERE quantity < 50 AND quantity > 1";
$low_stock_count = mysqli_fetch_assoc(mysqli_query($conn, $low_stock_count_query))['count'];

$normal_stock_count_query = "SELECT COUNT(*) as count FROM products WHERE quantity >= 50 AND quantity <= 200";
$normal_stock_count = mysqli_fetch_assoc(mysqli_query($conn, $normal_stock_count_query))['count'];

$high_stock_count_query = "SELECT COUNT(*) as count FROM products WHERE quantity > 200";
$high_stock_count = mysqli_fetch_assoc(mysqli_query($conn, $high_stock_count_query))['count'];

$no_stock_count_query = "SELECT COUNT(*) as count FROM products WHERE quantity = 0";
$no_stock_count = mysqli_fetch_assoc(mysqli_query($conn, $no_stock_count_query))['count'];

// New queries for normal and high stock products
$normal_stock_query = "SELECT p.*, c.category_name 
                   FROM products p 
                   LEFT JOIN categories c ON p.category_id = c.category_id 
                   WHERE p.quantity >= 50 AND p.quantity <= 200 
                   ORDER BY p.quantity ASC 
                   LIMIT 100";
$normal_stock_result = mysqli_query($conn, $normal_stock_query);

$high_stock_query = "SELECT p.*, c.category_name 
                   FROM products p 
                   LEFT JOIN categories c ON p.category_id = c.category_id 
                   WHERE p.quantity > 200 
                   ORDER BY p.quantity ASC 
                   LIMIT 100";
$high_stock_result = mysqli_query($conn, $high_stock_query);

// Add stock movement
if (isset($_POST['add_stock_movement'])) {
    $product_id = $_POST['product_id'];
    $movement_type = $_POST['movement_type'];
    $quantity = $_POST['quantity'];
    $unit_price = $_POST['unit_price'];
    $supplier_id = $_POST['supplier_id'];
    $notes = $_POST['notes'];
    $total_amount = $quantity * $unit_price;
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert stock movement
        $sql = "INSERT INTO stock_movements (product_id, movement_type, quantity, unit_price, total_amount, supplier_id, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isiddis", $product_id, $movement_type, $quantity, $unit_price, $total_amount, $supplier_id, $notes);
        $stmt->execute();
        
        // Check if product exists in products
        $check_sql = "SELECT quantity FROM products WHERE product_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $product_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // Product exists in products, update quantity
            $row = $check_result->fetch_assoc();
            $current_quantity = $row['quantity'];
            
            if ($movement_type == 'Stock-in') {
                $new_quantity = $current_quantity + $quantity;
            } elseif ($movement_type == 'Stock-out') {
                if ($current_quantity < $quantity) {
                    throw new Exception("Insufficient stock! Current stock: $current_quantity, trying to remove: $quantity");
                }
                $new_quantity = $current_quantity - $quantity;
            } else {
                // Should not happen with the current form, but good practice to handle
                $new_quantity = $current_quantity;
            }
            
            $update_sql = "UPDATE products SET quantity = ?, updated_at = NOW() WHERE product_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ii", $new_quantity, $product_id);
            $update_stmt->execute();
        } else {
            // Product doesn't exist in products, insert new record
            if ($movement_type == 'Stock-in') {
                $insert_sql = "INSERT INTO products (product_id, quantity, updated_at) VALUES (?, ?, NOW())";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("ii", $product_id, $quantity);
                $insert_stmt->execute();
            } else {
                // Stock-out for non-existent product is not allowed
                throw new Exception("Cannot perform Stock-out for product that doesn't exist in inventory!");
            }
        }
        
        // Commit transaction
        $conn->commit();
        echo "<script>localStorage.setItem('message', 'Stock movement added successfully!');</script>";
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo "<script>localStorage.setItem('error', 'Error adding stock movement: " . addslashes($e->getMessage()) . "');</script>";
    }
}

// Fetch all products for stock movement
$products_sql = "SELECT p.*, c.category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.category_id";
$products_result = $conn->query($products_sql);


// Fetch all categories
$categories_sql = "SELECT * FROM categories";
$categories_result = $conn->query($categories_sql);

// Fetch all stock movements with product and supplier details
$stock_movements_sql = "SELECT sm.*, p.product_name, s.supplier_name 
                       FROM stock_movements sm 
                       LEFT JOIN products p ON sm.product_id = p.product_id 
                       LEFT JOIN suppliers s ON sm.supplier_id = s.supplier_id 
                       ";

// Date filter for Stock Movements Reports
$filter_start = isset($_GET['filter_start']) ? $_GET['filter_start'] : '';
$filter_end = isset($_GET['filter_end']) ? $_GET['filter_end'] : '';
$date_filter = '';
if ($filter_start && $filter_end) {
    $date_filter = "WHERE DATE(sm.movement_date) BETWEEN '" . $conn->real_escape_string($filter_start) . "' AND '" . $conn->real_escape_string($filter_end) . "'";
} elseif ($filter_start) {
    $date_filter = "WHERE DATE(sm.movement_date) >= '" . $conn->real_escape_string($filter_start) . "'";
} elseif ($filter_end) {
    $date_filter = "WHERE DATE(sm.movement_date) <= '" . $conn->real_escape_string($filter_end) . "'";
}
if ($date_filter) {
    $stock_movements_sql .= $date_filter . " ";
}
$stock_movements_sql .= "ORDER BY sm.movement_date DESC";
$stock_movements_result = $conn->query($stock_movements_sql);

// Store products in an array for JavaScript
$products_array = array();
while($product = $products_result->fetch_assoc()) {
    $products_array[] = $product;
}
$products_json = json_encode($products_array);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Report</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <!-- Custom CSS -->
    <link href="../img/bau.jpg" rel="icon">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: #f5f6fa;
        }

        .main-content {
            margin-left: 250px;
            padding: 80px 20px 20px;
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

        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .card-title {
            font-size: 1.1rem;
            color: #2c3e50;
        }

        .card-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .bg-primary { background: #3498db; color: white; }
        .bg-success { background: #2ecc71; color: white; }
        .bg-warning { background: #f1c40f; color: white; }
        .bg-danger { background: #e74c3c; color: white; }

        /* Table Styles */
        .low-stock-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            margin-top: 20px;
        }

        .low-stock-table h2 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 1.2rem;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th, .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }

        .table tr:hover {
            background-color: #f8f9fa;
        }

        .stock-warning {
            color: #e74c3c;
            font-weight: 600;
        }
        .section-header {
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
        #stockMovementsTable {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(44, 62, 80, 0.08), 0 1.5px 4px rgba(52, 152, 219, 0.07);
            overflow: hidden;
        }
        #stockMovementsTable thead th {
            background: linear-gradient(90deg,rgb(15, 127, 22) 0%,rgb(101, 219, 142) 100%);
            color: #fff;
            font-size: 1.1rem;
            font-weight: 600;
            border: none;
            letter-spacing: 0.5px;
        }
        #stockMovementsTable tbody tr {
            transition: background 0.2s, box-shadow 0.2s;
        }
        #stockMovementsTable tbody tr:hover {
            background: #eaf6ff;
            box-shadow: 0 2px 8px rgba(52,152,219,0.08);
        }
        #stockMovementsTable td, #stockMovementsTable th {
            vertical-align: middle;
            border: none;
        }
        #stockMovementsTable td {
            font-size: 1rem;
            color: #34495e;
        }
        .action-buttons .btn-primary {
            background: linear-gradient(90deg,rgb(15, 127, 22) 0%,rgb(101, 219, 142) 100%);
            border: none;
            color: #fff;
            box-shadow: 0 2px 8px rgba(52,152,219,0.08);
            transition: background 0.2s, box-shadow 0.2s;
        }
        .action-buttons .btn-primary:hover {
            background: linear-gradient(90deg,rgb(15, 127, 22) 0%,rgb(101, 219, 142) 100%);
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
        #lowStockTable {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(44, 62, 80, 0.08), 0 1.5px 4px rgba(52, 152, 219, 0.07);
            overflow: hidden;
        }
        #lowStockTable thead th {
            background: linear-gradient(90deg,rgb(15, 127, 22) 0%,rgb(101, 219, 142) 100%);
            color: #fff;
            font-size: 1.1rem;
            font-weight: 600;
            border: none;
            letter-spacing: 0.5px;
        }
        #lowStockTable tbody tr {
            transition: background 0.2s, box-shadow 0.2s;
        }
        #lowStockTable tbody tr:hover {
            background: #eaf6ff;
            box-shadow: 0 2px 8px rgba(52,152,219,0.08);
        }
        #lowStockTable td, #lowStockTable th {
            vertical-align: middle;
            border: none;
        }
        #lowStockTable td {
            font-size: 1rem;
            color: #34495e;
        }
        .action-buttons .btn-primary {
            background: linear-gradient(90deg,rgb(15, 127, 22) 0%,rgb(101, 219, 142) 100%);
            border: none;
            color: #fff;
            box-shadow: 0 2px 8px rgba(52,152,219,0.08);
            transition: background 0.2s, box-shadow 0.2s;
        }
        .action-buttons .btn-primary:hover {
            background: linear-gradient(90deg,rgb(15, 127, 22) 0%,rgb(101, 219, 142) 100%);
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
        #normalStockTable {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(44, 62, 80, 0.08), 0 1.5px 4px rgba(52, 152, 219, 0.07);
            overflow: hidden;
        }
        #normalStockTable thead th {
            background: linear-gradient(90deg,rgb(15, 127, 22) 0%,rgb(101, 219, 142) 100%);
            color: #fff;
            font-size: 1.1rem;
            font-weight: 600;
            border: none;
            letter-spacing: 0.5px;
        }
        #normalStockTable tbody tr {
            transition: background 0.2s, box-shadow 0.2s;
        }
        #normalStockTable tbody tr:hover {
            background: #eaf6ff;
            box-shadow: 0 2px 8px rgba(52,152,219,0.08);
        }
        #normalStockTable td, #normalStockTable th {
            vertical-align: middle;
            border: none;
        }
        #normalStockTable td {
            font-size: 1rem;
            color: #34495e;
        }
        .action-buttons .btn-primary {
            background: linear-gradient(90deg,rgb(15, 127, 22) 0%,rgb(101, 219, 142) 100%);
            border: none;
            color: #fff;
            box-shadow: 0 2px 8px rgba(52,152,219,0.08);
            transition: background 0.2s, box-shadow 0.2s;
        }
        .action-buttons .btn-primary:hover {
            background: linear-gradient(90deg,rgb(15, 127, 22) 0%,rgb(101, 219, 142) 100%);
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
        #highStockTable {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(44, 62, 80, 0.08), 0 1.5px 4px rgba(52, 152, 219, 0.07);
            overflow: hidden;
        }
        #highStockTable thead th {
            background: linear-gradient(90deg,rgb(15, 127, 22) 0%,rgb(101, 219, 142) 100%);
            color: #fff;
            font-size: 1.1rem;
            font-weight: 600;
            border: none;
            letter-spacing: 0.5px;
        }
        #highStockTable tbody tr {
            transition: background 0.2s, box-shadow 0.2s;
        }
        #highStockTable tbody tr:hover {
            background: #eaf6ff;
            box-shadow: 0 2px 8px rgba(52,152,219,0.08);
        }
        #highStockTable td, #highStockTable th {
            vertical-align: middle;
            border: none;
        }
        #highStockTable td {
            font-size: 1rem;
            color: #34495e;
        }
        .action-buttons .btn-primary {
            background: linear-gradient(90deg,rgb(15, 127, 22) 0%,rgb(101, 219, 142) 100%);
            border: none;
            color: #fff;
            box-shadow: 0 2px 8px rgba(52,152,219,0.08);
            transition: background 0.2s, box-shadow 0.2s;
        }
        .action-buttons .btn-primary:hover {
            background: linear-gradient(90deg,rgb(15, 127, 22) 0%,rgb(101, 219, 142) 100%);
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

        
    <div class="main-content">
        <div class="row mb-4">
            <div class="col">
                <div class="section-header">Stock Movements Reports</div>
            </div>
            <div class="col text-end">
                <a href="export_stock_movements_csv.php?filter_start=<?php echo urlencode($filter_start); ?>&filter_end=<?php echo urlencode($filter_end); ?>" class="btn btn-success">Export to CSV</a>
            </div>
        </div>
        <!-- Date Filter Form -->
        <form method="get" class="row g-3 mb-3">
            <div class="col-auto">
                <label for="filter_start" class="col-form-label">Start Date:</label>
            </div>
            <div class="col-auto">
                <input type="date" class="form-control" id="filter_start" name="filter_start" value="<?php echo htmlspecialchars($filter_start); ?>">
            </div>
            <div class="col-auto">
                <label for="filter_end" class="col-form-label">End Date:</label>
            </div>
            <div class="col-auto">
                <input type="date" class="form-control" id="filter_end" name="filter_end" value="<?php echo htmlspecialchars($filter_end); ?>">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary custom-filter-btn">Filter</button>
            </div>
        </form>
        <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="stockMovementsTable">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Movement Type</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Total Amount</th>
                                    <th>Supplier</th>
                                    <th>Movement Date</th>
                                    <th>Notes</th>                                   
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($movement = $stock_movements_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $movement['product_name']; ?></td>
                                    <td>
                                        <span class="badge <?php echo ($movement['movement_type'] == 'Stock-in') ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo $movement['movement_type']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $movement['quantity']; ?></td>
                                    <td>₱<?php echo number_format($movement['unit_price'], 2); ?></td>
                                    <td>₱<?php echo number_format($movement['total_amount'], 2); ?></td>
                                    <td><?php echo $movement['supplier_name']; ?></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($movement['movement_date'])); ?></td>
                                    <td><?php echo $movement['notes']; ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
    </div> 

    <div class="main-content">
        <div class="row mb-4">
            <div class="col">
                <div class="section-header">Inventory Report</div>
            </div>
            <div class="col text-end">
                <a href="export_inventory_report_csv.php" class="btn btn-success">Export to CSV</a>
            </div>
        </div>

        
        <div class="low-stock-table">
            <h2>Low Stock Products</h2>
            <table class="table" id="lowStockTable">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Current Stock</th>
                        <th>Last Updated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($product = mysqli_fetch_assoc($low_stock_result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                        <td>₱<?php echo number_format($product['price'], 2); ?></td>
                        <td class="stock-warning"><?php echo $product['quantity']; ?> units</td>
                        <td><?php echo date('M d, Y', strtotime($product['updated_at'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Normal Stock Products Table -->
        <div class="low-stock-table">
            <h2>Normal Stock Products</h2>
            <table class="table" id="normalStockTable">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Current Stock</th>
                        <th>Last Updated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($product = mysqli_fetch_assoc($normal_stock_result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                        <td>₱<?php echo number_format($product['price'], 2); ?></td>
                        <td><?php echo $product['quantity']; ?> units</td>
                        <td><?php echo date('M d, Y', strtotime($product['updated_at'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- High Stock Products Table -->
        <div class="low-stock-table">
            <h2>High Stock Products</h2>
            <table class="table" id="highStockTable">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Current Stock</th>
                        <th>Last Updated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($product = mysqli_fetch_assoc($high_stock_result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                        <td>₱<?php echo number_format($product['price'], 2); ?></td>
                        <td><?php echo $product['quantity']; ?> units</td>
                        <td><?php echo date('M d, Y', strtotime($product['updated_at'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>   

    </div>  

     
</body>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function() {
    $('#lowStockTable').DataTable({
        "pageLength": 5,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
        "language": {
            "search": "Search:"
        }
    });
    $('#normalStockTable').DataTable({
        "pageLength": 5,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
        "language": {
            "search": "Search:"
        }
    });
    $('#highStockTable').DataTable({
        "pageLength": 5,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
        "language": {
            "search": "Search:"
        }
    });

    // Initialize Stock Movements DataTable
    $('#stockMovementsTable').DataTable({
                "lengthMenu": [[10, 20, 30, -1], [10, 20, 30, "All"]],
                "pageLength": 10,
                "order": [[6, "desc"]], // Sort by movement date descending
                "language": {
                    "search": "Search movements:",
                    "lengthMenu": "Show _MENU_ entries per page",
                    "info": "Showing _START_ to _END_ of _TOTAL_ movements",
                    "infoEmpty": "Showing 0 to 0 of 0 movements",
                    "infoFiltered": "(filtered from _MAX_ total movements)"
                }
            });
});
</script>
</html>