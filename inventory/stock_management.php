<?php
session_start();
include '../includes/database.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_stock'])) {
        $product_id = $_POST['product_id'];
        $quantity = $_POST['quantity'];
        
        $sql = "INSERT INTO inventory (product_id, quantity, updated_at) VALUES (?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $product_id, $quantity);
        if($stmt->execute()) {
            echo "<script>localStorage.setItem('message', 'Stock added successfully!');</script>";
        }
    }
    
    if (isset($_POST['delete_stock'])) {
        $product_id = $_POST['product_id'];
        
        $sql = "DELETE FROM inventory WHERE product_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $product_id);
        if($stmt->execute()) {
            echo "<script>localStorage.setItem('message', 'Stock deleted successfully!');</script>";
        }
    }
    
    if (isset($_POST['update_stock'])) {
        $product_id = $_POST['product_id'];
        $quantity = $_POST['quantity'];
        
        $sql = "UPDATE inventory SET quantity = ?, updated_at = NOW() WHERE product_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $quantity, $product_id);
        if($stmt->execute()) {
            echo "<script>localStorage.setItem('message', 'Stock updated successfully!');</script>";
        }
    }
}

// Define threshold values for alerts
$LOW_STOCK_THRESHOLD = 10;
$HIGH_STOCK_THRESHOLD = 100;

// Fetch stock data with product and category information
$query = "SELECT p.product_id, p.product_name, p.description, p.price, 
          c.category_name, c.category_type, i.quantity, i.updated_at 
          FROM inventory i 
          JOIN products p ON i.product_id = p.product_id 
          JOIN categories c ON p.category_id = c.category_id 
          ORDER BY i.updated_at DESC";
$result = mysqli_query($conn, $query);

// Fetch products for dropdown
$products_query = "SELECT DISTINCT p.product_id, p.product_name, c.category_name 
                  FROM products p 
                  JOIN categories c ON p.category_id = c.category_id 
                  ORDER BY p.product_name";
$products_result = mysqli_query($conn, $products_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Management</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Toastr CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <!-- Custom CSS -->
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

        .low-stock {
            background-color: #ffe6e6 !important;
        }
        .high-stock {
            background-color: #e6ffe6 !important;
        }
        .alert-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
        }
        .low-stock-badge {
            background-color: #ff4444;
            color: white;
        }
        .high-stock-badge {
            background-color: #00C851;
            color: white;
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
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/topbar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col">
                    <h2>Stock Management</h2>
                </div>
                <div class="col text-end">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStockModal">
                        <i class="fas fa-plus"></i> Add New Stock
                    </button>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="stockTable">
                            <thead>
                                <tr>
                                    <th>Product ID</th>
                                    <th>Product Name</th>
                                    <th>Category</th>
                                    <th>Quantity</th>
                                    <th>Status</th>
                                    <th>Last Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                    <?php
                                    $stockClass = '';
                                    $statusBadge = '';
                                    
                                    if ($row['quantity'] <= $LOW_STOCK_THRESHOLD) {
                                        $stockClass = 'low-stock';
                                        $statusBadge = '<span class="alert-badge low-stock-badge">Low Stock</span>';
                                    } elseif ($row['quantity'] >= $HIGH_STOCK_THRESHOLD) {
                                        $stockClass = 'high-stock';
                                        $statusBadge = '<span class="alert-badge high-stock-badge">High Stock</span>';
                                    }
                                    ?>
                                    <tr class="<?php echo $stockClass; ?>">
                                        <td><?php echo htmlspecialchars($row['product_id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                                        <td><?php echo $statusBadge; ?></td>
                                        <td><?php echo date('Y-m-d H:i:s', strtotime($row['updated_at'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button type="button" class="btn btn-sm btn-primary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editStockModal<?php echo $row['product_id']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" style="display: inline;" onsubmit="return confirmDelete()">
                                                    <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
                                                    <button type="submit" name="delete_stock" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Edit Modal for each stock item -->
                                    <div class="modal fade" id="editStockModal<?php echo $row['product_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Stock</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label">Product</label>
                                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($row['product_name']); ?>" readonly>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Category</label>
                                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($row['category_name']); ?>" readonly>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Quantity</label>
                                                            <input type="number" class="form-control" name="quantity" value="<?php echo $row['quantity']; ?>" required min="0">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" name="update_stock" class="btn btn-primary">Update Stock</button>
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

    <!-- Add Stock Modal -->
    <div class="modal fade" id="addStockModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Product</label>
                            <select class="form-select" name="product_id" required>
                                <option value="">Select Product</option>
                                <?php while($product = mysqli_fetch_assoc($products_result)): ?>
                                    <option value="<?php echo $product['product_id']; ?>">
                                        <?php echo htmlspecialchars($product['product_name'] . ' (' . $product['category_name'] . ')'); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" class="form-control" name="quantity" required min="0">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_stock" class="btn btn-primary">Add Stock</button>
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
        // Initialize Toastr
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "timeOut": "3000"
        };

        // Initialize DataTable
        $(document).ready(function() {
            $('#stockTable').DataTable({
                "lengthMenu": [[10, 20, 30, -1], [10, 20, 30, "All"]],
                "pageLength": 10,
                "language": {
                    "search": "Search stock:",
                    "lengthMenu": "Show _MENU_ entries per page",
                    "info": "Showing _START_ to _END_ of _TOTAL_ items",
                    "infoEmpty": "Showing 0 to 0 of 0 items",
                    "infoFiltered": "(filtered from _MAX_ total items)"
                }
            });
        });

        // Check for messages in localStorage
        window.onload = function() {
            const message = localStorage.getItem('message');
            if (message) {
                toastr.success(message);
                localStorage.removeItem('message');
            }
        };

        // Confirmation dialog for delete
        function confirmDelete() {
            return confirm('Are you sure you want to delete this stock record?');
        }

        // Close modals and reset forms
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('hidden.bs.modal', function () {
                this.querySelector('form').reset();
            });
        });
    </script>
</body>
</html>
