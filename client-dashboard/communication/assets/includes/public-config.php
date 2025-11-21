<?php
/*
LOCATION: client-dashboard/tickets/assets/includes/public-config.php

user-config.php files exist in all subfolders due to dynamic links.
*/
session_start();
echo '<!--
 Included Files:
 1. SESSION STARTED
 2. PUBLIC CONFIG CALL: client-dashboard/communication/assets/includes/public-config.php-->';
include_once '../../../private/config.php';
$pageName = basename($_SERVER['PHP_SELF']); 
include includes_path . 'main.php';

