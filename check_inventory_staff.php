<?php
include 'includes/database.php';

// Check if inventory_staff table exists
$check_table = "SHOW TABLES LIKE 'inventory_staff'";
$result = $conn->query($check_table);

if ($result->num_rows == 0) {
    // Create inventory_staff table
    $create_table = "
    CREATE TABLE IF NOT EXISTS `inventory_staff` (
      `staff_id` int(11) NOT NULL AUTO_INCREMENT,
      `username` varchar(50) NOT NULL,
      `password` varchar(50) NOT NULL,
      `firstname` varchar(50) DEFAULT NULL,
      `lastname` varchar(50) DEFAULT NULL,
      `role` enum('admin','staff') NOT NULL,
      `profile_image` varchar(255) DEFAULT 'images/default-avatar.png',
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`staff_id`),
      UNIQUE KEY `username` (`username`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    
    if ($conn->query($create_table) === TRUE) {
        echo "Inventory staff table created successfully.<br>";
        
        // Insert default admin staff
        $insert_admin = "
        INSERT INTO `inventory_staff` (`username`, `password`, `firstname`, `lastname`, `role`) VALUES
        ('adminstaff', 'admin', 'Admin', 'Staff', 'admin')
        ON DUPLICATE KEY UPDATE `password` = VALUES(`password`);
        ";
        
        if ($conn->query($insert_admin) === TRUE) {
            echo "Default admin staff created successfully.<br>";
        } else {
            echo "Error creating default admin staff: " . $conn->error . "<br>";
        }
    } else {
        echo "Error creating inventory staff table: " . $conn->error . "<br>";
    }
} else {
    echo "Inventory staff table already exists.<br>";
}

// Check table structure
$describe_table = "DESCRIBE inventory_staff";
$result = $conn->query($describe_table);

if ($result) {
    echo "<br>Table structure:<br>";
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "<br>";
    }
}

$conn->close();
echo "<br>Check completed!";
?> 