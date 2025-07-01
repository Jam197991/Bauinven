<?php
// Sidebar component
?>
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h3>Inventory System</h3>
        <button class="toggle-btn" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    
    <nav class="sidebar-nav">
        <ul>
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="category.php" class="nav-link">
                    <i class="fas fa-tags"></i>
                    <span>Categories</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="products.php" class="nav-link">
                    <i class="fas fa-box"></i>
                    <span>Products</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="suppliers.php" class="nav-link">
                    <i class="fas fa-truck"></i>
                    <span>Suppliers</span>
                </a>
            </li>

            <li class="nav-item has-dropdown">
                <a href="#" class="nav-link" id="reportsDropdownToggle">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                    <i class="fas fa-caret-down" style="margin-left:auto;"></i>
                </a>
                <div class="sidebar-dropdown-menu" id="reportsDropdownMenu" style="display:none;">
                    <a class="dropdown-item" href="SalesReport.php">Sales Report</a>
                    <a class="dropdown-item" href="InventoryReport.php">Inventory Report</a>
                    <a class="dropdown-item" href="AnalyticalReport.php">Analytical Report</a>
                </div>
            </li>

            
        </ul>
    </nav>
</div>

<style>
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    height: 100vh;
    width: 250px;
    background: linear-gradient(180deg, #388E3C 0%, #81C784 100%);
    color: #fff;
    transition: all 0.3s ease;
    z-index: 1000;
}

.sidebar.collapsed {
    width: 70px;
}

.sidebar-header {
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.sidebar-header h3 {
    margin: 0;
    font-size: 1.2rem;
    color: #fff;
}

.toggle-btn {
    background: none;
    border: none;
    color: #fff;
    cursor: pointer;
    font-size: 1.2rem;
}

.sidebar-nav ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.nav-item {
    margin: 5px 0;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    color: #fff;
    text-decoration: none;
    transition: all 0.3s ease;
}

.nav-link:hover {
    background: #66bb6a;
    color: #1b5e20;
}

.nav-link i {
    margin-right: 10px;
    width: 20px;
    text-align: center;
}

.sidebar.collapsed .nav-link span {
    display: none;
}

.sidebar.collapsed .sidebar-header h3 {
    display: none;
}

.sidebar-dropdown-menu {
    background: #c8e6c9;
    padding-left: 30px;
    padding-top: 5px;
    padding-bottom: 5px;
    border-radius: 0 0 8px 8px;
}
.sidebar-dropdown-menu .dropdown-item {
    display: block;
    color: #388E3C;
    padding: 6px 0;
    text-decoration: none;
    font-size: 0.95em;
    transition: color 0.2s, background 0.2s;
}
.sidebar-dropdown-menu .dropdown-item:hover {
    color: #fff;
    background: #388E3C;
}

@media (max-width: 768px) {
    .sidebar {
        width: 70px;
    }
    
    .sidebar.expanded {
        width: 250px;
    }
    
    .sidebar .nav-link span {
        display: none;
    }
    
    .sidebar.expanded .nav-link span {
        display: inline;
    }
    
    .sidebar .sidebar-header h3 {
        display: none;
    }
    
    .sidebar.expanded .sidebar-header h3 {
        display: block;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    
    toggleBtn.addEventListener('click', function() {
        sidebar.classList.toggle('collapsed');
        if (window.innerWidth <= 768) {
            sidebar.classList.toggle('expanded');
        }
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth <= 768) {
            sidebar.classList.remove('collapsed');
            sidebar.classList.remove('expanded');
        }
    });

    // Sidebar dropdown toggle
    const reportsDropdownToggle = document.getElementById('reportsDropdownToggle');
    const reportsDropdownMenu = document.getElementById('reportsDropdownMenu');
    if (reportsDropdownToggle && reportsDropdownMenu) {
        reportsDropdownToggle.addEventListener('click', function(e) {
            e.preventDefault();
            reportsDropdownMenu.style.display = reportsDropdownMenu.style.display === 'block' ? 'none' : 'block';
        });
        // Optional: close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!reportsDropdownToggle.contains(e.target) && !reportsDropdownMenu.contains(e.target)) {
                reportsDropdownMenu.style.display = 'none';
            }
        });
    }
});
</script>
<!-- Bootstrap 4 (example CDN setup) -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
