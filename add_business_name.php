<?php
$c = mysqli_connect('localhost','root','','campaign_manager');
mysqli_query($c, "ALTER TABLE leads ADD COLUMN business_name VARCHAR(255) NULL AFTER client_name");
echo mysqli_error($c);
echo "Done.";
?>
