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

            <li class="nav-item">
                <a href="stock_management.php" class="nav-link">
                    <i class="fas fa-boxes"></i>
                    <span>Stock Management</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="reports.php" class="nav-link">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="settings.php" class="nav-link">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
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
    background:rgb(193, 232, 137);
    color: black;
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
}

.toggle-btn {
    background: none;
    border: none;
    color: black;
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
    color: black;
    text-decoration: none;
    transition: all 0.3s ease;
}

.nav-link:hover {
    background: rgba(84, 197, 89, 0.51);
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
});
</script>
