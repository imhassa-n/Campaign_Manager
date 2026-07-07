<?php

session_start();

if(!isset($_SESSION['user']))
{
    header("Location: login.php");
    exit;
}

include 'db.php';
require_once 'auth.php';
require_permission('retainers');

if(isset($_POST['save']))
{
    $client_id = $_POST['client_id'];
    $service_name = $_POST['service_name'];
    $budget = isset($_POST['budget']) ? $_POST['budget'] : 0;
    $status = $_POST['status'];

    $service_type = "Monthly Service Retainer";
    $start_date = $_POST['start_date'];
    $billing_cycle = "Monthly";
    $advance_amount = !empty($_POST['advance_amount']) ? floatval($_POST['advance_amount']) : 0;
    
    // Auto-calculate Next Billing Date
    if ($advance_amount > 0) {
        // If advance is paid, the next billing is exactly 1 month from start date
        $payment_due_date = date('Y-m-d', strtotime('+1 month', strtotime($start_date)));
    } else {
        // If no advance, the first payment is due on the start date
        $payment_due_date = $start_date;
    }
    
    // We don't need these manual fields anymore for retainers
    $payment_status = 'Pending'; 
    $reminder_date = "NULL";
    $end_date = "0000-00-00";

    mysqli_query($conn,"
    INSERT INTO services
    (
    client_id,
    service_name,
    budget,
    status,
    service_type,
    start_date,
    end_date,
    payment_due_date,
    payment_status,
    reminder_date,
    billing_cycle,
    advance_amount
    )
    VALUES
    (
    '$client_id',
    '$service_name',
    '$budget',
    '$status',
    '$service_type',
    '$start_date',
    '$end_date',
    '$payment_due_date',
    '$payment_status',
    $reminder_date,
    '$billing_cycle',
    '$advance_amount'
    )
    ");
    $service_id = mysqli_insert_id($conn);
    
    if($advance_amount > 0) {
        $payment_date = date("Y-m-d");
        mysqli_query($conn, "INSERT INTO payments (client_id, service_id, amount, payment_date) VALUES ('$client_id', '$service_id', '$advance_amount', '$payment_date')");
    }

    header("Location: retainers.php");
    exit;
}

?>

<?php include 'header.php'; ?>

<style>
/* Modern professional tweaks for retainers */
.modern-table tbody td {
    padding: 16px;
    font-size: 13.5px;
}
.soft-badge {
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    letter-spacing: 0.3px;
}
.soft-badge.due { background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; }
.soft-badge.upcoming { background: #fffbeb; color: #d97706; border: 1px solid #fef3c7; }
.soft-badge.track { background: #f0fdf4; color: #16a34a; border: 1px solid #dcfce7; }

.action-btn-soft {
    width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s ease;
    border: 1px solid transparent;
    cursor: pointer;
    text-decoration: none;
}
.action-btn-soft.pay { background: #f0f9ff; color: #0284c7; }
.action-btn-soft.pay:hover { background: #0ea5e9; color: white; border-color: #0ea5e9; }

.action-btn-soft.renew { background: #f0fdf4; color: #16a34a; }
.action-btn-soft.renew:hover { background: #16a34a; color: white; border-color: #16a34a; }

.action-btn-soft.wa { background: #f0fdf4; color: #25d366; }
.action-btn-soft.wa:hover { background: #25d366; color: white; border-color: #25d366; }

.action-btn-soft.inv { background: #f8fafc; color: #475569; }
.action-btn-soft.inv:hover { background: #e2e8f0; color: #0f172a; }

.action-btn-soft.edit { background: #f8fafc; color: #64748b; }
.action-btn-soft.edit:hover { background: #f1f5f9; color: #0f172a; }

.action-btn-soft.del { background: #fef2f2; color: #ef4444; }
.action-btn-soft.del:hover { background: #ef4444; color: white; border-color: #ef4444; }

.progress-track {
    width: 100%;
    height: 6px;
    background: #f1f5f9;
    border-radius: 10px;
    overflow: hidden;
    margin-top: 6px;
}
.progress-fill {
    height: 100%;
    border-radius: 10px;
    transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
<div class="topbar">
    <div class="topbar-left">
        <button class="mobile-toggle" onclick="openSidebar()">
            <i class="bi bi-list"></i>
        </button>
        <div class="topbar-title">
            <h1>Monthly Clients</h1>
            <p>Manage monthly recurring clients like SEO, Social Media Management, etc.</p>
        </div>
    </div>
    <div class="topbar-right">
        <div class="topbar-search">
            <i class="bi bi-search"></i>
            <input type="text" placeholder="Search monthly clients..." id="serviceSearch" onkeyup="filterTable()">
        </div>
    </div>
</div>

<!-- Content -->
<div class="content-wrapper">

    <?php
    if(can('payments')) {
        $total_monthly_fee = 0;
        $total_received = 0;
        $total_pending = 0;

        $stats_query = mysqli_query($conn,"
        SELECT id, budget, start_date, payment_due_date 
        FROM services
        WHERE service_type = 'Monthly Service Retainer' AND status = 'Active'
        ");

        while($s = mysqli_fetch_assoc($stats_query)) {
            $b = floatval($s['budget']);
            $total_monthly_fee += $b;

            $current_due = $s['payment_due_date'];
            if($current_due && $current_due != '0000-00-00') {
                $cycle_start = date('Y-m-d', strtotime('-1 month', strtotime($current_due)));
            } else {
                $cycle_start = $s['start_date'];
            }

            $rec_q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(amount),0) as total FROM payments WHERE service_id='".$s['id']."' AND payment_date >= '$cycle_start'"));
            $rec = floatval($rec_q['total']);
            
            $total_received += $rec;
            $total_pending += max(0, $b - $rec);
        }
    ?>
    <!-- Primary Metrics Grid -->
    <div class="grid-4 mb-4">
        
        <div class="metric-card">
            <div class="metric-icon success">
                <i class="bi bi-wallet2"></i>
            </div>
            <div class="metric-label">Total Monthly Expected</div>
            <div class="metric-value">Rs <?php echo number_format($total_monthly_fee); ?></div>
        </div>

        <div class="metric-card">
            <div class="metric-icon teal">
                <i class="bi bi-graph-up-arrow"></i>
            </div>
            <div class="metric-label">Received This Cycle</div>
            <div class="metric-value">Rs <?php echo number_format($total_received); ?></div>
        </div>

        <div class="metric-card">
            <div class="metric-icon danger">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <div class="metric-label">Pending Receivables</div>
            <div class="metric-value">Rs <?php echo number_format($total_pending); ?></div>
        </div>

    </div>
    <?php } ?>

    <!-- Add Service Form -->
    <div class="page-card mb-4">
        <div class="page-card-header">
            <h2><i class="bi bi-person-workspace"></i> Add New Monthly Client</h2>
        </div>
        <div class="page-card-body">
            <form method="POST" id="serviceForm">

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-section">
                            <label class="form-label">Select Client</label>
                            <select name="client_id" class="form-control form-select" required>
                                <option value="">Choose a client...</option>
                                <?php
                                $clients = mysqli_query($conn,"
                                    SELECT * FROM clients c
                                    WHERE NOT EXISTS (
                                        SELECT 1 FROM services s 
                                        WHERE s.client_id = c.id 
                                        AND s.service_type = 'Monthly Service Retainer'
                                    )
                                    ORDER BY name ASC
                                ");
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
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-section">
                            <label class="form-label">Service / Package</label>
                            <input type="text"
                                   name="service_name"
                                   class="form-control"
                                   placeholder="E.g. SEO, Social Media Management"
                                   required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <?php if(can('payments')): ?>
                    <div class="col-md-3">
                        <div class="form-section">
                            <label class="form-label">Monthly Fee (Rs)</label>
                            <input type="number"
                                   name="budget"
                                   class="form-control"
                                   placeholder="E.g. 15000"
                                   required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-section">
                            <label class="form-label">First Month Upfront (Rs) <small style="color:var(--gray-500)">Optional</small></label>
                            <input type="number"
                                   name="advance_amount"
                                   class="form-control"
                                   placeholder="E.g. 15000">
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-3">
                        <div class="form-section">
                            <label class="form-label">Billing Cycle Start Date</label>
                            <input type="date"
                                   name="start_date"
                                   class="form-control"
                                   required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-section">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control form-select">
                                <option>Active</option>
                                <option>Paused</option>
                                <option>Completed</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info mt-2" style="font-size: 13px; display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-info-circle-fill"></i>
                    <strong>Automated Billing:</strong> The system will automatically calculate the next billing date based on the Start Date.
                </div>

                <button type="submit" name="save" class="btn-brand mt-2">
                    <i class="bi bi-plus-circle-fill"></i>
                    Add Monthly Client
                </button>

            </form>
        </div>
    </div>

    <!-- Monthly Clients List -->
    <div class="page-card">
        <div class="page-card-header">
            <h2><i class="bi bi-person-workspace"></i> Monthly Clients List</h2>
        </div>
        <div class="page-card-body" style="padding: 0;">
            <div class="table-wrapper">
                <table class="modern-table" id="servicesTable">
                    <thead>
                    <tr>
                        <th>Client</th>
                        <th>Package / Service</th>
                        <?php if(can('payments')): ?>
                        <th>Monthly Fee</th>
                        <th>Received</th>
                        <?php endif; ?>
                        <th>Start Date</th>
                        <th>Next Billing Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $result = mysqli_query($conn,"
                    SELECT services.*, clients.name as client_name, clients.phone as client_phone, clients.image as client_image
                    FROM services
                    LEFT JOIN clients ON services.client_id = clients.id
                    WHERE services.service_type = 'Monthly Service Retainer'
                    ORDER BY services.id DESC
                    ");

                    while($row = mysqli_fetch_assoc($result))
                    {
                        $budget = floatval($row['budget']);
                        
                        // Calculate received for current billing cycle
                        $current_due = $row['payment_due_date'];
                        if($current_due && $current_due != '0000-00-00') {
                            $cycle_start = date('Y-m-d', strtotime('-1 month', strtotime($current_due)));
                        } else {
                            $cycle_start = $row['start_date'];
                        }
                        $received_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(amount),0) as total FROM payments WHERE service_id='".$row['id']."' AND payment_date >= '$cycle_start'"));
                        $received = floatval($received_res['total']);
                        $remaining = max(0, $budget - $received);
                        $percent = ($budget > 0) ? min(100, round(($received / $budget) * 100)) : 0;
                        
                        $today = date('Y-m-d');
                        $next_billing_date = $row['payment_due_date'];
                        
                        $is_due = false;
                        $is_upcoming = false;
                        $badge_html = "";
                        
                        if ($next_billing_date) {
                            if ($next_billing_date <= $today) {
                                $is_due = true;
                                $badge_html = '<span class="soft-badge due"><i class="bi bi-exclamation-circle-fill"></i> Payment Due</span>';
                            } else if ($next_billing_date <= date('Y-m-d', strtotime('+3 days'))) {
                                $is_upcoming = true;
                                $badge_html = '<span class="soft-badge upcoming"><i class="bi bi-clock-fill"></i> Upcoming Soon</span>';
                            } else {
                                $badge_html = '<span class="soft-badge track"><i class="bi bi-check-circle-fill"></i> On Track</span>';
                            }
                        }
                    ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <?php if(!empty($row['client_image'])) { 
                                    $img_src = (strpos($row['client_image'], 'data:image') === 0) ? $row['client_image'] : 'assets/clients/'.$row['client_image'];
                                ?>
                                <img src="<?php echo $img_src; ?>" style="width: 30px; height: 30px; border-radius: 50%; object-fit: cover; flex-shrink: 0; box-shadow: var(--shadow-sm); border: 2px solid white;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div style="width: 30px; height: 30px; border-radius: 50%; background: linear-gradient(135deg, #10b981, #047857); align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 11px; flex-shrink: 0; display: none;">
                                    <?php echo strtoupper(substr($row['client_name'], 0, 1)); ?>
                                </div>
                                <?php } else { ?>
                                <div style="width: 30px; height: 30px; border-radius: 50%; background: linear-gradient(135deg, #10b981, #047857); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 11px; flex-shrink: 0;">
                                    <?php echo strtoupper(substr($row['client_name'], 0, 1)); ?>
                                </div>
                                <?php } ?>
                                <span style="font-weight: 500;"><?php echo $row['client_name']; ?></span>
                            </div>
                        </td>
                        <td style="font-weight: 600; color: var(--navy-800);">
                            <?php echo $row['service_name']; ?>
                        </td>
                        <?php if(can('payments')): ?>
                        <td style="font-weight: 600; color: #16a34a;">Rs <?php echo number_format($budget); ?></td>
                        <td>
                            <div style="font-size: 11.5px; font-weight: 600; margin-bottom: 4px; display: flex; justify-content: space-between; align-items: center; gap: 8px; white-space: nowrap;">
                                <span><span style="color: <?php echo ($percent >= 100) ? '#16a34a' : '#0f172a'; ?>;">Rs <?php echo number_format($received); ?></span> <span style="color: #94a3b8; font-weight: 500;">/ <?php echo number_format($budget); ?></span></span>
                                <?php if($remaining > 0) { ?>
                                <span style="color: #ef4444; font-size: 11px;">Rs <?php echo number_format($remaining); ?> left</span>
                                <?php } else { ?>
                                <span style="color: #16a34a; font-size: 11px;"><i class="bi bi-check2-all"></i> Paid</span>
                                <?php } ?>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill" style="width: <?php echo $percent; ?>%; background: <?php echo ($percent >= 100) ? '#10b981' : (($percent >= 50) ? '#f59e0b' : '#ef4444'); ?>;"></div>
                            </div>
                        </td>
                        <?php endif; ?>
                        <td style="font-weight: 500; color: var(--navy-600);">
                            <?php echo date('d M, Y', strtotime($row['start_date'])); ?>
                        </td>
                        <td style="font-weight: 600; color: <?php echo ($is_due) ? 'var(--danger)' : (($is_upcoming) ? '#d97706' : 'var(--navy-800)'); ?>; white-space: nowrap;">
                            <div style="margin-bottom: 4px;"><?php echo $next_billing_date ? date('d M, Y', strtotime($next_billing_date)) : '-'; ?></div>
                            <?php echo $badge_html; ?>
                        </td>
                        <td>
                            <?php
                            if($row['status']=='Active') {
                                echo '<span class="status-badge active">Active</span>';
                            } elseif($row['status']=='Paused') {
                                echo '<span class="status-badge paused">Paused</span>';
                            } else {
                                echo '<span class="status-badge completed">Completed</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <div style="display: flex; gap: 6px; align-items: center; flex-wrap: nowrap; white-space: nowrap;">
                                
                                <?php if(can('payments')): ?>
                                <?php if($remaining > 0) { 
                                    // Fetch payment history for this service in current cycle
                                    $hist_query = mysqli_query($conn, "SELECT * FROM payments WHERE service_id='".$row['id']."' AND payment_date >= '$cycle_start' ORDER BY payment_date DESC");
                                    $history_html = '';
                                    while($h = mysqli_fetch_assoc($hist_query)) {
                                        $method = !empty($h['payment_method']) ? htmlspecialchars($h['payment_method']) : 'Online Transfer';
                                        $history_html .= '<div style="display:flex; justify-content:space-between; align-items:center; padding:10px 14px; background:#f1f5f9; border: 1px solid #e2e8f0; border-radius:8px;">';
                                        $history_html .= '<div>';
                                        $history_html .= '<div style="font-weight:700; color:#0f172a; font-size: 14px;">Rs '.number_format($h['amount']).'</div>';
                                        $history_html .= '<div style="font-size:11px; color:#64748b; margin-top:2px;">'.$method.' &bull; '.date('d M Y', strtotime($h['payment_date'])).'</div>';
                                        $history_html .= '</div>';
                                        $history_html .= '<a href="edit_payment.php?id='.$h['id'].'" class="action-btn edit" style="width:28px;height:28px;font-size:12px; background:white; border-color:#cbd5e1;" title="Edit"><i class="bi bi-pencil-fill"></i></a>';
                                        $history_html .= '</div>';
                                    }
                                ?>
                                <!-- Add Partial Payment Button -->
                                <button type="button" class="action-btn-soft pay" title="Add Payment" onclick="openPaymentModal(<?php echo $row['id']; ?>, <?php echo htmlspecialchars(json_encode($row['client_name']), ENT_QUOTES, 'UTF-8'); ?>, <?php echo htmlspecialchars(json_encode($row['service_name']), ENT_QUOTES, 'UTF-8'); ?>, <?php echo $remaining; ?>, <?php echo htmlspecialchars(json_encode($history_html), ENT_QUOTES, 'UTF-8'); ?>)">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                                <?php } ?>
                                <?php if($is_due || $is_upcoming) { ?>
                                <!-- Mark Full Paid Button -->
                                <a href="renew_retainer.php?id=<?php echo $row['id']; ?>" class="action-btn-soft renew" title="Mark Full Paid & Renew" onclick="return confirm('Log payment of Rs <?php echo number_format($remaining); ?> and push billing date to next month?')">
                                    <i class="bi bi-check2-all"></i>
                                </a>
                                <?php } ?>
                                <?php endif; ?>

                                <?php if($is_due || $is_upcoming) { 
                                    $phone = $row['client_phone'];
                                    if(substr($phone, 0, 1) === '0') {
                                        $phone = '+92' . substr($phone, 1);
                                    } elseif (substr($phone, 0, 3) !== '+92') {
                                        $phone = '+92' . ltrim($phone, '+');
                                    }
                                    
                                    if(can('payments')) {
                                        if($received > 0 && $remaining > 0) {
                                            $msg = "Hello ".$row['client_name'].", your monthly fee for '".$row['service_name']."' is Rs ".number_format($budget).". We have received Rs ".number_format($received)." so far. The remaining amount of Rs ".number_format($remaining)." is due on ".date('d M Y', strtotime($next_billing_date)).". Please kindly process the payment.";
                                        } else {
                                            $msg = "Hello ".$row['client_name'].", your monthly fee of Rs ".number_format($budget)." for '".$row['service_name']."' is due on ".date('d M Y', strtotime($next_billing_date)).". Please kindly process the payment.";
                                        }
                                    } else {
                                        $msg = "Hello ".$row['client_name'].", your monthly fee for '".$row['service_name']."' is due on ".date('d M Y', strtotime($next_billing_date)).". Please kindly process the payment.";
                                    }
                                    
                                    $wa_link = "https://wa.me/".str_replace(['+',' ','-'], '', $phone)."?text=".urlencode($msg);
                                ?>
                                <?php 
                                    if(has_permission('can_send_whatsapp')) {
                                ?>
                                <a href="<?php echo $wa_link; ?>" target="_blank" class="action-btn-soft wa" title="Send WhatsApp Reminder">
                                    <i class="bi bi-whatsapp"></i>
                                </a>
                                <?php } ?>
                                <?php } ?>
                                <?php if(has_permission('can_invoice_monthly_clients')) { ?>
                                <a class="action-btn-soft inv" 
                                   href="invoice_service.php?id=<?php echo $row['id']; ?>" 
                                   target="_blank" 
                                   title="Generate Invoice">
                                    <i class="bi bi-receipt"></i>
                                </a>
                                <?php } ?>
                                <a class="action-btn-soft edit"
                                   href="edit_retainer.php?id=<?php echo $row['id']; ?>"
                                   title="Edit">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                <a class="action-btn-soft del"
                                   href="delete_retainer.php?id=<?php echo $row['id']; ?>"
                                   title="Delete"
                                   onclick="return confirm('Are you sure you want to delete this client record?')">
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

<!-- Partial Payment Modal -->
<div id="paymentModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15, 23, 42, 0.6); z-index:9999; align-items:center; justify-content:center; backdrop-filter: blur(4px);">
    <div style="background:white; border-radius:16px; width:460px; max-width:95%; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25); position:relative; max-height:90vh; overflow-y:auto; padding:0;">
        
        <!-- Header -->
        <div style="background: #f8fafc; padding: 20px 24px; border-bottom: 1px solid #e2e8f0; border-radius: 16px 16px 0 0; display:flex; justify-content:space-between; align-items:flex-start;">
            <div>
                <h3 style="margin:0 0 4px 0; font-size:18px; color:#0f172a; font-weight:700;"><i class="bi bi-wallet2" style="color:#0ea5e9; margin-right: 6px;"></i> Record Payment</h3>
                <p id="modalClientInfo" style="color:#64748b; font-size:13px; margin:0;"></p>
            </div>
            <button onclick="closePaymentModal()" style="background:none; border:none; font-size:24px; cursor:pointer; color:#94a3b8; line-height:1; padding:0;">&times;</button>
        </div>
        
        <div style="padding: 24px;">
            <form method="POST" action="add_partial_payment.php">
                <input type="hidden" name="service_id" id="modalServiceId">
                <input type="hidden" name="save_partial" value="1">
                
                <div style="display: flex; gap: 16px; margin-bottom: 16px;">
                    <div style="flex: 1;">
                        <label style="font-size:12px; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px; display:block;">Amount (Rs)</label>
                        <input type="number" name="amount" id="modalAmount" class="form-control" placeholder="0" required style="font-size:16px; font-weight:600; padding: 10px 14px;">
                        <div id="modalRemaining" style="font-size:11.5px; color:#0ea5e9; font-weight:600; margin-top:6px;"></div>
                    </div>
                    <div style="flex: 1;">
                        <label style="font-size:12px; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px; display:block;">Method</label>
                        <select name="payment_method" class="form-control form-select" style="padding: 10px 14px; font-weight:500;">
                            <option value="Online Transfer">Online Transfer</option>
                            <option value="Cash">Cash</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="JazzCash">JazzCash</option>
                            <option value="EasyPaisa">EasyPaisa</option>
                            <option value="Cheque">Cheque</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                
                <div style="margin-bottom: 24px;">
                    <label style="font-size:12px; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px; display:block;">Note (Optional)</label>
                    <input type="text" name="notes" class="form-control" placeholder="e.g. advance, remaining, etc." style="padding: 10px 14px;">
                </div>
                
                <button type="submit" class="btn-brand" style="width:100%; padding: 12px; font-size: 15px;"><i class="bi bi-check2-circle"></i> Save Payment</button>
            </form>
            
            <!-- History Section -->
            <div id="modalHistory" style="margin-top: 24px; padding-top: 20px; border-top: 1px dashed #cbd5e1; display:none;">
                <label style="font-size:12px; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:12px; display:block;"><i class="bi bi-clock-history"></i> Payment History (This Month)</label>
                <div id="modalHistoryList" style="display: flex; flex-direction: column; gap: 8px;"></div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
function filterTable() {
    const query = document.getElementById('serviceSearch').value.toLowerCase();
    const rows = document.querySelectorAll('#servicesTable tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(query) ? '' : 'none';
    });
}

function openPaymentModal(serviceId, clientName, serviceName, remaining, historyHtml) {
    document.getElementById('modalServiceId').value = serviceId;
    document.getElementById('modalClientInfo').textContent = clientName + ' — ' + serviceName;
    document.getElementById('modalAmount').value = '';
    document.getElementById('modalAmount').max = remaining;
    document.getElementById('modalRemaining').textContent = 'Remaining: Rs ' + remaining.toLocaleString();
    
    var histSection = document.getElementById('modalHistory');
    var histList = document.getElementById('modalHistoryList');
    if(historyHtml && historyHtml.trim() !== '') {
        histList.innerHTML = historyHtml;
        histSection.style.display = 'block';
    } else {
        histSection.style.display = 'none';
    }
    
    document.getElementById('paymentModal').style.display = 'flex';
}

function closePaymentModal() {
    document.getElementById('paymentModal').style.display = 'none';
}

document.getElementById('paymentModal').addEventListener('click', function(e) {
    if(e.target === this) closePaymentModal();
});
</script>
