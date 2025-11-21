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
// Connect to the login Database using the PDO interface
try {
	$logon_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name . ';charset=' . db_charset, db_user, db_pass);
	$logon_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	// If there is an error with the connection, stop the script and display the error.
	exit('Failed to connect to the logon database!');
}
// Connect to the On the Go Database using the PDO interface
try {
	$error_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name9 . ';charset=' . db_charset, db_user9, db_pass);
	$error_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	// If there is an error with the connection, stop the script and display the error.
	exit('! Failed to connect to the on the error handling database!');
}
$page = 'View';
// Retrieve records from the database
$records = $error_db->query('SELECT * FROM error_handling')->fetchAll(PDO::FETCH_ASSOC);
// Check whether the record ID is specified
if (isset($_GET['id'])) {
    // Retrieve the record from the database
    $stmt = $error_db->prepare('SELECT * FROM error_handling WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    exit('No ID specified!');
}
$copy="";
?>
    <?=template_admin_header($page . ' Error Log', 'resources', 'errors')?>
    
<div class="content-title">
    <div class="title">
       <i class="fa-solid fa-circle-info"></i>
        <div class="txt">
            <h2>Error Log</h2>
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
        <a href="error-logs.php" class="btn">Return</a>
        <a href="error-logs.php?delete=<?=$record['id']?>" onclick="return confirm('Are you sure you want to delete this record?')" class="btn">Delete</a>
    </div>
</div>
<div class="content-block">
    <div class="table">
        <table>
            <thead> 
                <tr>
                    <td style='text-align:center; background:grey; color:white; text-transform: uppercase'><strong><?=htmlspecialchars($record['application'], ENT_QUOTES)?></strong></td>
                    <td style='text-align:center; background:grey; color:white; text-transform: uppercase'><strong><?=date('M d, Y', strtotime($record['timestamp']?? ''))?></strong></td>
                </tr>
                 </thead>
          <tbody>       
             <tr>
                   <td style='text-align: start; background:#F8F4FF'><strong>Path:</strong><br><?=htmlspecialchars($record['path'], ENT_QUOTES)?></td>
                   <td style='text-align: start; background:#F8F4FF'><strong><?=htmlspecialchars($record['pagename'], ENT_QUOTES)?></strong></td>
                   
                </tr>    
           
            
                <tr>
                  <td style='text-align: start; background:#F5F5F5'><strong>Section:</strong><br><?=htmlspecialchars($record['section'], ENT_QUOTES)?></td>  
                  <td style='text-align: start; background:#F5F5F5'><strong>Noted:</strong><br><?=htmlspecialchars($record['noted'], ENT_QUOTES)?></td>  
               </tr>
               <tr>
                  <td style='text-align: start; background:#FFF0F5'><strong>Inputs:</strong><br><?=htmlspecialchars($record['inputs'], ENT_QUOTES)?></td>  
                  <td style='text-align: start; background:#FFF0F5'><strong>Outputs:</strong><br><?=htmlspecialchars($record['outputs'], ENT_QUOTES)?></td>
               </tr>

                
            </tbody>
        </table>
    </div>
</div>
 
 
    <div class="content-block">
        <div class="data">
          <div class="table">
              
			<table>
				<thead>
				   	<tr>
						<td colspan=4><strong>Error Thrown</strong></td>
				
					</tr>
				</thead>
				<tbody>
				 
			      
					<tr>
					<td colspan=4 style='text-align: start; background:#F5F5F5'><strong><?=htmlspecialchars($record['outputs']?? '', ENT_QUOTES)?></strong></td>
 
					</tr>
					 
	   </tbody>
	  </table>
     </div>
    </div>
   </div> 
 
<script src="assets/js/resource-system-script.js"></script>
<?=template_admin_footer()?>