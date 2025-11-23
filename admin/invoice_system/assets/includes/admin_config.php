<?php
// production  9/13/24
// refreshed   6/14/25
// ADDED DEFINES.PHP 6-15-25
// re-deployed 6/15/25 VERIFIED
session_start();
include ('../../../private/invoice_system_config.php');
include admin_includes_path . 'main.php';
include admin_includes_path . 'admin_page_setup.php';
include client_invoice_defines . 'defines.php';
// Unified email system already loaded by invoice_system_config.php
?>