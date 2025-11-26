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
// Connect to the On the Go Database using the PDO interface
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
// Default record values
$record = [
    'resource' => '',
    'details' => '',
    'url'  => '',
    'userid' => '',
    'password'  => '',
    'email' => 'webdev@glitchwizardsolutions.com',
    'investment'  => 'none',
    'type'  => 'personal',
    'name' => 'Barbara Moore'
];
// Retrieve records from the database
$records = $onthego_db->query('SELECT * FROM sass_account')->fetchAll(PDO::FETCH_ASSOC);
// Check whether the record ID is specified
if (isset($_GET['id'])) {
    // Retrieve the record from the database
    $stmt = $onthego_db->prepare('SELECT * FROM sass_account WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    // ID param exists, edit an existing record
    $page = 'Edit';
    if (isset($_POST['submit'])) {
                $stmt = $onthego_db->prepare('UPDATE sass_account SET resource = ?, details = ?, url = ?, userid = ?, password = ?, email = ?, investment = ?, type = ?, name = ? WHERE id = ?');
                $stmt->execute([ $_POST['resource'], $_POST['details'], $_POST['url'], $_POST['userid'], $_POST['password'], $_POST['email'], $_POST['investment'], $_POST['type'], $_POST['name'], $_GET['id'] ]);
                header('Location: sass-accounts.php?success_msg=2');
                exit;
            
       
    }
    if (isset($_POST['delete'])) {
        // Redirect and delete the record
        header('Location: sass-accounts.php?delete=' . $_GET['id']);
        exit;
    }

} else {
    // Create a new record
    $page = 'Create';
        if (isset($_POST['submit'])) {
                $stmt = $onthego_db->prepare('INSERT INTO sass_account (resource, details, url, userid, password, email, investment, type, name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([ $_POST['resource'], $_POST['details'], $_POST['url'], $_POST['userid'], $_POST['password'], $_POST['email'], $_POST['investment'], $_POST['type'], $_POST['name'] ]);
                header('Location: sass-accounts.php?success_msg=1');
                exit;
            }
}
?>
<?=template_admin_header($page . ' SASS', 'resources', 'sass')?>

<?=generate_breadcrumbs([
    ['label' => 'Resource System', 'url' => 'index.php'],
    ['label' => 'SaaS Accounts', 'url' => 'sass-accounts.php'],
    ['label' => $page . ' Account']
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
.form-professional input[type="email"],
.form-professional select,
.form-professional textarea {
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

.form-professional textarea {
    resize: vertical;
    min-height: 100px;
    font-family: inherit;
    line-height: 1.6;
}

.form-professional input:focus,
.form-professional select:focus,
.form-professional textarea:focus {
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
.form-group select,
.form-group textarea {
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
       <i class="fa-solid fa-cloud"></i>
        <div class="txt">
             <h2 class="responsive-width-100"><?=$page?> SaaS Account</h2>
            <p>Online resource login credentials</p>
        </div>
    </div>
</div>

<form action="" method="post" class="form-professional">
    <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
        <a href="sass-accounts.php" class="btn alt mar-right-2">Cancel</a>
        <?php if ($page == 'Edit'): ?>
        <input type="submit" name="delete" value="Delete" class="btn red mar-right-2" onclick="return confirm('Are you sure you want to delete this record?')">
        <?php endif; ?>
        <input type="submit" name="submit" value="Save" class="btn">
    </div>

    <div class="content-block">
        <div class="form responsive-width-100">

            <!-- Row 1: Resource Name + URL -->
            <div class="form-row">
                <div class="form-group">
                    <label for="resource">Resource Name</label>
                    <input type="text" name="resource" id="resource" placeholder="Resource Name" value="<?=htmlspecialchars($record['resource']??'', ENT_QUOTES)?>" required>
                </div>
                
                <div class="form-group">
                    <label for="url">URL</label>
                    <input type="text" name="url" id="url" placeholder="https://example.com" value="<?=htmlspecialchars($record['url']??'', ENT_QUOTES)?>" required>
                </div>
            </div>

            <!-- Row 2: Details (full width) -->
            <div class="form-group">
                <label for="details">Details</label>
                <textarea name="details" id="details" placeholder="Add notes about this resource..."><?=htmlspecialchars($record['details']??'', ENT_QUOTES)?></textarea>
            </div>

            <!-- Row 3: User ID + Password -->
            <div class="form-row">
                <div class="form-group">
                    <label for="userid">User ID</label>
                    <input type="text" name="userid" id="userid" placeholder="Username" value="<?=htmlspecialchars($record['userid']??'', ENT_QUOTES)?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="text" name="password" id="password" placeholder="Password" value="<?=htmlspecialchars($record['password']??'', ENT_QUOTES)?>" required>
                </div>
            </div>

            <!-- Row 4: Name + Email -->
            <div class="form-row">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" name="name" id="name" placeholder="Name on File" value="<?=htmlspecialchars($record['name']??'Barbara Moore', ENT_QUOTES)?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" placeholder="Email on File" value="<?=htmlspecialchars($record['email']??'webdev@glitchwizardsolutions.com', ENT_QUOTES)?>">
                </div>
            </div>

            <!-- Row 5: Investment + Type -->
            <div class="form-row">
                <div class="form-group">
                    <label for="investment">Investment</label>
                    <select name="investment" id="investment">
                        <option value="none" <?=($record['investment']??'none') == 'none' ? 'selected' : ''?>>None</option>
                        <option value="lifetime" <?=($record['investment']??'') == 'lifetime' ? 'selected' : ''?>>Lifetime</option>
                        <option value="subscription" <?=($record['investment']??'') == 'subscription' ? 'selected' : ''?>>Subscription</option>
                        <option value="inactive" <?=($record['investment']??'') == 'inactive' ? 'selected' : ''?>>Inactive</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="type">Type</label>
                    <select name="type" id="type">
                        <option value="personal" <?=($record['type']??'personal') == 'personal' ? 'selected' : ''?>>Personal</option>
                        <option value="business" <?=($record['type']??'') == 'business' ? 'selected' : ''?>>Business</option>
                    </select>
                </div>
            </div>

        </div>
    </div>

    <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
        <a href="sass-accounts.php" class="btn alt mar-right-2">Cancel</a>
        <?php if ($page == 'Edit'): ?>
        <input type="submit" name="delete" value="Delete" class="btn red mar-right-2" onclick="return confirm('Are you sure you want to delete this record?')">
        <?php endif; ?>
        <input type="submit" name="submit" value="Save" class="btn btn-success">
    </div>
</form>
<script src="assets/js/resource-system-script.js"></script>
<?=template_admin_footer()?>