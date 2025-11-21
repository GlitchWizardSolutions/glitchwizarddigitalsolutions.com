<?php     
/***********************************************************************************************
WARRANTY SYSTEM - warranty-ticket-view.php
LOCATION:   /public_html/barb-resources
DESCRIBE:   This page is for the web developer (admin) to view the details of a specific warranty. 
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
include process_path . 'warranty-ticket-view-process.php';
   
if ($_SESSION['role'] !="Admin") { 
    //Remove anyone but Admin.
    header("Location: {$outside_url}");
    exit;
}

$onthego= pdo_connect_onthego_db();
 
$checked_active="";
$checked_closed="";
$checked_service="";
$checked_new="";
if($ticket_data['ticket_status']=='new'){
    $checked_new ='checked="checked"';
    $checked_active="";
    $checked_closed="";
    $checked_service="";
} elseif($ticket_data['ticket_status']=='active'){
    $checked_active ='checked="checked"';
    $checked_closed="";
    $checked_service="";
    $checked_new="";
} elseif($ticket_data['ticket_status']=='closed'){
    $checked_closed ='checked="checked"';
    $checked_active="";
    $checked_service="";
    $checked_new="";
} else {
    $checked_service ='checked="checked"'; 
    $checked_active="";
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
    // Insert the new comment into the "warranty_tickets_comments" table
    $stmt = $onthego->prepare('INSERT INTO warranty_tickets_comments (ticket_id, message) VALUES (?, ?)');
    $stmt->execute([$_SESSION['ticket_id'], $_POST['new_comment']]);
    $last_comment=0;
    
    //update the status in warranty_tickets, and last comment
    $stmt = $onthego->prepare('UPDATE warranty_tickets SET ticket_status = ?, reminder_date = ? WHERE id = ?');
    $stmt->execute([$_POST['status'], $_POST['reminder_date'], $_SESSION['ticket_id'] ]);

    //Redirect to ticket page
    header('Location: warranty-ticket-view.php');
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
         <li class="breadcrumb-item"><a href="<?php echo(site_menu_base) ?>client-dashboard/barb-resources/warranty-submit-ticket.php">Create Warranty Record</a></li> 
         <li class="breadcrumb-item"><a href="<?php echo(site_menu_base) ?>client-dashboard/barb-resources/warranty-review-responses.php">Warranty Record</a></li> 
         <li class="breadcrumb-item active">Warranty Details</li>
        </ol>
      </nav>
    </div>
<section class="section profile">
  <div class="col-xl-12 col-lg-12 col-sm-12">
    <div class="card">
     <div class="card-body pt-3">
       
        
                 <h6 class="card-title"> <?=$ticket_data['title'] ?> </h6> <span style='font-size:16px'>
                    <?php if ($ticket_data['ticket_status'] == 'active'): ?>
                         This warranty is current and available.
                     <?php elseif ($ticket_data['ticket_status'] == 'new'): ?> 
                         This warranty needs review for completion of information.
                     <?php elseif ($ticket_data['ticket_status'] == 'service'): ?>
                        This warranty may be expired, but the service information is useful.
                     <?php elseif ($ticket_data['ticket_status'] == 'closed'): ?>
                        This product is not being kept in the queue.
                     <?php endif; ?>
 </span>
       <div class="tab-content pt-2">
        <div class="tab-pane fade show active ticket-view" id="ticket-view">
 

          
          <div class="row">
<!--leftside-->
            <div class="col-lg-6">
              <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Warranty Ticket #<?=$ticket_data['id'] ?><span class='fs-7' style='float:right'> Submitted <?=date('M d, Y', strtotime($ticket_data['created']))?></span> </h6>
 <div class="block tickets view"> 

       <?php if ($ticket_data['ticket_status'] == 'active'): ?>
           
       <p class='fs-6'>This product warranty is still active.</p>
       <?php endif; ?>        

       <?php if ($ticket_data['ticket_status'] == 'service'): ?>
          <p class='fs-6' style="color: #FF5F15;"><strong>If another warranty is purchased, please re-activate the ticket.</p>
       <?php endif; ?>
       
       <?php if ($ticket_data['ticket_status'] == 'closed'): ?>
          
         <p class='fs-5'>Closed.</p>
       <?php endif; ?>

    <div class="profile">
     
    </div><!--profile-->
    <div class="info">
 
        </div><!--info-->
 
   <div class="ticket">   
     <div class='fs-6'>
      <p class='fs-6'><strong>Status: </strong> <span class="fs-6 <?=$ticket_data['ticket_status']?>"><?=ucfirst($ticket_data['ticket_status']) ?> </span></p>
       <p class='fs-6'><strong>Owner: </strong> <span class="fs-6 <?=$ticket_data['owner']?>"> <?=$ticket_data['owner']?></span></p>
       <p class='fs-6'><strong>Product: </strong><span class="warranty_type" title="warranty_type"><?=$ticket_data['warranty_type']?></span></p>
        </div><!--no name div tag close-->
    </div><!--warranty tickets-->
<div style='border:solid .25px grey'>
   <p class='ms-3'><strong><?=$ticket_data['title'] ?></strong></p>
<hr>

        <p class="msg fs-6 ms-3"><?=str_ireplace(['&lt;strong&gt;','&lt;/strong&gt;','&lt;u&gt;','&lt;/u&gt;','&lt;i&gt;','&lt;/i&gt;'], ['<strong>','</strong>','<u>','</u>','<i>','</i>'], nl2br(htmlspecialchars($ticket_data['msg'] ?? '', ENT_QUOTES)))?></p>
    </div><!--warranty tickets-->
</div><!--block warranty tickets view-->
    <?php if (!empty($ticket_uploads)): ?>
    
    <h5 class="uploads-header mt-3 fs-6">Attachment(s)</h5>
    <div class="uploads">
        <?php foreach($ticket_uploads as $ticket_upload): ?>
        <a title="download ticket" href="<?=$ticket_upload['filepath']?>" download>
            <?php if (getimagesize($ticket_upload['filepath'])): ?>
            <img src="<?=$ticket_upload['filepath']?>" width="80" height="80" alt="">
            <?php else: ?>
            <i class="fas fa-file"></i>
            <span><?=pathinfo($ticket_upload['filepath'], PATHINFO_EXTENSION)?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div><!--uploads-->
    <?php endif; ?>
         </div><!--/body-->
        </div><!--/card-->
       </div><!--/column-6-->
        <div class="col-lg-6">
          <div class="card">
            <div class="card-body">
             <h6 class="card-title">Warranty Notes</h6>

<div class="comments mt-4">
          
        <?php foreach($comments as $comment): ?>
        <div class="comment">
       
        <p><span class="comment-header">
                    <span class="date"><?=date('M d, Y', strtotime($comment['created']))?></span>
                   
                    <a href="../../admin/warranty_system/comment.php?id=<?=$comment['id']?>" target="_blank" class="edit"><i class="fa-solid fa-pen fa-xs"></i></a>
                  
                </span>
          <span class='fs-5 mt-3'><br> <?=str_ireplace(['&lt;strong&gt;','&lt;/strong&gt;','&lt;u&gt;','&lt;/u&gt;','&lt;i&gt;','&lt;/i&gt;'], ['<strong>','</strong>','<u>','</u>','<i>','</i>'], nl2br(htmlspecialchars($comment['message'] ?? '', ENT_QUOTES)))?></span>
         </p>
        </div><!--comment-->
        <?php endforeach; ?>

 <form action="warranty-ticket-view.php" id='comment' class='form' method="post">
   <div class="new_comment">
     <textarea aria-labelledby="new_comment" id="new_comment" name="new_comment" class='fs-6'  style='padding:10px; width:100%' placeholder="Enter your comment..."  maxlength="<?=max_msg_length?>" required></textarea>
   </div>
   	        <?php if ($msg): ?>
                 <p class="error-msg" style='color:red'><?=$msg?></p>
            <?php endif; ?>

<span class='fs-6 mb-1'><strong>Status</strong></span> 
<div class="d-flex justify-content-start">
  
<label><span class='fs-6 mt-2 ms-4'>New</span> 
<input name="status" <?=$checked_new ?>  type="radio" id="status1" onclick="return confirm('Are you sure you want to set this warranty to NEW??')" value="new">
   <span class="checkmark"></span></label> 
  
  
  <label for="status2"><span class='fs-6 ms-4 mt-2'>Active</span> 
<input name="status" aria-labelledby="status2"  <?=$checked_active ?>  type="radio" id="status2" onclick="return confirm('Are you sure you want to active/re-active the ticket?')" value="active">
           <span class="checkmark"></span></label> 
 
            <label for="status3"><span class='fs-6 ms-4 mt-2'>Service Only</span>             
                <input aria-labelledby="status3" id='status3'  <?=$checked_service ?>  type="radio" name="status" onclick="return confirm('Are you sure you want to change this warranty to service only?')" value='service'>
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
            <button type="submit" onclick="return confirm('select if this warranty is completed, or it will clear your message.)" class="btn btn-success">Post Comment</button>
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