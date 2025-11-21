<?php
/*******************************************************************************
BLOGIN SYSTEM - resetpassword.php
LOCATION: /public_html/blog/
DESCRIBE: Clients enter their new password here.
INPUTREQ: This page is accessed via a link in client email.
LOGGEDIN: NO
REQUIRED: SESSION
  SYSTEM: BLOGIN SYSTEM
   ADMIN: NO
   PAGES: index.php, forgotpassword.php, reset-pass-email-template.php
   FILES: php_mailer system
DATABASE: TABLES: users
LOG NOTE: 2025-06-25 Conversion to Blog
*******************************************************************************/
include 'assets/includes/public-config.php';
//include process_path . 'email-process.php';//If testing passes, this can be deleted.
// Output message
$msg = '';
// Now we check if the data from the blogin form was submitted, isset() will check if the data exists.
if (isset($_GET['email'], $_GET['code']) && !empty($_GET['code'])) {
    // Checking first to see if an account holder is trying to change their password on the blog form.
    $stmt = $pdo->prepare('SELECT * FROM accounts WHERE email = ? AND reset_code = ?');
    $stmt->execute([ $_GET['email'], $_GET['code'] ]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    //Checking to see if it's a user that is resetting their password.
    $stmt = $blog_pdo->prepare('SELECT * FROM users WHERE email = ? AND reset_code = ?');
    $stmt->execute([ $_GET['email'], $_GET['code'] ]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    // If the account exists with the email and code
    if ($account) {
        if (isset($_POST['npassword'], $_POST['cpassword'])) {
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
                        <i class="fa-solid fa-check fa-fade"></i>&nbsp;You can now &nbsp;<a class="btn btn-sm" style="background-color:#4b3969; color:#f5f5f5" href="login.php">LOG IN</a>';
            }//end password input validation
        }//end check if it is even set
    } elseif($user) {
            if (isset($_POST['npassword'], $_POST['cpassword'])) {
             $pwdLength=strlen($_POST['npassword']);
            if ($_POST['npassword'] != $_POST['cpassword']) {
                $msg = 'Passwords must match!';
            }else if (strlen($_POST['npassword']) > 20 || strlen($_POST['npassword']) < 6) {
            	$msg = 'Password must be between 8 and 20 characters long!';
            }else if (strlen($_POST['npassword']) > 20 || strlen($_POST['npassword']) < 5) {
            	$msg = 'Password must be between 6 and 20 characters long!';
            } else {
                $stmt = $blog_pdo->prepare('UPDATE users SET password = ?, reset_code = "" WHERE email = ?');
            	// We do not want to expose passwords in our database, so hash the password and use password_verify when a user logs in.
            	$password = password_hash($_POST['npassword'], PASSWORD_DEFAULT);
            	$stmt->execute([ $password, $_GET['email'] ]);
                $msg = 'Password has been reset!<br>
                        <i class="fa-solid fa-check fa-fade"></i>&nbsp;You can now &nbsp;<a class="btn btn-sm" style="background-color:#4b3969; color:#f5f5f5" href="login.php">LOG IN</a>';
            }//end password input validation
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