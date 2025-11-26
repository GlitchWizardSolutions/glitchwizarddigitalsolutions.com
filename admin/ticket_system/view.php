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
$ticket_uploads_path = public_path . "client-dashboard/communication/ticket-uploads/";
$ticket_uploads_url = BASE_URL . "client-dashboard/communication/ticket-uploads/";

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
<?=template_admin_header('Tickets', 'ticketing', 'client')?>
<div class="content-title mb-3">
    <div class="title">
        <div class="icon alt">
            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21 13.34C20.37 13.12 19.7 13 19 13V5H5V18.26L6 17.6L9 19.6L12 17.6L13.04 18.29C13 18.5 13 18.76 13 19C13 19.65 13.1 20.28 13.3 20.86L12 20L9 22L6 20L3 22V3H21V13.34M17 9V7H7V9H17M15 13V11H7V13H15M18 15V18H15V20H18V23H20V20H23V18H20V15H18Z" /></svg>
        </div>
        <div class="txt">
            <h2 class="responsive-width-100">View Client Ticket</h2>
            <p>Client support ticket details and communication</p>
        </div>
    </div>
</div>

<div class="form-professional" style="max-width: 1200px;">
    
    <!-- Ticket Header Section -->
    <div class="form-section">
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px;">
            <div>
                <h2 style="margin: 0 0 10px 0; color: #6b46c1;"><?=htmlspecialchars($ticket['title'], ENT_QUOTES)?></h2>
                <p style="margin: 0; color: #666; font-size: 14px;">
                    <strong>Client:</strong> <?=htmlspecialchars($ticket['a_name'] ?? $ticket['full_name'], ENT_QUOTES)?> 
                    (<?=htmlspecialchars($ticket['a_email'] ?? $ticket['email'], ENT_QUOTES)?>)
                </p>
                <p style="margin: 5px 0 0 0; color: #666; font-size: 14px;">
                    <strong>Created:</strong> <?=date('F dS, Y h:ia', strtotime($ticket['created']))?>
                </p>
            </div>
            <a href="tickets.php" class="btn btn-secondary">← Back to List</a>
        </div>

        <div class="form-row" style="margin-top: 20px;">
            <div class="form-group">
                <label><strong>Status</strong></label>
                <div>
                    <span class="<?=$ticket['ticket_status']=='resolved'?'blue':($ticket['ticket_status']=='open'?'green':'grey')?>" style="display: inline-block; padding: 6px 12px; border-radius: 4px; font-weight: 500; text-transform: capitalize;">
                        <?=ucwords($ticket['ticket_status'])?>
                    </span>
                </div>
            </div>
            
            <div class="form-group">
                <label><strong>Priority</strong></label>
                <div>
                    <span style="display: inline-block; padding: 6px 12px; border-radius: 4px; background: <?=$ticket['priority']=='high'?'#fee':'#efefef'?>; color: <?=$ticket['priority']=='high'?'#c00':'#666'?>; font-weight: 500; text-transform: capitalize;">
                        <?=ucwords($ticket['priority'])?>
                    </span>
                </div>
            </div>
        </div>

        <div class="form-group" style="margin-top: 15px;">
            <label><strong>Category</strong></label>
            <div style="color: #666;"><?=htmlspecialchars($ticket['category'], ENT_QUOTES)?></div>
        </div>
    </div>

    <!-- Message Section -->
    <div class="form-section">
        <h3 class="section-title">Ticket Message</h3>
        <div style="padding: 15px; background: white; border: 1px solid #e0d4f7; border-radius: 6px; line-height: 1.6;">
            <?=str_ireplace(['&lt;strong&gt;','&lt;/strong&gt;','&lt;u&gt;','&lt;/u&gt;','&lt;i&gt;','&lt;/i&gt;'], ['<strong>','</strong>','<u>','</u>','<i>','</i>'], nl2br(htmlspecialchars($ticket['msg'], ENT_QUOTES)))?>
        </div>
    </div>

    <?php if (!empty($ticket_uploads)): ?>
    <!-- Attachments Section -->
    <div class="form-section">
        <h3 class="section-title">Attachments</h3>
        <div style="display: flex; flex-wrap: wrap; gap: 15px;">
            <?php foreach($ticket_uploads as $ticket_upload): ?>
            <?php 
                $upload_file_path = $ticket_uploads_path . $ticket_upload['filepath'];
                $upload_url = $ticket_uploads_url . $ticket_upload['filepath'];
            ?>
            <a href="<?=$upload_url ?>" download style="display: flex; flex-direction: column; align-items: center; padding: 15px; border: 2px dashed #9f7aea; border-radius: 8px; text-decoration: none; background: white; transition: all 0.3s;" onmouseover="this.style.borderColor='#6b46c1'; this.style.background='#f8f4ff'" onmouseout="this.style.borderColor='#9f7aea'; this.style.background='white'">
                <?php if (file_exists($upload_file_path) && @getimagesize($upload_file_path)): ?>
                    <img src="<?=$upload_url?>" width="100" height="100" alt="" style="border-radius: 4px; object-fit: cover;">
                <?php else: ?>
                    <i class="fas fa-file" style="font-size: 48px; color: #6b46c1;"></i>
                    <span style="margin-top: 8px; font-size: 12px; color: #666; text-transform: uppercase;"><?=pathinfo($upload_file_path, PATHINFO_EXTENSION)?></span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Comments Section -->
    <div class="form-section">
        <h3 class="section-title">Conversation</h3>
        
        <?php if (empty($comments)): ?>
        <p style="color: #999; font-style: italic; padding: 20px; text-align: center; background: white; border-radius: 6px;">No comments yet</p>
        <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 15px;">
            <?php foreach($comments as $comment): ?>
            <div style="padding: 20px; background: white; border-left: 4px solid <?=$comment['a_role']=='Admin'?'#6b46c1':'#4a90e2'?>; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                    <div>
                        <strong style="color: <?=$comment['a_role']=='Admin'?'#6b46c1':'#4a90e2'?>; font-size: 15px;">
                            <?=htmlspecialchars($comment['full_name'], ENT_QUOTES)?>
                        </strong>
                        <span style="color: #999; font-size: 12px; margin-left: 10px;">
                            <?=$comment['a_role']=='Admin'?'(Support Team)':'(Client)'?>
                        </span>
                    </div>
                    <div style="text-align: right;">
                        <div style="color: #999; font-size: 13px; margin-bottom: 5px;">
                            <?=date('F dS, Y h:ia', strtotime($comment['created']))?>
                        </div>
                        <a href="comment.php?id=<?=$comment['id']?>" target="_blank" class="btn" style="font-size: 11px; padding: 4px 10px; background: #6b46c1; color: white; text-decoration: none; border-radius: 4px;">
                            Edit
                        </a>
                    </div>
                </div>
                <div style="color: #444; line-height: 1.6;">
                    <?=str_ireplace(['&lt;strong&gt;','&lt;/strong&gt;','&lt;u&gt;','&lt;/u&gt;','&lt;i&gt;','&lt;/i&gt;'], ['<strong>','</strong>','<u>','</u>','<i>','</i>'], nl2br(htmlspecialchars($comment['msg'], ENT_QUOTES)))?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['loggedin']) && $ticket['ticket_status'] != 'closed'): ?>
        <!-- Add New Comment Form -->
        <form action="" method="post" style="margin-top: 25px; padding: 25px; background: #f8f4ff; border: 2px solid #9f7aea; border-radius: 8px;">
            <h4 style="margin: 0 0 15px 0; color: #6b46c1; font-size: 16px;">Add Comment</h4>
            
            <?php if ($msg): ?>
            <div style="padding: 12px; background: #fee; border: 1px solid #fcc; border-radius: 6px; color: #c00; margin-bottom: 15px;">
                <?=$msg?>
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="new-comment">Comment <span class="required">*</span></label>
                <textarea id="new-comment" name="new-comment" rows="5" placeholder="Enter your comment..." maxlength="<?=max_msg_length?>" required></textarea>
            </div>

            <div class="form-group" style="margin-top: 20px;">
                <label><strong>Update Status</strong></label>
                <div style="display: flex; gap: 20px; flex-wrap: wrap; padding: 15px; background: white; border-radius: 6px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 8px 12px; border: 2px solid #ddd; border-radius: 6px; transition: all 0.3s;" onmouseover="this.style.borderColor='#6b46c1'" onmouseout="if(!this.querySelector('input').checked) this.style.borderColor='#ddd'">
                        <input type="radio" name="status" value="open" checked style="width: 18px; height: 18px; cursor: pointer;" onclick="return confirm('Are you sure you want to open/re-open the ticket?')">
                        <span style="font-weight: 500;">Keep Open</span>
                    </label>
                    
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 8px 12px; border: 2px solid #ddd; border-radius: 6px; transition: all 0.3s;" onmouseover="this.style.borderColor='#4a90e2'" onmouseout="if(!this.querySelector('input').checked) this.style.borderColor='#ddd'">
                        <input type="radio" name="status" value="resolved" style="width: 18px; height: 18px; cursor: pointer;">
                        <span style="font-weight: 500; color: #4a90e2;">Mark Resolved</span>
                    </label>
                    
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 8px 12px; border: 2px solid #ddd; border-radius: 6px; transition: all 0.3s;" onmouseover="this.style.borderColor='#c00'" onmouseout="if(!this.querySelector('input').checked) this.style.borderColor='#ddd'">
                        <input type="radio" name="status" value="closed" style="width: 18px; height: 18px; cursor: pointer;" onclick="return confirm('Are you sure you want to permanently close the ticket?')">
                        <span style="font-weight: 500; color: #c00;">Close Permanently</span>
                    </label>
                </div>
            </div>

            <div class="form-actions" style="margin-top: 20px;">
                <button type="submit" class="btn btn-success">Post Comment</button>
            </div>
        </form>
        <?php endif; ?>
    </div>

</div>

<?=template_admin_footer()?> 