<?php

session_start();

if(!isset($_SESSION['user']))
{
    header("Location: login.php");
    exit;
}

require_once 'auth.php';
include 'db.php';

$id = $_GET['id'];
$source = isset($_GET['source']) ? $_GET['source'] : '';

$result = mysqli_query($conn,"
SELECT * FROM campaigns
WHERE id='$id'
");

$campaign = mysqli_fetch_assoc($result);

if(isset($_POST['update']))
{
    $client_id = $_POST['client_id'];
    $campaign_name = $_POST['campaign_name'];
    $status = $_POST['status'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    $budget = isset($_POST['budget']) ? $_POST['budget'] : 0;
    $payment_status = isset($_POST['payment_cleared']) ? 'Cleared' : 'Pending';
    
    $reminder_date_val = !empty($_POST['reminder_date']) ? $_POST['reminder_date'] : '';
    $payment_due_date = !empty($reminder_date_val) ? "'".$reminder_date_val."'" : "NULL";
    $reminder_date = !empty($reminder_date_val) ? "'".$reminder_date_val."'" : "NULL";
    
    $finance_update = "
    budget='$budget',
    payment_status='$payment_status',
    payment_due_date=$payment_due_date,
    reminder_date=$reminder_date,
    ";

    mysqli_query($conn,"
    UPDATE campaigns
    SET
    $finance_update
    client_id='$client_id',
    campaign_name='$campaign_name',
    status='$status',
    start_date='$start_date',
    end_date='$end_date'
    WHERE id='$id'
    ");

    $redirect = ($source === 'history') ? 'campaign_history.php' : 'campaigns.php';
    header("Location: " . $redirect);
    exit;
}

$back_link = ($source === 'history') ? 'campaign_history.php' : 'campaigns.php';
$back_text = ($source === 'history') ? 'Back to History' : 'Back to Campaigns';

?>

<?php include 'header.php'; ?>

<!-- Top Bar -->
<div class="topbar">
    <div class="topbar-left">
        <button class="mobile-toggle" onclick="openSidebar()">
            <i class="bi bi-list"></i>
        </button>
        <div class="topbar-title">
            <h1>Edit Campaign</h1>
            <p>Update campaign details</p>
        </div>
    </div>
    <div class="topbar-right">
        <a href="<?php echo $back_link; ?>" class="btn-brand-outline">
            <i class="bi bi-arrow-left"></i>
            <?php echo $back_text; ?>
        </a>
    </div>
</div>

<!-- Content -->
<div class="content-wrapper">

    <div class="page-card">
        <div class="page-card-header">
            <h2><i class="bi bi-pencil-square"></i> Edit Campaign</h2>
        </div>
        <div class="page-card-body">
            <form method="POST" action="edit_campaign.php?id=<?php echo $id; ?><?php echo ($source === 'history') ? '&source=history' : ''; ?>">

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
                                if($campaign['client_id'] == $client['id'])
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
                            <label class="form-label">Campaign Name</label>
                            <input type="text"
                                   name="campaign_name"
                                   class="form-control"
                                   value="<?php echo $campaign['campaign_name']; ?>">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-section">
                            <label class="form-label">Budget (Rs)</label>
                            <input type="number"
                                   name="budget"
                                   class="form-control"
                                   value="<?php echo $campaign['budget']; ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-section">
                            <label class="form-label">Start Date</label>
                            <input type="date"
                                   name="start_date"
                                   class="form-control"
                                   value="<?php echo $campaign['start_date']; ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-section">
                            <label class="form-label">End Date</label>
                            <input type="date"
                                   name="end_date"
                                   class="form-control"
                                   value="<?php echo $campaign['end_date']; ?>">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-section">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control form-select">
                                <option <?php if($campaign['status']=='Active') echo 'selected'; ?>>
                                    Active
                                </option>
                                <option <?php if($campaign['status']=='Paused') echo 'selected'; ?>>
                                    Paused
                                </option>
                                <option <?php if($campaign['status']=='Completed') echo 'selected'; ?>>
                                    Completed
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div style="background: #ffffff; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 40px; height: 40px; border-radius: 10px; background: #f0fdf4; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                                        <i class="bi bi-credit-card-fill"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; font-size: 14px; color: #1e293b; margin-bottom: 2px;">Ad Payment</div>
                                        <div style="font-size: 12px; color: #64748b; font-weight: normal;">Toggle if payment is cleared</div>
                                    </div>
                                </div>
                                <label style="position: relative; display: inline-block; width: 44px; height: 24px; margin: 0;">
                                    <input type="checkbox" name="payment_cleared" id="paymentClearedToggle" style="opacity: 0; width: 0; height: 0; position: absolute;" <?php echo (isset($campaign['payment_status']) && $campaign['payment_status'] == 'Cleared') ? 'checked' : ''; ?>>
                                    <span class="custom-slider" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; border-radius: 24px;">
                                        <span class="custom-slider-knob" style="position: absolute; content: ''; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,0.2);"></span>
                                    </span>
                                </label>
                                <style>
                                    #paymentClearedToggle:checked ~ .custom-slider { background-color: #16a34a !important; }
                                    #paymentClearedToggle:checked ~ .custom-slider .custom-slider-knob { transform: translateX(20px); }
                                </style>
                            </div>
                            
                            <div id="reminderDateContainer" style="background: #f0f9ff; padding: 12px; border-radius: 8px; border: 1px solid #bae6fd; margin-top: 12px; transition: all 0.3s ease; display: <?php echo (isset($campaign['payment_status']) && $campaign['payment_status'] == 'Cleared') ? 'none' : 'block'; ?>;">
                                <label class="form-label" style="font-size: 12px; font-weight: 600; color: #0369a1; display: flex; align-items: center; gap: 6px; margin-bottom: 8px;">
                                    <i class="bi bi-clock-history"></i> Payment Reminder Date
                                </label>
                                <input type="date" name="reminder_date" class="form-control" style="border-color: #7dd3fc; background: #ffffff; color: #475569; font-size: 13px; padding: 8px 12px; border-radius: 6px; box-shadow: none;" value="<?php echo isset($campaign['reminder_date']) ? $campaign['reminder_date'] : ''; ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 12px; margin-top: 8px;">
                    <button type="submit" name="update" class="btn-brand">
                        <i class="bi bi-check-circle-fill"></i>
                        Update Campaign
                    </button>
                    <a href="<?php echo $back_link; ?>" class="btn-back">
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