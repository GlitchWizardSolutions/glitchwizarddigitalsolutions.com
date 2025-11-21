<?php
// phpBlog version
$phpblog_version = "2.4";
// ✅ must come after all session-related ini_set and cookie params
//Set longer maxlifetime of the session (7 days)
@ini_set( "session.gc_maxlifetime", '604800');
// Set session and cookie lifetime (7 days)
$cookie_lifetime = 60 * 60 * 24 * 7;
session_set_cookie_params($cookie_lifetime);
// Set longer cookie lifetime of the session (7 days)
@ini_set( "session.cookie_lifetime", '604800');
// Configuration
session_start();
// Load blog config (has database settings and paths)
require '../../../private/blog_config2025.php';
// Load dashboard main.php (has $pdo, $blog_pdo, and authentication)
include includes_path . 'main.php';
include "config_settings.php";
include "functions.php";
$pageName = basename($_SERVER['PHP_SELF']);
$debug='Yes';
error_log('***ATTENTION ***');
error_log('***ATTENTION *** DEBUG ($debug) value is set to ' . $debug); 
error_log('***ATTENTION *** in blog/assets/includes/blog-config.php');
error_log('***ATTENTION ***');
?>