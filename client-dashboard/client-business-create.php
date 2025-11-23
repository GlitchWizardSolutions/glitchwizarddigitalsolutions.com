<?php  
include 'assets/includes/user-config.php';
//include process_path . 'users-profile-process.php';
// output message (errors, etc)
$msg = '';

// Retrieve additional account info from the database because we don't have them stored in sessions.
$stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
// In this case, we can use the account ID to retrieve the account info.
$stmt->execute([ $_SESSION['id'] ]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);
 
// FORM SUBMITTED
if (isset($_POST['business_name'], $_POST['business_email'], $_POST['first_name'], $_POST['last_name'])) {
	// Make sure the submitted values are not empty.
	if (empty($_POST['business_name']) || empty($_POST['business_email']) || empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['last_name']) || empty($_POST['last_name']) || empty($_POST['last_name'])) {
		$msg = 'The input fields must not be empty!';
	} else if (!filter_var($_POST['business_email'], FILTER_VALIDATE_EMAIL)) {
		$msg = 'Please provide a valid email address!';
	}else if (!preg_match('/^[a-zA-Z0-9 ]+$/', $_POST['first_name'])) {
	    $msg = 'Name must not have special characters';
	} 
		// Check if business already exists in the database
	    	 $stmt = $pdo->prepare('SELECT COUNT(*) FROM invoice_clients WHERE acc_id = ? && business_name = ?');
		     $stmt->execute([ $account['id'], $_POST['business_name']]);
		// Account exists?  
        	if ($result = $stmt->fetchColumn() > 0) {
        	     $stmt = $pdo->prepare('SELECT id FROM invoice_clients WHERE acc_id = ? && business_name = ?');
		         $stmt->execute([ $account['id'], $_POST['business_name']]);
        	     $invoice_client_id = $stmt->fetch(PDO::FETCH_ASSOC);  
            	// Update the session variables
			     $_SESSION['invoice_client_id'] = $invoice_client_id['id'];
                 $msg = "This business name already exists on your account. &nbsp;<a href='client-business-edit.php?business_id=" . $invoice_client_id['id'] . "'>Edit Here</a>";  
        	}
	// No validation errors... Process update
	if (empty($msg)) {
			// Insert New record
	        // Prepare query; prevents SQL injection
	        $stmt = $pdo->prepare('INSERT INTO invoice_clients (
	        acc_id, 
	        business_name, 
	        description, 
	        bluesky,
	        facebook, 
	        instagram, 
	        x, 
	        linkedin, 
	        first_name, 
	        last_name, 
	        email, 
	        phone, 
	        address_street, 
        address_city, 
        address_state, 
        address_zip, 
        address_country,
        incomplete,
        issue) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute(
            [ $_SESSION['id'], $_POST['business_name'], $_POST['description'], 
            $_POST['bluesky'], $_POST['facebook'], $_POST['instagram'], $_POST['x'], 
            $_POST['linkedin'], $_POST['first_name'], $_POST['last_name'], 
            $_POST['business_email'], $_POST['business_phone'], 
            $_POST['business_address_street'], $_POST['business_address_city'], 
            $_POST['business_address_state'], $_POST['business_address_zip'], 
            $_POST['business_address_country'],
            'No',  // incomplete = No (profile is complete)
            'No'   // issue = No (no validation issues)
            ]);
		// Insert Sucessful
		    header('Location: client-businesses.php');
			exit;
	 }// end insert */
    }
//END FORM SUBMITTED
 
include includes_path . 'page-setup.php';
?>
<main id="main" class="main">
    <div class="pagetitle">
      <nav>
        <ol class="breadcrumb">
         <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>/index.php">&nbsp;Home</a></li>
         <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>/users-profile-edit.php">My Account</a></li> 
         <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>/client-businesses.php">My Businesses</a></li> 
         <li class="breadcrumb-item active">Create Business Profile</li>
        </ol>
      </nav>
    </div>
