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


// Display error message if exists
if (isset($_SESSION['error'])) {
    echo "<script>localStorage.setItem('message', '" . addslashes($_SESSION['error']) . "');</script>";
    unset($_SESSION['error']);
}

// Display success message if exists
if (isset($_SESSION['success'])) {
    echo "<script>localStorage.setItem('message', '" . addslashes($_SESSION['success']) . "');</script>";
    unset($_SESSION['success']);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_category'])) {
        $category_name = $_POST['category_name'];
        $category_type = $_POST['category_type'];
        
        // Add validation and debugging
        if (empty($category_type)) {
            $_SESSION['error'] = 'Error: Category Type cannot be empty!';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

        // First check if category name already exists
        $check_name_sql = "SELECT category_name, category_type FROM categories WHERE category_name = ?";
        $check_name_stmt = $conn->prepare($check_name_sql);
        $check_name_stmt->bind_param("s", $category_name);
        $check_name_stmt->execute();
        $name_result = $check_name_stmt->get_result();
        
        if ($name_result->num_rows > 0) {
            $existing_categories = [];
            while($row = $name_result->fetch_assoc()) {
                $existing_categories[] = $row['category_type'];
            }
            $message = "Error: Category name '$category_name' already exists with type(s): " . implode(", ", $existing_categories);
            $_SESSION['error'] = $message;
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

        // Then check for exact duplicate (name + type)
        $check_sql = "SELECT COUNT(*) as count FROM categories WHERE category_name = ? AND category_type = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $category_name, $category_type);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            $_SESSION['error'] = "Error: A category with name \"$category_name\" and type \"$category_type\" already exists!";
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        
        // Handle image upload
        $target_dir = "../uploads/categories/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $image_url = "";
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_file = $target_dir . time() . '_' . basename($_FILES['image']['name']);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_url = $target_file;
            }
        }
        
        $sql = "INSERT INTO categories (category_name, category_type, image_url) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $category_name, $category_type, $image_url);
        if($stmt->execute()) {
            $_SESSION['success'] = 'Category added successfully!';
        } else {
            $_SESSION['error'] = 'Error adding category: ' . $stmt->error;
        }
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    if (isset($_POST['delete_category'])) {
        $category_id = $_POST['category_id'];
        
        // Get image URL before deleting
        $sql = "SELECT image_url FROM categories WHERE category_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if (file_exists($row['image_url'])) {
                unlink($row['image_url']);
            }
        }
        
        $sql = "DELETE FROM categories WHERE category_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $category_id);
        if($stmt->execute()) {
            $_SESSION['success'] = 'Category deleted successfully!';
        }
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    if (isset($_POST['update_category'])) {
        $category_id = $_POST['category_id'];
        $category_name = $_POST['category_name'];
        $category_type = $_POST['category_type'];
        
        // First check if category name already exists (excluding current category)
        $check_name_sql = "SELECT category_name, category_type FROM categories WHERE category_name = ? AND category_id != ?";
        $check_name_stmt = $conn->prepare($check_name_sql);
        $check_name_stmt->bind_param("si", $category_name, $category_id);
        $check_name_stmt->execute();
        $name_result = $check_name_stmt->get_result();
        
        if ($name_result->num_rows > 0) {
            $existing_categories = [];
            while($row = $name_result->fetch_assoc()) {
                $existing_categories[] = $row['category_type'];
            }
            $message = "Error: Category name '$category_name' already exists with type(s): " . implode(", ", $existing_categories);
            $_SESSION['error'] = $message;
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

        // Then check for exact duplicate (name + type)
        $check_sql = "SELECT COUNT(*) as count FROM categories WHERE category_name = ? AND category_type = ? AND category_id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ssi", $category_name, $category_type, $category_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            $_SESSION['error'] = "Error: A category with name \"$category_name\" and type \"$category_type\" already exists!";
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        
        $image_url = "";
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = "../uploads/categories/";
            $target_file = $target_dir . time() . '_' . basename($_FILES['image']['name']);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_url = $target_file;
                
                // Delete old image
                $sql = "SELECT image_url FROM categories WHERE category_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $category_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    if (file_exists($row['image_url'])) {
                        unlink($row['image_url']);
                    }
                }
            }
        }
        
        if ($image_url) {
            $sql = "UPDATE categories SET category_name = ?, category_type = ?, image_url = ? WHERE category_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $category_name, $category_type, $image_url, $category_id);
        } else {
            $sql = "UPDATE categories SET category_name = ?, category_type = ? WHERE category_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $category_name, $category_type, $category_id);
        }
        if($stmt->execute()) {
            $_SESSION['success'] = 'Category updated successfully!';
        }
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Fetch all categories
$sql = "SELECT * FROM categories";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Management</title>
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

        .category-image {
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
        #categoriesTable {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(44, 62, 80, 0.08), 0 1.5px 4px rgba(52, 152, 219, 0.07);
            overflow: hidden;
        }
        #categoriesTable thead th {
            background: linear-gradient(90deg,rgb(15, 127, 22) 0%,rgb(101, 219, 142) 100%);
            color: #fff;
            font-size: 1.1rem;
            font-weight: 600;
            border: none;
            letter-spacing: 0.5px;
        }
        #categoriesTable tbody tr {
            transition: background 0.2s, box-shadow 0.2s;
        }
        #categoriesTable tbody tr:hover {
            background: #eaf6ff;
            box-shadow: 0 2px 8px rgba(52,152,219,0.08);
        }
        #categoriesTable td, #categoriesTable th {
            vertical-align: middle;
            border: none;
        }
        #categoriesTable td {
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
        #categoriesTable .category-image {
            border: 2px solid #eaf6ff;
            box-shadow: 0 2px 8px rgba(52,152,219,0.07);
            transition: transform 0.2s;
        }
        #categoriesTable .category-image:hover {
            transform: scale(1.08) rotate(-2deg);
            border-color: #3498db;
        }
        #categoriesTable tbody tr td {
            padding: 0.75rem 1rem;
        }
        #categoriesTable thead th {
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
                    <div class="section-header">Category Management</div>
                </div>
                <div class="col text-end">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="fas fa-plus"></i> Add New Category
                    </button>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="categoriesTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Image</th>
                                    <th>Category Name</th>
                                    <th>Category Type</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['category_id']; ?></td>
                                    <td>
                                        <?php if($row['image_url']): ?>
                                            <img src="<?php echo $row['image_url']; ?>" class="category-image" alt="Category Image">
                                        <?php else: ?>
                                            <img src="../assets/images/no-image.png" class="category-image" alt="No Image">
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $row['category_name']; ?></td>
                                    <td><?php echo $row['category_type']; ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editCategoryModal<?php echo $row['category_id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" style="display: inline;" onsubmit="return confirmDelete()">
                                                <input type="hidden" name="category_id" value="<?php echo $row['category_id']; ?>">
                                                <button type="submit" name="delete_category" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Edit Modal for each category -->
                                <div class="modal fade" id="editCategoryModal<?php echo $row['category_id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Category</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST" enctype="multipart/form-data">
                                                <div class="modal-body">
                                                    <input type="hidden" name="category_id" value="<?php echo $row['category_id']; ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label">Category Name</label>
                                                        <input type="text" class="form-control" name="category_name" 
                                                               value="<?php echo $row['category_name']; ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Category Type</label>
                                                        <select class="form-select" name="category_type" required>
                                                            <option value="">Select Category Type</option>
                                                            <option value="Drinks" <?php echo ($row['category_type'] == 'Drinks') ? 'selected' : ''; ?>>Drinks</option>
                                                            <option value="Meals" <?php echo ($row['category_type'] == 'Meals') ? 'selected' : ''; ?>>Meals</option>
                                                            <option value="Snacks" <?php echo ($row['category_type'] == 'Snacks') ? 'selected' : ''; ?>>Snacks</option>
                                                            <option value="Bread" <?php echo ($row['category_type'] == 'Bread') ? 'selected' : ''; ?>>Bread</option>
                                                            <option value="Muffins" <?php echo ($row['category_type'] == 'Muffins') ? 'selected' : ''; ?>>Muffins</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Current Image</label><br>
                                                        <?php if($row['image_url']): ?>
                                                            <img src="<?php echo $row['image_url']; ?>" class="category-image mb-2" alt="Category Image">
                                                        <?php endif; ?>
                                                        <input type="file" class="form-control" name="image">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" name="update_category" class="btn btn-primary">Update Category</button>
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

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Category Name</label>
                            <input type="text" class="form-control" name="category_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category Type</label>
                            <select class="form-select" name="category_type" required>
                                <option value="">Select Category Type</option>
                                <option value="Drinks">Drinks</option>
                                <option value="Meals">Meals</option>
                                <option value="Snacks">Snacks</option>
                                <option value="Bread">Bread</option>
                                <option value="Muffins">Muffins</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Image</label>
                            <input type="file" class="form-control" name="image">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
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
            $('#categoriesTable').DataTable({
                "lengthMenu": [[10, 20, 30, -1], [10, 20, 30, "All"]],
                "pageLength": 10,
                "language": {
                    "search": "Search categories:",
                    "lengthMenu": "Show _MENU_ entries per page",
                    "info": "Showing _START_ to _END_ of _TOTAL_ categories",
                    "infoEmpty": "Showing 0 to 0 of 0 categories",
                    "infoFiltered": "(filtered from _MAX_ total categories)"
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
            return confirm('Are you sure you want to delete this category?');
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
