<?php
/*  
    EDITED: 12-9-2024
    USAGE: included at the top of pages that require logging in.
    LOCATIONS: user-config.php files exist in all subfolders due to the relative patch of the config file.
*/
session_start();
require  '../../private/invoice_system_config.php';
include public_path .'/client-invoices/defines.php'; 
// Unified email system already loaded by invoice_system_config.php
$pageName = basename($_SERVER['PHP_SELF']); 
include includes_path . 'main.php';