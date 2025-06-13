<?php
session_start();

// Check if config file exists
if (!file_exists('includes/config.php')) {
    die('Configuration file not found. Please check the file path.');
}
    
require_once 'includes/config.php';

// Check if connection variable exists
if (!isset($connection)) {
    die('Database connection not established. Please check your config.php file.');
}

// Check if connection is valid
if ($connection->connect_error) {
    die('Database connection failed: ' . $connection->connect_error);
}

$error = ''; // Initialize error variable

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $ip = $_SERVER['REMOTE_ADDR'];
    $now = date('Y-m-d H:i:s');
    $max_attempts = 5;

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $sql = "SELECT * FROM inventory_staff WHERE username = ? AND password = ? LIMIT 1";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("ss", $username, $password);
            $stmt->execute();
            $result = $stmt->get_result();  
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $_SESSION['staff_id'] = $row['staff_id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];
    
                // Redirect to inventory dashboard
                    header('Location: inventory/dashboard.php');
                exit();
            } else {
                $error = 'Invalid username or password. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Staff Login - BauApp</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="img/bau.jpg" rel="icon">
    <style>
        :root {
            --primary-color: #FF9800;
            --primary-dark: #F57C00;
            --text-color: #333;
            --bg-color: #f5f5f5;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --hover-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
            --transition-speed: 0.3s;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-color);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            width: 100%;
            max-width: 400px;
            margin: 20px;
            position: relative;
            overflow: hidden;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: var(--primary-color);
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: var(--text-color);
            font-size: 1.8rem;
            margin-bottom: 10px;
        }

        .login-header p {
            color: #666;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            transition: color var(--transition-speed);
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: all var(--transition-speed);
        }

        .form-group input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(255, 152, 0, 0.1);
            outline: none;
        }

        .form-group input:focus + i {
            color: var(--primary-color);
        }

        .login-btn {
            width: 100%;
            padding: 12px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: all var(--transition-speed);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .login-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
            transition: all var(--transition-speed);
        }

        .back-link:hover {
            color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .debug-info {
            background: #e3f2fd;
            color: #1976d2;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 0.8rem;
            font-family: monospace;
        }

        @media (max-width: 480px) {
            .login-container {
                margin: 15px;
                padding: 30px 20px;
            }

            .login-header h1 {
                font-size: 1.5rem;
            }

            .form-group input {
                padding: 10px 15px 10px 40px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Inventory Staff Login</h1>
            <p>Please enter your credentials to continue</p>
        </div>

        <?php if ($error): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <input type="text" name="username" placeholder="Username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                <i class="fas fa-user"></i>
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="Password" required>
                <i class="fas fa-lock"></i>
            </div>
            <button type="submit" class="login-btn">
                <i class="fas fa-sign-in-alt"></i>
                Login
            </button>
        </form>

        <a href="index.php" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Back to Home
        </a>

        
    </div>
</body>
</html>