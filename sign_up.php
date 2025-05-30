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
$success = ''; // Initialize success variable

//Add users
function isUsernameAvailable($username, $connection)
{
    $sql = "SELECT COUNT(*) as count FROM user WHERE username = ?";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return ($row['count'] == 0);
}

if (isset($_POST['submit'])) {
    $user_id = $_POST['user_id'];
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $username = $_POST['username'];
    $password = md5($_POST['password']);
    $role = $_POST['role'];

    if (!isUsernameAvailable($username, $connection)) {
        $error = "Username is not available. Please choose a different one.";
    } else {
        $sql = "INSERT INTO user (user_id, firstname, lastname, username, password, role) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("isssss", $user_id, $firstname, $lastname, $username, $password, $role);

        if ($stmt->execute()) {
            $success = "User registered successfully!";
            // Clear form data after successful registration
            $_POST = array();
        } else {
            $error = "Error: " . $stmt->error;
        }
    }
}

// retrieve users
$roles = ['Admin', 'Chef'];
$roles_str = "'" . implode("', '", $roles) . "'";

$select = mysqli_query($connection, "SELECT * FROM user WHERE role IN ($roles_str)");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - BauApp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2e7d32;
            --accent-color: #4caf50;
            --error-color: #dc3545;
            --success-color: #28a745;
            --text-color: #333;
            --bg-color: #f5f5f5;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            padding: 30px;
        }

        h1 {
            color: var(--text-color);
            text-align: center;
            margin-bottom: 30px;
            font-size: 2rem;
        }

        .form-container {
            margin-bottom: 40px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: var(--text-color);
            font-weight: 500;
        }

        input, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        button {
            background: var(--primary-color);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s;
        }

        button:hover {
            background: var(--accent-color);
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert.error {
            background: #f8d7da;
            color: var(--error-color);
            border: 1px solid #f5c6cb;
        }

        .alert.success {
            background: #d4edda;
            color: var(--success-color);
            border: 1px solid #c3e6cb;
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .users-table th,
        .users-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .users-table th {
            background: var(--primary-color);
            color: white;
        }

        .users-table tr:hover {
            background: #f5f5f5;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>

        <h1><i class="fas fa-user-plus"></i> User Registration</h1>

        <?php if ($error): ?>
        <div class="alert error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>

        <div class="form-container">
            <form action="sign_up.php" method="POST">
                <div class="form-group">
                    <label for="id"><i class="fas fa-id-badge"></i> User ID:</label>
                    <input type="text" id="id" name="id" required>
                </div>

                <div class="form-group">
                    <label for="firstname"><i class="fas fa-user"></i> First Name:</label>
                    <input type="text" id="firstname" name="firstname" required>
                </div>

                <div class="form-group">
                    <label for="lastname"><i class="fas fa-user"></i> Last Name:</label>
                    <input type="text" id="lastname" name="lastname" required>
                </div>

                <div class="form-group">
                    <label for="username"><i class="fas fa-at"></i> Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="role"><i class="fas fa-user-tag"></i> Role:</label>
                    <select id="role" name="role" required>
                        <option value="">Select Role</option>
                        <option value="Admin">Admin</option>
                        <option value="Chef">Chef</option>
                    </select>
                </div>

                <button type="submit" name="submit">
                    <i class="fas fa-user-plus"></i> Register User
                </button>
            </form>
        </div>

        <h2><i class="fas fa-users"></i> Existing Users</h2>
        <table class="users-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Username</th>
                    <th>Role</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($select && mysqli_num_rows($select) > 0) {
                    while ($row = mysqli_fetch_assoc($select)) {
                        echo "<tr>
                                <td>" . htmlspecialchars($row['user_id']) . "</td>
                                <td>" . htmlspecialchars($row['firstname']) . "</td>
                                <td>" . htmlspecialchars($row['lastname']) . "</td>
                                <td>" . htmlspecialchars($row['username']) . "</td>
                                <td>" . htmlspecialchars($row['role']) . "</td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='5' style='text-align: center;'>No users found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>