<?php
// Determine current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
if(file_exists(__DIR__ . '/auth.php')) require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="WebDex Campaign Manager Pro — Professional Campaign & Client Management Dashboard">

    <title>
        <?php
        $titles = [
            'dashboard.php' => 'Dashboard',
            'clients.php' => 'Clients',
            'campaigns.php' => 'Campaigns',
            'payments.php' => 'Payments',
            'expenses.php' => 'Expenses',
            'edit_client.php' => 'Edit Client',
            'edit_campaign.php' => 'Edit Campaign',
            'edit_payment.php' => 'Edit Payment',
            'edit_expense.php' => 'Edit Expense',
            'leads.php' => 'Follow-ups',
            'edit_lead.php' => 'Edit Follow-up',
            'lead_history.php' => 'Follow-up History',
            'digital_tasks.php' => 'Daily Tasks',
            'digital_clients.php' => 'Digital Clients',
            'task_history.php' => 'Task History',
        ];
        echo (isset($titles[$current_page]) ? $titles[$current_page] . ' — ' : '') . 'WebDex Campaign Manager';
        ?>
    </title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- WebDex Custom Styles -->
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>">
    <link rel="stylesheet" href="premium.css?v=<?php echo filemtime(__DIR__ . '/premium.css'); ?>">
</head>
<body>

<!-- Sidebar Overlay (Mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- Sidebar Navigation -->
<aside class="sidebar" id="sidebar">

    <!-- Brand -->
    <a href="dashboard.php" class="sidebar-brand" style="flex-direction: column; justify-content: center; padding: 24px 16px; gap: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); margin-bottom: 10px;">
        <img src="assets/logo.png" alt="WebDex Logo" style="max-width: 130px; height: auto; object-fit: contain; filter: brightness(0) invert(1);">
        <div style="font-size: 10px; font-weight: 700; letter-spacing: 2.5px; color: #4ecdc4; text-transform: uppercase; background: rgba(78, 205, 196, 0.1); padding: 6px 12px; border-radius: 20px; border: 1px solid rgba(78, 205, 196, 0.2);">
            Campaign Manager
        </div>
    </a>

    <!-- Menu -->
    <nav class="sidebar-menu">

        <span class="sidebar-menu-label">Main Menu</span>

        <a href="dashboard.php"
           class="sidebar-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
            <i class="bi bi-grid-1x2-fill"></i>
            Dashboard
        </a>

        <?php if(can('clients')): ?>
        <a href="clients.php"
           class="sidebar-link <?php echo ($current_page == 'clients.php' || $current_page == 'edit_client.php' || $current_page == 'view_client.php') ? 'active' : ''; ?>">
            <i class="bi bi-people-fill"></i>
            Clients
        </a>
        <?php endif; ?>

        <?php if(can('leads')): ?>
        <a href="leads.php"
           class="sidebar-link <?php echo ($current_page == 'leads.php' || $current_page == 'edit_lead.php' || $current_page == 'lead_history.php') ? 'active' : ''; ?>">
            <i class="bi bi-bullseye"></i>
            Follow-ups
        </a>
        <?php endif; ?>

        <?php if(can('digital_tasks')): ?>
        <a href="digital_tasks.php"
           class="sidebar-link <?php echo (in_array($current_page, ['digital_tasks.php', 'digital_clients.php', 'task_history.php'])) ? 'active' : ''; ?>">
            <i class="bi bi-calendar2-check-fill"></i>
            Daily Tasks
        </a>
        <?php endif; ?>

        <?php if(can('campaigns')): ?>
        <a href="campaigns.php"
           class="sidebar-link <?php echo ($current_page == 'campaigns.php' || $current_page == 'edit_campaign.php') ? 'active' : ''; ?>">
            <i class="bi bi-megaphone-fill"></i>
            Campaigns
        </a>
        <?php endif; ?>

        <?php if(can('retainers')): ?>
        <a href="retainers.php"
           class="sidebar-link <?php echo ($current_page == 'retainers.php' || $current_page == 'edit_retainer.php') ? 'active' : ''; ?>">
            <i class="bi bi-person-workspace"></i>
            Monthly Clients
        </a>
        <?php endif; ?>

        <span class="sidebar-menu-label">Websites</span>

        <?php if(can('web_projects')): ?>
        <a href="web_projects.php"
           class="sidebar-link <?php echo ($current_page == 'web_projects.php' || $current_page == 'edit_web_project.php') ? 'active' : ''; ?>">
            <i class="bi bi-laptop"></i>
            Web Projects
        </a>
        <?php endif; ?>

        <span class="sidebar-menu-label">Finance</span>

        <?php if(can('payments')): ?>
        <a href="payments.php"
           class="sidebar-link <?php echo ($current_page == 'payments.php' || $current_page == 'edit_payment.php') ? 'active' : ''; ?>">
            <i class="bi bi-credit-card-fill"></i>
            Payments
        </a>
        <a href="payment_history.php"
           class="sidebar-link <?php echo ($current_page == 'payment_history.php') ? 'active' : ''; ?>">
            <i class="bi bi-clock-history"></i>
            Payment History
        </a>
        <?php endif; ?>

        <?php if(can('expenses')): ?>
        <a href="expenses.php"
           class="sidebar-link <?php echo ($current_page == 'expenses.php' || $current_page == 'edit_expense.php') ? 'active' : ''; ?>">
            <i class="bi bi-wallet2"></i>
            Expenses
        </a>
        <?php endif; ?>

        <?php if(isset($_SESSION['role']) && in_array('Admin', array_map('trim', explode(',', $_SESSION['role'])))): ?>
        <span class="sidebar-menu-label">Admin</span>
        <a href="users.php"
           class="sidebar-link <?php echo ($current_page == 'users.php' || $current_page == 'add_user.php' || $current_page == 'edit_user.php') ? 'active' : ''; ?>">
            <i class="bi bi-shield-lock-fill"></i>
            User Management
        </a>
        <?php endif; ?>

    </nav>

    <!-- User Footer -->
    <div class="sidebar-footer">
        <div class="sidebar-user">
                <div class="sidebar-user-avatar">
                    <?php echo strtoupper(substr($_SESSION['user_name'] ?? $_SESSION['user'], 0, 1)); ?>
                </div>
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name"><?php echo $_SESSION['user_name'] ?? $_SESSION['user']; ?></div>
                    <div class="sidebar-user-role" style="display: flex; align-items: center; gap: 5px;">
                        <?php 
                            $roleText = htmlspecialchars($_SESSION['role'] ?? 'User');
                            $isAdmin = isset($_SESSION['role']) && in_array('Admin', array_map('trim', explode(',', $_SESSION['role'])));
                        ?>
                        <span style="width: 6px; height: 6px; border-radius: 50%; background: <?php echo $isAdmin ? '#10b981' : '#f59e0b'; ?>; display:inline-block;"></span>
                        <?php echo $roleText; ?>
                    </div>
                </div>
            <a href="logout.php" class="sidebar-logout" title="Logout">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>

</aside>

<!-- Main Content -->
<div class="main-content">
<?php if(isset($_SESSION['access_denied'])): ?>
<div style="background:#fef2f2;border-left:4px solid #ef4444;color:#991b1b;padding:12px 20px;font-weight:600;font-size:14px;display:flex;align-items:center;gap:10px;">
    <i class="bi bi-shield-exclamation"></i> <?php echo htmlspecialchars($_SESSION['access_denied']); unset($_SESSION['access_denied']); ?>
</div>
<?php endif; ?>
