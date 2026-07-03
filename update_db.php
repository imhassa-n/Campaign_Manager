<?php
$c = mysqli_connect('localhost','root','','campaign_manager');
mysqli_query($c, "ALTER TABLE payments ADD COLUMN payment_date DATE DEFAULT NULL");
mysqli_query($c, "ALTER TABLE campaigns ADD COLUMN billing_cycle VARCHAR(50) DEFAULT 'One-time'");
echo "Done";
?>
