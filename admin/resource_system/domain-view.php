<?php
require 'assets/includes/admin_config.php';
try {
	$login_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name . ';charset=' . db_charset, db_user, db_pass); 
	$login_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	// If there is an error with the connection, stop the script and display the error.
	exit('Failed to connect to the login system database!');
}
$page = 'View';
// Retrieve records from the database
$records = $login_db ->query('SELECT * FROM domains')->fetchAll(PDO::FETCH_ASSOC);
// Check whether the record ID is specified
if (isset($_GET['id'])) {
    // Retrieve the record from the database
    $stmt = $login_db ->prepare('SELECT * FROM domains WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    exit('No ID specified!');
}
$copy="";
?>
    <?=template_admin_header($page . 'Domains', 'resources', 'domains')?>
    
<div class="content-title">
    <div class="title">
       <i class="fa-solid fa-circle-info"></i>
        <div class="txt">
            <h2>Domains </h2>
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
        <a href="domains.php" class="btn">Return</a>
        <a href="domain.php?id=<?=$record['id']?>" class="btn">Edit</a>
        <a href="domains.php"?delete=<?=$record['id']?>" onclick="return confirm('Are you sure you want to delete this record?')" class="btn">Delete</a>
    </div>
</div>

<div class="content-block">
    <div class="table"style="width:100%">
        <table>
                <tr>
                   <td class="title">Status</td> <td><?=htmlspecialchars($record['status'], ENT_QUOTES)?></td>
                </tr>
                <tr>
               <td class="title">Row ID: </td><td><?=htmlspecialchars($record['id'], ENT_QUOTES)?></td>
                    </tr>
                <tr>
               <td class="title">Domain Name: </td><td><?=htmlspecialchars($record['domain'], ENT_QUOTES)?></td>
                </tr>
                <tr>
               <td class="title">Account ID</td> <td><?=htmlspecialchars($record['account_id'], ENT_QUOTES)?></td>
                </tr>
                <tr>
               <td class="title">Date Due</td> <td><?=date("m/d/y", strtotime($record['due_date'])?? '')?></td>
                </tr>
                <tr>
               <td class="title">Amount Due</td> <td>$<?=htmlspecialchars($record['amount'], ENT_QUOTES)?></td>
                </tr>
            <tbody>
                <tr>

                </tr>
            </tbody>
        </table>
    </div>
</div>
<style>
.title  {
font-weight:bold;
width:30%; 
}
</style>
<script src="assets/js/resource-system-script.js"></script>
<?=template_admin_footer()?>