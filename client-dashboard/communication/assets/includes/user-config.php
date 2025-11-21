<?php
/*
LOCATION: client-dashboard/communication/assets/includes/user-config.php

user-config.php files exist in all subfolders due to dynamic links.
*/
if (!session_id()) {
    session_start();
}
echo '<!--
 Included Files:
 1. SESSION STARTED
 2. USER CONFIG CALL: client-dashboard/communication/assets/includes/user-config.php-->';
require  '../../../private/config.php';

// Load unified email system (config.php already loads it, but being explicit for clarity)
// require_once public_path . 'lib/email-system.php'; // Already loaded by config.php

$pageName = basename($_SERVER['PHP_SELF']); 
include includes_path . 'main.php';
check_loggedin($pdo);
?>