<?php
session_start();

include 'includes/database.php';

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all fields']);
    exit();
}

// Debug information
error_log("Attempting login for username: " . $username);

$stmt = $conn->prepare("SELECT user_id, username, role, password FROM users WHERE username = ? AND role = 'chef'");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    
    // Debug information
    error_log("Found user: " . print_r($user, true));
    
    if ($password === $user['password']) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        echo json_encode([
            'success' => true, 
            'message' => 'Login successful',
            'redirect' => 'chef.php'
        ]);
        exit();
    } else {
        error_log("Password mismatch. Entered: " . $password . ", Stored: " . $user['password']);
        echo json_encode(['success' => false, 'message' => 'Invalid password']);
    }
} else {
    error_log("No user found with username: " . $username);
    echo json_encode(['success' => false, 'message' => 'Invalid username or role']);
}

$stmt->close();
$conn->close();
?> 