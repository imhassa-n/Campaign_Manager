<?php
$content = file_get_contents('services.php');

// 1. Titles and Links
$content = str_replace('Services', 'Monthly Retainers', $content);
$content = str_replace('services.php', 'retainers.php', $content);
$content = str_replace('edit_service.php', 'edit_retainer.php', $content);
$content = str_replace('delete_service.php', 'delete_retainer.php', $content);
$content = str_replace('Record New Service', 'Record New Retainer', $content);
$content = str_replace('Service Name', 'Retainer Name', $content);

// 2. Query to fetch only Monthly Retainers
$content = str_replace('SELECT services.*, clients.name as client_name, clients.phone as client_phone FROM services LEFT JOIN clients ON services.client_id = clients.id ORDER BY services.id DESC', "SELECT services.*, clients.name as client_name, clients.phone as client_phone FROM services LEFT JOIN clients ON services.client_id = clients.id WHERE services.service_type = 'Monthly Retainer' ORDER BY services.id DESC", $content);

// 3. Form fields
// We need to remove the Service Type grid
$type_grid = '
                <!-- Service Type Selection -->
                <div class="form-section">
                    <label class="form-label">Select Service Platform</label>
                    <input type="hidden" name="service_type" id="service_type" required>

                    <div class="grid-4 mt-2">
                        <div class="service-type-card" onclick="selectService(\'Monthly Retainer\',this)">
                            <i class="bi bi-person-workspace text-primary"></i>
                            <h6>Monthly Retainer</h6>
                        </div>

                        <div class="service-type-card" onclick="selectService(\'Website Development\',this)">
                            <i class="bi bi-laptop text-info"></i>
                            <h6>Website Dev</h6>
                        </div>

                        <div class="service-type-card" onclick="selectService(\'SEO Optimization\',this)">
                            <i class="bi bi-graph-up-arrow text-success"></i>
                            <h6>SEO Optimization</h6>
                        </div>
                        
                        <div class="service-type-card" onclick="selectService(\'Social Media Management\',this)">
                            <i class="bi bi-phone text-warning"></i>
                            <h6>Social Media Mgt</h6>
                        </div>
                    </div>
                </div>
';

$content = preg_replace('/<!-- Service Type Selection -->.*?<\/div>\s*<\/div>\s*<\/div>/is', '', $content);

// 4. Processing logic (INSERT)
$insert_target = '    $service_type = $_POST[\'service_type\'];
    $start_date = $_POST[\'start_date\'];
    $end_date = $_POST[\'end_date\'];
    $billing_cycle = $_POST[\'billing_cycle\'];';

$insert_replacement = '    $service_type = "Monthly Retainer";
    $start_date = $_POST[\'start_date\'];
    $end_date = $_POST[\'end_date\'];
    $billing_cycle = "Monthly";';
    
$content = str_replace($insert_target, $insert_replacement, $content);

file_put_contents('retainers.php', $content);
echo "retainers.php generated successfully!";
?>
