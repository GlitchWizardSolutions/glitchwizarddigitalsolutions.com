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

<div class="content-title mb-3">
    <div class="title">
       <i class="fa-solid fa-cloud"></i>
        <div class="txt">
             <h2 class="responsive-width-100"><?=$page?> SaaS Account</h2>
            <p>Online resource login credentials</p>
        </div>
    </div>
</div>

<form action="" method="post">
    <div class="form-professional">
        <div class="form-section">
            <h3 class="section-title">Account Information</h3>

            <!-- Row 1: Resource Name + URL -->
            <div class="form-row">
                <div class="form-group">
                    <label for="resource">Resource Name <span class="required">*</span></label>
                    <input type="text" name="resource" id="resource" placeholder="Resource Name" value="<?=htmlspecialchars($record['resource']??'', ENT_QUOTES)?>" required>
                </div>
                
                <div class="form-group">
                    <label for="url">URL <span class="required">*</span></label>
                    <input type="text" name="url" id="url" placeholder="https://example.com" value="<?=htmlspecialchars($record['url']??'', ENT_QUOTES)?>" required>
                </div>
            </div>

            <!-- Row 2: Details (full width) -->
            <div class="form-group">
                <label for="details">Details</label>
                <textarea name="details" id="details" placeholder="Add notes about this resource..." rows="4"><?=htmlspecialchars($record['details']??'', ENT_QUOTES)?></textarea>
            </div>

            <!-- Row 3: User ID + Password -->
            <div class="form-row">
                <div class="form-group">
                    <label for="userid">User ID <span class="required">*</span></label>
                    <input type="text" name="userid" id="userid" placeholder="Username" value="<?=htmlspecialchars($record['userid']??'', ENT_QUOTES)?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password <span class="required">*</span></label>
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
        <div class="form-actions">
            <a href="sass-accounts.php" class="btn btn-secondary">Cancel</a>
            <?php if ($page == 'Edit'): ?>
            <input type="submit" name="delete" value="Delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this record?')">
            <?php endif; ?>
            <input type="submit" name="submit" value="Save" class="btn btn-success">
        </div>
    </div>
</form>
<script src="assets/js/resource-system-script.js"></script>
<?=template_admin_footer()?>