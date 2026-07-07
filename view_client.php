<?php
session_start();

if(!isset($_SESSION['user']))
{
    header("Location: login.php");
    exit;
}

include 'db.php';

if(!isset($_GET['id'])) {
    header("Location: clients.php");
    exit;
}

$id = $_GET['id'];

// Fetch Client Details
$client_res = mysqli_query($conn,"SELECT * FROM clients WHERE id='$id'");
$client = mysqli_fetch_assoc($client_res);

if(!$client) {
    header("Location: clients.php");
    exit;
}

// Handle Notes Update
if(isset($_POST['update_notes'])) {
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    mysqli_query($conn, "UPDATE clients SET notes='$notes' WHERE id='$id'");
    
    // Log Activity
    $action = "Updated Notes";
    $desc = "Added/updated notes for this client.";
    mysqli_query($conn, "INSERT INTO client_activity_log (client_id, action, description) VALUES ('$id', '$action', '$desc')");
    
    header("Location: view_client.php?id=$id&msg=Notes+Updated");
    exit;
}

// Fetch Stats
$total_campaigns_res = mysqli_query($conn, "SELECT count(*) as total FROM campaigns WHERE client_id='$id'");
$total_campaigns = mysqli_fetch_assoc($total_campaigns_res)['total'];

$active_campaigns_res = mysqli_query($conn, "SELECT count(*) as total FROM campaigns WHERE client_id='$id' AND status='Active'");
$active_campaigns = mysqli_fetch_assoc($active_campaigns_res)['total'];

$total_payments_res = mysqli_query($conn, "SELECT sum(amount) as total FROM payments WHERE client_id='$id'");
$total_payments_row = mysqli_fetch_assoc($total_payments_res);
$total_payments = $total_payments_row['total'] ? $total_payments_row['total'] : 0;

$total_services_res = mysqli_query($conn, "SELECT count(*) as total FROM services WHERE client_id='$id'");
$total_services = mysqli_fetch_assoc($total_services_res)['total'] ?? 0;

// Fetch Activity Log
$activity_res = mysqli_query($conn, "SELECT * FROM client_activity_log WHERE client_id='$id' ORDER BY id DESC LIMIT 20");

?>

<?php include 'header.php'; ?>

<!-- Top Bar -->
<div class="topbar">
    <div class="topbar-left">
        <button class="mobile-toggle" onclick="openSidebar()">
            <i class="bi bi-list"></i>
        </button>
        <div class="topbar-title">
            <h1>Client Profile</h1>
            <p>Overview & Activity History</p>
        </div>
    </div>
    <div class="topbar-right">
        <a href="clients.php" class="btn-brand-outline">
            <i class="bi bi-arrow-left"></i>
            Back to Clients
        </a>
    </div>
</div>

