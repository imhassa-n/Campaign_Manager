<?php
session_start();

if(!isset($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

include 'db.php';

if(isset($_POST['type']) && isset($_POST['ref_id'])) {
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $ref_id = (int)$_POST['ref_id'];
    
    // Check if already dismissed to prevent duplicates
    $check = mysqli_query($conn, "SELECT id FROM dismissed_notifications WHERE notification_type='$type' AND reference_id='$ref_id'");
    
    if(mysqli_num_rows($check) == 0) {
        mysqli_query($conn, "INSERT INTO dismissed_notifications (notification_type, reference_id) VALUES ('$type', '$ref_id')");
    }
    
    echo json_encode(['status' => 'success']);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
?>
