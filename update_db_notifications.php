<?php
$c = mysqli_connect('localhost','root','','campaign_manager');

if(!$c) {
    die("Database Connection Failed");
}

$sql = "
CREATE TABLE IF NOT EXISTS `dismissed_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notification_type` varchar(100) NOT NULL,
  `reference_id` int(11) NOT NULL,
  `dismissed_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";
mysqli_query($c, $sql);

echo "Database updated for dismissed_notifications!";
?>
