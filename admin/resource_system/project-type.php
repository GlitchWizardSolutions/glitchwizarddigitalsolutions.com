<?php
/*
choose selected value in dropdown for the account_id by what is in the database, this is not occuring nor coded for yet. Was interrupted.
This means accessing the login database.

*/
require 'assets/includes/admin_config.php';
 

try {
	$login_system_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name . ';charset=' . db_charset, db_user, db_pass);
	$login_system_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	// If there is an error with the connection, stop the script and display the error.
	exit('Failed to connect to the login_system database!');
}

// Error message
$error_msg = '';
// Success message
$success_msg = '';
//declaire vars
$proj_name='';
// Default record values
$record = [
    'id' => '',
    'name' => '',
    'description' => '',
    'deliverables' => '',
    'amount' => 0.00,
    'frequency' => 0,
];
 
// Retrieve records from the database
$records = $login_system_db->query('SELECT * FROM project_types')->fetchAll(PDO::FETCH_ASSOC);
 

// Check whether the record ID is specified
if (isset($_GET['id'])) {
    // Retrieve the record from the database
    $stmt = $login_system_db->prepare('SELECT * FROM project_types WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    // ID param exists, edit an existing record
    $page = 'Edit';
    if (isset($_POST['submit'])) {
 $stmt = $login_system_db->prepare('UPDATE project_types SET name = ?, description = ?, deliverables = ?, amount = ?, frequency = ?  WHERE id = ?');
                $stmt->execute([$_POST['name'], $_POST['description'], $_POST['deliverables'], $_POST['amount'], $_POST['frequency'], $_GET['id'] ]);
                header('Location: project-types.php?success_msg=2');
                exit;
    }
    if (isset($_POST['delete'])) {
        // Redirect and delete the record
        header('Location: project-types.php?delete=' . $_GET['id']);
        exit;
    }

} else {
    // Create a new record
    $page = 'Create';
        if (isset($_POST['submit'])) {
          
                $stmt = $login_system_db->prepare('INSERT INTO project_types (name, description, deliverables, amount, frequency) VALUES (?,?,?,?,?)');
                $stmt->execute([ $_POST['name'], $_POST['description'], $_POST['deliverables'], $_POST['amount'], $_POST['frequency'] ]);
                header('Location: project-types.php?success_msg=1');
                exit;
            }
}
?>
<?=template_admin_header($page . ' Project Types', 'resources', 'types')?>

<div class="content-title">
    <div class="title">
     <i class="fa-solid fa-user-secret"></i>
        <div class="txt">
             <h2 class="responsive-width-100"><?=$page?> Project Type</h2>
        </div>
    </div>
</div>
<form action="" method="post">

    <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
        <a href="project-types.php" class="btn btn-secondary mar-right-2">Cancel</a>
        <?php if ($page == 'Edit'): ?>
        <input type="submit" name="delete" value="Delete" class="btn btn-danger mar-right-2" onclick="return confirm('Are you sure you want to delete this record?')">
        <?php endif; ?>
        <input type="submit" name="submit" value="Save" class="btn btn-success">
    </div>
 
    <div class="content-block">

        <div class="form responsive-width-100">
 
            <label for="name">Project</label>
            <input type="text" name="name" id="name" style='background:cornsilk' placeholder="Project Name" value="<?=htmlspecialchars($record['name']??'', ENT_QUOTES)?>">
            <div style="width:100%; margin:15px">
            <strong>Current Description:</strong><br>
             <?=htmlspecialchars($record['description']??'', ENT_QUOTES)?>
            </div>
            <label for="description">Description</label> 
            <input type="text" height='50px' name="description" id="description" style='background:cornsilk' placeholder="Description"  value="<?=htmlspecialchars($record['description']??'', ENT_QUOTES)?>">
            <label for="amount">Value</label>
            <input type="number" name="amount" id="amount" style='background:cornsilk' placeholder=0.00 value="<?=htmlspecialchars($record['amount']??'', ENT_QUOTES)?>">
            <div style="width:100%; margin:15px">
            <strong>Current Description:</strong><br>
             <?=htmlspecialchars($record['deliverables']??'', ENT_QUOTES)?>
            </div>
 
            <label for="deliverables">Deliverables</label>
            <input type="text" name="deliverables" id="deliverables" style='background:cornsilk' placeholder='List deliverables' value="<?=htmlspecialchars($record['deliverables']??'', ENT_QUOTES)?>">
                       
            <label for="frequency"># of Days</label>
            <input type="number" name="frequency" id="frequency" style='background:cornsilk' placeholder=0 value="<?=htmlspecialchars($record['frequency']??'', ENT_QUOTES)?>">
            
  
            
        </div>

    </div>
    <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
        <a href="project-types.php" class="btn btn-secondary mar-right-2">Cancel</a>
        <?php if ($page == 'Edit'): ?>
        <input type="submit" name="delete" value="Delete" class="btn btn-danger mar-right-2" onclick="return confirm('Are you sure you want to delete this record?')">
        <?php endif; ?>
        <input type="submit" name="submit" value="Save" class="btn btn-success">
    </div>
</form>
<script src="assets/js/resource-system-script.js"></script>
<?=template_admin_footer()?>