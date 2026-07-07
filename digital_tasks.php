<?php
session_start();
if(!isset($_SESSION['user'])) { header("Location: login.php"); exit; }

include 'db.php';
require_once 'auth.php';

$today = date('Y-m-d');

// Process Form Submissions
if(isset($_POST['mark_done'])) {
    $client_id = intval($_POST['client_id']);
    $designed = intval($_POST['posts_designed']);
    $published = intval($_POST['posts_published']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    
    // Check if task exists
    $check = mysqli_query($conn, "SELECT id FROM daily_tasks WHERE digital_client_id=$client_id AND task_date='$today'");
    if(mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "UPDATE daily_tasks SET status='Done', posts_designed=$designed, posts_published=$published, notes='$notes', reason=NULL WHERE digital_client_id=$client_id AND task_date='$today'");
    } else {
        mysqli_query($conn, "INSERT INTO daily_tasks (digital_client_id, task_date, status, posts_designed, posts_published, notes) VALUES ($client_id, '$today', 'Done', $designed, $published, '$notes')");
    }
    header("Location: digital_tasks.php");
    exit;
}

if(isset($_POST['mark_skipped'])) {
    $client_id = intval($_POST['client_id']);
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);
    
    $check = mysqli_query($conn, "SELECT id FROM daily_tasks WHERE digital_client_id=$client_id AND task_date='$today'");
    if(mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "UPDATE daily_tasks SET status='Skipped', reason='$reason', posts_designed=0, posts_published=0, notes='' WHERE digital_client_id=$client_id AND task_date='$today'");
    } else {
        mysqli_query($conn, "INSERT INTO daily_tasks (digital_client_id, task_date, status, reason) VALUES ($client_id, '$today', 'Skipped', '$reason')");
    }
    header("Location: digital_tasks.php");
    exit;
}

if(isset($_GET['reset_task']) && (strpos($_SESSION['role'], 'Admin') !== false || strpos($_SESSION['role'], 'Supervisor') !== false)) {
    $task_id = intval($_GET['reset_task']);
    mysqli_query($conn, "DELETE FROM daily_tasks WHERE id=$task_id");
    header("Location: digital_tasks.php");
    exit;
}

// Get metrics for today
$total_active = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM digital_clients WHERE status='Active'"));
$done_today = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM daily_tasks WHERE task_date='$today' AND status='Done'"));
$skipped_today = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM daily_tasks WHERE task_date='$today' AND status='Skipped'"));
$pending_today = $total_active - ($done_today + $skipped_today);

?>

<?php include 'header.php'; ?>

<style>
@keyframes pulse-alert-blue {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(37, 99, 235, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(37, 99, 235, 0); }
}
.pulse-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    background-color: #2563eb;
    border-radius: 50%;
    margin-right: 6px;
    animation: pulse-alert-blue 2s infinite;
}
</style>

