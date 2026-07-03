<?php
session_start();
if(!isset($_SESSION['user'])) { header("Location: login.php"); exit; }
include 'db.php';
require_once 'auth.php';

// Admin only
$roles_arr = array_map('trim', explode(',', $_SESSION['role'] ?? ''));
if(!in_array('Admin', $roles_arr)) {
    $_SESSION['access_denied'] = "Only Admins can access User Management.";
    header("Location: dashboard.php"); exit;
}

$users = mysqli_query($conn, "SELECT * FROM users ORDER BY id ASC");
?>

<?php include 'header.php'; ?>

<!-- Top Bar -->
<div class="topbar">
    <div class="topbar-left">
        <button class="mobile-toggle" onclick="openSidebar()"><i class="bi bi-list"></i></button>
        <div class="topbar-title">
            <h1>User Management</h1>
            <p>Manage team members and their access roles</p>
        </div>
    </div>
    <div class="topbar-right">
        <a href="add_user.php" class="btn-brand">
            <i class="bi bi-person-plus-fill"></i> Add Team Member
        </a>
    </div>
</div>

<div class="content-wrapper">
    <div class="page-card">
        <div class="page-card-header">
            <h2><i class="bi bi-people-fill"></i> Team Members</h2>
        </div>
        <div class="page-card-body" style="padding: 0;">
            <div class="table-wrapper">
                <table class="modern-table">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php while($u = mysqli_fetch_assoc($users)): ?>
                    <tr>
                        <td style="font-weight:600;">#<?php echo $u['id']; ?></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--teal-600),var(--navy-600));display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:13px;flex-shrink:0;">
                                    <?php echo strtoupper(substr($u['name'], 0, 1)); ?>
                                </div>
                                <span style="font-weight:600;color:var(--navy-800);"><?php echo htmlspecialchars($u['name']); ?></span>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td>
                            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                            <?php
                            $roles_str = $u['role'] ?? 'Admin';
                            $user_roles = array_map('trim', explode(',', $roles_str));
                            
                            $roleColors = [
                                'Admin'            => 'background:#dbeafe;color:#1d4ed8;',
                                'Supervisor'       => 'background:#e0f2fe;color:#0369a1;',
                                'Campaign Manager' => 'background:#ede9fe;color:#7c3aed;',
                                'Finance Manager'  => 'background:#dcfce7;color:#15803d;',
                                'Client Manager'   => 'background:#fef3c7;color:#b45309;',
                                'Staff'            => 'background:#f1f5f9;color:#475569;',
                            ];
                            
                            foreach($user_roles as $r):
                                $style = $roleColors[$r] ?? 'background:var(--gray-100);color:var(--gray-700);';
                            ?>
                                <span style="padding:4px 10px;border-radius:20px;font-size:11px;font-weight:600;<?php echo $style; ?>">
                                    <?php echo htmlspecialchars($r); ?>
                                </span>
                            <?php endforeach; ?>
                            </div>
                        </td>
                        <td>
                            <div style="display:flex;gap:6px;">
                                <a class="action-btn edit" href="edit_user.php?id=<?php echo $u['id']; ?>" title="Edit">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                <?php if($u['email'] !== $_SESSION['user']): // Can't delete yourself ?>
                                <a class="action-btn delete" href="delete_user.php?id=<?php echo $u['id']; ?>"
                                   onclick="return confirm('Are you sure you want to delete this user?')" title="Delete">
                                    <i class="bi bi-trash-fill"></i>
                                </a>
                                <?php endif; ?>
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
