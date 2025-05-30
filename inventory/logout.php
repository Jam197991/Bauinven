<?php
session_start();

// Unset all session variables
unset($_SESSION['staff_id']);
unset($_SESSION['username']);
unset($_SESSION['role']);

// Destroy the session
session_destroy();

// Redirect to the login page
header('Location: ../login.php');
exit();
?> 