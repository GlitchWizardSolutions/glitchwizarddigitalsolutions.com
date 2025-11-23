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
// Connect to the login accounts Database using the PDO interface
try {
	$login_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name . ';charset=' . db_charset, db_user, db_pass);
	$login_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	// If there is an error with the connection, stop the script and display the error.
	exit('Failed to connect to the login system database!');
}
// Connect to the invoice database using the PDO interface
try {
	$invoice_system = new PDO('mysql:host=' . db_host . ';dbname=' . db_name5 . ';charset=' . db_charset, db_user, db_pass);
	$invoice_system->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	// If there is an error with the connection, stop the script and display the error.
	exit('Failed to connect to the invoice system database!');
}
// Connect to the On the Go Database using the PDO interface
try {
	$onthego_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name2 . ';charset=' . db_charset, db_user, db_pass);
	$onthego_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	// If there is an error with the connection, stop the script and display the error.
	exit('Failed to connect to the on the go database!');
}
$page = 'View';
// Retrieve records from the database
$records = $onthego_db->query('SELECT * FROM dev_projects')->fetchAll(PDO::FETCH_ASSOC);
// Check whether the record ID is specified
if (isset($_GET['id'])) {
    // Retrieve the record from the database
    $stmt = $onthego_db->prepare('SELECT * FROM dev_projects WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    exit('No ID specified!');
}
$copy="";
?>
    <?=template_admin_header($page . ' Dev Projects', 'resources', 'financials')?>
    
<div class="content-title">
    <div class="title">
       <i class="fa-solid fa-circle-info"></i>
        <div class="txt">
            <h2>Viewing <?=htmlspecialchars($record['description'], ENT_QUOTES)?></h2>
        </div>
    </div>
</div>

<?php if (isset($success_msg)): ?>
<div class="msg success">
    <i class="fas fa-check-circle"></i>
    <p><?=$success_msg?></p>
    <i class="fas fa-times"></i>
</div>
<?php endif; ?>

<div class="content-header responsive-flex-column pad-top-5">
    <div class="btns">
        <a href="dev-projects.php" class="btn btn-secondary">Return</a>
        <a href="dev-project.php?id=<?=$record['id']?>" class="btn btn-primary">Edit</a>
        <a href="dev-projects.php?delete=<?=$record['id']?>" onclick="return confirm('Are you sure you want to delete this record?')" class="btn btn-danger">Delete</a>
    </div>
</div>

<div class="content-block">
    <div class="table">
        <table>
            <tbody>
                <tr>
                 <td>Description</td><td> </td><td><strong><?=htmlspecialchars($record['description'], ENT_QUOTES)?></strong></td>
                </tr>
                <tr>
                    <td>Account</td><td> </td><td><strong><?=htmlspecialchars($record['account_id'], ENT_QUOTES)?></strong></td>
                </tr>
                 <tr>
                    <td>Domain</td><td></td><td><strong><?=htmlspecialchars($record['domain_id'], ENT_QUOTES)?></strong></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<script src="assets/js/resource-system-script.js"></script>
<?=template_admin_footer()?>