<section class="section profile">
  <div class="col-xl-12 col-lg-12 col-sm-12">
    <div class="card">
     <div class="card-body pt-3">
      <div class="tab-content pt-2">
       <div class="tab-pane fade show active profile-overview" id="profile-overview">
  <h5 class="card-title">New Business Profile</h5>
  <p>This is the information we use in our invoicing system, as well as on your website.</p>
             <div class="col-lg-12">
               <div class="card">
                <div class="card-body">
                  <form action="" id='business-edit-form' class='form' method="post">
                    <div class="text-center"><strong><p style='color:red'><?=$msg?></p></strong></div>
                    <div class="row">
                      <label for="business_name" class="col-lg-4 col-md-4 label">Business Name</label>
                      <div class="col-lg-8 col-md-8">
                        <input name="business_name" type="text" class="form-control" id="business_name" autocomplete="off" value="<?=htmlspecialchars($invoice_clients['business_name'] ?? '', ENT_QUOTES)?>" required>
                      </div>
                    </div>
                    <div class="row">
                      <label for="description" class="col-lg-4 col-md-4 label">Short Description</label>
                    </div>
                    <div class="row">
                      <div class="col-lg-12 col-md-12">
                        <input name="description" class="form-control" style="border:.25px solid grey" id="description" autocomplete="off" value="<?=htmlspecialchars($invoice_clients['description'] ?? '', ENT_QUOTES)?>" required>
                      </div>
                    </div>
                   
                    <div class="row">
                      <label for="first_name" class="col-lg-4 col-md-4 label">First Name (Invoices)</label>
                      <div class="col-lg-8 col-md-8">
                        <input name="first_name" type="text" class="form-control" id="first_name" autocomplete="off" value="<?=htmlspecialchars($invoice_clients['first_name'] ?? '', ENT_QUOTES)?>" required>
                      </div>
                    </div>
                   <div class="row">
                      <label for="last_name" class="col-lg-4 col-md-4 label">Last Name (Invoices)</label>
                      <div class="col-lg-8 col-md-8">
                        <input name="last_name" type="text" class="form-control" id="last_name" autocomplete="off" value="<?=htmlspecialchars($invoice_clients['last_name'] ?? '', ENT_QUOTES)?>" required>
                      </div>
                    </div>
                   <div class="row">
                      <label for="business_phone" class="col-lg-4 col-md-4 label">Business Phone</label>
                      <div class="col-lg-8 col-md-8">
                        <input name="business_phone" type="text" class="form-control" id="business_phone"  autocomplete="off" value="<?=htmlspecialchars($invoice_clients['phone'] ?? '', ENT_QUOTES)?>" required>
                      </div>
                    </div>

                   <div class="row">
                      <label for="business_email" class="col-lg-4 col-md-4 label">Business Email</label>
                      <div class="col-lg-8 col-md-8">
                        <input name="business_email" type="email" class="form-control" id="business_email"  autocomplete="off"  value="<?=htmlspecialchars($invoice_clients['email'] ?? '', ENT_QUOTES)?>" required>
                      </div>
                    </div> 
                    

                   <div class="row">
                      <label for="address_street" class="col-lg-4 col-md-4 label">Business Address</label>
                      <div class="col-lg-8 col-md-8">
                        <input name="business_address_street" type="text" class="form-control" id="business_address_street" value="<?=htmlspecialchars($invoice_clients['address_street'] ?? '', ENT_QUOTES)?>" autocomplete="off"> 
                      </div>
                    </div>
                    
                  <div class="row">
                      <label for="business_address_city" class="col-lg-4 col-md-4 label">City</label>
                      <div class="col-lg-8 col-md-8">
                        <input name="business_address_city" type="text" class="form-control" id="business_address_city" value="<?=htmlspecialchars($invoice_clients['address_city'] ?? '', ENT_QUOTES)?>" autocomplete="off">
                      </div>
                    </div>
                    
                   <div class="row">
                      <label for="business_address_state" class="col-lg-4 col-md-4 label">State</label>
                      <div class="col-lg-8 col-md-8">
                        <input name="business_address_state" type="text" class="form-control" id="business_address_state" value="<?=htmlspecialchars($invoice_clients['address_state'] ?? '', ENT_QUOTES)?>"  autocomplete="off"> 
                      </div>
                    </div>
                    
                 <div class="row">
                      <label for="business_address_zip" class="col-lg-4 col-md-4 label">Zipcode</label>
                      <div class="col-lg-8 col-md-8">
                        <input name="business_address_zip" type="text" class="form-control" id="business_addresss_zip" value="<?=htmlspecialchars($invoice_clients['address_zip'] ?? '', ENT_QUOTES)?>" autocomplete="off"> 
                      </div>
                    </div>
                    
                   <div class="row">
                      <label for="business_address_country" class="col-lg-4 col-md-4 label">Country</label>
                      <div class="col-lg-8 col-md-8">
                        <input name="business_address_country" type="text" class="form-control" id="business_address_country" value="<?=htmlspecialchars($invoice_clients['address_country'] ?? '', ENT_QUOTES)?>" autocomplete="off"> 
                      </div>
                    </div>
                <div class="row">
                      <label for="bluesky" class="col-lg-4 col-md-4 label">BlueSky</label>
                      <div class="col-lg-8 col-md-8">
                        <input name="bluesky" type="text" class="form-control" id="bluesky" placeholder='bluesky.com/' value="<?=htmlspecialchars($invoice_clients['bluesky'] ?? '', ENT_QUOTES)?>" autocomplete="off">
                      </div>
                    </div>
                 

                    <div class="row">
                      <label for="facebook" class="col-lg-4 col-md-4 label">Facebook Profile</label>
                      <div class="col-lg-8 col-md-8">
                        <input name="facebook" type="text" class="form-control" id="facebook" placeholder="https://facebook.com/" value="<?=htmlspecialchars($invoice_clients['facebook'] ?? '', ENT_QUOTES)?>" autocomplete="off"> 
                      </div>
                    </div>

                   <div class="row">
                      <label for="instagram" class="col-lg-4 col-md-4 label">Instagram Profile</label>
                      <div class="col-lg-8 col-md-8">
                        <input name="instagram" type="text" class="form-control" id="instagram" placeholder="https://instagram.com/"  value="<?=htmlspecialchars($invoice_clients['instagram'] ?? '', ENT_QUOTES)?>" autocomplete="off">
                      </div>
                    </div>

                    <div class="row">
                      <label for="linkedin" class="col-lg-4 col-md-4 label">Linkedin Profile</label>
                      <div class="col-lg-8 col-md-8">
                        <input name="linkedin" type="text" class="form-control" id="linkedin"  placeholder="https://linkedin.com/in/" value="<?=htmlspecialchars($invoice_clients['linkedin'] ?? '', ENT_QUOTES)?>" autocomplete="off">
                      </div>
                    </div>
                     <div class="row">
                      <label for="x" class="col-lg-4 col-md-4 label">X Profile</label>
                      <div class="col-lg-8 col-md-8">
                        <input name="x" type="text" class="form-control" id="x" placeholder='https://x.com/'  value="<?=htmlspecialchars($invoice_clients['x'] ?? '', ENT_QUOTES)?>" autocomplete="off">
                      </div>
                    </div>
                   <div class="text-center"><strong><p style='color:red'><?=$msg?></p></strong>
			<div class="mar-bot-2">
			<button class="btn btn-success mar-top-1 mar-right-1" type="submit">Save Changes</button>
				<a href="index.php" class="btn alt mar-top-1">Cancel</a>
		    </div>
           </div>
          </form><!-- End Business Edit Form -->
         </div><!--/body-->
        </div><!--/card-->
       </div><!--/column-6-->
      </div><!--/row-->
     </div><!--/overview-->
    </div>
   </div>
  </div>
 </div>
</section>
</main><!-- End #main -->
<?php include includes_path . 'footer-close.php'; ?>