<?php
echo '<!--
 3. PUBLIC PAGE SET UP: client-dashboard/assets/includes/public-page-setup.php-->';
/*This is called from all public-config.php files.*/
$pageName = basename($_SERVER['PHP_SELF']);
include includes_path . 'doctype.php';
?>