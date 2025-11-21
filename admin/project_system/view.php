<?php
/*******************************************************************************
PROJECT SYSTEM - view.php 
LOCATION: /public_html/admin/project_system
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
DATABASE: TABLES project_tickets, project_ticket_comments, project_ticket_uploads
LOG NOTE: 2025-01-01 Created Admin only version  
*******************************************************************************/
require 'assets/includes/admin_config.php';
$path="client-dashboard/communication/";
$current_date=date('Y-m-d'); // Current date and time
// Check if the user is logged-in
check_loggedin($pdo, '../../index.php');
// Fetch account details associated with the logged-in user
$stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
$stmt->execute([ $_SESSION['id'] ]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);
// Check if the user is an admin...
if ($account['role'] != 'Admin') {
    exit('You do not have permission to access this page!');
}
// Connect to the login Database using the PDO interface
try {
	$logon_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name . ';charset=' . db_charset, db_user, db_pass);
	$logon_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	// If there is an error with the connection, stop the script and display the error.
	exit('Failed to connect to the logon database!');
}
$page = 'View';
// Check if the ID param in the URL exists
if (!isset($_GET['id'])) {
    exit('No ID specified!');
}
// output message (errors, etc)
$msg = '';
$radio="";
$status="";

$private = default_private ? 0 : 1;

// Connect to MySQL using the below function
$pdo = pdo_connect_mysql();

//MySQL query that selects the ticket by the ID column, using the ID GET request variable
$stmt = $pdo->prepare('SELECT t.*, c.title AS category FROM project_tickets t LEFT JOIN project_categories c ON c.id = t.category_id WHERE t.id = ?');
$stmt->execute([ $_GET['id'] ]);
$ticket  = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if ticket exists
if (!$ticket) {
    exit('Invalid ticket ID!');
}

