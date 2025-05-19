<?php
// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in (if session variable exists)
if (isset($_SESSION['userid']) && !empty($_SESSION['userid'])) {
    // Proceed with the query only if userid exists in session
    $userid = $_SESSION['userid']; // Store in variable for safer usage
    
    // Use parameterized query for security
    $query = "SELECT * FROM user WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $rs = $stmt->get_result();
    
    if ($rs && $rs->num_rows > 0) {
        $rows = $rs->fetch_assoc();
        $fullName = $rows['firstname'] . " " . $rows['lastname'];
    } else {
        // Handle case where user isn't found
        $fullName = "Guest";
    }
} else {
    // If not logged in, set default name
    $fullName = "Guest";
    // Optionally redirect to login page
    // header("Location: ../../login.php");
    // exit;
}
?>

<nav class="navbar navbar-expand navbar-light bg-gradient-primary topbar mb-4 static-top">
    <button id="sidebarToggleTop" class="btn btn-link rounded-circle mr-3">
        <i class="fa fa-bars"></i>
    </button>
    <div class="text-white big" style="margin-left:100px;"></div>
    <ul class="navbar-nav ml-auto">
        <div class="topbar-divider d-none d-sm-block"></div>
        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown"
                aria-haspopup="true" aria-expanded="false">
                <img class="img-profile rounded-circle" src="img/user-icn.png" style="max-width: 60px">
                <span class="ml-2 d-none d-lg-inline text-white small"><b>Welcome <?php echo htmlspecialchars($fullName); ?></b></span>
            </a>
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                <!-- <a class="dropdown-item" href="#">
                  <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                  Profile
                </a>
                <a class="dropdown-item" href="#">
                  <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
                  Settings
                </a>
                <a class="dropdown-item" href="#">
                  <i class="fas fa-list fa-sm fa-fw mr-2 text-gray-400"></i>
                  Activity Log
                </a> -->
                <div class="dropdown-divider"></div>
                <?php if (isset($_SESSION['userid'])): ?>
                <a class="dropdown-item" href="../../logout.php">
                    <i class="fas fa-power-off fa-fw mr-2 text-danger"></i>
                    Logout
                </a>
                <?php else: ?>
                <a class="dropdown-item" href="../../inv_login.php">
                    <i class="fas fa-sign-in-alt fa-fw mr-2 text-primary"></i>
                    Login
                </a>
                <?php endif; ?>
            </div>
        </li>
    </ul>
</nav>