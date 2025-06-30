<?php
session_start();

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
                // In a real app, you might want to log this error instead of dying
                die("Database connection failed after $max_retries attempts. Please check if MySQL is running and try again.");
            }
            sleep(1); // Wait 1 second before retrying
        }
    }
}

// Try to establish database connection
try {
    $conn = connectDB($db_host, $db_user, $db_pass, $db_name);
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}


if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    header("Location: admin_orders.php?error=Invalid request.");
    exit();
}

$order_id = intval($_GET['order_id']);

// Start transaction
$conn->begin_transaction();

try {
    // 1. Fetch the order and lock the row
    $stmt = $conn->prepare("SELECT status FROM orders WHERE order_id = ? FOR UPDATE");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception("Order not found.");
    }
    $order = $result->fetch_assoc();
    $stmt->close();

    // 2. Check if order can be cancelled
    if ($order['status'] === 'completed' || $order['status'] === 'cancelled') {
        throw new Exception("Orders is already " . $order['status'] . " and cannot be cancelled.");
    }

    // 3. Get item counts for the order
    $item_sql = "SELECT product_id, COUNT(item_id) AS quantity FROM order_items WHERE order_id = ? GROUP BY product_id";
    $stmt = $conn->prepare($item_sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $items_result = $stmt->get_result();
    $stmt->close();

    // 4. Restore quantities to inventory
    $update_inventory_stmt = $conn->prepare("UPDATE inventory SET quantity = quantity + ?, updated_at = NOW() WHERE product_id = ?");
    while ($item = $items_result->fetch_assoc()) {
        $update_inventory_stmt->bind_param("ii", $item['quantity'], $item['product_id']);
        if (!$update_inventory_stmt->execute()) {
            throw new Exception("Failed to restock product ID " . $item['product_id']);
        }
    }
    $update_inventory_stmt->close();

    // 5. Update order status to 'cancelled'
    $update_order_stmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE order_id = ?");
    $update_order_stmt->bind_param("i", $order_id);
    if (!$update_order_stmt->execute()) {
        throw new Exception("Failed to cancel the order.");
    }
    $update_order_stmt->close();

    // Commit the transaction
    $conn->commit();
    
    // Redirect with success message
    header("Location: admin_orders.php?success=" . urlencode("Order #" . $order_id . " has been cancelled successfully."));
    exit();

} catch (Exception $e) {
    // An error occurred, rollback
    $conn->rollback();
    
    // Redirect with error message
    header("Location: admin_orders.php?error=" . urlencode($e->getMessage()));
    exit();
} finally {
    $conn->close();
}
?> 