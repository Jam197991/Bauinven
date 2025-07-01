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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_product'])) {
        $category_id = $_POST['category_id'];
        $product_name = $_POST['product_name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        
        // Check for duplicate product name
        $check_sql = "SELECT product_id FROM products WHERE product_name = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $product_name);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            echo "<script>localStorage.setItem('error', 'Product name already exists! Please use a different name.');</script>";
        } else {
            // Handle image upload
            $target_dir = "../uploads/products/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $image_url = "";
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $target_file = $target_dir . time() . '_' . basename($_FILES['image']['name']);
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $image_url = 'uploads/products/' . basename($target_file);
                }
            }
            
            $sql = "INSERT INTO products (category_id, product_name, description, price, image_url) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issds", $category_id, $product_name, $description, $price, $image_url);
            if($stmt->execute()) {
                echo "<script>localStorage.setItem('message', 'Product added successfully!');</script>";
            } else {
                echo "<script>localStorage.setItem('error', 'Error adding product!');</script>";
            }
        }
    }
    
    if (isset($_POST['delete_product'])) {
        $product_id = $_POST['product_id'];
        
        // Get image URL before deleting
        $sql = "SELECT image_url FROM products WHERE product_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if (file_exists($row['image_url'])) {
                unlink($row['image_url']);  
            }
        }
        
        $sql = "DELETE FROM products WHERE product_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $product_id);
        if($stmt->execute()) {
            echo "<script>localStorage.setItem('message', 'Product deleted successfully!');</script>";
        } else {
            echo "<script>localStorage.setItem('error', 'Error deleting product!');</script>";
        }
    }
    
    if (isset($_POST['update_product'])) {
        $product_id = $_POST['product_id'];
        $category_id = $_POST['category_id'];
        $product_name = $_POST['product_name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        
        // Check for duplicate product name, excluding current product
        $check_sql = "SELECT product_id FROM products WHERE product_name = ? AND product_id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $product_name, $product_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            echo "<script>localStorage.setItem('error', 'Product name already exists! Please use a different name.');</script>";
        } else {
            $image_url = "";
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $target_dir = "../uploads/products/";
                $target_file = $target_dir . time() . '_' . basename($_FILES['image']['name']);
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $image_url = 'uploads/products/' . basename($target_file);
                    
                    // Delete old image
                    $sql = "SELECT image_url FROM products WHERE product_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $product_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($row = $result->fetch_assoc()) {
                        if ($row['image_url'] && file_exists('../' . $row['image_url'])) {
                            unlink('../' . $row['image_url']);
                        }
                    }
                }
            }
            
            if ($image_url) {
                $sql = "UPDATE products SET category_id = ?, product_name = ?, description = ?, price = ?, image_url = ? WHERE product_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issdsi", $category_id, $product_name, $description, $price, $image_url, $product_id);
            } else {
                $sql = "UPDATE products SET category_id = ?, product_name = ?, description = ?, price = ? WHERE product_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issdi", $category_id, $product_name, $description, $price, $product_id);
            }
            if($stmt->execute()) {
                echo "<script>localStorage.setItem('message', 'Product updated successfully!');</script>";
            } else {
                echo "<script>localStorage.setItem('error', 'Error updating product!');</script>";
            }
        }
    }

    if (isset($_POST['update_quantity'])) {
        $product_id = $_POST['product_id'];
        $quantity = $_POST['quantity'];
        $operation = $_POST['operation']; // 'add', 'update', or 'delete'
        
        if ($operation == 'delete') {
            $sql = "DELETE FROM products WHERE product_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $product_id);
        } else {
            // Check if products record exists
            $check_sql = "SELECT quantity FROM products WHERE product_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("i", $product_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Update existing record
                $sql = "UPDATE products SET quantity = ?, updated_at = NOW() WHERE product_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $quantity, $product_id);
            } else {
                // Insert new record
                $sql = "INSERT INTO products (product_id, quantity, updated_at) VALUES (?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $product_id, $quantity);
            }
        }
        
        if($stmt->execute()) {
            echo "<script>localStorage.setItem('message', 'Quantity " . ($operation == 'delete' ? 'deleted' : 'updated') . " successfully!');</script>";
        } else {
            echo "<script>localStorage.setItem('error', 'Error " . ($operation == 'delete' ? 'deleting' : 'updating') . " quantity!');</script>";
        }
    }
}


