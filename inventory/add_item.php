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

// Get categories for dropdown
$categories_sql = "SELECT * FROM categories ORDER BY category_name";
$categories_result = $conn->query($categories_sql);

// Get suppliers for dropdown
$suppliers_sql = "SELECT * FROM suppliers ORDER BY supplier_name";
$suppliers_result = $conn->query($suppliers_sql);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $item_name = trim($_POST['item_name']);
    $description = trim($_POST['description']);
    $category_id = $_POST['category_id'];
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity']);
    $supplier_id = !empty($_POST['supplier_id']) ? $_POST['supplier_id'] : null;
    
    // Validate input
    if (empty($item_name)) {
        $error = "Item name is required";
    } elseif ($price <= 0) {
        $error = "Price must be greater than 0";
    } elseif ($quantity < 0) {
        $error = "Quantity cannot be negative";
    } else {
        // Check if item already exists
        $check_sql = "SELECT * FROM inventory_items WHERE item_name = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $item_name);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Item already exists";
        } else {
            // Insert new item
            $sql = "INSERT INTO inventory_items (item_name, description, category_id, price, quantity, supplier_id) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssidii", $item_name, $description, $category_id, $price, $quantity, $supplier_id);
            
            if ($stmt->execute()) {
                $success = "Item added successfully";
                // Clear form
                $item_name = '';
                $description = '';
                $price = '';
                $quantity = '';
                $supplier_id = '';
            } else {
                $error = "Error adding item: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Item - BauApp</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .form-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
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
    </style>
</head>
<body>
    <div class="form-container">
        <a href="inventory_dashboard.php" class="back-link">← Back to Dashboard</a>
        <h2>Add New Item</h2>
        
        <?php if ($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="item_name">Item Name</label>
                <input type="text" id="item_name" name="item_name" value="<?php echo isset($item_name) ? htmlspecialchars($item_name) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description"><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id" required>
                    <option value="">Select Category</option>
                    <?php while($category = $categories_result->fetch_assoc()): ?>
                        <option value="<?php echo $category['category_id']; ?>" <?php echo (isset($category_id) && $category_id == $category['category_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['category_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="price">Price (₱)</label>
                <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo isset($price) ? htmlspecialchars($price) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="quantity">Initial Quantity</label>
                <input type="number" id="quantity" name="quantity" min="0" value="<?php echo isset($quantity) ? htmlspecialchars($quantity) : '0'; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="supplier_id">Supplier (Optional)</label>
                <select id="supplier_id" name="supplier_id">
                    <option value="">Select Supplier</option>
                    <?php while($supplier = $suppliers_result->fetch_assoc()): ?>
                        <option value="<?php echo $supplier['supplier_id']; ?>" <?php echo (isset($supplier_id) && $supplier_id == $supplier['supplier_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <button type="submit" class="submit-btn">Add Item</button>
        </form>
    </div>

    <script>
        // Add animation when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            const formContainer = document.querySelector('.form-container');
            formContainer.style.opacity = '0';
            formContainer.style.transform = 'translateY(20px)';
            formContainer.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            
            setTimeout(() => {
                formContainer.style.opacity = '1';
                formContainer.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>
</html> 