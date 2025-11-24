<?php
/*
This page is client-project-log.php and it can edit or create a log in the 
client-projects-logs table.
*/ 
require 'assets/includes/admin_config.php';
include_once '../assets/includes/components.php';
// Check if the user is logged-in
check_loggedin($pdo, '../../index.php');
// Fetch account details associated with the logged-in user
$stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
$stmt->execute([ $_SESSION['id'] ]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch projects details for the drop down selection below.
$stmt = $pdo->prepare('SELECT * FROM client_projects ORDER BY subject');
$stmt->execute();
$client_projects = $stmt->fetchALL(PDO::FETCH_ASSOC);

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

// Error message
$error_msg = '';
// Success message
$success_msg = '';
$value=0;
$selected='';
$match=0;
// Default record values
$record = [ 
    'client_projects_id' => $_GET['project_id'] ?? '',
    'area' => 'Function',
    'status' => 'Working On',
    'client_note'  => '',
    'dev_note' => '',
    'private_dev_note'  => ''
];
 
// Retrieve records from the database
$records = $logon_db->query('SELECT * FROM client_projects_logs')->fetchAll(PDO::FETCH_ASSOC);
// Check whether the record ID is specified
if (isset($_GET['id'])) {
    // Retrieve the record from the database
    $stmt = $logon_db->prepare('SELECT * FROM client_projects_logs WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // ID param exists, edit an existing record
    $page = 'Edit';
    if (isset($_POST['submit'])) {
                $stmt = $logon_db->prepare('UPDATE client_projects_logs SET client_projects_id = ?, client_note = ?, dev_note = ?, private_dev_note = ?  WHERE id = ?');
                $stmt->execute([ $_POST['client_projects_id'], $_POST['client_note'], $_POST['dev_note'], $_POST['private_dev_note'], $_GET['id'] ]);
                header('Location: client-project-logs.php?success_msg=2');
                exit;
            
       
    }
    if (isset($_POST['delete'])) {
        // Redirect and delete the record
        header('Location: client-project-logs?delete=' . $_GET['id']);
        exit;
    }

} else {
    // Create a new record
    $page = 'Create';
        if (isset($_POST['submit'])) {
                $stmt = $logon_db->prepare('INSERT INTO client_projects_logs (client_projects_id, area, status, client_note, dev_note, private_dev_note) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([ $_POST['client_projects_id'], $_POST['area'],$_POST['status'], $_POST['client_note'], $_POST['dev_note'], $_POST['private_dev_note'] ]);
                header('Location: client-project-logs.php?success_msg=1');
                exit;
            }
}
?>
<?=template_admin_header($page . ' Client Project Logs', 'resources', 'logs')?>

<?=generate_breadcrumbs([
    ['label' => 'Resource System', 'url' => 'index.php'],
    ['label' => 'Project Logs', 'url' => 'client-project-logs.php'],
    ['label' => $page . ' Log']
])?>

<div class="content-title">
    <div class="title">
        <i class="fa-solid fa-clipboard-list"></i>
        <div class="txt">
            <h2><?=$page?> Project Log</h2>
            <p>Track project activity and communications</p>
        </div>
    </div>
</div>

<form action="" method="post">
    <div class="form-professional">
        
        <!-- Project Details Section -->
        <div class="form-section">
            <h3 class="section-title">Project & Status</h3>
            
            <div class="form-group">
                <label for="client_projects_id">Project <span class="required">*</span></label>
                <select name="client_projects_id" id="client_projects_id" required> 
                <?php foreach($client_projects as $row) :?>
                 <?php 
                        $selected='';
                        $value=$row['id'];
                        $match=$record['client_projects_id']?? '';
                        if($value==$match){
                           $selected='selected';
                        }
                   ?>
                 <option value='<?=$value?>'<?=$selected;?>><?=$value?>&nbsp;<?=$row['subject'] ?></option>
                <?php endforeach ?>
                </select>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="area">Area <span class="required">*</span></label>
                    <select name="area" id="area" required> 
                     <option value="<?=$record['area']?>" selected><?=$record['area'] ?></option>
                     <option value="Branding">Branding</option>
                     <option value="Copy">Copy</option>
                     <option value="Function">Function</option>
                     <option value="Maintenance">Maintenance</option>
                     <option value="Other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="status">Status <span class="required">*</span></label>
                    <select name="status" id="status" required> 
                     <option value="<?=$record['status']?>" selected><?=$record['status'] ?></option>
                     <option value="Not Started">Not Started</option>
                     <option value="Working On">Working On</option>
                     <option value="Waiting On">Waiting On</option>
                     <option value="Completed">Completed</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Notes Section -->
        <div class="form-section">
            <h3 class="section-title">Log Notes</h3>
            
            <div class="form-group">
                <label for="client_note">Client Notes</label>
                <textarea id="client_note" name="client_note" placeholder="What did the client say or request?" rows="4"><?=htmlspecialchars($record['client_note'], ENT_QUOTES)?></textarea>
            </div>

            <div class="form-group">
                <label for="dev_note">Developer Note</label>
                <textarea id="dev_note" name="dev_note" placeholder="What work was done or what's the response?" rows="4"><?=htmlspecialchars($record['dev_note'], ENT_QUOTES)?></textarea>
            </div>

            <div class="form-group">
                <label for="private_dev_note">Private Dev Note</label>
                <textarea id="private_dev_note" name="private_dev_note" placeholder="Internal notes not shared with client" rows="4"><?=htmlspecialchars($record['private_dev_note'], ENT_QUOTES)?></textarea>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="form-actions">
            <a href="client-project-logs.php" class="btn btn-secondary">Cancel</a>
            <?php if ($page == 'Edit'): ?>
            <input type="submit" name="delete" value="Delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this log?')">
            <?php endif; ?>
            <input type="submit" name="submit" value="Save" class="btn btn-primary">
        </div>
        
    </div>
</form>
<script src="assets/js/resource-system-script.js"></script>
<?=template_admin_footer()?>