<?php
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
// Connect to the On the Go Database using the PDO interface
try {
	$onthego_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name2 . ';charset=' . db_charset, db_user, db_pass);
	$onthego_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	// If there is an error with the connection, stop the script and display the error.
	exit('Failed to connect to the on the go database!');
}
$page = 'Use';
// Retrieve records from the database
$records = $onthego_db->query('SELECT * FROM sass_account')->fetchAll(PDO::FETCH_ASSOC);
// Check whether the record ID is specified
if (isset($_GET['id'])) {
    // Retrieve the record from the database
    $stmt = $onthego_db->prepare('SELECT * FROM sass_account WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    exit('No ID specified!');
}
$copy="";
?>
    <?=template_admin_header($page . ' Sass Resource', 'resources', 'sass')?>
    
<div class="content-title">
    <div class="title">
      <i class="fa-solid fa-circle-info" style='background:purple'></i>
        <div class="txt">
            <h2>Domain Information</h2>
        </div>
    </div>
</div>

<div class="content-block">
   <div class="table">
        <table>
          <tbody>
              <tr><td><h3><?=htmlspecialchars($record['resource'], ENT_QUOTES)?></h3></td></tr>
              <tr><td><?=htmlspecialchars($record['details'], ENT_QUOTES)?></td></tr>
            </tbody>
        </table>
    </div>
</div>

<div class="content-block">
    <div class="table">
        <table>
          <tbody>
              <tr><td>Url</td><td><a href="https://<?=htmlspecialchars($record['url'], ENT_QUOTES)?>"><?=htmlspecialchars($record['url'], ENT_QUOTES)?></a></td></tr>
              <tr><td>User ID</td><td><?=htmlspecialchars($record['userid'], ENT_QUOTES)?></td></tr>
              <tr><td>Password</td><td><?=htmlspecialchars($record['password'], ENT_QUOTES)?></td></tr>
            </tbody>
        </table>
    </div>
</div>

<div class="content-block">
    <div class="table">
        <table>
              <tbody>
                <tr><td>Email</td><td><?=htmlspecialchars($record['email'], ENT_QUOTES)?></td></tr> 
                <tr><td>Name</td> <td><?=htmlspecialchars($record['name'], ENT_QUOTES)?></td></tr>
            </tbody>
        </table>
    </div>
</div>
<script src="assets/js/resource-system-script.js"></script>
<?=template_admin_footer()?>