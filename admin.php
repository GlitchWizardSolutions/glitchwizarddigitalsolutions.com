<?php /* EDITED 12/08/24  Created Admin login OAuth for myself.*/
include_once 'assets/includes/public-config.php';

// No need for the user to see the login form if they're logged-in, so redirect them to the home page.
if (isset($_SESSION['loggedin']) && $_SESSION['role']=='Admin') {
    // Redirect to the login page.
    header('Location: admin/index.php');
    exit;
}
// Check if they are "remembered." 
// If the remember me cookie matches one in the database then we can update the session variables and the user will be logged-in.
if (isset($_COOKIE['rememberme']) && !empty($_COOKIE['rememberme'])) {
	$stmt = $pdo->prepare('SELECT * FROM accounts WHERE rememberme = ?');
	$stmt->execute([ $_COOKIE['rememberme'] ]);
	$auto_login = $stmt->fetch(PDO::FETCH_ASSOC);
	if ($auto_login && $auto_login['role']=='Admin') {
		// Authenticate the user
		session_regenerate_id();
		$_SESSION['loggedin'] = TRUE;
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
		// Redirect to admin dashboard page
        header('Location: admin/index.php');
		exit;
	}//end if account
}//end if remember me

//CSRF Protection Add on to prevent cross site request forgery attacks.  
$_SESSION['token'] = md5(uniqid(rand(), true));
?>
		<div class="login">
		    <div class="brand-bg" style='height:65px'>
		    <img class="r-logo-public" src="assets/imgs/black_logo.png"  alt="logo"/></a>
            <span class="r-biz-name-public" href="index.php">GlitchWizard Solutions</span>
		    </div>

			 	<h1>Admin Portal</h1>
  
	<form action="authenticate-admin.php" method="post" class="form login-form">

				<label class="form-label" for="username">
					<i class="fas fa-user"></i>
				</label>
				
			<input class="form-input" type="text" name="username" placeholder="Username" id="username" required>

			<label class="form-label" for="password"> 
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
			<a href="google-oauth.php"><button style="padding:10px"> <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 488 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M488 261.8C488 403.3 391.1 504 248 504 110.8 504 0 393.2 0 256S110.8 8 248 8c66.8 0 123 24.5 166.3 64.9l-67.5 64.9C258.5 52.6 94.3 116.6 94.3 256c0 86.5 69.1 156.6 153.7 156.6 98.2 0 135-70.4 140.8-106.9H248v-85.3h236.1c2.3 12.7 3.9 24.9 3.9 41.4z"></svg>
&nbsp;
	Login Admin with Google </button></a>
		</div>
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
					window.location.href = 'admin/index.php';
				}else {
					loginForm.querySelector('.msg').classList.remove('error','success');
					loginForm.querySelector('.msg').classList.add('error');
					loginForm.querySelector('.msg').innerHTML = result.replace('Error: ', '');
				}
			});
		};
		</script>
<?php include includes_path . 'site-close.php'; ?>