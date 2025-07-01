<?php
include '../includes/database.php';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="inventory_report.csv"');

$output = fopen('php://output', 'w');

// Helper to write a section
function write_section($output, $title, $query, $conn) {
    fputcsv($output, [$title]);
    fputcsv($output, ['Product Name', 'Category', 'Price', 'Current Stock', 'Last Updated']);
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['product_name'],
            $row['category_name'] ?? 'Uncategorized',
            $row['price'],
            $row['quantity'],
            $row['updated_at']
        ]);
    }
    fputcsv($output, []); // Blank line between sections
}

// Low Stock
$low_stock_query = "SELECT p.product_name, c.category_name, p.price, p.quantity, p.updated_at FROM products p LEFT JOIN categories c ON p.category_id = c.category_id WHERE p.quantity < 50 ORDER BY p.quantity ASC LIMIT 100";
write_section($output, 'Low Stock Products', $low_stock_query, $conn);

// Normal Stock
$normal_stock_query = "SELECT p.product_name, c.category_name, p.price, p.quantity, p.updated_at FROM products p LEFT JOIN categories c ON p.category_id = c.category_id WHERE p.quantity >= 50 AND p.quantity <= 200 ORDER BY p.quantity ASC LIMIT 100";
write_section($output, 'Normal Stock Products', $normal_stock_query, $conn);

// High Stock
$high_stock_query = "SELECT p.product_name, c.category_name, p.price, p.quantity, p.updated_at FROM products p LEFT JOIN categories c ON p.category_id = c.category_id WHERE p.quantity > 200 ORDER BY p.quantity ASC LIMIT 100";
write_section($output, 'High Stock Products', $high_stock_query, $conn);

fclose($output);
exit; 