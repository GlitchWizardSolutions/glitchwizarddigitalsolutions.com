<?php error_log('PAGE LOADED! : USERS-PROFILE-EDIT');
/*
LOG NOTE: 2024-10-09 PRODUCTION  
          2025-01-28 Bug Fix:   Re-Activation with Email Change in Profile Edit.
*/
include 'assets/includes/user-config.php';
// Error message variable
$error_msg = '';
// Success message variable
$success_msg = '';
// Check logged-in
check_loggedin($pdo);
// Retrieve additional account info from the database because we don't have them stored in sessions.
$stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
$stmt->execute([ $_SESSION['id'] ]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

if (isset($_POST['username'], $_POST['email'], $_POST['first_name'])) {
	// Make sure the submitted values are not empty.
	if (empty($_POST['username']) || empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['email'])) {
		$error_msg = 'The input fields must not be empty!';
	} else if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
		$error_msg = 'Please provide a valid email address!';
	} else if (!preg_match('/^[a-zA-Z0-9]+$/', $_POST['username'])) {
	    $error_msg = 'Login User Name must contain only letters and numbers!';
	} //end of the data validation on form.
	
	// Handle avatar upload
	// Upload directory: /public_html/media/avatars/
	// Files are named using username (e.g., johnsmith.jpg)
	// Uploading a new avatar automatically overwrites the old one
	$avatar_path = $account['avatar']; // Keep existing avatar by default
	if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
		$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
		$max_size = 5 * 1024 * 1024; // 5MB
		
		if (!in_array($_FILES['avatar']['type'], $allowed_types)) {
			$error_msg = 'Avatar must be a JPG, PNG, GIF, or WEBP image.';
		} else if ($_FILES['avatar']['size'] > $max_size) {
			$error_msg = 'Avatar file size must be less than 5MB.';
		} else {
			// Create upload directory if it doesn't exist
			$upload_dir = '../media/avatars/';
			if (!is_dir($upload_dir)) {
				mkdir($upload_dir, 0755, true);
			}
			
			// Use username as filename (automatically overwrites previous uploads)
			$file_ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
			$filename = $account['username'] . '.' . $file_ext;
			$target_path = $upload_dir . $filename;
			
			// Delete old avatar if it exists and has a different extension
			if (!empty($account['avatar']) && file_exists('..' . $account['avatar'])) {
				$old_file = '..' . $account['avatar'];
				// Only delete if it's a different file (different extension)
				if ($old_file !== $target_path) {
					unlink($old_file);
				}
			}
			
			if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_path)) {
				$avatar_path = '/public_html/media/avatars/' . $filename;
			} else {
				$error_msg = 'Failed to upload avatar. Please try again.';
			}
		}
	}
	
	// No validation errors... Process update
	if (empty($error_msg)) {
		// Check if new username or email already exists in the database
		$stmt = $pdo->prepare('SELECT COUNT(*) FROM accounts WHERE (username = ? OR email = ?) AND username != ? AND email != ?');
		$stmt->execute([ $_POST['username'], $_POST['email'], $account['username'], $account['email'] ]);
		// Account exists? Output error...
		if ($stmt->fetchColumn() > 0) {
			$error_msg = 'Account already exists with that username and/or email!';
		} else {
		    //no errors
			// If email has changed, generate a new activation code
			$activation_code = account_activation && $account['email'] != $_POST['email'] ? hash('sha256', uniqid() . $_POST['email'] . secret_key) : $account['activation_code'];
			// Update the account	    
			$full_name=$_POST['first_name'] . ' ' .  $_POST['last_name'];
			error_log('VARIABLES SET: $full_name ' . $full_name);
            error_log('INPUT PARAMS: ' .  $_POST['username'] . ' ' . $_POST['email']. ' ' . $activation_code . ' '. $_POST['first_name']. ' ' . $_POST['last_name'] . ' '. $full_name . ' '. $_POST['phone']. ' ' .$_POST['address_street'] . ' '. $_POST['address_city'] . ' '. $_POST['address_state'] . ' '. $_POST['address_zip']. ' ' . $avatar_path . ' ' . $_SESSION['id'] );			
  	        $stmt = $pdo->prepare('UPDATE accounts SET username = ?,  email = ?, activation_code = ?, first_name = ?, last_name = ?, full_name = ?, phone = ?, address_street = ?, address_city = ?, address_state = ?, address_zip = ?, avatar = ? WHERE id = ?');
			$stmt->execute([ $_POST['username'], $_POST['email'], $activation_code, $_POST['first_name'], $_POST['last_name'], $full_name, $_POST['phone'], $_POST['address_street'], $_POST['address_city'], $_POST['address_state'], $_POST['address_zip'], $avatar_path, $_SESSION['id'] ]);
			// Update the session variables
			$_SESSION['name'] = $_POST['username'];
			$_SESSION['full_name'] = $full_name;
			$_SESSION['first_name'] = $_POST['first_name'];
			$_SESSION['last_name'] = $_POST['last_name'];
			// If email has changed, logout the user and send a new activation email
			if (account_activation && $account['email'] != $_POST['email']) {
				// Account activation required, send the user the activation email with the "send_activation_email" function
			    send_email($_POST['email'], $activation_code, $_POST['username'], 'activation'); 
				// Logout the user
				unset($_SESSION['loggedin']);
				$error_msg = 'You have changed your email address! <br> 
				We have sent you an email, check inbox/spam folder.
				<br>Click link in email to re-activate your account!';
			} else {
				// Profile updated successfully, redirect the user back to the profile page
			 	header('Location: users-profile-edit.php');
			    exit;
		}
	}
}
}
include includes_path . 'page-setup.php';
?>
<main id="main" class="main">
    <div class="pagetitle">
      <nav>
        <ol class="breadcrumb">
         <li class="breadcrumb-item"><a href="<?php echo(site_menu_base) ?>client-dashboard/index.php"?>&nbsp;Home</a></li>
         <li class="breadcrumb-item">My Account</li>
         <li class="breadcrumb-item active">My Profile</li>
        </ol>
      </nav>
    </div>
