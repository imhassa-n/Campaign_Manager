<?php
$c = mysqli_connect('localhost','root','','campaign_manager');
mysqli_query($c, "ALTER TABLE services ADD COLUMN payment_status ENUM('Cleared', 'Pending') DEFAULT 'Pending'");
echo mysqli_error($c);
echo "Done";
?>
