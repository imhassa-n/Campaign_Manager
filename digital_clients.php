<?php
session_start();
if(!isset($_SESSION['user'])) { header("Location: login.php"); exit; }

include 'db.php';
require_once 'auth.php';

if(isset($_POST['save_client'])) {
    $name = mysqli_real_escape_string($conn, $_POST['client_name']);
    $platforms = mysqli_real_escape_string($conn, $_POST['platforms']);
    mysqli_query($conn, "INSERT INTO digital_clients (client_name, platforms) VALUES ('$name', '$platforms')");
    header("Location: digital_clients.php");
    exit;
}

if(isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM digital_clients WHERE id=$id");
    header("Location: digital_clients.php");
    exit;
}

if(isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $status = $_GET['status'] == 'Active' ? 'Inactive' : 'Active';
    mysqli_query($conn, "UPDATE digital_clients SET status='$status' WHERE id=$id");
    header("Location: digital_clients.php");
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
            <h1>Digital Marketing Clients</h1>
            <p>Manage clients for daily social media operations</p>
        </div>
    </div>
</div>

<div class="content-wrapper">
    <!-- Add Client Form -->
    <div class="page-card mb-4 glass-panel" style="background: white;">
        <div class="page-card-header">
            <h2><i class="bi bi-person-plus-fill"></i> Add Digital Client</h2>
        </div>
        <div class="page-card-body">
            <form method="POST">
                <div class="row align-items-end">
                    <div class="col-md-5">
                        <div class="form-section">
                            <label class="form-label">Client Name</label>
                            <input type="text" name="client_name" class="form-control" placeholder="E.g. WebDex Agency" required>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-section">
                            <label class="form-label">Platforms</label>
                            <input type="text" name="platforms" class="form-control" placeholder="E.g. FB, Insta, LinkedIn" required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-section">
                            <button type="submit" name="save_client" class="btn-brand w-100">
                                <i class="bi bi-plus-circle-fill"></i> Add
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Clients List -->
    <div class="page-card">
        <div class="page-card-header">
            <h2><i class="bi bi-people-fill"></i> Managed Clients</h2>
        </div>
        <div class="page-card-body" style="padding: 0;">
            <div class="table-wrapper">
                <table class="modern-table premium-table">
                    <thead>
                        <tr>
                            <th>Client Name</th>
                            <th>Platforms Managed</th>
                            <th>Status</th>
                            <th>Added On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $res = mysqli_query($conn, "SELECT * FROM digital_clients ORDER BY status ASC, created_at DESC");
                        while($row = mysqli_fetch_assoc($res)):
                            $is_active = $row['status'] == 'Active';
                        ?>
                        <tr <?php if(!$is_active) echo 'style="opacity: 0.6; background: var(--gray-50);"'; ?>>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, var(--brand-400), var(--brand-700)); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 11px;">
                                        <?php echo strtoupper(substr($row['client_name'], 0, 1)); ?>
                                    </div>
                                    <span style="font-weight: 600; color: var(--navy-800);"><?php echo htmlspecialchars($row['client_name']); ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="platform-badge"><i class="bi bi-share-fill me-1"></i> <?php echo htmlspecialchars($row['platforms']); ?></span>
                            </td>
                            <td>
                                <?php if($is_active): ?>
                                    <span class="status-badge active"><i class="bi bi-check-circle-fill"></i> Active</span>
                                <?php else: ?>
                                    <span class="status-badge" style="background: var(--gray-200); color: var(--gray-600);"><i class="bi bi-dash-circle-fill"></i> Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td style="color: var(--gray-600); font-size: 13px;">
                                <?php echo date('d M, Y', strtotime($row['created_at'])); ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px;">
                                    <a href="digital_clients.php?toggle=<?php echo $row['id']; ?>&status=<?php echo $row['status']; ?>" class="btn btn-sm <?php echo $is_active ? 'btn-outline-warning' : 'btn-outline-success'; ?>" title="<?php echo $is_active ? 'Pause Work' : 'Resume Work'; ?>" style="padding: 4px 8px;">
                                        <i class="bi <?php echo $is_active ? 'bi-pause-circle-fill' : 'bi-play-circle-fill'; ?>"></i>
                                    </a>
                                    <a href="digital_clients.php?delete=<?php echo $row['id']; ?>" class="action-btn delete" title="Delete" onclick="return confirm('Permanently delete this client and all their task history?')">
                                        <i class="bi bi-trash-fill"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
