<?php
include '../includes/database.php';

$filter_start = isset($_GET['filter_start']) ? $_GET['filter_start'] : '';
$filter_end = isset($_GET['filter_end']) ? $_GET['filter_end'] : '';
$date_filter = '';
if ($filter_start && $filter_end) {
    $date_filter = "WHERE DATE(sm.movement_date) BETWEEN '" . $conn->real_escape_string($filter_start) . "' AND '" . $conn->real_escape_string($filter_end) . "'";
} elseif ($filter_start) {
    $date_filter = "WHERE DATE(sm.movement_date) >= '" . $conn->real_escape_string($filter_start) . "'";
} elseif ($filter_end) {
    $date_filter = "WHERE DATE(sm.movement_date) <= '" . $conn->real_escape_string($filter_end) . "'";
}

$sql = "SELECT sm.*, p.product_name, s.supplier_name 
        FROM stock_movements sm 
        LEFT JOIN products p ON sm.product_id = p.product_id 
        LEFT JOIN suppliers s ON sm.supplier_id = s.supplier_id 
        $date_filter
        ORDER BY sm.movement_date DESC";
$result = $conn->query($sql);

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="stock_movements_report.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Product', 'Movement Type', 'Quantity', 'Unit Price', 'Total Amount', 'Supplier', 'Movement Date', 'Notes']);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['product_name'],
        $row['movement_type'],
        $row['quantity'],
        $row['unit_price'],
        $row['total_amount'],
        $row['supplier_name'],
        $row['movement_date'],
        $row['notes']
    ]);
}
fclose($output);
exit; 