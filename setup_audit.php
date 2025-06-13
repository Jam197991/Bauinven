<?php
require_once 'includes/config.php';

// Read the SQL file
$sql = file_get_contents('database/audit_logs.sql');

// Execute the SQL commands
if ($conn->multi_query($sql)) {
    do {
        // Store first result set
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    
    echo "Audit logs table created successfully!";
} else {
    echo "Error creating audit logs table: " . $conn->error;
}
?> 