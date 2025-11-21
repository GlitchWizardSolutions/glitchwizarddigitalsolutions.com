<?php
/*  
    EDITED: 10-9-2024
    USAGE: included at the top of pages that require logging in.
    LOCATIONS: user-config.php files exist in all subfolders due to the relative patch of the config file.
*/
if (!session_id()) {
    session_start();
}
require  '../../private/config.php';

// Load unified email system
require_once public_path . 'lib/email-system.php';

$pageName = basename($_SERVER['PHP_SELF']); 
include includes_path . 'main.php';
check_loggedin($pdo);?>