<?php
session_start();
require_once '../includes/config.php';


// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$query = "SELECT * FROM user WHERE user_id = ".$_SESSION['user_id']."";
$rs = $connection->query($query);
$num = $rs->num_rows;
$rows = $rs->fetch_assoc();
$fullName = $rows['firstname']." ".$rows['lastname'];

// Get active section from URL parameter, default to categories
$active_section = isset($_GET['section']) ? $_GET['section'] : 'categories';


// Get categories
$categories_sql = "SELECT * FROM categories ORDER BY category_name";
$categories_result = $connection->query($categories_sql);

// Get inventory items
$items_sql = "SELECT i.*, c.category_name, s.supplier_name 
              FROM inventory_items i 
              LEFT JOIN categories c ON i.category_id = c.category_id 
              LEFT JOIN suppliers s ON i.supplier_id = s.supplier_id 
              ORDER BY i.item_name";
$items_result = $connection->query($items_sql);

// Get suppliers
$suppliers_sql = "SELECT * FROM suppliers ORDER BY supplier_name";
$suppliers_result = $connection->query($suppliers_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Dashboard - BauApp</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --sidebar-width: 280px;
            --header-height: 60px;
            --primary-color: #4CAF50;
            --primary-dark: #388E3C;
            --accent-color: #FF9800;
            --text-color: #333;
            --bg-color: #f5f5f5;
            --transition-speed: 0.3s;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --hover-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-color);
            min-height: 100vh;
            display: flex;
            color: var(--text-color);
            line-height: 1.6;
        }

        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background: white;
            position: fixed;
            left: 0;
            top: 0;
            box-shadow: var(--card-shadow);
            transition: transform var(--transition-speed);
            z-index: 1000;
        }

        .sidebar-header {
            height: var(--header-height);
            display: flex;
            align-items: center;
            padding: 0 15px;
            background: var(--primary-color);
            color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .sidebar-header h2 {
            font-size: 1.1rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            font-family: 'Poppins', sans-serif;
            display: flex;
            align-items: center;
            white-space: nowrap;
            width: 100%;
        }

        .sidebar-header h2 i {
            margin-right: 8px;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            flex-shrink: 0;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .menu-item {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            color: var(--text-color);
            text-decoration: none;
            transition: all var(--transition-speed);
            cursor: pointer;
            position: relative;
            overflow: hidden;
            font-weight: 500;
            font-size: 1.05rem;
        }

        .menu-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: var(--primary-color);
            transform: scaleY(0);
            transition: transform var(--transition-speed);
        }

        .menu-item:hover::before,
        .menu-item.active::before {
            transform: scaleY(1);
        }

        .menu-item:hover {
            background: rgba(76, 175, 80, 0.1);
            color: var(--primary-color);
            padding-left: 25px;
        }

        .menu-item.active {
            background: rgba(76, 175, 80, 0.1);
            color: var(--primary-color);
        }

        .menu-item i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
            transition: transform var(--transition-speed);
            font-size: 1.1rem;
        }

        .menu-item:hover i {
            transform: scale(1.1);
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: margin var(--transition-speed);
        }

        .header {
            height: var(--header-height);
            background: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: var(--card-shadow);
        }

        .header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
        }

        .menu-toggle {
            display: none;
            font-size: 1.5rem;
            color: var(--text-color);
            cursor: pointer;
            transition: transform var(--transition-speed);
        }

        .menu-toggle:hover {
            transform: scale(1.1);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-info span {
            color: var(--text-color);
            font-weight: 500;
            font-size: 1.1rem;
        }

        .logout-btn {
            background: var(--primary-color);
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transition: all var(--transition-speed);
            text-decoration: none;
            font-size: 1rem;
            min-width: 140px;
            justify-content: center;
            letter-spacing: 0.5px;
        }

        .logout-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
            color: white;
        }

        .logout-btn i {
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }

        .logout-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.95);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .logout-overlay.active {
            opacity: 1;
        }

        .logout-container {
            position: relative;
            width: 120px;
            height: 120px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .logout-leaf {
            position: absolute;
            font-size: 2.5rem;
            color: var(--primary-color);
            opacity: 0;
            transform: scale(0.5);
            transition: all 0.3s ease;
        }

        .logout-leaf.main {
            font-size: 3.5rem;
            animation: mainLeafSpin 2s infinite ease-in-out;
            opacity: 1;
            transform: scale(1);
        }

        .logout-leaf.orbit {
            animation: orbitLeaf 3s infinite linear;
        }

        .logout-leaf.orbit:nth-child(2) {
            animation-delay: -1s;
        }

        .logout-leaf.orbit:nth-child(3) {
            animation-delay: -2s;
        }

        .logout-text {
            margin-top: 2rem;
            color: var(--primary-color);
            font-size: 1.2rem;
            font-weight: 500;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
        }

        .logout-text.active {
            opacity: 1;
            transform: translateY(0);
        }

        .logout-dots {
            display: inline-block;
            animation: loadingDots 1.5s infinite;
        }

        @keyframes mainLeafSpin {
            0% {
                transform: rotate(0deg) scale(1);
            }
            50% {
                transform: rotate(180deg) scale(1.1);
            }
            100% {
                transform: rotate(360deg) scale(1);
            }
        }

        @keyframes orbitLeaf {
            0% {
                transform: rotate(0deg) translateX(40px) rotate(0deg) scale(0.8);
                opacity: 0.6;
            }
            100% {
                transform: rotate(360deg) translateX(40px) rotate(-360deg) scale(0.8);
                opacity: 0.6;
            }
        }

        @keyframes loadingDots {
            0%, 20% {
                content: '.';
            }
            40% {
                content: '..';
            }
            60%, 100% {
                content: '...';
            }
        }

        .section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: var(--card-shadow);
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.5s ease;
            display: none;
        }

        .section.active {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }

        .section-header h2 {
            color: var(--text-color);
            font-size: 1.5rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .add-btn {
            background: var(--primary-color);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all var(--transition-speed);
            font-weight: 500;
            font-size: 1rem;
            letter-spacing: 0.5px;
        }

        .add-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
        }

        .table-container {
            overflow-x: auto;
            margin-top: 20px;
            border-radius: 8px;
            box-shadow: var(--card-shadow);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: var(--text-color);
            font-size: 1.05rem;
            letter-spacing: 0.5px;
        }

        td {
            font-size: 1rem;
            color: #444;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .action-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all var(--transition-speed);
            font-weight: 500;
            font-size: 0.95rem;
            letter-spacing: 0.3px;
        }

        .edit-btn {
            background: #2196F3;
            color: white;
        }

        .edit-btn:hover {
            background: #1976D2;
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
        }

        .delete-btn {
            background: #f44336;
            color: white;
        }

        .delete-btn:hover {
            background: #d32f2f;
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
        }

        .stock-btn {
            background: var(--primary-color);
            color: white;
        }

        .stock-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .menu-toggle {
                display: block;
            }

            .section {
                padding: 15px;
            }

            .table-container {
                margin-top: 15px;
            }

            th, td {
                padding: 10px;
            }

            .action-btn {
                padding: 6px 12px;
                font-size: 0.9rem;
            }

            .section-header h2 {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <div class="logout-overlay">
        <div class="logout-container">
            <i class="fas fa-leaf logout-leaf main"></i>
            <i class="fas fa-leaf logout-leaf orbit"></i>
            <i class="fas fa-leaf logout-leaf orbit"></i>
            <i class="fas fa-leaf logout-leaf orbit"></i>
        </div>
        <div class="logout-text">Logging out<span class="logout-dots">...</span></div>
    </div>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-boxes"></i>BAULAND Inventory</h2>
        </div>
        <div class="sidebar-menu">
            <a href="?section=categories" class="menu-item <?php echo $active_section === 'categories' ? 'active' : ''; ?>">
                <i class="fas fa-tags"></i>
                Categories
            </a>
            <a href="?section=items" class="menu-item <?php echo $active_section === 'items' ? 'active' : ''; ?>">
                <i class="fas fa-box"></i>
                Inventory Items
            </a>
            <a href="?section=suppliers" class="menu-item <?php echo $active_section === 'suppliers' ? 'active' : ''; ?>">
                <i class="fas fa-truck"></i>
                Suppliers
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <div class="header-top">
                <div class="menu-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </div>
                <div class="user-info">
                <span>Welcome <?php echo htmlspecialchars($fullName); ?></span>
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-power-off"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Categories Section -->
        <div id="categories" class="section <?php echo $active_section === 'categories' ? 'active' : ''; ?>">
            <div class="section-header">
                <h2>Categories</h2>
                <a href="add_category.php" class="add-btn">
                    <i class="fas fa-plus"></i>
                    Add Category
                </a>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Category Name</th>
                            <th>Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($category = $categories_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                            <td><?php echo htmlspecialchars($category['category_type']); ?></td>
                            <td>
                                <a href="edit_category.php?id=<?php echo $category['category_id']; ?>" class="action-btn edit-btn">
                                    <i class="fas fa-edit"></i>
                                    Edit
                                </a>
                                <a href="delete_category.php?id=<?php echo $category['category_id']; ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure?')">
                                    <i class="fas fa-trash"></i>
                                    Delete
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Items Section -->
        <div id="items" class="section <?php echo $active_section === 'items' ? 'active' : ''; ?>">
            <div class="section-header">
                <h2>Inventory Items</h2>
                <a href="add_item.php" class="add-btn">
                    <i class="fas fa-plus"></i>
                    Add Item
                </a>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Supplier</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($item = $items_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                            <td>₱<?php echo number_format($item['price'], 2); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td><?php echo htmlspecialchars($item['supplier_name'] ?? 'N/A'); ?></td>
                            <td>
                                <a href="edit_item.php?id=<?php echo $item['item_id']; ?>" class="action-btn edit-btn">
                                    <i class="fas fa-edit"></i>
                                    Edit
                                </a>
                                <a href="stock_movement.php?id=<?php echo $item['item_id']; ?>" class="action-btn stock-btn">
                                    <i class="fas fa-exchange-alt"></i>
                                    Stock
                                </a>
                                <a href="delete_item.php?id=<?php echo $item['item_id']; ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure?')">
                                    <i class="fas fa-trash"></i>
                                    Delete
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Suppliers Section -->
        <div id="suppliers" class="section <?php echo $active_section === 'suppliers' ? 'active' : ''; ?>">
            <div class="section-header">
                <h2>Suppliers</h2>
                <a href="add_supplier.php" class="add-btn">
                    <i class="fas fa-plus"></i>
                    Add Supplier
                </a>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Supplier Name</th>
                            <th>Contact Person</th>
                            <th>Contact Number</th>
                            <th>Email</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($supplier = $suppliers_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($supplier['supplier_name']); ?></td>
                            <td><?php echo htmlspecialchars($supplier['contact_person']); ?></td>
                            <td><?php echo htmlspecialchars($supplier['contact_number']); ?></td>
                            <td><?php echo htmlspecialchars($supplier['email'] ?? 'N/A'); ?></td>
                            <td>
                                <a href="edit_supplier.php?id=<?php echo $supplier['supplier_id']; ?>" class="action-btn edit-btn">
                                    <i class="fas fa-edit"></i>
                                    Edit
                                </a>
                                <a href="delete_supplier.php?id=<?php echo $supplier['supplier_id']; ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure?')">
                                    <i class="fas fa-trash"></i>
                                    Delete
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Toggle sidebar on mobile
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            const sidebar = document.querySelector('.sidebar');
            const menuToggle = document.querySelector('.menu-toggle');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(e.target) && 
                !menuToggle.contains(e.target) && 
                sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
            }
        });

        // Add logout animation
        document.querySelector('.logout-btn').addEventListener('click', function(e) {
            e.preventDefault();
            const logoutOverlay = document.querySelector('.logout-overlay');
            const logoutText = document.querySelector('.logout-text');
            
            // Show logout overlay with fade effect
            logoutOverlay.style.display = 'flex';
            setTimeout(() => {
                logoutOverlay.classList.add('active');
                logoutText.classList.add('active');
            }, 50);
            
            // Redirect after animation
            setTimeout(() => {
                window.location.href = 'logout.php';
            }, 2000);
        });
    </script>
</body>
</html> 