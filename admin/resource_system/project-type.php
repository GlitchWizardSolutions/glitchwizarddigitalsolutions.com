<?php
/*
choose selected value in dropdown for the account_id by what is in the database, this is not occuring nor coded for yet. Was interrupted.
This means accessing the login database.

*/
require 'assets/includes/admin_config.php';
include_once '../assets/includes/components.php';
 

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

<?=generate_breadcrumbs([
    ['label' => 'Resource System', 'url' => 'index.php'],
    ['label' => 'Project Types', 'url' => 'project-types.php'],
    ['label' => $page . ' Project Type']
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
.form-professional textarea {
    width: 100%;
    max-width: 100%;
    padding: 12px 16px;
    border: 2px solid #6b46c1;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
    background: #ffffff;
    color: #2c3e50;
    box-sizing: border-box;
}

.form-professional textarea {
    resize: vertical;
    min-height: 100px;
    font-family: inherit;
    line-height: 1.6;
}

.form-professional input:focus,
.form-professional textarea:focus {
    outline: none;
    border-color: #8e44ad;
    box-shadow: 0 0 0 3px rgba(107, 70, 193, 0.15);
    background: #ffffff;
}

.form-professional .form-row {
    display: grid !important;
    grid-template-columns: 1fr 1fr 1fr !important;
    gap: 20px !important;
    margin-bottom: 20px !important;
    width: 100%;
}

.form-professional .form-row .form-group {
    margin-bottom: 0 !important;
}

.form-professional .form-group {
    margin-bottom: 20px;
    box-sizing: border-box;
    width: 100%;
    min-width: 0;
}

.form-professional .form-group label {
    margin-bottom: 8px;
}

.form-professional .form-group input,
.form-professional .form-group textarea {
    margin-bottom: 0;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    min-width: 0;
}

@media (max-width: 768px) {
    .form-professional .form-row {
        grid-template-columns: 1fr !important;
    }
}
</style>

<div class="content-title mb-3">
    <div class="title">
     <i class="fa-solid fa-user-secret"></i>
        <div class="txt">
             <h2 class="responsive-width-100"><?=$page?> Project Type</h2>
             <p>Define project templates with deliverables and pricing</p>
        </div>
    </div>
</div>

<form action="" method="post" class="form-professional">

    <div class="content-block">
        <div class="form responsive-width-100">

            <!-- Row 1: Project Name + Value + Number of Days -->
            <div class="form-row">
                <div class="form-group">
                    <label for="name">Project Name</label>
                    <input type="text" name="name" id="name" placeholder="Project Name" value="<?=htmlspecialchars($record['name']??'', ENT_QUOTES)?>" required>
                </div>
                
                <div class="form-group">
                    <label for="amount">Value</label>
                    <input type="number" name="amount" id="amount" placeholder="0.00" step="0.01" value="<?=htmlspecialchars($record['amount']??'', ENT_QUOTES)?>">
                </div>
                
                <div class="form-group">
                    <label for="frequency"># of Days</label>
                    <input type="number" name="frequency" id="frequency" placeholder="0" value="<?=htmlspecialchars($record['frequency']??'', ENT_QUOTES)?>">
                </div>
            </div>

            <!-- Row 2: Description (full width) -->
            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" id="description" placeholder="Project description and overview..."><?=htmlspecialchars($record['description']??'', ENT_QUOTES)?></textarea>
            </div>

            <!-- Row 3: Deliverables (full width) -->
            <div class="form-group">
                <label for="deliverables">Deliverables</label>
                <textarea name="deliverables" id="deliverables" placeholder="List project deliverables..."><?=htmlspecialchars($record['deliverables']??'', ENT_QUOTES)?></textarea>
            </div>

        </div>
    </div>

    <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
        <a href="project-types.php" class="btn alt mar-right-2">Cancel</a>
        <?php if ($page == 'Edit'): ?>
        <input type="submit" name="delete" value="Delete" class="btn red mar-right-2" onclick="return confirm('Are you sure you want to delete this record?')">
        <?php endif; ?>
        <input type="submit" name="submit" value="Save" class="btn btn-success">
    </div>

</form>
<script src="assets/js/resource-system-script.js"></script>
<?=template_admin_footer()?>