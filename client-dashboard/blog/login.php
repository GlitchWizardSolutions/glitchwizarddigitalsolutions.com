<?php //THIS IS THE BLOG LOGIN
include_once 'assets/includes/blog-config.php';
// $debug is set in blog/assets/includes/blog-config.php file
if ($debug='Yes') {
error_log('DEBUG BLOG SYSTEM PAGE login.php ');
$errors='';
}
include "core.php";
$error='';
$success='';
$gcaptcha_projectid = $settings["gcaptcha_projectid"];
head();

// No need for the user to see the login form if:
// they're logged-in, or rememberme cookie is set.
// so redirect them to the BLOG.
if (isset($_SESSION['loggedin'])) {//This is members only who are logged in already.
    echo '<meta http-equiv="refresh" content="0; url=' . $settings['blog_site_url'] . '">';
    exit;
}elseif(isset($_SESSION['bloggedin'])) {//this is blog users only who are logged in already.
    echo '<meta http-equiv="refresh" content="0; url=' . $settings['blog_site_url'] . '">';
    exit;
}elseif(isset($_COOKIE['rememberme']) && !empty($_COOKIE['rememberme']) && !isset($_SESSION['loggedin']) && !isset($_SESSION['loggedin'])) { //remembered cookie
// Initialize
        $auto_login_blog = '';
        $auto_login_account = '';
// If the remember me cookie matches one in the database 
// then we can update the session variables and the user 
// or member, will be logged-in.

        //determine if someone is in the blog users table, first...
        $stmt = $blog_pdo->prepare('SELECT * FROM users WHERE rememberme = ?');
	    $stmt->execute([ $_COOKIE['rememberme'] ]);
	    $auto_login_blog = $stmt->fetch(PDO::FETCH_ASSOC);
	    
        //determine if someone is in accounts table, next.
	    $stmt = $pdo->prepare('SELECT * FROM accounts WHERE rememberme = ?');
	    $stmt->execute([ $_COOKIE['rememberme'] ]);
	    $auto_login_account = $stmt->fetch(PDO::FETCH_ASSOC);
	    
	if ($auto_login_blog) {// Authenticates the blog user
		session_regenerate_id();
		$_SESSION['bloggedin'] = TRUE;//logs all into the blog area for commenting.
		$_SESSION['sec-username'] = $auto_login_blog['username'];
		$_SESSION['name'] = $auto_login_blog['username'];
		$_SESSION['id'] = $auto_login_blog['id'];
        $_SESSION['role'] = $auto_login_blog['role'];
        $_SESSION['access_level'] = $auto_login_blog['access_level'];
        $_SESSION['email'] = $auto_login_blog['email'];
        $_SESSION['full_name'] = $auto_login_blog['full_name'];
        $_SESSION['document_path'] = $auto_login_blog['document_path'];
		// Update last seen date
		$date = date('Y-m-d\TH:i:s');
		$stmt = $blog_pdo->prepare('UPDATE users SET last_seen = ? WHERE id = ?');
		$stmt->execute([ $date, $auto_login_blog['id'] ]);
		// Redirect to blog home page
         echo '<meta http-equiv="refresh" content="0; url=' . $settings['blog_site_url'] . '">';
         exit;
	}elseif($auto_login_account) {
		// Authenticate the account member
		session_regenerate_id();
		$_SESSION['loggedin'] = TRUE;//logs into both the blog and to the member portal.
		$_SESSION['sec-username'] = $auto_login_account['username'];
		$_SESSION['name'] = $auto_login_account['username'];
		$_SESSION['id'] = $auto_login_account['id'];
        $_SESSION['role'] = $auto_login_account['role'];
        $_SESSION['access_level'] = $auto_login_account['access_level'];
        $_SESSION['email'] = $auto_login_account['email'];
        $_SESSION['full_name'] = $auto_login_account['full_name'];
        $_SESSION['document_path'] = $auto_login_account['document_path'];
		// Update last seen date
		$date = date('Y-m-d\TH:i:s');
		$stmt = $pdo->prepare('UPDATE accounts SET last_seen = ? WHERE id = ?');
		$stmt->execute([ $date, $auto_login_account['id'] ]);
		// Redirect to dashboard home page
        echo '<meta http-equiv="refresh" content="0; url=' . $settings['blog_site_url'] . '">';
        exit;
	}//end if user or account is able to be auto authenticated.
}//end all the ways a user could be auto authenticated.
//If user cannot be auto authenticated, continue to show the login/registration page.

//CSRF Protection Add on to prevent cross site request forgery attacks.  
$_SESSION['token'] = md5(uniqid(rand(), true));
?>
if ($settings['sidebar_position'] == 'Left') {
	sidebar();
}

