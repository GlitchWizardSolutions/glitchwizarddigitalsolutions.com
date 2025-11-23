<?php
ini_set('display_errors', 1); 
ini_set('display_startup_errors', 1); 
error_reporting(E_ALL);

echo "1. Starting...<br>";

require 'assets/includes/admin_config.php';
echo "2. Admin config loaded<br>";

include_once '../assets/includes/components.php';
echo "3. Components loaded<br>";

// Check if the user is logged-in
check_loggedin($pdo, '../../index.php');
echo "4. Login check passed<br>";

// Fetch account details associated with the logged-in user
$stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
$stmt->execute([ $_SESSION['id'] ]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);
echo "5. Account fetched<br>";

// Check if the user is an admin...
if ($account['role'] != 'Admin') {
    exit('You do not have permission to access this page!');
}
echo "6. Admin check passed<br>";

// Connect to the On the Go Database using the PDO interface
try {
	$onthego_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name2 . ';charset=' . db_charset, db_user, db_pass);
	$onthego_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	echo "7. Database connected<br>";
} catch (PDOException $exception) {
	exit('Failed to connect to the on the go database! ' . $exception->getMessage());
}

echo "8. All checks passed!<br>";
echo "Database: " . db_name2 . "<br>";
?>
