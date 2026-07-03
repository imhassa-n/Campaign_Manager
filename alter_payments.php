<?php
include 'db.php';
$check = mysqli_query($conn, "SHOW COLUMNS FROM payments LIKE 'custom_client_name'");
if(mysqli_num_rows($check) == 0) {
    mysqli_query($conn, "ALTER TABLE payments ADD COLUMN custom_client_name VARCHAR(255) NULL AFTER client_id");
    echo "Column custom_client_name added successfully.";
} else {
    echo "Column already exists.";
}
?>
