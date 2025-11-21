<?php
/*public-config and user-config.php files exist in all subfolders due to the relative patch of the config file.
calls site-set-up, but that can be called only once.
*/
session_start();
echo '<!--
 Included Files:
 1. SESSION STARTED
 2. PUBLIC CONFIG CALL: client-dashboard/assets/includes/public-config.php-->';
require '../../private/config.php';
include includes_path . 'main.php';
include includes_path . 'public-page-setup.php';
?>