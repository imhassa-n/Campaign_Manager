<?php
// cron_whatsapp_followups.php
// Setup a cron job to run this file every morning (e.g., 09:00 AM)
// Example cron command: wget -qO- https://yourdomain.com/campaign_manager/cron_whatsapp_followups.php > /dev/null

require 'db.php'; // ensure this path is correct

$config_file = __DIR__ . '/config_whatsapp.json';

if(!file_exists($config_file)) {
    die("WhatsApp is not configured. Please visit whatsapp_settings.php first.");
}

$data = json_decode(file_get_contents($config_file), true);
$phone_number = $data['phone_number'] ?? '';
$api_key = $data['api_key'] ?? '';

if(empty($phone_number) || empty($api_key)) {
    die("Phone number or API key is missing. Please configure them in whatsapp_settings.php.");
}

$followup_query = mysqli_query($conn,"
SELECT l.*, c.name as client_name 
FROM leads l
LEFT JOIN clients c ON l.client_id = c.id
WHERE l.followup_date = CURDATE() AND l.status = 'Active'
ORDER BY l.followup_date ASC
");

if(mysqli_num_rows($followup_query) > 0) {
    $msg = "🔔 *Daily Follow-ups Due Today*\n\n";
    $count = 1;
    while($row = mysqli_fetch_assoc($followup_query)) {
        // If client_id is not set, use name field from leads if it exists. Wait, the leads table doesn't have client_id in standard schema?
        // Let's check leads schema. Usually leads have a 'name' or 'client_name' column. 
        // Oh, wait, in dashboard.php it's `SELECT * FROM leads WHERE followup_date <= CURDATE()`. So it has `client_name` column.
        $client_name = $row['client_name']; 
        $action = $row['action_type'];
        $service = $row['service_interest'];
        $phone = $row['phone'];
        
        $msg .= "*$count. " . $client_name . "*\n";
        $msg .= "📞 Action: " . $action . "\n";
        $msg .= "🛠️ Service: " . $service . "\n";
        if(!empty($phone)) {
            $msg .= "📱 Phone: " . $phone . "\n";
        }
        $msg .= "\n";
        $count++;
    }

    $msg .= "_Please check the dashboard for more details._";

    // URL encode the message
    $urlMsg = urlencode($msg);

    // CallMeBot API URL
    $url = "https://api.callmebot.com/whatsapp.php?phone=" . urlencode($phone_number) . "&text=" . $urlMsg . "&apikey=" . urlencode($api_key);

    // Send HTTP GET request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Add timeout so cron doesn't hang
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if($httpcode == 200) {
        echo "Messages sent successfully. Response: " . $response;
    } else {
        echo "Failed to send message. HTTP Code: $httpcode. Response: $response";
    }
} else {
    echo "No follow-ups due today.";
}
?>
