<?php

include 'db.php';

$id = $_GET['id'];

mysqli_query($conn,"
DELETE FROM expenses
WHERE id='$id'
");

header("Location: expenses.php");
exit;

?>