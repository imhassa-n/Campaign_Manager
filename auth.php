<?php
/**
 * auth.php
 * Central Role-Based Access Control helper.
 * Include this at the top of any protected page.
 *
 * Usage:
 *   require_once 'auth.php';
 *   require_permission('campaigns'); // 'view' assumed
 *   require_permission('payments', 'edit');
 */

// ─── Permission Map ────────────────────────────────────────────────────────────
// Format: 'role' => [ 'module' => ['view','edit','delete'] ]
// Admin inherits everything, so not listed here.

$PERMISSIONS = [
    'Campaign Manager' => [
        'dashboard'     => ['view'],
        'campaigns'     => ['view','edit','delete'],
        'web_projects'  => ['view','edit','delete'],
    ],
    'Finance Manager' => [
        'dashboard'     => ['view'],
        'clients'       => ['view'],
        'payments'      => ['view','edit','delete'],
        'expenses'      => ['view','edit','delete'],
    ],
    'Client Manager' => [
        'dashboard'     => ['view'],
        'clients'       => ['view','edit','delete'],
        'retainers'     => ['view','edit','delete'],
        'leads'         => ['view','edit','delete'],
    ],
    'Staff' => [
        'digital_tasks' => ['view','edit'],
    ],
    'Supervisor' => [
        'dashboard'     => ['view'],
        'clients'       => ['view','edit','delete'],
        'campaigns'     => ['view','edit','delete'],
        'web_projects'  => ['view','edit','delete'],
        'retainers'     => ['view','edit','delete'],
        'leads'         => ['view','edit','delete'],
        'digital_tasks' => ['view','edit','delete'],
    ]
];

// ─── Helpers ──────────────────────────────────────────────────────────────────

/**
 * Returns true if the logged-in user can perform $action on $module.
 */
function can(string $module, string $action = 'view'): bool
{
    global $PERMISSIONS;

    if (!isset($_SESSION['user'])) return false;
    $roles_str = $_SESSION['role'] ?? 'Admin';
    
    $roles = array_map('trim', explode(',', $roles_str));

    // Admin can do everything
    if (in_array('Admin', $roles)) return true;

    foreach($roles as $role) {
        if (isset($PERMISSIONS[$role][$module]) && in_array($action, $PERMISSIONS[$role][$module])) {
            return true;
        }
    }

    return false;
}

/**
 * Returns true if the user has a specific granular permission enabled.
 * Admins automatically return true.
 */
function has_permission(string $perm_key): bool
{
    global $conn;
    if (!isset($_SESSION['user'])) return false;
    $roles_str = $_SESSION['role'] ?? 'Admin';
    $roles = array_map('trim', explode(',', $roles_str));
    
    // Admin has all granular permissions
    if (in_array('Admin', $roles)) return true;

    // Load from DB if not in session
    if (!isset($_SESSION['extra_permissions']) && isset($_SESSION['user_id']) && isset($conn)) {
        $uid = (int)$_SESSION['user_id'];
        $res = mysqli_query($conn, "SELECT extra_permissions FROM users WHERE id='$uid'");
        if ($res && $r = mysqli_fetch_assoc($res)) {
            $_SESSION['extra_permissions'] = $r['extra_permissions'] ?: '{}';
        } else {
            $_SESSION['extra_permissions'] = '{}';
        }
    }
    
    $perms_json = $_SESSION['extra_permissions'] ?? '{}';
    $perms = json_decode($perms_json, true);
    if (is_array($perms) && isset($perms[$perm_key]) && $perms[$perm_key] === true) {
        return true;
    }

    return false;
}

/**
 * Redirect with an "Access Denied" flash message if the user cannot perform
 * $action on $module. Call this at the top of every protected page.
 */
function require_permission(string $module, string $action = 'view'): void
{
    if (!isset($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }

    if (!can($module, $action)) {
        $_SESSION['access_denied'] = "You don't have permission to access that page.";
        header('Location: dashboard.php');
        exit;
    }
}
?>
