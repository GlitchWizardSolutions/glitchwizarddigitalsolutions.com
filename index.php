<?php /* 
//THIS IS THE MEMBER LOGIN
2024-02-01 EDITED Issues with logging in.
2025-06-19 FEATURE Integrated blogging login with main website login.
2025-11-18 FIXED Use config-only.php to prevent headers already sent errors.
*/

// PHASE 1: Load config and database WITHOUT HTML output
include 'assets/includes/config-only.php';

// PHASE 2: Check session and handle redirects (BEFORE any HTML)
// No need for the user to see the login form if they're logged-in, so redirect them to the home page.
//if (isset($_SESSION['loggedin'])) {
   // header('Location: client-dashboard/index.php');
   // exit;
//}
// Check if they are "remembered."
// If the remember me cookie matches one in the database then we can update the session variables and the user will be logged-in.
if (isset($_COOKIE['rememberme']) && !empty($_COOKIE['rememberme'])) {
	$stmt = $pdo->prepare('SELECT * FROM accounts WHERE rememberme = ?');
	$stmt->execute([ $_COOKIE['rememberme'] ]);
	$auto_login = $stmt->fetch(PDO::FETCH_ASSOC);
	if ($auto_login) {
		// Authenticate the user
		session_regenerate_id();
		$_SESSION['loggedin'] = TRUE;
		$_SESSION['bloggedin'] = TRUE;//logs all members into the blog area for commenting.
		$_SESSION['sec-username'] = $auto_login['username'];
		$_SESSION['name'] = $auto_login['username'];
		$_SESSION['id'] = $auto_login['id'];
        $_SESSION['role'] = $auto_login['role'];
        $_SESSION['access_level'] = $auto_login['access_level'];
        $_SESSION['email'] = $auto_login['email'];
        $_SESSION['full_name'] = $auto_login['full_name'];
        $_SESSION['document_path'] = $auto_login['document_path'];
		// Update last seen date
		$date = date('Y-m-d\TH:i:s');
		$stmt = $pdo->prepare('UPDATE accounts SET last_seen = ? WHERE id = ?');
		$stmt->execute([ $date, $auto_login['id'] ]);
		// Redirect to dashboard home page
        header('Location: client-dashboard/index.php');
		exit;
	}//end if account
}//end if remember me

//CSRF Protection Add on to prevent cross site request forgery attacks.  
$_SESSION['token'] = md5(uniqid(rand(), true));

// PHASE 3: NOW load the page HTML
include includes_path . 'public-page-setup.php';
?>
		<div class="login">
		    <div class="brand-bg" style='height:65px'>
		    <img class="r-logo-public" src="assets/imgs/black_logo.png"  alt="logo"/></a>
            <span class="r-biz-name-public" href="index.php">GlitchWizard Solutions</span>
		    </div>

			 	<h1>Member Portal</h1>
	<form action="authenticate.php" method="post" class="form login-form">

			<label class="form-label" for="username">
				<i class="fas fa-user"></i>
			</label>
			
		<input class="form-input" type="text" name="username" placeholder="Username" id="username" autocomplete="username" required>			<label class="form-label" for="password"> 
					<i  class="fas fa-lock"></i>
				</label>
			<input class="form-input" type="password" name="password" placeholder="Password" id="password" autocomplete="current-password" required>
                <span  class="forgotpassword"><a id="forgotpassword" class="forgotpassword" href="forgotpassword.php">Forgot Password?</a></span> 
			    
			    
			    <label id="rememberme" class="rememberme">
			    <input type="checkbox" name="rememberme"> Remember me</label>

                <input type="hidden" name="token" value="<?=$_SESSION['token']?>">
				<div class="msg"></div>

				<input type="submit" value="Login">

			</form>

		</div>
 	<?php 
/*	<script>
		  AJAX code
		let loginForm = document.querySelector('.login form');
		loginForm.onsubmit = event => {
			event.preventDefault();
			fetch(loginForm.action, { method: 'POST', body: new FormData(loginForm) }).then(response => response.text()).then(result => {
				if (result.toLowerCase().includes('success')) {
					window.location.href = 'https://glitchwizarddigitalsolutions.com/client-dashboard/index.php';
				} else if (result.includes('tfa:')) {
                   //   window.location.href = 'twofactor.php';
                     window.location.href = result.replace('tfa: ', '');
				} else {
					document.querySelector('.msg').innerHTML = result;
				}
			});
		};
		</script>	*/ ?>	
		
		<script>
	// AJAX code
		const loginForm = document.querySelector('.login-form');
		loginForm.onsubmit = event => {
			event.preventDefault();
			fetch(loginForm.action, { method: 'POST', body: new FormData(loginForm), cache: 'no-store' }).then(response => response.text()).then(result => {
				if (result.toLowerCase().includes('success:')) {
					loginForm.querySelector('.msg').classList.remove('error','success');
					loginForm.querySelector('.msg').classList.add('success');
					loginForm.querySelector('.msg').innerHTML = result.replace('Success: ', '');
				} else if (result.toLowerCase().includes('redirect')) {
					window.location.href = '<?php echo BASE_URL; ?>client-dashboard/index.php';
				} else if (result.includes('tfa:')) {
                   // window.location.href = result.replace('tfa: ', '');
                      window.location.href = 'twofactor.php';
				} else {
					loginForm.querySelector('.msg').classList.remove('error','success');
					loginForm.querySelector('.msg').classList.add('error');
					loginForm.querySelector('.msg').innerHTML = result.replace('Error: ', '');
				}
			});
		};
		</script>
<?php include includes_path . 'site-close.php'; ?>