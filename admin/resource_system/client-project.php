<?php
require 'assets/includes/admin_config.php';
include_once '../assets/includes/components.php';

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
// Error message
$error_msg = '';
// Success message
$success_msg = '';
$value=0;
$selected='';
$match=0;
// Default record values
$record = [ 
    'acc_id' => '',
    'domain_id'  => '',
    'project_type_id' => '',
    'subject'  => '',
    'client_quote' => '',
    'dev_comment'  => '',
];
$stmt =$logon_db->prepare('SELECT * FROM accounts');
$stmt->execute();
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt =$logon_db->prepare('SELECT * FROM domains');
$stmt->execute();
$domain = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt =$logon_db->prepare('SELECT * FROM project_types');
$stmt->execute();
$project_type = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Retrieve records from the database
$records = $logon_db->query('SELECT * FROM client_projects')->fetchAll(PDO::FETCH_ASSOC);
// Check whether the record ID is specified
if (isset($_GET['id'])) {
    // Retrieve the record from the database
    $stmt = $logon_db->prepare('SELECT * FROM client_projects WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    // ID param exists, edit an existing record
    $page = 'Edit';
    if (isset($_POST['submit'])) {
                $stmt = $logon_db->prepare('UPDATE client_projects SET acc_id = ?, domain_id = ?, project_type_id = ?, subject = ?, client_quote = ?, dev_comment = ?  WHERE id = ?');
                $stmt->execute([ $_POST['acc_id'], $_POST['domain_id'], $_POST['project_type_id'], $_POST['subject'], $_POST['client_quote'], $_POST['dev_comment'], $_GET['id'] ]);
                header('Location: client-projects.php?success_msg=2');
                exit;
            
       
    }
    if (isset($_POST['delete'])) {
        // Redirect and delete the record
        header('Location: client-projects.php?delete=' . $_GET['id']);
        exit;
    }

} else {
    // Create a new record
    $page = 'Create';
        if (isset($_POST['submit'])) {
                $stmt = $logon_db->prepare('INSERT INTO client_projects (acc_id, domain_id, project_type_id, subject, client_quote, dev_comment) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([ $_POST['acc_id'], $_POST['domain_id'], $_POST['project_type_id'], $_POST['subject'], $_POST['client_quote'], $_POST['dev_comment'] ]);
                header('Location: client-projects.php?success_msg=1');
                exit;
            }
}
?>
<?=template_admin_header($page . ' Client Projects', 'resources', 'projects')?>

<?=generate_breadcrumbs([
    ['label' => 'Resource System', 'url' => 'index.php'],
    ['label' => 'Client Projects', 'url' => 'client-projects.php'],
    ['label' => $page . ' Project']
])?>

<div class="content-title mb-3">
    <div class="title">
       <i class="fa-solid fa-diagram-project"></i>
        <div class="txt">
             <h2 class="responsive-width-100"><?=$page?> Client Project</h2>
            <p>Client project tracking and management</p>
        </div>
    </div>
</div>

<form action="" method="post">

    <div class="form-professional">
        
        <div class="form-section">
            <h3 class="section-title">Project Details</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="acc_id">Account <span class="required">*</span></label>
                    <select name="acc_id" id="acc_id" required> 
                        <option value="">Select Account</option>
                        <?php foreach($accounts as $row): ?>
                            <?php $selected = ($record['acc_id'] == $row['id']) ? 'selected' : ''; ?>
                            <option value="<?=$row['id']?>" <?=$selected?>><?=$row['full_name']?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="domain_id">Domain <span class="required">*</span></label>
                    <select name="domain_id" id="domain_id" required> 
                        <option value="">Select Domain</option>
                        <?php foreach($domain as $row): ?>
                            <?php $selected = ($record['domain_id'] == $row['id']) ? 'selected' : ''; ?>
                            <option value="<?=$row['id']?>" <?=$selected?>><?=$row['domain']?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="subject">Project Name <span class="required">*</span></label>
                    <input type="text" name="subject" id="subject" placeholder="Name of project" value="<?=htmlspecialchars($record['subject']??'', ENT_QUOTES)?>" required>
                </div>
                
                <div class="form-group">
                    <label for="project_type_id">Project Type <span class="required">*</span></label>
                    <select name="project_type_id" id="project_type_id" required> 
                        <option value="">Select Type</option>
                        <?php foreach($project_type as $row): ?>
                            <?php $selected = ($record['project_type_id'] == $row['id']) ? 'selected' : ''; ?>
                            <option value="<?=$row['id']?>" <?=$selected?>><?=$row['name']?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="client_quote">Client Quote <span class="required">*</span></label>
                    <textarea name="client_quote" id="client_quote" rows="4" placeholder="Client quote or requirements..." required><?=htmlspecialchars($record['client_quote']??'', ENT_QUOTES)?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="dev_comment">Dev Comment</label>
                    <textarea name="dev_comment" id="dev_comment" rows="4" placeholder="Developer notes or comments..."><?=htmlspecialchars($record['dev_comment']??'', ENT_QUOTES)?></textarea>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <a href="client-projects.php" class="btn btn-secondary">Cancel</a>
            <?php if ($page == 'Edit'): ?>
            <input type="submit" name="delete" value="Delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this project AND it\'s logs?')">
            <?php endif; ?>
            <input type="submit" name="submit" value="Save" class="btn btn-success">
        </div>

    </div>

</form>

<script src="assets/js/resource-system-script.js"></script>
<?=template_admin_footer()?>