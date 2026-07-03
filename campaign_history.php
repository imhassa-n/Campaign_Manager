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

$status_filter = isset($_GET['status']) ? $_GET['status'] : 'All';
$payment_filter = isset($_GET['payment']) ? $_GET['payment'] : 'All';

$query_str = "
SELECT campaigns.*, clients.name as client_name, clients.phone as client_phone 
FROM campaigns
LEFT JOIN clients ON campaigns.client_id = clients.id
WHERE 1=1
";

if($status_filter !== 'All') {
    $safe_status = mysqli_real_escape_string($conn, $status_filter);
    $query_str .= " AND campaigns.status = '$safe_status'";
}

if($payment_filter === 'Paid') {
    $query_str .= " AND campaigns.payment_status = 'Cleared'";
} elseif($payment_filter === 'Pending') {
    $query_str .= " AND campaigns.payment_status = 'Pending'";
}

$query_str .= " ORDER BY campaigns.id DESC";

$campaigns_result = mysqli_query($conn, $query_str);

?>

<?php include 'header.php'; ?>

<!-- Top Bar -->
<div class="topbar">
    <div class="topbar-left">
        <button class="mobile-toggle" onclick="openSidebar()">
            <i class="bi bi-list"></i>
        </button>
        <div class="topbar-title">
            <h1>Campaign History</h1>
            <p>Comprehensive archive of all your campaigns</p>
        </div>
    </div>
    <div class="topbar-right">
        <a href="campaigns.php" class="btn-brand-outline">
            <i class="bi bi-arrow-left"></i> Back to Campaigns
        </a>
    </div>
</div>

