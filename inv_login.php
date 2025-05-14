<?php
// Start or resume session
session_start();
include 'includes/database.php';

if(isset($_POST['login'])) {
    $userType = $_POST['userType'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    try {
        // Set up PDO connection
        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, $user, $pass, $options);

        if($userType == "Administrator") {
            // Query for Administrator
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password = ? AND role = 'admin' AND status = 'active'");
            $stmt->execute([$username, $password]);
            $user = $stmt->fetch();

            if($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;

                // Update last login time
                $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$user['id']]);

                $response = [
                    'success' => true,
                    'message' => 'Login successful',
                    'redirect' => 'Admin/index.php'
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Invalid Username/Password!'
                ];
            }
        }
        else if($userType == "Inventory") {
            // Query for Inventory Staff
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password = ? AND role = 'inventory' AND status = 'active'");
            $stmt->execute([$username, $password]);
            $user = $stmt->fetch();

            if($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;

                // Update last login time
                $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$user['id']]);

                $response = [
                    'success' => true,
                    'message' => 'Login successful',
                    'redirect' => 'Inventory/index.php'
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Invalid Username/Password!'
                ];
            }
        } else {
            $response = [
                'success' => false,
                'message' => 'Invalid user type!'
            ];
        }

    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        $response = [
            'success' => false,
            'message' => 'An error occurred during login. Please try again.'
        ];
    }
} else {
    $response = [
        'success' => false,
        'message' => 'Invalid request method.'
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>