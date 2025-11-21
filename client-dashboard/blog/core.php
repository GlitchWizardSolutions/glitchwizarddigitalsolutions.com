<?php
// phpBlog version
$phpblog_version = "2.4";
// Configuration
require_once 'assets/includes/blog-config.php';

// Load unified authentication system
require_once public_path . 'lib/auth-system.php';

try {
    $pdo = new PDO('mysql:host=' . db_host . ';dbname=' . db_name . ';charset=' . db_charset, db_user, db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
    // If there is an error with the connection, stop the script and display the error.
    trigger_error("Failure to connect to the login database", E_USER_ERROR);
    exit('Failed to connect to database: ' . $exception->getMessage());
}
/*Connect to the envato blog database*/
try {
    $blog_pdo = new PDO('mysql:host=' . db_host . ';dbname=' . db_name12 . ';charset=' . db_charset, db_user, db_pass);
    $blog_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
   
} catch (PDOException $exception) {
    // If there is an error with the connection, stop the script and display the error.
    error_log('failed to connect to blog pdo database from client dashboard main.');
    exit('Failed to connect to the blog pdo database: ' . $exception->getMessage());
}
// Data Sanitization
$_GET  = filter_input_array(INPUT_GET, FILTER_SANITIZE_SPECIAL_CHARS);
$_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);

// Blog authentication wrapper - uses unified check_loggedin() from lib/auth-system.php
if (!function_exists('check_bloggedin')){
function check_bloggedin($blog_pdo, $pdo, $redirect_file = null) {
    global $settings;
    if ($redirect_file === null) {
        $redirect_file = $settings['blog_site_url'] . 'blog.php';
    }
    
    // Use unified authentication system
    check_loggedin($pdo, $redirect_file);
}
}
check_bloggedin($blog_pdo, $pdo);
// Global site settings
$site_name = $settings['site_name'] ?? 'My Blog';
$site_description = $settings['description'] ?? '';
$site_keywords = $settings['keywords'] ?? '';
$theme = $settings['theme'] ?? 'default';
$rtl = $settings['rtl'] ?? 'off';
//functions.php is loaded inside config, along with config_settings...
?>