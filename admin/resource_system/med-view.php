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
$page = 'View';
// Retrieve records from the database
$records = $onthego_db->query('SELECT * FROM meds')->fetchAll(PDO::FETCH_ASSOC);
// Check whether the record ID is specified
if (isset($_GET['id'])) {
    // Retrieve the record from the database
    $stmt = $onthego_db->prepare('SELECT * FROM meds WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    exit('No ID specified!');
}
$copy="";
?>
    <?=template_admin_header($page . ' Medications', 'resources', 'meds')?>
    
<div class="content-title mb-3">
    <div class="title">
       <i class="fa-solid fa-circle-info"></i>
        <div class="txt">
            <h2>Medication Record</h2>
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
        <a href="meds.php" class="btn">Return</a>
        <a href="med.php?id=<?=$record['id']?>" class="btn">Edit</a>
        <a href="meds.php?delete=<?=$record['id']?>" onclick="return confirm('Are you sure you want to delete this record?')" class="btn">Delete</a>
    </div>
</div>
<div class="content-block">
    <div class="table">
        <table>
            <thead>
                <tr>
                    <td style='text-align:center; background:grey; color:white; text-transform: uppercase'><strong><?=htmlspecialchars($record['name'], ENT_QUOTES)?></strong></td>
                </tr>
             <tr>
                    <td><strong><?=htmlspecialchars($record['status'], ENT_QUOTES)?></strong></td>
                </tr>    
            </thead>
            <tbody>
      
               <tr>
                    <td><strong>Notes:</strong><br><?=htmlspecialchars($record['notes'], ENT_QUOTES)?></td>
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
                    <td><strong>Usage</strong></td>
                </tr>
            </thead>
            <tbody>
                 <tr>
                    <td><?=htmlspecialchars($record['patient'], ENT_QUOTES)?></td>
                </tr>
                <tr>
                    <td><?=htmlspecialchars($record['dosage'], ENT_QUOTES)?></td>
                </tr>
                 <tr>
                    <td><?=htmlspecialchars($record['type'], ENT_QUOTES)?></td>
                </tr>
                 <tr>
                    <td><?=htmlspecialchars($record['frequency'], ENT_QUOTES)?></td>
                </tr>
                
            </tbody>
        </table>
    </div>
</div>
 
<script src="assets/js/resource-system-script.js"></script>
<?=template_admin_footer()?>