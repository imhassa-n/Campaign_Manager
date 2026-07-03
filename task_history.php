<?php
session_start();
if(!isset($_SESSION['user'])) { header("Location: login.php"); exit; }

include 'db.php';
require_once 'auth.php';

// Filter
$date_filter = isset($_GET['date']) ? mysqli_real_escape_string($conn, $_GET['date']) : '';
$client_filter = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

$query = "SELECT dt.*, dc.client_name, dc.platforms FROM daily_tasks dt LEFT JOIN digital_clients dc ON dt.digital_client_id = dc.id WHERE 1=1";

if($date_filter) {
    $query .= " AND dt.task_date = '$date_filter'";
}
if($client_filter > 0) {
    $query .= " AND dt.digital_client_id = $client_filter";
}
if($status_filter) {
    $query .= " AND dt.status = '$status_filter'";
}

$query .= " ORDER BY dt.task_date DESC, dc.client_name ASC";
$res = mysqli_query($conn, $query);

$clients = mysqli_query($conn, "SELECT id, client_name FROM digital_clients ORDER BY client_name ASC");

if(isset($_GET['delete_task']) && $_SESSION['role'] === 'Admin') {
    $del_id = intval($_GET['delete_task']);
    mysqli_query($conn, "DELETE FROM daily_tasks WHERE id=$del_id");
    header("Location: task_history.php");
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
            <h1>Task History</h1>
            <p>Archive of all digital marketing operations</p>
        </div>
    </div>
    <div class="topbar-right">
        <a href="digital_tasks.php" class="btn-brand-outline">
            <i class="bi bi-arrow-left"></i> Back to Daily Board
        </a>
    </div>
</div>

<div class="content-wrapper">

    <!-- Filters -->
    <div class="page-card mb-4" style="background: white;">
        <div class="page-card-body">
            <form method="GET" class="row align-items-end">
                <div class="col-md-3">
                    <label class="form-label" style="font-weight: 600; color: var(--navy-800);">Filter by Date</label>
                    <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($date_filter); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label" style="font-weight: 600; color: var(--navy-800);">Filter by Client</label>
                    <select name="client_id" class="form-select">
                        <option value="">All Clients</option>
                        <?php while($c = mysqli_fetch_assoc($clients)): ?>
                            <option value="<?php echo $c['id']; ?>" <?php if($client_filter == $c['id']) echo 'selected'; ?>><?php echo htmlspecialchars($c['client_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" style="font-weight: 600; color: var(--navy-800);">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="Done" <?php if($status_filter == 'Done') echo 'selected'; ?>>Done</option>
                        <option value="Skipped" <?php if($status_filter == 'Skipped') echo 'selected'; ?>>Skipped</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <div style="display: flex; gap: 8px;">
                        <button type="submit" class="btn-brand w-100">Filter</button>
                        <a href="task_history.php" class="btn btn-light" style="border: 1px solid var(--gray-300);" title="Clear"><i class="bi bi-x-lg"></i></a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- History Table -->
    <div class="page-card">
        <div class="page-card-header">
            <h2><i class="bi bi-archive-fill"></i> Task Logs</h2>
        </div>
        <div class="page-card-body" style="padding: 0;">
            <div class="table-wrapper">
                <table class="modern-table premium-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Client</th>
                            <th>Status</th>
                            <th>Details</th>
                            <th>Logged At</th>
                            <?php if($_SESSION['role'] === 'Admin'): ?>
                            <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        while($row = mysqli_fetch_assoc($res)):
                        ?>
                        <tr>
                            <td style="font-weight: 600; color: var(--navy-800);">
                                <?php echo date('d M Y', strtotime($row['task_date'])); ?>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 28px; height: 28px; border-radius: 50%; background: linear-gradient(135deg, var(--brand-400), var(--brand-700)); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 10px;">
                                        <?php echo strtoupper(substr($row['client_name'], 0, 1)); ?>
                                    </div>
                                    <span style="font-weight: 600; color: var(--navy-800);"><?php echo htmlspecialchars($row['client_name']); ?></span>
                                </div>
                            </td>
                            <td>
                                <?php if($row['status'] == 'Done'): ?>
                                    <span class="status-badge active" style="background: var(--success-light); color: var(--success);"><i class="bi bi-check-circle-fill"></i> Done</span>
                                <?php else: ?>
                                    <span class="status-badge" style="background: var(--gray-200); color: var(--gray-600);"><i class="bi bi-skip-forward-fill"></i> Skipped</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($row['status'] == 'Done'): ?>
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
                            <td style="color: var(--gray-500); font-size: 12px;">
                                <?php echo date('h:i A', strtotime($row['updated_at'])); ?>
                            </td>
                            <?php if($_SESSION['role'] === 'Admin'): ?>
                            <td>
                                <a href="task_history.php?delete_task=<?php echo $row['id']; ?>" class="action-btn delete" title="Delete Task Log" onclick="return confirm('Delete this past log permanently?')">
                                    <i class="bi bi-trash-fill"></i>
                                </a>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endwhile; ?>
                        
                        <?php if(mysqli_num_rows($res) == 0): ?>
                        <tr><td colspan="<?php echo $_SESSION['role'] === 'Admin' ? '6' : '5'; ?>" class="text-center py-4" style="color: var(--gray-500);">No tasks found for the selected filters.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
