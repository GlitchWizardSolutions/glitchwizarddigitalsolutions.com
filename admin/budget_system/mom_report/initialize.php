<?php
// Load centralized configuration for secure credentials
if (file_exists(__DIR__ . '/../../../../private/config.php')) {
    require_once __DIR__ . '/../../../../private/config.php';
}

// Environment detection (duplicate from main config to avoid loading conflict)
if (!defined('ENVIRONMENT')) {
    $is_local = in_array($_SERVER['HTTP_HOST'] ?? '', [
        'localhost',
        '127.0.0.1',
        'localhost:3000',
        'localhost:8080',
        '::1'
    ]) || strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost:') === 0;
    
    define('ENVIRONMENT', $is_local ? 'development' : 'production');
}

// Base URL - environment aware (specific for mom_report)
if (!defined('BASE_URL')) {
    if (ENVIRONMENT === 'development') {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        define('BASE_URL', $protocol . '://' . $host . '/public_html/');
    } else {
        define('BASE_URL', 'https://glitchwizarddigitalsolutions.com/');
    }
}

$dev_data = array('id'=>'-1','firstname'=>'Barbara','lastname'=>'Moore','username'=>'GlitchWizard','password'=>'','last_login'=>'','date_updated'=>'','date_added'=>'');

// Mom report specific base_url
if(!defined('base_url')) define('base_url', BASE_URL . 'admin/budget_system/mom_report/');
if(!defined('base_app')) define('base_app', str_replace('\\','/',__DIR__).'/' );
if(!defined('dev_data')) define('dev_data',$dev_data);

// Database credentials - environment aware (loaded from private/config.php)
if (ENVIRONMENT === 'development') {
    if(!defined('DB_SERVER')) define('DB_SERVER', '127.0.0.1:3307');
    if(!defined('DB_USERNAME')) define('DB_USERNAME', 'root');
    if(!defined('DB_PASSWORD')) define('DB_PASSWORD', defined('db_pass') ? db_pass : '');
} else {
    if(!defined('DB_SERVER')) define('DB_SERVER', 'localhost');
    if(!defined('DB_USERNAME')) define('DB_USERNAME', 'glitchwizarddigi_webdev');
    if(!defined('DB_PASSWORD')) define('DB_PASSWORD', defined('db_pass') ? db_pass : '');
}
if(!defined('DB_NAME')) define('DB_NAME', 'glitchwizarddigi_budget_2025');
?>
 
 