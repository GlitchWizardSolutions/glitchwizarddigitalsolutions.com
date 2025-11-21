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
    'resource' => '',
    'details' => '',
    'url'  => '',
    'userid' => '',
    'password'  => '',
    'email' => 'webdev@glitchwizardsolutions.com',
    'investment'  => 'none',
    'type'  => 'personal',
    'name' => 'Barbara Moore'
];
// Retrieve records from the database
$records = $onthego_db->query('SELECT * FROM sass_account')->fetchAll(PDO::FETCH_ASSOC);
// Check whether the record ID is specified
if (isset($_GET['id'])) {
    // Retrieve the record from the database
    $stmt = $onthego_db->prepare('SELECT * FROM sass_account WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    // ID param exists, edit an existing record
    $page = 'Edit';
    if (isset($_POST['submit'])) {
                $stmt = $onthego_db->prepare('UPDATE sass_account SET resource = ?, details = ?, url = ?, userid = ?, password = ?, email = ?, investment = ?, type = ?, name = ? WHERE id = ?');
                $stmt->execute([ $_POST['resource'], $_POST['details'], $_POST['url'], $_POST['userid'], $_POST['password'], $_POST['email'], $_POST['investment'], $_POST['type'], $_POST['name'], $_GET['id'] ]);
                header('Location: sass-accounts.php?success_msg=2');
                exit;
            
       
    }
    if (isset($_POST['delete'])) {
        // Redirect and delete the record
        header('Location: sass-accounts.php?delete=' . $_GET['id']);
        exit;
    }

} else {
    // Create a new record
    $page = 'Create';
        if (isset($_POST['submit'])) {
                $stmt = $onthego_db->prepare('INSERT INTO sass_account (resource, details, url, userid, password, email, investment, type, name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([ $_POST['resource'], $_POST['details'], $_POST['url'], $_POST['userid'], $_POST['password'], $_POST['email'], $_POST['investment'], $_POST['type'], $_POST['name'] ]);
                header('Location: sass-accounts.php?success_msg=1');
                exit;
            }
}
?>
<?=template_admin_header($page . ' Sass Resource', 'resources', 'sass')?>
<div class="content-title">
    <div class="title">
     <i class="fa-solid fa-user-secret"></i>
        <div class="txt">
             <h2 class="responsive-width-100"><?=$page?> Record</h2>
            <p>Online Resource Login Data</p>
        </div>
    </div>
</div>
<form action="" method="post">

    <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
        <a href="sass-accounts.php" class="btn alt mar-right-2">Cancel</a>
        <?php if ($page == 'Edit'): ?>
        <input type="submit" name="delete" value="Delete" class="btn red mar-right-2" onclick="return confirm('Are you sure you want to delete this record?')">
        <?php endif; ?>
        <input type="submit" name="submit" value="Save" class="btn">
    </div>

    <div class="content-block">

        <div class="form responsive-width-100">

            <label for="resource"><i class="required">*</i> Resource</label>
            <input id="resource" type="text" name="resource" placeholder="Resource Name" value="<?=htmlspecialchars($record['resource'], ENT_QUOTES)?>" required>

            <label for="details">Details</label>
            <textarea id="details" name="details" placeholder="Add Notes..."><?=htmlspecialchars($record['details'], ENT_QUOTES)?></textarea>

            <label for="url"><i class="required">*</i> Url</label>
            <input id="url" type="text" name="url" placeholder="Login Link" value="<?=htmlspecialchars($record['url'], ENT_QUOTES)?>" required>

            <label for="userid"><i class="required">*</i> User Id</label>
            <input id="userid" type="text" name="userid" placeholder="User Id" value="<?=htmlspecialchars($record['userid'], ENT_QUOTES)?>" required>

            <label for="password"><i class="required">*</i> Password</label>
            <input id="password" type="text" name="password" placeholder="Password" value="<?=htmlspecialchars($record['password'], ENT_QUOTES)?>" required>
    
            <label for="name"> Name</label>
            <input id="name" type="text" name="name" placeholder="Name on File" value="<?=htmlspecialchars($record['name'], ENT_QUOTES)?>">
            
            <label for="email"> Email</label>
            <input id="email" type="email" name="email" placeholder="Email on File" value="<?=htmlspecialchars($record['email'], ENT_QUOTES)?>">

            <label for="investment">Investment</label>
                <select name="investment" id="investment">
                    <option value="lifetime"<?=$record['investment']=='lifetime'?' selected':''?>>Lifetime</option>
                    <option value="subscription"<?=$record['investment']=='subscription'?' selected':''?>>Subscription</option>
                    <option value="inactive"<?=$record['investment']=='inactive'?' selected':''?>>Inactive</option>
                    <option value="none"<?=$record['investment']=='none'?' selected':''?>>None</option>
                </select>

                <label for="type">Type</label>
                <select name="type" id="type">
                    <option value="business"<?=$record['type']=='business'?' selected':''?>>Business</option>
                    <option value="personal"<?=$record['type']=='personal'?' selected':''?>>Personal</option>
                </select>
        </div>

    </div>

</form>
<script src="assets/js/resource-system-script.js"></script>
<?=template_admin_footer()?>