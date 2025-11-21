<?php
/*******************************************************************************
WARRANTY SYSTEM - warranty-submit-ticket.php
LOCATION: /public_html/client-dashboard/barb-resources/
DESCRIBE:  
   INPUT: SESSION
LOGGEDIN: REQUIRED ADMIN
  OUTPUT: 
REQUIRED:
   PAGES:
   FILES: /assets/css/css_handler/tickets.css
DATABASE: TABLES: warranty_typess, warranty_tickets, warranty_tickets_uploads
LOG NOTE: PRODUCTION 2024-09-14 - Active
CHANGELG: 2024-10-02 Moved to client-dashboard/barb-resources
*******************************************************************************/
include 'assets/includes/user-config.php';  
$onthego= pdo_connect_onthego_db();
// output message (errors, etc)
$msg = '';

$stmt = $onthego->prepare('SELECT * FROM warranty_tickets ORDER BY title');
$stmt->execute();
$warranty_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC); 

$stmt = $onthego->prepare('SELECT * FROM warranty_types');
$stmt->execute();
$warranty_types = $stmt->fetchALL(PDO::FETCH_ASSOC);

// Set ticket_id in session to avoid passing in url to warranty-ticket-view.php.
function set_admin_response_ticket_id($ticketID, $redirect_to = site_menu_base . 'client-dashboard/barb-resources/warranty-ticket-view.php') { 
    if (isset($ticketID)) { 
    		session_regenerate_id();
			$_SESSION['ticket_id']=$ticketID;
		    header('Location: ' . $redirect_to);
    	    exit;
    }else{
        echo 'No ticket_id set';
        exit;
    }
  }//function

