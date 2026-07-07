<?php

session_start();

if(!isset($_SESSION['user']))
{
    header("Location: login.php");
    exit;
}

include 'db.php';
require_once 'auth.php';

// Filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'All';
$action_filter = isset($_GET['action']) ? $_GET['action'] : 'All';

$query_str = "SELECT * FROM leads WHERE 1=1";

if($status_filter === 'Active') {
    $query_str .= " AND status = 'Active'";
} elseif($status_filter === 'Secured') {
    $query_str .= " AND status = 'Secured'";
}

if($action_filter !== 'All') {
    $safe_action = mysqli_real_escape_string($conn, $action_filter);
    $query_str .= " AND action_type = '$safe_action'";
}

$query_str .= " ORDER BY created_at DESC";

$leads_result = mysqli_query($conn, $query_str);

// Follow-up notifications
$followup_due = mysqli_query($conn, "SELECT * FROM leads WHERE followup_date <= CURDATE() AND status='Active' ORDER BY followup_date ASC");
$followup_due_count = mysqli_num_rows($followup_due);
$followup_due_arr = [];
while($r = mysqli_fetch_assoc($followup_due)) { $followup_due_arr[] = $r; }

?>

<?php include 'header.php'; ?>

<!-- Top Bar -->
<div class="topbar">
    <div class="topbar-left">
        <button class="mobile-toggle" onclick="openSidebar()">
            <i class="bi bi-list"></i>
        </button>
        <div class="topbar-title">
            <h1>Follow-up History</h1>
            <p>Complete archive of all follow-ups — active and secured</p>
        </div>
    </div>
    <div class="topbar-right">
        <!-- Notification Dropdown -->
        <div class="dropdown">
            <button class="notification-btn" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-bell-fill"></i>
                <?php if($followup_due_count > 0): ?>
                <span class="notification-badge"><?php echo $followup_due_count; ?></span>
                <?php endif; ?>
            </button>

            <ul class="dropdown-menu dropdown-menu-end" style="width: 320px; padding: 0;">
                <div style="padding: 12px 15px; border-bottom: 1px solid var(--gray-200); font-weight: 700; color: var(--navy-800); background: var(--gray-50); border-radius: var(--radius-sm) var(--radius-sm) 0 0;">
                    Follow-up Reminders
                </div>
                
                <div style="max-height: 300px; overflow-y: auto;">
                    <?php if($followup_due_count == 0): ?>
                        <div style="padding: 20px; text-align: center; color: var(--gray-500);">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 24px; display: block; margin-bottom: 10px;"></i>
                            No pending follow-ups for today!
                        </div>
                    <?php else: ?>
                        <?php foreach($followup_due_arr as $fu): ?>
                            <div class="dropdown-item" style="padding: 12px 15px; border-bottom: 1px solid var(--gray-100); white-space: normal; display: flex; gap: 10px; align-items: flex-start;">
                                <?php if($fu['action_type'] == 'Call Pending'): ?>
                                    <i class="bi bi-telephone-fill text-danger mt-1"></i>
                                <?php elseif($fu['action_type'] == 'Meeting Scheduled'): ?>
                                    <i class="bi bi-people-fill text-success mt-1"></i>
                                <?php else: ?>
                                    <i class="bi bi-chat-dots-fill text-primary mt-1"></i>
                                <?php endif; ?>
                                <div style="flex-grow: 1;">
                                    <div style="font-weight: 600; font-size: 13px; color: var(--navy-800);">
                                        <?php echo $fu['action_type']; ?>
                                        <?php if($fu['followup_date'] < date('Y-m-d')): ?>
                                            <span style="color:var(--danger);font-size:11px;"> (Overdue)</span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size: 12px; color: var(--gray-600); margin-top: 2px;"><?php echo htmlspecialchars($fu['client_name']); ?> &mdash; <?php echo htmlspecialchars($fu['service_interest']); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <a href="leads.php" style="display: block; text-align: center; padding: 10px; background: var(--gray-50); color: var(--brand-500); font-size: 13px; font-weight: 600; text-decoration: none; border-radius: 0 0 var(--radius-sm) var(--radius-sm);">
                    Go to Follow-ups
                </a>
            </ul>
        </div>

        <a href="leads.php" class="btn-brand-outline">
            <i class="bi bi-arrow-left"></i> Back to Follow-ups
        </a>
    </div>
</div>

