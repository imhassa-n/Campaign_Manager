<?php

session_start();

if(!isset($_SESSION['user']))
{
    header("Location: login.php");
    exit;
}

include 'db.php';
require_once 'auth.php';
require_permission('payments');

if(isset($_POST['save']))
{
    $custom_client_name = '';
    if(isset($_POST['client_id']) && $_POST['client_id'] === 'custom') {
        $client_id = "NULL";
        $custom_client_name = mysqli_real_escape_string($conn, $_POST['custom_client_name']);
    } else {
        $client_id = !empty($_POST['client_id']) ? "'".$_POST['client_id']."'" : "NULL";
    }
    $campaign_id = !empty($_POST['campaign_id']) ? "'".$_POST['campaign_id']."'" : "NULL";
    $service_id = !empty($_POST['service_id']) ? "'".$_POST['service_id']."'" : "NULL";
    $amount = $_POST['amount'];
    $payment_date = $_POST['payment_date'];
    $payment_method = $_POST['payment_method'];
    $notes = !empty($_POST['notes']) ? $_POST['notes'] : '';

    mysqli_query($conn,"
    INSERT INTO payments
    (client_id, custom_client_name, campaign_id,service_id,amount,payment_date,payment_method,notes)
    VALUES
    ($client_id, '$custom_client_name', $campaign_id,$service_id,'$amount','$payment_date','$payment_method','$notes')
    ");

    header("Location: payments.php");
    exit;
}

// Summary Stats
$total_received = mysqli_fetch_assoc(mysqli_query($conn,"SELECT IFNULL(SUM(amount),0) as total FROM payments"))['total'];
$this_month_received = mysqli_fetch_assoc(mysqli_query($conn,"SELECT IFNULL(SUM(amount),0) as total FROM payments WHERE MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE())"))['total'];
$total_budget = mysqli_fetch_assoc(mysqli_query($conn,"SELECT IFNULL(SUM(budget),0) as total FROM campaigns"))['total'];
$total_pending = $total_budget - $total_received;
if($total_pending < 0) $total_pending = 0;
$payment_count = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as cnt FROM payments"))['cnt'];

?>

<?php include 'header.php'; ?>

<!-- Top Bar -->
<div class="topbar">
    <div class="topbar-left">
        <button class="mobile-toggle" onclick="openSidebar()">
            <i class="bi bi-list"></i>
        </button>
        <div class="topbar-title">
            <h1>Payments</h1>
            <p>Track client payments and campaign balances</p>
        </div>
    </div>
    <div class="topbar-right">
        <div class="topbar-search">
            <i class="bi bi-search"></i>
            <input type="text" placeholder="Search payments & receivables..." id="paymentSearch" onkeyup="filterTables()">
        </div>
    </div>
</div>

<!-- Content -->
<div class="content-wrapper">

    <!-- Summary Cards -->
    <div class="grid-4 mb-4">
        <div class="metric-card">
            <div class="metric-icon success">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div class="metric-label">Total Received</div>
            <div class="metric-value">Rs <?php echo number_format($total_received); ?></div>
        </div>
        <div class="metric-card">
            <div class="metric-icon teal">
                <i class="bi bi-calendar-month"></i>
            </div>
            <div class="metric-label">This Month</div>
            <div class="metric-value">Rs <?php echo number_format($this_month_received); ?></div>
        </div>
        <div class="metric-card">
            <div class="metric-icon danger">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <div class="metric-label">Total Pending</div>
            <div class="metric-value">Rs <?php echo number_format($total_pending); ?></div>
        </div>
        <div class="metric-card">
            <div class="metric-icon navy">
                <i class="bi bi-receipt"></i>
            </div>
            <div class="metric-label">Total Entries</div>
            <div class="metric-value"><?php echo $payment_count; ?></div>
        </div>
    </div>

    <!-- Add Payment Form -->
    <div class="page-card mb-4">
        <div class="page-card-header">
            <h2><i class="bi bi-plus-circle-fill"></i> Record New Payment</h2>
        </div>
        <div class="page-card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-section">
                            <label class="form-label">Select Client</label>
                            <select name="client_id" class="form-control form-select" id="clientSelect" onchange="toggleCustomClient()" required>
                                <option value="">Select a Client...</option>
                                <option value="custom">Custom Client</option>
                                <?php
                                $clients = mysqli_query($conn,"SELECT * FROM clients ORDER BY name");
                                while($client = mysqli_fetch_assoc($clients))
                                {
                                ?>
                                <option value="<?php echo $client['id']; ?>">
                                    <?php echo $client['name']; ?>
                                </option>
                                <?php
                                }
                                ?>
                            </select>
                            <input type="text" name="custom_client_name" id="customClientName" class="form-control mt-2" placeholder="Enter Custom Client Name" style="display: none;">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-section">
                            <label class="form-label">Amount (Rs)</label>
                            <input type="number"
                                   name="amount"
                                   class="form-control"
                                   placeholder="Enter amount"
                                   required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-section">
                            <label class="form-label">Payment Date</label>
                            <input type="date"
                                   name="payment_date"
                                   class="form-control"
                                   value="<?php echo date('Y-m-d'); ?>"
                                   required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-section">
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method" class="form-control form-select">
                                <option value="Cash">Cash</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="JazzCash">JazzCash</option>
                                <option value="EasyPaisa">EasyPaisa</option>
                                <option value="Cheque">Cheque</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-section">
                            <label class="form-label">Campaign (Optional)</label>
                            <select name="campaign_id" class="form-control form-select">
                                <option value="">No Campaign...</option>
                                <?php
                                $campaigns = mysqli_query($conn,"SELECT campaigns.*, clients.name as client_name FROM campaigns LEFT JOIN clients ON campaigns.client_id = clients.id ORDER BY campaigns.id DESC");
                                while($campaign = mysqli_fetch_assoc($campaigns))
                                {
                                ?>
                                <option value="<?php echo $campaign['id']; ?>">
                                    <?php echo $campaign['campaign_name']; ?> (<?php echo $campaign['client_name']; ?>)
                                </option>
                                <?php
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-section">
                            <label class="form-label">Service / Retainer (Optional)</label>
                            <select name="service_id" class="form-control form-select">
                                <option value="">No Service...</option>
                                <?php
                                $services = mysqli_query($conn,"SELECT * FROM services");
                                while($service = mysqli_fetch_assoc($services))
                                {
                                ?>
                                <option value="<?php echo $service['id']; ?>">
                                    <?php echo $service['service_name']; ?>
                                </option>
                                <?php
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-section">
                            <label class="form-label">Notes (Optional)</label>
                            <input type="text"
                                   name="notes"
                                   class="form-control"
                                   placeholder="e.g., Partial payment, advance...">
                        </div>
                    </div>
                </div>
                <button type="submit" name="save" class="btn-brand">
                    <i class="bi bi-plus-circle-fill"></i>
                    Save Payment
                </button>
            </form>
        </div>
    </div>

    <!-- Pending Receivables Dashboard -->
    <div class="page-card mb-4" style="border-left: 4px solid var(--danger);">
        <div class="page-card-header">
            <h2><i class="bi bi-exclamation-octagon-fill text-danger"></i> Pending Receivables</h2>
        </div>
        <div class="page-card-body" style="padding: 0;">
            <div class="table-wrapper">
                <table class="modern-table" id="receivablesTable">
                    <thead>
                    <tr>
                        <th>Client</th>
                        <th>Project / Month</th>
                        <th style="width: 150px;">Status</th>
                        <th>Due Date</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $pending_query = mysqli_query($conn,"
                    SELECT 
                        campaigns.id as proj_id,
                        campaigns.campaign_name as proj_name,
                        campaigns.budget,
                        campaigns.start_date,
                        campaigns.end_date,
                        campaigns.payment_due_date,
                        campaigns.campaign_type as proj_type,
                        campaigns.reminder_date,
                        clients.name as client_name, 
                        clients.phone as client_phone,
                        IFNULL(SUM(payments.amount),0) as received,
                        'campaign' as type
                    FROM campaigns
                    LEFT JOIN clients ON campaigns.client_id = clients.id
                    LEFT JOIN payments ON campaigns.id = payments.campaign_id
                    WHERE campaigns.payment_status != 'Cleared'
                    GROUP BY campaigns.id
                    HAVING (campaigns.budget - received) > 0
                    
                    UNION ALL
                    
                    SELECT 
                        services.id as proj_id,
                        services.service_name as proj_name,
                        services.budget,
                        services.start_date,
                        services.end_date,
                        services.payment_due_date,
                        services.service_type as proj_type,
                        services.reminder_date,
                        clients.name as client_name, 
                        clients.phone as client_phone,
                        GREATEST(IFNULL(services.advance_amount, 0), IFNULL(SUM(payments.amount),0)) as received,
                        'service' as type
                    FROM services
                    LEFT JOIN clients ON services.client_id = clients.id
                    LEFT JOIN payments ON services.id = payments.service_id
                    WHERE services.payment_status != 'Cleared'
                    GROUP BY services.id
                    HAVING (services.budget - received) > 0
                    
                    ORDER BY payment_due_date ASC
                    ");

                    $has_pending = false;
                    while($p = mysqli_fetch_assoc($pending_query))
                    {
                        $has_pending = true;
                        $budget = floatval($p['budget']);
                        $received = floatval($p['received']);
                        $remaining = $budget - $received;
                        $valid_due_date = $p['payment_due_date'] && $p['payment_due_date'] != '0000-00-00';
                        $is_overdue = $valid_due_date && strtotime($p['payment_due_date']) < strtotime('today');
                        
                        $percent = $budget > 0 ? ($received / $budget) * 100 : 0;
                        $percent = min(100, $percent);
                    ?>
                    <tr <?php if($is_overdue) echo 'style="background-color: #fff1f2;"'; ?>>
                        <td>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="width: 30px; height: 30px; border-radius: 50%; background: linear-gradient(135deg, var(--teal-600), var(--navy-600)); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 11px; flex-shrink: 0;">
                                    <?php echo strtoupper(substr($p['client_name'], 0, 1)); ?>
                                </div>
                                <span style="font-weight: 600;"><?php echo $p['client_name']; ?></span>
                            </div>
                        </td>
                        <td>
                            <span style="font-weight: 600; color: var(--navy-800);"><?php echo $p['proj_name']; ?></span><br>
                            <span style="font-size: 11px; color: var(--gray-500); font-weight: 600;">
                                <?php if($p['type'] == 'service'): ?>
                                <i class="bi bi-person-workspace"></i> 
                                <?php else: ?>
                                <i class="bi bi-megaphone"></i>
                                <?php endif; ?>
                                <?php echo date('d M', strtotime($p['start_date'])) . ' - ' . date('d M, Y', strtotime($p['end_date'])); ?>
                            </span>
                        </td>
                        <td>
                            <div style="font-weight: 700; font-size: 11px; margin-bottom: 4px; color: var(--navy-800);">
                                Rs <?php echo number_format($received); ?> <span style="color: var(--gray-500); font-weight: 500;">/ <?php echo number_format($budget); ?></span>
                            </div>
                            <div style="background: #e2e8f0; height: 6px; border-radius: 4px; overflow: hidden; width: 100%;">
                                <div style="background: <?php echo $percent == 100 ? 'var(--success)' : 'var(--orange-500)'; ?>; height: 100%; width: <?php echo $percent; ?>%;"></div>
                            </div>
                            <?php if($remaining > 0): ?>
                            <div style="font-size: 10px; color: var(--danger); margin-top: 3px; font-weight: 700;">Remaining: Rs <?php echo number_format($remaining); ?></div>
                            <?php else: ?>
                            <div style="font-size: 10px; color: var(--success); margin-top: 3px; font-weight: 700;"><i class="bi bi-check-circle-fill"></i> Full Paid</div>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight: 500; color: var(--navy-600);">
                            <?php if($valid_due_date): ?>
                                <?php echo date('d M, Y', strtotime($p['payment_due_date'])); ?>
                                <?php if($is_overdue) echo '<br><span style="display:inline-block; margin-top:4px; font-size: 10px; padding: 2px 6px; background:#fee2e2; color: var(--danger); border-radius:4px; font-weight: 700;"><i class="bi bi-exclamation-circle-fill"></i> Overdue</span>'; ?>
                            <?php else: ?>
                                <span style="color: var(--gray-400);">Not Set</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $phone = $p['client_phone'];
                            if(substr($phone, 0, 1) === '0') {
                                $phone = '+92' . substr($phone, 1);
                            } elseif (substr($phone, 0, 3) !== '+92') {
                                $phone = '+92' . ltrim($phone, '+');
                            }
                            
                            $is_service = $p['type'] == 'service' || in_array($p['proj_type'], ['Monthly Service Retainer', 'Website Development']);
                            $amount_req = $is_service ? $remaining : $remaining * 1.16;
                            $gst_text = $is_service ? "" : " (including 16% GST)";
                            
                            $msg = "Hello ".$p['client_name'].", your remaining payment of Rs ".number_format($amount_req).$gst_text." for the '".$p['proj_name']."' project is due. Please clear it";
                            if($p['reminder_date']) {
                                $msg .= " by " . date('d M Y', strtotime($p['reminder_date']));
                            }
                            $msg .= ".";
                            
                            $wa_link = "https://wa.me/".str_replace(['+', ' ', '-'], '', $phone)."?text=".urlencode($msg);
                            ?>
                            <a href="<?php echo $wa_link; ?>" target="_blank" class="action-btn" style="background: #25D366; color: white; border-color: #25D366;" title="Send WhatsApp Reminder">
                                <i class="bi bi-whatsapp"></i>
                            </a>
                        </td>
                    </tr>
                    <?php }
                    if(!$has_pending) {
                    ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 30px; color: var(--gray-500);">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 24px; display: block; margin-bottom: 8px;"></i>
                            All payments are cleared! No pending receivables.
                        </td>
                    </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Payments List -->
    <div class="page-card mb-4">
        <div class="page-card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <h2 style="margin: 0;"><i class="bi bi-cash-stack"></i> Recent Payments</h2>
            <div style="display: flex; gap: 10px; align-items: center;">
                <input type="month" id="historyMonthFilter" class="form-control" style="width: auto; height: 36px; padding: 4px 10px; font-size: 13px;" onchange="filterTables()">
                <select id="historyMethodFilter" class="form-control form-select" style="width: auto; height: 36px; padding: 4px 10px; font-size: 13px;" onchange="filterTables()">
                    <option value="">All Methods</option>
                    <option value="Cash">Cash</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="JazzCash">JazzCash</option>
                    <option value="EasyPaisa">EasyPaisa</option>
                    <option value="Cheque">Cheque</option>
                    <option value="Other">Other</option>
                </select>
            </div>
        </div>
        <div class="page-card-body" style="padding: 0;">
            <div class="table-wrapper">
                <table class="modern-table" id="paymentsTable">
                    <thead>
                    <tr>
                        <th>Client</th>
                        <th>Campaign / Service</th>
                        <th>Date</th>
                        <th>Method</th>
                        <th>Amount</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $result = mysqli_query($conn,"
                    SELECT payments.*,
                    clients.name as client_name,
                    campaigns.campaign_name,
                    services.service_name
                    FROM payments
                    LEFT JOIN clients ON payments.client_id = clients.id
                    LEFT JOIN campaigns ON payments.campaign_id = campaigns.id
                    LEFT JOIN services ON payments.service_id = services.id
                    ORDER BY payments.payment_date DESC, payments.id DESC
                    ");

                    while($row = mysqli_fetch_assoc($result))
                    {
                        // Method badges
                        $method = $row['payment_method'] ?? 'Cash';
                        $method_colors = [
                            'Cash' => '#10b981',
                            'Bank Transfer' => '#3b82f6',
                            'JazzCash' => '#ef4444',
                            'EasyPaisa' => '#22c55e',
                            'Cheque' => '#f59e0b',
                            'Other' => '#6b7280'
                        ];
                        $method_icons = [
                            'Cash' => 'bi-cash',
                            'Bank Transfer' => 'bi-bank',
                            'JazzCash' => 'bi-phone',
                            'EasyPaisa' => 'bi-phone',
                            'Cheque' => 'bi-file-earmark-text',
                            'Other' => 'bi-three-dots'
                        ];
                        $m_color = $method_colors[$method] ?? '#6b7280';
                        $m_icon = $method_icons[$method] ?? 'bi-three-dots';
                    ?>
                    <tr data-method="<?php echo htmlspecialchars($method); ?>" data-month="<?php echo date('Y-m', strtotime($row['payment_date'])); ?>">
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 36px; height: 36px; border-radius: 8px; background: linear-gradient(135deg, var(--teal-500), var(--navy-600)); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 14px; flex-shrink: 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                    <?php 
                                        if(!empty($row['client_name'])) echo strtoupper(substr($row['client_name'], 0, 1)); 
                                        elseif(!empty($row['custom_client_name'])) echo strtoupper(substr($row['custom_client_name'], 0, 1)); 
                                        else echo 'G';
                                    ?>
                                </div>
                                <div>
                                    <span style="font-weight: 700; color: var(--navy-800); font-size: 14px; display: block;">
                                        <?php 
                                            if(!empty($row['client_name'])) echo htmlspecialchars($row['client_name']);
                                            elseif(!empty($row['custom_client_name'])) echo htmlspecialchars($row['custom_client_name']);
                                            else echo 'Generic Payment';
                                        ?>
                                    </span>
                                    <?php if(!empty($row['custom_client_name'])): ?>
                                        <span style="font-size: 11px; background: var(--gray-100); color: var(--gray-600); padding: 2px 6px; border-radius: 4px; font-weight: 600;">Custom Client</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 700; color: var(--navy-700); font-size: 13px;">
                            <?php 
                            if($row['campaign_name']) echo '<i class="bi bi-megaphone" style="color:var(--teal-500);"></i> ' . htmlspecialchars($row['campaign_name']);
                            elseif($row['service_name']) echo '<i class="bi bi-laptop" style="color:var(--teal-500);"></i> ' . htmlspecialchars($row['service_name']);
                            else echo '<i class="bi bi-box" style="color:var(--gray-400);"></i> Direct Payment';
                            ?>
                            </div>
                            <?php if(!empty($row['notes'])): ?>
                            <div style="font-size: 11px; color: var(--gray-500); margin-top: 4px; background: #f8fafc; padding: 4px 8px; border-radius: 4px; border-left: 2px solid #cbd5e1; max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($row['notes']); ?>">
                                <?php echo htmlspecialchars($row['notes']); ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="font-weight: 700; color: var(--navy-800); font-size: 13px;">
                                <?php echo $row['payment_date'] ? date('d M, Y', strtotime($row['payment_date'])) : '-'; ?>
                            </div>
                            <div style="font-size: 10px; color: var(--gray-400); font-weight: 600; text-transform: uppercase;">
                                <?php echo $row['payment_date'] ? date('l', strtotime($row['payment_date'])) : ''; ?>
                            </div>
                        </td>
                        <td>
                            <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 700; padding: 4px 12px; border-radius: 20px; background: <?php echo $m_color; ?>15; color: <?php echo $m_color; ?>; border: 1px solid <?php echo $m_color; ?>30;">
                                <i class="bi <?php echo $m_icon; ?>"></i>
                                <?php echo $method; ?>
                            </span>
                        </td>
                        <td>
                            <div style="font-weight: 800; color: var(--success); font-size: 15px;">
                                <span style="font-size: 11px; color: var(--gray-400); font-weight: 600;">Rs</span> <?php echo number_format($row['amount']); ?>
                            </div>
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <a class="btn-brand-outline" style="padding: 4px 8px; font-size: 12px;"
                                   href="edit_payment.php?id=<?php echo $row['id']; ?>"
                                   title="Edit">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                <a class="btn-brand-outline" style="padding: 4px 8px; font-size: 12px; border-color: #fecaca; color: var(--danger);"
                                   href="delete_payment.php?id=<?php echo $row['id']; ?>"
                                   title="Delete"
                                   onclick="return confirm('Are you sure you want to delete this payment?')">
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
function toggleCustomClient() {
    const select = document.getElementById('clientSelect');
    const input = document.getElementById('customClientName');
    if (select.value === 'custom') {
        input.style.display = 'block';
        input.required = true;
    } else {
        input.style.display = 'none';
        input.required = false;
        input.value = '';
    }
}

function filterTables() {
    const query = document.getElementById('paymentSearch').value.toLowerCase();
    const monthFilter = document.getElementById('historyMonthFilter') ? document.getElementById('historyMonthFilter').value : '';
    const methodFilter = document.getElementById('historyMethodFilter') ? document.getElementById('historyMethodFilter').value.toLowerCase() : '';
    
    // Filter Receivables
    const recRows = document.querySelectorAll('#receivablesTable tbody tr');
    recRows.forEach(row => {
        if(row.cells.length === 1) return; // Skip empty state row
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(query) ? '' : 'none';
    });

    // Filter Payments
    const payRows = document.querySelectorAll('#paymentsTable tbody tr');
    payRows.forEach(row => {
        if(row.cells.length === 1) return;
        const text = row.textContent.toLowerCase();
        
        let show = text.includes(query);
        
        if (monthFilter) {
            const rowMonth = row.getAttribute('data-month');
            if (rowMonth !== monthFilter) show = false;
        }
        
        if (methodFilter) {
            const rowMethod = row.getAttribute('data-method').toLowerCase();
            if (rowMethod !== methodFilter) show = false;
        }
        
        row.style.display = show ? '' : 'none';
    });
}
</script>
