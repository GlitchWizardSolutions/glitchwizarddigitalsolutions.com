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
    'description'=> '',
    'filepath' => '',
    'name' => 'Barbara Moore',
    'card_number' => '',
    'expires'  => '',
    'code' => '',
    'brand'  => '',
    'zip_code' => '32327',
    'card_type' => ''
];
// Retrieve records from the database
$records = $onthego_db->query('SELECT * FROM financial_cards')->fetchAll(PDO::FETCH_ASSOC);
// Check whether the record ID is specified
if (isset($_GET['id'])) {
    // Retrieve the record from the database
    $stmt = $onthego_db->prepare('SELECT * FROM financial_cards WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    // ID param exists, edit an existing record
    $page = 'Edit';
    if (isset($_POST['submit'])) {
                $stmt = $onthego_db->prepare('UPDATE financial_cards SET bank = ?, account_number = ?, routing_number = ?, brand = ?, description = ?, name = ?, card_type = ?, card_number = ?, expires = ?, code = ?, zip_code = ?, filepath = ?,   WHERE id = ?');
                $stmt->execute([ $_POST['bank'],$_POST['account_number'],$_POST['routing_number'],$_POST['brand'], $_POST['description'], $_POST['name'], $_POST['card_type'], $_POST['card_number'], $_POST['expires'], $_POST['code'], $_POST['zip_code'], $_POST['filepath'], $_GET['id'] ]);
                header('Location: financial-institutions.php?success_msg=2');
                exit;
            
       
    }
    if (isset($_POST['delete'])) {
        // Redirect and delete the record
        header('Location: financial-institutions.php?delete=' . $_GET['id']);
        exit;
    }

} else {
    // Create a new record
    $page = 'Create';
        if (isset($_POST['submit'])) {
                $stmt = $onthego_db->prepare('INSERT INTO financial_cards (bank, account_number, routing_number, brand, description, name, card_type, card_number, expires, code, zip_code, filepath) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([ $_POST['bank'], $_POST['account_number'], $_POST['routing_number'], $_POST['brand'], $_POST['description'], $_POST['name'], $_POST['card_type'], $_POST['card_number'], $_POST['expires'],  $_POST['code'], $_POST['zip_code'], $_POST['filepath'] ]);
                header('Location: financial-institutions.php?success_msg=1');
                exit;
            }
}
?>
<?=template_admin_header($page . ' Financial', 'resources', 'cards')?>

<?=generate_breadcrumbs([
    ['label' => 'Resource System', 'url' => 'index.php'],
    ['label' => 'Financial Institutions', 'url' => 'financial-institutions.php'],
    ['label' => $page . ' Record']
])?>

<div class="content-title mb-3">
    <div class="title">
       <i class="fa-solid fa-credit-card"></i>
        <div class="txt">
             <h2 class="responsive-width-100"><?=$page?> Financial Record</h2>
             <p>Bank and card information</p>
        </div>
    </div>
</div>

<form action="" method="post">
    <div class="form-professional">
        <div class="form-section">
            <h3 class="section-title">Financial Information</h3>

            <!-- Row 1: Bank + Account + Routing -->
            <div class="form-row">
                <div class="form-group">
                    <label for="bank">Bank <span class="required">*</span></label>
                    <input type="text" name="bank" id="bank" placeholder="Bank Name" value="<?=htmlspecialchars($record['bank']??'', ENT_QUOTES)?>" required>
                </div>
                
                <div class="form-group">
                    <label for="account_number">Account # <span class="required">*</span></label>
                    <input type="text" name="account_number" id="account_number" placeholder="Account Number" value="<?=htmlspecialchars($record['account_number']??'', ENT_QUOTES)?>" required>
                </div>
                
                <div class="form-group">
                    <label for="routing_number">Routing <span class="required">*</span></label>
                    <input type="text" name="routing_number" id="routing_number" placeholder="Routing Number" value="<?=htmlspecialchars($record['routing_number']??'', ENT_QUOTES)?>" required>
                </div>
            </div>

            <!-- Row 2: Brand + Card Type + Filepath -->
            <div class="form-row">
                <div class="form-group">
                    <label for="brand">Brand</label>
                    <select name="brand" id="brand">
                        <option value="">Select...</option>
                        <option value="Visa" <?=($record['brand']??'') == 'Visa' ? 'selected' : ''?>>Visa</option>
                        <option value="MasterCard" <?=($record['brand']??'') == 'MasterCard' ? 'selected' : ''?>>MasterCard</option>
                        <option value="Discover" <?=($record['brand']??'') == 'Discover' ? 'selected' : ''?>>Discover</option>
                        <option value="Amex" <?=($record['brand']??'') == 'Amex' ? 'selected' : ''?>>Amex</option>
                        <option value="Home Depot" <?=($record['brand']??'') == 'Home Depot' ? 'selected' : ''?>>Home Depot</option>
                        <option value="Other" <?=($record['brand']??'') == 'Other' ? 'selected' : ''?>>Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="card_type">Card Type</label>
                    <select name="card_type" id="card_type">
                        <option value="">Select...</option>
                        <option value="credit" <?=($record['card_type']??'') == 'credit' ? 'selected' : ''?>>Credit</option>
                        <option value="debit-checking" <?=($record['card_type']??'') == 'debit-checking' ? 'selected' : ''?>>Debit Checking</option>
                        <option value="debit-savings" <?=($record['card_type']??'') == 'debit-savings' ? 'selected' : ''?>>Debit Savings</option>
                        <option value="gift" <?=($record['card_type']??'') == 'gift' ? 'selected' : ''?>>Gift Card</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="filepath">Filepath <span class="required">*</span></label>
                    <input type="text" name="filepath" id="filepath" placeholder="Image Path" value="<?=htmlspecialchars($record['filepath']??'', ENT_QUOTES)?>" required>
                </div>
            </div>

            <!-- Row 3: Name + Description (2/3 width) -->
            <div class="form-row-2-1">
                <div class="form-group">
                    <label for="name">Name <span class="required">*</span></label>
                    <input type="text" name="name" id="name" placeholder="Name on Card" value="<?=htmlspecialchars($record['name']??'Barbara Moore', ENT_QUOTES)?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description <span class="required">*</span></label>
                    <input type="text" name="description" id="description" placeholder="Card Description" value="<?=htmlspecialchars($record['description']??'', ENT_QUOTES)?>" required>
                </div>
            </div>

            <!-- Row 4: Card Number + Expires + Code -->
            <div class="form-row">
                <div class="form-group">
                    <label for="card_number">Card Number <span class="required">*</span></label>
                    <input type="text" name="card_number" id="card_number" placeholder="#### #### #### ####" value="<?=htmlspecialchars($record['card_number']??'', ENT_QUOTES)?>" required>
                </div>
                
                <div class="form-group">
                    <label for="expires">Expires <span class="required">*</span></label>
                    <input type="text" name="expires" id="expires" placeholder="MM/YY" value="<?=htmlspecialchars($record['expires']??'', ENT_QUOTES)?>" required>
                </div>
                
                <div class="form-group">
                    <label for="code">Code</label>
                    <input type="text" name="code" id="code" placeholder="CVV" value="<?=htmlspecialchars($record['code']??'', ENT_QUOTES)?>">
                </div>
            </div>

            <!-- Row 5: Zip Code (single field) -->
            <div class="form-group">
                <label for="zip_code">Zip Code</label>
                <input type="text" name="zip_code" id="zip_code" placeholder="ZIP Code" value="<?=htmlspecialchars($record['zip_code']??'32327', ENT_QUOTES)?>">
            </div>

        </div>
        <div class="form-actions">
            <a href="financial-institutions.php" class="btn btn-secondary">Cancel</a>
            <?php if ($page == 'Edit'): ?>
            <input type="submit" name="delete" value="Delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this record?')">
            <?php endif; ?>
            <input type="submit" name="submit" value="Save" class="btn btn-success">
        </div>
    </div>
</form> 
<script src="assets/js/resource-system-script.js"></script>
<?=template_admin_footer()?>