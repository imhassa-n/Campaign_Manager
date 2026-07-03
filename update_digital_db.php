<?php
include 'db.php';

echo "<h2>Updating Database for Digital Tasks...</h2>";

// 1. Create digital_clients table
$createClients = "CREATE TABLE IF NOT EXISTS digital_clients (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    client_name VARCHAR(255) NOT NULL,
    platforms VARCHAR(255) NOT NULL,
    status VARCHAR(50) DEFAULT 'Active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";

if(mysqli_query($conn, $createClients)) {
    echo "digital_clients table created successfully.<br>";
} else {
    echo "Error creating digital_clients: " . mysqli_error($conn) . "<br>";
}

// 2. Create daily_tasks table
$createTasks = "CREATE TABLE IF NOT EXISTS daily_tasks (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    digital_client_id INT(11) NOT NULL,
    task_date DATE NOT NULL,
    status VARCHAR(50) NOT NULL,
    reason VARCHAR(255) DEFAULT NULL,
    posts_designed INT(11) DEFAULT 0,
    posts_published INT(11) DEFAULT 0,
    notes TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (digital_client_id) REFERENCES digital_clients(id) ON DELETE CASCADE
)";

if(mysqli_query($conn, $createTasks)) {
    echo "daily_tasks table created successfully.<br>";
} else {
    echo "Error creating daily_tasks: " . mysqli_error($conn) . "<br>";
}

echo "<h3>Done!</h3>";
?>
