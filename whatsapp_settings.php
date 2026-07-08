<?php
session_start();
if(!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$config_file = 'config_whatsapp.json';

if(isset($_POST['save_settings'])) {
    $data = [
        'phone_number' => $_POST['phone_number'],
        'api_key' => $_POST['api_key']
    ];
    file_put_contents($config_file, json_encode($data));
    $success = "WhatsApp Settings saved successfully!";
}

$current_phone = '';
$current_api_key = '';
if(file_exists($config_file)) {
    $data = json_decode(file_get_contents($config_file), true);
    $current_phone = $data['phone_number'] ?? '';
    $current_api_key = $data['api_key'] ?? '';
}

include 'header.php';
?>

<div class="topbar">
    <div class="topbar-left">
        <button class="mobile-toggle" onclick="openSidebar()">
            <i class="bi bi-list"></i>
        </button>
        <div class="topbar-title">
            <h1>WhatsApp Automation Settings</h1>
            <p>Configure CallMeBot API to receive daily follow-up alerts on your WhatsApp.</p>
        </div>
    </div>
</div>

<div class="content-wrapper">
    <div class="row">
        <div class="col-md-6">
            <div class="page-card">
                <div class="page-card-header">
                    <h2><i class="bi bi-whatsapp"></i> CallMeBot Configuration</h2>
                </div>
                <div class="page-card-body">
                    <?php if(isset($success)) { echo "<div class='alert alert-success'><i class='bi bi-check-circle-fill'></i> $success</div>"; } ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Your WhatsApp Number</label>
                            <input type="text" name="phone_number" class="form-control" value="<?php echo htmlspecialchars($current_phone); ?>" placeholder="e.g. +923001234567" required>
                            <small class="text-muted">Include country code with + sign.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">CallMeBot API Key</label>
                            <input type="text" name="api_key" class="form-control" value="<?php echo htmlspecialchars($current_api_key); ?>" placeholder="e.g. 123456" required>
                        </div>
                        <button type="submit" name="save_settings" class="btn btn-brand w-100">
                            <i class="bi bi-save"></i> Save Settings
                        </button>
                    </form>

                    <hr class="my-4">

                    <h5 style="color: var(--navy-800); font-weight: 700;">How to get your Free API Key?</h5>
                    <ol style="margin-top: 10px; font-size: 14px; color: var(--navy-600); line-height: 1.6;">
                        <li>Add the phone number <strong>+34 611 04 87 48</strong> to your Phone Contacts. (Name it CallMeBot)</li>
                        <li>Send this message on WhatsApp to the bot: <br><code style="background: var(--gray-100); padding: 3px 6px; border-radius: 4px; display: inline-block; margin: 5px 0;">I allow callmebot to send me messages</code></li>
                        <li>Wait for the bot to reply. It will reply with your <strong>API Key</strong>.</li>
                        <li>Paste your WhatsApp number and API Key above and Save.</li>
                    </ol>

                    <hr class="my-4">

                    <h5 style="color: var(--navy-800); font-weight: 700;">How to automate this? (Cron Job)</h5>
                    <p style="font-size: 14px; color: var(--navy-600);">
                        To receive the daily notifications automatically, you must setup a Cron Job in your Hostinger Panel to trigger this URL every morning (e.g. at 09:00 AM):
                    </p>
                    <code style="display: block; background: var(--gray-100); padding: 10px; border-radius: 6px; font-size: 13px; color: var(--navy-800); border: 1px solid var(--gray-200); word-break: break-all;">
                        wget -qO- https://<?php echo $_SERVER['HTTP_HOST']; ?>/campaign_manager/cron_whatsapp_followups.php &gt; /dev/null
                    </code>
                    <p style="font-size: 13px; color: var(--gray-500); margin-top: 10px;">
                        <i class="bi bi-info-circle"></i> Alternatively, you can click <a href="cron_whatsapp_followups.php" target="_blank" style="color: var(--brand-500); font-weight: 600;">here</a> to run it manually right now.
                    </p>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
