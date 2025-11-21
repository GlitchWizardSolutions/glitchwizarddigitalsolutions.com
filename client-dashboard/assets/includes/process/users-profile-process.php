<?php
include includes_path . 'process/email-process.php';

// output message (errors, etc)
$msg = '';
// Retrieve additional account info from the database because we don't have them stored in sessions.
$stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
// In this case, we can use the account ID to retrieve the account info.
$stmt->execute([ $_SESSION['id'] ]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);
 error_log('session id ' .  $_SESSION['id']);
// Handle edit profile post data
if (isset($_POST['username'], $_POST['email'], $_POST['first_name'])) {
	// Make sure the submitted values are not empty.
	if (empty($_POST['username']) || empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['email'])) {
		$msg = 'The input fields must not be empty!';
	} else if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
		$msg = 'Please provide a valid email address!';
	} else if (!preg_match('/^[a-zA-Z0-9]+$/', $_POST['username'])) {
	    $msg = 'Login User Name must contain only letters and numbers!';
	} 
	// No validation errors... Process update
	if (empty($msg)) {
	    
		// Check if new username or email already exists in database
		if ($_POST['username'] != $account['username'] ||  $_POST['email'] != $account['email']){
		$stmt = $pdo->prepare('SELECT COUNT(*) FROM accounts WHERE (username = ? OR email = ?) AND (username != ? AND email != ?)');
		$stmt->execute([ $_POST['username'], $_POST['email'], $account['username'], $account['email'] ]);
		// Account exists? Output error...
		if ($result = $stmt->fetchColumn()) {
			$msg = 'Account already exists with that login user name and/or email!';
		} else {
			// No errors occured, update the account...
			// If email has changed, generate a new activation code
			 error_log('No validation errors... update the account');
			$uniqid = account_activation && $account['email'] != $_POST['email'] ? uniqid() : $account['activation_code'];
 
  	        $stmt = $pdo->prepare('UPDATE accounts SET username = ?,  email = ?, activation_code = ?, first_name = ?, last_name = ?, phone = ?, address_street = ?, address_city = ?, address_state = ?, address_zip = ? WHERE id = ?');
			$stmt->execute([ $_POST['username'], $_POST['email'], $uniqid, $_POST['first_name'], $_POST['last_name'], $_POST['phone'], $_POST['address_street'], $_POST['address_city'], $_POST['address_state'], $_POST['address_zip'], $_SESSION['id'] ]);
			// Update the session variables
			$_SESSION['name'] = $_POST['username'];
			 
				$_SESSION['first_name'] = $_POST['first_name'];
					$_SESSION['last_name'] = $_POST['last_name'];
			$_SESSION['access_level'] =$account['access_level'];
			$_SESSION['role'] = $account['role'];
		   
			if (account_activation && $account['email'] != $_POST['email']) {
				// Account activation required, send the user the activation email with the "send_activation_email" function from the "main.php" file
				send_activation_email($_POST['email'], $uniqid);
				// Logout the user
				unset($_SESSION['loggedin']);
				$msg = 'You have changed your email address! Click link in email to re-activate your account!';
			} else {
				// Profile updated successfully, redirect the user back to the profile page
				header('Location: users-profile-edit.php');
				exit;
			}
		}
	}
}
}

$error_msg = '';
$success_msg = '';

