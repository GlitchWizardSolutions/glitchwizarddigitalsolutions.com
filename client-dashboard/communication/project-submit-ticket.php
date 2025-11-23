<?php
/*******************************************************************************
PROJECT SYSTEM - project-submit-ticket.php
LOCATION: /public_html/client-dashboard/communication/
DESCRIBE:  
   INPUT: SESSION
LOGGEDIN: REQUIRED ADMIN
  OUTPUT: 
REQUIRED:
   PAGES:
   FILES: /assets/css/css_handler/tickets.css
DATABASE: TABLES: project_categories, project_tickets, project_tickets_uploads
LOG NOTE: PRODUCTION 2024-09-14 - Active
CHANGELG: 2024-10-02 Moved to client-dashboard/communications
          2025-06-19 Bug Fix - Variable out of scope.
*******************************************************************************/
include 'assets/includes/user-config.php';  
$pdo = pdo_connect_mysql();
// output message (errors, etc)
$msg = '';
//Get initial values to default to.
        $stmt = $pdo->prepare('SELECT * FROM client_projects ORDER BY subject');
        $stmt->execute();
        $client_projects = $stmt->fetchAll(PDO::FETCH_ASSOC); 
$domain_id=5;        
$domains = $pdo->query('SELECT * FROM domains ORDER BY domain')->fetchAll(PDO::FETCH_ASSOC);
$client_projects= $pdo->query('SELECT * FROM client_projects ORDER BY subject')->fetchAll(PDO::FETCH_ASSOC);
// Fetch all the category names from the categories MySQL table
$categories = $pdo->query('SELECT * FROM project_categories ORDER BY title')->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare('SELECT pt.name, cp.id AS id, cp.subject, d.domain FROM client_projects cp, domains d, project_types pt WHERE d.id = cp.domain_id AND pt.id=cp.project_type_id ORDER BY d.domain');
$stmt->execute();
$project_links = $stmt->fetchALL(PDO::FETCH_ASSOC);

