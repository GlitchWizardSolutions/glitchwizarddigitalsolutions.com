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
.form-professional input[type="datetime-local"],
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

.form-row-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.form-row .form-group,
.form-row-3 .form-group {
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
    .form-row,
    .form-row-3 {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="content-title">
    <div class="title">
       <i class="fa-solid fa-globe"></i>
        <div class="txt">
             <h2 class="responsive-width-100"><?=$page?> Domain</h2>
            <p>Domain registration and renewal information</p>
        </div>
    </div>
</div>

<form action="" method="post" class="form-professional">
    <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
        <a href="domains.php" class="btn alt mar-right-2">Cancel</a>
        <?php if ($page == 'Edit'): ?>
        <input type="submit" name="delete" value="Delete" class="btn red mar-right-2" onclick="return confirm('Are you sure you want to delete this record?')">
        <?php endif; ?>
        <input type="submit" name="submit" value="Save" class="btn">
    </div>
 
    <div class="content-block">
        <div class="form responsive-width-100">
 
            <!-- Row 1: Domain Name + Account ID -->
            <div class="form-row">
                <div class="form-group">
                    <label for="domain">Domain Name</label>
                    <input type="text" name="domain" id="domain" placeholder="example.com" value="<?=htmlspecialchars($record['domain']??'', ENT_QUOTES)?>" required>
                </div>
                
                <div class="form-group">
                    <label for="account_id">Account</label>
                    <select name="account_id" id="account_id" required> 
                        <option value="">Select Account</option>
                        <?php foreach($accounts as $row): ?>
                            <?php $selected = ($record['account_id'] == $row['id']) ? 'selected' : ''; ?>
                            <option value="<?=$row['id']?>" <?=$selected?>><?=$row['full_name']?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Row 2: Status + Renewal Date + Renewal Fee -->
            <div class="form-row-3">
                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" id="status" required>
                        <option value="Active" <?=($record['status']??'Active') == 'Active' ? 'selected' : ''?>>Active</option>
                        <option value="Inactive" <?=($record['status']??'') == 'Inactive' ? 'selected' : ''?>>Inactive</option>
                        <option value="Expired" <?=($record['status']??'') == 'Expired' ? 'selected' : ''?>>Expired</option>
                        <option value="Cancelled" <?=($record['status']??'') == 'Cancelled' ? 'selected' : ''?>>Cancelled</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="due_date">Renewal Date</label>
                    <?php if (isset($_GET['id'])): ?>
                        <input type="datetime-local" name="due_date" id="due_date" value="<?=$record['due_date']?>" required> 
                    <?php else: ?>
                        <input type="datetime-local" name="due_date" id="due_date" value="<?=date('Y-m-d\TH:i', strtotime('+1 year'))?>" required>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="amount">Renewal Fee</label>
                    <input type="number" step="0.01" name="amount" id="amount" placeholder="25.00" value="<?=htmlspecialchars($record['amount']??'25.00', ENT_QUOTES)?>" required>
                </div>
            </div>

            <!-- Row 3: Host URL + Host Login + Host Password -->
            <div class="form-row-3">
                <div class="form-group">
                    <label for="host_url">Host URL</label>
                    <input type="text" name="host_url" id="host_url" placeholder="https://registrar.com" value="<?=htmlspecialchars($record['host_url']??'', ENT_QUOTES)?>">
                </div>
                
                <div class="form-group">
                    <label for="host_login">Host Login</label>
                    <input type="text" name="host_login" id="host_login" placeholder="username" value="<?=htmlspecialchars($record['host_login']??'', ENT_QUOTES)?>">
                </div>
                
                <div class="form-group">
                    <label for="host_password">Host Password</label>
                    <input type="text" name="host_password" id="host_password" placeholder="password" value="<?=htmlspecialchars($record['host_password']??'', ENT_QUOTES)?>">
                </div>
            </div>

            <!-- Row 4: Notes (full width) -->
            <div class="form-group">
                <label for="notes">Notes</label>
                <input type="text" name="notes" id="notes" placeholder="Additional notes..." value="<?=htmlspecialchars($record['notes']??'', ENT_QUOTES)?>">
            </div>

        </div>
    </div>

    <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
        <a href="domains.php" class="btn alt mar-right-2">Cancel</a>
        <?php if ($page == 'Edit'): ?>
        <input type="submit" name="delete" value="Delete" class="btn red mar-right-2" onclick="return confirm('Are you sure you want to delete this record?')">
        <?php endif; ?>
        <input type="submit" name="submit" value="Save" class="btn">
    </div>
</form>
<script src="assets/js/resource-system-script.js"></script>
<?=template_admin_footer()?>