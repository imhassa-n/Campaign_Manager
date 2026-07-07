<?php
session_start();

if(!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

include 'db.php';

// Active Notifications
$expiring_campaigns = mysqli_query($conn,"
SELECT campaigns.*, clients.name as client_name
FROM campaigns
LEFT JOIN clients ON campaigns.client_id = clients.id
WHERE campaigns.end_date <= CURDATE() AND campaigns.status='Active'
");

$due_payments = mysqli_query($conn,"
SELECT campaigns.*, clients.name as client_name 
FROM campaigns
LEFT JOIN clients ON campaigns.client_id = clients.id
WHERE campaigns.payment_due_date <= CURDATE() AND campaigns.payment_status = 'Pending'
");

// Due Retainers
$due_retainers_query = mysqli_query($conn, "
SELECT s.*, c.name as client_name 
FROM services s
LEFT JOIN clients c ON s.client_id = c.id
WHERE s.service_type = 'Monthly Service Retainer' AND s.status = 'Active' AND s.payment_due_date <= CURDATE()
");
$due_retainers_arr = [];
while($r = mysqli_fetch_assoc($due_retainers_query)) {
    $b = floatval($r['budget']);
    $current_due = $r['payment_due_date'];
    if($current_due && $current_due != '0000-00-00') {
        $cycle_start = date('Y-m-d', strtotime('-1 month', strtotime($current_due)));
    } else {
        $cycle_start = $r['start_date'];
    }
    $rec_q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(amount),0) as total FROM payments WHERE service_id='".$r['id']."' AND payment_date >= '$cycle_start'"));
    $rec = floatval($rec_q['total']);
    $remaining = max(0, $b - $rec);
    if($remaining > 0) {
        $r['remaining'] = $remaining;
        $due_retainers_arr[] = $r;
    }
}

// Follow-up Due/Overdue
$followup_due = mysqli_query($conn,"
SELECT * FROM leads
WHERE followup_date <= CURDATE() AND status = 'Active'
ORDER BY followup_date ASC
");

// Daily Tasks Pending
$daily_tasks_pending = mysqli_query($conn,"
SELECT dc.* 
FROM digital_clients dc
LEFT JOIN daily_tasks dt ON dc.id = dt.digital_client_id AND dt.task_date = CURDATE()
WHERE dc.status = 'Active' AND (dt.status IS NULL OR dt.status = 'Pending')
");

// Recently Dismissed Notifications
$dismissed_history = mysqli_query($conn, "
SELECT dn.notification_type, dn.dismissed_at, c.campaign_name, cl.name as client_name
FROM dismissed_notifications dn
LEFT JOIN campaigns c ON dn.reference_id = c.id
LEFT JOIN clients cl ON c.client_id = cl.id
ORDER BY dn.dismissed_at DESC LIMIT 50
");

?>

<?php include 'header.php'; ?>

<!-- Top Bar -->
<div class="topbar">
    <div class="topbar-left">
        <button class="mobile-toggle" onclick="openSidebar()">
            <i class="bi bi-list"></i>
        </button>
        <div class="topbar-title">
            <h1>Notifications center</h1>
            <p>All your alerts and history in one place</p>
        </div>
    </div>
</div>

<!-- Content -->
<div class="content-wrapper">

    <div class="row">
        <div class="col-md-8">
            <div class="page-card mb-4">
                <div class="page-card-header">
                    <h2><i class="bi bi-bell-fill"></i> Active Alerts</h2>
                </div>
                <div class="page-card-body">
                    
                    <?php 
                    $has_active = false;
                    if(can('payments') && mysqli_num_rows($due_payments) > 0) $has_active = true;
                    if(can('payments') && count($due_retainers_arr) > 0) $has_active = true;
                    if(can('campaigns') && mysqli_num_rows($expiring_campaigns) > 0) $has_active = true;
                    if(can('leads') && mysqli_num_rows($followup_due) > 0) $has_active = true;
                    if(can('digital_tasks') && mysqli_num_rows($daily_tasks_pending) > 0) $has_active = true;
                    
                    if(!$has_active): ?>
                        <div style="text-align: center; padding: 40px 20px; color: var(--gray-500);">
                            <i class="bi bi-check-circle text-success" style="font-size: 48px;"></i>
                            <h4 class="mt-3" style="color: var(--navy-800);">No active alerts!</h4>
                            <p>You have caught up with all payments, campaigns and follow-ups.</p>
                        </div>
                    <?php else: ?>

                        <!-- Due Payments (Urgent - Red) -->
                        <?php if(can('payments')): while($due = mysqli_fetch_assoc($due_payments)): ?>
                        <div class="alert-item" style="border: 1px solid var(--danger-light); background: #fffcfc; display: flex; justify-content: space-between; align-items: center;" id="page-due-<?php echo $due['id']; ?>">
                            <div style="display: flex; gap: 15px; align-items: center;">
                                <div class="alert-icon danger" style="margin:0;"><i class="bi bi-exclamation-triangle-fill"></i></div>
                                <div>
                                    <h6 style="margin: 0; color: var(--danger); font-weight: 700;">Payment Due</h6>
                                    <p style="margin: 3px 0 0 0; font-size: 14px; color: var(--navy-800);"><?php echo htmlspecialchars($due['client_name']); ?> owes Rs <?php echo number_format($due['budget']); ?> for the campaign '<?php echo htmlspecialchars($due['campaign_name']); ?>'.</p>
                                </div>
                            </div>
                            <button class="btn btn-outline-secondary btn-sm" onclick="dismissPageNotification('due_payment', <?php echo $due['id']; ?>, 'page-due-<?php echo $due['id']; ?>')">
                                <i class="bi bi-check-lg"></i> Mark Read
                            </button>
                        </div>
                        <?php endwhile; endif; ?>

                        <!-- Due Retainers (Urgent - Red) -->
                        <?php if(can('payments')): foreach($due_retainers_arr as $ret): ?>
                        <div class="alert-item" style="border: 1px solid var(--danger-light); background: #fffcfc; display: flex; justify-content: space-between; align-items: center;" id="page-ret-<?php echo $ret['id']; ?>">
                            <div style="display: flex; gap: 15px; align-items: center;">
                                <div class="alert-icon danger" style="margin:0;"><i class="bi bi-exclamation-triangle-fill"></i></div>
                                <div>
                                    <h6 style="margin: 0; color: var(--danger); font-weight: 700;">Retainer Payment Due</h6>
                                    <p style="margin: 3px 0 0 0; font-size: 14px; color: var(--navy-800);"><?php echo htmlspecialchars($ret['client_name']); ?> owes Rs <?php echo number_format($ret['remaining']); ?> for their monthly retainer.</p>
                                </div>
                            </div>
                            <button class="btn btn-outline-secondary btn-sm" onclick="dismissPageNotification('due_retainer', <?php echo $ret['id']; ?>, 'page-ret-<?php echo $ret['id']; ?>')">
                                <i class="bi bi-check-lg"></i> Mark Read
                            </button>
                        </div>
                        <?php endforeach; endif; ?>

                        <!-- Expiring Campaigns (Upcoming - Yellow) -->
                        <?php if(can('campaigns')): while($exp = mysqli_fetch_assoc($expiring_campaigns)): ?>
                        <div class="alert-item" style="border: 1px solid var(--warning-light); background: #fffdf5; display: flex; justify-content: space-between; align-items: center;" id="page-exp-<?php echo $exp['id']; ?>">
                            <div style="display: flex; gap: 15px; align-items: center;">
                                <div class="alert-icon warning" style="margin:0;"><i class="bi bi-clock-fill"></i></div>
                                <div>
                                    <h6 style="margin: 0; color: var(--warning); font-weight: 700;">Campaign Ended / Ending</h6>
                                    <p style="margin: 3px 0 0 0; font-size: 14px; color: var(--navy-800);">Campaign '<?php echo htmlspecialchars($exp['campaign_name']); ?>' for <?php echo htmlspecialchars($exp['client_name']); ?> ended on <?php echo date('d M Y', strtotime($exp['end_date'])); ?>.</p>
                                </div>
                            </div>
                            <button class="btn btn-outline-secondary btn-sm" onclick="dismissPageNotification('expiring_campaign', <?php echo $exp['id']; ?>, 'page-exp-<?php echo $exp['id']; ?>')">
                                <i class="bi bi-check-lg"></i> Mark Read
                            </button>
                        </div>
                        <?php endwhile; endif; ?>

                        <!-- Follow-up Due (Blue) -->
                        <?php if(can('leads')): while($fu = mysqli_fetch_assoc($followup_due)): ?>
                        <div class="alert-item" style="border: 1px solid #bfdbfe; background: #f8fbff; display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; gap: 15px; align-items: center;">
                                <div class="alert-icon" style="margin:0; background: #eff6ff; color: #2563eb; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                                    <?php if($fu['action_type'] == 'Call Pending'): ?>
                                        <i class="bi bi-telephone-fill"></i>
                                    <?php elseif($fu['action_type'] == 'Meeting Scheduled'): ?>
                                        <i class="bi bi-people-fill"></i>
                                    <?php else: ?>
                                        <i class="bi bi-chat-dots-fill"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h6 style="margin: 0; color: #2563eb; font-weight: 700;">Follow-up Due<?php if($fu['followup_date'] < date('Y-m-d')) echo ' <span style="color:var(--danger);">(Overdue)</span>'; ?></h6>
                                    <p style="margin: 3px 0 0 0; font-size: 14px; color: var(--navy-800);"><?php echo htmlspecialchars($fu['client_name']); ?> &mdash; <?php echo $fu['action_type']; ?> for <?php echo htmlspecialchars($fu['service_interest']); ?>.</p>
                                </div>
                            </div>
                            <a href="leads.php" class="btn btn-outline-primary btn-sm" style="white-space: nowrap;">
                                <i class="bi bi-arrow-right"></i> View
                            </a>
                        </div>
                        <?php endwhile; endif; ?>

                        <!-- Daily Tasks Pending (Info - Blue/Teal) -->
                        <?php if(can('digital_tasks')): while($dt = mysqli_fetch_assoc($daily_tasks_pending)): ?>
                        <div class="alert-item" style="border: 1px solid #cff4fc; background: #f6fcff; display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; gap: 15px; align-items: center;">
                                <div class="alert-icon" style="margin:0; background: #e0f8fc; color: #0dcaf0; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                                    <i class="bi bi-calendar2-check-fill"></i>
                                </div>
                                <div>
                                    <h6 style="margin: 0; color: #0dcaf0; font-weight: 700;">Daily Task Pending</h6>
                                    <p style="margin: 3px 0 0 0; font-size: 14px; color: var(--navy-800);">Today's work for <b><?php echo htmlspecialchars($dt['client_name']); ?></b> (<?php echo htmlspecialchars($dt['platforms']); ?>) is not updated.</p>
                                </div>
                            </div>
                            <a href="digital_tasks.php" class="btn btn-outline-info btn-sm" style="white-space: nowrap; color: #0dcaf0; border-color: #0dcaf0;">
                                <i class="bi bi-arrow-right"></i> Update Now
                            </a>
                        </div>
                        <?php endwhile; endif; ?>

                    <?php endif; ?>

                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="page-card">
                <div class="page-card-header">
                    <h2><i class="bi bi-clock-history"></i> Read History</h2>
                </div>
                <div class="page-card-body" style="max-height: 500px; overflow-y: auto;">
                    <?php if(mysqli_num_rows($dismissed_history) == 0): ?>
                        <div style="text-align: center; color: var(--gray-500); padding: 20px 0;">
                            No history found.
                        </div>
                    <?php else: ?>
                        <div class="activity-timeline" style="padding-left: 15px; border-left: 2px solid var(--gray-200); margin-left: 10px;">
                            <?php while($hist = mysqli_fetch_assoc($dismissed_history)): ?>
                            <div style="position: relative; margin-bottom: 20px;">
                                <div style="position: absolute; left: -22px; top: 0; width: 12px; height: 12px; background: var(--gray-400); border-radius: 50%; border: 2px solid white;"></div>
                                <div style="font-weight: 600; color: var(--navy-800); font-size: 13px;">
                                    <?php echo $hist['notification_type'] == 'due_payment' ? 'Payment Read' : 'Campaign Expiry Read'; ?>
                                </div>
                                <div style="color: var(--gray-500); font-size: 11px; margin-bottom: 4px;"><?php echo date('d M Y, h:i A', strtotime($hist['dismissed_at'])); ?></div>
                                <div style="font-size: 12px; color: var(--gray-600); background: var(--gray-50); padding: 8px; border-radius: 4px;">
                                    <?php echo htmlspecialchars($hist['campaign_name']); ?> (<?php echo htmlspecialchars($hist['client_name']); ?>)
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<?php include 'footer.php'; ?>

<script>
function dismissPageNotification(type, refId, elemId) {
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
            const el = document.getElementById(elemId);
            if(el) {
                el.style.opacity = '0';
                setTimeout(() => {
                    el.remove();
                    // Optionally refresh the page after a short delay to update history
                    setTimeout(() => window.location.reload(), 500);
                }, 300);
            }
        }
    });
}
</script>
