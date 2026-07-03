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

$result = mysqli_query($conn,"
SELECT * FROM services
WHERE id='$id' AND service_type = 'Monthly Service Retainer'
");

$service = mysqli_fetch_assoc($result);

if(!$service) {
    header("Location: retainers.php");
    exit;
}

if(isset($_POST['update']))
{
    $client_id = $_POST['client_id'];
    $service_name = $_POST['service_name'];
    $status = $_POST['status'];
    $start_date = $_POST['start_date'];

    $finance_update = "";
    if(can('payments')) {
        $budget = isset($_POST['budget']) ? $_POST['budget'] : 0;
        $payment_due_date = !empty($_POST['payment_due_date']) ? "'".$_POST['payment_due_date']."'" : "NULL"; // Next billing date
        $finance_update = "budget='$budget', payment_due_date=$payment_due_date,";
    }

    mysqli_query($conn,"
    UPDATE services
    SET
    $finance_update
    client_id='$client_id',
    service_name='$service_name',
    status='$status',
    start_date='$start_date'
    WHERE id='$id'
    ");

    header("Location: retainers.php");
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
            <h1>Edit Monthly Client</h1>
            <p>Update monthly client details</p>
        </div>
    </div>
    <div class="topbar-right">
        <a href="retainers.php" class="btn-brand-outline">
            <i class="bi bi-arrow-left"></i>
            Back to Monthly Clients
        </a>
    </div>
</div>

<!-- Content -->
<div class="content-wrapper">

    <div class="page-card">
        <div class="page-card-header">
            <h2><i class="bi bi-pencil-square"></i> Edit Monthly Client</h2>
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
                            <label class="form-label">Service / Package Name</label>
                            <input type="text"
                                   name="service_name"
                                   class="form-control"
                                   value="<?php echo $service['service_name']; ?>">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <?php if(can('payments')): ?>
                    <div class="col-md-4">
                        <div class="form-section">
                            <label class="form-label">Monthly Fee (Rs)</label>
                            <input type="number"
                                   name="budget"
                                   class="form-control"
                                   value="<?php echo $service['budget']; ?>">
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-4">
                        <div class="form-section">
                            <label class="form-label">Start Date</label>
                            <input type="date"
                                   name="start_date"
                                   class="form-control"
                                   value="<?php echo $service['start_date']; ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-section">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control form-select">
                                <option <?php if($service['status']=='Active') echo 'selected'; ?>>
                                    Active
                                </option>
                                <option <?php if($service['status']=='Paused') echo 'selected'; ?>>
                                    Paused
                                </option>
                                <option <?php if($service['status']=='Completed') echo 'selected'; ?>>
                                    Completed
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <?php if(can('payments')): ?>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-section">
                            <label class="form-label" style="color:var(--navy-800);"><strong>Next Billing Date</strong></label>
                            <input type="date"
                                   name="payment_due_date"
                                   class="form-control"
                                   value="<?php echo $service['payment_due_date']; ?>">
                            <small style="color:var(--gray-500)">Manually overriding this will change when the next reminder appears.</small>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div style="display: flex; gap: 12px; margin-top: 8px;">
                    <button type="submit" name="update" class="btn-brand">
                        <i class="bi bi-check-circle-fill"></i>
                        Update Client
                    </button>
                    <a href="retainers.php" class="btn-back">
                        <i class="bi bi-x-circle"></i>
                        Cancel
                    </a>
                </div>

            </form>
        </div>
    </div>

</div>

<?php include 'footer.php'; ?>
