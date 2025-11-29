<?php error_log('PAGE LOADED! : USERS-PROFILE-EDIT');
/*
2024-10-09 PRODUCTION  
2025-01-28 Bug Fix:   Re-Activation with Email Change in Profile Edit.
2025-06-18 Enhanced:  Users can have multiple profiles.
*/
include 'assets/includes/user-config.php';

if (isset($_GET['business_id'])) {
$business_id =  $_GET['business_id'];
}else{
$business_id = $_SESSION['invoice_client_id'];
}
// Error message variable
$error_msg = '';
// Check logged-in
check_loggedin($pdo);
// Retrieve additional account info from the database because we don't have them stored in sessions.
$stmt = $pdo->prepare("SELECT * FROM accounts WHERE id = ?");
$stmt->execute([ $_SESSION['id'] ]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

// Retrieve from invoice_clients
$stmt = $pdo->prepare('SELECT * FROM invoice_clients WHERE id = ?');
$stmt->execute([$business_id]);
$invoice_clients = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle edit business profile post data
if (isset($_POST['business_name'], $_POST['business_email'], $_POST['first_name'], $_POST['last_name'])) {
    // Validate CSRF token
    if (!validate_csrf_token()) {
        $error_msg = 'Security validation failed. Please try again.';
    } else {
        // Make sure the submitted values are not empty.
        if (empty($_POST['business_name']) || empty($_POST['business_email']) || empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['last_name']) || empty($_POST['last_name']) || empty($_POST['last_name'])) {
            $error_msg = 'The input fields must not be empty!';
        } else if (!filter_var($_POST['business_email'], FILTER_VALIDATE_EMAIL)) {
            $error_msg = 'Please provide a valid email address!';
        }else if (!preg_match('/^[a-zA-Z0-9 ]+$/', $_POST['first_name'])) {
	    $error_msg = 'Name must not have special characters';
	} 
	// No validation errors... Process update
	if (empty($error_msg)) {
	    //Check for duplicate businesses by business name.
		// Check if new username or email already exists in the database
		$stmt = $pdo->prepare("SELECT COUNT(*) FROM invoice_clients WHERE business_name = ? AND acc_id != ?");
		$stmt->execute([ $_POST['business_name'], $account['id']]);
		// Account exists? Output error...
		if ($stmt->fetchColumn() > 0) {
			$error_msg = 'There is already a business with that name, with a different account.';
		} else {
		    //no errors
		 	// Update the account
            $stmt = $pdo->prepare("UPDATE invoice_clients SET business_name = ?, description = ?, bluesky = ?, facebook = ?, instagram = ?, x = ?, linkedin = ?, email = ?, first_name = ?, last_name = ?, phone = ?, address_street = ?, address_city = ?, address_state = ?, address_zip = ?,  address_country = ?, incomplete = ?, issue = ?  WHERE id = ?");
			$stmt->execute([ $_POST['business_name'], $_POST['description'], $_POST['bluesky'], $_POST['facebook'],$_POST['instagram'], $_POST['x'],$_POST['linkedin'], $_POST['business_email'], $_POST['first_name'],$_POST['last_name'], $_POST['business_phone'], $_POST['business_address_street'], $_POST['business_address_city'], $_POST['business_address_state'], $_POST['business_address_zip'], $_POST['business_address_country'], "No", "No", $_GET['business_id']]);
				// Record updated successfully, redirect the user back to the profile page
			 header('Location: client-business-edit.php?business_id=' . $_GET['business_id']);
		     exit;
		}
    }
   }//end posted business information
}
include includes_path . 'page-setup.php';
?>
<main id="main" class="main">
    <div class="pagetitle">
      <nav>
        <ol class="breadcrumb">
         <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>/index.php">&nbsp;Home</a></li>
         <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>/users-profile-edit.php">My Account</a></li> 
         <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>/client-businesses.php">My Business Profiles</a></li> 
         <li class="breadcrumb-item active">My Business Profile for <?=htmlspecialchars($invoice_clients['business_name'] ??'', ENT_QUOTES)?></li>
        </ol>
      </nav>
    </div>
