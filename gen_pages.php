<?php
$content = file_get_contents('services.php');

// web_projects.php
$web_content = str_replace('Services', 'Web Projects', $content);
$web_content = str_replace('services', 'web_projects', $web_content);
$web_content = str_replace('servicesTable', 'webProjectsTable', $web_content);
$web_content = str_replace('serviceSearch', 'webProjectSearch', $web_content);

// In web_projects, the query should only select 'Website Development'
// and the form should only insert 'Website Development' as service_type.
// Let's just create these files with standard code generation to be safe.
?>
