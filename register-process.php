<?php
/*******************************************************************************
LOGIN SYSTEM - register-process.php
LOCATION: /public_html/
DESCRIBE: This is a system process file.
INPUTREQ: .
LOGGEDIN: NO
REQUIRED:  
  SYSTEM: LOGIN SYSTEM
   ADMIN: NO
   PAGES: 
   FILES: php_mailer system
DATABASE: TABLES: accounts
LOG NOTE: PRODUCTION 2024-19-19 - Not in use, would need upgraded to match our system.
*******************************************************************************/
include 'assets/includes/public-config.php';
include_once includes_path . 'main.php';
// Unified email system already loaded by public-config.php
if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['token']) {
	exit('Incorrect token provided!');
}
// Now we check if the data was submitted, isset() function will check if the data exists.
if (!isset($_POST['full_name'], $_POST['username'], $_POST['password'], $_POST['cpassword'], $_POST['email'])) {
	// Could not get the data that should have been sent.
	exit('Please complete the registration form!');
}
// Make sure the submitted registration values are not empty.
if (empty($_POST['full_name']) || empty($_POST['username']) || empty($_POST['password']) || empty($_POST['email'])) {
	// One or more values are empty.
	exit('Please complete the registration form!');
}
// Check to see if the email is valid.
if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
	exit('Please provide a valid email address!');
}
// full_name must contain only characters.
if (!preg_match('/^[a-zA-Z. ]+$/', $_POST['full_name'])) {
    exit('Full Name must contain only letters, periods, and spaces!');
}
// Username must contain only characters and numbers.
if (!preg_match('/^[a-zA-Z0-9]+$/', $_POST['username'])) {
    exit('Username must contain only letters and numbers (no spaces)!');
}
// Password must be between 5 and 20 characters long.
if (strlen($_POST['password']) > 20 || strlen($_POST['password']) < 5) {
	exit('Password must be between 5 and 20 characters long!');
}
// Check if both the password and confirm password fields match
if ($_POST['cpassword'] != $_POST['password']) {
	exit('Passwords do not match!');
}
// Check if the account with that username already exists
$stmt = $pdo->prepare('SELECT * FROM accounts WHERE username = ? OR email = ?');
$stmt->execute([ $_POST['username'], $_POST['email'] ]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);
// Store the result, so we can check if the account exists in the database.
if ($account) {
	// Username already exists
	echo 'Username and/or email exists!';
} else {
	// Username doesn't exist, insert new account
	// We do not want to expose passwords in our database, so hash the password and use password_verify when a user logs in.
	$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
	// Generate unique activation code
		$activation_code = account_activation ? hash('sha256', uniqid() . $_POST['email'] . SECRET_KEY) : 'activated';
	// Default role
	$role = 'Member';
	// Current date
	$date = date('Y-m-d\TH:i:s');
	// Prepare query; prevents SQL injection
	$stmt = $pdo->prepare('INSERT INTO accounts (full_name, username, password, email, activation_code, role, registered, last_seen, approved, ip) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
	$ip = $_SERVER['REMOTE_ADDR'];
    $stmt->execute([ $_POST['full_name'], $_POST['username'], $password, $_POST['email'], $uniqid, $role, $date, $date, 'Approved', $ip ]);
    $email=$_POST['email'];
	// If account activation is required, send activation email

	if (account_activation) {
		// Account activation required, send the user the activation email with the "send_activation_email" function from the "main.php" file
//		send_activation_email($_POST['email'], $uniqid);
	    send_email($_POST['email'], $uniqid, $_POST['username'], 'activation');
		echo "Activation link sent to: " . $email;
	} else {
		// Automatically authenticate the user if the option is enabled
		if (auto_login_after_register) {
			// Regenerate session ID
			session_regenerate_id();
			// Declare session variables
			$_SESSION['loggedin'] = TRUE;
			$_SESSION['name'] = $_POST['username'];
			$_SESSION['id'] = $pdo->lastInsertId();
			$_SESSION['role'] = $role;	
			$_SESSION['email'] = $_POST['email'];
			$_SESSION['full_name'] = $_POST['full_name'];
		} else {
			echo 'You have successfully registered!';
		}
	}
}