<?php

session_start();

if(!isset($_SESSION['user']))
{
    header("Location: login.php");
    exit;
}

include 'db.php';
require_once 'auth.php';

if(!isset($_GET['id'])) {
    header("Location: leads.php");
    exit;
}

$id = mysqli_real_escape_string($conn, $_GET['id']);
$res = mysqli_query($conn, "SELECT * FROM leads WHERE id='$id'");
$lead = mysqli_fetch_assoc($res);

if(!$lead) {
    header("Location: leads.php");
    exit;
}

if(isset($_POST['update']))
{
    $client_name = mysqli_real_escape_string($conn, $_POST['client_name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $service_interest = mysqli_real_escape_string($conn, $_POST['service_interest']);
    $action_type = mysqli_real_escape_string($conn, $_POST['action_type']);
    $followup_date = mysqli_real_escape_string($conn, $_POST['followup_date']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);

    mysqli_query($conn,"
    UPDATE leads SET 
        client_name='$client_name', 
        phone='$phone', 
        service_interest='$service_interest', 
        action_type='$action_type', 
        followup_date='$followup_date', 
        notes='$notes'
    WHERE id='$id'
    ");

    header("Location: leads.php");
    exit;
}

?>

<?php include 'header.php'; ?>

<!-- Top Bar -->
<div class="topbar">
    <div class="topbar-left">
        <a href="leads.php" class="btn-back" style="margin-right:15px; text-decoration:none; color:var(--gray-600); font-size:20px;">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div class="topbar-title">
            <h1>Edit Follow-up</h1>
            <p>Update follow-up details</p>
        </div>
    </div>
</div>

<!-- Content -->
<div class="content-wrapper">
    <div class="page-card" style="max-width: 800px; margin: 0 auto;">
        <div class="page-card-header">
            <h2><i class="bi bi-pencil-square"></i> Edit <?php echo htmlspecialchars($lead['client_name']); ?></h2>
        </div>
        <div class="page-card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-section">
                            <label class="form-label">Client Name</label>
                            <input type="text" name="client_name" class="form-control" value="<?php echo htmlspecialchars($lead['client_name']); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-section">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($lead['phone']); ?>" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-section">
                            <label class="form-label">Service Interest</label>
                            <input type="text" name="service_interest" class="form-control" value="<?php echo htmlspecialchars($lead['service_interest']); ?>" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-section">
                            <label class="form-label">Next Action</label>
                            <select name="action_type" class="form-control form-select">
                                <option <?php if($lead['action_type']=='Call Pending') echo 'selected'; ?>>Call Pending</option>
                                <option <?php if($lead['action_type']=='Meeting Scheduled') echo 'selected'; ?>>Meeting Scheduled</option>
                                <option <?php if($lead['action_type']=='Message Follow-up') echo 'selected'; ?>>Message Follow-up</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-section">
                            <label class="form-label">Follow-up Date</label>
                            <input type="date" name="followup_date" class="form-control" value="<?php echo htmlspecialchars($lead['followup_date']); ?>" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-section">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($lead['notes']); ?></textarea>
                        </div>
                    </div>
                </div>

                <hr style="border-color: var(--gray-200); margin: 24px 0;">

                <div style="display: flex; gap: 12px;">
                    <button type="submit" name="update" class="btn-brand">
                        <i class="bi bi-check-circle-fill"></i>
                        Update Follow-up
                    </button>
                    <a href="leads.php" class="btn-back" style="display: flex; align-items: center; justify-content: center; text-decoration: none; padding: 0 20px; border-radius: 8px; font-weight: 500; font-size: 14px; color: var(--gray-700); background: var(--gray-100);">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
