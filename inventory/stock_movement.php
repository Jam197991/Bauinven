<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['inventory_staff_id'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

// Get item details
if (!isset($_GET['id'])) {
    header("Location: inventory_dashboard.php");
    exit();
}

$item_id = $_GET['id'];
$item_sql = "SELECT i.*, c.category_name, s.supplier_name 
             FROM inventory_items i 
             LEFT JOIN categories c ON i.category_id = c.category_id 
             LEFT JOIN suppliers s ON i.supplier_id = s.supplier_id 
             WHERE i.item_id = ?";
$item_stmt = $conn->prepare($item_sql);
$item_stmt->bind_param("i", $item_id);
$item_stmt->execute();
$item_result = $item_stmt->get_result();

if ($item_result->num_rows === 0) {
    header("Location: inventory_dashboard.php");
    exit();
}

$item = $item_result->fetch_assoc();

// Get suppliers for dropdown
$suppliers_sql = "SELECT * FROM suppliers ORDER BY supplier_name";
$suppliers_result = $conn->query($suppliers_sql);

// Get stock movement history
$movements_sql = "SELECT sm.*, s.supplier_name 
                 FROM stock_movements sm 
                 LEFT JOIN suppliers s ON sm.supplier_id = s.supplier_id 
                 WHERE sm.item_id = ? 
                 ORDER BY sm.movement_date DESC";
$movements_stmt = $conn->prepare($movements_sql);
$movements_stmt->bind_param("i", $item_id);
$movements_stmt->execute();
$movements_result = $movements_stmt->get_result();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $movement_type = $_POST['movement_type'];
    $quantity = intval($_POST['quantity']);
    $unit_price = floatval($_POST['unit_price']);
    $supplier_id = !empty($_POST['supplier_id']) ? $_POST['supplier_id'] : null;
    $notes = trim($_POST['notes']);
    
    // Validate input
    if ($quantity <= 0) {
        $error = "Quantity must be greater than 0";
    } elseif ($unit_price <= 0) {
        $error = "Unit price must be greater than 0";
    } else {
        $total_amount = $quantity * $unit_price;
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Insert stock movement
            $sql = "INSERT INTO stock_movements (item_id, movement_type, quantity, unit_price, total_amount, supplier_id, notes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isiddis", $item_id, $movement_type, $quantity, $unit_price, $total_amount, $supplier_id, $notes);
            $stmt->execute();
            
            // Update item quantity
            $new_quantity = $movement_type == 'in' ? $item['quantity'] + $quantity : $item['quantity'] - $quantity;
            if ($new_quantity < 0) {
                throw new Exception("Insufficient stock for stock out");
            }
            
            $update_sql = "UPDATE inventory_items SET quantity = ? WHERE item_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ii", $new_quantity, $item_id);
            $update_stmt->execute();
            
            $conn->commit();
            $success = "Stock movement recorded successfully";
            
            // Refresh item data
            $item_stmt->execute();
            $item = $item_stmt->get_result()->fetch_assoc();
            
            // Refresh movements
            $movements_stmt->execute();
            $movements_result = $movements_stmt->get_result();
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Movement - <?php echo htmlspecialchars($item['item_name']); ?> - BauApp</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
        }
        .item-details {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .item-details h2 {
            margin-top: 0;
            color: #333;
        }
        .item-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .info-group {
            margin-bottom: 10px;
        }
        .info-label {
            font-weight: bold;
            color: #666;
        }
        .form-container {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        .submit-btn {
            background: #4CAF50;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            transition: background 0.3s;
        }
        .submit-btn:hover {
            background: #45a049;
        }
        .error-message {
            color: #f44336;
            margin-bottom: 15px;
            padding: 10px;
            background: #ffebee;
            border-radius: 4px;
        }
        .success-message {
            color: #4CAF50;
            margin-bottom: 15px;
            padding: 10px;
            background: #e8f5e9;
            border-radius: 4px;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #2196F3;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .history-container {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .table-container {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        tr:hover {
            background-color: #f9f9f9;
        }
        .stock-in {
            color: #4CAF50;
        }
        .stock-out {
            color: #f44336;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="inventory_dashboard.php" class="back-link">← Back to Dashboard</a>
        
        <div class="item-details">
            <h2><?php echo htmlspecialchars($item['item_name']); ?></h2>
            <div class="item-info">
                <div class="info-group">
                    <div class="info-label">Category</div>
                    <div><?php echo htmlspecialchars($item['category_name']); ?></div>
                </div>
                <div class="info-group">
                    <div class="info-label">Current Stock</div>
                    <div><?php echo $item['quantity']; ?> units</div>
                </div>
                <div class="info-group">
                    <div class="info-label">Price</div>
                    <div>₱<?php echo number_format($item['price'], 2); ?></div>
                </div>
                <div class="info-group">
                    <div class="info-label">Supplier</div>
                    <div><?php echo htmlspecialchars($item['supplier_name'] ?? 'N/A'); ?></div>
                </div>
            </div>
        </div>
        
        <div class="form-container">
            <h3>Record Stock Movement</h3>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="movement_type">Movement Type</label>
                    <select id="movement_type" name="movement_type" required>
                        <option value="in">Stock In</option>
                        <option value="out">Stock Out</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="quantity">Quantity</label>
                    <input type="number" id="quantity" name="quantity" min="1" required>
                </div>
                
                <div class="form-group">
                    <label for="unit_price">Unit Price (₱)</label>
                    <input type="number" id="unit_price" name="unit_price" step="0.01" min="0.01" required>
                </div>
                
                <div class="form-group">
                    <label for="supplier_id">Supplier (Optional)</label>
                    <select id="supplier_id" name="supplier_id">
                        <option value="">Select Supplier</option>
                        <?php while($supplier = $suppliers_result->fetch_assoc()): ?>
                            <option value="<?php echo $supplier['supplier_id']; ?>">
                                <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes"></textarea>
                </div>
                
                <button type="submit" class="submit-btn">Record Movement</button>
            </form>
        </div>
        
        <div class="history-container">
            <h3>Movement History</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total Amount</th>
                            <th>Supplier</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($movement = $movements_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('M d, Y H:i', strtotime($movement['movement_date'])); ?></td>
                            <td class="<?php echo $movement['movement_type'] == 'in' ? 'stock-in' : 'stock-out'; ?>">
                                <?php echo ucfirst($movement['movement_type']); ?>
                            </td>
                            <td><?php echo $movement['quantity']; ?></td>
                            <td>₱<?php echo number_format($movement['unit_price'], 2); ?></td>
                            <td>₱<?php echo number_format($movement['total_amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($movement['supplier_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($movement['notes'] ?? ''); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Add animation when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            const containers = document.querySelectorAll('.item-details, .form-container, .history-container');
            containers.forEach((container, index) => {
                container.style.opacity = '0';
                container.style.transform = 'translateY(20px)';
                container.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                
                setTimeout(() => {
                    container.style.opacity = '1';
                    container.style.transform = 'translateY(0)';
                }, index * 200);
            });
        });
    </script>
</body>
</html> 