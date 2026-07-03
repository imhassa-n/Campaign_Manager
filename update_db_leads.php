<?php
$c = mysqli_connect('localhost','root','','campaign_manager');
mysqli_query($c, "ALTER TABLE leads ADD COLUMN status VARCHAR(50) DEFAULT 'Active'");
echo "Done - status column added to leads table.";
?>
