<?php
/*******************************************************************************
CLIENT TICKETING SYSTEM - view.php
LOCATION: /public_html/admin/
DESCRIBE: Admin dashboard to view and respond to a particular ticket.
INPUTREQ: 
LOGGEDIN: REQUIRED ADMIN
REQUIRED:
  SYSTEM: DATABASE,LOGIN
   ADMIN: /public_html/admin/
   PAGES: This is only compatable with Admin due to passing url parms.
   FILES: 
   PARMS: 
     OUT:
DATABASE: TABLES tickets, ticket_comments, ticket_uploads
LOG NOTE: 2024-09-14 PRODUCTION  
          2024-10-19 Created Admin only version (prototype - works, though)
*******************************************************************************/
// Check if the ID param in the URL exists
if (!isset($_GET['id'])) {
    exit('No ID specified!');
}
require 'assets/includes/admin_config.php';
$path="client-dashboard/communication/";

// output message (errors, etc)
$msg = '';
$radio="";
$status="";

$private = default_private ? 0 : 1;

// Connect to MySQL using the below function
$pdo = pdo_connect_mysql();

// Retrieve additional account info from the database because we don't have them stored in sessions.
$stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
$stmt->execute([ $_SESSION['id'] ]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

//MySQL query that selects the ticket by the ID column, using the ID GET request variable 
$stmt = $pdo->prepare('SELECT t.*, a.full_name AS a_name, a.role AS a_role, a.email AS a_email, c.title AS category FROM tickets t LEFT JOIN categories c ON c.id = t.category_id LEFT JOIN accounts a ON a.id = t.acc_id WHERE t.id = ?');
$stmt->execute([ $_GET['id'] ]);
$ticket  = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if ticket exists
if (!$ticket) {
    exit('Invalid ticket ID!');
}

// Retrieve the ticket comments from the database
$stmt = $pdo->prepare('SELECT tc.*, a.full_name, a.role AS a_role FROM tickets_comments tc LEFT JOIN accounts a ON a.id = tc.acc_id WHERE tc.ticket_id = ? ORDER BY tc.created');
$stmt->execute([ $_GET['id']]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Retrieve ticket uploads from the database
$stmt = $pdo->prepare('SELECT * FROM tickets_uploads WHERE ticket_id = ?');
$stmt->execute([ $_GET['id']]);
$ticket_uploads = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Retrieve ticket admin comment tickets that have not been replied to.
$stmt = $pdo->prepare('SELECT * FROM tickets WHERE acc_id = ?  AND ticket_status != "closed" AND last_comment = "Member" ORDER BY last_update DESC');
$stmt->execute([ $_GET['id']]);
$action_required_tickets = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch all the category names from the categories MySQL table
$categories = $pdo->query('SELECT * FROM categories')->fetchAll(PDO::FETCH_ASSOC);

// Check if POST data exists (user submitted the form)
if (isset($_POST['new-comment'])) {
    
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
    $stmt->execute([$_GET['id'], $_POST['new-comment'], $account['id'], $account['role'] ]);
    $stmt = $pdo->prepare('UPDATE tickets SET last_comment = ?, ticket_status = ?, last_update = ? WHERE id = ?');
    $stmt->execute([ $account['role'], $_POST['status'], date('Y-m-d\TH:i:s'), $_GET['id']]);
    
if ($_SESSION['role'] =="Admin") { 
   // Send updated ticket email to user
    send_ticket_email($ticket['email'], $ticket['id'], $ticket['title'], $ticket['msg'], $ticket['priority'], $ticket['category'], $ticket['private'], $_POST['status'], 'comment');
}else{
    
    $admin_email = notify_admin_email;
     send_ticket_email($admin_email, $ticket['id'], $ticket['title'], $ticket['msg'], $ticket['priority'], $ticket['category'], $ticket['private'], $_POST['status'], 'notification-comment', $account['full_name'], $account['email']);
}
    //Redirect to this page
     header('Location: view.php?id=' . $_GET['id']);
    exit;
}
}

// Check if the comment form has been submitted
if (isset($_POST['msg'], $_SESSION['loggedin']) && !empty($_POST['msg']) && $ticket['ticket_status'] != 'closed') {
    // Insert the new comment into the "tickets_comments" table
    $stmt = $pdo->prepare('INSERT INTO tickets_comments (ticket_id, msg, acc_id) VALUES (?, ?, ?)');
    $stmt->execute([ $_GET['id'], $_POST['msg'], $_SESSION['id'] ]);
    
    // Send updated ticket email to user
    send_ticket_email($ticket['email'], $ticket['id'], $ticket['title'], $ticket['msg'], $ticket['priority'], $ticket['category'], $ticket['private'], $ticket['status'], 'comment');
    // Redirect to ticket page
    header('Location: view.php?id=' . $_GET['id']);
    exit;
}

?>
<?=template_admin_header('Tickets', 'tickets', 'manage')?>
<div class="content-title">
    <div class="title">
        <div class="icon alt">
            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21 13.34C20.37 13.12 19.7 13 19 13V5H5V18.26L6 17.6L9 19.6L12 17.6L13.04 18.29C13 18.5 13 18.76 13 19C13 19.65 13.1 20.28 13.3 20.86L12 20L9 22L6 20L3 22V3H21V13.34M17 9V7H7V9H17M15 13V11H7V13H15M18 15V18H15V20H18V23H20V20H23V18H20V15H18Z" /></svg>
        </div>
        <div class="txt">
                  <h2 class="responsive-width-100">Ticketing System</h2>
            <p>View Member Ticket</p>
        </div>
    </div>
</div>

 
<div class="content view">

            <h3 style='color:blue'><?=htmlspecialchars($ticket['a_name'] ?? $ticket['full_name'], ENT_QUOTES)?> </h3>
             <h3 style='color:orange'><?=$ticket['a_email'] ?? $ticket['email']?> </h3>
             <br>
             <p><strong>Status:</strong> <?=$ticket['ticket_status']?></p> 
            <p><strong>Priority </strong><?=$ticket['priority']?></p> 
            <p><strong>Category </strong><?=$ticket['category']?></p> 
            
            <p class="created"><?=date('F dS, G:ia', strtotime($ticket['created']))?></p>
        </div>
        
	<h2><?=htmlspecialchars($ticket['title'], ENT_QUOTES)?></h2>
        <p class="msg"><?=str_ireplace(['&lt;strong&gt;','&lt;/strong&gt;','&lt;u&gt;','&lt;/u&gt;','&lt;i&gt;','&lt;/i&gt;'], ['<strong>','</strong>','<u>','</u>','<i>','</i>'], nl2br(htmlspecialchars($ticket['msg'], ENT_QUOTES)))?></p>
    </div>

    <?php if (!empty($ticket_uploads)): ?>
    <h3 class="uploads-header">Attachment(s)</h3>
      <div class="uploads">
        <?php foreach($ticket_uploads as $ticket_upload): ?>
        <?php $upload_path='../../' . $path . $ticket_upload['filepath']; ?>
        <a title="download ticket" href="<?=$upload_path ?>" download>
            <?php if (getimagesize($upload_path)): ?>
               <img src="<?=$upload_path?>" width="80" height="80" alt="">
            <?php else: ?>
            <i class="fas fa-file"></i>
            <span><?=pathinfo($upload_path, PATHINFO_EXTENSION)?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div><!--uploads-->
    <?php endif; ?>

    <div class="comments">
        <?php foreach($comments as $comment): ?>
        <div class="comment">
            
            <p>
                <span class="comment-header" style='background:grey'>
             <?php if ($comment['a_role']=='Admin'): ?>
                    <p style='color:purple'>
                <?php else : ?>
                <p style='color:orange'>
                    <?php endif; ?>
                    
    <?=htmlspecialchars($comment['full_name'], ENT_QUOTES)?> </p>
                   
                    <p><a class='btn btn-sm' href="comment.php?id=<?=$comment['id']?>" target="_blank" class="edit">Edit</a> &nbsp; <?=date('F dS, G:ia', strtotime($comment['created']))?></p>

                </span>
                <?=str_ireplace(['&lt;strong&gt;','&lt;/strong&gt;','&lt;u&gt;','&lt;/u&gt;','&lt;i&gt;','&lt;/i&gt;'], ['<strong>','</strong>','<u>','</u>','<i>','</i>'], nl2br(htmlspecialchars($comment['msg'], ENT_QUOTES)))?>
            </p>
        </div>
        <?php endforeach; ?>
        <?php if (isset($_SESSION['loggedin']) && $ticket['ticket_status'] != 'closed'): ?>
 
 <form action="" id='comment' class='form' method="post">
   <div class="new-comment">
     <textarea aria-labelledby="new_comment" name="new-comment" class='fs-6'  style='padding:10px; width:100%' placeholder="Enter your comment..."  maxlength="<?=max_msg_length?>" required></textarea>
   </div>
   	        <?php if ($msg): ?>
                 <p class="error-msg"><?=$msg?></p>
            <?php endif; ?>
   <span class='fs-6 mb-1'><strong>Is this resolved?</strong></span> 
<div class="d-flex justify-content-start">
  
  <label for="status"><span class='fs-6 mt-2 ms-4'>Yes (close)</span> 
      
<input name="status" aria-labelledby="status" type="radio" value="closed" id="status" onclick="return confirm('Are you sure you want to permenantly close the ticket?')" >
           <span class="checkmark"></span></label> 
  
  
  <label><span class='fs-6 ms-4 mt-2'>Not yet (keep open)</span> 
<input name="status" aria-labelledby="status" checked='checked' value="open" type="radio" id="status" onclick="return confirm('Are you sure you want to open/re-open the ticket?')" >
           <span class="checkmark"></span></label> 
 
       
            <label><span class='fs-6 ms-4 mt-2'>Res.</span>             
                <input aria-labelledby="status" id='status' type="radio" name="status" value='resolved'>
                <span class="checkmark"></span>
            </label> 
  
</div>
           <div class='mt-3'>
	        <?php if ($msg): ?>
                 <p class="error-msg"><?=$msg?></p>
            <?php endif; ?>
</div>
 <div class='mt-3'>
            <button type="submit" style='background:green; color:white' onclick="return confirm('select if this ticket has resolved the issue, or it will clear your message.)" class="btn btn-sm">Post Comment</button>
 </div>
  </form>
        <?php endif; ?>
    </div>

</div>

<?=template_admin_footer()?> 