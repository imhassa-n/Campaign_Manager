<?php
$c = mysqli_connect('localhost','root','','campaign_manager');

// Create services table
$sql_services = "
CREATE TABLE IF NOT EXISTS `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) DEFAULT NULL,
  `service_name` varchar(100) DEFAULT NULL,
  `service_type` varchar(100) DEFAULT NULL,
  `budget` decimal(10,2) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Active',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `payment_due_date` date DEFAULT NULL,
  `reminder_date` date DEFAULT NULL,
  `billing_cycle` varchar(50) DEFAULT 'One-time',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";
mysqli_query($c, $sql_services);

// Add service_id to payments
mysqli_query($c, "ALTER TABLE payments ADD COLUMN service_id INT DEFAULT NULL AFTER campaign_id");

echo "Database updated for services!";
?>
