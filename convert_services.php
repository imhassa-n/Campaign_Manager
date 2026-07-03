<?php
$content = file_get_contents('services.php');

$replacements = [
    'Campaigns' => 'Services',
    'campaigns' => 'services',
    'Campaign' => 'Service',
    'campaign' => 'service',
    'campaign_type' => 'service_type',
    'campaign_name' => 'service_name',
    'campaigns.php' => 'services.php',
    'edit_campaign.php' => 'edit_service.php',
    'delete_campaign.php' => 'delete_service.php',
    'campaignSearch' => 'serviceSearch',
    'campaignsTable' => 'servicesTable',
    'campaign-type-card' => 'service-type-card'
];

foreach($replacements as $search => $replace) {
    // We want to be careful not to replace 'campaign' globally in CSS classes or things that might break, but we do want mostly a clean swap.
    // Let's do simple str_replace
    $content = str_replace($search, $replace, $content);
}

// Fix back some specific things if needed, e.g., 'service_id' vs 'campaign_id'.
$content = str_replace('service_id', 'id', $content); // if $row['campaign_id'] became $row['service_id'] ? Wait, in campaigns table it was just 'id'.
// So if we replaced campaigns -> services, INSERT INTO services (..., service_name...) works.

// Specifically for the types grid
$grid_html = "
                        <div class=\"service-type-card\" onclick=\"selectService('Monthly Retainer',this)\">
                            <i class=\"bi bi-person-workspace text-primary\"></i>
                            <h6>Monthly Retainer</h6>
                        </div>

                        <div class=\"service-type-card\" onclick=\"selectService('Website Development',this)\">
                            <i class=\"bi bi-laptop text-info\"></i>
                            <h6>Website Dev</h6>
                        </div>

                        <div class=\"service-type-card\" onclick=\"selectService('SEO Optimization',this)\">
                            <i class=\"bi bi-graph-up-arrow text-success\"></i>
                            <h6>SEO Optimization</h6>
                        </div>
                        
                        <div class=\"service-type-card\" onclick=\"selectService('Social Media Management',this)\">
                            <i class=\"bi bi-phone text-warning\"></i>
                            <h6>Social Media Mgt</h6>
                        </div>
";

// Let's just manually replace the grid
$content = preg_replace('/<div class="grid-4 mt-2">.*?<\/div>\s*<\/div>/is', '<div class="grid-4 mt-2">' . $grid_html . '</div></div>', $content);

file_put_contents('services.php', $content);
echo "services.php updated!";
?>