// Retrieve from invoice_clients.
$stmt = $pdo->prepare('SELECT * FROM invoice_clients WHERE id = ?');
$stmt->execute([ $_SESSION['invoice_client_id'] ]);
$invoice_clients = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle edit business post data
if (isset($_POST['business_name'])) {
	// Make sure the submitted values are not empty.
	if (empty($_POST['business_name']) || empty($_POST['description']) || empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['business_address_street']) || empty($_POST['business_address_city'])|| empty($_POST['business_address_state'])|| empty($_POST['business_address_zip'])){
			$error_msg = 'The input fields must not be empty!';
	} else if (!filter_var($_POST['business_email'], FILTER_VALIDATE_EMAIL)) {
        	$error_msg = 'Please provide a valid email address!';
	} else if (!preg_match('/^[a-zA-Z0-9]+$/', $_POST['first_name'])) {
	       $error_msg = 'First Name must contain only letters and numbers!';
	} 
	// No validation errors... Process update
	
	if (empty($error_msg)) {
	// Check if business already exists in the database
	  		$stmt = $pdo->prepare('SELECT COUNT(*) FROM invoice_clients WHERE acc_id = ?');
		    $stmt->execute([ $account['id']]);
		// Account exists? Update
		if ($result = $stmt->fetchColumn() > 0) {
		 	// Update the account
            $stmt = $pdo->prepare('UPDATE invoice_clients SET business_name = ?, description = ?, business_email = ?, first_name = ?, last_name = ?, business_phone = ?, business_address_street = ?, business_address_city = ?, business_address_state = ?, business_address_zip = ?,  business_address_country = ? WHERE acc_id = ?');
			$stmt->execute([ $_POST['business_name'], $_POST['description'], $_POST['business_email'], $_POST['first_name'],$_POST['last_name'], $_POST['business_phone'], $_POST['business_address_street'], $_POST['business_address_city'], $_POST['business_address_state'], $_POST['business_address_zip'], $_POST['business_address_country'], $_SESSION['id'] ]);
				// Record updated successfully, redirect the user back to the profile page
				header('Location: users-profile.php');
				exit;
		} else {
			// Insert New record
	        // Current date
	        $date = date('Y-m-d\TH:i:s');
	        // Prepare query; prevents SQL injection
	        $stmt = $pdo->prepare('INSERT INTO invoice_clients (
	        acc_id, 
	        project_id, 
	        business_name, 
	        description, 
	        facebook, 
	        instagram, 
	        x, 
	        linkedin, 
	        first_name, 
	        last_name, 
	        email, 
	        phone, 
	        address_street, 
	        address_city, 
	        address_state, 
	        address_zip, 
	        address_country, 
	        created) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute(
            [ $_SESSION['id'], 0, $_POST['business_name'], $_POST['description'], 
            $_POST['facebook'], $_POST['instagram'], $_POST['x'], 
            $_POST['linkedin'], $_POST['first_name'], $_POST['last_name'], 
            $_POST['business_email'], $_POST['business_phone'], 
            $_POST['business_address_street'], $_POST['business_address_city'], 
            $_POST['business_address_state'], $_POST['business_address_zip'], 
            $_POST['business_address_country'],$date ]);
		// Insert Sucessful, redirect the user back to the profile page
		    header('Location: users-profile.php');
			exit;
	}// end insert/update
    }//end posted business information
}
// Handle edit password post data
if (isset($_POST['newPassword'], $_POST['confirmPassword'])) {
	// Make sure the submitted registration values are not empty.
	if (empty($_POST['confirmPassword']) || empty($_POST['newPassword'])) {
		$error_msg = 'The input fields must not be empty!';
	} else if (!empty($_POST['newPassword']) && (strlen($_POST['newPassword']) > 20 || strlen($_POST['newPassword']) < 5)) {
		$error_msg = 'Password must be between 5 and 20 characters long!';
	} else if ($_POST['confirmPassword'] != $_POST['newPassword']) {
		$error_msg = 'Passwords do not match!';
	}
	// No validation errors... Process update
	if (empty($error_msg)) {
			// No errors occured, update the account...
			// Hash the new password if it was posted and is not blank
			$password = !empty($_POST['newPassword']) ? password_hash($_POST['newPassword'], PASSWORD_DEFAULT) : $account['password'];
			// Update the account
			$stmt = $pdo->prepare('UPDATE accounts SET password = ? WHERE id = ?');
			$stmt->execute([ $password, $_SESSION['id'] ]);
				// Output success message
				$success_msg = 'You have successfully changed your password!';
				// Profile updated successfully, redirect the user back to the profile page
				header('Location: users-profile.php');
				exit;
			}
}?>