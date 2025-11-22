<?php     
/***********************************************************************************************
PROJECT SYSTEM - project-ticket-view.php
LOCATION:   /public_html/communication
DESCRIBE:   This page is for the web developer (admin) to view the details of a specific project. 
            This can be done from Admin Section, but is not as nice to look at.
INPUTREQ:   Ticket id is passed into a session variable.
LOGGEDIN:   REQUIRED
   ADMIN:   /public_html/admin/
     OUT:   Notifications are not coded.
      IN:   SESSION variables and sql queries
LOG NOTE:   2024-10-08 PRODUCTION  - Active
            2025-01-11 Removed all but Admin User.
                       Added reminder_date & revamped status.
***********************************************************************************************/
include 'assets/includes/user-config.php';
include process_path . 'project-ticket-view-process.php';

// Set upload paths for attachments (filepath already contains project-ticket-uploads/)
$ticket_uploads_path = public_path . "client-dashboard/communication/";
$ticket_uploads_url = BASE_URL . "client-dashboard/communication/";
   
if ($_SESSION['role'] !="Admin") { 
    //Remove anyone but Admin.
    header("Location: {$outside_url}");
    exit;
}

 
$checked_open="";
$checked_closed="";
$checked_paused="";
$checked_new="";
if($ticket_data['ticket_status']=='new'){
    $checked_new ='checked="checked"';
    $checked_open="";
    $checked_closed="";
    $checked_paused="";
} elseif($ticket_data['ticket_status']=='open'){
    $checked_open ='checked="checked"';
    $checked_closed="";
    $checked_paused="";
    $checked_new="";
} elseif($ticket_data['ticket_status']=='closed'){
    $checked_closed ='checked="checked"';
    $checked_open="";
    $checked_paused="";
    $checked_new="";
} else {
    $checked_paused ='checked="checked"'; 
    $checked_open="";
    $checked_closed="";
    $checked_new="";
} 
 
 
// Check if POST data exists (web dev submitted the form)
if (isset($_POST['new_comment'])) {
    // Validation checks...
    if (empty($_POST['new_comment']) || empty( $_POST['status'])) {
        $msg = 'Please complete the form!';
    } else if (strlen($_POST['new_comment']) > max_msg_length) {
        $msg = 'Message must be less than ' . max_msg_length . ' characters long!';
    } else {
    $account_id = $_SESSION['id'];
    // Insert the new comment into the "project_tickets_comments" table
    //ticket_id of the specific ticket, the new comment, the id of the person logged in, and the role of the person leaving the comment.
    //
    $stmt = $pdo->prepare('INSERT INTO project_tickets_comments (ticket_id, msg, acc_id, new) VALUES (?, ?, ?, ?)');
    $stmt->execute([$_SESSION['ticket_id'], $_POST['new_comment'], $account['id'], $account['role'] ]);
    $last_comment=0;
    
    //update the status in project_tickets, and last comment
    $stmt = $pdo->prepare('UPDATE project_tickets SET last_comment = ?, ticket_status = ?, last_update = ?, reminder_date = ? WHERE id = ?');
    $stmt->execute([ $last_comment, $_POST['status'], date('Y-m-d\TH:i:s'), $_POST['reminder_date'], $_SESSION['ticket_id'] ]);

    //Redirect to ticket page
    header('Location: project-ticket-view.php');
    exit;
}
}
include includes_path . "page-setup.php";
?>   
<main id="main" class="main">
    <div class="pagetitle">
      <nav>
        <ol class="breadcrumb">
         <li class="breadcrumb-item"><a href="<?php echo(site_menu_base) ?>client-dashboard/index.php">Home</a></li>
         <li class="breadcrumb-item"><a href="<?php echo(site_menu_base) ?>client-dashboard/communication/project-submit-ticket.php">Create New Project</a></li> 
         <li class="breadcrumb-item"><a href="<?php echo(site_menu_base) ?>client-dashboard/communication/project-review-responses.php">Projects</a></li> 
         <li class="breadcrumb-item active">Project Details</li>
        </ol>
      </nav>
    </div>
<section class="section profile">
  <div class="col-xl-12 col-lg-12 col-sm-12">
    <div class="card">
     <div class="card-body pt-3">
         <?php /* This is being reworked for different last_comment.  It should be 1 if new comment, set to zero if it's been "read" */ ?>
         <?php if ($ticket_data['last_comment'] == '1' and $ticket_data['ticket_status'] == 'open'): ?>
            <p class='fs-6' style="color: #FF5F15;"><strong>ALERT: NEW REPLY! </strong></p> 
       <?php else : ?>
                 <h6 class="card-title"> <?=$ticket_data['title'] ?> </h6> <span style='font-size:16px'>
                    <?php if ($ticket_data['ticket_status'] == 'open'): ?>
                         This project is currently being worked.
                     <?php elseif ($ticket_data['ticket_status'] == 'new'): ?> 
                         This project is currently in the queue.
                     <?php elseif ($ticket_data['ticket_status'] == 'paused'): ?>
                        This project has been paused.
                     <?php elseif ($ticket_data['ticket_status'] == 'closed'): ?>
                        This project has been completed, and is now closed.
                     <?php endif; ?>
 </span>
                
     <?php endif; ?>
       <div class="tab-content pt-2">
        <div class="tab-pane fade show active ticket-view" id="ticket-view">
 

          
          <div class="row">
