<?php
session_start();
include 'includes/database.php';
include 'includes/audit.php';   

logAudit($conn, $_SESSION['staff_id'], "Logout", "logout.php");

session_destroy();
header("Location: index.php");
exit();
?> 