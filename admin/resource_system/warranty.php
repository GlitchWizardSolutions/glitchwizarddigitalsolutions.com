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
$records = $onthego_db->query('SELECT * FROM warranty_tickets')->fetchAll(PDO::FETCH_ASSOC);
// Check whether the record ID is specified
if (isset($_GET['id'])) {
    // Retrieve the record from the database
    $stmt = $onthego_db->prepare('SELECT * FROM warranty_tickets WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    // ID param exists, edit an existing record
    $page = 'Edit';
    if (isset($_POST['submit'])) {
                $stmt = $onthego_db->prepare('UPDATE warranty_tickets SET title = ?, msg = ?, warranty_type_id = ?, ticket_status = ?, owner = ?, reminder_date = ?, purchase_date = ?, warranty_expiration_date = ?,   WHERE id = ?');
                $stmt->execute([ $_POST['title'],$_POST['msg'],$_POST['warranty_type_id'], _POST['ticket_status'], _POST['owner'],_POST['reminder_date'],_POST['purchase_date'],_POST['warranty_expiration_date'], $_GET['id'] ]);
                header('Location: warranties.php?success_msg=2');
                exit;
            
       
    }
    if (isset($_POST['delete'])) {
        // Redirect and delete the record
        header('Location: warranties.php?delete=' . $_GET['id']);
        exit;
    }

} else {
    // Create a new record
    $page = 'Create';
        if (isset($_POST['submit'])) {
                $stmt = $onthego_db->prepare('INSERT INTO warranty_tickets (title, msg, warranty_type_id, ticket_status, owner, reminder_date, purchase_date, warranty_expiration_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([ $_POST['title'], $_POST['msg'],  $_POST['warranty_type_id'], $_POST['ticket_status'], $_POST['owner'], $_POST['reminder_date'], $_POST['purchase_date'], $_POST['warranty_expiration_date']]);
                header('Location: warranties.php?success_msg=1');
                exit;
            }
}
?>
<?=template_admin_header($page . 'Warranties', 'resources', 'warranty')?>
<div class="content-title">
    <div class="title">
      <i class="fa-solid fa-user-secret"></i>
        <div class="txt">
             <h2 class="responsive-width-100"><?=$page?>&nbsp;<?=htmlspecialchars($record['description'], ENT_QUOTES)?></h2>
             <p>Warranty Record</p>
            
        </div>
    </div>
</div>

<form action="" method="post">

    <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
        <a href="warranties.php" class="btn alt mar-right-2">Cancel</a>
        <?php if ($page == 'Edit'): ?>
        <input type="submit" name="delete" value="Delete" class="btn red mar-right-2" onclick="return confirm('Are you sure you want to delete this record?')">
        <?php endif; ?>
        <input type="submit" name="submit" value="Save" class="btn">
    </div>

    <div class="content-block">

        <div class="form responsive-width-100">
        
            <label for="title"><i class="required">*</i>Title</label>
            <input id="title" type="text" name="title" placeholder="Description" value="<?=htmlspecialchars($record['title']??'', ENT_QUOTES)?>" required>
             
            <label for="msg"><i class="required">*</i>Message</label>
            <input id="msg" type="text" name="msg" placeholder="" value="<?=htmlspecialchars($record['msg']??'', ENT_QUOTES)?>" required>
           
            <label for="ticket_status"><i class="required">*</i>Status</label>
            <input id="ticket_status" type="text" name="ticket_status" placeholder="" value="<?=htmlspecialchars($record['ticket_status']??'', ENT_QUOTES)?>" required>
        </div>
    </div>
</form> 
<script src="assets/js/resource-system-script.js"></script>
<?=template_admin_footer()?>