<!--leftside-->
            <div class="col-lg-6">
              <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Project Ticket #<?=$ticket_data['id'] ?><span class='fs-7' style='float:right'> Submitted <?=date('M d, Y', strtotime($ticket_data['created']))?></span> </h6>
 <div class="block tickets view"> 

       <?php if ($ticket_data['last_comment'] == '1' and $ticket_data['ticket_status'] == 'open'): ?>
           
       <p class='fs-6'>This ticket is ready to be scheduled.</p>
       <?php endif; ?>        

       <?php if ($ticket_data['ticket_status'] == 'paused'): ?>
          <p class='fs-6' style="color: #FF5F15;"><strong>This ticket is ready to be reviewed.</strong></p>
           
          <p class='fs-6'>If the ticket is ready, please reopen or close it.</p>
       <?php endif; ?>
       
       <?php if ($ticket_data['ticket_status'] == 'closed'): ?>
          
         <p class='fs-5'>Thank you for being here! I hope something wonderful happens to you today!</p>
       <?php endif; ?>

    <div class="profile">
     
    </div><!--profile-->
    <div class="info">
 
        </div><!--info-->
 
   <div class="ticket">
     <div class='fs-6'>
      <p class='fs-6'><strong>Status: </strong> <span class="fs-6 <?=$ticket_data['ticket_status']?>"><?=$ticket_data['ticket_status']?> </span></p>
       <p class='fs-6'><strong>Priority: </strong> <span class="fs-6 <?=$ticket_data['priority']?>"><?=$ticket_data['priority']?> </span></p>
       <p class='fs-6'><strong>Category: </strong><span class="category" title="Category"><?=$ticket_data['category']?></span></p>
        </div><!--no name div tag close-->
    </div><!--projects-->
<div style='border:solid .25px grey'>
   <p class='ms-3'><strong><?=$ticket_data['title'] ?></strong></p>
<hr>

        <p class="msg fs-6 ms-3"><?=str_ireplace(['&lt;strong&gt;','&lt;/strong&gt;','&lt;u&gt;','&lt;/u&gt;','&lt;i&gt;','&lt;/i&gt;'], ['<strong>','</strong>','<u>','</u>','<i>','</i>'], nl2br(htmlspecialchars($ticket_data['msg'] ?? '', ENT_QUOTES)))?></p>
    </div><!--projects-->
</div><!--block projects view-->

    <?php if (!empty($ticket_uploads)): ?>
    <h6 class="mt-4 mb-3">Attachment(s)</h6>
    <?php foreach($ticket_uploads as $ticket_upload): ?>
        <?php 
            $upload_file_path = $ticket_uploads_path . $ticket_upload['filepath'];
            $upload_url = $ticket_uploads_url . $ticket_upload['filepath'];
        ?>
        <div class="mb-3" style="border: 1px solid #ddd; padding: 10px; border-radius: 4px;">
            <?php if (file_exists($upload_file_path) && @getimagesize($upload_file_path)): ?>
                <img src="<?=$upload_url?>" class="img-fluid" style="max-width: 100%; height: auto;" alt="Attachment">
                <div class="mt-2">
                    <small class="text-muted"><a href="<?=$upload_url?>" target="_blank">View full size</a></small>
                </div>
            <?php else: ?>
                <a href="<?=$upload_url?>" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-file"></i> Download <?=strtoupper(pathinfo($ticket_upload['filepath'], PATHINFO_EXTENSION))?> file
                </a>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    <?php endif; ?>

         </div><!--/body-->
        </div><!--/card-->
       </div><!--/column-6-->
        
        <div class="col-lg-6">
          <div class="card">
            <div class="card-body">
             <h6 class="card-title">Project Notes</h6>

