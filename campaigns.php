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
    $campaign_name = $_POST['campaign_name'];
    $budget = isset($_POST['budget']) ? $_POST['budget'] : 0;
    $status = $_POST['status'];

    $campaign_type = $_POST['campaign_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $billing_cycle = $_POST['billing_cycle'];
    
    $payment_status = isset($_POST['payment_cleared']) ? 'Cleared' : 'Pending';
    $reminder_date_val = !empty($_POST['reminder_date']) ? $_POST['reminder_date'] : '';
    $payment_due_date = $reminder_date_val;
    $reminder_date = !empty($reminder_date_val) ? "'".$reminder_date_val."'" : "NULL";

    mysqli_query($conn,"
    INSERT INTO campaigns
    (
    client_id,
    campaign_name,
    budget,
    status,
    campaign_type,
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
    '$campaign_name',
    '$budget',
    '$status',
    '$campaign_type',
    '$start_date',
    '$end_date',
    '$payment_due_date',
    '$payment_status',
    $reminder_date,
    '$billing_cycle'
    )
    ");

    header("Location: campaigns.php");
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
            <h1>Ad Campaigns</h1>
            <p>Create and manage advertising campaigns and media buys</p>
        </div>
    </div>
    <div class="topbar-right">
        <div class="topbar-search">
            <i class="bi bi-search"></i>
            <input type="text" placeholder="Search campaigns..." id="campaignSearch" onkeyup="filterTable()">
        </div>
    </div>
</div>

<!-- Content -->
<div class="content-wrapper">

    <!-- Add Campaign Form -->
    <div class="page-card mb-4 glass-panel" style="background: white;">
        <div class="page-card-header">
            <h2><i class="bi bi-megaphone-fill"></i> Add New Campaign</h2>
        </div>
        <div class="page-card-body">
            <form method="POST" id="campaignForm">

                <div class="section-title"><i class="bi bi-info-circle-fill"></i> Basic Details</div>
                <div class="row mb-3">
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
                            <label class="form-label">Campaign Name</label>
                            <input type="text"
                                   name="campaign_name"
                                   class="form-control"
                                   placeholder="E.g. Summer Sale Meta Ads"
                                   required>
                        </div>
                    </div>
                </div>

                <div class="section-title mt-4"><i class="bi bi-laptop"></i> Platform</div>
                <div class="form-section">
                    <input type="hidden" name="campaign_type" id="campaign_type" required>
                    <div class="platform-pill-container">
                        <div class="platform-pill" onclick="selectCampaign('Facebook Ads',this)">
                            <i class="bi bi-facebook text-primary"></i> Facebook
                        </div>
                        <div class="platform-pill" onclick="selectCampaign('Instagram Ads',this)">
                            <i class="bi bi-instagram text-danger"></i> Instagram
                        </div>
                        <div class="platform-pill" onclick="selectCampaign('Facebook + Instagram Ads',this)">
                            <i class="bi bi-meta text-primary"></i> Meta (FB+IG)
                        </div>
                        <div class="platform-pill" onclick="selectCampaign('Google Ads',this)">
                            <i class="bi bi-google text-success"></i> Google Ads
                        </div>
                        <div class="platform-pill" onclick="selectCampaign('YouTube Ads',this)">
                            <i class="bi bi-youtube text-danger"></i> YouTube
                        </div>
                        <div class="platform-pill" onclick="selectCampaign('TikTok Ads',this)">
                            <i class="bi bi-tiktok"></i> TikTok
                        </div>
                        <div class="platform-pill" onclick="selectCampaign('SEO Ads',this)">
                            <i class="bi bi-graph-up-arrow text-success"></i> SEO
                        </div>
                    </div>
                </div>

                <div class="section-title mt-4"><i class="bi bi-cash-coin"></i> Timeline & Financials</div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-section">
                            <label class="form-label">Ad Budget (Rs)</label>
                            <input type="number" name="budget" class="form-control" placeholder="E.g. 50000" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-section">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-section">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control" required>
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

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-section">
                            <label class="form-label">Billing Cycle</label>
                            <select name="billing_cycle" class="form-control form-select">
                                <option>One-time</option>
                                <option>Monthly</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div style="background: #ffffff; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 40px; height: 40px; border-radius: 10px; background: #f0fdf4; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                                        <i class="bi bi-credit-card-fill"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; font-size: 14px; color: #1e293b; margin-bottom: 2px;">Initial Payment</div>
                                        <div style="font-size: 12px; color: #64748b; font-weight: normal;">Mark as cleared if paid</div>
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
                                    <i class="bi bi-clock-history"></i> Follow-up Reminder Date
                                </label>
                                <input type="date" name="reminder_date" class="form-control" style="border-color: #7dd3fc; background: #ffffff; color: #475569; font-size: 13px; padding: 8px 12px; border-radius: 6px; box-shadow: none;">
                            </div>
                        </div>
                    </div>
                </div>

                <hr style="border-color: var(--gray-200); margin: 24px 0;">

                <button type="submit" name="save" class="btn-brand">
                    <i class="bi bi-plus-circle-fill"></i>
                    Launch Campaign
                </button>

            </form>
        </div>
    </div>

    <!-- Campaigns List -->
    <div class="page-card">
        <div class="page-card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h2><i class="bi bi-collection-fill"></i> Campaigns Log</h2>
            <a href="campaign_history.php" class="btn-brand-outline btn-sm">
                <i class="bi bi-clock-history"></i> View Full History
            </a>
        </div>
        <div class="page-card-body" style="padding: 0;">
            <div class="table-wrapper">
                <table class="modern-table premium-table" id="campaignsTable">
                    <thead>
                    <tr>
                        <th>Client</th>
                        <th>Campaign</th>
                        <th>Platform</th>
                        <th>Budget (+16% GST)</th>
                        <th>Timeline</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $result = mysqli_query($conn,"
                    SELECT campaigns.*, clients.name as client_name, clients.phone as client_phone
                    FROM campaigns
                    LEFT JOIN clients ON campaigns.client_id = clients.id
                    ORDER BY campaigns.id DESC
                    ");

                    while($row = mysqli_fetch_assoc($result))
                    {
                        $is_expiring = ($row['status'] == 'Active' && strtotime($row['end_date']) <= strtotime('today'));
                    ?>
                    <tr <?php if($is_expiring) echo 'style="background-color: #fff1f2;"'; ?>>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, var(--teal-600), var(--navy-600)); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 11px; flex-shrink: 0;">
                                    <?php echo strtoupper(substr($row['client_name'], 0, 1)); ?>
                                </div>
                                <span style="font-weight: 500;"><?php echo $row['client_name']; ?></span>
                            </div>
                        </td>
                        <td style="font-weight: 600; color: var(--navy-800);">
                            <?php echo $row['campaign_name']; ?>
                            <?php if($row['payment_status'] == 'Pending') { ?>
                                <br><span style="font-size: 10px; background: var(--danger-light); color: var(--danger); padding: 2px 6px; border-radius: 4px; display: inline-block; margin-top: 4px;"><i class="bi bi-exclamation-circle-fill"></i> Payment Pending</span>
                            <?php } ?>
                        </td>
                        <td><span class="platform-badge"><i class="bi bi-tag-fill me-1"></i> <?php echo $row['campaign_type']; ?></span></td>
                        <td style="font-weight: 600; color: var(--navy-800);">
                            Rs <?php echo number_format($row['budget']); ?>
                            <div style="font-size: 11px; color: var(--gray-500); font-weight: 500; margin-top: 2px;">With GST: Rs <?php echo number_format($row['budget'] * 1.16); ?></div>
                        </td>
                        <td style="font-weight: 500; color: var(--gray-600); font-size: 12px;">
                            <div><i class="bi bi-play-circle text-success me-1"></i> <?php echo date('d M, Y', strtotime($row['start_date'])); ?></div>
                            <div class="mt-1"><i class="bi bi-stop-circle text-danger me-1"></i> <?php echo date('d M, Y', strtotime($row['end_date'])); ?>
                                <?php if($is_expiring) echo '<br><span style="font-size: 10px; background: var(--danger); color: white; padding: 2px 6px; border-radius: 4px; display: inline-block; margin-top: 4px; animation: pulse 2s infinite;"><i class="bi bi-exclamation-triangle-fill"></i> Stop Campaign Today!</span>'; ?>
                            </div>
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
                                <?php 
                                $phone = isset($row['client_phone']) ? $row['client_phone'] : '';
                                if($phone) {
                                    if(substr($phone, 0, 1) === '0') {
                                        $phone = '+92' . substr($phone, 1);
                                    } elseif (substr($phone, 0, 3) !== '+92') {
                                        $phone = '+92' . ltrim($phone, '+');
                                    }
                                    
                                    $start_date_str = date('d M Y', strtotime($row['start_date']));
                                    $end_date_str = date('d M Y', strtotime($row['end_date']));
                                    $days = max(1, round((strtotime($row['end_date']) - strtotime($row['start_date'])) / 86400));
                                    $amount = number_format($row['budget'] * 1.16);
                                    
                                    $is_pending = (isset($row['payment_status']) && $row['payment_status'] == 'Pending');
                                    $reminder_text = "";
                                    if($is_pending) {
                                        if(!empty($row['reminder_date'])) {
                                            $reminder_text = " Please clear it by *" . date('d M Y', strtotime($row['reminder_date'])) . "*.";
                                        } else {
                                            $reminder_text = " Please clear it at your earliest convenience.";
                                        }
                                    }
                                    
                                    $msg = "Hi ".$row['client_name']."!\n\n";
                                    
                                    $payment_amount_text = "the payment of *Rs ".$amount."* (inc. 16% GST) is currently pending.";
                                    $payment_amount_text_paused = "the payment of *Rs ".$amount."* (inc. 16% GST) is still pending.";
                                    
                                    if($row['status'] == 'Active') {
                                        if($is_pending) {
                                            $msg .= "Your *".$row['campaign_name']."* ad campaign is live and running!\n\nJust a gentle reminder that ".$payment_amount_text.$reminder_text." Please process it so we can keep your ads running smoothly without interruptions.";
                                        } else {
                                            $msg .= "Great news: Your *".$row['campaign_name']."* ad campaign is live!\n\nIt is scheduled to run for $days days (from $start_date_str to $end_date_str). We are monitoring it closely to ensure the best results. Sit back and relax!";
                                        }
                                    } elseif($row['status'] == 'Paused') {
                                        if($is_pending) {
                                            $msg .= "Your *".$row['campaign_name']."* ad campaign is currently paused.\n\nWe noticed ".$payment_amount_text_paused.$reminder_text." Please clear it so we can resume your ads right away!";
                                        } else {
                                            $msg .= "Just a quick update: Your *".$row['campaign_name']."* ad campaign is currently paused.\n\nPlease get in touch with us to resolve any issues so we can get your ads running again!";
                                        }
                                    } else {
                                        if($is_pending) {
                                            $msg .= "Your *".$row['campaign_name']."* ad campaign has successfully completed its $days-day run ($start_date_str to $end_date_str). We hope you loved the results!\n\nA quick reminder that ".$payment_amount_text_paused.$reminder_text."\n\nWe would also love to hear your feedback on the ad's performance!";
                                        } else {
                                            $msg .= "Your *".$row['campaign_name']."* ad campaign has successfully wrapped up after running for $days days ($start_date_str to $end_date_str)!\n\nWe hope you are thrilled with the results. We would highly appreciate it if you could share your feedback or a review about the ad performance. Thank you for trusting us!";
                                        }
                                    }
                                    
                                    $wa_link = "https://wa.me/".str_replace(['+',' ','-'], '', $phone)."?text=".urlencode($msg);
                                ?>
                                <?php 
                                    if(has_permission('can_send_whatsapp')) {
                                ?>
                                <a href="<?php echo $wa_link; ?>" target="_blank" class="action-btn" style="background: #25D366; color: white; border-color: #25D366;" title="Send WhatsApp Message">
                                    <i class="bi bi-whatsapp"></i>
                                </a>
                                <?php } ?>
                                <?php } ?>
                                <?php if(has_permission('can_invoice_campaigns')) { ?>
                                <a class="action-btn" 
                                   style="background: #f8fafc; color: #0ea5e9; border-color: #bae6fd;" 
                                   href="invoice_campaign.php?id=<?php echo $row['id']; ?>" 
                                   target="_blank" 
                                   title="Generate Invoice">
                                    <i class="bi bi-receipt"></i>
                                </a>
                                <?php } ?>
                                <a class="action-btn edit"
                                   href="edit_campaign.php?id=<?php echo $row['id']; ?>"
                                   title="Edit">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                <a class="action-btn delete"
                                   href="delete_campaign.php?id=<?php echo $row['id']; ?>"
                                   title="Delete"
                                   onclick="return confirm('Are you sure you want to delete this campaign?')">
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

function selectCampaign(type, element) {
    document.getElementById('campaign_type').value = type;

    document.querySelectorAll('.platform-pill')
    .forEach(pill => {
        pill.classList.remove('selected');
    });

    element.classList.add('selected');
}

function filterTable() {
    const query = document.getElementById('campaignSearch').value.toLowerCase();
    const rows = document.querySelectorAll('#campaignsTable tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(query) ? '' : 'none';
    });
}
</script>
