<?php
include 'assets/includes/process-config.php';

// Check if IP is blocked from too many failed attempts
if (!can_attempt_login($pdo)) {
	exit('You cannot login right now! Please try again later!');
}

if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['token']) {
	exit('Token expired. Please refresh your browser!');
}
// Now we check if the data from the login form was submitted, isset() will check if the data exists.
if (!isset($_POST['username'], $_POST['password'])) {
    $login_attempts = login_attempts($pdo);
	// Could not retrieve the captured data, output error
	exit('Please fill both the username and password fields!');
}

// Preparing the SQL statement will prevent SQL injection
$stmt = $pdo->prepare('SELECT * FROM accounts WHERE username = ?');
$stmt->execute([ $_POST['username'] ]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);
   
// Check if the account exists
if ($account) {
// Account exists... Verify the password
	if (password_verify($_POST['password'], $account['password'])) {
		// Check if the account is activated
		 if(account_activation && $account['activation_code'] != 'activated' && $account['activation_code'] != 'deactivated') {
			// User has not activated their account, output the message
	       echo 'Error: Please activate your account to login! <br>Click <a href="resend-activation.php">here</a> to resend the activation email.';
		} else if($account['activation_code'] == 'deactivated') {
			echo 'Error: Your account has been deactivated!';
		} else if (account_approval && !$account['approved']) {
			// The account is not approved
			echo 'Error: Your account has not been approved yet!';
		} else if($_SERVER['REMOTE_ADDR'] != $account['ip']) {
	        // Two-factor authentication required - IP address doesn't match saved IP
			// NOTE: Currently stores only one IP per user. Future enhancement: store multiple trusted IPs
        	$_SESSION['tfa_id'] = $account['id'];
	        echo 'tfa: twofactor.php';
		} else {
			// Verification success! User has loggedin!
			// Declare the session variables, which will basically act like cookies, but will store the data on the server as opposed to the client
			session_regenerate_id();
			$_SESSION['loggedin'] = TRUE;
			$_SESSION['sec-username'] = $account['username'];//for blog system
		    $_SESSION['name'] = $account['username'];
		    $_SESSION['id'] = $account['id'];
            $_SESSION['role'] = $account['role'];
            $_SESSION['access_level'] = $account['access_level'];
            $_SESSION['email'] = $account['email'];
            $_SESSION['full_name'] = $account['full_name'];
            $_SESSION['document_path'] = $account['document_path'];
			// IF the user checked the remember me checkbox...
			if (isset($_POST['rememberme'])) {
				// Generate a hash that will be stored as a cookie and in the database. It will be used to identify the user.
				$cookiehash = !empty($account['rememberme']) ? $account['rememberme'] : password_hash($account['id'] . $account['username'] . SECRET_KEY, PASSWORD_DEFAULT);
				// The number of days a user will be remembered
				$days = 60;
				// Create the cookie
				setcookie('rememberme', $cookiehash, (int)(time()+60*60*24*$days));
				// Update the "rememberme" field in the accounts table with the new hash
				$stmt = $pdo->prepare('UPDATE accounts SET rememberme = ? WHERE id = ?');
				$stmt->execute([ $cookiehash, $account['id'] ]);
			}
			// Update last seen date
			$date = date('Y-m-d\TH:i:s');
			$stmt = $pdo->prepare('UPDATE accounts SET last_seen = ? WHERE id = ?');
			$stmt->execute([ $date, $account['id'] ]);
            $stmt = $pdo->prepare('DELETE FROM login_attempts WHERE ip_address = ?');
            $stmt->execute([ $ip ]);
			// Output msg; do not change this line as the AJAX code depends on it
			echo 'redirect'; 
		}

	} else {
		// Incorrect password
		$attempts_left = record_login_attempt($pdo);
		echo 'Error: Incorrect Password! <br>You have ' . $attempts_left . ' attempts remaining!';
	}
} else {
	// Incorrect username
	$attempts_left = record_login_attempt($pdo);
	echo 'Error: Incorrect Username!<br>You have ' . $attempts_left . ' attempts remaining!';
}
?>