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
<?=template_admin_header($page . ' Financial Instututions', 'resources', 'cards')?>
<div class="content-title">
    <div class="title">
      <i class="fa-solid fa-user-secret"></i>
        <div class="txt">
             <h2 class="responsive-width-100"><?=$page?>&nbsp;<?=htmlspecialchars($record['description'], ENT_QUOTES)?></h2>
             <p>Online Financial Data</p>
            
        </div>
    </div>
</div>

<form action="" method="post">

    <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
        <a href="financial-institutions.php" class="btn alt mar-right-2">Cancel</a>
        <?php if ($page == 'Edit'): ?>
        <input type="submit" name="delete" value="Delete" class="btn red mar-right-2" onclick="return confirm('Are you sure you want to delete this record?')">
        <?php endif; ?>
        <input type="submit" name="submit" value="Save" class="btn">
    </div>

    <div class="content-block">

        <div class="form responsive-width-100">
        
            <label for="bank"><i class="required">*</i>Bank</label>
            <input id="bank" type="text" name="bank" placeholder="Bank" value="<?=htmlspecialchars($record['bank']??'', ENT_QUOTES)?>" required>
            
            <label for="account_number"><i class="required">*</i>Account #</label>
            <input id="account_number" type="text" name="account_number" placeholder="" value="<?=htmlspecialchars($record['account_number']??'', ENT_QUOTES)?>" required>
            
            <label for="routing_number"><i class="required">*</i>Routing</label>
            <input id="routing_number" type="text" name="routing_number" placeholder="" value="<?=htmlspecialchars($record['routing_number']??'', ENT_QUOTES)?>" required>
            
             <label for="brand">Brand</label>
                <select name="brand" id="brand">
                    <option value="Visa"<?=$record['brand']=='Visa'?' selected':''?>>Visa</option>
                    <option value="MasterCard"<?=$record['brand']=='MasterCard'?' selected':''?>>MasterCard</option>
                    <option value="Discover"<?=$record['brand']=='Discover'?' selected':''?>>Discover</option>
                    <option value="Amex"<?=$record['brand']=='Amex'?' selected':''?>>Amex</option>
                    <option value="Home Depot"<?=$record['brand']=='Home Depot'?' selected':''?>>Home Depot</option>
                    <option value="Other"<?=$record['brand']=='Other'?' selected':''?>>Other</option>
                </select>
            
            <label for="description"><i class="required">*</i>Description</label>
            <input id="description" type="text" name="description" placeholder="" value="<?=htmlspecialchars($record['description']??'', ENT_QUOTES)?>" required>
            
            <label for="name"><i class="required">*</i>Name</label>
            <input id="name" type="text" name="name" placeholder="Name on card" value="<?=htmlspecialchars($record['name']??'', ENT_QUOTES)?>" required>

               <label for="card_type">Card Type</label>
                <select name="card_type" id="card_type">
                    <option value="credit"<?=$record['card_type']=='credit'?' selected':''?>>Credit</option>
                    <option value="debit-checking"<?=$record['card_type']=='debit-checking'?' selected':''?>>Debit Checking</option>
                    <option value="debit-savings"<?=$record['card_type']=='debit-savings'?' selected':''?>>Debit Savings</option>
                    <option value="gift"<?=$record['card_type']=='gift'?' selected':''?>>Gift Card</option>
                </select>
                
            <label for="card_number"><i class="required">*</i>Card Number</label>
            <input id="card_number" type="text" name="card_number" placeholder="#### #### #### ####" value="<?=htmlspecialchars($record['card_number']??'', ENT_QUOTES)?>" required>

            <label for="expires"><i class="required">*</i> Expires</label>
            <input id="expires" type="text" name="expires" placeholder="XX/XX" value="<?=htmlspecialchars($record['expires']??'', ENT_QUOTES)?>" required>
    
            <label for="code"> Code</label>
            <input id="code" type="text" name="code" placeholder="XXX" value="<?=htmlspecialchars($record['code']??'', ENT_QUOTES)?>">
            
            <label for="zip_code"> ZipCode</label>
            <input id="zip_code" type="text" name="zip_code" placeholder="XXXXX" value="<?=htmlspecialchars($record['zip_code']??'', ENT_QUOTES)?>">

                 <label for="filepath"><i class="required">*</i>Filepath</label>
            <input id="filepath" type="text" name="filepath" placeholder="Img" value="<?=htmlspecialchars($record['filepath']??'', ENT_QUOTES)?>" required>
                
        </div>

    </div>

</form> 
<script src="assets/js/resource-system-script.js"></script>
<?=template_admin_footer()?>