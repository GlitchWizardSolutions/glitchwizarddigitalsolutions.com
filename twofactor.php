<?php     
/*******************************************************************************
LOGIN SYSTEM - twofactor.php
LOCATION: /public_html/
DESCRIBE: Clients enter their secret code from their email.
INPUTREQ: generated security code
LOGGEDIN: NO
REQUIRED:
  SYSTEM: LOGIN SYSTEM
   ADMIN: NO
   PAGES: index.php
   FILES: 
DATABASE: TABLES: accounts
   PARMS: 
     OUT: 
LOG NOTE: PRODUCTION 2024-10-16 - Active
          2025-11-18 FIXED: Use config-only.php to prevent email errors
*******************************************************************************/

// PHASE 1: Load config and database WITHOUT HTML output
include 'assets/includes/config-only.php';
// Unified email system already loaded by config-only.php

// PHASE 2: Handle two-factor logic (BEFORE any HTML)
// Output message
$msg = '';
// Verify the ID and email provided
if (isset($_SESSION['tfa_id'])) {
    // Prepare our SQL, preparing the SQL statement will prevent SQL injection.
    $stmt = $pdo->prepare('SELECT email, tfa_code, username, id, role, access_level, full_name, document_path FROM accounts WHERE id = ?');
    $stmt->execute([ $_SESSION['tfa_id'] ]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    // If the account exists with the ID provided...
    if ($account) {
        // Account exists
        if (isset($_POST['code'])) {
            // Code submitted via the form
            if ($_POST['code'] == $account['tfa_code']) {
                // Code accepted, update the IP address
                $ip = $_SERVER['REMOTE_ADDR'];
                $stmt = $pdo->prepare('UPDATE accounts SET ip = ? WHERE id = ?');
                $stmt->execute([ $ip, $account['id'] ]);
                // Destroy tfa session variable
                 unset($_SESSION['tfa_id']);
                // Authenticate the user
                session_regenerate_id();
                $_SESSION['loggedin'] = TRUE;
                $_SESSION['sec-username'] = $account['username'];
                $_SESSION['name'] = $account['username'];
                $_SESSION['id'] = $account['id'];
                $_SESSION['role'] = $account['role'];
                $_SESSION['access_level'] = $account['access_level'];
                $_SESSION['email'] = $account['email'];
                $_SESSION['full_name'] = $account['full_name'];
                $_SESSION['document_path'] = $account['document_path'];
                // Redirect to dashboard home page
                header('Location: client-dashboard/index.php');
                exit;
            } else {
                $msg = 'Error: Incorrect code provided!';
            }
        } else {
           // Generate a unique code
            $code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
            // Update the account with the new code
            $stmt = $pdo->prepare('UPDATE accounts SET tfa_code = ? WHERE id = ?');
            $stmt->execute([ $code, $account['id'] ]);
            // Send the code to the user's email
            send_email($account['email'], $code, $account['username'], 'twofactor');
        }
    } else {
     $msg = 'Error: No email and/or ID provided!';
    }
} else {
    $msg = 'Error: No email and/or ID provided!';
}
include includes_path . 'public-page-setup.php';
?>

		<div class="login">
			<h1>Two-factor Authentication</h1>		
            <p style="padding:15px;margin:0;">Enter the code we sent to your email.</p>
			<form action="" method="post">
                <label for="code">
					<i class="fas fa-lock"></i>
				</label>
				<input type="text" name="code" placeholder="Your Code" id="code" required>
				<?php if (!empty($msg)): ?>
				<div class="msg"><?=$msg?></div>
				<?php endif; ?>
					<input type="submit" value="Submit">
			</form>
		</div>
<?php include includes_path . 'site-close.php'; ?>