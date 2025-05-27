<?php
session_start();

// Unset all inventory session variables
unset($_SESSION['inventory_staff_id']);
unset($_SESSION['inventory_username']);
unset($_SESSION['inventory_role']);

// Destroy the session
session_destroy();

// Redirect to the login page
header('Location: login.php');
exit();
?> 