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
            <h1>Payment History</h1>
            <p>View detailed, searchable history of all payments received</p>
        </div>
    </div>
    <div class="topbar-right">
        <div class="topbar-search">
            <i class="bi bi-search"></i>
            <input type="text" placeholder="Search history..." id="paymentSearch" onkeyup="filterTables()">
        </div>
        <a href="payments.php" class="btn-brand">
            <i class="bi bi-plus-circle-fill"></i>
            Add New Payment
        </a>
    </div>
</div>

<!-- Content -->
<div class="content-wrapper">

    <div class="page-card mb-4">
        <div class="page-card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <h2 style="margin: 0;"><i class="bi bi-clock-history"></i> Payment History</h2>
            <div style="display: flex; gap: 10px; align-items: center;">
                <select id="historyMonthFilter" class="form-control form-select" style="width: auto; height: 36px; padding: 4px 10px; font-size: 13px;" onchange="filterTables()">
                    <option value="">All Months</option>
                    <?php
                    $months_query = mysqli_query($conn, "SELECT DISTINCT DATE_FORMAT(payment_date, '%Y-%m') as month_val, DATE_FORMAT(payment_date, '%b %Y') as month_label FROM payments ORDER BY payment_date DESC");
                    while($m = mysqli_fetch_assoc($months_query)) {
                        if(empty($m['month_val'])) continue;
                        echo "<option value='".$m['month_val']."'>".$m['month_label']."</option>";
                    }
                    ?>
                </select>
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
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="width: 30px; height: 30px; border-radius: 50%; background: linear-gradient(135deg, var(--teal-600), var(--navy-600)); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 11px; flex-shrink: 0;">
                                    <?php 
                                        if(!empty($row['client_name'])) {
                                            echo strtoupper(substr($row['client_name'], 0, 1)); 
                                        } elseif(!empty($row['custom_client_name'])) {
                                            echo strtoupper(substr($row['custom_client_name'], 0, 1)); 
                                        } else {
                                            echo 'G';
                                        }
                                    ?>
                                </div>
                                <span style="font-weight: 600;">
                                    <?php 
                                        if(!empty($row['client_name'])) {
                                            echo $row['client_name'];
                                        } elseif(!empty($row['custom_client_name'])) {
                                            echo htmlspecialchars($row['custom_client_name']) . " <span style='font-size:10px; color:#aaa;'>(Custom)</span>";
                                        } else {
                                            echo 'Generic Payment';
                                        }
                                    ?>
                                </span>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: var(--navy-800);">
                            <?php 
                            if($row['campaign_name']) echo htmlspecialchars($row['campaign_name']) . " <span style='font-size:10px; color:#aaa;'>(Campaign)</span>";
                            elseif($row['service_name']) echo htmlspecialchars($row['service_name']) . " <span style='font-size:10px; color:#aaa;'>(Service)</span>";
                            else echo "Direct Payment";
                            ?>
                            </div>
                            <?php if(!empty($row['notes'])): ?>
                            <div style="font-size: 11px; color: var(--gray-500); margin-top: 2px;">
                                <?php echo htmlspecialchars($row['notes']); ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight: 500; color: var(--navy-600); font-size: 13px;">
                            <?php echo $row['payment_date'] ? date('d M, Y', strtotime($row['payment_date'])) : '-'; ?>
                        </td>
                        <td>
                            <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 20px; background: <?php echo $m_color; ?>15; color: <?php echo $m_color; ?>;">
                                <i class="bi <?php echo $m_icon; ?>" style="font-size: 10px;"></i>
                                <?php echo $method; ?>
                            </span>
                        </td>
                        <td>
                            <span style="font-weight: 700; color: var(--success);">
                                Rs <?php echo number_format($row['amount']); ?>
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 6px;">
                                <a class="action-btn edit"
                                   href="edit_payment.php?id=<?php echo $row['id']; ?>"
                                   title="Edit">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                <a class="action-btn delete"
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
