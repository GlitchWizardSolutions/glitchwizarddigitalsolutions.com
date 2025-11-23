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

<div class="content-title">
    <div class="title">
       <i class="fa-solid fa-diagram-project"></i>
        <div class="txt">
             <h2 class="responsive-width-100"><?=$page?> Client Project</h2>
            <p>Manage client project details and assignments</p>
        </div>
    </div>
</div>
<form action="" method="post">

    <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
        <a href="client-projects.php" class="btn alt mar-right-2">Cancel</a>
        <?php if ($page == 'Edit'): ?>
        <input type="submit" name="delete" value="Delete" class="btn red mar-right-2" onclick="return confirm('Are you sure you want to delete this project AND it's logs?')">
        <?php endif; ?>
        <input type="submit" name="submit" value="Save" class="btn">
    </div>

    <div class="content-block">

        <div class="form responsive-width-100">

           <div class="form-control" style='width:80%'>
                <label for="acc_id">Account</label>
                <select name="acc_id" id="acc_id" required> 
            
            <?php foreach($accounts as $row) :?>
             <?php 
                    $selected='';
                    $value=$row['id'];
                    $match=$record['acc_id'];
                    if($value==$match){
                       $selected='selected';
                    }
               ?>
             <option value='<?=$value?>'<?=$selected;?>><?=$value?>&nbsp;<?=$row['full_name'] ?></option>
            
            <?php endforeach ?>
                </select>
            </div>
            
         <div class="form-control" style='width:80%'>
                <label for="domain_id">Domain</label>
                <select name="domain_id" id="domain_id" required> 
            
            <?php foreach($domain as $row) :?>
             <?php 
                    $selected='';
                    $value=$row['id'];
                    $match=$record['domain_id'];
                    if($value==$match){
                       $selected='selected';
                    }
               ?>
             <option value='<?=$value?>'<?=$selected;?>><?=$value?>&nbsp;<?=$row['domain'] ?></option>
            
            <?php endforeach ?>
                </select>
            </div>
            
            
        <div class="form-control" style='width:80%'>
                <label for="project_type_id">Project Type</label>
                <select name="project_type_id" id="project_type_id" required> 
            
            <?php foreach($project_type as $row) :?>
             <?php 
                    $selected='';
                    $value=$row['id'];
                    $match=$record['project_type_id'];
                    if($value==$match){
                       $selected='selected';
                    }
               ?>
             <option value='<?=$value?>'<?=$selected;?>><?=$value?>&nbsp;<?=$row['name'] ?></option>
            
            <?php endforeach ?>
                </select>
            </div>

            <label for="subject"><i class="required">*</i> Project Name</label>
            <input id="subject" type="text" name="subject" placeholder="Name of Project" value="<?=htmlspecialchars($record['subject'], ENT_QUOTES)?>" required>

            <label for="client_quote"><i class="required">*</i> Client Quote</label>
            <input id="client_quote" type="text" name="client_quote" placeholder="Client Quote" value="<?=htmlspecialchars($record['client_quote'], ENT_QUOTES)?>" required>
    
            <label for="dev_comment"> Dev Comment</label>
            <input id="dev_comment" type="text" name="dev_comment" placeholder="Dev Comment" value="<?=htmlspecialchars($record['dev_comment'], ENT_QUOTES)?>">

        </div>

    </div>

    <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
        <a href="client-projects.php" class="btn btn-secondary mar-right-2">Cancel</a>
        <?php if ($page == 'Edit'): ?>
        <input type="submit" name="delete" value="Delete" class="btn btn-danger mar-right-2" onclick="return confirm('Are you sure you want to delete this project AND it\'s logs?')">
        <?php endif; ?>
        <input type="submit" name="submit" value="Save" class="btn btn-success">
    </div>

</form>

<script src="assets/js/resource-system-script.js"></script>
<?=template_admin_footer()?>