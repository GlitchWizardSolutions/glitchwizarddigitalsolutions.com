<?php
/*******************************************************************************
BLOGIN SYSTEM - resend-activation.php
LOCATION: /public_html/blog/
DESCRIBE: Clients enter email to resend an activation email from registering.
INPUTREQ: client email.
LOGGEDIN: NO
REQUIRED:  
  SYSTEM: BLOGIN SYSTEM
   ADMIN: NO
   PAGES: index.php,main.php
   FILES: php_mailer system
DATABASE: TABLES: users & accounts
LOG NOTE: 2025-06-25 - Ready for testing
*******************************************************************************/
include_once 'assets/includes/public-config.php';
include process_path . 'email-process.php';
// Output message
$msg = '';
// Now we check if the email from the resend activation form was submitted, isset() will check if the email exists.
if (isset($_POST['email'])) {
    // First, check to see if the email is associated with an account, or a blog user.
    $stmt = $pdo->prepare('SELECT * FROM accounts WHERE email = ? AND activation_code != "" AND activation_code != "activated"');
    $stmt->execute([ $_POST['email'] ]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $blog_pdo->prepare('SELECT * FROM users WHERE email = ? AND activation_code != "" AND activation_code != "activated"');
    $stmt->execute([ $_POST['email'] ]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    // If the account exists with the email
    if ($account) {
        // Account exists, the $msg variable will be used to show the output message (on the HTML form)
        send_email($_POST['email'], $account['activation_code'], $account['username'], 'activation'); 
        $msg = 'Activaton link has been sent to your email!';
    }elseif($user){
        // User exists, the $msg variable will be used to show the output message (on the HTML form)
        send_email($_POST['email'], $user['activation_code'],$user['username'], 'blog-activation'); 
        $msg = 'Activaton link has been sent to your email!';
    } else {
        $msg = 'We cannot activate this account, it might already be activated.';
    }
}
include includes_path . 'public-page-setup.php';
?>
		<div class="login">
		<div class="brand-bg" style='height:65px'>
		<img class="r-logo-public" src="<?php echo site_menu_base ?>assets/imgs/black_logo.png"  alt="logo"/></a>
        <span class="r-biz-name-public" href="<?php echo site_menu_base ?>index.php">GlitchWizard Solutions</span>
			</div>
			<h1>Resend Activation Email</h1>

			<form action="" method="post">

                <label for="email">
					<i class="fas fa-envelope"></i>
				</label>
				<input type="email" name="email" placeholder="Your Email" autocomplete="email" id="email" required>

				<div class="msg"><?=$msg?></div>

				<input type="submit" value="Submit">

			</form>

		</div>
<?php include_once includes_path . 'site-close.php'; ?>