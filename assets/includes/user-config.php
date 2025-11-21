<?php
session_start();
echo '<!--
 Included Files:
 1. SESSION STARTED - User
 2. USER CONFIG CALL: /public_html/assets/includes/user-config.php-->';
include_once '../private/config.php';
$pageName = basename($_SERVER['PHP_SELF']); 
include includes_path . 'main.php';
check_loggedin($pdo);?>