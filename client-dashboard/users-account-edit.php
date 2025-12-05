<?php 
/*
2024-10-10 Production Ready
2025-06-21 Bug Fix Removed call to users-profile-process.
*/
include 'assets/includes/user-config.php';

// output message (errors, etc)
$msg = '';
$success = '';
if (!empty($_GET['success'])) {
    $success = $_GET['success'];
}


// Retrieve additional account info from the database because we don't have them stored in sessions.
$stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
// In this case, we can use the account ID to retrieve the account info.
$stmt->execute([ $_SESSION['id'] ]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle edit password post data
if (isset($_POST['newPassword'], $_POST['confirmPassword'])) {
	// Make sure the submitted registration values are not empty.
	if (empty($_POST['confirmPassword']) || empty($_POST['newPassword'])) {
		$msg = 'The input fields must not be empty!';
	} else if (!empty($_POST['newPassword']) && (strlen($_POST['newPassword']) > 20 || strlen($_POST['newPassword']) < 5)) {
		$msg = 'Password must be between 5 and 20 characters long!';
	} else if ($_POST['confirmPassword'] != $_POST['newPassword']) {
		$msg = 'Passwords do not match!';
	}
	// No validation errors... Process update
	if (empty($msg)) {
			// No errors occured, update the account...
			// Hash the new password if it was posted and is not blank
			$password = !empty($_POST['newPassword']) ? password_hash($_POST['newPassword'], PASSWORD_DEFAULT) : $account['password'];
			// Update the account (and set password_changed flag)
			$stmt = $pdo->prepare('UPDATE accounts SET password = ?, password_changed = 1 WHERE id = ?');
			$stmt->execute([ $password, $_SESSION['id'] ]);
				// Output success message
				$success = 'You have successfully changed your password!';
				// Profile updated successfully, redirect the user back to the profile page
				header('Location: users-account-edit.php?success='. $success);
				exit;
			}
}

include includes_path . 'page-setup.php';
?>
<main id="main" class="main">
    <div class="pagetitle">
      <nav>
        <ol class="breadcrumb">
         <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>/index.php"?>&nbsp;Home</a></li>
         <li class="breadcrumb-item">My Account</li>
         <li class="breadcrumb-item active">My Settings</li>
        </ol>
      </nav>
    </div>
<section class="section profile">
  <div class="col-xl-12 col-lg-12 col-sm-12">
    <div class="card">
     <div class="card-body pt-3">
      <div class="tab-content pt-2">
       <div class="tab-pane fade show active profile-overview" id="profile-overview">
            <h5 class="card-title">My Settings</h5>
         <div class="row">
          <div class="col-lg-6">
            <div class="card">
             <div class="card-body">
              <h6 class="card-title">Change Password</h6>
                  <!-- Change Password Form -->
                  <form action="users-account-edit.php" id='password-edit-form' class='form' method="post">

                   <div class="row mb-3">
                      <label for="newPassword" class="col-lg-4 col-md-4 label">New Password</label>
                      <div class="col-lg-8 col-md-8">
                        <input name="newPassword" type="newPassword" class="form-control" id="newPassword" autocomplete="off" >
                      </div>
                    </div>

                    <div class="row mb-3">
                      <label for="confirmPassword" class="col-lg-4 col-md-4 label">Re-enter New Password</label>
                      <div class="col-lg-8 col-md-8">
                        <input name="confirmPassword" type="password" class="form-control" 'autocomplete=off' id="confirmPassword" autocomplete="off">
                      </div>
                    </div>
                <div class="text-center"><strong><p style='color:red'><?=$msg?></p></strong>
                <div class="text-center"><strong><p style='color:green'><?=$success?></p></strong>
			<div class="mar-bot-2">
			<button class="btn btn-success mar-top-1 mar-right-1" type="submit">Save Changes</button>
			<a href="index.php" class="btn alt mar-top-1">Cancel</a>
		    </div>
                  </form><!-- End Change Password Form -->
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