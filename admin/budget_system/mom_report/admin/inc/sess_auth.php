<?php 
/**
 * TODO: REWRITE - Replace with main application SSO authentication
 * 
 * This mom_report system currently has its own separate login system.
 * During the budget system rewrite, this should be converted to use the 
 * main application's authentication ($_SESSION['id'], check_loggedin(), etc.)
 * 
 * TEMPORARY DEV BYPASS: Auto-login as dev user in development environment
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// TEMPORARY: Auto-login in development environment
if (defined('ENVIRONMENT') && ENVIRONMENT === 'development' && !isset($_SESSION['userdata'])) {
    // Auto-authenticate as dev user for development
    $_SESSION['userdata'] = array(
        'id' => '-1',
        'firstname' => 'Barbara',
        'lastname' => 'Moore',
        'username' => 'GlitchWizard',
        'login_type' => 1 // Admin
    );
}

// Also check for localhost to handle cases where ENVIRONMENT might not be set yet
if (!isset($_SESSION['userdata']) && 
    (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || 
     strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false)) {
    // Auto-authenticate for localhost development
    $_SESSION['userdata'] = array(
        'id' => '-1',
        'firstname' => 'Barbara',
        'lastname' => 'Moore',
        'username' => 'GlitchWizard',
        'login_type' => 1 // Admin
    );
}

if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') 
    $link = "https"; 
else
    $link = "http"; 
$link .= "://"; 
$link .= $_SERVER['HTTP_HOST']; 
$link .= $_SERVER['REQUEST_URI'];
if(!isset($_SESSION['userdata']) && !strpos($link, 'login.php')){
	redirect('admin/login.php');
}
if(isset($_SESSION['userdata']) && strpos($link, 'login.php')){
	redirect('admin/index.php');
}
$module = array('','admin','faculty','student');
if(isset($_SESSION['userdata']) && (strpos($link, 'index.php') || strpos($link, 'admin/')) && $_SESSION['userdata']['login_type'] !=  1){
	echo "<script>alert('Access Denied!');location.replace('".base_url.$module[$_SESSION['userdata']['login_type']]."');</script>";
    exit;
}
