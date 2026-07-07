<?php
include 'db.php';
mysqli_query($conn, "ALTER TABLE clients MODIFY COLUMN image LONGTEXT NULL");
echo mysqli_error($conn);
echo "Column image changed to LONGTEXT.";
?>
