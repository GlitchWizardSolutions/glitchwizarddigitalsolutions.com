<?php
@session_start();
if(file_exists('../../../private/blog_config2025.php')){
include ('../../../private/blog_config2025.php');
}else{
    error_log('../../../private/blog_config2025.php does not exist.');
}
include '../../client-dashboard/blog/config_settings.php';
include admin_includes_path . 'main.php';
include admin_includes_path . 'admin_page_setup.php';
include admin_includes_path . 'components.php';
include process_path . 'email-process.php';
require_once __DIR__ . '/../../functions.php';

// Initialize blog database connection
try {
    $blog_pdo = new PDO('mysql:host=' . db_host . ';dbname=' . db_name12 . ';charset=' . db_charset, db_user, db_pass);
    $blog_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
    error_log('Failed to connect to blog database from admin: ' . $exception->getMessage());
    die('Failed to connect to blog database: ' . $exception->getMessage());
}
?>