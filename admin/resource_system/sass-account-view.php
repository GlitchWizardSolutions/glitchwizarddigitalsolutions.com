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
$page = 'View';
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
       <i class="fa-solid fa-circle-info"></i>
        <div class="txt">
            <h2>Sass Resources</h2>
            <p><?=$page . ' Record' ?></p>
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
        <a href="sass-accounts.php" class="btn btn-secondary">Return</a>
        <a href="sass-account.php?id=<?=$record['id']?>" class="btn btn-primary">Edit</a>
        <a href="sass-accounts.php?delete=<?=$record['id']?>" onclick="return confirm('Are you sure you want to delete this record?')" class="btn btn-danger">Delete</a>
    </div>
</div>

<div class="content-block">
    <div class="table">
        <table>
            <thead>
                <tr>
                    <td>Resource</td>
                    <td>Url</td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?=htmlspecialchars($record['resource'], ENT_QUOTES)?></td>
                    <td><a href="https://<?=htmlspecialchars($record['url'], ENT_QUOTES)?>"><?=htmlspecialchars($record['url'], ENT_QUOTES)?></a></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<div class="content-block">
    <div class="table">
        <table>
            <thead>
                <tr>
                    <td>Details</td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?=htmlspecialchars($record['details'], ENT_QUOTES)?></td>
               </tr>
            </tbody>
        </table>
    </div>
</div>
<div class="content-block">
    <div class="table">
        <table>
            <thead>
                <tr>
                    <td>User ID</td>
                    <td>Password</td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?=htmlspecialchars($record['userid'], ENT_QUOTES)?></td>
                    <td><?=htmlspecialchars($record['password'], ENT_QUOTES)?></td>
                    
               </tr>
            </tbody>
        </table>
    </div>
</div>
<div class="content-block">
    <div class="table">
        <table>
            <thead>
                <tr>
                    <td>Usage</td>
                    <td>Investment</td>
                    <td>Type</td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?=htmlspecialchars($record['usage'], ENT_QUOTES)?></td>
                    <td><?=htmlspecialchars($record['investment'], ENT_QUOTES)?></td>
                    <td><?=htmlspecialchars($record['type'], ENT_QUOTES)?></td>
               </tr>
            </tbody>
        </table>
    </div>
</div>
<div class="content-block">
    <div class="table">
        <table>
            <thead>
                <tr>
                    <td>Email</td>
                    <td>Name</td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?=htmlspecialchars($record['email'], ENT_QUOTES)?></td>
                    <td><?=htmlspecialchars($record['name'], ENT_QUOTES)?></td>
               </tr>
            </tbody>
        </table>
    </div>
</div>
<script src="assets/js/resource-system-script.js"></script>
<?=template_admin_footer()?>