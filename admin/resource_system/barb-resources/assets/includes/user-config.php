<?php
/*
LOCATION: client-dashboard/barb-resources/assets/includes/user-config.php

user-config.php files exist in all subfolders due to dynamic links.
*/
session_start();
require  '../../../private/config.php';
$pageName = basename($_SERVER['PHP_SELF']); 
include includes_path . 'main.php';
check_loggedin($pdo);
?>