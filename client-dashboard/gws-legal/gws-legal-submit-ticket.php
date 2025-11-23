<?php
/*******************************************************************************
GWS LEGAL SYSTEM - gws-legal-submit-ticket.php
LOCATION: /public_html/client-dashboard/gws-legal/
DESCRIBE:  
   INPUT: SESSION
LOGGEDIN: REQUIRED
  OUTPUT: 
REQUIRED:
   PAGES:
   FILES: /assets/css/css_handler/tickets.css
DATABASE: TABLES: gws_legal_categories, gws_legal, gws_legal_uploads
LOG NOTE:  
 
*******************************************************************************/
include 'assets/includes/user-config.php';  
// Unified email system already loaded by user-config.php
$pdo = pdo_connect_mysql();
// Retrieve additional account info from the database because we don't have them stored in sessions.
$stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
$stmt->execute([ $_SESSION['id'] ]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);
$email=$account['email'];
$name=$account['full_name'];
$private = default_private ? 0 : 1;
$date=date('Y-m-d');
// output message (errors, etc)
$msg = '';

// Fetch all the category names from the categories MySQL table
$categories = $pdo->query('SELECT * FROM gws_legal_categories ORDER BY title')->fetchAll(PDO::FETCH_ASSOC);

// Set ticket_id in session to avoid passing in url to ticket-view.
function set_admin_response_ticket_id($ticketID, $redirect_to = site_menu_base . 'client-dashboard/gws-legal/gws-legal-view.php') { 
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

// Check if POST data exists (user submitted the Requirements form)
if (isset($_POST['title'], $_POST['ticket-message'], $_POST['priority'], $_POST['category'])) {
    // Validation checks...
    if (empty($_POST['title']) || empty($_POST['ticket-message'])) {
        $msg = 'Please complete the form!';
    } else if (strlen($_POST['title']) > max_title_length) {
        $msg = 'Title must be less than ' . max_title_length . ' characters long!';
    } else if (strlen($_POST['ticket-message']) > max_msg_length) {
        $msg = 'Message must be less than ' . max_msg_length . ' characters long!';
    }else {
        
        // Get the account ID
        $account_id = $account['id'];
        $approved = approval_required ? 0 : 1; 
        
        // Insert new record into the gws_legal table
        $stmt = $pdo->prepare('INSERT INTO gws_legal (title, email, msg, priority, category_id, private, acc_id, created, approved, full_name, reminder_date, last_comment) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$_POST['title'], $email, $_POST['ticket-message'], $_POST['priority'], $_POST['category'], $private, $account_id, date('Y-m-d H:i:s'), $approved, $name, $_POST['reminder_date'], 'Admin' ]);

        // Retrieve the ticket ID
        $ticket_id = $pdo->lastInsertId();
 
        // Handle the file uploads
        if (attachments && isset($_FILES['attachments'])) {
            // Iterate the uploaded files
            for ($i = 0; $i < count($_FILES['attachments']['name']); $i++) {
                
                // Get the file extension (png, jpg, etc)
                $ext = strtolower(pathinfo($_FILES['attachments']['name'][$i], PATHINFO_EXTENSION));
                
                // The file name will contain a unique code to prevent multiple files with the same name.
            	$upload_path = gws_legal_uploads_directory . sha1(uniqid() . $ticket_id . $i) .  '.' . $ext;
            	
            	// Check to make sure the file is valid
            	if (!empty($_FILES['attachments']['tmp_name'][$i]) && in_array($ext, explode(',', attachments_allowed))) {
            		if (!file_exists($upload_path) && $_FILES['attachments']['size'][$i] <= max_allowed_upload_file_size) {
            			// If everything checks out, we can move the uploaded file to its final destination...
            			move_uploaded_file($_FILES['attachments']['tmp_name'][$i], $upload_path);
            			// Insert attachment info into the database (ticket_id, filepath)
            			$stmt = $pdo->prepare('INSERT INTO gws_legal_uploads (ticket_id, filepath) VALUES (?, ?)');
            	        $stmt->execute([ $ticket_id, $upload_path ]);
            		}
            	}
            }
        }
        // Get the category name
        $category_name = 'none';
        foreach ($categories as $c) {
            $category_name = $c['id'] == $_POST['category'] ? $c['title'] : $category_name;
        }
 
        // Redirect to the view ticket page, the user should see their created ticket on this page
          set_admin_response_ticket_id($ticket_id);
            exit;
    }
}
include includes_path . 'page-setup.php';  
?>
<main id="main" class="main">
    <div class="pagetitle">
      <nav>
        <ol class="breadcrumb">
         <li class="breadcrumb-item"><a href="<?php echo site_menu_base?>client-dashboard/index.php">Home</a></li>
         <li class="breadcrumb-item active">Submit GWS Legal</li> 
        </ol>
      </nav>
    </div> <!--/pgtitle-->
    
<section class="section profile">
  <div class="col-xl-12 col-lg-12 col-sm-12">
    <div class="card">
     <div class="card-body pt-3">
       <div class="tab-content pt-2">
        <div class="tab-pane fade show active contact-us" id="contact-us">
           <h6 class="card-title"><p class="name"><?=htmlspecialchars($account['full_name'], ENT_QUOTES)?>
            <span class='fs-7' style='float:right'> <?=date('M d, Y h:ia')?></span></h6>
          <div class="row">
            <div class='col-1'></div>
            <div class="col-lg-10">
              <div class="card">
                <div class="card-body">
                   <h6 class="card-title">Establish New GWS Legal Record</h6>
                    <p>Explain the Requirement in detail and include images if necessary.</p>
 <!-- Create a Record Form -->
                    <form action= "" method="post" id='create-ticket-form' class="form" enctype="multipart/form-data">
                    <div class="row mb-3">
                      <label for="title" class="col-md-4 col-lg-3 col-form-label">Title</label>
                    <div class="col-md-8 col-lg-9">
                        <input aria-labelledby="title" type="text" name="title" class="form-control" placeholder="Title of Requirement" autocomplete="on" id="title" maxlength="<?=max_title_length?>" aria-required="true" required>
                    </div>
                    </div>

                    <div class="row mb-3">
                        <label for="ticket-message" class="col-md-4 col-lg-3 col-form-label fs-7">Message</label>
                      <div class="col-md-8 col-lg-9">
                       <div class="ticket-message">
                        <textarea aria-description="message" name="ticket-message" placeholder="Enter details here..." class="form-control" autocomplete="on" id="ticket-message" maxlength="<?=max_msg_length?>" aria-required="true" required></textarea>
                    </div>
                   </div>
                  </div>

            
          <div class="row mb-3">
            <label for="category" class="col-md-4 col-lg-3 col-form-label">Category</label> 
            <div class="col-md-3 col-lg-3 mt-auto">
             <select aria-labelledby="catagory" name="category" id="category">
               <?php foreach($categories as $category): ?>
                 <option aria-description="<?=$category['title']?>" value="<?=$category['id']?>"><?=$category['title']?></option>
               <?php endforeach; ?>
             </select>
            </div><!--/mt-auto-->
           </div><!--/col-md-3-->
           <div class="row mb-3" id='priority-class'>
               <label for="priority" class="col-md-4 col-lg-3 col-form-label">Priority</label>
            <div class="col-md-2 col-lg-2 mt-auto">
             <select aria-labelledby="priority" name="priority" id="priority" aria-required="true" required>
               <option aria-description="low" value="low">Low</option>
               <option aria-description="medium" value="medium">Medium</option>
               <option aria-description="high" value="high">High</option>
             </select>
            </div><!--/mt-auto-->
           </div><!--/row priority-class-->   
         
             <?php if (attachments): ?>
                   <div class="row mb-3">
                      <label for="attachments" class="col-md-4 col-lg-3 col-form-label">Attachment</label>
                     <div class="col-md-8 col-lg-9">
                       <input type="file" name="attachments[]" id="attachments" accept=".<?=str_replace(',', ',.', attachments_allowed)?>" multiple>
                      </div>
                    </div>
            <?php endif; ?>  
               <div class="row mb-3">
            <label for="reminder_date" class="col-md-4 col-lg-3 col-form-label">Review Date</label> 
            <div class="col-md-3 col-lg-3 mt-auto">
           <input id="reminder_date" type="date" aria-labelledby="date to review" name="reminder_date" value="<?=date('Y-m-d')?>" required>
               
            </div><!--/mt-auto-->
           </div><!--/col-md-3-->      
      
           <div class="text-center">
	          <?php if ($msg): ?><p class="error-msg"><?=$msg?></p><?php endif; ?>
			 <div class="mar-bot-2" id='buttons'>
			   <button class="btn btn-success mar-top-1 mar-right-1" type="submit">Create Requirement</button>
			   <a href="gws-legal-review-responses.php" class="btn alt mar-top-1">Cancel</a>
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