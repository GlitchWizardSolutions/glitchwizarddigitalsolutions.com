<?php
// Include the root "main.php" file and check if user is logged-in...
include_once '../config.php';
include_once '../functions.php';
// Connect to MySQL using the below function
$pdo = pdo_connect_mysql();
// Check if the user is logged-in and is an admin
if (!isset($_SESSION['account_loggedin']) || $_SESSION['account_role'] != 'Admin') {
    header('Location: ../index.php');
    exit;
}
?>