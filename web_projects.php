<?php

session_start();

if(!isset($_SESSION['user']))
{
    header("Location: login.php");
    exit;
}

include 'db.php';
require_once 'auth.php';
require_permission('web_projects');

if(isset($_POST['save']))
{
    $client_id = $_POST['client_id'];
    $service_name = $_POST['service_name'];
    $budget = isset($_POST['budget']) ? $_POST['budget'] : 0;
    $status = $_POST['status']; // Not Started, In Progress, Delivered

    $service_type = "Website Development";
    // Auto-capture creation date, no end date needed
    $start_date = date('Y-m-d');
    $end_date = "0000-00-00"; 
    $billing_cycle = "One-time";
    $advance_amount = !empty($_POST['advance_amount']) ? floatval($_POST['advance_amount']) : 0;
    
    $payment_status = isset($_POST['payment_cleared']) ? 'Cleared' : 'Pending';
    $reminder_date_val = !empty($_POST['reminder_date']) ? $_POST['reminder_date'] : '';
    $payment_due_date = $reminder_date_val;
    $reminder_date = !empty($reminder_date_val) ? "'".$reminder_date_val."'" : "NULL";

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

    header("Location: web_projects.php");
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
            <h1>Web Projects</h1>
            <p>Manage website development projects and client payments</p>
        </div>
    </div>
    <div class="topbar-right">
        <div class="topbar-search">
            <i class="bi bi-search"></i>
            <input type="text" placeholder="Search projects..." id="serviceSearch" onkeyup="filterTable()">
        </div>
    </div>
</div>

<!-- Content -->
<div class="content-wrapper">

    <!-- Add Project Form -->
    <div class="page-card mb-4">
        <div class="page-card-header">
            <h2><i class="bi bi-laptop"></i> Add New Web Project</h2>
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
                            <label class="form-label">Project Name</label>
                            <input type="text"
                                   name="service_name"
                                   class="form-control"
                                   placeholder="E.g. E-commerce Website, Portfolio Site"
                                   required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <?php if(can('payments')): ?>
                    <div class="col-md-3">
                        <div class="form-section">
                            <label class="form-label">Total Budget (Rs)</label>
                            <input type="number"
                                   name="budget"
                                   class="form-control"
                                   placeholder="E.g. 150000"
                                   required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-section">
                            <label class="form-label">Advance Received (Rs) <small style="color:var(--gray-500)">Optional</small></label>
                            <input type="number"
                                   name="advance_amount"
                                   class="form-control"
                                   placeholder="E.g. 50000">
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-3">
                        <div class="form-section">
                            <label class="form-label">Milestone Status</label>
                            <select name="status" class="form-control form-select">
                                <option>Not Started</option>
                                <option selected>In Progress</option>
                                <option>Delivered</option>
                            </select>
                        </div>
                    </div>
                    <?php if(can('payments')): ?>
                    <div class="col-md-3">
                        <div style="background: #ffffff; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 40px; height: 40px; border-radius: 10px; background: #f0fdf4; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                                        <i class="bi bi-credit-card-fill"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; font-size: 14px; color: #1e293b; margin-bottom: 2px;">Full Payment</div>
                                    </div>
                                </div>
                                <label style="position: relative; display: inline-block; width: 44px; height: 24px; margin: 0;">
                                    <input type="checkbox" name="payment_cleared" id="paymentClearedToggle" style="opacity: 0; width: 0; height: 0; position: absolute;">
                                    <span class="custom-slider" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; border-radius: 24px;">
                                        <span class="custom-slider-knob" style="position: absolute; content: ''; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,0.2);"></span>
                                    </span>
                                </label>
                                <style>
                                    #paymentClearedToggle:checked ~ .custom-slider { background-color: #16a34a !important; }
                                    #paymentClearedToggle:checked ~ .custom-slider .custom-slider-knob { transform: translateX(20px); }
                                </style>
                            </div>
                            
                            <div id="reminderDateContainer" style="background: #f0f9ff; padding: 12px; border-radius: 8px; border: 1px solid #bae6fd; margin-top: 12px; transition: all 0.3s ease;">
                                <label class="form-label" style="font-size: 12px; font-weight: 600; color: #0369a1; display: flex; align-items: center; gap: 6px; margin-bottom: 8px;">
                                    <i class="bi bi-clock-history"></i> Next Payment Reminder
                                </label>
                                <input type="date" name="reminder_date" class="form-control" style="border-color: #7dd3fc; background: #ffffff; color: #475569; font-size: 13px; padding: 8px 12px; border-radius: 6px; box-shadow: none;">
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="alert alert-info mt-2" style="font-size: 13px; display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-info-circle-fill"></i>
                    <strong>No dates required:</strong> Start dates are automatically logged invisibly. Focus on Milestone Status instead!
                </div>

                <button type="submit" name="save" class="btn-brand mt-2">
                    <i class="bi bi-plus-circle-fill"></i>
                    Add Web Project
                </button>

            </form>
        </div>
    </div>

    <!-- Web Projects List -->
    <div class="page-card">
        <div class="page-card-header">
            <h2><i class="bi bi-laptop"></i> Web Projects List</h2>
        </div>
        <div class="page-card-body" style="padding: 0;">
            <div class="table-wrapper">
                <table class="modern-table" id="servicesTable">
                    <thead>
                    <tr>
                        <th>Client</th>
                        <th>Project</th>
                        <?php if(can('payments')): ?>
                        <th>Budget</th>
                        <th>Advance</th>
                        <th>Remaining</th>
                        <?php endif; ?>
                        <th>Milestone Status</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $result = mysqli_query($conn,"
                    SELECT services.*, clients.name as client_name, clients.phone as client_phone
                    FROM services
                    LEFT JOIN clients ON services.client_id = clients.id
                    WHERE services.service_type = 'Website Development'
                    ORDER BY services.id DESC
                    ");

                    while($row = mysqli_fetch_assoc($result))
                    {
                        $advance = floatval($row['advance_amount']);
                        $budget = floatval($row['budget']);
                        $remaining = $budget - $advance;
                    ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="width: 30px; height: 30px; border-radius: 50%; background: linear-gradient(135deg, #0ea5e9, #0f172a); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 11px; flex-shrink: 0;">
                                    <?php echo strtoupper(substr($row['client_name'], 0, 1)); ?>
                                </div>
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-weight: 500;"><?php echo $row['client_name']; ?></span>
                                    <span style="font-size: 11px; color: var(--gray-500);">Added: <?php echo date('M Y', strtotime($row['start_date'])); ?></span>
                                </div>
                            </div>
                        </td>
                        <td style="font-weight: 600; color: var(--navy-800);">
                            <?php echo $row['service_name']; ?>
                            <?php if(can('payments')): ?>
                                <?php if($row['payment_status'] == 'Pending') { ?>
                                    <br><span style="font-size: 11px; background: var(--danger-light); color: var(--danger); padding: 2px 6px; border-radius: 4px;">Payment Pending</span>
                                <?php } else { ?>
                                    <br><span style="font-size: 11px; background: #dcfce7; color: #16a34a; padding: 2px 6px; border-radius: 4px;">Payment Cleared</span>
                                <?php } ?>
                            <?php endif; ?>
                        </td>
                        <?php if(can('payments')): ?>
                        <td style="font-weight: 600;">Rs <?php echo number_format($budget); ?></td>
                        <td style="font-weight: 600; color: #16a34a;">
                            <?php echo $advance > 0 ? 'Rs ' . number_format($advance) : '<span style="color:var(--gray-400)">None</span>'; ?>
                        </td>
                        <td style="font-weight: 600; color: <?php echo $remaining > 0 ? 'var(--danger)' : '#16a34a'; ?>;">
                            Rs <?php echo number_format($remaining); ?>
                        </td>
                        <?php endif; ?>
                        <td>
                            <?php
                            if($row['status'] == 'Not Started') {
                                echo '<span style="background: var(--gray-200); color: var(--gray-700); padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">Not Started</span>';
                            } elseif($row['status'] == 'In Progress') {
                                echo '<span style="background: #e0e7ff; color: #4338ca; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;"><i class="bi bi-arrow-repeat"></i> In Progress</span>';
                            } elseif($row['status'] == 'Delivered') {
                                echo '<span style="background: #dcfce7; color: #16a34a; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;"><i class="bi bi-check-circle-fill"></i> Delivered</span>';
                            } else {
                                echo '<span class="status-badge">'.$row['status'].'</span>'; // Fallback
                            }
                            ?>
                        </td>
                        <td>
                            <div style="display: flex; gap: 6px; align-items: center;">
                                <?php if(can('payments') && $row['payment_status'] == 'Pending') { 
                                    $phone = $row['client_phone'];
                                    if(substr($phone, 0, 1) === '0') {
                                        $phone = '+92' . substr($phone, 1);
                                    } elseif (substr($phone, 0, 3) !== '+92') {
                                        $phone = '+92' . ltrim($phone, '+');
                                    }
                                    
                                    $msg = "Hello ".$row['client_name'].", your payment of Rs ".number_format($remaining)." for the '".$row['service_name']."' web project is due. Please clear it";
                                    if($row['reminder_date']) {
                                        $msg .= " by " . date('d M Y', strtotime($row['reminder_date']));
                                    }
                                    $msg .= ".";
                                    
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
                                <?php if(has_permission('can_invoice_web_projects')) { ?>
                                <a class="action-btn" 
                                   style="background: #f8fafc; color: #0ea5e9; border-color: #bae6fd;" 
                                   href="invoice_service.php?id=<?php echo $row['id']; ?>" 
                                   target="_blank" 
                                   title="Generate Invoice">
                                    <i class="bi bi-receipt"></i>
                                </a>
                                <?php } ?>
                                <a class="action-btn edit"
                                   href="edit_web_project.php?id=<?php echo $row['id']; ?>"
                                   title="Edit">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                <a class="action-btn delete"
                                   href="delete_web_project.php?id=<?php echo $row['id']; ?>"
                                   title="Delete"
                                   onclick="return confirm('Are you sure you want to delete this project?')">
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
document.getElementById('paymentClearedToggle').addEventListener('change', function() {
    const reminderContainer = document.getElementById('reminderDateContainer');
    if (this.checked) {
        reminderContainer.style.display = 'none';
        reminderContainer.querySelector('input').value = '';
    } else {
        reminderContainer.style.display = 'block';
    }
});

function filterTable() {
    const query = document.getElementById('serviceSearch').value.toLowerCase();
    const rows = document.querySelectorAll('#servicesTable tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(query) ? '' : 'none';
    });
}
</script>
