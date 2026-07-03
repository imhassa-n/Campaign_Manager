<?php
$c = mysqli_connect('localhost','root','','campaign_manager');

if(!$c) {
    die("Database Connection Failed");
}

// Update clients table
mysqli_query($c, "ALTER TABLE clients ADD COLUMN notes TEXT DEFAULT NULL");
mysqli_query($c, "ALTER TABLE clients ADD COLUMN tag VARCHAR(50) DEFAULT 'Active'");

// Create client_activity_log table
$sql_activity = "
CREATE TABLE IF NOT EXISTS `client_activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";
mysqli_query($c, $sql_activity);

echo "Database updated for clients features!";
?>
