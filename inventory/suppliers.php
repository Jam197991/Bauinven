<?php
session_start();
include '../includes/database.php';
include '../includes/audit.php';

if (!isset($_SESSION['staff_id'])) {
    echo "<script>
            alert('Please log in first');
            window.location.href = '../index.php';
        </script>";
    exit();
}

// Handle supplier operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Add new supplier
    if (isset($_POST['add_supplier'])) {
        $supplier_name = $_POST['supplier_name'];
        $contact_number = $_POST['contact_number'];
        $email = $_POST['email'];
        $address = $_POST['address'];
        
        $sql = "INSERT INTO suppliers (supplier_name, contact_number, email, address) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $supplier_name, $contact_number, $email, $address);
        
        if($stmt->execute()) {
            echo "<script>localStorage.setItem('message', 'Supplier added successfully!');</script>";
        } else {
            echo "<script>localStorage.setItem('error', 'Error adding supplier!');</script>";
        }
    }
    
    // Update supplier
    if (isset($_POST['update_supplier'])) {
        $supplier_id = $_POST['supplier_id'];
        $supplier_name = $_POST['supplier_name'];
        $contact_number = $_POST['contact_number'];
        $email = $_POST['email'];
        $address = $_POST['address'];
        
        $sql = "UPDATE suppliers SET supplier_name = ?, contact_number = ?, email = ?, address = ? WHERE supplier_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $supplier_name, $contact_number, $email, $address, $supplier_id);
        
        if($stmt->execute()) {
            echo "<script>localStorage.setItem('message', 'Supplier updated successfully!');</script>";
        } else {
            echo "<script>localStorage.setItem('error', 'Error updating supplier!');</script>";
        }
    }
    
    // Delete supplier
    if (isset($_POST['delete_supplier'])) {
        $supplier_id = $_POST['supplier_id'];
        
        $sql = "DELETE FROM suppliers WHERE supplier_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $supplier_id);
        
        if($stmt->execute()) {
            echo "<script>localStorage.setItem('message', 'Supplier deleted successfully!');</script>";
        } else {
            echo "<script>localStorage.setItem('error', 'Error deleting supplier!');</script>";
        }
    }
    
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

    // Update stock movement
    if (isset($_POST['update_stock_movement'])) {
        $movement_id = $_POST['movement_id'];
        $product_id = $_POST['product_id'];
        $new_movement_type = $_POST['movement_type'];
        $new_quantity = (int)$_POST['quantity'];
        $new_unit_price = $_POST['unit_price'];
        $new_supplier_id = $_POST['supplier_id'];
        $new_notes = $_POST['notes'];
        $new_total_amount = $new_quantity * $new_unit_price;

        $conn->begin_transaction();
        try {
            // Get original movement to calculate inventory change
            $get_sql = "SELECT product_id, movement_type, quantity FROM stock_movements WHERE movement_id = ?";
            $get_stmt = $conn->prepare($get_sql);
            $get_stmt->bind_param("i", $movement_id);
            $get_stmt->execute();
            $original_movement = $get_stmt->get_result()->fetch_assoc();

            if (!$original_movement) {
                throw new Exception("Stock movement not found!");
            }

            $original_quantity = (int)$original_movement['quantity'];
            $original_movement_type = $original_movement['movement_type'];

            // Get current inventory
            $inv_sql = "SELECT quantity FROM products WHERE product_id = ?";
            $inv_stmt = $conn->prepare($inv_sql);
            $inv_stmt->bind_param("i", $product_id);
            $inv_stmt->execute();
            $current_inventory = (int)$inv_stmt->get_result()->fetch_assoc()['quantity'];

            // Calculate inventory change
            $inventory_change = 0;
            if ($original_movement_type == 'Stock-in') {
                $inventory_change -= $original_quantity;
            } else {
                $inventory_change += $original_quantity;
            }

            if ($new_movement_type == 'Stock-in') {
                $inventory_change += $new_quantity;
            } else {
                $inventory_change -= $new_quantity;
            }

            $final_inventory = $current_inventory + $inventory_change;

            if ($final_inventory < 0) {
                throw new Exception("Update would result in negative inventory. Operation cancelled.");
            }

            // Update inventory
            $update_inv_sql = "UPDATE products SET quantity = ? WHERE product_id = ?";
            $update_inv_stmt = $conn->prepare($update_inv_sql);
            $update_inv_stmt->bind_param("ii", $final_inventory, $product_id);
            $update_inv_stmt->execute();

            // Update stock movement
            $update_move_sql = "UPDATE stock_movements SET movement_type = ?, quantity = ?, unit_price = ?, total_amount = ?, supplier_id = ?, notes = ? WHERE movement_id = ?";
            $update_move_stmt = $conn->prepare($update_move_sql);
            $update_move_stmt->bind_param("siddisi", $new_movement_type, $new_quantity, $new_unit_price, $new_total_amount, $new_supplier_id, $new_notes, $movement_id);
            $update_move_stmt->execute();

            $conn->commit();
            echo "<script>localStorage.setItem('message', 'Stock movement updated successfully!'); window.location.href = window.location.pathname;</script>";
        } catch (Exception $e) {
            $conn->rollback();
            echo "<script>localStorage.setItem('error', 'Error updating stock movement: " . addslashes($e->getMessage()) . "');</script>";
        }
    }

    // Delete stock movement
    if (isset($_POST['delete_stock_movement'])) {
        $movement_id = $_POST['movement_id'];

        $conn->begin_transaction();
        try {
            // Get movement details before deleting to reverse inventory change
            $get_sql = "SELECT product_id, movement_type, quantity FROM stock_movements WHERE movement_id = ?";
            $get_stmt = $conn->prepare($get_sql);
            $get_stmt->bind_param("i", $movement_id);
            $get_stmt->execute();
            $movement_to_delete = $get_stmt->get_result()->fetch_assoc();

            if (!$movement_to_delete) {
                throw new Exception("Stock movement not found!");
            }

            $product_id = $movement_to_delete['product_id'];
            $quantity_to_reverse = $movement_to_delete['quantity'];
            $movement_type = $movement_to_delete['movement_type'];

            // Delete the movement
            $delete_sql = "DELETE FROM stock_movements WHERE movement_id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $movement_id);
            $delete_stmt->execute();

            // Get current inventory
            $inv_sql = "SELECT quantity FROM products WHERE product_id = ?";
            $inv_stmt = $conn->prepare($inv_sql);
            $inv_stmt->bind_param("i", $product_id);
            $inv_stmt->execute();
            $current_inventory = (int)$inv_stmt->get_result()->fetch_assoc()['quantity'];
            
            $new_inventory = $current_inventory;
            if ($movement_type == 'Stock-in') {
                $new_inventory -= $quantity_to_reverse;
            } else { // Stock-out
                $new_inventory += $quantity_to_reverse;
            }

            if ($new_inventory < 0) {
                throw new Exception("Reversal would result in negative inventory. Please check product stock.");
            }

            // Update inventory
            $update_inv_sql = "UPDATE products SET quantity = ? WHERE product_id = ?";
            $update_inv_stmt = $conn->prepare($update_inv_sql);
            $update_inv_stmt->bind_param("ii", $new_inventory, $product_id);
            $update_inv_stmt->execute();

            $conn->commit();
            echo "<script>localStorage.setItem('message', 'Stock movement deleted successfully!'); window.location.href = window.location.pathname;</script>";
        } catch (Exception $e) {
            $conn->rollback();
            echo "<script>localStorage.setItem('error', 'Error deleting stock movement: " . addslashes($e->getMessage()) . "');</script>";
        }
    }
}

