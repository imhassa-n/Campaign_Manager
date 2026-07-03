<?php

session_start();

if(!isset($_SESSION['user']))
{
    header("Location: login.php");
    exit;
}

include 'db.php';
require_once 'auth.php';
require_permission('campaigns');

if(isset($_POST['save']))
{
    $client_id = $_POST['client_id'];
    $service_name = $_POST['service_name'];
    $budget = $_POST['budget'];
    $status = $_POST['status'];

    $service_type = $_POST['service_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $billing_cycle = $_POST['billing_cycle'];
    
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
    billing_cycle
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
    '$billing_cycle'
    )
    ");

    header("Location: services.php");
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
            <h1>Services</h1>
            <p>Create and manage advertising services</p>
        </div>
    </div>
    <div class="topbar-right">
        <div class="topbar-search">
            <i class="bi bi-search"></i>
            <input type="text" placeholder="Search services..." id="serviceSearch" onkeyup="filterTable()">
        </div>
    </div>
</div>

<!-- Content -->
<div class="content-wrapper">

    <!-- Add Service Form -->
    <div class="page-card mb-4">
        <div class="page-card-header">
            <h2><i class="bi bi-megaphone-fill"></i> Add New Service</h2>
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
                                $clients = mysqli_query($conn,"SELECT * FROM clients");
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
                            <label class="form-label">Service Name</label>
                            <input type="text"
                                   name="service_name"
                                   class="form-control"
                                   placeholder="Enter service name"
                                   required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-section">
                            <label class="form-label">Budget (Rs)</label>
                            <input type="number"
                                   name="budget"
                                   class="form-control"
                                   placeholder="Enter budget amount"
                                   required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-section">
                            <label class="form-label">Start Date</label>
                            <input type="date"
                                   name="start_date"
                                   class="form-control"
                                   required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-section">
                            <label class="form-label">End Date</label>
                            <input type="date"
                                   name="end_date"
                                   class="form-control"
                                   required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-section">
                            <label class="form-label">Billing Cycle</label>
                            <select name="billing_cycle" class="form-control form-select">
                                <option>One-time</option>
                                <option>Monthly</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-section">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control form-select">
                                <option>Active</option>
                                <option>Paused</option>
                                <option>Completed</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div style="background: #ffffff; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 40px; height: 40px; border-radius: 10px; background: #f0fdf4; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                                        <i class="bi bi-credit-card-fill"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; font-size: 14px; color: #1e293b; margin-bottom: 2px;">Payment Status</div>
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
                                    <i class="bi bi-clock-history"></i> Payment Reminder Date
                                </label>
                                <input type="date" name="reminder_date" class="form-control" style="border-color: #7dd3fc; background: #ffffff; color: #475569; font-size: 13px; padding: 8px 12px; border-radius: 6px; box-shadow: none;">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Service Type Selection -->
                <div class="form-section">
                    <label class="form-label">Select Service Platform</label>
                    <input type="hidden" name="service_type" id="service_type" required>

                    <div class="grid-4 mt-2">
                        <div class="service-type-card" onclick="selectService('Monthly Retainer',this)">
                            <i class="bi bi-person-workspace text-primary"></i>
                            <h6>Monthly Retainer</h6>
                        </div>

                        <div class="service-type-card" onclick="selectService('Website Development',this)">
                            <i class="bi bi-laptop text-info"></i>
                            <h6>Website Dev</h6>
                        </div>

                        <div class="service-type-card" onclick="selectService('SEO Optimization',this)">
                            <i class="bi bi-graph-up-arrow text-success"></i>
                            <h6>SEO Optimization</h6>
                        </div>
                        
                        <div class="service-type-card" onclick="selectService('Social Media Management',this)">
                            <i class="bi bi-phone text-warning"></i>
                            <h6>Social Media Mgt</h6>
                        </div>
</div></div>
                </div>

                <button type="submit" name="save" class="btn-brand mt-2">
                    <i class="bi bi-plus-circle-fill"></i>
                    Save Service
                </button>

            </form>
        </div>
    </div>

    <!-- Services List -->
    <div class="page-card">
        <div class="page-card-header">
            <h2><i class="bi bi-collection-fill"></i> Services List</h2>
        </div>
        <div class="page-card-body" style="padding: 0;">
            <div class="table-wrapper">
                <table class="modern-table" id="servicesTable">
                    <thead>
                    <tr>
                        <th>Client</th>
                        <th>Service</th>
                        <th>Platform</th>
                        <th>Budget</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $result = mysqli_query($conn,"
                    SELECT services.*, clients.name as client_name, clients.phone as client_phone
                    FROM services
                    LEFT JOIN clients
                    ON services.client_id = clients.id
                    ");

                    while($row = mysqli_fetch_assoc($result))
                    {
                    ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="width: 30px; height: 30px; border-radius: 50%; background: linear-gradient(135deg, var(--teal-600), var(--navy-600)); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 11px; flex-shrink: 0;">
                                    <?php echo strtoupper(substr($row['client_name'], 0, 1)); ?>
                                </div>
                                <span style="font-weight: 500;"><?php echo $row['client_name']; ?></span>
                            </div>
                        </td>
                        <td style="font-weight: 600; color: var(--navy-800); display: flex; align-items: center; flex-wrap: wrap; gap: 6px;">
                            <?php echo $row['service_name']; ?>
                            <?php if($row['payment_status'] == 'Pending') { ?>
                                <span style="font-size: 11px; background: var(--danger-light); color: var(--danger); padding: 2px 6px; border-radius: 4px;">Ad Payment Pending</span>
                            <?php } ?>
                        </td>
                        <td><span class="platform-badge"><?php echo $row['service_type']; ?></span></td>
                        <td style="font-weight: 600;">Rs <?php echo number_format($row['budget']); ?></td>
                        <td style="font-weight: 500; color: var(--navy-600);">
                            <?php echo date('d M, Y', strtotime($row['start_date'])); ?>
                        </td>
                        <td style="font-weight: 500; color: var(--navy-600);">
                            <?php echo date('d M, Y', strtotime($row['end_date'])); ?>
                        </td>
                        <td style="font-weight: 500; color: var(--navy-600);">
                            <?php echo $row['payment_due_date'] ? date('d M, Y', strtotime($row['payment_due_date'])) : '-'; ?>
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
                                <?php if($row['payment_status'] == 'Pending') { 
                                    $phone = $row['client_phone'];
                                    if(substr($phone, 0, 1) === '0') {
                                        $phone = '+92' . substr($phone, 1);
                                    } elseif (substr($phone, 0, 3) !== '+92') {
                                        $phone = '+92' . ltrim($phone, '+');
                                    }
                                    
                                    $is_service = in_array($row['service_type'], ['Monthly Service Retainer', 'Website Development']);
                                    $amount = $is_service ? $row['budget'] : $row['budget'] * 1.16;
                                    $gst_text = $is_service ? "" : " (including 16% GST)";
                                    
                                    $msg = "Hello ".$row['client_name'].", your payment of Rs ".number_format($amount).$gst_text." for the '".$row['service_name']."' project is due. Please clear it";
                                    if($row['reminder_date']) {
                                        $msg .= " by " . date('d M Y', strtotime($row['reminder_date']));
                                    }
                                    $msg .= ".";
                                    
                                    $wa_link = "https://wa.me/".str_replace(['+',' ','-'], '', $phone)."?text=".urlencode($msg);
                                ?>
                                <a href="<?php echo $wa_link; ?>" target="_blank" class="action-btn" style="background: #25D366; color: white; border-color: #25D366;" title="Send WhatsApp Reminder">
                                    <i class="bi bi-whatsapp"></i>
                                </a>
                                <?php } ?>
                                <a class="action-btn edit"
                                   href="edit_service.php?id=<?php echo $row['id']; ?>"
                                   title="Edit">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                <a class="action-btn delete"
                                   href="delete_service.php?id=<?php echo $row['id']; ?>"
                                   title="Delete"
                                   onclick="return confirm('Are you sure you want to delete this service?')">
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

function selectService(type, element) {
    document.getElementById('service_type').value = type;

    document.querySelectorAll('.service-type-card')
    .forEach(card => {
        card.classList.remove('selected');
    });

    element.classList.add('selected');
}

function filterTable() {
    const query = document.getElementById('serviceSearch').value.toLowerCase();
    const rows = document.querySelectorAll('#servicesTable tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(query) ? '' : 'none';
    });
}
</script>
