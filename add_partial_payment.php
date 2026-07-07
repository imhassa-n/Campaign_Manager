<?php

session_start();

if(!isset($_SESSION['user']))
{
    header("Location: login.php");
    exit;
}

include 'db.php';

if(isset($_POST['save_partial']))
{
    $service_id = $_POST['service_id'];
    $amount = $_POST['amount'];
    $payment_method = $_POST['payment_method'];
    $notes = !empty($_POST['notes']) ? mysqli_real_escape_string($conn, $_POST['notes']) : '';
    $payment_date = date('Y-m-d');
    
    // Get service details
    $service = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM services WHERE id='$service_id'"));
    
    if($service) {
        $client_id = $service['client_id'];
        
        // Save payment
        mysqli_query($conn, "INSERT INTO payments (client_id, service_id, amount, payment_date, payment_method, notes) VALUES ('$client_id', '$service_id', '$amount', '$payment_date', '$payment_method', '$notes')");
        
        // Check if total received >= budget (auto-renew)
        $total_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(amount),0) as total FROM payments WHERE service_id='$service_id' AND payment_date >= '".$service['payment_due_date']."'"));
        
        // Calculate total received for current billing cycle
        $current_due = $service['payment_due_date'];
        if($current_due && $current_due != '0000-00-00') {
            $cycle_start = date('Y-m-d', strtotime('-1 month', strtotime($current_due)));
        } else {
            $cycle_start = $service['start_date'];
        }
        
        $cycle_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(amount),0) as total FROM payments WHERE service_id='$service_id' AND payment_date >= '$cycle_start'"));
        
        if($cycle_total['total'] >= $service['budget']) {
            // Auto-renew: push billing date to next month
            if($current_due && $current_due != '0000-00-00') {
                $next_due = date('Y-m-d', strtotime('+1 month', strtotime($current_due)));
            } else {
                $next_due = date('Y-m-d', strtotime('+1 month'));
            }
            mysqli_query($conn, "UPDATE services SET payment_due_date = '$next_due' WHERE id='$service_id'");
        }
    }
}

header("Location: retainers.php");
exit;
?>