$error = 0;
?>
    <div class="col-md-8 mb-3">
        <div class="card">
            <div class="card-header"><i class="fas fa-user-plus"></i> Membership</div>

                <div class="card-body">

                    <div class="row">
						<div class="col-md-6 mb-4">
							<h5><i class="fas fa-sign-in-alt"></i> Sign In</h5><hr />
                <form id='login_form' name='login_form' action="" method="post">
                <br>
                <div class="input-group mb-3 needs-validation <?php
if ($error == 1) {
    echo 'is-invalid';
}
?>">
                <span class="input-group-text"><i class="fas fa-user"></i></span>
                <input type="username" name="username" class="form-control" placeholder="Username" <?php
if ($error == 1) {
    echo 'autofocus';
}
?> required>
            </div>
            <div class="input-group mb-3 needs-validation">
                <span class="input-group-text"><i class="fas fa-key"></i></span>
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>

            <button id='login' name='login' type="submit" class="btn btn-primary col-12"><i class="fas fa-sign-in-alt"></i>
&nbsp;Sign In</button>

        </form> 
<?php  
            // Error display if redirected back
if (isset($_GET['error'])) {
  echo "<p style='color:red'>" . htmlspecialchars($_GET['error'], ENT_QUOTES) . "</p>";
}
?>
<?php
// Success display if redirected back
if (isset($_GET['success'])) {
  echo "<p style='color:green'>" . htmlspecialchars($_GET['success'], ENT_QUOTES) . "</p>";
}
?>        
</div>
					<div class="col-md-6">
						<h5><i class="fas fa-user-plus"></i> Blog User Registration</h5><hr />
<?php
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = hash('sha256', $_POST['password']);
    $stmt = $blog_pdo->prepare("SELECT username, password FROM `users` WHERE `username`=? AND password=?");
    $stmt->execute([$username, $password]);
    if ($stmt->rowCount() > 0) {
        $_SESSION['sec-username'] = $username;
        echo '<meta http-equiv="refresh" content="0; url=' . $settings['blog_site_url'] . '">';
    } else {
        echo '
		<div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> The entered <strong>Username</strong> or <strong>Password</strong> is incorrect.
        </div>';
        $error = 1;
    }//end processing of the login form.
}elseif (isset($_POST['register'])) { 
    $username = $_POST['username'];
    $password = hash('sha256', $_POST['password']);
    $email    = $_POST['email'];
    $recaptcha_response = $_POST['g-recaptcha-response'];
 // === Validate basic fields ===   
        if (strlen($username) < 5 || strlen($password) < 5) {
                // Safely encode the query parameters
                 $error = 'Please use at least 5 letters for usernames and passwords.';
                 $encoded_error = urlencode($error);
                 //redirect
                 header("Location: login?error=$encoded_error");
                 exit;
        }//end username length validation
        if (empty($email)) {
                // Safely encode the query parameters
                 $error = 'Please include your email address.';
                 $encoded_error = urlencode($error);
                 //redirect
                 header("Location: login?error=$encoded_error");
                 exit;
        } //end empty email validation
      $stmt = $blog_pdo->prepare("SELECT username FROM `users` WHERE username=?");
      $stmt->execute([$username]);
      if ($stmt->rowCount() > 0) {
                // Safely encode the query parameters
                 $error = 'The username already exists on our servers.';
                 $encoded_error = urlencode($error);
                 //redirect
                 header("Location: login?error=$encoded_error");
                 exit;
       }//end username duplication validation.
      $stmt = $blog_pdo->prepare("SELECT email FROM `users` WHERE email=?");
      $stmt->execute([$email]);
      if ($stmt->rowCount() > 0) {
                // Safely encode the query parameters
                 $error = 'This email is already registered.';
                 $encoded_error = urlencode($error);
               //redirect
                 header("Location: login?error=$encoded_error");
                 exit;
       }//end email duplication validation.
      if ($recaptcha_response) {
      // === Verify reCAPTCHA token ===
      if ($debug='Yes') {
         error_log('BEGIN CAPTCHA VERIFY WITH GOOGLE... ');
         $errors='';
      }
      $verify_url = 'https://www.google.com/recaptcha/api/siteverify';

      $data = [
       'secret' => $secret,
       'response' => $recaptcha_response,
       'remoteip' => $_SERVER['REMOTE_ADDR']
      ];

      $options = [
         'http' => [
         'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
         'method'  => 'POST',
         'content' => http_build_query($data),
        ]
      ];

      $context  = stream_context_create($options);
      $result = file_get_contents($verify_url, false, $context);
      $verification = json_decode($result, true);

      // === Check score and success ===
      if ($verification['success'] && $verification['score']??0 >= 0.5) {
                     // Safely encode the query parameters
                           if ($debug='Yes') {
                            error_log('REGISTRATION SUCCESSFULLY SUBMITTED.');
                            $errors='';
                            }
                        $success = 'Your registration request has been successfully submitted.';
                        $encoded_success = urlencode($success);
                        $stmt = $blog_pdo->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
                        $stmt->execute([$username, $password, $email]);
                        $stmt = $blog_pdo->prepare("INSERT INTO newsletter (email) VALUES (?)");
                        $stmt->execute([$email]);
      //Send verification email.
                    $subject = 'Welcome at ' . $settings['sitename'] . '';
                    $message = '<a href="' . $settings['blog_site_url'] . '" title="Visit ' . $settings['sitename'] . '" target="_blank">
                                    <h4>' . $settings['sitename'] . '</h4>
                                </a><br />

                                <h5>You have successfully registered at ' . $settings['sitename'] . '</h5><br /><br />

                                <b>Registration details:</b><br />
                                Username: <b>' . $username . '</b>';
                    $headers = 'MIME-Version: 1.0' . "\r\n";
                    $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
                    $headers .= 'To: ' . $email . ' <' . $email . '>' . "\r\n";
                    $headers .= 'From: ' . $settings['email'] . ' <' . $settings['email'] . '>' . "\r\n";
                    @mail($email, $subject, $message, $headers);
                    
                    $_SESSION['sec-username'] = $username;
                    echo '<meta http-equiv="refresh" content="0;url=profile">';
                           if ($debug='Yes') {
                            error_log('NEW BLOG USER REGISTRATION IS COMPLETE.');
                            $errors='';
                            }
                         header("Location: profile?success=$encoded_success");
                         exit;
                     
            }//end verification by google recaptcha.
      } else {
       // ❌ Low score or invalid
                 $error = 'Google cannot verify you are a human.';
                 $encoded_error = urlencode($error);
                 $errors=$errors . ' Low Score or Invalid. ';
                            if ($debug='Yes') {
                            error_log('FAILED GOOGLE VERIFICATION ERROR: ' . $encoded_error);
                            $errors='';
                            }
                 //redirect
                 header("Location: login?error=$encoded_error");
                 exit;
        }
     }//end check if registration form was submitted.
                            if ($debug='Yes') {
                            error_log('PROCESS HAS ENDED FOR BLOG LOGIN.');
                            $errors='';
                            }