// Retrieve the ticket comments from the database
$stmt = $pdo->prepare('SELECT tc.* FROM project_tickets_comments tc WHERE tc.ticket_id = ? ORDER BY tc.created');
$stmt->execute([ $_GET['id']]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Retrieve ticket uploads from the database
$stmt = $pdo->prepare('SELECT * FROM project_tickets_uploads WHERE ticket_id = ?');
$stmt->execute([ $_GET['id']]);
$ticket_uploads = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
// Fetch all the category names from the categories MySQL table
$categories = $pdo->query('SELECT * FROM project_categories')->fetchAll(PDO::FETCH_ASSOC);

// Check if POST data exists (user submitted the form)
if (isset($_POST['new-comment'])) {
    
    // Validation checks...
    if (empty($_POST['new-comment']) || empty( $_POST['status'])) {
        $msg = 'Please complete the form!';
    } else if (strlen($_POST['new-comment']) > max_msg_length) {
        $msg = 'Message must be less than ' . max_msg_length . ' characters long!';
    } else {

    // Insert the new comment into the "project_tickets_comments" table

    $stmt = $pdo->prepare('INSERT INTO project_tickets_comments (ticket_id, msg) VALUES (?, ?)');
    $stmt->execute([$_GET['id'], $_POST['new-comment'] ]);
 
    $stmt = $pdo->prepare('UPDATE project_tickets SET ticket_status = ? WHERE id = ?');
    $stmt->execute([ $_POST['status'], $_GET['id']]);
  
    //Redirect to this page
     header('Location: view.php?id=' . $_GET['id']);
    exit;
}
}

?>
<?=template_admin_header('Project Tickets', 'projects', 'manage')?>
<div class="content-title">
    <div class="title">
        <div class="icon alt">
            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21 13.34C20.37 13.12 19.7 13 19 13V5H5V18.26L6 17.6L9 19.6L12 17.6L13.04 18.29C13 18.5 13 18.76 13 19C13 19.65 13.1 20.28 13.3 20.86L12 20L9 22L6 20L3 22V3H21V13.34M17 9V7H7V9H17M15 13V11H7V13H15M18 15V18H15V20H18V23H20V20H23V18H20V15H18Z" /></svg>
        </div>
        <div class="txt">
             
            </p>
           <h2><?=htmlspecialchars($ticket['title'], ENT_QUOTES)?></h2>
           <p>Viewing Project Ticket # <?=$ticket['id']?></p>
           <?php $ticket_reminder_date = $ticket['reminder_date']?>
           <?php if($ticket['reminder_date']=='9999-12-31'):?>
           <p>This project is has no reminders set.</p>
           <?php else:?>
             <?php if ($current_date > $ticket['reminder_date']): ?>
             <span style="color:red; font-size:18">This project was due to be reviewed&nbsp;<?=time_difference_string($ticket['reminder_date'])?></span>
             <?php elseif ($current_date < $ticket['reminder_date']): ?>
             <span style="color:blue; font-size:18">It is <?=time_difference_string($ticket['reminder_date'])?> this project is due for review </span>
               <?php else: ?>  
              <span style="color:green; font-size:18">This project is due for review today.</span>
                            <?php endif; ?> 
                             <?php endif; ?>
        </div>
    </div>
</div>
<div class="content-header responsive-flex-column pad-top-5">
    <div class="btns">
       <a href="tickets.php" class="btn alt mar-right-2">Cancel</a>
       <a href="ticket.php?id=<?=$ticket['id']?>" class="btn btn-primary mar-right-2">Edit</a>
       <a class="btn btn-danger mar-right-2" href="tickets.php?delete=<?=$ticket['id']?>" onclick="return confirm('Are you sure you want to delete this ticket?')">
                                    Delete</a>
    </div>
</div>
 <div class="content-block" style="background:#EDE3FF">
    <div class="table" style="background:#FFF">
        <table>
            <thead>
                <tr>
               <td style="font-size:1.3em; text-align:left; color:#7F50AB"><?=htmlspecialchars($ticket['title'], ENT_QUOTES)?></td>
               <td colspan="3" style="font-size:1.3em; text-align:left; color:#7F50AB">Project Ticket # <?=$ticket['id']?></td>          
                </tr>
            </thead>
            <tbody>
                <tr><td colspan="3" style="font-size:1.3em; text-align:left;  <p class="msg"><?=str_ireplace(['&lt;strong&gt;','&lt;/strong&gt;','&lt;u&gt;','&lt;/u&gt;','&lt;i&gt;','&lt;/i&gt;'], ['<strong>','</strong>','<u>','</u>','<i>','</i>'], nl2br(htmlspecialchars($ticket['msg'], ENT_QUOTES)))?></p></td></tr>
                 <tr>
                     <td>Created: &nbsp;<strong><?=date('F dS, Y', strtotime($ticket['created']))?></strong></td>
                       <td> Updated: &nbsp;<strong><?=date('F dS, Y', strtotime($ticket['last_update']))?> </strong></td>
            <?php if($ticket['reminder_date']=='9999-12-31'):?>
           <td><p>This project is has no reminders set.</p></td>
           <?php else:?>
              <?php if ($current_date > $ticket['reminder_date']): ?>
              <td> Reminder: &nbsp;<strong><span style="color:red; font-size:18"><?=date('F dS, Y', strtotime($ticket_reminder_date))?></span></strong></p></td>
             <?php elseif ($current_date < $ticket['reminder_date']): ?>
             <td> Reminder: &nbsp;<strong><span style="color:blue; font-size:18"><?=date('F dS, Y', strtotime($ticket_reminder_date))?> </strong></td>
             <?php else: ?> 
             <td> Reminder: &nbsp;<strong><span style="color:green; font-size:18"><?=date('F dS, Y', strtotime($ticket_reminder_date))?> </strong></td>
                            <?php endif; ?> 
                            <?php endif; ?> 
    
               
                </tr><tr>
                    <td>Status: &nbsp;
                            <?php if ($ticket['ticket_status'] == 'open'): ?>
                            <span class="green">Open</span>
                            <?php elseif ($ticket['ticket_status'] == 'closed'): ?>
                            <span class="red">Closed</span>
                            <?php elseif ($ticket['ticket_status'] == 'paused'): ?>
                            <span class="orange">Paused</span>
                            <?php endif; ?>
                   </td>
                   <td>Priority: &nbsp;
                            <?php if ($ticket['priority'] == 'low'): ?>
                            <span class="grey">Low</span>
                            <?php elseif ($ticket['priority'] == 'medium'): ?>
                            <span class="blue">Medium</span>
                            <?php elseif ($ticket['priority'] == 'high'): ?>
                            <span class="orange">High</span>
                            <?php elseif ($ticket['priority'] == 'critical'): ?>
                            <span class="red">CRITICAL</span>
                            <?php elseif ($ticket['priority'] == 'paused'): ?>
                            <span class="grey">Paused</span>
                            <?php else: ?>
                            <span><?=$ticket['priority']?></span>
                            <?php endif; ?> </td>
                    <td><strong>Category: </strong>
                    <?php if ($ticket['category'] == 'Bug Found'): ?>
                            <span class="red">Bug Found</span>
                            <?php elseif ($ticket['category'] == 'Request'): ?>
                            <span class="red">Client Request</span>
                            <?php elseif ($ticket['category'] == 'Brand'): ?>
                            <span class="red">Client Branding</span>
                            <?php elseif ($ticket['category'] == 'Function'): ?>
                            <span class="blue">Functionality</span>
                            <?php elseif ($ticket['category'] == 'Idea'): ?>
                            <span class="grey">Paused</span>
                            <?php else: ?>
                            <span class="blue"><?=$ticket['category']?></span>
                            <?php endif; ?> </td>
                </tr>
            </tbody>
        </table>
    </div>
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
                    <p style='color:purple'></p>
                   
                    <p><a class='btn btn-sm' href="comment.php?id=<?=$comment['id']?>" target="_blank" style="background:orange; color:black">Edit Comment</a> &nbsp; <?=date('F dS, Y h:ia', strtotime($comment['created']))?>&nbsp;
                      </p>
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
 
<div class="d-flex justify-content-start">
<div style="text-align:center"> 
  <label for="status"><span class='fs-6 mt-2 ms-4'>Close</span> 
      
<input name="status" aria-labelledby="status" type="radio" value="closed" id="status" onclick="return confirm('Are you sure you want to permenantly close the ticket?')" >
           <span class="checkmark"></span></label> 
  
<label><span class='fs-6 ms-4 mt-2'>Open</span> 
<input name="status" aria-labelledby="status" checked='checked' value="open" type="radio" id="status" onclick="return confirm('Are you sure you want to open/re-open the ticket?')" >
           <span class="checkmark"></span></label> 
<label><span class='fs-6 ms-4 mt-2'>Pause</span>             
           <input aria-labelledby="status" id='status' type="radio" name="status" value='paused'>
           <span class="checkmark"></span>
</label> 
 </div>
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