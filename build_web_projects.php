<?php
$content = file_get_contents('services.php');

// 1. Titles and Links
$content = str_replace('Services', 'Web Projects', $content);
$content = str_replace('services.php', 'web_projects.php', $content);
$content = str_replace('edit_service.php', 'edit_web_project.php', $content);
$content = str_replace('delete_service.php', 'delete_web_project.php', $content);
$content = str_replace('Record New Service', 'Record New Web Project', $content);
$content = str_replace('Service Name', 'Project Name', $content);

// 2. Query to fetch only Web Projects
$content = str_replace('SELECT services.*, clients.name as client_name, clients.phone as client_phone FROM services LEFT JOIN clients ON services.client_id = clients.id ORDER BY services.id DESC', "SELECT services.*, clients.name as client_name, clients.phone as client_phone FROM services LEFT JOIN clients ON services.client_id = clients.id WHERE services.service_type = 'Website Development' ORDER BY services.id DESC", $content);

// 3. Form fields
// We need to remove the Service Type grid and add Advance Amount
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

$advance_html = '
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-section">
                            <label class="form-label">Advance Received (Rs) - Optional</label>
                            <input type="number" name="advance_amount" class="form-control" placeholder="E.g. 50000">
                        </div>
                    </div>
                </div>
';

// Because the exact HTML might differ due to spacing, regex is safer
$content = preg_replace('/<!-- Service Type Selection -->.*?<\/div>\s*<\/div>\s*<\/div>/is', $advance_html, $content);

// 4. Processing logic (INSERT)
$insert_target = '    $service_type = $_POST[\'service_type\'];
    $start_date = $_POST[\'start_date\'];
    $end_date = $_POST[\'end_date\'];
    $billing_cycle = $_POST[\'billing_cycle\'];';

$insert_replacement = '    $service_type = "Website Development";
    $start_date = $_POST[\'start_date\'];
    $end_date = $_POST[\'end_date\'];
    $billing_cycle = "One-time";
    $advance_amount = !empty($_POST[\'advance_amount\']) ? $_POST[\'advance_amount\'] : 0;';
    
$content = str_replace($insert_target, $insert_replacement, $content);

// 5. Query execution logic
$query_target = '
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
    \'$client_id\',
    \'$service_name\',
    \'$budget\',
    \'$status\',
    \'$service_type\',
    \'$start_date\',
    \'$end_date\',
    \'$payment_due_date\',
    \'$payment_status\',
    $reminder_date,
    \'$billing_cycle\'
    )
    ");
';

$query_replacement = '
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
    \'$client_id\',
    \'$service_name\',
    \'$budget\',
    \'$status\',
    \'$service_type\',
    \'$start_date\',
    \'$end_date\',
    \'$payment_due_date\',
    \'$payment_status\',
    $reminder_date,
    \'$billing_cycle\',
    \'$advance_amount\'
    )
    ");
    $service_id = mysqli_insert_id($conn);
    
    if($advance_amount > 0) {
        $payment_date = date("Y-m-d");
        mysqli_query($conn, "INSERT INTO payments (client_id, service_id, amount, payment_date) VALUES (\'$client_id\', \'$service_id\', \'$advance_amount\', \'$payment_date\')");
    }
';

$content = str_replace($query_target, $query_replacement, $content);

// 6. Update Table headers
$content = str_replace('<th>End Date</th>', '<th>End Date</th><th>Advance</th>', $content);

$td_target = '                        <td><?php echo date(\'d M, Y\', strtotime($row[\'end_date\'])); ?></td>';
$td_replacement = '                        <td><?php echo date(\'d M, Y\', strtotime($row[\'end_date\'])); ?></td>
                        <td>
                            <?php if($row[\'advance_amount\'] > 0): ?>
                                <span class="badge" style="background: var(--success); color: white;">Rs <?php echo number_format($row[\'advance_amount\']); ?></span>
                            <?php else: ?>
                                <span class="badge" style="background: var(--gray-400); color: white;">None</span>
                            <?php endif; ?>
                        </td>';

$content = str_replace($td_target, $td_replacement, $content);

file_put_contents('web_projects.php', $content);
echo "web_projects.php generated successfully!";
?>
