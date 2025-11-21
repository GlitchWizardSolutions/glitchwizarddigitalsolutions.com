<?php
require 'assets/includes/admin_config.php';
// Connect to the Client Database using the PDO interface
try {
	$account_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name . ';charset=' . db_charset, db_user, db_pass);
	$account_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	// If there is an error with the connection, stop the script and display the error.
	exit('Failed to connect to the client database!');
}

// Connect to the On the Go Database using the PDO interface
try {
	$onthego_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name2 . ';charset=' . db_charset, db_user, db_pass);
	$onthego_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	// If there is an error with the connection, stop the script and display the error.
	exit('Failed to connect to the on the go database!');
}
// Connect to the Demo Database using the PDO interface
try {
	$demo_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name3 . ';charset=' . db_charset, db_user, db_pass);
	$demo_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	// If there is an error with the connection, stop the script and display the error.
	exit('Failed to connect to demo database!');
}
// Connect to the Accounting Database using the PDO interface
try {
	$accounting_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name4 . ';charset=' . db_charset, db_user, db_pass);
	$accounting_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	// If there is an error with the connection, stop the script and display the error.
	exit('Failed to connect to the accounting database!');
}
try {
	$budget_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name7 . ';charset=' . db_charset, db_user, db_pass);
	$budget_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	// If there is an error with the connection, stop the script and display the error.
	exit('Failed to connect to the client database!');
}
