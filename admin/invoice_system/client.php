<?php
// 2024-12-09 Production.
// 2025-06-15 Reworked. VERIFIED.
include_once 'assets/includes/admin_config.php';
include_once '../assets/includes/components.php';
// Check if the user is logged-in
check_loggedin($pdo, '../../index.php');
// Default client values
$client = [
    'acc_id' => 0,
    'business_name' => 'none',
    'description' => 'none',
    'facebook' => 'https://facebook.com/#',
    'instagram' => 'https://instagram.com/#',
    'bluesky' => 'https://bluesky.com/#',
    'x' => 'https://twitter.com/#',
    'linkedin' => 'https://linkedin.com/#',
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'address_street' => '',
    'address_city' => '',
    'address_state' => '',
    'address_zip' => '',
    'address_country' => 'United States',
    'created' => date('Y-m-d\TH:i')
];
  // Retrieve the account list from the database
    $stmt = $pdo->prepare('SELECT * FROM accounts');
    $stmt->execute();
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Check if the ID param exists
if (isset($_GET['id'])) {
    // Retrieve the client from the database
    $stmt = $pdo->prepare('SELECT * FROM invoice_clients WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    // ID param exists, edit an existing client
    $page = 'Edit';
    if (isset($_POST['submit'])) {
        // Update the client
        
        // Validate if profile is incomplete (missing required contact info)
        $incomplete = "No";
        $issue = "No";
        
        if (empty($_POST['email']) || empty($_POST['phone']) || empty($_POST['address_street']) || empty($_POST['address_city']) || empty($_POST['address_state']) || empty($_POST['address_zip'])) {
            $incomplete = "Yes";
        }
        
        // Validate email format if provided
        if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $issue = "Yes";
        }
        
        $stmt = $pdo->prepare('UPDATE invoice_clients SET acc_id = ?, business_name = ?, description = ?,  facebook = ?, instagram = ?, bluesky = ?, x = ?, linkedin = ?, first_name = ?, last_name = ?,  email = ?, phone = ?, address_street = ?, address_city = ?, address_state = ?, address_zip = ?, address_country = ?, incomplete = ?, issue = ?, created = ? WHERE id = ?');
        $stmt->execute([ $_POST['acc_id'], $_POST['business_name'], $_POST['description'],  $_POST['facebook'], $_POST['instagram'], $_POST['bluesky'], $_POST['x'], $_POST['linkedin'], $_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['phone'], $_POST['address_street'], $_POST['address_city'], $_POST['address_state'], $_POST['address_zip'], $_POST['address_country'], $incomplete, $issue, $_POST['created'], $_GET['id'] ]);
        header('Location: clients.php?success_msg=2');
        exit;
    }
    if (isset($_POST['delete'])) {
        // Redirect and delete client
        header('Location: clients.php?delete=' . $_GET['id']);
        exit;
    }
} else {
    // Create a new client
    $page = 'Create';
    if (isset($_POST['submit'])) {
        
        // Validate if profile is incomplete (missing required contact info)
        $incomplete = "No";
        $issue = "No";
        
        if (empty($_POST['email']) || empty($_POST['phone']) || empty($_POST['address_street']) || empty($_POST['address_city']) || empty($_POST['address_state']) || empty($_POST['address_zip'])) {
            $incomplete = "Yes";
        }
        
        // Validate email format if provided
        if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $issue = "Yes";
        }
        
        $stmt = $pdo->prepare('INSERT INTO invoice_clients (acc_id, business_name, description,  facebook, instagram, bluesky, x, linkedin, first_name,last_name,email,phone,address_street,address_city,address_state,address_zip,address_country,incomplete,issue,created) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([ $_POST['acc_id'], $_POST['business_name'], $_POST['description'],  $_POST['facebook'], $_POST['instagram'], $_POST['bluesky'], $_POST['x'], $_POST['linkedin'], $_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['phone'], $_POST['address_street'], $_POST['address_city'], $_POST['address_state'], $_POST['address_zip'], $_POST['address_country'], $incomplete, $issue, $_POST['created'] ]);
        header('Location: clients.php?success_msg=1');
        exit;
    }
}
?>
<?=template_admin_header($page . ' Invoice Clients', 'invoices', 'clients')?>

<?=generate_breadcrumbs([
    ['label' => 'Clients', 'url' => 'clients.php'],
    ['label' => $page . ' Client']
])?>

<div class="content-title mb-3">
    <div class="title">
       <i class="fa-solid fa-user"></i>
        <div class="txt">
            <h2><?=$page?> Client</h2>
            <p>Manage invoice client information</p>
        </div>
    </div>
</div>