<section class="section profile">
  <div class="col-xl-12 col-lg-12 col-sm-12">
    <div class="card">
     <div class="card-body pt-3">
      <div class="tab-content pt-2">
       <div class="tab-pane fade show active profile-overview" id="profile-overview">
  <h5 class="card-title">Current Active Project Profile</h5>
  <p>This is the information we use in our invoicing system, as well as on your website.</p>
         <div class="row">
          <div class="col-lg-6">
            <div class="card">
             <div class="card-body">
              <h6 class="card-title">Information We Have</h6>
            <div class="row">
                    <div class="col-lg-4 col-md-4 label">Business Name</div>
                    <div class="col-lg-8 col-md-8"><?=htmlspecialchars($invoice_clients['business_name'] ??'', ENT_QUOTES)?></div> 
           </div><!--/row-->
         
           <div class="row">
                    <span class="col-lg-4 col-md-4 label">Short Description</span>
                    </div>
                    <div class="row">
                      <div class="col-lg-11 col-md-11 form-control" style="height:100px;">
                    <?=htmlspecialchars($invoice_clients['description'] ?? '', ENT_QUOTES)?></div> 
           </div><!--/row-->
            
            <div class="row">
                    <div class="col-lg-4 col-md-4 label">Contact Name</div>
                    <div class="col-lg-8 col-md-8"><?=htmlspecialchars($invoice_clients['first_name'] ??'', ENT_QUOTES)?>
                    &nbsp;<?=htmlspecialchars($invoice_clients['last_name'] ?? '', ENT_QUOTES)?></div>
           </div><!--/row-->
            <div class="row">
                    <div class="col-lg-4 col-md-4 label">Business Phone</div>
                    <div class="col-lg-8 col-md-8"><?=htmlspecialchars($invoice_clients['phone'] ?? '', ENT_QUOTES)?></div>
                  </div><!--/row-->

                  <div class="row">
                    <div class="col-lg-4 col-md-4 label">Business Email</div>
                    <div class="col-lg-8 col-md-8"><?=htmlspecialchars($invoice_clients['email'] ?? '', ENT_QUOTES)?></div>
                  </div><!--/row-->

                  <div class="row">
                    <div class="col-lg-4 col-md-4 label">Business Address</div>
                    <div class="col-lg-8 col-md-8"><?=htmlspecialchars($invoice_clients['address_street'] ?? '', ENT_QUOTES)?><br>
                    <?=htmlspecialchars($invoice_clients['address_city'] ?? '', ENT_QUOTES)?>  <?=htmlspecialchars($invoice_clients['address_state'] ?? '', ENT_QUOTES)?>  <?=htmlspecialchars($invoice_clients['address_zip'] ?? '', ENT_QUOTES)?><br>
                    <?=htmlspecialchars($invoice_clients['address_country'] ?? '', ENT_QUOTES)?></div>
                </div><!--/row-->
                    <div class="row">
                      <div class="col-lg-4 col-md-4 label">BlueSky</div>
                      <div class="col-lg-8 col-md-8"><?=htmlspecialchars($invoice_clients['bluesky'] ?? '', ENT_QUOTES)?></div>
                    </div><!--/row-->
                   
                    <div class="row">
                      <div class="col-lg-4 col-md-4 label">Facebook</div>
                      <div class="col-lg-8 col-md-8"><?=htmlspecialchars($invoice_clients['facebook'] ?? '', ENT_QUOTES)?></div>
                    </div><!--/row-->
                    <div class="row">
                      <div class="col-lg-4 col-md-4 label">Instagram</div>
                      <div class="col-lg-8 col-md-8"><?=htmlspecialchars($invoice_clients['instagram'] ?? '', ENT_QUOTES)?></div>
                    </div><!--/row-->
                    <div class="row">
                      <div class="col-lg-4 col-md-4 label">LinkedIn</div>
                      <div class="col-lg-8 col-md-8"><?=htmlspecialchars($invoice_clients['linkedin'] ?? '', ENT_QUOTES)?></div>
                  </div><!--/row-->
                   <div class="row">
                      <div class="col-lg-4 col-md-4 label">X (Twitter)</div>
                      <div class="col-lg-8 col-md-8"><?=htmlspecialchars($invoice_clients['x'] ?? '', ENT_QUOTES)?></div>
                    </div><!--/row-->
                </div><!--/body-->
               </div><!--/card-->
              </div><!--/column-6-->
             <div class="col-lg-6">
               <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Update Business Profile Here</h6>
                  <form action="" id='business-edit-form' class='form' method="post">
                    <div class="text-center"><strong><p style='color:red'><?=$error_msg?></p></strong></div>
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
                        <input name="bluesky" type="text" class="form-control" id="bluesky" value="<?=htmlspecialchars($invoice_clients['bluesky'] ?? '', ENT_QUOTES)?>" autocomplete="off">
                      </div>
                    </div>
                 

                    <div class="row">
                      <label for="facebook" class="col-lg-4 col-md-4 label">Facebook Profile</label>
                      <div class="col-lg-8 col-md-8">
                        <input name="facebook" type="text" class="form-control" id="facebook" value="<?=htmlspecialchars($invoice_clients['facebook'] ?? '', ENT_QUOTES)?>" autocomplete="off"> 
                      </div>
                    </div>

                   <div class="row">
                      <label for="instagram" class="col-lg-4 col-md-4 label">Instagram Profile</label>
                      <div class="col-lg-8 col-md-8">
                        <input name="instagram" type="text" class="form-control" id="instagram" value="<?=htmlspecialchars($invoice_clients['instagram'] ?? '', ENT_QUOTES)?>" autocomplete="off">
                      </div>
                    </div>

                    <div class="row">
                      <label for="linkedin" class="col-lg-4 col-md-4 label">Linkedin Profile</label>
                      <div class="col-lg-8 col-md-8">
                        <input name="linkedin" type="text" class="form-control" id="linkedin" value="<?=htmlspecialchars($invoice_clients['linkedin'] ?? '', ENT_QUOTES)?>" autocomplete="off">
                      </div>
                    </div>
                     <div class="row">
                      <label for="x" class="col-lg-4 col-md-4 label">X Profile</label>
                      <div class="col-lg-8 col-md-8">
                        <input name="x" type="text" class="form-control" id="x" value="<?=htmlspecialchars($invoice_clients['x'] ?? '', ENT_QUOTES)?>" autocomplete="off">
                      </div>
                    </div>
                   <div class="text-center"><strong><p style='color:red'><?=$error_msg?></p></strong>
			<div class="mar-bot-2">
			<?php csrf_token_field(); ?>
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