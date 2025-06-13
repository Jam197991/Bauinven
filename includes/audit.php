<?php
function logAudit($conn, $user_id, $action, $page, $user_type = 'staff') {
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    // Use different column names based on user type
    $user_id_column = ($user_type == 'staff') ? 'staff_id' : 'user_id';
    
    $stmt = $conn->prepare("INSERT INTO audit_logs ($user_id_column, action, page, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $action, $page, $ip, $user_agent);
    $stmt->execute();
    $stmt->close();
}
?>
