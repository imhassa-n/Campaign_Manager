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

if(!has_permission('can_invoice_campaigns')) {
    die("Access denied: You do not have permission to invoice Campaigns.");
}

if(!isset($_GET['id'])) {
    die("Invoice ID not provided.");
}

$id = mysqli_real_escape_string($conn, $_GET['id']);
$query = mysqli_query($conn, "
SELECT campaigns.*, clients.name as client_name, clients.email as client_email, clients.phone as client_phone
FROM campaigns
LEFT JOIN clients ON campaigns.client_id = clients.id
WHERE campaigns.id = '$id'
");

if(mysqli_num_rows($query) == 0) {
    die("Invoice not found.");
}

$invoice = mysqli_fetch_assoc($query);

$budget = floatval($invoice['budget']);
$gst = $budget * 0.16;
$total = $budget + $gst;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - <?php echo htmlspecialchars($invoice['campaign_name']); ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --primary: #0ea5e9;
            --primary-dark: #0284c7;
            --text-dark: #0f172a;
            --text-medium: #334155;
            --text-light: #64748b;
            --bg-light: #f8fafc;
            --border-color: #e2e8f0;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: #f1f5f9;
            color: var(--text-dark);
            -webkit-font-smoothing: antialiased;
        }
        
        .invoice-container {
            max-width: 850px;
            margin: 40px auto;
            background: #ffffff;
            padding: 50px 60px;
            border-radius: 16px;
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.05);
        }
        
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 50px;
        }
        
        .agency-logo {
            max-height: 150px;
            max-width: 350px;
            margin-bottom: 20px;
            display: block;
        }
        
        .agency-details p {
            margin: 0 0 4px 0;
            color: var(--text-light);
            font-size: 15px;
            font-weight: 400;
        }
        
        .invoice-title {
            text-align: right;
        }
        
        .invoice-title h1 {
            font-weight: 800;
            color: var(--text-dark);
            text-transform: uppercase;
            letter-spacing: 4px;
            margin-bottom: 8px;
            font-size: 36px;
        }
        
        .invoice-title p {
            margin: 0;
            color: var(--text-light);
            font-weight: 500;
            font-size: 16px;
        }
        
        .details-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            gap: 30px;
        }
        
        .client-box, .invoice-info-box {
            flex: 1;
        }
        
        .box-title {
            font-size: 13px;
            text-transform: uppercase;
            font-weight: 700;
            color: var(--text-light);
            margin-bottom: 12px;
            letter-spacing: 1.5px;
        }
        
        .client-name {
            font-weight: 700;
            font-size: 20px;
            color: var(--text-dark);
            margin-bottom: 8px;
        }
        
        .client-contact {
            color: var(--text-medium);
            font-size: 15px;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .client-contact i {
            color: var(--primary);
        }
        
        .info-table {
            width: 100%;
        }
        
        .info-table td {
            padding: 6px 0;
            font-size: 15px;
        }
        
        .info-label {
            color: var(--text-light);
            font-weight: 500;
            width: 130px;
        }
        
        .info-value {
            font-weight: 600;
            color: var(--text-dark);
            text-align: right;
        }
        
        .modern-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 40px;
        }
        
        .modern-table th {
            background: var(--bg-light);
            color: var(--text-medium);
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 16px 20px;
            border-top: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
        }
        
        .modern-table th:first-child { border-top-left-radius: 8px; border-bottom-left-radius: 8px; border-left: 1px solid var(--border-color); }
        .modern-table th:last-child { border-top-right-radius: 8px; border-bottom-right-radius: 8px; border-right: 1px solid var(--border-color); }
        
        .modern-table td {
            padding: 24px 20px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-dark);
        }
        
        .item-title {
            font-weight: 700;
            font-size: 17px;
            margin-bottom: 4px;
        }
        
        .item-desc {
            font-size: 14px;
            color: var(--text-light);
        }
        
        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 50px;
        }
        
        .totals-box {
            width: 380px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 14px 0;
            font-size: 16px;
        }
        
        .total-row:not(:last-child) {
            border-bottom: 1px solid #f1f5f9;
        }
        
        .total-row.grand-total {
            background: var(--primary);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-top: 15px;
            box-shadow: 0 10px 15px -3px rgba(14, 165, 233, 0.2);
        }
        
        .total-label {
            color: var(--text-light);
            font-weight: 500;
        }
        
        .total-value {
            font-weight: 700;
        }
        
        .grand-total .total-label {
            color: rgba(255,255,255,0.9);
            font-weight: 600;
            font-size: 18px;
        }
        
        .grand-total .total-value {
            color: white;
            font-size: 22px;
        }

        .payment-methods {
            background: var(--bg-light);
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 40px;
            display: flex;
            gap: 40px;
            border: 1px solid var(--border-color);
        }
        
        .payment-method {
            flex: 1;
        }
        
        .payment-method-title {
            font-weight: 700;
            color: var(--text-dark);
            font-size: 16px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .payment-method-title i {
            color: var(--primary);
            font-size: 20px;
        }
        
        .payment-detail {
            display: flex;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .payment-detail-label {
            width: 100px;
            color: var(--text-light);
            font-weight: 500;
        }
        
        .payment-detail-value {
            font-weight: 600;
            color: var(--text-dark);
            flex: 1;
        }
        
        .divider {
            width: 1px;
            background: var(--border-color);
        }

        .footer-note {
            text-align: center;
            color: var(--text-light);
            font-size: 14px;
            padding-top: 30px;
            border-top: 1px solid var(--border-color);
        }
        
        .print-btn-container {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .btn-print {
            background: var(--text-dark);
            color: white;
            border: none;
            padding: 14px 32px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 10px 15px -3px rgba(15, 23, 42, 0.2);
            transition: all 0.3s ease;
            letter-spacing: 0.5px;
        }
        
        .btn-print:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 20px -3px rgba(15, 23, 42, 0.3);
            background: black;
        }
        
        .status-stamp {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 6px;
            font-weight: 800;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }
        
        .status-paid {
            background: #ecfdf5;
            color: #059669;
        }
        
        .status-pending {
            background: #fef2f2;
            color: #dc2626;
        }

        @media print {
            body {
                background: white;
                margin: 0;
                padding: 0;
            }
            .invoice-container {
                box-shadow: none;
                margin: 0;
                padding: 0;
                max-width: 100%;
            }
            .print-btn-container {
                display: none;
            }
            .total-row.grand-total {
                background: var(--text-dark) !important;
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }
            .payment-methods {
                background: var(--bg-light) !important;
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }
            .status-stamp {
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }
        }
    </style>
</head>
<body>

    <div class="print-btn-container mt-4">
        <button class="btn-print" onclick="window.print()">
            <i class="bi bi-printer me-2"></i> Print Document
        </button>
    </div>

    <div class="invoice-container">
        
        <!-- Header Section -->
        <div class="invoice-header">
            <div class="agency-details">
                <img src="assets/logo.png" alt="WebDex Logo" class="agency-logo" onerror="this.outerHTML='<h2 style=\'font-weight:800; color:#0f172a; font-size: 28px; margin-bottom:15px; letter-spacing: -1px;\'>WebDex</h2>'">
                <p>Digital Marketing & Development</p>
                <p>Lahore, Pakistan</p>
                <p>Email: info@webdex.pk</p>
            </div>
            <div class="invoice-title">
                <h1>INVOICE</h1>
                <p># INV-<?php echo str_pad($invoice['id'], 5, '0', STR_PAD_LEFT); ?></p>
            </div>
        </div>

        <!-- Details Section -->
        <div class="details-section">
            <div class="client-box">
                <div class="box-title">Billed To</div>
                <div class="client-name"><?php echo htmlspecialchars($invoice['client_name']); ?></div>
                <?php if(!empty($invoice['client_email'])): ?>
                    <div class="client-contact"><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($invoice['client_email']); ?></div>
                <?php endif; ?>
                <?php if(!empty($invoice['client_phone'])): ?>
                    <div class="client-contact"><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($invoice['client_phone']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="invoice-info-box">
                <div class="box-title">Invoice Details</div>
                <table class="info-table">
                    <tr>
                        <td class="info-label">Issue Date</td>
                        <td class="info-value"><?php echo date('d M, Y'); ?></td>
                    </tr>
                    <tr>
                        <td class="info-label">Campaign Start</td>
                        <td class="info-value"><?php echo date('d M, Y', strtotime($invoice['start_date'])); ?></td>
                    </tr>
                    <tr>
                        <td class="info-label">Campaign End</td>
                        <td class="info-value"><?php echo date('d M, Y', strtotime($invoice['end_date'])); ?></td>
                    </tr>
                    <tr>
                        <td class="info-label">Payment Status</td>
                        <td class="info-value" style="padding-top: 10px;">
                            <?php 
                            if(isset($invoice['payment_status']) && $invoice['payment_status'] == 'Cleared') {
                                echo '<span class="status-stamp status-paid">PAID</span>';
                            } else {
                                echo '<span class="status-stamp status-pending">PENDING</span>';
                            }
                            ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Table Section -->
        <table class="modern-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Description</th>
                    <th style="text-align: center;">Platform</th>
                    <th style="text-align: center;">Timeline</th>
                    <th style="text-align: right;">Amount (Rs)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div class="item-title"><?php echo htmlspecialchars($invoice['campaign_name']); ?></div>
                        <div class="item-desc">Digital Advertising Campaign Management and Ad Spend</div>
                    </td>
                    <td style="text-align: center; vertical-align: middle; font-weight: 600;">
                        <?php echo htmlspecialchars($invoice['campaign_type']); ?>
                    </td>
                    <td style="text-align: center; vertical-align: middle; color: var(--text-medium); font-weight: 500;">
                        <?php 
                            $days = max(1, round((strtotime($invoice['end_date']) - strtotime($invoice['start_date'])) / 86400));
                            echo $days . " Days";
                        ?>
                    </td>
                    <td style="text-align: right; vertical-align: middle; font-weight: 700; font-size: 18px;">
                        <?php echo number_format($budget, 2); ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Totals Section -->
        <div class="totals-section">
            <div class="totals-box">
                <div class="total-row">
                    <span class="total-label">Subtotal</span>
                    <span class="total-value">Rs <?php echo number_format($budget, 2); ?></span>
                </div>
                <div class="total-row">
                    <span class="total-label">GST (16%)</span>
                    <span class="total-value">Rs <?php echo number_format($gst, 2); ?></span>
                </div>
                <div class="total-row grand-total">
                    <span class="total-label">Grand Total</span>
                    <span class="total-value">Rs <?php echo number_format($total, 2); ?></span>
                </div>
            </div>
        </div>

        <!-- Payment Information -->
        <div class="payment-methods">
            <div class="payment-method">
                <div class="payment-method-title">
                    <i class="bi bi-bank"></i> Bank Islamic Transfer
                </div>
                <div class="payment-detail">
                    <span class="payment-detail-label">Title</span>
                    <span class="payment-detail-value">Hassan Ali</span>
                </div>
                <div class="payment-detail">
                    <span class="payment-detail-label">IBAN</span>
                    <span class="payment-detail-value">PK83BKIP0206400221280001</span>
                </div>
                <div class="payment-detail">
                    <span class="payment-detail-label">Account No</span>
                    <span class="payment-detail-value">206400221280001</span>
                </div>
            </div>
            
            <div class="divider"></div>
            
            <div class="payment-method">
                <div class="payment-method-title" style="color: #ed2125;">
                    <i class="bi bi-phone" style="color: #ed2125;"></i> JazzCash Transfer
                </div>
                <div class="payment-detail">
                    <span class="payment-detail-label">Title</span>
                    <span class="payment-detail-value">Hassan Ali</span>
                </div>
                <div class="payment-detail">
                    <span class="payment-detail-label">Mobile No</span>
                    <span class="payment-detail-value">03394050455</span>
                </div>
                <div style="margin-top: 15px; font-size: 13px; color: var(--text-light);">
                    <em>* Please include INV-<?php echo str_pad($invoice['id'], 5, '0', STR_PAD_LEFT); ?> in the reference.</em>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer-note">
            Thank you for your business! This is a computer generated invoice and does not require a physical signature.
        </div>

    </div>

</body>
</html>