<!-- Content -->
<div class="content-wrapper">

    <!-- Filters -->
    <div class="mb-4" style="display: flex; gap: 20px; flex-wrap: wrap;">
        <div>
            <span style="font-weight: 600; font-size: 14px; margin-right: 10px; color: var(--navy-800);">Lead Status:</span>
            <div style="display: inline-flex; gap: 10px;">
                <a href="lead_history.php?status=All&action=<?php echo $action_filter; ?>" class="btn <?php echo $status_filter == 'All' ? 'btn-brand' : 'btn-outline-secondary'; ?>" style="border-radius: 20px; font-size: 14px; font-weight: 600; padding: 6px 16px;">All</a>
                <a href="lead_history.php?status=Active&action=<?php echo $action_filter; ?>" class="btn <?php echo $status_filter == 'Active' ? 'btn-brand' : 'btn-outline-secondary'; ?>" style="border-radius: 20px; font-size: 14px; font-weight: 600; padding: 6px 16px;">Active</a>
                <a href="lead_history.php?status=Secured&action=<?php echo $action_filter; ?>" class="btn <?php echo $status_filter == 'Secured' ? 'btn-brand' : 'btn-outline-secondary'; ?>" style="border-radius: 20px; font-size: 14px; font-weight: 600; padding: 6px 16px;">Secured</a>
            </div>
        </div>
        <div>
            <span style="font-weight: 600; font-size: 14px; margin-right: 10px; color: var(--navy-800);">Action Type:</span>
            <div style="display: inline-flex; gap: 10px;">
                <a href="lead_history.php?status=<?php echo $status_filter; ?>&action=All" class="btn <?php echo $action_filter == 'All' ? 'btn-brand' : 'btn-outline-secondary'; ?>" style="border-radius: 20px; font-size: 14px; font-weight: 600; padding: 6px 16px;">All</a>
                <a href="lead_history.php?status=<?php echo $status_filter; ?>&action=Call Pending" class="btn <?php echo $action_filter == 'Call Pending' ? 'btn-brand' : 'btn-outline-secondary'; ?>" style="border-radius: 20px; font-size: 14px; font-weight: 600; padding: 6px 16px;">Calls</a>
                <a href="lead_history.php?status=<?php echo $status_filter; ?>&action=Meeting Scheduled" class="btn <?php echo $action_filter == 'Meeting Scheduled' ? 'btn-brand' : 'btn-outline-secondary'; ?>" style="border-radius: 20px; font-size: 14px; font-weight: 600; padding: 6px 16px;">Meetings</a>
                <a href="lead_history.php?status=<?php echo $status_filter; ?>&action=Message Follow-up" class="btn <?php echo $action_filter == 'Message Follow-up' ? 'btn-brand' : 'btn-outline-secondary'; ?>" style="border-radius: 20px; font-size: 14px; font-weight: 600; padding: 6px 16px;">Messages</a>
            </div>
        </div>
    </div>

    <!-- History Table -->
    <div class="page-card">
        <div class="page-card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h2><i class="bi bi-archive-fill"></i> Follow-ups Archive</h2>
            <div class="topbar-search" style="max-width: 250px; margin: 0; box-shadow: none; border: 1px solid var(--gray-200); border-radius: var(--radius-sm); padding: 6px 12px; display: flex; align-items: center; gap: 8px;">
                <i class="bi bi-search text-gray-400"></i>
                <input type="text" placeholder="Search..." id="leadSearch" onkeyup="filterTable()" style="border: none; outline: none; width: 100%; font-size: 14px; background: transparent;">
            </div>
        </div>
        <div class="page-card-body" style="padding: 0;">
            <div class="table-wrapper">
                <table class="modern-table premium-table" id="leadsTable">
                    <thead>
                    <tr>
                        <th>Client</th>
                        <th>Service Interest</th>
                        <th>Last Action</th>
                        <th>Follow-up Date</th>
                        <th>Lead Status</th>
                        <th>Added On</th>
                        <th>Notes</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    while($row = mysqli_fetch_array($leads_result))
                    {
                        // Action Badge
                        if($row['action_type'] == 'Call Pending') {
                            $action_badge = '<span class="status-badge" style="background:#fef2f2;color:#dc2626;"><i class="bi bi-telephone-fill me-1"></i> Call</span>';
                        } elseif($row['action_type'] == 'Meeting Scheduled') {
                            $action_badge = '<span class="status-badge active"><i class="bi bi-people-fill me-1"></i> Meeting</span>';
                        } else {
                            $action_badge = '<span class="status-badge" style="background:#eff6ff;color:#2563eb;"><i class="bi bi-chat-dots-fill me-1"></i> Message</span>';
                        }

                        // Status Badge
                        if($row['status'] == 'Secured') {
                            $status_badge = '<span class="status-badge active" style="background: var(--success-light); color: var(--success);"><i class="bi bi-trophy-fill"></i> Secured</span>';
                        } else {
                            $status_badge = '<span class="status-badge paused" style="background: var(--danger-light); color: var(--danger);"><i class="bi bi-hourglass-split"></i> Active</span>';
                        }

                        // WhatsApp Message
                        $phone = preg_replace('/[^0-9+]/', '', $row['phone']);
                        if (strpos($phone, '0') === 0) {
                            $phone = '+92' . ltrim($phone, '0');
                        } elseif (strpos($phone, '+') !== 0) {
                            $phone = '+92' . ltrim($phone, '+');
                        }

                        $msg = "Hi ".$row['client_name'].",\n\n";
                        if($row['action_type'] == 'Call Pending') {
                            $msg .= "I will be giving you a quick call soon regarding the *".$row['service_interest']."* services we discussed. Let me know what time works best for you!";
                        } elseif($row['action_type'] == 'Meeting Scheduled') {
                            $msg .= "Just checking in to confirm our upcoming meeting regarding the *".$row['service_interest']."*. Looking forward to it!";
                        } else {
                            $msg .= "Just following up regarding the *".$row['service_interest']."* we discussed. Let me know if you have any questions or if you are ready to proceed!";
                        }
                        
                        $wa_link = "https://wa.me/".str_replace(['+',' ','-'], '', $phone)."?text=".urlencode($msg);

                        // Overdue / Today check (only matters for Active leads)
                        $is_overdue = ($row['status'] == 'Active' && $row['followup_date'] && strtotime($row['followup_date']) < strtotime(date('Y-m-d')));
                        $is_today = ($row['status'] == 'Active' && $row['followup_date'] == date('Y-m-d'));
                    ?>
                    <tr <?php if($is_overdue) echo 'style="background-color: #fff1f2;"'; ?>>
                        <td>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="width: 30px; height: 30px; border-radius: 50%; background: linear-gradient(135deg, <?php echo $row['status'] == 'Secured' ? '#16a34a, #059669' : 'var(--teal-600), var(--navy-600)'; ?>); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 11px; flex-shrink: 0;">
                                    <?php echo strtoupper(substr($row['client_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <span style="font-weight: 600;"><?php echo htmlspecialchars($row['client_name']); ?></span>
                                    <?php if(!empty($row['business_name'])): ?>
                                        <div style="font-size: 11px; color: var(--brand-600); font-weight: 600;"><i class="bi bi-building me-1"></i><?php echo htmlspecialchars($row['business_name']); ?></div>
                                    <?php endif; ?>
                                    <div style="font-size: 11px; color: var(--gray-500); margin-top: 1px;"><i class="bi bi-telephone me-1" style="font-size: 10px;"></i><?php echo htmlspecialchars($row['phone']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="platform-badge"><i class="bi bi-briefcase-fill me-1"></i> <?php echo htmlspecialchars($row['service_interest']); ?></span>
                        </td>
                        <td><?php echo $action_badge; ?></td>
                        <td style="font-weight: 500; color: var(--gray-600); font-size: 12px;">
                            <?php if(!$row['followup_date']): ?>
                                —
                            <?php elseif($is_overdue): ?>
                                <div><i class="bi bi-exclamation-circle-fill text-danger me-1"></i> <?php echo date('d M, Y', strtotime($row['followup_date'])); ?></div>
                                <span style="font-size: 10px; background: var(--danger); color: white; padding: 2px 6px; border-radius: 4px; display: inline-block; margin-top: 4px; animation: pulse 2s infinite;"><i class="bi bi-exclamation-triangle-fill"></i> OVERDUE</span>
                            <?php elseif($is_today): ?>
                                <div><i class="bi bi-clock-fill text-primary me-1"></i> Today</div>
                                <span style="font-size: 10px; background: var(--navy-600); color: white; padding: 2px 6px; border-radius: 4px; display: inline-block; margin-top: 4px;">Follow-up Today!</span>
                            <?php else: ?>
                                <div><i class="bi bi-calendar3 me-1"></i> <?php echo date('d M, Y', strtotime($row['followup_date'])); ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $status_badge; ?></td>
                        <td style="font-weight: 500; color: var(--gray-500); font-size: 12px;">
                            <?php echo $row['created_at'] ? date('d M, Y', strtotime($row['created_at'])) : '—'; ?>
                        </td>
                        <td>
                            <span style="font-size: 12px; color: var(--gray-600);"><?php echo htmlspecialchars($row['notes']) ?: '<span style="color:var(--gray-400);font-style:italic;">—</span>'; ?></span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 6px; align-items: center;">
                                <a href="<?php echo $wa_link; ?>" target="_blank" class="action-btn" style="background: #25D366; color: white; border-color: #25D366;" title="Send WhatsApp">
                                    <i class="bi bi-whatsapp"></i>
                                </a>
                                <?php if($row['status'] == 'Active'): ?>
                                <a class="action-btn" href="secure_lead.php?id=<?php echo $row['id']; ?>" title="Mark as Secured" onclick="return confirm('Mark this lead as Secured/Done?');" style="background: #fef9c3; color: #ca8a04; border-color: #fde68a;">
                                    <i class="bi bi-trophy-fill"></i>
                                </a>
                                <?php endif; ?>
                                <a class="action-btn edit" href="edit_lead.php?id=<?php echo $row['id']; ?>" title="Edit">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                <a class="action-btn delete" href="delete_lead.php?id=<?php echo $row['id']; ?>" title="Delete" onclick="return confirm('Delete this prospect permanently?')">
                                    <i class="bi bi-trash-fill"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php include 'footer.php'; ?>

<script>
function filterTable() {
    const query = document.getElementById('leadSearch').value.toLowerCase();
    const rows = document.querySelectorAll('#leadsTable tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(query) ? '' : 'none';
    });
}
</script>
