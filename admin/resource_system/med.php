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
// Error message
$error_msg = '';
// Success message
$success_msg = '';
$value=0;
$selected='';
$match=0;
// Default record values
$record = [ 
    'patient' => '',
    'name'  => '',
    'dosage' => '',
    'type'  => '',
    'frequency' => '',
    'notes'  => '',
    'status'  => ''
];
/* meds table:
     patient, name, dosage, type, frequency, notes, status
   */

// Retrieve records from the database
$records =  $onthego_db->query('SELECT * FROM meds')->fetchAll(PDO::FETCH_ASSOC);
// Check whether the record ID is specified
if (isset($_GET['id'])) {
    // Retrieve the record from the database
    $stmt = $onthego_db->prepare('SELECT * FROM meds WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    // ID param exists, edit an existing record
    $page = 'Edit';
    if (isset($_POST['submit'])) {
                $stmt =  $onthego_db->prepare('UPDATE meds SET patient = ?, name = ?, dosage = ?, type = ?, frequency = ?, notes = ?  WHERE id = ?');
                $stmt->execute([ $_POST['patient'], $_POST['name'], $_POST['dosage'], $_POST['type'], $_POST['frequency'], $_POST['notes'], $_GET['id'] ]);
                header('Location: meds.php?success_msg=2');
                exit;
            
       
    }
    if (isset($_POST['delete'])) {
        // Redirect and delete the record
        header('Location: meds.php?delete=' . $_GET['id']);
        exit;
    }

} else {
    // Create a new record
    $page = 'Create';
        if (isset($_POST['submit'])) {
                $stmt = $onthego_db->prepare('INSERT INTO meds (patient, name, dosage, type, frequency, notes) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([ $_POST['patient'], $_POST['name'], $_POST['dosage'], $_POST['type'], $_POST['frequency'], $_POST['notes'] ]);
                header('Location: meds.php?success_msg=1');
                exit;
            }
}
?>
<?=template_admin_header($page . ' Medications', 'resources', 'meds')?>
<div class="content-title">
    <div class="title">
     <i class="fa-solid fa-user-secret"></i>
        <div class="txt">
             <h2 class="responsive-width-100"><?=$page?> Medications</h2>
        </div>
    </div>
</div>
<form action="" method="post">

    <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
        <a href="meds.php" class="btn alt mar-right-2">Cancel</a>
        <?php if ($page == 'Edit'): ?>
        <input type="submit" name="delete" value="Delete" class="btn red mar-right-2" onclick="return confirm('Are you sure you want to delete this item?')">
        <?php endif; ?>
        <input type="submit" name="submit" value="Save" class="btn">
    </div>

    <div class="content-block">

        <div class="form responsive-width-100">
  
            <label for="patient"><i class="required">*</i> Patient</label>
            <input id="patient" type="text" name="patient" placeholder="Patient" value="<?=htmlspecialchars($record['patient'], ENT_QUOTES)?>" required>
            <label for="type"> Type</label>
            <input id="type" type="text" name="type" placeholder="Type" value="<?=htmlspecialchars($record['type'], ENT_QUOTES)?>">

            <label for="name"> Name</label>
            <input id="name" type="text" name="name" placeholder="Name" value="<?=htmlspecialchars($record['name'], ENT_QUOTES)?>" >
    
            <label for="dosage"> Dosage</label>
            <input id="dosage" type="text" name="dosage" placeholder="Dosage" value="<?=htmlspecialchars($record['dosage'], ENT_QUOTES)?>">

            <label for="type">Frequency</label>
            <input id="frequency" type="text" name="frequency" placeholder="Frequency" value="<?=htmlspecialchars($record['frequency'], ENT_QUOTES)?>" >

            <label for="notes">Notes</label>
            <input id="notes" type="text" name="notes" placeholder="Notes" value="<?=htmlspecialchars($record['notes'], ENT_QUOTES)?>" >
            <label for="status">Status</label>
            <input id="status" type="text" name="status" placeholder="Status" value="<?=htmlspecialchars($record['status'], ENT_QUOTES)?>" >
    

        </div>

    </div>

</form>
<script src="assets/js/resource-system-script.js"></script>
<?=template_admin_footer()?>