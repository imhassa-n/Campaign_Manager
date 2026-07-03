<?php
session_start();
if(!isset($_SESSION['user'])) { header("Location: login.php"); exit; }
include 'db.php';
require_once 'auth.php';

$roles_arr = array_map('trim', explode(',', $_SESSION['role'] ?? ''));
if(!in_array('Admin', $roles_arr)) {
    $_SESSION['access_denied'] = "Only Admins can create users.";
    header("Location: dashboard.php"); exit;
}

$error = '';
$success = '';

if(isset($_POST['save'])) {
    $name     = mysqli_real_escape_string($conn, trim($_POST['name']));
    $email    = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = mysqli_real_escape_string($conn, trim($_POST['password']));
    $roles    = $_POST['roles'] ?? [];
    $role_str = implode(', ', $roles);

    $perms = [
        'can_invoice_campaigns' => isset($_POST['perm_invoice_campaigns']),
        'can_invoice_monthly_clients' => isset($_POST['perm_invoice_monthly_clients']),
        'can_invoice_web_projects' => isset($_POST['perm_invoice_web_projects']),
        'can_view_income' => isset($_POST['perm_view_income']),
        'can_send_whatsapp' => isset($_POST['perm_send_whatsapp'])
    ];
    $extra_permissions_json = mysqli_real_escape_string($conn, json_encode($perms));

    // Check if email already exists
    $check = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");
    if(mysqli_num_rows($check) > 0) {
        $error = "A user with this email already exists.";
    } elseif(empty($name) || empty($email) || empty($password)) {
        $error = "Name, email, and password are required.";
    } elseif(empty($roles)) {
        $error = "Please select at least one role.";
    } else {
        mysqli_query($conn, "INSERT INTO users (name, email, password, role, extra_permissions) VALUES ('$name', '$email', '$password', '$role_str', '$extra_permissions_json')");
        header("Location: users.php");
        exit;
    }
}
?>

<?php include 'header.php'; ?>

<div class="topbar">
    <div class="topbar-left">
        <button class="mobile-toggle" onclick="openSidebar()"><i class="bi bi-list"></i></button>
        <div class="topbar-title">
            <h1>Add Team Member</h1>
            <p>Create a new user account and assign a role</p>
        </div>
    </div>
    <div class="topbar-right">
        <a href="users.php" class="btn-brand-outline"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
</div>

