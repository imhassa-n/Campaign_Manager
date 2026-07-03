<?php
$c = mysqli_connect('localhost','root','','campaign_manager');

// Add role column to users
mysqli_query($c, "ALTER TABLE users ADD COLUMN role VARCHAR(50) DEFAULT 'Admin'");

// Set all existing users to Admin
mysqli_query($c, "UPDATE users SET role='Admin' WHERE role IS NULL OR role=''");

echo "Done! All existing users set to Admin role.";
?>
