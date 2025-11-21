<?php
/*
LOCATION: client-dashboard/barb-resources/assets/includes/public-config.php

user-config.php files exist in all subfolders due to dynamic links.
*/
session_start();
include_once '../../../private/config.php';
$pageName = basename($_SERVER['PHP_SELF']); 
include includes_path . 'main.php';

