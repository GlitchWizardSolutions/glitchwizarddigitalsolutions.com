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
    'domain' => '',
    'account_id' => '',
    'due_date'  => '2025-12-30',
    'host_url' => '',
    'host_login' => '',
    'host_password'  => '',
    'notes' => '',
    'status' => '',
    'amount'  => 0.00
];
 
// Retrieve records from the database
$accounts = $login_system_db->query('SELECT * FROM accounts')->fetchAll(PDO::FETCH_ASSOC);

// Retrieve records from the database
$records = $login_system_db->query('SELECT * FROM domains')->fetchAll(PDO::FETCH_ASSOC);

// Check whether the record ID is specified
if (isset($_GET['id'])) {
    // Retrieve the record from the database
    $stmt = $login_system_db->prepare('SELECT * FROM domains WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    // ID param exists, edit an existing record
    $page = 'Edit';
    if (isset($_POST['submit'])) {
 $stmt = $login_system_db->prepare('UPDATE domains SET domain = ?, account_id = ?, due_date = ?, host_url = ?, amount = ?, host_login = ?, host_password = ?, notes = ?, status = ?  WHERE id = ?');
                $stmt->execute([$_POST['domain'], $_POST['account_id'], $_POST['due_date'], $_POST['host_url'], $_POST['amount'], $_POST['host_login'], $_POST['host_password'], $_POST['notes'], $_POST['status'], $_GET['id'] ]);
                header('Location: domains.php?success_msg=2');
                exit;
    }
    if (isset($_POST['delete'])) {
        // Redirect and delete the record
        header('Location: domains.php?delete=' . $_GET['id']);
        exit;
    }

} else {
    // Create a new record
    $page = 'Create';
        if (isset($_POST['submit'])) {
          
                $stmt = $login_system_db->prepare('INSERT INTO domains (domain, account_id, due_date, host_url, amount, host_login, host_password, notes, status) VALUES (?,?,?,?,?,?,?,?,?)');
                $stmt->execute([ $_POST['domain'], $_POST['account_id'], $_POST['due_date'], $_POST['host_url'], $_POST['amount'], $_POST['host_login'], $_POST['host_password'], $_POST['notes'], $_POST['status'] ]);
                header('Location: domains.php?success_msg=1');
                exit;
            }
}
?>
<?=template_admin_header($page . ' Domains', 'resources', 'domains')?>

<?=generate_breadcrumbs([
    ['label' => 'Resource System', 'url' => 'index.php'],
    ['label' => 'Domains', 'url' => 'domains.php'],
    ['label' => $page . ' Domain']
])?>

<div class="content-title mb-3">
    <div class="title">
       <i class="fa-solid fa-globe"></i>
        <div class="txt">
             <h2 class="responsive-width-100"><?=$page?> Domain</h2>
            <p>Domain registration and renewal information</p>
        </div>
    </div>
</div>

<form action="" method="post">

    <div class="form-professional">
        
        <div class="form-section">
            <h3 class="section-title">Domain Information</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="domain">Domain Name <span class="required">*</span></label>
                    <input type="text" name="domain" id="domain" placeholder="example.com" value="<?=htmlspecialchars($record['domain']??'', ENT_QUOTES)?>" required>
                </div>
                
                <div class="form-group">
                    <label for="account_id">Account <span class="required">*</span></label>
                    <select name="account_id" id="account_id" required> 
                        <option value="">Select Account</option>
                        <?php foreach($accounts as $row): ?>
                            <?php $selected = ($record['account_id'] == $row['id']) ? 'selected' : ''; ?>
                            <option value="<?=$row['id']?>" <?=$selected?>><?=$row['full_name']?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="status">Status <span class="required">*</span></label>
                    <select name="status" id="status" required>
                        <option value="Active" <?=($record['status']??'Active') == 'Active' ? 'selected' : ''?>>Active</option>
                        <option value="Inactive" <?=($record['status']??'') == 'Inactive' ? 'selected' : ''?>>Inactive</option>
                        <option value="Expired" <?=($record['status']??'') == 'Expired' ? 'selected' : ''?>>Expired</option>
                        <option value="Cancelled" <?=($record['status']??'') == 'Cancelled' ? 'selected' : ''?>>Cancelled</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="due_date">Renewal Date <span class="required">*</span></label>
                    <?php if (isset($_GET['id'])): ?>
                        <input type="datetime-local" name="due_date" id="due_date" value="<?=$record['due_date']?>" required> 
                    <?php else: ?>
                        <input type="datetime-local" name="due_date" id="due_date" value="<?=date('Y-m-d\TH:i', strtotime('+1 year'))?>" required>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="amount">Renewal Fee <span class="required">*</span></label>
                    <input type="number" step="0.01" name="amount" id="amount" placeholder="25.00" value="<?=htmlspecialchars($record['amount']??'25.00', ENT_QUOTES)?>" required>
                </div>
            </div>
        </div>
        
        <div class="form-section">
            <h3 class="section-title">Hosting Credentials</h3>
            
            <div class="form-group">
                <label for="host_url">Host URL</label>
                <input type="text" name="host_url" id="host_url" placeholder="https://registrar.com" value="<?=htmlspecialchars($record['host_url']??'', ENT_QUOTES)?>">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="host_login">Host Login</label>
                    <input type="text" name="host_login" id="host_login" placeholder="Username" value="<?=htmlspecialchars($record['host_login']??'', ENT_QUOTES)?>">
                </div>
                
                <div class="form-group">
                    <label for="host_password">Host Password</label>
                    <input type="text" name="host_password" id="host_password" placeholder="Password" value="<?=htmlspecialchars($record['host_password']??'', ENT_QUOTES)?>">
                </div>
            </div>

            <div class="form-group">
                <label for="notes">Notes</label>
                <textarea name="notes" id="notes" rows="4" placeholder="Additional notes..."><?=htmlspecialchars($record['notes']??'', ENT_QUOTES)?></textarea>
            </div>
        </div>
        
        <div class="form-actions">
            <a href="domains.php" class="btn btn-secondary">Cancel</a>
            <?php if ($page == 'Edit'): ?>
            <input type="submit" name="delete" value="Delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this record?')">
            <?php endif; ?>
            <input type="submit" name="submit" value="Save" class="btn btn-success">
        </div>

    </div>

</form>
<script src="assets/js/resource-system-script.js"></script>
<?=template_admin_footer()?>