<div class="content-wrapper">
    <div class="page-card" style="max-width: 640px; margin: 0 auto;">
        <div class="page-card-header">
            <h2><i class="bi bi-person-plus-fill"></i> New Team Member</h2>
        </div>
        <div class="page-card-body">
            <?php if($error): ?>
            <div style="background:#fef2f2;border-left:4px solid #ef4444;color:#991b1b;padding:12px 16px;border-radius:6px;margin-bottom:16px;font-weight:600;">
                <i class="bi bi-exclamation-circle-fill me-2"></i><?php echo $error; ?>
            </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-section">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" placeholder="Enter full name" required>
                </div>
                <div class="form-section">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="Enter email address" required>
                </div>
                <div class="form-section">
                    <label class="form-label">Password</label>
                    <input type="text" name="password" class="form-control" placeholder="Set a password" required>
                </div>
                <div class="form-section">
                    <label class="form-label">Assign Roles (Select multiple if needed)</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; background: #f8fafc; padding: 16px; border-radius: 8px; border: 1px solid var(--gray-200);">
                        <label style="display: flex; align-items: flex-start; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="roles[]" value="Admin" style="margin-top: 4px;">
                            <div>
                                <div style="font-weight: 600; font-size: 14px; color: var(--navy-800);">Admin</div>
                                <div style="font-size: 12px; color: var(--gray-500);">Full Access to everything</div>
                            </div>
                        </label>
                        <label style="display: flex; align-items: flex-start; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="roles[]" value="Supervisor" style="margin-top: 4px;">
                            <div>
                                <div style="font-weight: 600; font-size: 14px; color: var(--navy-800);">Supervisor</div>
                                <div style="font-size: 12px; color: var(--gray-500);">Everything EXCEPT Finances</div>
                            </div>
                        </label>
                        <label style="display: flex; align-items: flex-start; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="roles[]" value="Campaign Manager" style="margin-top: 4px;">
                            <div>
                                <div style="font-weight: 600; font-size: 14px; color: var(--navy-800);">Campaign Manager</div>
                                <div style="font-size: 12px; color: var(--gray-500);">Campaigns & Web Projects</div>
                            </div>
                        </label>
                        <label style="display: flex; align-items: flex-start; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="roles[]" value="Finance Manager" style="margin-top: 4px;">
                            <div>
                                <div style="font-weight: 600; font-size: 14px; color: var(--navy-800);">Finance Manager</div>
                                <div style="font-size: 12px; color: var(--gray-500);">Payments & Expenses</div>
                            </div>
                        </label>
                        <label style="display: flex; align-items: flex-start; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="roles[]" value="Client Manager" style="margin-top: 4px;">
                            <div>
                                <div style="font-weight: 600; font-size: 14px; color: var(--navy-800);">Client Manager</div>
                                <div style="font-size: 12px; color: var(--gray-500);">Clients, Retainers & Follow-ups</div>
                            </div>
                        </label>
                        <label style="display: flex; align-items: flex-start; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="roles[]" value="Staff" style="margin-top: 4px;">
                            <div>
                                <div style="font-weight: 600; font-size: 14px; color: var(--navy-800);">Staff</div>
                                <div style="font-size: 12px; color: var(--gray-500);">Daily Tasks Board Only</div>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="form-section mt-4" style="margin-top: 24px;">
                    <label class="form-label" style="color:var(--brand-primary);"><i class="bi bi-shield-lock-fill"></i> Advanced Feature Permissions</label>
                    <div style="font-size: 13px; color: var(--gray-500); margin-bottom: 12px;">These permissions apply to regular users. Admins have all permissions enabled by default.</div>
                    <div style="display: grid; grid-template-columns: 1fr; gap: 12px; background: #fff; padding: 16px; border-radius: 8px; border: 1px solid var(--gray-200);">
                        <label style="display: flex; align-items: flex-start; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="perm_invoice_campaigns" style="margin-top: 4px;" checked>
                            <div>
                                <div style="font-weight: 600; font-size: 14px; color: var(--navy-800);">Can Invoice Ad Campaigns</div>
                                <div style="font-size: 12px; color: var(--gray-500);">Allow generating invoices for standard ad campaigns.</div>
                            </div>
                        </label>
                        <label style="display: flex; align-items: flex-start; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="perm_invoice_monthly_clients" style="margin-top: 4px;" checked>
                            <div>
                                <div style="font-weight: 600; font-size: 14px; color: var(--navy-800);">Can Invoice Monthly Clients</div>
                                <div style="font-size: 12px; color: var(--gray-500);">Allow generating invoices for retainers / monthly services.</div>
                            </div>
                        </label>
                        <label style="display: flex; align-items: flex-start; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="perm_invoice_web_projects" style="margin-top: 4px;" checked>
                            <div>
                                <div style="font-weight: 600; font-size: 14px; color: var(--navy-800);">Can Invoice Web Projects</div>
                                <div style="font-size: 12px; color: var(--gray-500);">Allow generating invoices for website development projects.</div>
                            </div>
                        </label>
                        <label style="display: flex; align-items: flex-start; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="perm_view_income" style="margin-top: 4px;" checked>
                            <div>
                                <div style="font-weight: 600; font-size: 14px; color: var(--navy-800);">Can View Total Income/Profit</div>
                                <div style="font-size: 12px; color: var(--gray-500);">Allow viewing the Profitability & Expense Analytics on the dashboard.</div>
                            </div>
                        </label>
                        <label style="display: flex; align-items: flex-start; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="perm_send_whatsapp" style="margin-top: 4px;" checked>
                            <div>
                                <div style="font-weight: 600; font-size: 14px; color: var(--navy-800);">Can Send WhatsApp Reminders</div>
                                <div style="font-size: 12px; color: var(--gray-500);">Allow using the 1-click WhatsApp buttons for payment reminders.</div>
                            </div>
                        </label>
                    </div>
                </div>
                <div style="display:flex;gap:12px;margin-top:8px;">
                    <button type="submit" name="save" class="btn-brand">
                        <i class="bi bi-plus-circle-fill"></i> Create User
                    </button>
                    <a href="users.php" class="btn-back"><i class="bi bi-x-circle"></i> Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