<!-- Content -->
<div class="content-wrapper">

    <!-- Filters -->
    <div class="mb-4" style="display: flex; gap: 20px; flex-wrap: wrap;">
        <div>
            <span style="font-weight: 600; font-size: 14px; margin-right: 10px; color: var(--navy-800);">Campaign Status:</span>
            <div style="display: inline-flex; gap: 10px;">
                <a href="campaign_history.php?status=All&payment=<?php echo $payment_filter; ?>" class="btn <?php echo $status_filter == 'All' ? 'btn-brand' : 'btn-outline-secondary'; ?>" style="border-radius: 20px; font-size: 14px; font-weight: 600; padding: 6px 16px;">All</a>
                <a href="campaign_history.php?status=Active&payment=<?php echo $payment_filter; ?>" class="btn <?php echo $status_filter == 'Active' ? 'btn-brand' : 'btn-outline-secondary'; ?>" style="border-radius: 20px; font-size: 14px; font-weight: 600; padding: 6px 16px;">Active</a>
                <a href="campaign_history.php?status=Completed&payment=<?php echo $payment_filter; ?>" class="btn <?php echo $status_filter == 'Completed' ? 'btn-brand' : 'btn-outline-secondary'; ?>" style="border-radius: 20px; font-size: 14px; font-weight: 600; padding: 6px 16px;">Completed</a>
                <a href="campaign_history.php?status=Paused&payment=<?php echo $payment_filter; ?>" class="btn <?php echo $status_filter == 'Paused' ? 'btn-brand' : 'btn-outline-secondary'; ?>" style="border-radius: 20px; font-size: 14px; font-weight: 600; padding: 6px 16px;">Paused</a>
            </div>
        </div>
        <div>
            <span style="font-weight: 600; font-size: 14px; margin-right: 10px; color: var(--navy-800);">Payment Status:</span>
            <div style="display: inline-flex; gap: 10px;">
                <a href="campaign_history.php?status=<?php echo $status_filter; ?>&payment=All" class="btn <?php echo $payment_filter == 'All' ? 'btn-brand' : 'btn-outline-secondary'; ?>" style="border-radius: 20px; font-size: 14px; font-weight: 600; padding: 6px 16px;">All</a>
                <a href="campaign_history.php?status=<?php echo $status_filter; ?>&payment=Paid" class="btn <?php echo $payment_filter == 'Paid' ? 'btn-brand' : 'btn-outline-secondary'; ?>" style="border-radius: 20px; font-size: 14px; font-weight: 600; padding: 6px 16px;">Paid</a>
                <a href="campaign_history.php?status=<?php echo $status_filter; ?>&payment=Pending" class="btn <?php echo $payment_filter == 'Pending' ? 'btn-brand' : 'btn-outline-secondary'; ?>" style="border-radius: 20px; font-size: 14px; font-weight: 600; padding: 6px 16px;">Pending</a>
            </div>
        </div>
    </div>

    <!-- Campaigns List -->
    <div class="page-card">
        <div class="page-card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h2><i class="bi bi-archive-fill"></i> Campaigns Archive</h2>
            <div class="topbar-search" style="max-width: 250px; margin: 0; box-shadow: none; border: 1px solid var(--gray-200); border-radius: var(--radius-sm); padding: 6px 12px; display: flex; align-items: center; gap: 8px;">
                <i class="bi bi-search text-gray-400"></i>
                <input type="text" placeholder="Search..." id="campaignSearch" onkeyup="filterTable()" style="border: none; outline: none; width: 100%; font-size: 14px; background: transparent;">
            </div>
        </div>
        <div class="page-card-body" style="padding: 0;">
            <div class="table-wrapper">
                <table class="modern-table premium-table" id="campaignsTable">
                    <thead>
                    <tr>
                        <th onclick="sortTable(0)" style="cursor: pointer;">Client <i class="bi bi-arrow-down-up" style="font-size: 10px; opacity: 0.5;"></i></th>
                        <th onclick="sortTable(1)" style="cursor: pointer;">Campaign <i class="bi bi-arrow-down-up" style="font-size: 10px; opacity: 0.5;"></i></th>
                        <th onclick="sortTable(2)" style="cursor: pointer;">Platform <i class="bi bi-arrow-down-up" style="font-size: 10px; opacity: 0.5;"></i></th>
                        <th onclick="sortTable(3, true)" style="cursor: pointer;">Budget <i class="bi bi-arrow-down-up" style="font-size: 10px; opacity: 0.5;"></i></th>
                        <th onclick="sortTable(4, false, true)" style="cursor: pointer;">Start Date <i class="bi bi-arrow-down-up" style="font-size: 10px; opacity: 0.5;"></i></th>
                        <th onclick="sortTable(5, false, true)" style="cursor: pointer;">End Date <i class="bi bi-arrow-down-up" style="font-size: 10px; opacity: 0.5;"></i></th>
                        <th>Status</th>
                        <th>Payment Status</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    while($row = mysqli_fetch_assoc($campaigns_result))
                    {
                        $is_expiring = ($row['status'] == 'Active' && strtotime($row['end_date']) <= strtotime('today'));
                    ?>
                    <tr <?php if($is_expiring) echo 'style="background-color: #fff1f2;"'; ?>>
                        <td>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="width: 30px; height: 30px; border-radius: 50%; background: linear-gradient(135deg, var(--teal-600), var(--navy-600)); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 11px; flex-shrink: 0;">
                                    <?php echo strtoupper(substr($row['client_name'], 0, 1)); ?>
                                </div>
                                <span style="font-weight: 600;"><?php echo $row['client_name']; ?></span>
                            </div>
                        </td>
                        <td style="font-weight: 600; color: var(--navy-800);">
                            <?php echo $row['campaign_name']; ?>
                        </td>
                        <td>
                            <?php echo '<span class="platform-badge">'.$row['campaign_type'].'</span>'; ?>
                        </td>
                        <td style="font-weight: 600;" data-val="<?php echo $row['budget']; ?>">Rs <?php echo number_format($row['budget']); ?></td>
                        <td style="font-weight: 500; color: var(--navy-600);" data-date="<?php echo $row['start_date']; ?>">
                            <?php echo date('d M, Y', strtotime($row['start_date'])); ?>
                        </td>
                        <td style="font-weight: 500; color: var(--navy-600);" data-date="<?php echo $row['end_date']; ?>">
                            <?php echo date('d M, Y', strtotime($row['end_date'])); ?>
                            <?php if($is_expiring) echo '<br><span style="font-size: 10px; background: var(--danger); color: white; padding: 2px 6px; border-radius: 4px; display: inline-block; margin-top: 4px; animation: pulse 2s infinite;"><i class="bi bi-exclamation-triangle-fill"></i> Stop Campaign Today!</span>'; ?>
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
                            <?php
                            if(isset($row['payment_status']) && $row['payment_status'] == 'Cleared') {
                                echo '<span class="status-badge active" style="background: var(--success-light); color: var(--success);"><i class="bi bi-check-circle-fill"></i> Paid</span>';
                            } else {
                                echo '<span class="status-badge paused" style="background: var(--danger-light); color: var(--danger);"><i class="bi bi-exclamation-circle-fill"></i> Pending Payment</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <div style="display: flex; gap: 6px; align-items: center;">
                                <a href="edit_campaign.php?id=<?php echo $row['id']; ?>&source=history" class="action-btn edit" title="Details" style="background: var(--brand-50); color: var(--brand); border-color: var(--brand-200); text-decoration: none; padding: 4px 10px; border-radius: 4px; font-size: 12px; display: inline-flex; align-items: center; gap: 4px; width: auto; height: auto; white-space: nowrap;">
                                    <i class="bi bi-eye-fill"></i> Details
                                </a>
                                <a href="invoice_campaign.php?id=<?php echo $row['id']; ?>" target="_blank" class="action-btn" title="Invoice" style="background: #f0f9ff; color: #0ea5e9; border-color: #bae6fd; text-decoration: none; padding: 4px 10px; border-radius: 4px; font-size: 12px; display: inline-flex; align-items: center; gap: 4px; width: auto; height: auto; white-space: nowrap;">
                                    <i class="bi bi-receipt"></i> Invoice
                                </a>
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
                                    
                                    if($row['status'] == 'Active') {
                                        if($is_pending) {
                                            $msg .= "Your *".$row['campaign_name']."* ad campaign is live and running!\n\nJust a gentle reminder that the payment of *Rs ".$amount."* (inc. 16% GST) is currently pending.".$reminder_text." Please process it so we can keep your ads running smoothly without interruptions.";
                                        } else {
                                            $msg .= "Great news: Your *".$row['campaign_name']."* ad campaign is live!\n\nIt is scheduled to run for $days days (from $start_date_str to $end_date_str). We are monitoring it closely to ensure the best results. Sit back and relax!";
                                        }
                                    } elseif($row['status'] == 'Paused') {
                                        if($is_pending) {
                                            $msg .= "Your *".$row['campaign_name']."* ad campaign is currently paused.\n\nWe noticed the payment of *Rs ".$amount."* (inc. 16% GST) is still pending.".$reminder_text." Please clear it so we can resume your ads right away!";
                                        } else {
                                            $msg .= "Just a quick update: Your *".$row['campaign_name']."* ad campaign is currently paused.\n\nPlease get in touch with us to resolve any issues so we can get your ads running again!";
                                        }
                                    } else {
                                        if($is_pending) {
                                            $msg .= "Your *".$row['campaign_name']."* ad campaign has successfully completed its $days-day run ($start_date_str to $end_date_str). We hope you loved the results!\n\nA quick reminder that the payment of *Rs ".$amount."* (inc. 16% GST) is still pending.".$reminder_text."\n\nWe would also love to hear your feedback on the ad's performance!";
                                        } else {
                                            $msg .= "Your *".$row['campaign_name']."* ad campaign has successfully wrapped up after running for $days days ($start_date_str to $end_date_str)!\n\nWe hope you are thrilled with the results. We would highly appreciate it if you could share your feedback or a review about the ad performance. Thank you for trusting us!";
                                        }
                                    }
                                    
                                    $wa_link = "https://wa.me/".str_replace(['+',' ','-'], '', $phone)."?text=".urlencode($msg);
                                ?>
                                <a href="<?php echo $wa_link; ?>" target="_blank" class="action-btn" style="background: #25D366; color: white; border-color: #25D366; padding: 4px 10px; border-radius: 4px; font-size: 12px; display: inline-flex; align-items: center; gap: 4px; text-decoration: none; width: auto; height: auto; white-space: nowrap;" title="Send WhatsApp Message">
                                    <i class="bi bi-whatsapp"></i> WhatsApp
                                </a>
                                <?php } ?>
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
let sortDirections = [true, true, true, true, true, true]; // keep track of asc/desc

function sortTable(columnIndex, isNumeric = false, isDate = false) {
    const table = document.getElementById("campaignsTable");
    const tbody = table.querySelector("tbody");
    const rows = Array.from(tbody.querySelectorAll("tr"));
    
    // Toggle direction
    const asc = sortDirections[columnIndex];
    sortDirections[columnIndex] = !asc;

    rows.sort((a, b) => {
        let valA, valB;
        
        const cellA = a.querySelectorAll("td")[columnIndex];
        const cellB = b.querySelectorAll("td")[columnIndex];
        
        if (isDate) {
            valA = new Date(cellA.getAttribute('data-date') || "1970-01-01").getTime();
            valB = new Date(cellB.getAttribute('data-date') || "1970-01-01").getTime();
        } else if (isNumeric) {
            valA = parseFloat(cellA.getAttribute('data-val') || 0);
            valB = parseFloat(cellB.getAttribute('data-val') || 0);
        } else {
            valA = cellA.textContent.trim().toLowerCase();
            valB = cellB.textContent.trim().toLowerCase();
        }

        if (valA < valB) return asc ? -1 : 1;
        if (valA > valB) return asc ? 1 : -1;
        return 0;
    });

    // Re-append sorted rows
    tbody.innerHTML = "";
    rows.forEach(row => tbody.appendChild(row));
}

function filterTable() {
    const query = document.getElementById('campaignSearch').value.toLowerCase();
    const rows = document.querySelectorAll('#campaignsTable tbody tr');
    rows.forEach(row => {
        const tds = row.querySelectorAll("td");
        if(tds.length >= 3) {
            const clientName = tds[0].textContent.toLowerCase();
            const campaignName = tds[1].textContent.toLowerCase();
            const platform = tds[2].textContent.toLowerCase();
            
            const text = clientName + " " + campaignName + " " + platform;
            row.style.display = text.includes(query) ? '' : 'none';
        } else {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        }
    });
}
</script>