// Check if POST data exists (user submitted the Warranty Tickets form)
if (isset($_POST['title'], $_POST['ticket-message'])) {
    // Validation checks...
    if (empty($_POST['title']) || empty($_POST['ticket-message'])) {
        $msg = 'Please complete the form!';
    } else if (strlen($_POST['title']) > max_title_length) {
        $msg = 'Title must be less than ' . max_title_length . ' characters long!';
    } else if (strlen($_POST['ticket-message']) > max_msg_length) {
        $msg = 'Message must be less than ' . max_msg_length . ' characters long!';
    }else {
 
        // Insert new record into the warranty_tickets table
        $stmt = $onthego->prepare('INSERT INTO warranty_tickets (title, msg, warranty_type_id, ticket_status, owner, reminder_date, purchase_date, warranty_expiration_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$_POST['title'], $_POST['ticket-message'], $_POST['warranty_type_id'], $_POST['ticket_status'], $_POST['owner'], $_POST['reminder_date'], $_POST['purchase_date'], $_POST['warranty_expiration_date'] ]);

        // Retrieve the ticket ID
        $ticket_id = $onthego->lastInsertId();
 
        // Handle the file uploads
        if (attachments && isset($_FILES['attachments'])) {
            // Iterate the uploaded files
            for ($i = 0; $i < count($_FILES['attachments']['name']); $i++) {
                
                // Get the file extension (png, jpg, etc)
                $ext = strtolower(pathinfo($_FILES['attachments']['name'][$i], PATHINFO_EXTENSION));
                
                // The file name will contain a unique code to prevent multiple files with the same name.
            	$upload_path = warranty_uploads_directory . sha1(uniqid() . $ticket_id . $i) .  '.' . $ext;
            	
            	// Check to make sure the file is valid
            	if (!empty($_FILES['attachments']['tmp_name'][$i]) && in_array($ext, explode(',', attachments_allowed))) {
            		if (!file_exists($upload_path) && $_FILES['attachments']['size'][$i] <= max_allowed_upload_file_size) {
            			// If everything checks out, we can move the uploaded file to its final destination...
            			move_uploaded_file($_FILES['attachments']['tmp_name'][$i], $upload_path);
            			// Insert attachment info into the database (ticket_id, filepath)
            			$stmt = $onthego->prepare('INSERT INTO warranty_tickets_uploads (ticket_id, filepath) VALUES (?, ?)');
            	        $stmt->execute([ $ticket_id, $upload_path ]);
            		}
            	}
            }
        }
    }

}
include includes_path . 'page-setup.php';  
?>
<main id="main" class="main">
    <div class="pagetitle">
      <nav> <span class='fs-7' style='float:right'> <?=date('M d, Y h:ia')?></span>
        <ol class="breadcrumb">
         <li class="breadcrumb-item"><a href="<?php echo(site_menu_base)?>client-dashboard/index.php">Home</a></li>
         <li class="breadcrumb-item active">Create New Warranty</li> 
        </ol>
      </nav>
    </div> <!--/pgtitle-->
<section class="section profile">
  <div class="col-xl-12 col-lg-12 col-sm-12">
    <div class="card">
     <div class="card-body pt-3">
       <div class="tab-content pt-2">
        <div class="tab-pane fade show active contact-us" id="contact-us">
            
          <div class="row">
            <div class='col-1'></div>
            <div class="col-lg-10">
              <div class="card">
                <div class="card-body">
                   <h6 class="card-title">Create New Warranty</h6>
                   
                    <p><strong>Warranty Record</strong><br>
                    Explain the warranty requirements in detail and include images if necessary.</p>
 <!-- Create a Warranty Form -->
          <form action= "" method="post" id='create-ticket-form' class="form" enctype="multipart/form-data">
           <div class="row mb-3">
            <label for="warranty_type_id" class="col-md-2 col-lg-2 col-form-label">Product</label> 
            <div class="col-md-3 col-lg-3 mt-auto">
             <select aria-labelledby="warranty_type_id" name="warranty_type_id" id="warranty_type_id">   
             
             
               <?php foreach($warranty_types as $warranty_type_id): ?>
           
           <option aria-description="<?=$warranty_type_id['name']?>" value="<?=$warranty_type_id['id']?>"><?=$warranty_type_id['name']  ?></option>
               <?php endforeach; ?>
             </select>
            </div><!--/mt-auto-->
           </div><!--/col-md-3-->
     
                    <div class="row mb-3">
                      <label for="title" class="col-md-2 col-lg-2 col-form-label">Title</label>
                    <div class="col-md-8 col-lg-9">
                        <input aria-labelledby="title" type="text" name="title" class="form-control" placeholder="Title for this Warranty's Record" autocomplete="on" id="title" maxlength="<?=max_title_length?>" aria-required="true" required>
                    </div>
                    </div>

                    <div class="row mb-3">
                        <label for="ticket-message" class="col-md-2 col-lg-2 col-form-label fs-7">Description</label>
                      <div class="col-md-8 col-lg-9">
                       <div class="ticket-message">
                        <textarea aria-description="message" name="ticket-message" placeholder="Enter details here..." class="form-control" autocomplete="on" id="ticket-message" maxlength="<?=max_msg_length?>" aria-required="true" required></textarea>
                    </div>
                   </div>
                  </div>
            <div class="row mb-3">
                
                  <label for="purchase_date" class="col-md-2 col-lg-2 col-form-label">Purchased
            </label>
            
            <div class="col-md-3 col-lg-3 mt-auto">
                <?php 
                $date = new DateTime();
                $newDate = $date->modify('-1 days');
                ?>
           <input id="purchase_date" type="date" aria-labelledby="date expired" name="purchase_date" value="<?=$newDate->format('Y-m-d')?>" required>
            </div><!--/mt-auto-->
  
            <label for="warranty_expiration_date" class="col-md-2 col-lg-2 col-form-label">Date Expires</label> 
            <div class="col-md-3 col-lg-3 mt-auto">
                <?php 
                $date = new DateTime();
                $newDate = $date->modify('+365 days');
                ?>
           <input id="warranty_expiration_date" type="date" aria-labelledby="date expired" name="warranty_expiration_date" value="<?=$newDate->format('Y-m-d')?>" required>
           </div>
           </div><!--/col-md-3--> 
    
                   <div class="row mb-3">
  <label for="ticket_status" class="col-md-2 col-lg-2 col-form-label">Status</label>
            
            <div class="col-md-3 col-lg-3 mt-auto">
             <select aria-labelledby="ticket_status" id="ticket_status" name="ticket_status" required>
                <option selected value="new">New</option>
                <option value="active">Active</option>
                <option value="service">Service Only</option>
                 <option value="closed">Closed</option>
            </select>
            </div><!--/mt-auto-->
  
             <label for="owner" class="col-md-2 col-lg-2 col-form-label">Owner</label>
            <div class="col-md-3 col-lg-3 mt-auto">
                <select aria-labelledby="owner" name="owner" id="owner" aria-required="true" required>
               <option aria-description="Barb" selected value="Barb">Barb</option>
               <option aria-description="Joey" value="Joey">Joey</option>
               <option aria-description="Business" value="Business">Business</option>
             </select>
           </div>
           </div><!--/col-md-3-->
  
         
             <?php if (attachments): ?>
                   <div class="row mb-3">
                      <label for="attachments" class="col-md-2 col-lg-2 col-form-label">Attachment</label>
                     <div class="col-md-8 col-lg-9">
                       <input type="file" name="attachments[]" id="attachments" accept=".<?=str_replace(',', ',.', attachments_allowed)?>" multiple>
                      </div>
                    </div>
            <?php endif; ?>  
               <div class="row mb-3">
            <label for="reminder_date" class="col-md-2 col-lg-2 col-form-label">Review Date</label> 
            <div class="col-md-3 col-lg-3 mt-auto">
                <?php 
                $date = new DateTime();
                $newDate = $date->modify('+365 days');
                ?>
           <input id="reminder_date" type="date" aria-labelledby="date to review" name="reminder_date" value="<?=$newDate->format('Y-m-d')?>" required>
               
            </div><!--/mt-auto-->
           </div><!--/col-md-3-->      
      
           <div class="text-center">
	          <?php if ($msg): ?><p class="error-msg"><?=$msg?></p><?php endif; ?>
			 <div class="mar-bot-2" id='buttons'>
			   <button class="btn btn-success mar-top-1 mar-right-1" type="submit">Create Warranty</button>
			   <a href="warranty-review-responses.php" class="btn alt mar-top-1">Cancel</a>
		     </div><!--/buttons-->
            </div><!--/text center-->
           </form>
          </div><!--/body-->
         </div><!--/card-->
        </div><!--/column-8-->
        <div class='col-2'></div>
       </div><!--/row-->
      </div><!--/contactus-->
     </div><!--/tab-content pt2-->
    </div><!--/card-->
   </div><!--/card body pt3-->
  </div><!--/col-12-->
 </section>
</main><!-- End #main -->
 <?php include includes_path . 'footer-close.php'; ?>