// Fetch all products with category names
$sql = "SELECT p.*, c.category_type, COALESCE(i.quantity, 0) as quantity 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id 
        LEFT JOIN products i ON p.product_id = i.product_id
        ORDER BY p.created_at DESC";    
$result = $conn->query($sql);

// Fetch categories for dropdown
$categories_sql = "SELECT category_id, category_type FROM categories";
$categories_result = $conn->query($categories_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management</title>
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

        .product-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 5px;
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

        .price {
            font-weight: bold;
            color: #2ecc71;
        }

        .description {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .quantity-low {
            color: #e74c3c;
            font-weight: bold;
        }

        .quantity-high {
            color: #27ae60;
            font-weight: bold;
        }

        .quantity-normal {
            color: #f39c12;
            font-weight: bold;
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
        #productsTable {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(44, 62, 80, 0.08), 0 1.5px 4px rgba(52, 152, 219, 0.07);
            overflow: hidden;
        }
        #productsTable thead th {
            background: linear-gradient(90deg,rgb(15, 127, 22) 0%,rgb(101, 219, 142) 100%);
            color: #fff;
            font-size: 1.1rem;
            font-weight: 600;
            border: none;
            letter-spacing: 0.5px;
        }
        #productsTable tbody tr {
            transition: background 0.2s, box-shadow 0.2s;
        }
        #productsTable tbody tr:hover {
            background: #eaf6ff;
            box-shadow: 0 2px 8px rgba(52,152,219,0.08);
        }
        #productsTable td, #productsTable th {
            vertical-align: middle;
            border: none;
        }
        #productsTable td {
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
        #productsTable .product-image {
            border: 2px solid #eaf6ff;
            box-shadow: 0 2px 8px rgba(52,152,219,0.07);
            transition: transform 0.2s;
        }
        #productsTable .product-image:hover {
            transform: scale(1.08) rotate(-2deg);
            border-color: #3498db;
        }
        #productsTable tbody tr td {
            padding: 0.75rem 1rem;
        }
        #productsTable thead th {
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
                    <div class="section-header">Product Management</div>
                </div>
                <div class="col text-end">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                        <i class="fas fa-plus"></i> Add New Product
                    </button>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="productsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Image</th>
                                    <th>Product Name</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['product_id']; ?></td>
                                    <td>
                                        <?php if($row['image_url']): ?>
                                            <img src="../<?php echo $row['image_url']; ?>" class="product-image" alt="Product Image">
                                        <?php else: ?>
                                            <img src="../assets/images/no-image.png" class="product-image" alt="No Image">
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $row['product_name']; ?></td>
                                    <td><?php echo $row['category_type']; ?></td>
                                    <td class="description" title="<?php echo $row['description']; ?>">
                                        <?php echo $row['description']; ?>
                                    </td>
                                    <td class="price">₱<?php echo number_format($row['price'], 2); ?></td>
                                    <td>
                                        <?php 
                                        $quantity = $row['quantity'];
                                        if ($quantity <= 30) {
                                            echo '<span class="quantity-low">' . $quantity . ' (Low Stock)</span>';
                                        } elseif ($quantity >= 100) {
                                            echo '<span class="quantity-high">' . $quantity . ' (High Stock)</span>';
                                        } else {
                                            echo '<span class="quantity-normal">' . $quantity . ' (Normal)</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editProductModal<?php echo $row['product_id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-info" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#quantityModal<?php echo $row['product_id']; ?>">
                                                <i class="fas fa-boxes"></i>
                                            </button>
                                            <form method="POST" style="display: inline;" onsubmit="return confirmDelete()">
                                                <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
                                                <button type="submit" name="delete_product" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Edit Modal for each product -->
                                <div class="modal fade" id="editProductModal<?php echo $row['product_id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Product</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST" enctype="multipart/form-data">
                                                <div class="modal-body">
                                                    <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label">Category</label>
                                                        <select class="form-select" name="category_id" required>
                                                            <?php 
                                                            $categories_result->data_seek(0);
                                                            while($category = $categories_result->fetch_assoc()): 
                                                            ?>
                                                            <option value="<?php echo $category['category_id']; ?>" 
                                                                    <?php echo ($category['category_id'] == $row['category_id']) ? 'selected' : ''; ?>>
                                                                <?php echo $category['category_type']; ?>
                                                            </option>
                                                            <?php endwhile; ?>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">  
                                                        <label class="form-label">Product Name</label>
                                                        <input type="text" class="form-control" name="product_name" 
                                                               value="<?php echo $row['product_name']; ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Description</label>
                                                        <textarea class="form-control" name="description" rows="3" required><?php echo $row['description']; ?></textarea>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Price</label>
                                                        <input type="number" class="form-control" name="price" 
                                                               value="<?php echo $row['price']; ?>" step="0.01" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Current Image</label><br>
                                                        <?php if($row['image_url']): ?>
                                                            <img src="../<?php echo $row['image_url']; ?>" class="product-image mb-2" alt="Product Image">
                                                        <?php endif; ?>
                                                        <input type="file" class="form-control" name="image">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" name="update_product" class="btn btn-primary">Update Product</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Quantity Management Modal -->
                                <div class="modal fade" id="quantityModal<?php echo $row['product_id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Manage Quantity - <?php echo $row['product_name']; ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Current Quantity</label>
                                                        <input type="text" class="form-control" value="<?php echo $row['quantity']; ?>" readonly>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Operation</label>
                                                        <select class="form-select" name="operation" id="operation<?php echo $row['product_id']; ?>" required>
                                                            
                                                            <option value="update">Update Quantity</option>
                                                            
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="mb-3" id="quantityInput<?php echo $row['product_id']; ?>">
                                                        <label class="form-label">New Quantity</label>
                                                        <input type="number" class="form-control" name="quantity" min="0" value="<?php echo $row['quantity']; ?>">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" name="update_quantity" class="btn btn-primary">Save Changes</button>
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

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php 
                                $categories_result->data_seek(0);
                                while($category = $categories_result->fetch_assoc()): 
                                ?>
                                <option value="<?php echo $category['category_id']; ?>">
                                    <?php echo $category['category_type']; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Product Name</label>
                            <input type="text" class="form-control" name="product_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price</label>
                            <input type="number" class="form-control" name="price" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Image</label>
                            <input type="file" class="form-control" name="image">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
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
            $('#productsTable').DataTable({
                "lengthMenu": [[10, 20, 30, -1], [10, 20, 30, "All"]],
                "pageLength": 10,
                "language": {
                    "search": "Search categories:",
                    "lengthMenu": "Show _MENU_ entries per page",
                    "info": "Showing _START_ to _END_ of _TOTAL_ products",
                    "infoEmpty": "Showing 0 to 0 of 0 products",
                    "infoFiltered": "(filtered from _MAX_ total products)"
                }
            });
        });


        // Check for messages in localStorage
        window.onload = function() {
            const message = localStorage.getItem('message');
            const error = localStorage.getItem('error');
            
            if (message) {
                toastr.success(message);
                localStorage.removeItem('message');
            }
            
            if (error) {
                toastr.error(error);
                localStorage.removeItem('error');
            }
        };

        // Confirmation dialog for delete
        function confirmDelete() {
            return confirm('Are you sure you want to delete this product?');
        }

        // Close modals and reset forms
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('hidden.bs.modal', function () {
                this.querySelector('form').reset();
            });
        });

        // Handle quantity operation changes
        document.querySelectorAll('[id^="operation"]').forEach(select => {
            select.addEventListener('change', function() {
                const productId = this.id.replace('operation', '');
                const quantityInput = document.getElementById('quantityInput' + productId);
                
                if (this.value === 'delete') {
                    quantityInput.style.display = 'none';
                } else {
                    quantityInput.style.display = 'block';
                }
            });
        });
    </script>
</body>
</html>