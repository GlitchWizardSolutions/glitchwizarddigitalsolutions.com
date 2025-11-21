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
    $stmt = $invoice_system->prepare('SELECT * FROM domains');
    $stmt->execute();
    $domains = $stmt->fetch(PDO::FETCH_ASSOC);
// Error message
$error_msg = '';
// Success message
$success_msg = '';
// Default record values
$record = [
    'description'=> '',
    'account_id' => '',
    'domain_id' => ''
];
// Retrieve records from the database
$records = $onthego_db->query('SELECT * FROM dev_project')->fetchAll(PDO::FETCH_ASSOC);
// Check whether the record ID is specified
if (isset($_GET['id'])) {
    // Retrieve the record from the database
    $stmt = $onthego_db->prepare('SELECT * FROM dev_project WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    // ID param exists, edit an existing record
    $page = 'Edit';
    if (isset($_POST['submit'])) {
                $stmt = $onthego_db->prepare('UPDATE dev_project SET description = ?, account_id = ?, domain_id = ?,   WHERE id = ?');
                $stmt->execute([ $_POST['description'],$_POST['account_id'],$_POST['domain_id'], $_GET['id'] ]);
                header('Location: dev-projects.php?success_msg=2');
                exit;
            
       
    }
    if (isset($_POST['delete'])) {
        // Redirect and delete the record
        header('Location: dev-projects.php?delete=' . $_GET['id']);
        exit;
    }

} else {
    // Create a new record
    $page = 'Create';
        if (isset($_POST['submit'])) {
                $stmt = $onthego_db->prepare('INSERT INTO dev_project (description, account_id, domain_id) VALUES (?, ?, ?)');
                $stmt->execute([ $_POST['description'], $_POST['account_id'],  $_POST['domain_id']]);
                header('Location: dev-projects.php?success_msg=1');
                exit;
            }
}
?>
<?=template_admin_header($page . 'Dev Projects', 'resources', 'financials')?>
<div class="content-title">
    <div class="title">
      <i class="fa-solid fa-user-secret"></i>
        <div class="txt">
             <h2 class="responsive-width-100"><?=$page?>&nbsp;<?=htmlspecialchars($record['description'], ENT_QUOTES)?></h2>
             <p>Online Project Data</p>
            
        </div>
    </div>
</div>

<form action="" method="post">

    <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
        <a href="dev-projects.php" class="btn alt mar-right-2">Cancel</a>
        <?php if ($page == 'Edit'): ?>
        <input type="submit" name="delete" value="Delete" class="btn red mar-right-2" onclick="return confirm('Are you sure you want to delete this record?')">
        <?php endif; ?>
        <input type="submit" name="submit" value="Save" class="btn">
    </div>

    <div class="content-block">

        <div class="form responsive-width-100">
        
            <label for="description"><i class="required">*</i>Description</label>
            <input id="description" type="text" name="description" placeholder="Description" value="<?=htmlspecialchars($record['description']??'', ENT_QUOTES)?>" required>
             <!--put a select query here and loop through the rows in the login system table accounts, and try to match it to the current one.-->
            <label for="account_id"><i class="required">*</i>Account Email</label>
            <input id="account_id" type="text" name="account_id" placeholder="" value="<?=htmlspecialchars($record['account_id']??'', ENT_QUOTES)?>" required>
            <!--put a select query here and loop through the rows in the invoice system table domains, and try to match it to the current one.-->
            <label for="domain_id"><i class="required">*</i>Routing</label>
            <input id="domain_id" type="text" name="domain_id" placeholder="" value="<?=htmlspecialchars($record['domain_id']??'', ENT_QUOTES)?>" required>
        </div>
    </div>
</form> 
<script src="assets/js/resource-system-script.js"></script>
<?=template_admin_footer()?>