<!-- Top Bar -->
<div class="topbar">
    <div class="topbar-left">
        <button class="mobile-toggle" onclick="openSidebar()">
            <i class="bi bi-list"></i>
        </button>
        <div class="topbar-title">
            <h1>Daily Operations Board</h1>
            <p>Track your daily design and publishing tasks</p>
        </div>
    </div>
    <div class="topbar-right">
        <?php if(strpos($_SESSION['role'], 'Admin') !== false || strpos($_SESSION['role'], 'Supervisor') !== false): ?>
        <a href="digital_clients.php" class="btn-brand-outline">
            <i class="bi bi-gear-fill"></i> Manage Clients
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="content-wrapper">
    <!-- Top Metrics -->
    <div class="grid-4 mb-4">
        <div class="metric-card">
            <div class="metric-icon navy"><i class="bi bi-person-workspace"></i></div>
            <div class="metric-label">Active Clients</div>
            <div class="metric-value"><?php echo $total_active; ?></div>
        </div>
        <div class="metric-card">
            <div class="metric-icon" style="background: var(--success-light); color: var(--success);"><i class="bi bi-check-all"></i></div>
            <div class="metric-label">Completed Today</div>
            <div class="metric-value" style="color: var(--success);"><?php echo $done_today; ?></div>
        </div>
        <div class="metric-card">
            <div class="metric-icon" style="background: var(--warning-light); color: var(--warning);"><i class="bi bi-signpost-2-fill"></i></div>
            <div class="metric-label">Skipped Today</div>
            <div class="metric-value" style="color: var(--warning);"><?php echo $skipped_today; ?></div>
        </div>
        <div class="metric-card">
            <div class="metric-icon" style="background: <?php echo $pending_today > 0 ? 'var(--danger-light)' : 'var(--gray-100)'; ?>; color: <?php echo $pending_today > 0 ? 'var(--danger)' : 'var(--gray-500)'; ?>;">
                <i class="bi bi-<?php echo $pending_today > 0 ? 'hourglass-split' : 'emoji-smile-fill'; ?>"></i>
            </div>
            <div class="metric-label">Pending Tasks</div>
            <div class="metric-value" style="color: <?php echo $pending_today > 0 ? 'var(--danger)' : 'var(--gray-500)'; ?>;"><?php echo $pending_today; ?></div>
        </div>
    </div>

    <!-- Daily Board -->
    <div class="page-card">
        <div class="page-card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h2><i class="bi bi-calendar2-check-fill"></i> Today's Tasks &mdash; <?php echo date('l, d M Y'); ?></h2>
            <a href="task_history.php" class="btn-brand-outline btn-sm">
                <i class="bi bi-clock-history"></i> Work History
            </a>
        </div>
        <div class="page-card-body" style="padding: 0;">
            <div class="table-wrapper">
                <table class="modern-table premium-table">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Platforms</th>
                            <th>Status Today</th>
                            <th>Work Details</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch all active clients and their tasks for today
                        $query = "
                            SELECT dc.*, dt.id as task_id, dt.status as task_status, dt.posts_designed, dt.posts_published, dt.reason, dt.notes 
                            FROM digital_clients dc
                            LEFT JOIN daily_tasks dt ON dc.id = dt.digital_client_id AND dt.task_date = '$today'
                            WHERE dc.status = 'Active'
                            ORDER BY 
                                CASE 
                                    WHEN dt.status IS NULL OR dt.status = 'Pending' THEN 1 
                                    ELSE 2 
                                END ASC,
                                dc.client_name ASC
                        ";
                        $res = mysqli_query($conn, $query);
                        
                        while($row = mysqli_fetch_assoc($res)):
                            $task_status = $row['task_status'] ? $row['task_status'] : 'Pending';
                        ?>
                        <tr <?php if($task_status == 'Pending') echo 'style="background-color: #f8fbff;"'; ?>>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, var(--brand-400), var(--brand-700)); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 11px;">
                                        <?php echo strtoupper(substr($row['client_name'], 0, 1)); ?>
                                    </div>
                                    <span style="font-weight: 600; color: var(--navy-800);"><?php echo htmlspecialchars($row['client_name']); ?></span>
                                </div>
                            </td>
                            <td><span class="platform-badge"><i class="bi bi-share-fill me-1"></i> <?php echo htmlspecialchars($row['platforms']); ?></span></td>
                            <td>
                                <?php if($task_status == 'Pending'): ?>
                                    <span class="status-badge" style="background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; font-weight: 700;">
                                        <span class="pulse-dot"></span> Pending
                                    </span>
                                <?php elseif($task_status == 'Done'): ?>
                                    <span class="status-badge active" style="background: var(--success-light); color: var(--success);"><i class="bi bi-check-circle-fill"></i> Done</span>
                                <?php else: ?>
                                    <span class="status-badge" style="background: var(--gray-200); color: var(--gray-600);"><i class="bi bi-skip-forward-fill"></i> Skipped</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($task_status == 'Pending'): ?>
                                    <span style="color: var(--gray-400); font-style: italic; font-size: 13px;">Waiting for update...</span>
                                <?php elseif($task_status == 'Done'): ?>
                                    <div style="font-size: 13px; color: var(--navy-800);">
                                        <i class="bi bi-palette-fill text-brand me-1"></i> Designed: <b><?php echo $row['posts_designed']; ?></b>
                                        <span class="mx-2 text-gray-300">|</span>
                                        <i class="bi bi-send-fill text-brand me-1"></i> Published: <b><?php echo $row['posts_published']; ?></b>
                                    </div>
                                    <?php if($row['notes']): ?>
                                        <div style="font-size: 11px; color: var(--gray-500); margin-top: 4px;"><i class="bi bi-chat-text me-1"></i> <?php echo htmlspecialchars($row['notes']); ?></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div style="font-size: 13px; color: var(--gray-600);">
                                        <i class="bi bi-info-circle-fill me-1"></i> Reason: <b><?php echo htmlspecialchars($row['reason']); ?></b>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($task_status == 'Pending'): ?>
                                    <div style="display: flex; gap: 8px;">
                                        <button class="btn btn-sm btn-success" style="font-size: 12px; font-weight: 600; padding: 4px 12px; border-radius: 4px; border: none;" onclick="openDoneModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['client_name'])); ?>')">
                                            <i class="bi bi-check2"></i> Mark Done
                                        </button>
                                        <button class="btn btn-sm btn-light" style="font-size: 12px; font-weight: 600; padding: 4px 12px; border-radius: 4px; border: 1px solid var(--gray-300); color: var(--gray-600);" onclick="openSkipModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['client_name'])); ?>')">
                                            <i class="bi bi-skip-forward"></i> Skip
                                        </button>
                                    </div>
                                <?php elseif($task_status == 'Done'): ?>
                                    <div style="display: flex; align-items: center; justify-content: space-between;">
                                        <div style="color: var(--success); font-weight: 700; font-size: 13px; display: flex; align-items: center; gap: 6px;">
                                            <i class="bi bi-check-circle-fill" style="font-size: 16px;"></i> Today's work completed
                                        </div>
                                        <?php if(strpos($_SESSION['role'], 'Admin') !== false || strpos($_SESSION['role'], 'Supervisor') !== false): ?>
                                            <a href="digital_tasks.php?reset_task=<?php echo $row['task_id']; ?>" class="btn btn-sm btn-outline-danger" style="font-size: 11px; padding: 2px 6px;" title="Admin Reset" onclick="return confirm('Reset this task for today?')"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div style="display: flex; align-items: center; justify-content: space-between;">
                                        <div style="color: var(--gray-500); font-weight: 600; font-size: 13px; display: flex; align-items: center; gap: 6px;">
                                            <i class="bi bi-skip-forward-fill" style="font-size: 16px;"></i> Skipped for today
                                        </div>
                                        <?php if(strpos($_SESSION['role'], 'Admin') !== false || strpos($_SESSION['role'], 'Supervisor') !== false): ?>
                                            <a href="digital_tasks.php?reset_task=<?php echo $row['task_id']; ?>" class="btn btn-sm btn-outline-danger" style="font-size: 11px; padding: 2px 6px;" title="Admin Reset" onclick="return confirm('Reset this task for today?')"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        
                        <?php if(mysqli_num_rows($res) == 0): ?>
                        <tr><td colspan="5" class="text-center py-4" style="color: var(--gray-500);">No active digital clients. <a href="digital_clients.php">Add one here.</a></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->

