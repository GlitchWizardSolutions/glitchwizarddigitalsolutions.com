<?php
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
require 'assets/includes/admin_config.php';

// Check if the user is logged-in
check_loggedin($pdo, '../../index.php');
// Fetch account details associated with the logged-in user
$stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
$stmt->execute([ $_SESSION['id'] ]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);
// Check if the user is an admin...
if ($account['role'] != 'Admin') {
    exit('You do not have permission to access this page!');
}
// Connect to the login Database using the PDO interface
try {
	$logon_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name . ';charset=' . db_charset, db_user, db_pass);
	$logon_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	// If there is an error with the connection, stop the script and display the error.
	exit('Failed to connect to the logon database!');
}
 
?>
    <?=template_admin_header('Accounts', 'accounts', 'view')?>
<div class="content-title">
    <div class="title">
       <i class="fa-solid fa-circle-info"></i>
        <div class="txt">
            <h2>Envato Blog Connection Test Page</h2>
            <p>Making this a template for the rest of the site.</p>
        </div>
    </div>
</div>

<?php


if (isset($_SESSION['sec-username'])) {
    $uname = $_SESSION['sec-username'];
    $suser = mysqli_query($connect, "SELECT * FROM `users` WHERE username='$uname' AND (role='Admin' || role='Editor')");
    $count = mysqli_num_rows($suser);
    if ($count <= 0) {
        echo '<meta http-equiv="refresh" content="0; url=../login" />';
        exit;
    } else {
        echo '<meta http-equiv="refresh" content="0; url=dashboard.php" />';
    }
} else {
    echo '<meta http-equiv="refresh" content="0; url=../login" />';
    exit;
}
?>
<?=template_admin_footer()?>