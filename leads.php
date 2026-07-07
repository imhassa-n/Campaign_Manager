<?php

session_start();

if(!isset($_SESSION['user']))
{
    header("Location: login.php");
    exit;
}

include 'db.php';
require_once 'auth.php';

if(isset($_POST['save']))
{
    $client_name = mysqli_real_escape_string($conn, $_POST['client_name']);
    $business_name = mysqli_real_escape_string($conn, $_POST['business_name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $service_interest = mysqli_real_escape_string($conn, $_POST['service_interest']);
    $action_type = mysqli_real_escape_string($conn, $_POST['action_type']);
    $followup_date = mysqli_real_escape_string($conn, $_POST['followup_date']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);

    mysqli_query($conn,"
    INSERT INTO leads(client_name, business_name, phone, service_interest, action_type, followup_date, notes)
    VALUES('$client_name','$business_name','$phone','$service_interest','$action_type','$followup_date','$notes')
    ");

    header("Location: leads.php");
    exit;
}

// Stats
$total_prospects = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM leads WHERE status='Active'"));
$calls_pending = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM leads WHERE action_type='Call Pending' AND status='Active'"));
$meetings_pending = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM leads WHERE action_type='Meeting Scheduled' AND status='Active'"));
$overdue_count = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM leads WHERE followup_date < CURDATE() AND status='Active'"));
$secured_count = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM leads WHERE status='Secured'"));

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
            <h1>Follow-ups</h1>
            <p>Track and reach out to potential clients</p>
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
                
                <a href="lead_history.php" style="display: block; text-align: center; padding: 10px; background: var(--gray-50); color: var(--brand-500); font-size: 13px; font-weight: 600; text-decoration: none; border-radius: 0 0 var(--radius-sm) var(--radius-sm);">
                    View Full History
                </a>
            </ul>
        </div>

        <div class="topbar-search">
            <i class="bi bi-search"></i>
            <input type="text" placeholder="Search follow-ups..." id="leadSearch" onkeyup="filterTable()">
        </div>
    </div>
</div>

<!-- Content -->
<div class="content-wrapper">

    <!-- Prospect Stats -->
    <div class="grid-4 mb-4">
        <div class="metric-card">
            <div class="metric-icon navy">
                <i class="bi bi-bullseye"></i>
            </div>
            <div class="metric-label">Total Follow-ups</div>
            <div class="metric-value"><?php echo $total_prospects; ?></div>
        </div>

        <div class="metric-card">
            <div class="metric-icon danger">
                <i class="bi bi-telephone-fill"></i>
            </div>
            <div class="metric-label">Calls Pending</div>
            <div class="metric-value"><?php echo $calls_pending; ?></div>
        </div>

        <div class="metric-card">
            <div class="metric-icon teal">
                <i class="bi bi-people-fill"></i>
            </div>
            <div class="metric-label">Meetings Scheduled</div>
            <div class="metric-value"><?php echo $meetings_pending; ?></div>
        </div>

        <div class="metric-card">
            <div class="metric-icon" style="background: <?php echo $overdue_count > 0 ? 'var(--danger-light)' : '#f0fdf4'; ?>; color: <?php echo $overdue_count > 0 ? 'var(--danger)' : '#16a34a'; ?>;">
                <i class="bi bi-<?php echo $overdue_count > 0 ? 'exclamation-triangle-fill' : 'check-circle-fill'; ?>"></i>
            </div>
            <div class="metric-label">Overdue Follow-ups</div>
            <div class="metric-value" style="color: <?php echo $overdue_count > 0 ? 'var(--danger)' : '#16a34a'; ?>;"><?php echo $overdue_count; ?></div>
        </div>
    </div>

    <!-- Add Prospect Form -->
    <div class="page-card mb-4 glass-panel" style="background: white;">
        <div class="page-card-header">
            <h2><i class="bi bi-bullseye"></i> Add New Follow-up</h2>
        </div>
        <div class="page-card-body">
            <form method="POST">

                <div class="section-title"><i class="bi bi-person-fill"></i> Client Details</div>
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="form-section">
                            <label class="form-label">Client Name</label>
                            <input type="text" name="client_name" class="form-control" placeholder="Enter client's full name" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-section">
                            <label class="form-label">Business Name</label>
                            <input type="text" name="business_name" class="form-control" placeholder="e.g. ABC Corp">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-section">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" placeholder="e.g. 03001234567" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-section">
                            <label class="form-label">Service Interest</label>
                            <select name="service_interest" class="form-control form-select">
                                <option>Ads Campaign</option>
                                <option>Website Development</option>
                                <option>SEO Services</option>
                                <option>Social Media Management</option>
                                <option>Graphic Design</option>
                                <option>Other</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="section-title"><i class="bi bi-lightning-charge-fill"></i> Follow-up Plan</div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="form-section">
                            <label class="form-label">Next Action</label>
                            <select name="action_type" class="form-control form-select" id="actionTypeSelect">
                                <option value="Call Pending">Call Pending</option>
                                <option value="Meeting Scheduled">Meeting Scheduled</option>
                                <option value="Message Follow-up">Message Follow-up</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-section">
                            <label class="form-label">Follow-up Date</label>
                            <input type="date" name="followup_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-section">
                            <label class="form-label">Notes</label>
                            <input type="text" name="notes" class="form-control" placeholder="Quick notes about the client...">
                        </div>
                    </div>
                </div>

                <button type="submit" name="save" class="btn-brand">
                    <i class="bi bi-plus-circle-fill"></i>
                    Add Follow-up
                </button>

            </form>
        </div>
    </div>

    <!-- Prospects List -->
    <div class="page-card">
        <div class="page-card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h2><i class="bi bi-collection-fill"></i> Follow-up Pipeline</h2>
            <a href="lead_history.php" class="btn-brand-outline btn-sm">
                <i class="bi bi-clock-history"></i> View Full History
            </a>
        </div>
        <div class="page-card-body" style="padding: 0;">
            <div class="table-wrapper">
                <table class="modern-table premium-table" id="leadsTable">
                    <thead>
                    <tr>
                        <th>Client</th>
                        <th>Service Interest</th>
                        <th>Action Required</th>
                        <th>Follow-up Date</th>
                        <th>Notes</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $res = mysqli_query($conn,"SELECT * FROM leads WHERE status='Active' ORDER BY followup_date ASC");
                    while($row = mysqli_fetch_array($res))
                    {
                        // Action Badge
                        if($row['action_type'] == 'Call Pending') {
                            $action_badge = '<span class="status-badge" style="background:#fef2f2;color:#dc2626;"><i class="bi bi-telephone-fill me-1"></i> Call Pending</span>';
                        } elseif($row['action_type'] == 'Meeting Scheduled') {
                            $action_badge = '<span class="status-badge active"><i class="bi bi-people-fill me-1"></i> Meeting</span>';
                        } else {
                            $action_badge = '<span class="status-badge" style="background:#eff6ff;color:#2563eb;"><i class="bi bi-chat-dots-fill me-1"></i> Follow-up</span>';
                        }

                        // Overdue / Today check
                        $is_overdue = strtotime($row['followup_date']) < strtotime(date('Y-m-d'));
                        $is_today = $row['followup_date'] == date('Y-m-d');

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
                    ?>
                    <tr <?php if($is_overdue) echo 'style="background-color: #fff1f2;"'; ?>>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, var(--teal-600), var(--navy-600)); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 11px; flex-shrink: 0;">
                                    <?php echo strtoupper(substr($row['client_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <span style="font-weight: 500;"><?php echo htmlspecialchars($row['client_name']); ?></span>
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
                            <?php if($is_overdue): ?>
                                <div><i class="bi bi-exclamation-circle-fill text-danger me-1"></i> <?php echo date('d M, Y', strtotime($row['followup_date'])); ?></div>
                                <span style="font-size: 10px; background: var(--danger); color: white; padding: 2px 6px; border-radius: 4px; display: inline-block; margin-top: 4px; animation: pulse 2s infinite;"><i class="bi bi-exclamation-triangle-fill"></i> OVERDUE</span>
                            <?php elseif($is_today): ?>
                                <div><i class="bi bi-clock-fill text-primary me-1"></i> Today</div>
                                <span style="font-size: 10px; background: var(--navy-600); color: white; padding: 2px 6px; border-radius: 4px; display: inline-block; margin-top: 4px;">Follow-up Today!</span>
                            <?php else: ?>
                                <div><i class="bi bi-calendar3 me-1"></i> <?php echo date('d M, Y', strtotime($row['followup_date'])); ?></div>
                                <div class="mt-1" style="font-size: 11px; color: var(--gray-400);">
                                    <?php 
                                    $diff = max(1, round((strtotime($row['followup_date']) - strtotime(date('Y-m-d'))) / 86400));
                                    echo 'in ' . $diff . ' day' . ($diff > 1 ? 's' : '');
                                    ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="font-size: 12px; color: var(--gray-600);"><?php echo htmlspecialchars($row['notes']) ?: '<span style="color:var(--gray-400);font-style:italic;">—</span>'; ?></span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 6px; align-items: center;">
                                <a href="<?php echo $wa_link; ?>" target="_blank" class="action-btn" style="background: #25D366; color: white; border-color: #25D366;" title="Send WhatsApp">
                                    <i class="bi bi-whatsapp"></i>
                                </a>
                                <a class="action-btn" href="secure_lead.php?id=<?php echo $row['id']; ?>" title="Mark as Secured" onclick="return confirm('Mark this lead as Secured/Done?');" style="background: #fef9c3; color: #ca8a04; border-color: #fde68a;">
                                    <i class="bi bi-trophy-fill"></i>
                                </a>
                                <a class="action-btn edit" href="edit_lead.php?id=<?php echo $row['id']; ?>" title="Edit">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                <a class="action-btn delete" href="delete_lead.php?id=<?php echo $row['id']; ?>" title="Delete" onclick="return confirm('Are you sure you want to delete this prospect?')">
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