<!-- Content -->
<div class="content-wrapper">

    <?php if(isset($_GET['msg'])) { ?>
        <div style="background: var(--green-100); color: var(--green-700); padding: 10px 15px; border-radius: var(--radius-sm); margin-bottom: 20px; font-weight: 500;">
            <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($_GET['msg']); ?>
        </div>
    <?php } ?>

    <!-- Stats Grid -->
    <div class="grid-4 mb-4">
        <div class="stat-card">
            <div class="stat-icon" style="background: var(--purple-100); color: var(--purple-600);">
                <i class="bi bi-megaphone-fill"></i>
            </div>
            <div class="stat-details">
                <h3><?php echo $active_campaigns; ?> / <?php echo $total_campaigns; ?></h3>
                <p>Active / Total Campaigns</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: var(--teal-100); color: var(--teal-600);">
                <i class="bi bi-pc-display-horizontal"></i>
            </div>
            <div class="stat-details">
                <h3><?php echo $total_services; ?></h3>
                <p>Total Services/Projects</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: var(--green-100); color: var(--green-600);">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div class="stat-details">
                <h3>Rs <?php echo number_format($total_payments); ?></h3>
                <p>Total Revenue from Client</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: var(--orange-100); color: var(--orange-600);">
                <i class="bi bi-tag-fill"></i>
            </div>
            <div class="stat-details">
                <h3><?php echo htmlspecialchars($client['tag'] ?? 'Active'); ?></h3>
                <p>Current Status</p>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <!-- Notes Section -->
            <div class="page-card mb-4">
                <div class="page-card-header">
                    <h2><i class="bi bi-journal-text"></i> Client Notes</h2>
                </div>
                <div class="page-card-body">
                    <form method="POST">
                        <div class="form-section">
                            <label class="form-label">Private Notes & Remarks</label>
                            <textarea name="notes" class="form-control" rows="6" placeholder="Enter notes, past discussions or important remarks here..."><?php echo htmlspecialchars($client['notes'] ?? ''); ?></textarea>
                        </div>
                        <button type="submit" name="update_notes" class="btn-brand">
                            <i class="bi bi-save-fill"></i> Save Notes
                        </button>
                    </form>
                </div>
            </div>

            <!-- Client Info Profile Card -->
            <div class="page-card">
                <div class="page-card-header">
                    <h2><i class="bi bi-person-badge-fill"></i> Contact Details</h2>
                </div>
                <div class="page-card-body">
                    <div style="display: flex; gap: 20px; align-items: center;">
                        <?php if(!empty($client['image'])) { 
                            $img_src = (strpos($client['image'], 'data:image') === 0) ? $client['image'] : 'assets/clients/'.$client['image'];
                        ?>
                        <img src="<?php echo $img_src; ?>" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; box-shadow: var(--shadow-sm); border: 3px solid var(--gray-100);" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, var(--teal-600), var(--navy-600)); align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 32px; flex-shrink: 0; box-shadow: var(--shadow-sm); border: 3px solid var(--gray-100); display: none;">
                            <?php echo strtoupper(substr($client['name'], 0, 1)); ?>
                        </div>
                        <?php } else { ?>
                        <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, var(--teal-600), var(--navy-600)); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 32px; flex-shrink: 0; box-shadow: var(--shadow-sm); border: 3px solid var(--gray-100);">
                            <?php echo strtoupper(substr($client['name'], 0, 1)); ?>
                        </div>
                        <?php } ?>
                        
                        <div>
                            <h3 style="margin: 0 0 5px 0; color: var(--navy-800);"><?php echo $client['name']; ?></h3>
                            <div style="color: var(--gray-600); margin-bottom: 3px;"><i class="bi bi-envelope-fill me-2"></i> <?php echo $client['email']; ?></div>
                            <div style="color: var(--gray-600);"><i class="bi bi-telephone-fill me-2"></i> <?php echo $client['phone']; ?></div>
                        </div>
                        
                        <div style="margin-left: auto;">
                            <a href="edit_client.php?id=<?php echo $client['id']; ?>" class="btn-brand-outline">
                                <i class="bi bi-pencil-fill"></i> Edit Client
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Activity Log -->
            <div class="page-card">
                <div class="page-card-header">
                    <h2><i class="bi bi-clock-history"></i> Activity Log</h2>
                </div>
                <div class="page-card-body" style="max-height: 500px; overflow-y: auto;">
                    <?php if(mysqli_num_rows($activity_res) > 0) { ?>
                        <div class="activity-timeline" style="position: relative; padding-left: 20px; border-left: 2px solid var(--gray-200); margin-left: 10px;">
                            <?php while($act = mysqli_fetch_assoc($activity_res)) { ?>
                            <div class="activity-item" style="position: relative; margin-bottom: 20px;">
                                <div style="position: absolute; left: -27px; top: 0; width: 12px; height: 12px; background: var(--teal-500); border-radius: 50%; border: 2px solid white;"></div>
                                <div style="font-weight: 600; color: var(--navy-800); font-size: 14px;"><?php echo htmlspecialchars($act['action']); ?></div>
                                <div style="color: var(--gray-500); font-size: 12px; margin-bottom: 4px;"><?php echo date('d M Y, h:i A', strtotime($act['created_at'])); ?></div>
                                <?php if($act['description']) { ?>
                                <div style="font-size: 13px; color: var(--gray-600); background: var(--gray-50); padding: 8px; border-radius: 4px; margin-top: 4px;">
                                    <?php echo htmlspecialchars($act['description']); ?>
                                </div>
                                <?php } ?>
                            </div>
                            <?php } ?>
                        </div>
                    <?php } else { ?>
                        <div style="text-align: center; color: var(--gray-500); padding: 20px 0;">
                            <i class="bi bi-inbox" style="font-size: 24px; display: block; margin-bottom: 10px;"></i>
                            No recent activity found.
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>

</div>

<?php include 'footer.php'; ?>
