<?php
session_start();
if(!isset($_SESSION['user'])) { header("Location: login.php"); exit; }
include 'db.php';
require_once 'auth.php';

if(isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    mysqli_query($conn, "DELETE FROM leads WHERE id='$id'");
}

$ref = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'leads.php';
header("Location: $ref");
exit;
?>
