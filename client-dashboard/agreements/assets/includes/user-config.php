<?php
/*
LOCATION: client-dashboard/agreements/assets/includes/user-config.php

user-config.php files exist in all subfolders due to dynamic links.
*/
session_start();
echo '<!--
 Included Files:
 1. SESSION STARTED
 2. USER CONFIG CALL: client-dashboard/agreements/assets/includes/user-config.php-->';
require  '../../../private/config.php';
$pageName = basename($_SERVER['PHP_SELF']); 
include includes_path . 'main.php';
check_loggedin($pdo);
?>