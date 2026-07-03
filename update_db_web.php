<?php
$c = mysqli_connect('localhost','root','','campaign_manager');
mysqli_query($c, "ALTER TABLE services ADD COLUMN advance_amount DECIMAL(10,2) DEFAULT 0");
echo "Done";
?>
