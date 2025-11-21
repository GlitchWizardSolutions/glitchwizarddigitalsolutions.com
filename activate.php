<?php /* EDITED 01/28/25 */
/*******************************************************************************
LOGIN SYSTEM - activate.php
LOCATION: /public_html/
DESCRIBE: User sees this page upon activation.
INPUTREQ: client email.
LOGGEDIN: NO
REQUIRED:  
  SYSTEM: LOGIN SYSTEM
   ADMIN: NO
   PAGES: index.php,main.php
   FILES: php_mailer system
DATABASE: TABLES: accounts
LOG NOTE: 2024-12-27 PRODUCTION  
          2025-01-28 Bug Fix:   Re-Activation with Email Change in Profile Edit.
*******************************************************************************/
include_once 'assets/includes/public-config.php';
// Output message
$msg = '';
// First we check if the email and code exists...
if (isset($_GET['email'], $_GET['code']) && !empty($_GET['code'])) {
	$stmt = $pdo->prepare('SELECT * FROM accounts WHERE email = ? AND activation_code = ?');
	$stmt->execute([ $_GET['email'], $_GET['code'] ]);
	// Store the result so we can check if the account exists in the database.
	$account = $stmt->fetch(PDO::FETCH_ASSOC);
	if ($account) {
		// Account exists with the requested email and code.
		$stmt = $pdo->prepare('UPDATE accounts SET activation_code = ? WHERE email = ? AND activation_code = ?');
		// Set the new activation code to 'activated', this is how we can check if the user has activated their account.
		$activated = 'activated';
		$stmt->execute([ $activated, $_GET['email'], $_GET['code'] ]);
		$msg = 'Your account is now activated! <br>You can now log in. <br><a href="index.php" class="btn btn-small">Login</a>.';
	} else {
		$msg = 'The account can\'t be activated. <br> It may be already activated.<br> <br><a href="index.php" class="btn btn-small">Login</a>';
	}
} else {
	$msg = 'Please use the activation code link in your email!';
}
include includes_path . 'public-page-setup.php';
?>
		<div class="login">
		<div class="brand-bg" style='height:65px'>
		<img class="r-logo-public" src="<?php echo(site_menu_base) ?>assets/imgs/black_logo.png"  alt="logo"/></a>
        <span class="r-biz-name-public" href="<?php echo(site_menu_base) ?>index.php">GlitchWizard Solutions</span>
			</div>
			<h1>Account Status</h1>
			<form action="" method="">
<label><i class="fas fa-lock"></i></label>
				<div class="msg" style="text-align:center"><br><?=$msg?><br><br><br></div>
			</form>

		</div>
<?php include_once includes_path . 'site-close.php'; ?>		
<?php require("assets/includes/site-close.php");?>