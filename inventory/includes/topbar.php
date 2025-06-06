<?php
// Topbar component
?>
<div class="topbar">
<div class="topbar-left">
        <div class="search-box">
            <input type="text" placeholder="Search...">
            <i class="fas fa-search"></i>
        </div>
    </div>
    
    <div class="topbar-right">
        <div class="user-profile">
            <img src="https://via.placeholder.com/40" alt="User Profile">
            <span class="user-name">John Doe</span>
            <i class="fas fa-chevron-down"></i>
        </div>
    </div>
</div>

<style>
.topbar {
    position: fixed;
    top: 0;
    right: 0;
    left: 250px;
    height: 60px;
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 20px;
    z-index: 999;
    transition: all 0.3s ease;
}

.sidebar.collapsed ~ .topbar {
    left: 70px;
}

.topbar-left {
    flex: 1;
    max-width: 400px;
}

.search-box {
    position: relative;
    width: 100%;
}

.search-box input {
    width: 100%;
    padding: 8px 35px 8px 15px;
    border: 1px solid #ddd;
    border-radius: 20px;
    outline: none;
}

.search-box i {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #666;
}

.topbar-right {
    display: flex;
    align-items: center;
    gap: 20px;
}

.notifications {
    position: relative;
    cursor: pointer;
}

.notifications i {
    font-size: 1.2rem;
    color: #666;
}

.badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #e74c3c;
    color: white;
    border-radius: 50%;
    padding: 2px 6px;
    font-size: 0.7rem;
}

.user-profile {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
}

.user-profile img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.user-name {
    font-weight: 500;
}

@media (max-width: 768px) {
    .topbar {
        left: 70px;
    }
    
    .sidebar.expanded ~ .topbar {
        left: 250px;
    }
    
    .user-name {
        display: none;
    }
    
    .search-box {
        max-width: 200px;
    }
}
</style>
