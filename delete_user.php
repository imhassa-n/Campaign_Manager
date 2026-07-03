<?php
session_start();
if(!isset($_SESSION['user'])) { header("Location: login.php"); exit; }
include 'db.php';
require_once 'auth.php';

if(($_SESSION['role'] ?? '') !== 'Admin') {
    $_SESSION['access_denied'] = "Only Admins can delete users.";
    header("Location: dashboard.php"); exit;
}

$id = (int)$_GET['id'];

// Prevent admin from deleting themselves
$check = mysqli_query($conn, "SELECT email FROM users WHERE id='$id'");
$target = mysqli_fetch_assoc($check);

if($target && $target['email'] === $_SESSION['user']) {
    header("Location: users.php?error=self");
    exit;
}

mysqli_query($conn, "DELETE FROM users WHERE id='$id'");
header("Location: users.php");
exit;
?>
