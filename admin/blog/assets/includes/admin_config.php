<?php
@session_start();
if(file_exists('../../../private/blog_config2025.php')){
include ('../../../private/blog_config2025.php');
}else{
    error_log('../../../private/blog_config2025.php does not exist.');
}

// Use constant from config.php instead of relative path
include public_path . 'client-dashboard/blog/config_settings.php';

// Define missing path constants using public_path constant
if(!defined('admin_includes_path')) define('admin_includes_path', public_path . 'admin/assets/includes/');
if(!defined('process_path')) define('process_path', public_path . 'client-dashboard/assets/includes/process/');

include admin_includes_path . 'main.php';
include admin_includes_path . 'admin_page_setup.php';
include admin_includes_path . 'components.php';
include process_path . 'email-process.php';
require_once __DIR__ . '/../../functions.php';

// Load Graph API email system for blog post notifications
$graph_email_file = public_path . 'lib/graph-email-system.php';
if (!file_exists($graph_email_file)) {
    error_log("CRITICAL: graph-email-system.php not found at: " . $graph_email_file);
} else {
    require_once $graph_email_file;
}

// Initialize blog database connection
try {
    $blog_pdo = new PDO('mysql:host=' . db_host . ';dbname=' . db_name12 . ';charset=' . db_charset, db_user, db_pass);
    $blog_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
    error_log('Failed to connect to blog database from admin: ' . $exception->getMessage());
    die('Failed to connect to blog database: ' . $exception->getMessage());
}
?>