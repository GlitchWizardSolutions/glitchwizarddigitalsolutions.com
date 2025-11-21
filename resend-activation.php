<?php /* EDITED 10/09/24 */
/*******************************************************************************
LOGIN SYSTEM - resend-activation.php
LOCATION: /public_html/
DESCRIBE: Clients enter email to resend an activation email from registering.
INPUTREQ: client email.
LOGGEDIN: NO
REQUIRED:  
  SYSTEM: LOGIN SYSTEM
   ADMIN: NO
   PAGES: index.php,main.php
   FILES: php_mailer system
DATABASE: TABLES: accounts
LOG NOTE: PRODUCTION 2024-19-19 - Not System Tested
*******************************************************************************/
include_once 'assets/includes/public-config.php';
// Unified email system already loaded by public-config.php
// Output message
$msg = '';
// Now we check if the email from the resend activation form was submitted, isset() will check if the email exists.
if (isset($_POST['email'])) {
    // Prepare our SQL, preparing the SQL statement will prevent SQL injection.
    $stmt = $pdo->prepare('SELECT * FROM accounts WHERE email = ? AND activation_code != "" AND activation_code != "activated"');
    $stmt->execute([ $_POST['email'] ]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    // If the account exists with the email
    if ($account) {
        // Account exist, the $msg variable will be used to show the output message (on the HTML form)
        send_email($_POST['email'], $account['activation_code'], $account['username'], 'activation'); 
        $msg = 'Activaton link has been sent to your email!';
    } else {
        $msg = 'We cannot activate this account, it might already be activated.';
    }
}
include includes_path . 'public-page-setup.php';
?>
		<div class="login">
		<div class="brand-bg" style='height:65px'>
		<img class="r-logo-public" src="<?php echo(site_menu_base) ?>assets/imgs/black_logo.png"  alt="logo"/></a>
        <span class="r-biz-name-public" href="<?php echo(site_menu_base) ?>index.php">GlitchWizard Solutions</span>
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