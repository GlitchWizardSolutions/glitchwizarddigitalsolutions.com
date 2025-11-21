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
$value=0;
$selected='';
$match=0;
// Default record values
$record = [ 
    'description' => '',
    'url'  => '',
    'username' => '',
    'password'  => '',
    'type' => '',
    'notes'  => ''
];
/* cache table:
   id, description, url, username, password, type, notes, updated
   */

// Retrieve records from the database
$records =  $onthego_db->query('SELECT * FROM cache')->fetchAll(PDO::FETCH_ASSOC);
// Check whether the record ID is specified
if (isset($_GET['id'])) {
    // Retrieve the record from the database
    $stmt = $onthego_db->prepare('SELECT * FROM cache WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    // ID param exists, edit an existing record
    $page = 'Edit';
    if (isset($_POST['submit'])) {
                $stmt =  $onthego_db->prepare('UPDATE cache SET description = ?, url = ?, username = ?, password = ?, type = ?, notes = ?  WHERE id = ?');
                $stmt->execute([ $_POST['description'], $_POST['url'], $_POST['username'], $_POST['password'], $_POST['type'], $_POST['notes'], $_GET['id'] ]);
                header('Location: caches.php?success_msg=2');
                exit;
            
       
    }
    if (isset($_POST['delete'])) {
        // Redirect and delete the record
        header('Location: caches.php?delete=' . $_GET['id']);
        exit;
    }

} else {
    // Create a new record
    $page = 'Create';
        if (isset($_POST['submit'])) {
                $stmt = $onthego_db->prepare('INSERT INTO cache (description, url, username, password, type, notes) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([ $_POST['description'], $_POST['url'], $_POST['username'], $_POST['password'], $_POST['type'], $_POST['notes'] ]);
                header('Location: caches.php?success_msg=1');
                exit;
            }
}
?>
<?=template_admin_header($page . ' Cache of Things', 'resources', 'cache')?>
<div class="content-title">
    <div class="title">
     <i class="fa-solid fa-user-secret"></i>
        <div class="txt">
             <h2 class="responsive-width-100"><?=$page?> Cache of Things</h2>
        </div>
    </div>
</div>
<form action="" method="post">

    <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
        <a href="caches.php" class="btn alt mar-right-2">Cancel</a>
        <?php if ($page == 'Edit'): ?>
        <input type="submit" name="delete" value="Delete" class="btn red mar-right-2" onclick="return confirm('Are you sure you want to delete this item?')">
        <?php endif; ?>
        <input type="submit" name="submit" value="Save" class="btn">
    </div>

    <div class="content-block">

        <div class="form responsive-width-100">
  
            <label for="description"><i class="required">*</i> Description</label>
            <input id="description" type="text" name="description" placeholder="Description" value="<?=htmlspecialchars($record['description'], ENT_QUOTES)?>" required>
            <label for="type"> Type</label>
            <input id="type" type="text" name="type" placeholder="Type" value="<?=htmlspecialchars($record['type'], ENT_QUOTES)?>">

            <label for="url"> URL</label>
            <input id="url" type="text" name="url" placeholder="URL" value="<?=htmlspecialchars($record['url'], ENT_QUOTES)?>" >
    
            <label for="username"> Username</label>
            <input id="username" type="text" name="username" placeholder="Username" value="<?=htmlspecialchars($record['username'], ENT_QUOTES)?>">

            <label for="password">Password</label>
            <input id="password" type="text" name="password" placeholder="Password" value="<?=htmlspecialchars($record['password'], ENT_QUOTES)?>" >

            <label for="notes">Notes</label>
            <input id="notes" type="text" name="notes" placeholder="Notes" value="<?=htmlspecialchars($record['notes'], ENT_QUOTES)?>" >
    

        </div>

    </div>

</form>
<script src="assets/js/resource-system-script.js"></script>
<?=template_admin_footer()?>