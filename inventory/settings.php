<?php
session_start();
include '../includes/database.php';

if (!isset($_SESSION['staff_id'])) {
    echo "<script>
            alert('Please log in first');
            window.location.href = '../index.php';
        </script>";
    exit();
}

$staff_id = $_SESSION['staff_id'];
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $username = trim($_POST['username']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate input
    if (empty($firstname) || empty($lastname) || empty($username)) {
        $error = "All fields are required";
    } else {
        // Check if username already exists (excluding current user)
        $check_username = $conn->prepare("SELECT staff_id FROM inventory_staff WHERE username = ? AND staff_id != ?");
        $check_username->bind_param("si", $username, $staff_id);
        $check_username->execute();
        $result = $check_username->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Username already exists";
        } else {
            // If password change is requested
            if (!empty($current_password)) {
                // Verify current password
                $verify_password = $conn->prepare("SELECT password FROM inventory_staff WHERE staff_id = ?");
                $verify_password->bind_param("i", $staff_id);
                $verify_password->execute();
                $result = $verify_password->get_result();
                $user = $result->fetch_assoc();

                if (!password_verify($current_password, $user['password'])) {
                    $error = "Current password is incorrect";
                } elseif (empty($new_password) || empty($confirm_password)) {
                    $error = "New password and confirmation are required";
                } elseif ($new_password !== $confirm_password) {
                    $error = "New passwords do not match";
                } else {
                    // Update with new password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE inventory_staff SET firstname = ?, lastname = ?, username = ?, password = ? WHERE staff_id = ?");
                    $stmt->bind_param("ssssi", $firstname, $lastname, $username, $hashed_password, $staff_id);
                }
            } else {
                // Update without changing password
                $stmt = $conn->prepare("UPDATE inventory_staff SET firstname = ?, lastname = ?, username = ? WHERE staff_id = ?");
                $stmt->bind_param("sssi", $firstname, $lastname, $username, $staff_id);
            }

            if (!isset($error)) {
                if ($stmt->execute()) {
                    $message = "Profile updated successfully";
                    // Update session username if changed
                    $_SESSION['username'] = $username;
                } else {
                    $error = "Error updating profile: " . $conn->error;
                }
            }
        }
    }
}

// Fetch current user data
$stmt = $conn->prepare("SELECT firstname, lastname, username FROM inventory_staff WHERE staff_id = ?");
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Custom CSS -->
    <link href="../img/bau.jpg" rel="icon">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: #f5f6fa;
        }

        .main-content {
            margin-left: 250px;
            padding: 80px 20px 20px;
            transition: all 0.3s ease;
        }

        .sidebar.collapsed ~ .main-content {
            margin-left: 70px;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 70px;
            }

            .sidebar.expanded ~ .main-content {
                margin-left: 250px;
            }
        }

        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .card-title {
            font-size: 1.1rem;
            color: #2c3e50;
        }

        .card-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .bg-primary { background: #3498db; color: white; }
        .bg-success { background: #2ecc71; color: white; }
        .bg-warning { background: #f1c40f; color: white; }
        .bg-danger { background: #e74c3c; color: white; }

        /* Settings Form Styles */
        .settings-form {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #2c3e50;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3498db;
        }

        .btn-update {
            background: #3498db;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s ease;
        }

        .btn-update:hover {
            background: #2980b9;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .password-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/topbar.php'; ?>

    <div class="main-content">
        <div class="settings-form">
            <h2 style="margin-bottom: 30px; color: #2c3e50;">Profile Settings</h2>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="firstname">First Name</label>
                    <input type="text" id="firstname" name="firstname" value="<?php echo htmlspecialchars($user_data['firstname']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="lastname">Last Name</label>
                    <input type="text" id="lastname" name="lastname" value="<?php echo htmlspecialchars($user_data['lastname']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user_data['username']); ?>" required>
                </div>

                <div class="password-section">
                    <h3 style="margin-bottom: 20px; color: #2c3e50;">Change Password</h3>
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password">
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password">
                    </div>
                </div>

                <button type="submit" class="btn-update">Update Profile</button>
            </form>
        </div>
    </div>
</body>
</html>