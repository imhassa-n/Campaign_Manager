<?php
$conn = mysqli_connect('localhost', 'root', '', 'campaign_manager');
if (!$conn) die("Connection failed: " . mysqli_connect_error());

$sql = "ALTER TABLE users ADD extra_permissions TEXT NULL";
if (mysqli_query($conn, $sql)) {
    echo "Column extra_permissions added successfully.";
} else {
    echo "Error adding column: " . mysqli_error($conn);
}
?>
