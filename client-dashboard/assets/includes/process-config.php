<?php
session_start();
echo '<!--
 -- SESSION RE STARTED
 -- PROCESS CONFIG: client-dashboard/assets/includes/process-config.php-->';
?>
require '../../private/config.php';
include includes_path . 'main.php';?>