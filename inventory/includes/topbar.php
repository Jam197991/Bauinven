<?php 
// Check if user is logged in and get user info
$fullname = "Guest User";
$profile_image = "images/default-avatar.png";

// Check if staff_id exists in session
if (isset($_SESSION['staff_id']) && !empty($_SESSION['staff_id'])) {
    $query = "SELECT * FROM inventory_staff WHERE staff_id = " . intval($_SESSION['staff_id']);
    $rs = $conn->query($query);
    if ($rs && $rs->num_rows > 0) {
        $rows = $rs->fetch_assoc();
        $fullname = isset($rows['firstname']) && isset($rows['lastname']) 
                   ? $rows['firstname'] . " " . $rows['lastname'] 
                   : "Staff User";
        $profile_image = isset($rows['profile_image']) ? $rows['profile_image'] : "images/default-avatar.png";
    }
} elseif (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    // Fallback to regular users table
    $query = "SELECT * FROM users WHERE user_id = " . intval($_SESSION['user_id']);
    $rs = $conn->query($query);
    if ($rs && $rs->num_rows > 0) {
        $rows = $rs->fetch_assoc();
        $fullname = isset($rows['username']) ? $rows['username'] : "User";
        $profile_image = isset($rows['profile_image']) ? $rows['profile_image'] : "images/default-avatar.png";
    }
}

// Ensure profile_image is set
if (!isset($profile_image) || empty($profile_image)) {
    $profile_image = "images/default-avatar.png";
}
?>

<div class="topbar">
    <div class="topbar-left">
        <div class="page-title">
            <?php echo isset($page_title) ? htmlspecialchars($page_title) : ''; ?>
        </div>
    </div>
    
    <div class="topbar-right">
        <div class="user-profile" onclick="toggleDropdown()">
            <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="User Profile">
            <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars($fullname); ?></span>
            </div>
            <i class="fas fa-chevron-down dropdown-arrow"></i>
        </div>
        
        <div class="dropdown-menu" id="dropdownMenu">
            <div class="dropdown-header">
                <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="User Profile">
                <div>
                    <div class="dropdown-name"><?php echo htmlspecialchars($fullname); ?></div>
                </div>
            </div>
            <div class="dropdown-divider"></div>
            <a href="../profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
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
    background: linear-gradient(90deg, #4CAF50 0%, #388E3C 100%);
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

.page-title {
    font-size: 1.2rem;
    font-weight: 600;
    color: #fff;
}

.topbar-right {
    display: flex;
    align-items: center;
    gap: 20px;
    position: relative;
}

.user-profile {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    padding: 8px 12px;
    border-radius: 8px;
    transition: all 0.2s ease;
    position: relative;
    background: rgba(255,255,255,0.05);
}

.user-profile:hover {
    background-color: #66bb6a;
}

.user-profile img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.user-info {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.user-name {
    font-weight: 500;
    font-size: 14px;
    color: #fff;
    line-height: 1.2;
}

.user-role {
    font-size: 12px;
    color: #666;
    line-height: 1.2;
}

.dropdown-arrow {
    font-size: 12px;
    color: #c8e6c9;
    transition: transform 0.3s ease;
}

.user-profile.active .dropdown-arrow {
    transform: rotate(180deg);
}

.dropdown-menu {
    display: none;
    position: absolute;
    top: calc(100% + 5px);
    right: 0;
    background: #e8f5e9;
    border: 1px solid #a5d6a7;
    border-radius: 8px;
    min-width: 220px;
    box-shadow: 0 8px 16px rgba(76,175,80,0.15);
    z-index: 1000;
    overflow: hidden;
    transition: all 0.3s ease;
}

.dropdown-menu.show {
    display: block;
    animation: dropdownFadeIn 0.2s ease;
}

@keyframes dropdownFadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.dropdown-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 15px;
    border-bottom: 1px solid #c8e6c9;
    background-color: #c8e6c9;
}

.dropdown-header img {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.dropdown-name {
    font-weight: 500;
    font-size: 14px;
    color: #388E3C;
}

.dropdown-role {
    font-size: 12px;
    color: #666;
}

.dropdown-divider {
    height: 1px;
    background-color: #a5d6a7;
    margin: 5px 0;
}

.dropdown-menu a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    color: #388E3C;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.2s ease;
}

.dropdown-menu a:hover {
    background-color: #a5d6a7;
    color: #1b5e20;
}

.dropdown-menu a i {
    width: 16px;
    text-align: center;
    font-size: 16px;
}

@media (max-width: 768px) {
    .topbar {
        left: 70px;
        padding: 0 15px;
    }
    
    .sidebar.expanded ~ .topbar {
        left: 250px;
    }
    
    .user-info {
        display: none;
    }
    
    .dropdown-menu {
        right: -10px;
        min-width: 200px;
    }
    
    .page-title {
        font-size: 1rem;
    }
}

@media (max-width: 480px) {
    .topbar {
        left: 0;
    }
    
    .sidebar.expanded ~ .topbar {
        left: 250px;
    }
}
</style>

<script>
function toggleDropdown() {
    const dropdown = document.getElementById('dropdownMenu');
    const userProfile = document.querySelector('.user-profile');
    
    dropdown.classList.toggle('show');
    userProfile.classList.toggle('active');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('dropdownMenu');
    const userProfile = document.querySelector('.user-profile');
    
    if (!userProfile.contains(event.target)) {
        dropdown.classList.remove('show');
        userProfile.classList.remove('active');
    }
});

// Prevent dropdown from closing when clicking inside it
document.getElementById('dropdownMenu').addEventListener('click', function(event) {
    event.stopPropagation();
});
</script>