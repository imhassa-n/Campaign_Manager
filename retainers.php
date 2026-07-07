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

<!-- Top Bar -->
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
                                $clients = mysqli_query($conn,"SELECT * FROM clients ORDER BY name ASC");
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
                        
                        $today = date('Y-m-d');
                        $next_billing_date = $row['payment_due_date'];
                        
                        $is_due = false;
                        $is_upcoming = false;
                        $badge_html = "";
                        
                        if ($next_billing_date) {
                            if ($next_billing_date <= $today) {
                                $is_due = true;
                                $badge_html = '<span style="font-size: 11px; background: var(--danger-light); color: var(--danger); padding: 2px 6px; border-radius: 4px;"><i class="bi bi-exclamation-circle-fill"></i> Payment Due</span>';
                            } else if ($next_billing_date <= date('Y-m-d', strtotime('+3 days'))) {
                                $is_upcoming = true;
                                $badge_html = '<span style="font-size: 11px; background: #fef3c7; color: #d97706; padding: 2px 6px; border-radius: 4px;"><i class="bi bi-clock-fill"></i> Upcoming Soon</span>';
                            } else {
                                $badge_html = '<span style="font-size: 11px; background: #dcfce7; color: #16a34a; padding: 2px 6px; border-radius: 4px;"><i class="bi bi-check-circle-fill"></i> On Track</span>';
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
                        <?php endif; ?>
                        <td style="font-weight: 500; color: var(--navy-600);">
                            <?php echo date('d M, Y', strtotime($row['start_date'])); ?>
                        </td>
                        <td style="font-weight: 600; color: <?php echo ($is_due) ? 'var(--danger)' : (($is_upcoming) ? '#d97706' : 'var(--navy-800)'); ?>;">
                            <?php echo $next_billing_date ? date('d M, Y', strtotime($next_billing_date)) : '-'; ?>
                            <br><?php echo $badge_html; ?>
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
                            <div style="display: flex; gap: 6px; align-items: center;">
                                
                                <?php if(can('payments')): ?>
                                <?php if($is_due || $is_upcoming) { ?>
                                <!-- Mark Paid Button -->
                                <a href="renew_retainer.php?id=<?php echo $row['id']; ?>" class="action-btn" style="background: #16a34a; color: white; border-color: #16a34a;" title="Mark Month as Paid & Renew" onclick="return confirm('Log payment of Rs <?php echo number_format($budget); ?> and push billing date to next month?')">
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
                                        $msg = "Hello ".$row['client_name'].", your monthly fee of Rs ".number_format($budget)." for '".$row['service_name']."' is due on ".date('d M Y', strtotime($next_billing_date)).". Please arrange the payment.";
                                    } else {
                                        $msg = "Hello ".$row['client_name'].", your monthly fee for '".$row['service_name']."' is due on ".date('d M Y', strtotime($next_billing_date)).". Please arrange the payment.";
                                    }
                                    
                                    $wa_link = "https://wa.me/".str_replace(['+',' ','-'], '', $phone)."?text=".urlencode($msg);
                                ?>
                                <?php 
                                    if(has_permission('can_send_whatsapp')) {
                                ?>
                                <a href="<?php echo $wa_link; ?>" target="_blank" class="action-btn" style="background: #25D366; color: white; border-color: #25D366;" title="Send WhatsApp Reminder">
                                    <i class="bi bi-whatsapp"></i>
                                </a>
                                <?php } ?>
                                <?php } ?>
                                <?php if(has_permission('can_invoice_monthly_clients')) { ?>
                                <a class="action-btn" 
                                   style="background: #f8fafc; color: #0ea5e9; border-color: #bae6fd;" 
                                   href="invoice_service.php?id=<?php echo $row['id']; ?>" 
                                   target="_blank" 
                                   title="Generate Invoice">
                                    <i class="bi bi-receipt"></i>
                                </a>
                                <?php } ?>
                                <a class="action-btn edit"
                                   href="edit_retainer.php?id=<?php echo $row['id']; ?>"
                                   title="Edit">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                <a class="action-btn delete"
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
</script>