<!-- Done Modal -->
<div class="modal fade" id="doneModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border: none; border-radius: var(--radius-md); box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
            <div class="modal-header" style="background: var(--brand-50); border-bottom: 1px solid var(--brand-100); border-radius: var(--radius-md) var(--radius-md) 0 0;">
                <h5 class="modal-title" style="color: var(--navy-800); font-weight: 700;"><i class="bi bi-check-circle-fill text-success me-2"></i> Mark Task Done</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body" style="padding: 24px;">
                    <div class="mb-3">
                        <label class="form-label text-gray-500" style="font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Client</label>
                        <div id="doneClientName" style="font-weight: 700; font-size: 18px; color: var(--navy-800);"></div>
                        <input type="hidden" name="client_id" id="doneClientId">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label" style="font-weight: 600; color: var(--navy-800);"><i class="bi bi-palette text-brand me-1"></i> Posts Designed</label>
                            <input type="number" name="posts_designed" class="form-control" value="0" min="0" required style="border-radius: var(--radius-sm); border: 1px solid var(--gray-300);">
                        </div>
                        <div class="col-6">
                            <label class="form-label" style="font-weight: 600; color: var(--navy-800);"><i class="bi bi-send text-brand me-1"></i> Posts Published</label>
                            <input type="number" name="posts_published" class="form-control" value="0" min="0" required style="border-radius: var(--radius-sm); border: 1px solid var(--gray-300);">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 600; color: var(--navy-800);"><i class="bi bi-journal-text text-brand me-1"></i> Additional Notes</label>
                        <input type="text" name="notes" class="form-control" placeholder="E.g. Reels scheduled for tomorrow..." style="border-radius: var(--radius-sm); border: 1px solid var(--gray-300);">
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid var(--gray-200); padding: 16px 24px;">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="font-weight: 600; border-radius: var(--radius-sm);">Cancel</button>
                    <button type="submit" name="mark_done" class="btn btn-success" style="font-weight: 600; border-radius: var(--radius-sm); border: none;">Save & Mark Done</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Skip Modal -->
