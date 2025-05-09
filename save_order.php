<?php
header('Content-Type: application/json');

// Database connection configuration
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "bauapp_db";

// Function to establish database connection with retry
function connectDB($host, $user, $pass, $dbname, $max_retries = 3) {
    $retries = 0;
    while ($retries < $max_retries) {
        try {
            $conn = new mysqli($host, $user, $pass, $dbname);
            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error);
            }
            return $conn;
        } catch (Exception $e) {
            $retries++;
            if ($retries == $max_retries) {
                die("Database connection failed after $max_retries attempts. Please check if MySQL is running and try again.");
            }
            sleep(1); // Wait 1 second before retrying
        }
    }
}

// Get JSON data from request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['items']) || !isset($data['total'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid order data']);
    exit;
}

// Try to establish database connection
try {
    $conn = connectDB($db_host, $db_user, $db_pass, $db_name);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection error: ' . $e->getMessage()]);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Insert order with status
    $status = 'pending'; // Default status for new orders
    $order_sql = "INSERT INTO orders (total_amount, order_date, status) VALUES (?, NOW(), ?)";
    $stmt = $conn->prepare($order_sql);
    $stmt->bind_param("ds", $data['total'], $status);
    $stmt->execute();
    
    // Get the order ID
    $order_id = $conn->insert_id;

    // Insert order items
    $item_sql = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($item_sql);

    foreach ($data['items'] as $item) {
        $stmt->bind_param("iiid", 
            $order_id,
            $item['id'],
            $item['quantity'],
            $item['price']
        );
        $stmt->execute();
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Order saved successfully',
        'order_id' => $order_id
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Error saving order: ' . $e->getMessage()
    ]);
}

$stmt->close();
$conn->close();
?> 