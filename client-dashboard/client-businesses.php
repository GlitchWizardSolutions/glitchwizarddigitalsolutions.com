<?php /*
Production 10-10-2024
2025-06-16 Bug Fix Tabs VERIFIED.

This page displays all the invoice client/businesses in tabs.  
Still under construction - 
This will allow users to add a new business or update an existing business in 
their member profile area.
*/ 
include 'assets/includes/user-config.php';
;
// Handle edit business profile
if (isset($_POST['invoice_client_id'])) {
			// Update the session variables
			$_SESSION['invoice_client_id'] = $_POST['invoice_client_id'];
			$business_id = $_POST['invoice_client_id'];
           //redirect the user to the edit business profile page.
				header("Location: {$base_url}/client-business-edit.php?business_id=" . $business_id);
				exit;
			}
			
// output message (errors, etc)
$msg = '';

$stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
// In this case, we can use the SESSION ID of the logged in user, to retrieve the account info.
$stmt->execute([ $_SESSION['id'] ]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare('SELECT * FROM invoice_clients WHERE acc_id = ? AND issue ="No" AND incomplete ="No"');
$stmt->execute([ $_SESSION['id'] ]);
$invoice_clients_complete = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Retrieve all of the businesses for the member - which are called invoice_clients in the database.
$stmt = $pdo->prepare('SELECT * FROM invoice_clients WHERE acc_id = ? AND incomplete ="Yes"');
$stmt->execute([ $_SESSION['id'] ]);
$invoice_clients_incomplete = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Retrieve all of the businesses for the member - which are called invoice_clients in the database.
$stmt = $pdo->prepare('SELECT * FROM invoice_clients WHERE acc_id = ? AND issue ="Yes" AND incomplete ="No"');
$stmt->execute([ $_SESSION['id'] ]);
$invoice_clients_invalid = $stmt->fetchAll(PDO::FETCH_ASSOC);
/*if(
      $invoice_clients['business_name']=='None'
   || $invoice_clients['business_name']==''
   || $invoice_clients['description']=='None'
   || $invoice_clients['description']==''
   || $invoice_clients['first_name']=='None'
   || $invoice_clients['first_name']==''
   || $invoice_clients['last_name']==''
   || $invoice_clients['last_name']=='None'
   || $invoice_clients['email']=='None'
   || $invoice_clients['email']==''
   || $invoice_clients['phone']==''
   || $invoice_clients['phone']=='None'
   || $invoice_clients['address_street']=='None'
   || $invoice_clients['address_street']==''
   || $invoice_clients['address_city']=='None'
   || $invoice_clients['address_city']==''
   || $invoice_clients['address_state']=='None'
   || $invoice_clients['address_state']==''
   || $invoice_clients['address_zip']=='None'
   || $invoice_clients['address_zip']==''
   ){
    $tab_flag='incomplete';
    $reason='Your Profile is Incomplete.';
}elseif($invoice_clients['issue']!='Yes'){
    $tab_flag='invalid';
    $reason='Your Profile has Invalid Contact Information.';
}else{
    $tab_flag='complete';
    $reason='Your Profile is Complete.';
}*/  
include includes_path . 'page-setup.php';
?>
<div id="top" style='margin:10px'>
<main id="main" class="main">
    <div class="pagetitle"><span class='right'><a class='btn btn-primary' href='client-business-create.php'>Create New Business</a></span>
      <nav>
        <ol class="breadcrumb">
         <li class="breadcrumb-item"><a href="<?php echo(site_menu_base) ?>client-dashboard/index.php">&nbsp;Home</a></li>
         <li class="breadcrumb-item"><a href="<?php echo(site_menu_base) ?>client-dashboard/users-profile-edit.php">My Account</a></li> 
         <li class="breadcrumb-item active">My Business Profiles</li>
        </ol>
      </nav>
    </div><? /* pagetitle */ ?>

<section id='viewing section' class="section profile">
 <div id='section-all' class="col-xl-12 col-lg-12 col-sm-12">
  <div id='card-all' class="card">
   <div id='card-body-all' class="card-body pt-3">
    <ul class="nav nav-tabs nav-tabs-bordered">    
     <li class="nav-item">
       <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#complete">Completed Profiles</button>
     </li>  
     <li class="nav-item">
       <button class="nav-link" data-bs-toggle="tab" data-bs-target="#incomplete"><p style="color:yellow'> Incompleted Profiles "</button>
     </li> 
     <li class="nav-item">
       <button class="nav-link" data-bs-toggle="tab" data-bs-target="#invalid">Invalid Profiles</button>
     </li>
     </ul>
<div id='tab-content-all' class="tab-content pt-2"> 

<?php /* ****************    COMPLETE BUSINESS PROFILES    **************************************************** */ ?>    
<div class="tab-pane fade show active complete" id="complete">
  <h5 class="card-title">Complete Business Profiles</h5>
     <p>Each Domain Name has a Business Profile, used in our invoicing system, as well as on your website.</p>
     
<?php foreach ($invoice_clients_complete as $invoice_complete): ?>
<div class="row" id='1'>
  <div class='col-1'></div>
   <div class="col-10"  id='2'>
     <div class="card"  id='3'>
      <div class="card-body"  id='4'>
        <div class="block tickets"  id='5'> 
	     <div class="tickets-list"  id='6'>
          <form action="" id='businesses-form' style="width:100%" class='form' method="post"> 
              <input name="invoice_client_id" type="hidden" id="invoice_client_id" value="<?=htmlspecialchars($invoice_complete['id']?? '', ENT_QUOTES)?>">
              <button class="btn btn-sm btn-success" style='width:75px' type="submit">Edit</button>  
          </form> 
          <div class="row ticket col-12">
               <h5>
			      <strong><?=htmlspecialchars($invoice_complete['business_name'] ?? '', ENT_QUOTES)?></strong>
			   </h5>
		  </div><? /* end row ticket col-12   */ ?>
		 <div class='row col-12'> 
		     <div class='col-1'></div><? /* end col-1 */ ?>
		     <div id='col-10' class='col-10'>
		        <span class="msg responsive-hidden"><p><?=htmlspecialchars(strip_tags($invoice_complete['description'] ?? ''), ENT_QUOTES)?></p></span>
		     </div><? /* end col-10 */ ?>
		   <div class='col-1'></div><? /* end col-1 */ ?>
		 </div><? /* end row col-12 */ ?>
       </div><!--1 end ticket list-->
      </div><!--2 end block tickets-->
     </div><!--3 end card body-->
    </div><!--4 end card-->
   </div><!--5 end column-->
  <div class='col-1'></div> 
</div><!--6 end row-->
<?php endforeach; ?><!--end for each-->

<?php if (!$invoice_clients_complete): ?>
  <p>You have no completed business profiles to edit.</p>
<?php endif; ?>    
</div> <? /* end completed tickets */ ?> 
 
<?php /* ****************    INCOMPLETE BUSINESS PROFILES    **************************************************** */ ?>    
<div class="tab-pane fade show incomplete" id="incomplete">
  <h5 class="card-title">Incomplete Business Profiles</h5>
     <p>Each Domain Name has a Business Profile, used in our invoicing system, as well as on your website.</p>
      
<?php foreach ($invoice_clients_incomplete as $invoice_incomplete): ?>
<div class="row" id='1'>
  <div class='col-1'></div>
   <div class="col-10"  id='2'>
     <div class="card"  id='3'>
      <div class="card-body"  id='4'>
        <div class="block tickets"  id='5'> 
	     <div class="tickets-list"  id='6'>
          <form action="" id='businesses-form' style="width:100%" class='form' method="post"> 
              <input name="invoice_client_id" type="hidden" id="invoice_client_id" value="<?=htmlspecialchars($invoice_incomplete['id']?? '', ENT_QUOTES)?>">
              <button class="btn btn-sm btn-success" style='width:75px' type="submit">Edit</button>  
          </form> 
          <div class="row ticket col-12">
               <h5>
			      <strong><?=htmlspecialchars($invoice_incomplete['business_name'] ?? '', ENT_QUOTES)?></strong>
			   </h5>
		  </div><? /* end row ticket col-12   */ ?>
		 <div class='row col-12'> 
		     <div class='col-1'></div><? /* end col-1 */ ?>
		     <div id='col-10' class='col-10'>
		        <span class="msg responsive-hidden"><p><?=htmlspecialchars(strip_tags($invoice_incomplete['description'] ?? ''), ENT_QUOTES)?></p></span>
		     </div><? /* end col-10 */ ?>
		   <div class='col-1'></div><? /* end col-1 */ ?>
		 </div><? /* end row col-12 */ ?>
       </div><!--1 end ticket list-->
      </div><!--2 end block tickets-->
     </div><!--3 end card body-->
    </div><!--4 end card-->
   </div><!--5 end column-->
  <div class='col-1'></div> 
</div><!--6 end row-->
<?php endforeach; ?><!--end for each-->

<?php if (!$invoice_clients_incomplete): ?>
  <p>You have no incomplete business profiles to edit.</p>
<?php endif; ?>    
</div> <? /* end incomplete tickets */ ?> 
 
<?php /* ****************    INVALID BUSINESS PROFILES    **************************************************** */ ?>    
<div class="tab-pane fade show invalid" id="invalid">
  <h5 class="card-title">Invalid Business Profiles</h5>
     <p>Each Domain Name has a Business Profile, used in our invoicing system, as well as on your website.</p>
  
<?php foreach ($invoice_clients_invalid as $invoice_invalid): ?>
<div class="row" id='1'>
  <div class='col-1'></div>
   <div class="col-10"  id='2'>
     <div class="card"  id='3'>
      <div class="card-body"  id='4'>
        <div class="block tickets"  id='5'> 
	     <div class="tickets-list"  id='6'>
          <form action="" id='businesses-form' style="width:100%" class='form' method="post"> 
              <input name="invoice_client_id" type="hidden" id="invoice_client_id" value="<?=htmlspecialchars($invoice_invalid['id']?? '', ENT_QUOTES)?>">
              <button class="btn btn-sm btn-success" style='width:75px' type="submit">Edit</button>  
          </form> 
          <div class="row ticket col-12">
               <h5>
			      <strong><?=htmlspecialchars($invoice_invalid['business_name'] ?? '', ENT_QUOTES)?></strong>
			   </h5>
		  </div><? /* end row ticket col-12   */ ?>
		 <div class='row col-12'> 
		     <div class='col-1'></div><? /* end col-1 */ ?>
		     <div id='col-10' class='col-10'>
		        <span class="msg responsive-hidden"><p><?=htmlspecialchars(strip_tags($invoice_invalid['description'] ?? ''), ENT_QUOTES)?></p></span>
		     </div><? /* end col-10 */ ?>
		   <div class='col-1'></div><? /* end col-1 */ ?>
		 </div><? /* end row col-12 */ ?>
       </div><!--1 end ticket list-->
      </div><!--2 end block tickets-->
     </div><!--3 end card body-->
    </div><!--4 end card-->
   </div><!--5 end column-->
  <div class='col-1'></div> 
</div><!--6 end row-->
<?php endforeach; ?><!--end for each-->

<?php if (!$invoice_clients_invalid): ?>
  <p>You have no invalid business profiles to edit.</p>
<?php endif; ?>    
</div> <? /* end invalid tickets */ ?> 
</div><!--/tab-content-all-->
 </div><!--/card-body-all-->
   </div><!--/card-all-->
  </div><!--section-all-->
 </section> <? /* / end viewing section */ ?>
</main><? /* end main */ ?> 
</div><? /* / end top */ ?>
<script>
<?php include includes_path . 'footer-close.php'; ?>