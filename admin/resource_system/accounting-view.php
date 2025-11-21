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
$records = $onthego_db->query('SELECT * FROM financial_cards')->fetchAll(PDO::FETCH_ASSOC);
// Check whether the record ID is specified
if (isset($_GET['id'])) {
    // Retrieve the record from the database
    $stmt = $onthego_db->prepare('SELECT * FROM financial_cards WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    exit('No ID specified!');
}
$copy="";
?>
    <?=template_admin_header($page . ' Is Important', 'secret', 'financials')?>
    
<div class="content-title">
    <div class="title">
       <i class="fa-solid fa-circle-info"></i>
        <div class="txt">
            <h2>Record</h2>
            <p>View Record</p>
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
        <a href="is_importants.php" class="btn">Return</a>
        <a href="is_important.php?id=<?=$record['id']?>" class="btn">Edit</a>
        <a href="is_importants.php?delete=<?=$record['id']?>" onclick="return confirm('Are you sure you want to delete this record?')" class="btn">Delete</a>
    </div>
</div>

<div class="content-block">
    <div class="table">
        <table>
            <thead>
                <tr>
                    <td>Name: </td><td><?=htmlspecialchars($record['name'], ENT_QUOTES)?></td>
                </tr>
                <tr>
                    <td>Number: </td><td><?=htmlspecialchars($record['card_number'], ENT_QUOTES)?></td>
                </tr>
                <tr>
                     <td>Expires: </td> <td><?=htmlspecialchars($record['expires'], ENT_QUOTES)?></td>
                </tr>
                  <tr>
                      <td>Code: </td> <td><?=htmlspecialchars($record['code'], ENT_QUOTES)?></td>
                  </tr>
                <tr>
                    <td>Brand: </td> <td><?=htmlspecialchars($record['brand'], ENT_QUOTES)?></td>
                </tr>
                <tr>
                      <td>ZipCode: </td> <td><?=htmlspecialchars($record['zip_code'], ENT_QUOTES)?></td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    
                    
                    
                   
                   
                   
                </tr>
            </tbody>
        </table>
    </div>
</div>
<script src="assets/js/not_important_script.js"></script>
<?=template_admin_footer()?>