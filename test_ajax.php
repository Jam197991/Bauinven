<?php
session_start();

// Simple test endpoint
header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'message' => 'AJAX test successful',
    'session_data' => $_SESSION,
    'timestamp' => date('Y-m-d H:i:s')
]);
?> 