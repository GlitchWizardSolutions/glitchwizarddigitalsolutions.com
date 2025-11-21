<?php
require 'assets/includes/admin_config.php';
include 'assets/includes/defines.php';
try {
	$budget_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name7 . ';charset=' . db_charset, db_user, db_pass);
	$budget_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	// If there is an error with the connection, stop the script and display the error.
	exit('Failed to connect to the client database!');
}

// Connect to the budget system's  MySQL database function
if (!function_exists('pdo_connect_budget_db')){
function pdo_connect_budget_db($host = null, $dbname = null, $user = null, $password = null) {
    // Use passed parameters or fall back to constants
    $host = $host ?? db_host;
    $dbname = $dbname ?? db_name7;
    $user = $user ?? db_user;
    $password = $password ?? db_pass;
    
    try {
    	$budget_pdo= new PDO('mysql:host=' . $host . ';dbname=' . $dbname . ';charset=utf8', $user, $password);
        $budget_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $exception) {
    	// If there is an error with the connection, stop the script and display the error.
    	exit('Failed to connect to database!' . $exception->getMessage());
    }
    return $budget_pdo;
 }
}//function exists