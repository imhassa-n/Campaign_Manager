<?php
session_start();

if(!isset($_SESSION['user']))
{
    header("Location: login.php");
    exit;
}

include 'db.php';

if(isset($_GET['id']))
{
    $id = $_GET['id'];
    mysqli_query($conn,"DELETE FROM services WHERE id='$id' AND service_type = 'Monthly Service Retainer'");
}

header("Location: retainers.php");
exit;
?>
