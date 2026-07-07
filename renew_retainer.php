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
    
    // Get service details
    $result = mysqli_query($conn, "SELECT * FROM services WHERE id='$id' AND service_type = 'Monthly Service Retainer'");
    $service = mysqli_fetch_assoc($result);
    
    if($service) {
        $client_id = $service['client_id'];
        $budget = floatval($service['budget']);
        $current_due_date = $service['payment_due_date'];
        
        // Calculate remaining amount for current cycle
        if($current_due_date && $current_due_date != '0000-00-00') {
            $cycle_start = date('Y-m-d', strtotime('-1 month', strtotime($current_due_date)));
        } else {
            $cycle_start = $service['start_date'];
        }
        $received_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(amount),0) as total FROM payments WHERE service_id='$id' AND payment_date >= '$cycle_start'"));
        $total_received = floatval($received_res['total']);
        $remaining = max(0, $budget - $total_received);
        
        // Log the payment only if there is a remaining amount
        if($remaining > 0) {
            $payment_date = date("Y-m-d");
            mysqli_query($conn, "INSERT INTO payments (client_id, service_id, amount, payment_date) VALUES ('$client_id', '$id', '$remaining', '$payment_date')");
        }
        
        // Push the next billing date by 1 month
        if($current_due_date && $current_due_date != '0000-00-00') {
            $next_due_date = date('Y-m-d', strtotime('+1 month', strtotime($current_due_date)));
        } else {
            // Fallback if due date was somehow empty, base it on today
            $next_due_date = date('Y-m-d', strtotime('+1 month'));
        }
        
        mysqli_query($conn, "UPDATE services SET payment_due_date = '$next_due_date' WHERE id='$id'");
    }
}

header("Location: retainers.php");
exit;
?>