?>     
  
 <form  id='registration_form' name='registration_form'action="" method="post">
                <label for="characters"><i>Characters left: </i></label>
	            <span id="characters">15</span> 
            <div class="input-group mb-3 needs-validation">
                <span class="input-group-text"><i class="fas fa-user"></i></span>
                <input type="username" name="username" title="Enter Username between 5 & 15 characters." class="form-control" placeholder="Username" maxlength="15" oninput="countUsername()" minlength="5" required>
            </div>
			<div class="input-group mb-3 needs-validation">
                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                <input type="email" name="email" title="Enter a valid Email address." class="form-control" placeholder="E-Mail Address" required>
            </div>
            <div class="input-group mb-3 needs-validation">
                <span class="input-group-text"><i class="fas fa-key"></i></span>
                <input type="password" name="password" id="password" title="Enter a Password between 5 & 15 characters." class="form-control" placeholder="Password" maxlength="15" oninput="countPassword()" minlength="5" required>
            </div>
          <!-- Hidden reCAPTCHA token field -->
          <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
            <button type="submit" name="register" id='register' class="btn btn-primary col-12 mt-2"><i class="fas fa-sign-in-alt"></i>
&nbsp;Sign Up</button>
        </form> 
					</div> 
				</div>							
            </div>
        </div> 
    </div>

<script>
function countUsername() {
	let text = document.registration_form.username.value;
	//limits the input field of the username to 15 characters.
	  document.getElementById('characters').innerText = 15 - text.length;
	//document.getElementById('words').innerText = text.length == 0 ? 0 : text.split(/\s+/).length;
	//document.getElementById('rows').innerText = text.length == 0 ? 0 : text.split(/\n/).length;
}
function countPassword() {
	let text = document.registration_form.password.value;
	//limits the input field of the password to 15 characters.
	  document.getElementById('characters').innerText = 15 - text.length;
	//document.getElementById('words').innerText = text.length == 0 ? 0 : text.split(/\s+/).length;
	//document.getElementById('rows').innerText = text.length == 0 ? 0 : text.split(/\n/).length;
}
</script>
<!-- JavaScript to handle reCAPTCHA execution and form submit -->
<script>
  document.getElementById('register').addEventListener('click', function (e) {
    e.preventDefault(); // Prevent default link behavior
//this firest when the registration form is submitted.  It does not fire for the login.
    grecaptcha.ready(function () {
        grecaptcha.execute('6LdmAmgrAAAAAIdsJeCLDjkPhYeVZIH6wSGqkxIH', { action: 'submit' }).then(function (token) {
        document.getElementById('g-recaptcha-response').value = token;
        document.getElementById('registration_form').submit(); // Submit after token
      });
    });
  });
</script>

<?php
if ($settings['sidebar_position'] == 'Right') {
	sidebar();
}
footer();
?>