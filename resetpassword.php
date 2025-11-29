<?php
/*******************************************************************************
LOGIN SYSTEM - resetpassword.php
LOCATION: /public_html/
DESCRIBE: Clients enter their new password here.
INPUTREQ: This page is accessed via a link in client email.
LOGGEDIN: NO
REQUIRED: SESSION
  SYSTEM: LOGIN SYSTEM
   ADMIN: NO
   PAGES: index.php, forgotpassword.php, reset-pass-email-template.php
   FILES: php_mailer system
DATABASE: TABLES: accounts
LOG NOTE: PRODUCTION 2024-19-19 - Active
*******************************************************************************/
include 'assets/includes/public-config.php';
// Unified email system loaded by public-config.php

// Output message
$msg = '';
// Now we check if the data from the login form was submitted, isset() will check if the data exists.
if (isset($_GET['email'], $_GET['code']) && !empty($_GET['code'])) {
    // Prepare our SQL, preparing the SQL statement will prevent SQL injection.
    $stmt = $pdo->prepare('SELECT * FROM accounts WHERE email = ? AND reset_code = ?');
    $stmt->execute([ $_GET['email'], $_GET['code'] ]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    // If the account exists with the email and code
    if ($account) {
        if (isset($_POST['npassword'], $_POST['cpassword'])) {
            // Validate CSRF token
            if (!validate_csrf_token()) {
                $msg = 'Security validation failed. Please try again.';
            } else {
                $pwdLength=strlen($_POST['npassword']);
                if ($_POST['npassword'] != $_POST['cpassword']) {
                    $msg = 'Passwords must match!';
                }else if (strlen($_POST['npassword']) > 20 || strlen($_POST['npassword']) < 6) {
                    $msg = 'Password must be between 8 and 20 characters long!';
                }else if (strlen($_POST['npassword']) > 20 || strlen($_POST['npassword']) < 5) {
                    $msg = 'Password must be between 6 and 20 characters long!';
                } else {
                $stmt = $pdo->prepare('UPDATE accounts SET password = ?, reset_code = "" WHERE email = ?');
            	// We do not want to expose passwords in our database, so hash the password and use password_verify when a user logs in.
            	$password = password_hash($_POST['npassword'], PASSWORD_DEFAULT);
            	$stmt->execute([ $password, $_GET['email'] ]);
                $msg = 'Password has been reset!<br>
                        <i class="fa-solid fa-check fa-fade"></i>&nbsp;You can now &nbsp;<a class="btn btn-sm" style="background-color:#4b3969; color:#f5f5f5" href="index.php">LOG IN</a>';
            }//end password input validation
            }
        }//end check if it is even set
    } else {
        $msg='Incorrect email and/or code. <strong><a href="forgotpassword.php">Try Again Here</a></strong>';
        //exit('Incorrect email and/or code!');
    }
} else {
    $msg='Please provide the email and code!';
    //exit('Please provide the email and code!');
}
include includes_path . 'public-page-setup.php';
?>
		<div class="login">
			<h1>Reset Password</h1>
			<?php if (!empty($_GET)) : ?>
			<form action="resetpassword.php?email=<?=$_GET['email']?>&code=<?=$_GET['code']?>" method="post">
                <label for="npassword">
					<i class="fas fa-lock"></i>
				</label>
				<input type="password" name="npassword" placeholder="New Password" id="npassword" required>
                <label for="cpassword">
					<i class="fas fa-lock"></i>
				</label>
				<input type="password" name="cpassword" placeholder="Confirm Password" id="cpassword" required>
				<?php csrf_token_field(); ?>
				<div class="msg"><?=$msg?></div>
				<input type="submit" value="Submit">
			</form>
			<?php else : ?>
			<form>
			    <div class="msg" style="text-align:center; font-weight:bold"><?=$msg?></div>
			</form>
			<?php endif;?>
		</div>
<?php include includes_path . 'site-close.php'; ?>