// Fetch all suppliers
$sql = "SELECT * FROM suppliers ORDER BY created_at DESC";
$result = $conn->query($sql);

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
                       ORDER BY sm.movement_date DESC";
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
    <title>Supplier Management</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Toastr CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
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

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        /* Toastr Customization */
        #toast-container > div {
            opacity: 1;
            box-shadow: 0 0 12px rgba(0,0,0,0.15);
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
        #suppliersTable {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(44, 62, 80, 0.08), 0 1.5px 4px rgba(52, 152, 219, 0.07);
            overflow: hidden;
        }
        #suppliersTable thead th {
            background: linear-gradient(90deg,rgb(15, 127, 22) 0%,rgb(101, 219, 142) 100%);
            color: #fff;
            font-size: 1.1rem;
            font-weight: 600;
            border: none;
            letter-spacing: 0.5px;
        }
        #suppliersTable tbody tr {
            transition: background 0.2s, box-shadow 0.2s;
        }
        #suppliersTable tbody tr:hover {
            background: #eaf6ff;
            box-shadow: 0 2px 8px rgba(52,152,219,0.08);
        }
        #suppliersTable td, #suppliersTable th {
            vertical-align: middle;
            border: none;
        }
        #suppliersTable td {
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
        #suppliersTable tbody tr td {
            padding: 0.75rem 1rem;
        }
        #suppliersTable thead th {
            padding: 1rem 1rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/topbar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col">
                    <div class="section-header">Supplier Management</div>
                </div>
                <div class="col text-end">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
                        <i class="fas fa-plus"></i> Add New Supplier
                    </button>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addStockMovementModal">
                        <i class="fas fa-boxes"></i> Add Stock Movement
                    </button>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="suppliersTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Supplier Name</th>
                                    <th>Contact Number</th>
                                    <th>Email</th>
                                    <th>Address</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['supplier_id']; ?></td>
                                    <td><?php echo $row['supplier_name']; ?></td>
                                    <td><?php echo $row['contact_number']; ?></td>
                                    <td><?php echo $row['email']; ?></td>
                                    <td><?php echo $row['address']; ?></td>
                                    <td><?php echo $row['created_at']; ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editSupplierModal<?php echo $row['supplier_id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" style="display: inline;" onsubmit="return confirmDelete()">
                                                <input type="hidden" name="supplier_id" value="<?php echo $row['supplier_id']; ?>">
                                                <button type="submit" name="delete_supplier" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Edit Modal for each supplier -->
                                <div class="modal fade" id="editSupplierModal<?php echo $row['supplier_id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Supplier</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="supplier_id" value="<?php echo $row['supplier_id']; ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label">Supplier Name</label>
                                                        <input type="text" class="form-control" name="supplier_name" 
                                                               value="<?php echo $row['supplier_name']; ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Contact Number</label>
                                                        <input type="text" class="form-control" name="contact_number" 
                                                               value="<?php echo $row['contact_number']; ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Email</label>
                                                        <input type="email" class="form-control" name="email" 
                                                               value="<?php echo $row['email']; ?>">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Address</label>
                                                        <textarea class="form-control" name="address" rows="3" required><?php echo $row['address']; ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" name="update_supplier" class="btn btn-primary">Update Supplier</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <br>
            <!-- Stock Movements Section -->
            <div class="row mb-4">
                <div class="col">
                    <div class="section-header">Stock Movements</div>
                </div>
            </div>

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
                                    <th>Actions</th>
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
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editStockMovementModal<?php echo $movement['movement_id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this stock movement? This will affect inventory levels.');">
                                                <input type="hidden" name="movement_id" value="<?php echo $movement['movement_id']; ?>">
                                                <button type="submit" name="delete_stock_movement" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Edit Modal for each stock movement -->
                                <div class="modal fade" id="editStockMovementModal<?php echo $movement['movement_id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Stock Movement</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="movement_id" value="<?php echo $movement['movement_id']; ?>">
                                                    <input type="hidden" name="product_id" value="<?php echo $movement['product_id']; ?>">
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Product</label>
                                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($movement['product_name']); ?>" disabled>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Movement Type</label>
                                                        <select class="form-select" name="movement_type" required>
                                                            <option value="Stock-in" <?php echo ($movement['movement_type'] == 'Stock-in') ? 'selected' : ''; ?>>Stock In</option>
                                                            <option value="Stock-out" <?php echo ($movement['movement_type'] == 'Stock-out') ? 'selected' : ''; ?>>Stock Out</option>
                                                        </select>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Quantity</label>
                                                        <input type="number" class="form-control" name="quantity" value="<?php echo $movement['quantity']; ?>" required min="1">
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Unit Price</label>
                                                        <input type="number" class="form-control" name="unit_price" value="<?php echo $movement['unit_price']; ?>" required step="0.01" min="0">
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Supplier</label>
                                                        <select class="form-select" name="supplier_id" required>
                                                            <option value="">Select Supplier</option>
                                                            <?php 
                                                            $result->data_seek(0);
                                                            while($supplier = $result->fetch_assoc()): 
                                                            ?>
                                                            <option value="<?php echo $supplier['supplier_id']; ?>" <?php echo ($movement['supplier_id'] == $supplier['supplier_id']) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                                                            </option>
                                                            <?php endwhile; ?>
                                                        </select>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Notes</label>
                                                        <textarea class="form-control" name="notes" rows="3"><?php echo htmlspecialchars($movement['notes']); ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" name="update_stock_movement" class="btn btn-primary">Update Movement</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Supplier Modal -->
    <div class="modal fade" id="addSupplierModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Supplier Name</label>
                            <input type="text" class="form-control" name="supplier_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contact Number</label>
                            <input type="text" class="form-control" name="contact_number" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_supplier" class="btn btn-primary">Add Supplier</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Stock Movement Modal -->
    <div class="modal fade" id="addStockMovementModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Stock Movement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category_id" id="categorySelect" required>
                                <option value="">Select Category</option>
                                <?php 
                                $categories_result->data_seek(0);
                                while($category = $categories_result->fetch_assoc()): 
                                ?>
                                <option value="<?php echo $category['category_id']; ?>">
                                    <?php echo $category['category_name']; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Product</label>
                            <select class="form-select" name="product_id" id="productSelect" required disabled>
                                <option value="">Select Product</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Movement Type</label>
                            <select class="form-select" name="movement_type" required>
                                <option value="Stock-in">Stock In</option>
                                <option value="Stock-out">Stock Out</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" class="form-control" name="quantity" required min="1">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Unit Price</label>
                            <input type="number" class="form-control" name="unit_price" required step="0.01" min="0">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Supplier</label>
                            <select class="form-select" name="supplier_id" required>
                                <option value="">Select Supplier</option>
                                <?php 
                                $result->data_seek(0);
                                while($supplier = $result->fetch_assoc()): 
                                ?>
                                <option value="<?php echo $supplier['supplier_id']; ?>">
                                    <?php echo $supplier['supplier_name']; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_stock_movement" class="btn btn-primary">Add Stock Movement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Toastr JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        // Store products data
        const products = <?php echo $products_json; ?>;
        
        // Initialize Toastr
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "timeOut": "3000"
        };

        // Initialize DataTable
        $(document).ready(function() {
            $('#suppliersTable').DataTable({
                "lengthMenu": [[10, 20, 30, -1], [10, 20, 30, "All"]],
                "pageLength": 10,
                "language": {
                    "search": "Search suppliers:",
                    "lengthMenu": "Show _MENU_ entries per page",
                    "info": "Showing _START_ to _END_ of _TOTAL_ suppliers",
                    "infoEmpty": "Showing 0 to 0 of 0 suppliers",
                    "infoFiltered": "(filtered from _MAX_ total suppliers)"
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

            // Handle category selection change
            $('#categorySelect').change(function() {
                const categoryId = $(this).val();
                const productSelect = $('#productSelect');
                
                // Clear current options
                productSelect.empty().append('<option value="">Select Product</option>');
                
                if (categoryId) {
                    // Filter products by selected category
                    const filteredProducts = products.filter(product => product.category_id === categoryId);
                    
                    // Add filtered products to select
                    filteredProducts.forEach(product => {
                        productSelect.append(`<option value="${product.product_id}">${product.product_name}</option>`);
                    });
                    
                    // Enable product select
                    productSelect.prop('disabled', false);
                } else {
                    // Disable product select if no category selected
                    productSelect.prop('disabled', true);
                }
            });
        });

        // Show messages from localStorage
        if (localStorage.getItem('message')) {
            toastr.success(localStorage.getItem('message'));
            localStorage.removeItem('message');
        }
        if (localStorage.getItem('error')) {
            toastr.error(localStorage.getItem('error'));
            localStorage.removeItem('error');
        }

        // Confirm delete
        function confirmDelete() {
            return confirm('Are you sure you want to delete this supplier?');
        }
    </script>
</body>
</html>