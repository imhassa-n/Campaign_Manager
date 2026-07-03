<?php

session_start();

if(!isset($_SESSION['user']))
{
    header("Location: login.php");
    exit;
}

include 'db.php';
require_once 'auth.php';

$total_clients = mysqli_num_rows(
mysqli_query($conn,"SELECT * FROM clients")
);

$total_campaigns = mysqli_num_rows(
mysqli_query($conn,"SELECT * FROM campaigns WHERE status='Active'")
);

$total_payments = mysqli_fetch_assoc(
mysqli_query($conn,"
SELECT IFNULL(SUM(amount),0) as total
FROM payments
")
);

$pending = mysqli_fetch_assoc(
mysqli_query($conn,"
SELECT
IFNULL(SUM(campaigns.budget),0)
-
IFNULL(SUM(payments.amount),0)
AS pending
FROM campaigns
LEFT JOIN payments
ON campaigns.id = payments.campaign_id
WHERE campaigns.payment_status != 'Cleared'
")
);

$expiring_campaigns = mysqli_query($conn,"
SELECT *
FROM campaigns
WHERE end_date <= CURDATE() 
AND status='Active'
");
$expiring_count = mysqli_num_rows($expiring_campaigns);

$due_payments = mysqli_query($conn,"
SELECT campaigns.*, clients.name as client_name 
FROM campaigns
LEFT JOIN clients ON campaigns.client_id = clients.id
WHERE payment_due_date <= CURDATE() AND payment_status = 'Pending'
");
$due_count = mysqli_num_rows($due_payments);

$followup_due = mysqli_query($conn,"
SELECT * FROM leads
WHERE followup_date <= CURDATE() AND status = 'Active'
ORDER BY followup_date ASC
");
$followup_due_count = mysqli_num_rows($followup_due);

$notification_count = $due_count + $expiring_count + $followup_due_count;

$due_payments_arr = [];
while($r = mysqli_fetch_assoc($due_payments)) { $due_payments_arr[] = $r; }

$expiring_campaigns_arr = [];
while($r = mysqli_fetch_assoc($expiring_campaigns)) { $expiring_campaigns_arr[] = $r; }

$followup_due_arr = [];
while($r = mysqli_fetch_assoc($followup_due)) { $followup_due_arr[] = $r; }

$daily_tasks_pending = mysqli_query($conn,"
SELECT dc.* 
FROM digital_clients dc
LEFT JOIN daily_tasks dt ON dc.id = dt.digital_client_id AND dt.task_date = CURDATE()
WHERE dc.status = 'Active' AND (dt.status IS NULL OR dt.status = 'Pending')
");
$daily_tasks_pending_count = mysqli_num_rows($daily_tasks_pending);
$daily_tasks_arr = [];
while($r = mysqli_fetch_assoc($daily_tasks_pending)) { $daily_tasks_arr[] = $r; }

$notification_count = 0;
if(can('payments')) $notification_count += $due_count;
if(can('campaigns')) $notification_count += $expiring_count;
if(can('leads')) $notification_count += $followup_due_count;
if(can('digital_tasks')) $notification_count += $daily_tasks_pending_count;


?>

<?php include 'header.php'; ?>

<!-- Top Bar -->
<div class="topbar">
    <div class="topbar-left">
        <button class="mobile-toggle" onclick="openSidebar()">
            <i class="bi bi-list"></i>
        </button>
        <div class="topbar-title">
            <h1>Overview</h1>
            <p>Your agency command center</p>
        </div>
    </div>
    <div class="topbar-right">
        <div class="topbar-search">
            <i class="bi bi-search"></i>
            <input type="text" placeholder="Search clients, campaigns...">
        </div>

        <!-- Notification Dropdown -->
        <div class="dropdown">
            <button class="notification-btn" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-bell-fill"></i>
                <?php if($notification_count > 0): ?>
                <span class="notification-badge"><?php echo $notification_count; ?></span>
                <?php endif; ?>
            </button>

            <ul class="dropdown-menu dropdown-menu-end" style="width: 320px; padding: 0;">
                <div style="padding: 12px 15px; border-bottom: 1px solid var(--gray-200); font-weight: 700; color: var(--navy-800); background: var(--gray-50); border-radius: var(--radius-sm) var(--radius-sm) 0 0;">
                    Notifications
                </div>
                
                <div style="max-height: 300px; overflow-y: auto;">
                    <?php if($notification_count == 0): ?>
                        <div style="padding: 20px; text-align: center; color: var(--gray-500);">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 24px; display: block; margin-bottom: 10px;"></i>
                            You're all caught up!
                        </div>
                    <?php else: ?>
                        <?php if(can('payments')): foreach($due_payments_arr as $due): ?>
                            <div class="dropdown-item" style="padding: 12px 15px; border-bottom: 1px solid var(--gray-100); white-space: normal; display: flex; gap: 10px; align-items: flex-start;" id="notif-due-<?php echo $due['id']; ?>">
                                <i class="bi bi-exclamation-circle-fill text-danger mt-1"></i>
                                <div style="flex-grow: 1;">
                                    <div style="font-weight: 600; font-size: 13px; color: var(--navy-800);">Payment Due</div>
                                    <div style="font-size: 12px; color: var(--gray-600); margin-top: 2px;">Rs <?php echo number_format($due['budget']); ?> pending from <?php echo $due['client_name']; ?>.</div>
                                </div>
                                <button class="btn btn-sm text-gray-400" onclick="dismissNotification('due_payment', <?php echo $due['id']; ?>, 'notif-due-<?php echo $due['id']; ?>')" title="Mark as Read" style="padding: 0; background: none; border: none;">
                                    <i class="bi bi-x-circle-fill"></i>
                                </button>
                            </div>
                        <?php endforeach; endif; ?>

                        <?php if(can('campaigns')): foreach($expiring_campaigns_arr as $exp): ?>
                            <div class="dropdown-item" style="padding: 12px 15px; border-bottom: 1px solid var(--gray-100); white-space: normal; display: flex; gap: 10px; align-items: flex-start;" id="notif-exp-<?php echo $exp['id']; ?>">
                                <i class="bi bi-clock-fill text-warning mt-1"></i>
                                <div style="flex-grow: 1;">
                                    <div style="font-weight: 600; font-size: 13px; color: var(--navy-800);">Campaign Ended / Ending</div>
                                    <div style="font-size: 12px; color: var(--gray-600); margin-top: 2px;">'<?php echo $exp['campaign_name']; ?>' ended on <?php echo date('d M Y', strtotime($exp['end_date'])); ?>.</div>
                                </div>
                                <button class="btn btn-sm text-gray-400" onclick="dismissNotification('expiring_campaign', <?php echo $exp['id']; ?>, 'notif-exp-<?php echo $exp['id']; ?>')" title="Mark as Read" style="padding: 0; background: none; border: none;">
                                    <i class="bi bi-x-circle-fill"></i>
                                </button>
                            </div>
                        <?php endforeach; endif; ?>

                        <?php if(can('leads')): foreach($followup_due_arr as $fu): ?>
                            <div class="dropdown-item" style="padding: 12px 15px; border-bottom: 1px solid var(--gray-100); white-space: normal; display: flex; gap: 10px; align-items: flex-start;">
                                <i class="bi bi-bullseye text-primary mt-1"></i>
                                <div style="flex-grow: 1;">
                                    <div style="font-weight: 600; font-size: 13px; color: var(--navy-800);">Follow-up Due</div>
                                    <div style="font-size: 12px; color: var(--gray-600); margin-top: 2px;">
                                        <?php echo htmlspecialchars($fu['client_name']); ?> — 
                                        <?php echo $fu['action_type']; ?>
                                        <?php if($fu['followup_date'] < date('Y-m-d')) echo ' <span style="color:var(--danger);font-weight:600;">(Overdue)</span>'; ?>
                                    </div>
                                </div>
                                <a href="leads.php" style="padding: 0; background: none; border: none; color: var(--brand-500); font-size: 12px; text-decoration: none; font-weight: 600; white-space: nowrap;" title="View">View</a>
                            </div>
                        <?php endforeach; endif; ?>

                        <?php if(can('digital_tasks')): foreach($daily_tasks_arr as $dt): ?>
                            <div class="dropdown-item" style="padding: 12px 15px; border-bottom: 1px solid var(--gray-100); white-space: normal; display: flex; gap: 10px; align-items: flex-start;">
                                <i class="bi bi-calendar2-check-fill text-info mt-1"></i>
                                <div style="flex-grow: 1;">
                                    <div style="font-weight: 600; font-size: 13px; color: var(--navy-800);">Task Pending</div>
                                    <div style="font-size: 12px; color: var(--gray-600); margin-top: 2px;">
                                        Today's work for <b><?php echo htmlspecialchars($dt['client_name']); ?></b> is not updated.
                                    </div>
                                </div>
                                <a href="digital_tasks.php" style="padding: 0; background: none; border: none; color: var(--brand-500); font-size: 12px; text-decoration: none; font-weight: 600; white-space: nowrap;" title="Update">Update</a>
                            </div>
                        <?php endforeach; endif; ?>
                    <?php endif; ?>
                </div>
                
                <a href="notifications.php" style="display: block; text-align: center; padding: 10px; background: var(--gray-50); color: var(--brand-500); font-size: 13px; font-weight: 600; text-decoration: none; border-radius: 0 0 var(--radius-sm) var(--radius-sm);">
                    View All Notifications
                </a>
            </ul>
        </div>
    </div>
</div>

<!-- Content -->
<div class="content-wrapper">

    <!-- Welcome Banner (Glassmorphism) -->
    <div class="welcome-banner glass-panel" style="background: linear-gradient(135deg, var(--navy-800), var(--navy-600));">
        <h2>Welcome Back 👋</h2>
        <p>Here is what's happening with your agency today.</p>
        <p class="welcome-date">
            <i class="bi bi-calendar3 me-1"></i>
            <?php echo date('l, F j, Y'); ?>
        </p>
    </div>

    <!-- Primary Metrics Grid -->
    <div class="grid-4 mb-4">
        
        <?php if(can('payments')): ?>
        <div class="metric-card">
            <div class="metric-icon success">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div class="metric-label">Total Revenue</div>
            <div class="metric-value">Rs <?php echo number_format($total_payments['total']); ?></div>
        </div>

        <div class="metric-card">
            <div class="metric-icon danger">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <div class="metric-label">Pending Receivables</div>
            <div class="metric-value">Rs <?php echo number_format($pending['pending']); ?></div>
        </div>
        <?php endif; ?>

        <div class="metric-card">
            <div class="metric-icon teal">
                <i class="bi bi-megaphone-fill"></i>
            </div>
            <div class="metric-label">Active Campaigns</div>
            <div class="metric-value"><?php echo $total_campaigns; ?></div>
        </div>

        <div class="metric-card">
            <div class="metric-icon navy">
                <i class="bi bi-people-fill"></i>
            </div>
            <div class="metric-label">Total Clients</div>
            <div class="metric-value"><?php echo $total_clients; ?></div>
        </div>

    </div>



    <!-- Main Layout (2 Columns) -->
    <div class="dashboard-layout">
        
        <!-- Left Side: Recent Campaigns & Top Clients -->
        <div class="layout-main">
            <div class="page-card mb-4">
                <div class="page-card-header">
                    <h2><i class="bi bi-lightning-charge-fill"></i> Active Campaigns Overview</h2>
                    <a href="campaigns.php" class="btn-brand-outline" style="padding: 6px 16px; font-size: 13px;">View All</a>
                </div>
                <div class="page-card-body" style="padding: 0;">
                    <div class="table-wrapper">
                        <table class="modern-table premium-table">
                            <thead>
                            <tr>
                                <th>Client / Campaign Name</th>
                                <th>Platform</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            $recent = mysqli_query($conn,"
                            SELECT campaigns.*, clients.phone, clients.name as client_name 
                            FROM campaigns 
                            LEFT JOIN clients ON campaigns.client_id = clients.id
                            WHERE campaigns.status = 'Active'
                            ORDER BY campaigns.id DESC LIMIT 5
                            ");

                            while($r = mysqli_fetch_assoc($recent))
                            {
                            ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, var(--teal-600), var(--navy-600)); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 12px; flex-shrink: 0;">
                                            <?php echo strtoupper(substr($r['client_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 600; color: var(--navy-800);"><?php echo $r['campaign_name']; ?></div>
                                            <div style="font-size: 12px; color: var(--gray-500);"><?php echo $r['client_name']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="platform-badge"><?php echo $r['campaign_type']; ?></span>
                                </td>
                                <td>
                                    <span class="status-badge active">Active</span>
                                </td>
                                <td>
                                    <a href="edit_campaign.php?id=<?php echo $r['id']; ?>" class="action-btn" title="View / Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Quick Action Buttons Row -->
            <div class="grid-3">
                <?php if(can('clients')): ?>
                <a href="clients.php" class="quick-action qa-navy">
                    <i class="bi bi-person-plus-fill"></i> Add Client
                </a>
                <?php endif; ?>
                
                <?php if(can('campaigns')): ?>
                <a href="campaigns.php" class="quick-action qa-teal">
                    <i class="bi bi-megaphone-fill"></i> Add Campaign
                </a>
                <?php endif; ?>

                <?php if(can('payments')): ?>
                <a href="payments.php" class="quick-action qa-blend">
                    <i class="bi bi-cash-stack"></i> Record Payment
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Side: Action Center Sidebar -->
        <div class="layout-sidebar">
            <div class="page-card glass-panel" style="background: white;">
                <div class="page-card-header" style="border-bottom: 1px solid var(--gray-100);">
                    <h2 style="font-size: 15px;"><i class="bi bi-bell-fill"></i> Action Center</h2>
                </div>
                <div class="page-card-body">
                    
                    <?php 
                    $has_due = $due_count > 0 && can('payments');
                    $has_exp = $expiring_count > 0 && can('campaigns');
                    
                    if($has_due || $has_exp): ?>
                    
                        <?php if(can('payments')): foreach($due_payments_arr as $due){ ?>
                        <div class="alert-item" id="alert-due-<?php echo $due['id']; ?>">
                            <div class="alert-icon danger"><i class="bi bi-exclamation-triangle-fill"></i></div>
                            <div class="alert-content">
                                <h6>Payment Due!</h6>
                                <p><?php echo $due['client_name']; ?> owes Rs <?php echo number_format($due['budget']); ?> for <?php echo $due['campaign_name']; ?>.</p>
                            </div>
                        </div>
                        <?php } endif; ?>

                        <?php foreach($expiring_campaigns_arr as $exp){ ?>
                        <div class="alert-item" id="alert-exp-<?php echo $exp['id']; ?>">
                            <div class="alert-icon warning"><i class="bi bi-clock-fill"></i></div>
                            <div class="alert-content">
                                <h6>Campaign Ended</h6>
                                <p>Campaign '<?php echo $exp['campaign_name']; ?>' ended on <?php echo date('d M Y', strtotime($exp['end_date'])); ?>.</p>
                            </div>
                        </div>
                        <?php } ?>

                    <?php else: ?>
                        <div style="text-align: center; padding: 30px 10px;">
                            <i class="bi bi-check-circle text-success" style="font-size: 40px;"></i>
                            <h6 class="mt-3" style="color: var(--navy-800); font-weight: 700;">You're all caught up!</h6>
                            <p style="font-size: 13px; color: var(--gray-500);">No urgent payments or expiring campaigns.</p>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

    </div>

</div>

<?php include 'footer.php'; ?>

<script>
function dismissNotification(type, refId, notifId) {
    // Prevent dropdown from closing
    event.stopPropagation();
    
    // AJAX request
    const formData = new FormData();
    formData.append('type', type);
    formData.append('ref_id', refId);

    fetch('dismiss_notification.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.status === 'success') {
            // Remove from UI
            const el = document.getElementById(notifId);
            if(el) {
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 300);
            }
            
            // Optionally remove from Action Center if it's the same item
            // Because ID formats in dropdown vs action center might be different, let's remove from action center too
            const alertId = type === 'due_payment' ? 'alert-due-' + refId : 'alert-exp-' + refId;
            const alertEl = document.getElementById(alertId);
            if(alertEl) {
                alertEl.style.opacity = '0';
                setTimeout(() => alertEl.remove(), 300);
            }

            // Update badge count
            const badge = document.querySelector('.notification-badge');
            if(badge) {
                let count = parseInt(badge.innerText);
                count--;
                if(count > 0) {
                    badge.innerText = count;
                } else {
                    badge.remove();
                }
            }
        } else {
            console.error('Error dismissing notification:', data.message);
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
    });
}
</script>
