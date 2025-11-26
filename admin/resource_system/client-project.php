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

<style>
.form-professional {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
}

.form-professional .form {
    max-width: 100% !important;
    width: 100% !important;
}

.form-professional label {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 8px;
    display: block;
    font-size: 14px;
}

.form-professional input[type="text"],
.form-professional input[type="number"],
.form-professional select {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #6b46c1;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
    background: #ffffff;
    color: #2c3e50;
    margin-bottom: 20px;
    box-sizing: border-box;
}

.form-professional input:focus,
.form-professional select:focus {
    outline: none;
    border-color: #8e44ad;
    box-shadow: 0 0 0 3px rgba(107, 70, 193, 0.15);
    background: #ffffff;
}

.form-professional select {
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b46c1' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 16px center;
    padding-right: 40px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 0;
}

.form-row .form-group {
    margin-bottom: 0;
}

.form-group {
    margin-bottom: 20px;
    box-sizing: border-box;
    width: 100%;
}

.form-group label {
    margin-bottom: 8px;
}

.form-group input,
.form-group select {
    margin-bottom: 0;
    width: 100%;
    box-sizing: border-box;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="content-title mb-3">
    <div class="title">
       <i class="fa-solid fa-diagram-project"></i>
        <div class="txt">
             <h2 class="responsive-width-100"><?=$page?> Client Project</h2>
            <p>Client project tracking and management</p>
        </div>
    </div>
</div>

<form action="" method="post" class="form-professional">
    <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
        <a href="client-projects.php" class="btn alt mar-right-2">Cancel</a>
        <?php if ($page == 'Edit'): ?>
        <input type="submit" name="delete" value="Delete" class="btn red mar-right-2" onclick="return confirm('Are you sure you want to delete this record?')">
        <?php endif; ?>
        <input type="submit" name="submit" value="Save" class="btn btn-success">
    </div>

    <div class="content-block">
        <div class="form responsive-width-100">

            <!-- Row 1: Account + Domain -->
            <div class="form-row">
                <div class="form-group">
                    <label for="acc_id">Account</label>
                    <select name="acc_id" id="acc_id" required> 
                        <option value="">Select Account</option>
                        <?php foreach($accounts as $row): ?>
                            <?php $selected = ($record['acc_id'] == $row['id']) ? 'selected' : ''; ?>
                            <option value="<?=$row['id']?>" <?=$selected?>><?=$row['full_name']?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="domain_id">Domain</label>
                    <select name="domain_id" id="domain_id" required> 
                        <option value="">Select Domain</option>
                        <?php foreach($domain as $row): ?>
                            <?php $selected = ($record['domain_id'] == $row['id']) ? 'selected' : ''; ?>
                            <option value="<?=$row['id']?>" <?=$selected?>><?=$row['domain']?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Row 2: Project Name + Project Type -->
            <div class="form-row">
                <div class="form-group">
                    <label for="subject">Project Name</label>
                    <input type="text" name="subject" id="subject" placeholder="Name of Project" value="<?=htmlspecialchars($record['subject']??'', ENT_QUOTES)?>" required>
                </div>
                
                <div class="form-group">
                    <label for="project_type_id">Project Type</label>
                    <select name="project_type_id" id="project_type_id" required> 
                        <option value="">Select Type</option>
                        <?php foreach($project_type as $row): ?>
                            <?php $selected = ($record['project_type_id'] == $row['id']) ? 'selected' : ''; ?>
                            <option value="<?=$row['id']?>" <?=$selected?>><?=$row['name']?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Row 3: Client Quote + Dev Comment -->
            <div class="form-row">
                <div class="form-group">
                    <label for="client_quote">Client Quote</label>
                    <input type="text" name="client_quote" id="client_quote" placeholder="Client Quote" value="<?=htmlspecialchars($record['client_quote']??'', ENT_QUOTES)?>" required>
                </div>
                
                <div class="form-group">
                    <label for="dev_comment">Dev Comment</label>
                    <input type="text" name="dev_comment" id="dev_comment" placeholder="Dev Comment" value="<?=htmlspecialchars($record['dev_comment']??'', ENT_QUOTES)?>">
                </div>
            </div>

        </div>
    </div>

    <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
        <a href="client-projects.php" class="btn alt mar-right-2">Cancel</a>
        <?php if ($page == 'Edit'): ?>
        <input type="submit" name="delete" value="Delete" class="btn red mar-right-2" onclick="return confirm('Are you sure you want to delete this project AND it\'s logs?')">
        <?php endif; ?>
        <input type="submit" name="submit" value="Save" class="btn">
    </div>
</form>

<script src="assets/js/resource-system-script.js"></script>
<?=template_admin_footer()?>