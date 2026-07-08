<?php
require 'db.php';
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS settings (setting_key VARCHAR(50) PRIMARY KEY, setting_value TEXT)");
mysqli_query($conn, "INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('whatsapp_phone', ''), ('whatsapp_api_key', '')");
echo "DB Updated";
?>
