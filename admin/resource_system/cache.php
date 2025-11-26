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
    'description' => '',
    'url'  => '',
    'username' => '',
    'password'  => '',
    'type' => '',
    'notes'  => ''
];
/* cache table:
   id, description, url, username, password, type, notes, updated
   */

// Retrieve records from the database
$records =  $onthego_db->query('SELECT * FROM cache')->fetchAll(PDO::FETCH_ASSOC);
// Check whether the record ID is specified
if (isset($_GET['id'])) {
    // Retrieve the record from the database
    $stmt = $onthego_db->prepare('SELECT * FROM cache WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    // ID param exists, edit an existing record
    $page = 'Edit';
    if (isset($_POST['submit'])) {
                $stmt =  $onthego_db->prepare('UPDATE cache SET description = ?, url = ?, username = ?, password = ?, type = ?, notes = ?  WHERE id = ?');
                $stmt->execute([ $_POST['description'], $_POST['url'], $_POST['username'], $_POST['password'], $_POST['type'], $_POST['notes'], $_GET['id'] ]);
                header('Location: caches.php?success_msg=2');
                exit;
            
       
    }
    if (isset($_POST['delete'])) {
        // Redirect and delete the record
        header('Location: caches.php?delete=' . $_GET['id']);
        exit;
    }

} else {
    // Create a new record
    $page = 'Create';
        if (isset($_POST['submit'])) {
                $stmt = $onthego_db->prepare('INSERT INTO cache (description, url, username, password, type, notes) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([ $_POST['description'], $_POST['url'], $_POST['username'], $_POST['password'], $_POST['type'], $_POST['notes'] ]);
                header('Location: caches.php?success_msg=1');
                exit;
            }
}
?>
<?=template_admin_header($page . ' Cache of Things', 'resources', 'cache')?>

<?=generate_breadcrumbs([
    ['label' => 'Resource System', 'url' => 'index.php'],
    ['label' => 'Cache of Things', 'url' => 'caches.php'],
    ['label' => $page . ' Item']
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
    grid-template-columns: 1fr 1fr !important;
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
             <h2 class="responsive-width-100"><?=$page?> Cache of Things</h2>
             <p>Store miscellaneous credentials and links</p>
        </div>
    </div>
</div>

<form action="" method="post" class="form-professional">

    <div class="content-block">
        <div class="form responsive-width-100">

            <!-- Row 1: Type + URL -->
            <div class="form-row">
                <div class="form-group">
                    <label for="type">Type</label>
                    <input type="text" name="type" id="type" placeholder="Type" value="<?=htmlspecialchars($record['type']??'', ENT_QUOTES)?>">
                </div>
                
                <div class="form-group">
                    <label for="url">URL</label>
                    <input type="text" name="url" id="url" placeholder="https://example.com" value="<?=htmlspecialchars($record['url']??'', ENT_QUOTES)?>">
                </div>
            </div>

            <!-- Row 2: Description (full width) -->
            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" id="description" placeholder="Item description..." required><?=htmlspecialchars($record['description']??'', ENT_QUOTES)?></textarea>
            </div>

            <!-- Row 3: Username + Password -->
            <div class="form-row">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" name="username" id="username" placeholder="Username" value="<?=htmlspecialchars($record['username']??'', ENT_QUOTES)?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="text" name="password" id="password" placeholder="Password" value="<?=htmlspecialchars($record['password']??'', ENT_QUOTES)?>">
                </div>
            </div>

            <!-- Row 4: Notes (full width) -->
            <div class="form-group">
                <label for="notes">Notes</label>
                <textarea name="notes" id="notes" placeholder="Additional notes..."><?=htmlspecialchars($record['notes']??'', ENT_QUOTES)?></textarea>
            </div>

        </div>
    </div>

    <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
        <a href="caches.php" class="btn alt mar-right-2">Cancel</a>
        <?php if ($page == 'Edit'): ?>
        <input type="submit" name="delete" value="Delete" class="btn red mar-right-2" onclick="return confirm('Are you sure you want to delete this item?')">
        <?php endif; ?>
        <input type="submit" name="submit" value="Save" class="btn btn-success">
    </div>

</form>
<script src="assets/js/resource-system-script.js"></script>
<?=template_admin_footer()?>