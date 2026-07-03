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
SELECT * FROM payments
WHERE id='$id'
");

$payment = mysqli_fetch_assoc($result);

if(isset($_POST['update']))
{
    $custom_client_name = '';
    if(isset($_POST['client_id']) && $_POST['client_id'] === 'custom') {
        $client_id = "NULL";
        $custom_client_name = mysqli_real_escape_string($conn, $_POST['custom_client_name']);
    } else {
        $client_id = !empty($_POST['client_id']) ? "'".$_POST['client_id']."'" : "NULL";
    }
    $campaign_id = !empty($_POST['campaign_id']) ? "'".$_POST['campaign_id']."'" : "NULL";
    $amount = $_POST['amount'];

    mysqli_query($conn,"
    UPDATE payments
    SET
    client_id=$client_id,
    custom_client_name='$custom_client_name',
    campaign_id=$campaign_id,
    amount='$amount'
    WHERE id='$id'
    ");

    header("Location: payments.php");
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
            <h1>Edit Payment</h1>
            <p>Update payment record</p>
        </div>
    </div>
    <div class="topbar-right">
        <a href="payments.php" class="btn-brand-outline">
            <i class="bi bi-arrow-left"></i>
            Back to Payments
        </a>
    </div>
</div>

<!-- Content -->
<div class="content-wrapper">

    <div class="page-card">
        <div class="page-card-header">
            <h2><i class="bi bi-pencil-square"></i> Edit Payment</h2>
        </div>
        <div class="page-card-body">
            <form method="POST">

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-section">
                            <label class="form-label">Select Client</label>
                            <select name="client_id" class="form-control form-select" id="clientSelect" onchange="toggleCustomClient()" required>
                                <option value="">Select a Client...</option>
                                <option value="custom" <?php if(empty($payment['client_id']) && !empty($payment['custom_client_name'])) echo "selected"; ?>>Custom Client</option>
                                <?php
                                $clients = mysqli_query($conn,"SELECT * FROM clients");
                                while($client = mysqli_fetch_assoc($clients))
                                {
                                ?>
                                <option
                                value="<?php echo $client['id']; ?>"
                                <?php
                                if($payment['client_id'] == $client['id'])
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
                            <input type="text" name="custom_client_name" id="customClientName" class="form-control mt-2" placeholder="Enter Custom Client Name" value="<?php echo htmlspecialchars($payment['custom_client_name'] ?? ''); ?>" style="display: <?php echo (empty($payment['client_id']) && !empty($payment['custom_client_name'])) ? 'block' : 'none'; ?>;">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-section">
                            <label class="form-label">Select Campaign (Optional)</label>
                            <select name="campaign_id" class="form-control form-select">
                                <option value="">No Campaign</option>
                                <?php
                                $campaigns = mysqli_query($conn,"SELECT * FROM campaigns");
                                while($campaign = mysqli_fetch_assoc($campaigns))
                                {
                                ?>
                                <option
                                value="<?php echo $campaign['id']; ?>"
                                <?php
                                if($payment['campaign_id'] == $campaign['id'])
                                {
                                    echo "selected";
                                }
                                ?>
                                >
                                <?php echo $campaign['campaign_name']; ?>
                                </option>
                                <?php
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-section">
                            <label class="form-label">Payment Amount (Rs)</label>
                            <input type="number"
                                   name="amount"
                                   class="form-control"
                                   value="<?php echo $payment['amount']; ?>">
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 12px; margin-top: 8px;">
                    <button type="submit" name="update" class="btn-brand">
                        <i class="bi bi-check-circle-fill"></i>
                        Update Payment
                    </button>
                    <a href="payments.php" class="btn-back">
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
function toggleCustomClient() {
    const select = document.getElementById('clientSelect');
    const input = document.getElementById('customClientName');
    if (select.value === 'custom') {
        input.style.display = 'block';
        input.required = true;
    } else {
        input.style.display = 'none';
        input.required = false;
        if (!input.value) input.value = '';
    }
}
</script>