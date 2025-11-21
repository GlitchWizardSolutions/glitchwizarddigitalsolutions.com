<?php     
/***********************************************************************************************
TICKETING SYSTEM - ticket-view.php
LOCATION:   /public_html/
DESCRIBE:   Client can view their ticket from clicking the ticket id in the dropdown notification bell of the header on any page.
            Clients can respond to their ticket, via posting comments, and only after an admin resolves the ticket,
            they can either reopen their ticket or close their ticket.
INPUTREQ:   When the user clicks the ticket, the ticket id is passed into a session variable OR via secure token link from email.
LOGGEDIN:   REQUIRED (auto-login via token if valid)
   ADMIN:   /public_html/admin/
     OUT:   client and admin notifications
      IN:   SESSION variables and sql queries
LOG NOTE:   2024-10-08 PRODUCTION  - Active
          2025-11-21 Added secure token-based direct access from email links
***********************************************************************************************/

// Handle direct ticket access via email link with token BEFORE login check
if (!session_id()) {
    session_start();
}
require '../../../private/config.php';

if (isset($_GET['t']) && isset($_GET['token'])) {
    $ticket_id = (int)$_GET['t'];
    $provided_token = $_GET['token'];
    
    // Generate expected token for verification
    $expected_token = hash_hmac('sha256', $ticket_id, TICKET_SECRET);
    
    // Verify token matches
    if (hash_equals($expected_token, $provided_token)) {
        // Token is valid - verify ticket exists and get owner email
        try {
            $pdo = new PDO('mysql:host=' . db_host . ';dbname=' . db_name . ';charset=' . db_charset, db_user, db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $stmt = $pdo->prepare('SELECT t.*, a.* FROM tickets t LEFT JOIN accounts a ON a.id = t.acc_id WHERE t.id = ?');
            $stmt->execute([$ticket_id]);
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($ticket) {
                // Auto-login the ticket owner
                $_SESSION['loggedin'] = TRUE;
                $_SESSION['sec-username'] = $ticket['username'];
                $_SESSION['name'] = $ticket['username'];
                $_SESSION['id'] = $ticket['acc_id'];
                $_SESSION['role'] = $ticket['role'];
                $_SESSION['access_level'] = $ticket['access_level'];
                $_SESSION['email'] = $ticket['email'];
                $_SESSION['full_name'] = $ticket['full_name'];
                $_SESSION['document_path'] = $ticket['document_path'];
                $_SESSION['ticket_id'] = $ticket_id;
                
                // Redirect to clean URL without token
                header('Location: ticket-view.php');
                exit;
            }
        } catch (PDOException $e) {
            // Database error - continue to normal login
        }
    }
    // Invalid token or ticket not found - clear params and continue
    header('Location: ticket-view.php');
    exit;
}

include 'assets/includes/user-config.php';
include process_path . 'ticket-view-process.php';
// Check if POST data exists (user submitted the form)
if (isset($_POST['new-comment']) && isset($_SESSION['loggedin'])) {
    
    // Validation checks...
    if (empty($_POST['new-comment']) || empty( $_POST['status'])) {
        $msg = 'Please complete the form!';
    } else if (strlen($_POST['new-comment']) > max_msg_length) {
        $msg = 'Message must be less than ' . max_msg_length . ' characters long!';
    } else {
        // Get the account ID if the user is logged in
        $account_id = isset($_SESSION['loggedin']) ? $_SESSION['id'] : 0;
    // Insert the new comment into the "tickets_comments" table
    //ticket_id of the specific ticket, the new comment, the id of the person logged in, and the role of the person leaving the comment.
    $stmt = $pdo->prepare('INSERT INTO tickets_comments (ticket_id, msg, acc_id, new) VALUES (?, ?, ?, ?)');
    $stmt->execute([$_SESSION['ticket_id'], $_POST['new-comment'], $account['id'], $account['role'] ]);
   
    //update the status in tickets, and last comment
    $stmt = $pdo->prepare('UPDATE tickets SET last_comment = ?, ticket_status = ?, last_update = ? WHERE id = ?');
    $stmt->execute([ $account['role'], $_POST['status'], date('Y-m-d\TH:i:s'), $_SESSION['ticket_id'] ]);
    
if ($_SESSION['role'] =="Admin") { 
   // Send updated ticket email to user
    send_ticket_email($ticket_data['email'], $ticket_data['id'], $ticket_data['title'], $_POST['new-comment'],$ticket_data['priority'],   $ticket_data['category'],  $_POST['private'], $_POST['status'], 'comment');
}else{
    
    $admin_email = notify_admin_email;
    send_ticket_email($admin_email, $ticket_data['id'], $ticket_data['title'], $_POST['new-comment'], $ticket_data['priority'],  $ticket_data['category'], $_POST['private'], $_POST['status'], 'notification-comment', $name, $email);
}
    //Redirect to ticket page
    header('Location: ticket-view.php');
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
         <li class="breadcrumb-item"><a href="<?php echo(site_menu_base) ?>client-dashboard/communication/submit-ticket.php">Communication</a></li> 
         <li class="breadcrumb-item"><a href="<?php echo(site_menu_base) ?>client-dashboard/communication/review-responses.php">My Tickets</a></li> 
         <li class="breadcrumb-item active">Ticket Details</li>
        </ol>
      </nav>
    </div>
<section class="section profile">
  <div class="col-xl-12 col-lg-12 col-sm-12">
    <div class="card">
     <div class="card-body pt-3">
         <?php if ($ticket_data['last_comment'] == 'Admin') : ?>
            <p class='fs-6' style="color: #FF5F15;"><strong>ALERT: NEW REPLY! </strong></p> 
       <?php else : ?>
                 <h6 class="card-title">#<?=$ticket_data['id'] ?> 
                    <?php if ($ticket_data['last_comment'] == 'Admin' and $ticket_data['ticket_status'] == 'open'): ?>
                         is currently being worked.
                     <?php elseif ($ticket_data['last_comment'] == 'Member' and $ticket_data['ticket_status'] == 'open'): ?> 
                         is currently in the queue.
                     <?php elseif ($ticket_data['ticket_status'] == 'resolved'): ?>
                         has been resolved.
                     <?php elseif ($ticket_data['ticket_status'] == 'closed'): ?>
                         has been completed, and is now closed.
                     <?php endif; ?>
 
                 </h6>
     <?php endif; ?>
       <div class="tab-content pt-2">
        <div class="tab-pane fade show active ticket-view" id="ticket-view">
 

          
          <div class="row">
<!--leftside-->
            <div class="col-lg-6">
              <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Ticket #<?=$ticket_data['id'] ?><span class='fs-7' style='float:right'> Submitted <?=date('m/d/y h:ia', strtotime($ticket_data['created']))?></span> </h6>
 <div class="block tickets view"> 

       <?php if ($ticket_data['last_comment'] == 'Member' and $ticket_data['ticket_status'] == 'open'): ?>
           
       <p class='fs-6'>We are generally able to respond and/or resolve tickets within 1-3 business days.</p>
       <?php endif; ?>        

       <?php if ($ticket_data['ticket_status'] == 'resolved'): ?>
          <p class='fs-6' style="color: #FF5F15;"><strong>If you approve the resolution, please close the ticket.</strong></p>
          <p class='fs-6'>We generally close resolved tickets within 1-2 business days.</p>
          <p class='fs-6'>If you require further action on our part, please re-open the ticket & comment.</p>
       <?php endif; ?>
       
       <?php if ($ticket_data['ticket_status'] == 'closed'): ?>
          
         <p class='fs-5'>Thank you for being here! I hope something wonderful happens to you today!</p>
       <?php endif; ?>

    <div class="profile">
        <div class="icon">
            <span style="background-color:<?=color_from_string($ticket_data['a_name'] ?? $ticket_data['full_name'])?>"><?=strtoupper(substr($ticket_data['a_name'] ?? $ticket_data['full_name'], 0, 1))?></span>
        </div><!--icon-->
        <div class="info">
            <p class="name mx-auto">&nbsp;<?=htmlspecialchars($ticket_data['a_name'] ?? $ticket_data['full_name'], ENT_QUOTES)?></p>
        </div><!--info-->
    </div><!--profile-->
    <div class="info">
            <?php if (isset($_SESSION['loggedin']) and $_SESSION['role'] == 'Admin'): ?>
            <table><td class="responsive-hidden">
            <p class="email"><?=htmlspecialchars($ticket_data['a_email'] ?? $ticket_data['email'], ENT_QUOTES)?></p>
            </td></table>
            <?php endif; ?>
   
        </div><!--info-->
 
   <div class="ticket">
     <div class='fs-6'>
      <p class='fs-6'><strong>Status: </strong> <span class="fs-6 <?=$ticket_data['ticket_status']?>"><?=$ticket_data['ticket_status']?> </span></p>
       <p class='fs-6'><strong>Category: </strong><span class="category" title="Category"><?=$ticket_data['category']?></span></p>
        </div><!--no name div tag close-->
    </div><!--ticket-->
<div style='border:solid .25px grey'>
   <p class='ms-3'><strong><?=$ticket_data['title'] ?></strong></p>
<hr>

        <p class="msg fs-6 ms-3"><?=str_ireplace(['&lt;strong&gt;','&lt;/strong&gt;','&lt;u&gt;','&lt;/u&gt;','&lt;i&gt;','&lt;/i&gt;'], ['<strong>','</strong>','<u>','</u>','<i>','</i>'], nl2br(htmlspecialchars($ticket_data['msg'] ?? '', ENT_QUOTES)))?></p>
    </div><!--ticket-->
</div><!--block tickets view-->
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
             <h6 class="card-title">Ticket Communication</h6>

<div class="comments mt-4">
          
        <?php foreach($comments as $comment): ?>
        <div class="comment">
       <div style='border:solid .25px grey'>
        <p><span class="comment-header">
                     <?php if ($comment['full_name']): ?>
                       <?php if ($comment['role'] == 'Admin'): ?>
                           <div style='float:left'>
                             <div>&nbsp;<i class="fa-solid fa-comment fa-flip-horizontal fa-1x" aria-hidden="true" style="color:#6050c9;"></i>
                          </div>
                         </div>
                       <?php endif; ?>
                       <?php if ($comment['role'] != 'Admin'): ?>
                           <div style='float:left'>
                             <div>&nbsp;<i class="fa-solid fa-comment fa-flip-horizontal fa-1x" aria-hidden="true" style="color:#c96050;"></i>
                          </div>
                         </div>
                        <?php endif; ?>
                   <span class="name<?=$comment['role'] == 'Admin' ? ' is-admin' : ''?>">&nbsp;&nbsp;<?=htmlspecialchars($comment['full_name'] ?? '', ENT_QUOTES)?></span>  
                    <?php endif; ?>
                    <span class="date"><?=date('m/d/y h:ia', strtotime($comment['created']))?> </span>
                    <?php if ($_SESSION['role'] == 'Admin'): ?>
                    <a href="admin/comment.php?id=<?=$comment['id']?>" target="_blank" class="edit"><i class="fa-solid fa-pen fa-xs"></i></a>
                    <?php endif; ?>
                </span>
               
          <p class="msg fs-7 ms-5"><?=str_ireplace(['&lt;strong&gt;','&lt;/strong&gt;','&lt;u&gt;','&lt;/u&gt;','&lt;i&gt;','&lt;/i&gt;'], ['<strong>','</strong>','<u>','</u>','<i>','</i>'], nl2br(htmlspecialchars($comment['msg'] ?? '', ENT_QUOTES)))?></span>
         </p></div>
        </div><!--comment-->
        <?php endforeach; ?>
        
<?php if ($ticket_data['ticket_status'] != 'closed'): ?>
 <form action="ticket-view.php" id='comment' class='form' method="post">
   <div class="new-comment">
     <textarea aria-labelledby="new_comment" name="new-comment" class='fs-6'  style='padding:10px; width:100%' placeholder="Enter your comment..."  maxlength="<?=max_msg_length?>" required></textarea>
   </div>
   	        <?php if ($msg): ?>
                 <p class="error-msg"><?=$msg?></p>
            <?php endif; ?>
   <span class='fs-6 mb-1'><strong>Is this resolved?</strong></span> 
<div class="d-flex justify-content-start">
  
  <label for="status"><span class='fs-6 mt-2 ms-4'>Yes (close)</span> 
      
<input name="status" aria-labelledby="status" type="radio" id="status" onclick="return confirm('Are you sure you want to permenantly close the ticket?')" value="closed">
           <span class="checkmark"></span></label> 
  
  
  <label><span class='fs-6 ms-4 mt-2'>Not yet (keep open)</span> 
<input name="status" aria-labelledby="status" checked='checked' type="radio" id="status" onclick="return confirm('Are you sure you want to open/re-open the ticket?')" value="open">
           <span class="checkmark"></span></label> 
  
  
  <?php if ($_SESSION['role'] == 'Admin'): ?>
       
            <label><span class='fs-6 ms-4 mt-2'>Res.</span>             
                <input aria-labelledby="status" id='status' type="radio" name="status" value='resolved'>
                <span class="checkmark"></span>
            </label> 
         
  <?php endif; ?> 
</div> <div class='mt-3'>
	        <?php if ($msg): ?>
                 <p class="error-msg"><?=$msg?></p>
            <?php endif; ?>
            <button type="submit" onclick="return confirm('select if this ticket has resolved the issue, or it will clear your message.)" class="btn btn-success">Post Comment</button>
 </div>
  </form>
<?php endif; ?>
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