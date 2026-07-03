<?php

include 'db.php';

$id = $_GET['id'];

mysqli_query($conn,"
DELETE FROM payments
WHERE id='$id'
");

header("Location: payments.php");
exit;

?>