// Set ticket_id in session to avoid passing in url to ticket-view.
function set_admin_response_ticket_id($ticketID, $redirect_to = site_menu_base . 'client-dashboard/communication/project-ticket-view.php') { 
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
//When there is a change to the domain name, it gets the client projects that have that domain id.
   if(isset($_POST["domains"])){
        $stmt = $pdo->prepare('SELECT * FROM client_projects WHERE id = ? ORDER BY subject');
        $stmt->execute([ $domain_id ]);
        $client_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $domain_id=$_POST["domains"];
   }

// Check if POST data exists (user submitted the Projects form)
if (isset($_POST['title'], $_POST['ticket-message'], $_POST['priority'], $_POST['category'])) {
    // Validation checks...
    if (empty($_POST['title']) || empty($_POST['ticket-message'])) {
        $msg = 'Please complete the form!';
    } else if (strlen($_POST['title']) > max_title_length) {
        $msg = 'Title must be less than ' . max_title_length . ' characters long!';
    } else if (strlen($_POST['ticket-message']) > max_msg_length) {
        $msg = 'Message must be less than ' . max_msg_length . ' characters long!';
    }else {
 
        // Insert new record into the project_tickets table
        $stmt = $pdo->prepare('INSERT INTO project_tickets (title, msg, ticket_status, priority, category_id,  reminder_date) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$_POST['title'], $_POST['ticket-message'], $_POST['ticket_status'], $_POST['priority'], $_POST['category'], $_POST['reminder_date'] ]);

        // Retrieve the ticket ID
        $ticket_id = $pdo->lastInsertId();
 
        // Handle the file uploads
        if (attachments && isset($_FILES['attachments'])) {
            // Iterate the uploaded files
            for ($i = 0; $i < count($_FILES['attachments']['name']); $i++) {
                
                // Get the file extension (png, jpg, etc)
                $ext = strtolower(pathinfo($_FILES['attachments']['name'][$i], PATHINFO_EXTENSION));
                
                // The file name will contain a unique code to prevent multiple files with the same name.
            	$upload_path = project_uploads_directory . sha1(uniqid() . $ticket_id . $i) .  '.' . $ext;
            	
            	// Check to make sure the file is valid
            	if (!empty($_FILES['attachments']['tmp_name'][$i]) && in_array($ext, explode(',', attachments_allowed))) {
            		if (!file_exists($upload_path) && $_FILES['attachments']['size'][$i] <= max_allowed_upload_file_size) {
            			// If everything checks out, we can move the uploaded file to its final destination...
            			move_uploaded_file($_FILES['attachments']['tmp_name'][$i], $upload_path);
            			// Insert attachment info into the database (ticket_id, filepath)
            			$stmt = $pdo->prepare('INSERT INTO project_tickets_uploads (ticket_id, filepath) VALUES (?, ?)');
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
      <nav> <span class='fs-7' style='float:right'> <?=date('M d, Y h:ia')?></span>
        <ol class="breadcrumb">
         <li class="breadcrumb-item"><a href="<?php echo site_menu_base?>client-dashboard/index.php">Home</a></li>
         <li class="breadcrumb-item active">Submit Projects</li> 
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
                   <h6 class="card-title">Establish New Ticket for EXISTING Project</h6>
                   
                    <p><strong>Project Ticket</strong><br>
                    Explain the ticket requirements in detail and include images if necessary.</p>
<?php /* This form grabs the domain selected, and once selected, automagically sets the domain id to use for the next drop down.  
        <form method="POST" action="">
           <div class="row mb-3">
            <label for="domains" class="col-md-4 col-lg-3 col-form-label">Project's Domain</label> 
            <div class="col-md-3 col-lg-3 mt-auto">
             <select aria-labelledby="domains" name="domains" id="domains" onchange="this.form.submit()">
                
               <?php foreach($domains as $domain): ?>
                 <option aria-description="<?=$domain['domain']??'No Domain' ?>" value="<?=$domain['id']??5 ?>">&nbsp;<?=$domain['domain']??'No Domain' ?></option>
               <?php endforeach; ?>
             </select>
            </div><!--/mt-auto-->
           </div><!--/col-md-3-->
        </form>

                <?=$domain_id?> */ ?>  
 <!-- Create a Project Form -->
          <form action= "" method="post" id='create-ticket-form' class="form" enctype="multipart/form-data">
           <div class="row mb-3">
            <label for="client_project" class="col-md-2 col-lg-2 col-form-label">Project</label> 
            <div class="col-md-3 col-lg-3 mt-auto">
             <select aria-labelledby="client_project" name="client_project" id="client_project">   
             
            
               <?php foreach($project_links as $project_id): ?>
           
           <option aria-description="<?=$project_id['subject']?>" value="<?=$project_id['id']?>">&nbsp;&nbsp;<?=$project_id['domain'] . ' [ ' . $project_id['name'] . ' ] ' ?></option>
               <?php endforeach; ?>
             </select>
            </div><!--/mt-auto-->
           </div><!--/col-md-3-->
     
                    <div class="row mb-3">
                      <label for="title" class="col-md-2 col-lg-2 col-form-label">Title</label>
                    <div class="col-md-8 col-lg-9">
                        <input aria-labelledby="title" type="text" name="title" class="form-control" placeholder="Title for this Project's Ticket" autocomplete="on" id="title" maxlength="<?=max_title_length?>" aria-required="true" required>
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
            <label for="ticket_status" class="col-md-2 col-lg-2 col-form-label">Status</label>
             <div class="col-md-2 col-lg-2 mt-auto">
            <select aria-labelledby="ticket_status" id="ticket_status" name="ticket_status" required>
                <option selected value="new">New</option>
                <option value="open">Open</option>
                <option value="paused">Paused</option>
                 <option value="closed">Closed</option>
            </select>
         </div>
     
            <label for="category" class="col-md-2 col-lg-2  col-form-label">Category</label> 
            <div class="col-md-2 col-lg-2 mt-auto">
             <select aria-labelledby="category" name="category" id="category">
               <option aria-description="Bug Found" value=8>Bug Found</option>  
               <?php foreach($categories as $category): ?>
                 <option aria-description="<?=$category['title']?>" value="<?=$category['id']?>"><?=$category['title']?></option>
               <?php endforeach; ?>
             </select>
            </div><!--/mt-auto-->
           </div><!--/col-md-3-->
           
           <div class="row mb-3" id='priority-class'>
               <label for="priority" class="col-md-2 col-lg-2 col-form-label">Priority</label>
            <div class="col-md-2 col-lg-2 mt-auto">
             <select aria-labelledby="priority" name="priority" id="priority" aria-required="true" required>
               <option aria-description="low" selected value="low">Low</option>
               <option aria-description="medium" value="medium">Medium</option>
               <option aria-description="high" value="high">High</option>
                <option style="color:red" aria-description="critical" value="critical">Critical</option>
               <option aria-description="paused" value="paused">Paused</option>
               <option aria-description="closed" value="closed">Closed</option>
             </select>
            </div><!--/mt-auto-->
           </div><!--/row priority-class-->   
         
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
                $newDate = $date->modify('+7 days');
                ?>
           <input id="reminder_date" type="date" aria-labelledby="date to review" name="reminder_date" value="<?=$newDate->format('Y-m-d')?>" required>
               
            </div><!--/mt-auto-->
           </div><!--/col-md-3-->      
      
           <div class="text-center">
	          <?php if ($msg): ?><p class="error-msg"><?=$msg?></p><?php endif; ?>
			 <div class="mar-bot-2" id='buttons'>
			   <button class="btn btn-success mar-top-1 mar-right-1" type="submit">Create Project</button>
			   <a href="project-review-responses.php" class="btn alt mar-top-1">Cancel</a>
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