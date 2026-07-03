<?php

session_start();

if(!isset($_SESSION['user']))
{
    header("Location: login.php");
    exit;
}

include 'db.php';

$id = $_GET['id'];

$result = mysqli_query($conn,"
SELECT * FROM services
WHERE id='$id' AND service_type = 'Website Development'
");

$service = mysqli_fetch_assoc($result);

if(!$service) {
    header("Location: web_projects.php");
    exit;
}

if(isset($_POST['update']))
{
    $client_id = $_POST['client_id'];
    $service_name = $_POST['service_name'];
    $status = $_POST['status']; // Not Started, In Progress, Delivered
    
    $finance_update = "";
    if(can('payments')) {
        $budget = isset($_POST['budget']) ? $_POST['budget'] : 0;
        $advance_amount = !empty($_POST['advance_amount']) ? floatval($_POST['advance_amount']) : 0;
        $payment_status = isset($_POST['payment_cleared']) ? 'Cleared' : 'Pending';
        $reminder_date_val = !empty($_POST['reminder_date']) ? $_POST['reminder_date'] : '';
        $payment_due_date = $reminder_date_val;
        $reminder_date = !empty($reminder_date_val) ? "'".$reminder_date_val."'" : "NULL";
        
        $finance_update = "
        budget='$budget',
        payment_due_date='$payment_due_date',
        payment_status='$payment_status',
        reminder_date=$reminder_date,
        advance_amount='$advance_amount',
        ";
    }

    mysqli_query($conn,"
    UPDATE services
    SET
    $finance_update
    client_id='$client_id',
    service_name='$service_name',
    status='$status'
    WHERE id='$id'
    ");

    header("Location: web_projects.php");
    exit;
}

?>

<?php include 'header.php'; ?>

<!-- Top Bar -->
<div class="topbar">
    <div class="topbar-left">
        <button class="mobile-toggle" onclick="openSidebar()">
            <i class="bi bi-list"></i>
        </button>
        <div class="topbar-title">
            <h1>Edit Web Project</h1>
            <p>Update website development project details</p>
        </div>
    </div>
    <div class="topbar-right">
        <a href="web_projects.php" class="btn-brand-outline">
            <i class="bi bi-arrow-left"></i>
            Back to Web Projects
        </a>
    </div>
</div>

<!-- Content -->
<div class="content-wrapper">

    <div class="page-card">
        <div class="page-card-header">
            <h2><i class="bi bi-pencil-square"></i> Edit Web Project</h2>
        </div>
        <div class="page-card-body">
            <form method="POST">

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-section">
                            <label class="form-label">Select Client</label>
                            <select name="client_id" class="form-control form-select">
                                <?php
                                $clients = mysqli_query($conn,"SELECT * FROM clients");
                                while($client = mysqli_fetch_assoc($clients))
                                {
                                ?>
                                <option
                                value="<?php echo $client['id']; ?>"
                                <?php
                                if($service['client_id'] == $client['id'])
                                {
                                    echo "selected";
                                }
                                ?>
                                >
                                <?php echo $client['name']; ?>
                                </option>
                                <?php
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-section">
                            <label class="form-label">Project Name</label>
                            <input type="text"
                                   name="service_name"
                                   class="form-control"
                                   value="<?php echo $service['service_name']; ?>">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <?php if(can('payments')): ?>
                    <div class="col-md-3">
                        <div class="form-section">
                            <label class="form-label">Total Budget (Rs)</label>
                            <input type="number"
                                   name="budget"
                                   class="form-control"
                                   value="<?php echo $service['budget']; ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-section">
                            <label class="form-label">Advance Received (Rs)</label>
                            <input type="number"
                                   name="advance_amount"
                                   class="form-control"
                                   value="<?php echo $service['advance_amount']; ?>">
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-3">
                        <div class="form-section">
                            <label class="form-label">Milestone Status</label>
                            <select name="status" class="form-control form-select">
                                <option <?php if($service['status']=='Not Started') echo 'selected'; ?>>
                                    Not Started
                                </option>
                                <option <?php if($service['status']=='In Progress' || $service['status']=='Active') echo 'selected'; ?>>
                                    In Progress
                                </option>
                                <option <?php if($service['status']=='Delivered' || $service['status']=='Completed') echo 'selected'; ?>>
                                    Delivered
                                </option>
                            </select>
                        </div>
                    </div>
                    <?php if(can('payments')): ?>
                    <div class="col-md-3">
                        <div style="background: #ffffff; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 40px; height: 40px; border-radius: 10px; background: #f0fdf4; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                                        <i class="bi bi-credit-card-fill"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; font-size: 14px; color: #1e293b; margin-bottom: 2px;">Full Payment</div>
                                    </div>
                                </div>
                                <label style="position: relative; display: inline-block; width: 44px; height: 24px; margin: 0;">
                                    <input type="checkbox" name="payment_cleared" id="paymentClearedToggle" style="opacity: 0; width: 0; height: 0; position: absolute;" <?php echo ($service['payment_status'] == 'Cleared') ? 'checked' : ''; ?>>
                                    <span class="custom-slider" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; border-radius: 24px;">
                                        <span class="custom-slider-knob" style="position: absolute; content: ''; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,0.2);"></span>
                                    </span>
                                </label>
                                <style>
                                    #paymentClearedToggle:checked ~ .custom-slider { background-color: #16a34a !important; }
                                    #paymentClearedToggle:checked ~ .custom-slider .custom-slider-knob { transform: translateX(20px); }
                                </style>
                            </div>
                            
                            <div id="reminderDateContainer" style="background: #f0f9ff; padding: 12px; border-radius: 8px; border: 1px solid #bae6fd; margin-top: 12px; transition: all 0.3s ease; display: <?php echo ($service['payment_status'] == 'Cleared') ? 'none' : 'block'; ?>;">
                                <label class="form-label" style="font-size: 12px; font-weight: 600; color: #0369a1; display: flex; align-items: center; gap: 6px; margin-bottom: 8px;">
                                    <i class="bi bi-clock-history"></i> Next Payment Reminder
                                </label>
                                <input type="date" name="reminder_date" class="form-control" style="border-color: #7dd3fc; background: #ffffff; color: #475569; font-size: 13px; padding: 8px 12px; border-radius: 6px; box-shadow: none;" value="<?php echo $service['reminder_date']; ?>">
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div style="display: flex; gap: 12px; margin-top: 8px;">
                    <button type="submit" name="update" class="btn-brand">
                        <i class="bi bi-check-circle-fill"></i>
                        Update Web Project
                    </button>
                    <a href="web_projects.php" class="btn-back">
                        <i class="bi bi-x-circle"></i>
                        Cancel
                    </a>
                </div>

            </form>
        </div>
    </div>

</div>

<?php include 'footer.php'; ?>

<script>
document.getElementById('paymentClearedToggle').addEventListener('change', function() {
    const reminderContainer = document.getElementById('reminderDateContainer');
    if (this.checked) {
        reminderContainer.style.display = 'none';
        reminderContainer.querySelector('input').value = '';
    } else {
        reminderContainer.style.display = 'block';
    }
});
</script>
