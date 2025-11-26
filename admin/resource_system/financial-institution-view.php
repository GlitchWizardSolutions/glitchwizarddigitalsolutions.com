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
<?=template_admin_header($page . ' Financial Instututions', 'resources', 'cards')?>
    
<div class="content-title mb-3">
    <div class="title">
       <i class="fa-solid fa-circle-info"></i>
        <div class="txt">
            <h2>Viewing <?=htmlspecialchars($record['description'], ENT_QUOTES)?></h2>
            <p>Type: <strong><?=htmlspecialchars($record['card_type'], ENT_QUOTES)?>&nbsp;&nbsp;</strong>Record: <strong>#<?=htmlspecialchars($record['id'], ENT_QUOTES)?></strong></p>
            
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
        <a href="financial-institutions.php" class="btn btn-secondary">Return</a>
        <a href="financial-institution.php?id=<?=$record['id']?>" class="btn btn-primary">Edit</a>
        <a href="financial-institutions.php?delete=<?=$record['id']?>" onclick="return confirm('Are you sure you want to delete this record?')" class="btn btn-danger">Delete</a>
    </div>
</div>

<div class="content-block">
    <div class="table">
        <table>
            <thead>
                <tr>
                 <td>Bank  </td><td> </td><td><strong><?=htmlspecialchars($record['bank'], ENT_QUOTES)?></strong></td>
                </tr>
                <tr>
                    <td>Account #  </td><td> </td><td><strong><?=htmlspecialchars($record['account_number'], ENT_QUOTES)?></strong></td>
                </tr>
                 <tr>
                    <td>Routing #  </td><td></td><td><strong><?=htmlspecialchars($record['routing_number'], ENT_QUOTES)?></strong></td>
                </tr>
                <tr>
                    <td>Brand  </td> <td></td><td><strong><?=htmlspecialchars($record['brand'], ENT_QUOTES)?></strong></td>
                </tr>
 
                <tr>
                    <td>Name  </td><td></td><td><strong><?=htmlspecialchars($record['name'], ENT_QUOTES)?></strong></td>
                </tr>
                <tr>
                    <td>Number  </td><td></td><td><strong><?=htmlspecialchars($record['card_number'], ENT_QUOTES)?></strong></td>
                </tr>
                <tr>
                     <td>Expires  </td><td></td> <td><strong><?=htmlspecialchars($record['expires'], ENT_QUOTES)?></strong></td>
                </tr>
                  <tr>
                      <td>Code  </td><td></td> <td><strong><?=htmlspecialchars($record['code'], ENT_QUOTES)?></strong></td>
                  </tr>
                
                <tr>
                      <td>ZipCode  </td><td></td> <td><strong><?=htmlspecialchars($record['zip_code'], ENT_QUOTES)?></strong></td>
                </tr>
                
                    
                    <tr>
                      <td>Filepath  </td><td></td> <td><strong><?=htmlspecialchars($record['filepath'], ENT_QUOTES)?></strong></td>
                </tr>
                
            </thead>
            <tbody>
                <tr>
                    
                    
                    
                   
                   
                   
                </tr>
            </tbody>
        </table>
    </div>
</div>
<script src="assets/js/resource-system-script.js"></script>
<?=template_admin_footer()?>