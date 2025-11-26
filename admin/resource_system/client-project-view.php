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
$page = 'View';
// Retrieve records from the database
$records = $logon_db->query('SELECT * FROM client_projects')->fetchAll(PDO::FETCH_ASSOC);
// Check whether the record ID is specified
if (isset($_GET['id'])) {
    // Retrieve the record from the database
    $stmt = $logon_db->prepare('SELECT * FROM client_projects WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    exit('No ID specified!');
}
$copy="";
    $stmt = $logon_db->prepare('SELECT * FROM accounts WHERE id = ?');
    $stmt->execute([ $record['acc_id'] ]);
    $acc_id = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $logon_db->prepare('SELECT * FROM domains WHERE id = ?');
    $stmt->execute([ $record['domain_id'] ]);
    $domain_id = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $logon_db->prepare('SELECT * FROM project_types WHERE id = ?');
    $stmt->execute([ $record['project_type_id'] ]);
    $project_type_id = $stmt->fetch(PDO::FETCH_ASSOC); 
    
    $stmt = $logon_db->prepare('SELECT area, status, client_projects_id, client_note, dev_note, private_dev_note, date_created FROM client_projects_logs WHERE client_projects_id = ? ORDER BY date_created DESC');
    $stmt->execute([ $_GET['id'] ]);
    $project_logs = $stmt->fetchALL(PDO::FETCH_ASSOC); 
?>
    <?=template_admin_header($page . ' Client Projects', 'resources', 'projects')?>
    
<div class="content-title mb-3">
    <div class="title">
       <i class="fa-solid fa-circle-info"></i>
        <div class="txt">
            <h2>Client Project</h2>
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
        <a href="client-projects.php" class="btn btn-secondary">Return</a>
        <a href="client-project.php?id=<?=$record['id']?>" class="btn btn-primary">Edit</a>
        <a href="client-project-log.php?project_id=<?=$record['id']?>" class="btn btn-success">Add Log Entry</a>
        <a href="client-projects.php?delete=<?=$record['id']?>" onclick="return confirm('Are you sure you want to delete this record?')" class="btn btn-danger">Delete</a>
    </div>
</div>
<div class="content-block">
    <div class="table">
        <table>
            <thead>
                <tr>
                    <td style='text-align:center; background:grey; color:white; text-transform: uppercase'><strong><?=htmlspecialchars($record['subject'], ENT_QUOTES)?></strong></td>
                </tr>
             <tr>
                    <td><strong><?=htmlspecialchars($project_type_id['name'], ENT_QUOTES)?></strong></td>
                </tr>    
            </thead>
            <tbody>
      
               <tr>
                    <td><strong>Description:</strong><br><?=htmlspecialchars($project_type_id['description'], ENT_QUOTES)?></td>
               </tr>
                <tr>
                    <td><strong>Deliverables:</strong><br><?=htmlspecialchars($project_type_id['deliverables'], ENT_QUOTES)?></td>
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
                    <td><strong>Account Information</strong></td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?=htmlspecialchars($acc_id['full_name'], ENT_QUOTES)?></td>
                </tr>
                 <tr>
                    <td><?=htmlspecialchars($acc_id['email'], ENT_QUOTES)?></td>
                </tr>
                 <tr>
                    <td><?=htmlspecialchars($acc_id['phone'], ENT_QUOTES)?></td>
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
                    <td><strong>Client Note</strong></td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?=htmlspecialchars($record['client_quote'], ENT_QUOTES)?></td>
                </tr>
  
            </tbody>
        </table>
    </div>
</div>
  <td></td>
    <div class="content-block">
        <div class="data">
          <div class="table">
              
			<table>
				<thead>
				   	<tr>
						<td colspan=2><strong>Project Logs</strong></td>
				
					</tr>
				</thead>
				<tbody>
					<?php if (empty($project_logs)): ?>
					<tr>
						<td colspan=2 style="text-align:center;">There are no logs found.</td>
					</tr>
					<?php endif; ?>
					
					<?php foreach ($project_logs as $project_logs): ?>
			      
					<tr>
						<td colspan=2 style="background:grey; color:white; text-align: start;"><strong><?=date('M d, Y', strtotime($project_logs['date_created']?? ''))?></strong></td>
					</tr>
					<tr>
					    <td style='text-align: start; color:grey background:white'>&nbsp;<strong>Area:</strong>&nbsp;<?=$project_logs['area']?? '' ?></td>
					    <td style='text-align: start; color:grey background:white; padding-left:20px'>&nbsp;<strong>Status:</strong>&nbsp;<?=$project_logs['status']?? '' ?></td>
					</tr>
					<tr>
					    <td style='text-align: start; background:#F8F4FF'><strong>WebDev:</strong></td>
					    <td style='text-align: start; background:#F8F4FF'>&nbsp;<?=$project_logs['dev_note']?? '' ?></td>
					</tr>
					<tr>
					    <td style='text-align: start; background:#F5F5F5'><strong>Client:</strong></td>
					    <td style='text-align: start; background:#F5F5F5'>&nbsp;<?=$project_logs['client_note']?? '' ?></td>
					</tr>
					<tr>
				        <td style='text-align: start; background:#FFF0F5'><strong>Private:</strong></td>
					    <td style='text-align: start; background:#FFF0F5'>&nbsp;<?=$project_logs['private_dev_note']?? '' ?></td>
					</tr>
					<?php endforeach; ?>
	   </tbody>
	  </table>
     </div>
    </div>
   </div> 
 
<script src="assets/js/resource-system-script.js"></script>
<?=template_admin_footer()?>