<section class="section profile">
  <div class="col-xl-12 col-lg-12 col-sm-12">
    <div class="card">
     <div class="card-body pt-3">
      <div class="tab-content pt-2">
       <div class="tab-pane fade show active profile-overview" id="profile-overview">
          <h5 class="card-title">My Profile</h5>
         <div class="row">
          <div class="col-lg-6">
            <div class="card">
             <div class="card-body">
                <h6 class="card-title">Current Profile</h6>
                  <div class="row">
                    <div class="col-lg-4 col-md-4 label">First Name</div>
                    <div class="col-lg-8 col-md-8"><?=htmlspecialchars($account['first_name']??'', ENT_QUOTES)?></div>
                  </div>   
                  <div class="row">
                    <div class="col-lg-4 col-md-4 label">Last Name</div>
                    <div class="col-lg-8 col-md-8"><?=htmlspecialchars($account['last_name']??'', ENT_QUOTES)?></div>
                  </div>
                  <div class="row">
                    <div class="col-lg-4 col-md-4 label">Login User Name</div>
                    <div class="col-lg-8 col-md-8"><?=htmlspecialchars($account['username'] ?? '', ENT_QUOTES)?></div>
                  </div>    
                  <div class="row">
                    <div class="col-lg-4 col-md-4 label">Best Email</div>
                    <div class="col-lg-8 col-md-8"><?=htmlspecialchars($account['email'] ?? '', ENT_QUOTES)?></div>
                   </div>
                   <div class="row">    
                    <div class="col-lg-4 col-md-4 label">Phone Number</div>
                    <div class="col-lg-8 col-md-8"><?=$account['phone']?></div>
                   </div>
                   <div class="row">
                    <div class="col-lg-4 col-md-4 label">Mailing Address</div>
                    <div class="col-lg-8 col-md-8"><?=htmlspecialchars($account['address_street'] ?? '', ENT_QUOTES)?><br>
                    <?=htmlspecialchars($account['address_city'] ?? '', ENT_QUOTES)?> , <?=htmlspecialchars($account['address_state'] ?? '', ENT_QUOTES)?>  <?=htmlspecialchars($account['address_zip'] ?? '', ENT_QUOTES)?><br>
                    </div>
                   </div>    
                   <div class="row">
                    <div class="col-lg-4 col-md-4 label">Registered</div>
                    <div class="col-lg-8 col-md-8"><?=date('F d, Y', strtotime($account['registered']))?></div>
                  </div><!--/row-->
                </div><!--/body-->
               </div><!--/card-->
              </div><!--/column-6-->
             <div class="col-lg-6">
               <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Update Profile Here</h6>
                  <form action="" id='profile-edit-form' class='form' method="post" autocomplete="on" enctype="multipart/form-data" novalidate>
                      <div class="text-center"><strong><p style='color:red'><?=$error_msg?></p></strong></div>
	     
                  <!-- Avatar Upload -->
                  <div class="row mb-3">
                      <label for="avatar" class="col-lg-4 col-md-4 label">Profile Picture</label>
                      <div class="col-lg-8 col-md-8">
                        <div class="mb-2">
                          <?php if (!empty($account['avatar'])): ?>
                            <img src="<?=htmlspecialchars($account['avatar'])?>" alt="Avatar" class="rounded-circle" width="100" height="100" style="object-fit: cover;">
                          <?php else: ?>
                            <img src="/public_html/assets/img/avatar.png" alt="Default Avatar" class="rounded-circle" width="100" height="100" style="object-fit: cover;">
                          <?php endif; ?>
                        </div>
                        <input name="avatar" type="file" class="form-control" id="avatar" accept="image/jpeg,image/png,image/gif,image/webp">
                        <small class="form-text text-muted">JPG, PNG, GIF, or WEBP. Max 5MB. Saved as: <strong><?=htmlspecialchars($account['username'])?>.ext</strong></small>
                      </div>
                  </div>
                  
                  <div class="row">
                      <label for="first_name" class="col-lg-4 col-md-4 label">First Name</label>
                      <div class="col-lg-8 col-md-8">
                        <input name="first_name" type="text" class="form-control" id="first_name" autocomplete="given-name" value="<?=htmlspecialchars($account['first_name'] ?? '', ENT_QUOTES)?>" required>
                      </div>
                  </div>
                  <div class="row">
                      <label for="last_name" class="col-lg-4 col-md-4 label">Last Name</label>
                      <div class="col-lg-8 col-md-8">
                        <input name="last_name" type="text" class="form-control" id="last_name" autocomplete="family-name" value="<?=htmlspecialchars($account['last_name'] ?? '', ENT_QUOTES)?>" required>
                      </div>
                  </div>
                  <div class="row">
                      <label for="username" class="col-lg-4 col-md-4 label">Login User Name </label>
                      <div class="col-lg-8 col-md-8">
                        <input name="username" type="text" class="form-control" id="username" autocomplete="on" value="<?=htmlspecialchars($account['username'] ?? '', ENT_QUOTES)?>" required>
                      </div>
                  </div>
                    <div class="row">
                      <label for="email" class="col-lg-4 col-md-4 label">Best Email</label>
                      <div class="col-lg-8 col-md-8">
                        <input name="email" type="email" class="form-control" id="email"  autocomplete="email"  value="<?=htmlspecialchars($account['email'] ?? '', ENT_QUOTES)?>" required>
                      </div>
                  </div> 
                  <div class="row">
                      <label for="phone" class="col-lg-4 col-md-4 label">Phone Number</label>
                      <div class="col-lg-8 col-md-8">
                        <input name="phone" type="tel" class="form-control" id="phone"  autocomplete="tel-national" value="<?=htmlspecialchars($account['phone'] ?? '', ENT_QUOTES)?>" required>
                      </div>
                  </div>
                  <div class="row">
                      <label for="address_street" class="col-lg-4 col-md-4 label">Mailing Address</label>
                      <div class="col-lg-8 col-md-8">
                        <input name="address_street" type="text" class="form-control" id="address_street" value="<?=htmlspecialchars($account['address_street'] ?? '', ENT_QUOTES)?>" autocomplete="address-line1"> 
                      </div>
                  </div>
                  <div class="row">
                      <label for="address_city" class="col-lg-4 col-md-4 label">City</label>
                      <div class="col-lg-8 col-md-8">
                        <input name="address_city" type="text" class="form-control" id="address_city" value="<?=htmlspecialchars($account['address_city'] ?? '', ENT_QUOTES)?>" autocomplete="address-level2">
                      </div>
                 </div>
                 <div class="row">
                      <label for="address_state" class="col-lg-4 col-md-4 label">State</label>
                      <div class="col-lg-8 col-md-8">
                        <input name="address_state" type="text" class="form-control" id="address_state" value="<?=htmlspecialchars($account['address_state'] ?? '', ENT_QUOTES)?>"  autocomplete="off"> 
                      </div>
                 </div>
                 <div class="row">
                      <label for="address_zip" class="col-lg-4 col-md-4 label">Zipcode</label>
                      <div class="col-lg-8 col-md-8">
                        <input name="address_zip" type="text" class="form-control" id="address_zip" value="<?=htmlspecialchars($account['address_zip'] ?? '', ENT_QUOTES)?>" autocomplete="postal-code"> 
                      </div>
                </div>
            <div class="text-center"><strong><p style='color:red'><?=$error_msg?></p></strong>
			<div class="mar-bot-2">
			<button class="btn btn-success mar-top-1 mar-right-1" type="submit">Save Changes</button>
			<a href="index.php" class="btn alt mar-top-1">Cancel</a>
		    </div>
           </div>
          </form><!-- End Profile Edit Form -->
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