<div class="comments mt-4">
          
        <?php foreach($comments as $comment): ?>
        <div class="comment">
       
        <p><span class="comment-header">
                    <span class="date"><?=date('M d, Y', strtotime($comment['created']))?></span>
                   
                    <a href="../../admin/project_system/comment.php?id=<?=$comment['id']?>" target="_blank" class="edit"><i class="fa-solid fa-pen fa-xs"></i></a>
                  
                </span>
          <span class='fs-5 mt-3'><br> <?=str_ireplace(['&lt;strong&gt;','&lt;/strong&gt;','&lt;u&gt;','&lt;/u&gt;','&lt;i&gt;','&lt;/i&gt;'], ['<strong>','</strong>','<u>','</u>','<i>','</i>'], nl2br(htmlspecialchars($comment['msg'] ?? '', ENT_QUOTES)))?></span>
         </p>
        </div><!--comment-->
        <?php endforeach; ?>

 <form action="project-ticket-view.php" id='comment' class='form' method="post">
   <div class="new_comment">
     <textarea aria-labelledby="new_comment" id="new_comment" name="new_comment" class='fs-6'  style='padding:10px; width:100%' placeholder="Enter your comment..."  maxlength="<?=max_msg_length?>" required></textarea>
   </div>
   	        <?php if ($msg): ?>
                 <p class="error-msg" style='color:red'><?=$msg?></p>
            <?php endif; ?>

<span class='fs-6 mb-1'><strong>Status</strong></span> 
<div class="d-flex justify-content-start">
  
<label><span class='fs-6 mt-2 ms-4'>New</span> 
<input name="status" <?=$checked_new ?>  type="radio" id="status1" onclick="return confirm('Are you sure you want to set this project to NEW??')" value="new">
   <span class="checkmark"></span></label> 
  
  
  <label for="status2"><span class='fs-6 ms-4 mt-2'>Open</span> 
<input name="status" aria-labelledby="status2"  <?=$checked_open ?>  type="radio" id="status2" onclick="return confirm('Are you sure you want to open/re-open the ticket?')" value="open">
           <span class="checkmark"></span></label> 
 
            <label for="status3"><span class='fs-6 ms-4 mt-2'>Pause</span>             
                <input aria-labelledby="status3" id='status3'  <?=$checked_paused ?>  type="radio" name="status" onclick="return confirm('Are you sure you want to pause this project?')" value='paused'>
                <span class="checkmark"></span>
            </label> 

<br>
 <label for="status4"><span class='fs-6 mt-2 ms-4'>Close</span> 
<input name="status" aria-labelledby="status4" <?=$checked_closed ?> type="radio" id="status4" onclick="return confirm('Are you sure you want to permenantly close the ticket?')" value="closed">
   <span class="checkmark"></span></label>
</div> 

 <span class='fs-6 mb-1'><strong>Review Date</strong></span> 
 <div class="d-flex justify-content-start">
     <label for="reminder_date"><span class='fs-6 mt-2 ms-4'></span></label>
            <input id="reminder_date" type="date" aria-labelledby="date to review" name="reminder_date" value="<?=date('Y-m-d', strtotime($ticket_data['reminder_date']))?>" required>
</div>
 
<div class='mt-3'>
	        <?php if ($msg): ?>
                 <p class="error-msg" style='color:red'><?=$msg?></p>
            <?php endif; ?>
            <button type="submit" onclick="return confirm('select if this project is completed, or it will clear your message.)" class="btn btn-success">Post Comment</button>
 </div>
  </form>

            </div><!--/comments-->
           </div><!--/block tickets view-->
          </div><!--/card body-->
         </div><!--/card-->
        </div><!--/column-lg 12-->
       </div><!--/row-->
      </div><!--/tab content-->
     </div><!--/card body-->
    </div><!--/card-->
   </div><!--/sm8-->
</section>
</main><!-- End #main -->
<style>
/* The container */
.container {
  position: relative;
  padding-left: 5px;
  margin-bottom: 5px;
  cursor: pointer;
  font-size: 18px;
  width:20px;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
}

/* Hide the default radio button */
.container input {
  position: absolute;
  opacity: 0;
  cursor: pointer;
}

/* Create a custom radio button */
.checkmark {
  position: absolute;
  top: 0;
  left: 0;
  height: 18px;
  width: 18px;
  background-color: #eee;
  border-radius: 50%;
}

/* On mouse-over, add a grey background color */
.container:hover input ~ .checkmark {
  background-color: #ccc;
}

/* When the radio button is checked, add a background */
.container input:checked ~ .checkmark {
  background-color: #5D4C78;
}

/* Create the indicator (the dot/circle - hidden when not checked) */
.checkmark:after {
  content: "";
  position: absolute;
  display: none;
}

/* Show the indicator (dot/circle) when checked */
.container input:checked ~ .checkmark:after {
  display: block;
}

/* Style the indicator (dot/circle) */
.container .checkmark:after {
 	top: 5px;
	left: 5px;
	width: 8px;
	height: 8px;
	border-radius: 100%;
	background: white;
}
</style>
 <?php include includes_path . 'site-close.php'; ?>