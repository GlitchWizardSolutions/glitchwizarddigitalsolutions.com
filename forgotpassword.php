<?php /* EDITED 11/23/24  Issues with logging in.*/     
/*******************************************************************************
LOGIN SYSTEM - forgotpassword.php
LOCATION: /public_html/
DESCRIBE: Clients enter their email address to be sent a link.
INPUTREQ: email address
LOGGEDIN: NO
REQUIRED:
  SYSTEM: LOGIN SYSTEM
   ADMIN: NO
   PAGES: index.php, processing stays on page
   FILES: config.php,main,close
DATABASE: TABLES: accounts
   PARMS: 
     OUT: 
LOG NOTE: PRODUCTION 2024-09-19 - Active
*******************************************************************************/
include_once 'assets/includes/public-config.php';
// Unified email system loaded by public-config.php

//Update the reset password link, when new.
// Output message
$msg = '';
// Now we check if the data from the login form was submitted, isset() will check if the data exists.
if (isset($_POST['email'])) {
    // Validate CSRF token
    if (!validate_csrf_token()) {
        $msg = 'Security validation failed. Please try again.';
    } else {
        // Prepare our SQL, preparing the SQL statement will prevent SQL injection.
        $stmt = $pdo->prepare('SELECT * FROM accounts WHERE email = ?');
        $stmt->execute([ $_POST['email'] ]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);
        // If the account exists with the email
        if ($account) {
        // Account exist
        // Update the reset code in the database
		$uniqid = uniqid();
        $stmt = $pdo->prepare('UPDATE accounts SET reset_code = ? WHERE email = ?');
    	$stmt->execute([ $uniqid, $_POST['email'] ]);
        send_email($_POST['email'], $uniqid, $account['username'], 'resetpass'); 
        $msg = '<div style="color:green"> Reset password link has been sent to your email!</div>';
    } else {
        $msg = 'We do not have an account with that email!';
    }
    }
}

?>
		<div class="login">
			<h1>Forgot Password</h1>
			<form action="" method="post">
                <label for="email">
					<i class="fas fa-envelope"></i>
				</label>
				<input type="email" name="email" placeholder="Your Email" id="email" required>
				<?php csrf_token_field(); ?>
				<div class="msg"><?=$msg?></div>
				<input type="submit" value="Submit">
			</form>
		</div>
<?php include includes_path . 'site-close.php'; ?>