<div class="modal fade" id="skipModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border: none; border-radius: var(--radius-md); box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
            <div class="modal-header" style="background: var(--gray-50); border-bottom: 1px solid var(--gray-200); border-radius: var(--radius-md) var(--radius-md) 0 0;">
                <h5 class="modal-title" style="color: var(--navy-800); font-weight: 700;"><i class="bi bi-skip-forward-fill text-gray-500 me-2"></i> Skip Today's Work</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body" style="padding: 24px;">
                    <div class="mb-3">
                        <label class="form-label text-gray-500" style="font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Client</label>
                        <div id="skipClientName" style="font-weight: 700; font-size: 18px; color: var(--navy-800);"></div>
                        <input type="hidden" name="client_id" id="skipClientId">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 600; color: var(--navy-800);"><i class="bi bi-question-circle-fill text-warning me-1"></i> Reason for Skipping</label>
                        <select name="reason" class="form-select" required style="border-radius: var(--radius-sm); border: 1px solid var(--gray-300);">
                            <option value="">Select a reason...</option>
                            <option value="Saturday/Sunday Off">Saturday/Sunday Off</option>
                            <option value="Event / Public Holiday">Event / Public Holiday</option>
                            <option value="Client Requested Pause">Client Requested Pause</option>
                            <option value="Waiting for Approvals">Waiting for Approvals</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid var(--gray-200); padding: 16px 24px;">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="font-weight: 600; border-radius: var(--radius-sm);">Cancel</button>
                    <button type="submit" name="mark_skipped" class="btn btn-warning" style="font-weight: 600; border-radius: var(--radius-sm); border: none; color: white;">Confirm Skip</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openDoneModal(clientId, clientName) {
    document.getElementById('doneClientId').value = clientId;
    document.getElementById('doneClientName').innerText = clientName;
    new bootstrap.Modal(document.getElementById('doneModal')).show();
}

function openSkipModal(clientId, clientName) {
    document.getElementById('skipClientId').value = clientId;
    document.getElementById('skipClientName').innerText = clientName;
    new bootstrap.Modal(document.getElementById('skipModal')).show();
}
</script>

<?php include 'footer.php'; ?>