<form action="" method="post" class="form-professional">
        <a href="clients.php" class="btn btn-secondary">Cancel</a>
        <?php if ($page == 'Edit'): ?>
        <input type="submit" name="delete" value="Delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this client?')">
        <?php endif; ?>
        <input type="submit" name="submit" value="Save" class="btn btn-success">
    </div>

    <?php if (isset($error_msg)): ?>
    <div class="mar-top-4">
        <div class="msg error">
            <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zm0-384c13.3 0 24 10.7 24 24V264c0 13.3-10.7 24-24 24s-24-10.7-24-24V152c0-13.3 10.7-24 24-24zM224 352a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z"/></svg>
            <p><?=$error_msg?></p>
            <svg class="close" width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg>
        </div>
    </div>
    <?php endif; ?>

    <div class="content-block">
        <div class="form-section">
            <div class="section-title">Account & Contact Information</div>
        
            <div class="form responsive-width-100">
            
            <label for="acc_id"><span class="required">*</span> Account Number</label>
           <select id="acc_id" name="acc_id" required>
            <option value="0">Select the Account</option>
            <?php foreach ($accounts as $account): ?>
        
             <option value="<?=$account['id']?>"<?=$account['id']==$client['acc_id']?' selected':''?>><strong><?=$account['first_name']?>&nbsp;&nbsp;<?=$account['last_name']?></strong></option>
                <?php endforeach; ?>
            </select>
 
            <label for="email">Email</label>
            <input id="email" type="email" name="email" placeholder="Email" value="<?=htmlspecialchars($client['email'], ENT_QUOTES)?>">

            <label for="first_name">First Name</label>
            <input id="first_name" type="text" name="first_name" placeholder="First Name" value="<?=htmlspecialchars($client['first_name'], ENT_QUOTES)?>">

            <label for="last_name">Last Name</label>
            <input id="last_name" type="text" name="last_name" placeholder="Last Name" value="<?=htmlspecialchars($client['last_name'], ENT_QUOTES)?>">

            <label for="phone">Phone</label>
            <input id="phone" type="text" name="phone" placeholder="Phone" value="<?=htmlspecialchars($client['phone'], ENT_QUOTES)?>">
            
            <label for="business_name"><span class="required">*</span> Business Name</label>
            <input id="business_name" type="text" name="business_name" placeholder="Business Name" value="<?=htmlspecialchars($client['business_name'], ENT_QUOTES)?>" required>
            
            <label for="description">Description</label>
            <input id="description" type="text" name="description" placeholder="Description" value="<?=htmlspecialchars($client['description'], ENT_QUOTES)?>">
            
            <label for="facebook">Facebook</label>
            <input id="facebook" type="text" name="facebook" placeholder="Facebook" value="<?=htmlspecialchars($client['facebook'], ENT_QUOTES)?>">
            
            <label for="instagram">Instagram</label>
            <input id="instagram" type="text" name="instagram" placeholder="Instagram" value="<?=htmlspecialchars($client['instagram'], ENT_QUOTES)?>">
            
            <label for="bluesky">Bluesky</label>
            <input id="bluesky" type="text" name="bluesky" placeholder="Bluesky" value="<?=htmlspecialchars($client['bluesky'], ENT_QUOTES)?>">
             <label for="x">X</label>
            <input id="x" type="text" name="x" placeholder="X" value="<?=htmlspecialchars($client['x'], ENT_QUOTES)?>">
            
            <label for="linkedin">Linkedin</label>
            <input id="linkedin" type="text" name="linkedin" placeholder="Linkedin" value="<?=htmlspecialchars($client['linkedin'], ENT_QUOTES)?>">

            <label for="address_street">Address</label>
            <input id="address_street" type="text" name="address_street" placeholder="Street" value="<?=htmlspecialchars($client['address_street'], ENT_QUOTES)?>">

            <label for="address_city">City</label>
            <input id="address_city" type="text" name="address_city" placeholder="City" value="<?=htmlspecialchars($client['address_city'], ENT_QUOTES)?>">

            <label for="address_state">State</label>
            <input id="address_state" type="text" name="address_state" placeholder="State" value="<?=htmlspecialchars($client['address_state'], ENT_QUOTES)?>">

            <label for="address_zip">Zip</label>
            <input id="address_zip" type="text" name="address_zip" placeholder="Zip" value="<?=htmlspecialchars($client['address_zip'], ENT_QUOTES)?>">

            <label for="address_country">Country</label>
            <select id="address_country" name="address_country">
                <?php foreach(get_countries() as $country): ?>
                <option value="<?=$country?>"<?=$client['address_country']==$country?' selected':''?>><?=$country?></option>
                <?php endforeach; ?>
            </select>

            <label for="created"><span class="required">*</span> Created</label>
            <input id="created" type="datetime-local" name="created" value="<?=date('Y-m-d\TH:i', strtotime($client['created']))?>" required>

        </div>
    
    </div>

</form>

<?=template_admin_footer()?>