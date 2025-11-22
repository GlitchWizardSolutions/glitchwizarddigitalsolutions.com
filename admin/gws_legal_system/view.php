<?php
/*******************************************************************************
GWS LEGAL SYSTEM - view.php  (Emails disabled)
LOCATION: /public_html/admin/gws_legal_system
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
DATABASE: TABLES gws_legal, gws_legal_comments, gws_legal_uploads
LOG NOTE: 2025-01-01 Created Admin only version  
*******************************************************************************/
// Check if the ID param in the URL exists
if (!isset($_GET['id'])) {
    exit('No ID specified!');
}
require 'assets/includes/admin_config.php';
$path="client-dashboard/gws-legal/";

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

//MySQL query that selects the ticket by the ID column, using the ID GET request variable & approval ==1
$stmt = $pdo->prepare('SELECT t.*, a.full_name AS a_name, a.role AS a_role, a.email AS a_email, c.title AS category FROM gws_legal t LEFT JOIN gws_legal_categories c ON c.id = t.category_id LEFT JOIN accounts a ON a.id = t.acc_id WHERE t.id = ?');
$stmt->execute([ $_GET['id'] ]);
$ticket  = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if ticket exists
if (!$ticket) {
    exit('Invalid ticket ID!');
}

// Retrieve the ticket comments from the database
$stmt = $pdo->prepare('SELECT tc.*, a.full_name, a.role AS a_role FROM gws_legal_comments tc LEFT JOIN accounts a ON a.id = tc.acc_id WHERE tc.ticket_id = ? ORDER BY tc.created');
$stmt->execute([ $_GET['id']]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Retrieve ticket uploads from the database
$stmt = $pdo->prepare('SELECT * FROM gws_legal_uploads WHERE ticket_id = ?');
$stmt->execute([ $_GET['id']]);
$ticket_uploads = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Retrieve ticket admin comment tickets that have not been replied to.
$stmt = $pdo->prepare('SELECT * FROM gws_legal WHERE acc_id = ?  AND ticket_status != "closed"  ORDER BY last_update DESC');
$stmt->execute([ $_GET['id']]);
$action_required_tickets = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch all the category names from the categories MySQL table
$categories = $pdo->query('SELECT * FROM gws_legal_categories')->fetchAll(PDO::FETCH_ASSOC);

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
    $stmt = $pdo->prepare('INSERT INTO gws_legal_comments (ticket_id, msg, acc_id, new) VALUES (?, ?, ?, ?)');
    $stmt->execute([$_GET['id'], $_POST['new-comment'], $account['id'], $account['role'] ]);
    
    //update the status in tickets, and last comment
    
        $last_comment='Admin';
    
    $stmt = $pdo->prepare('UPDATE gws_legal SET last_comment = ?, ticket_status = ?, last_update = ? WHERE id = ?');
    $stmt->execute([ $last_comment, $_POST['status'], date('Y-m-d\TH:i:s'), $_GET['id']]);
 
    //Redirect to this page
     header('Location: view.php?id=' . $_GET['id']);
    exit;
}
}

// Check if the comment form has been submitted
if (isset($_POST['msg'], $_SESSION['loggedin']) && !empty($_POST['msg']) && $ticket['ticket_status'] != 'closed') {
    // Insert the new comment into the "tickets_comments" table
    $stmt = $pdo->prepare('INSERT INTO gws_legal_comments (ticket_id, msg, acc_id) VALUES (?, ?, ?)');
    $stmt->execute([ $_GET['id'], $_POST['msg'], $_SESSION['id'] ]);
 
    // Redirect to ticket page
    header('Location: view.php?id=' . $_GET['id']);
    exit;
}

?>
<?=template_admin_header('GWS Legal Req', 'ticketing', 'legal')?>
<div class="content-title">
    <div class="title">
        <div class="icon alt">
            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z" /></svg>
        </div>
        <div class="txt">
            <h2 class="responsive-width-100">View Legal Requirement</h2>
            <p>Legal filing details and notes</p>
        </div>
    </div>
</div>

<div class="form-professional" style="max-width: 1200px;">
    
    <!-- Document Header Section -->
    <div class="form-section">
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px;">
            <div>
                <h2 style="margin: 0 0 10px 0; color: #6b46c1;"><?=htmlspecialchars($ticket['title'], ENT_QUOTES)?></h2>
                <p style="margin: 0; color: #666; font-size: 14px;">
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

    <!-- Description Section -->
    <div class="form-section">
        <h3 class="section-title">Description / Notes</h3>
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
            <?php $upload_path='../../' . $path . $ticket_upload['filepath']; ?>
            <a href="<?=$upload_path ?>" download style="display: flex; flex-direction: column; align-items: center; padding: 15px; border: 2px dashed #9f7aea; border-radius: 8px; text-decoration: none; background: white; transition: all 0.3s;" onmouseover="this.style.borderColor='#6b46c1'; this.style.background='#f8f4ff'" onmouseout="this.style.borderColor='#9f7aea'; this.style.background='white'">
                <?php if (@getimagesize($upload_path)): ?>
                    <img src="<?=$upload_path?>" width="100" height="100" alt="" style="border-radius: 4px; object-fit: cover;">
                <?php else: ?>
                    <i class="fas fa-file" style="font-size: 48px; color: #6b46c1;"></i>
                    <span style="margin-top: 8px; font-size: 12px; color: #666; text-transform: uppercase;"><?=pathinfo($upload_path, PATHINFO_EXTENSION)?></span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Comments Section -->
    <div class="form-section">
        <h3 class="section-title">Discussion Notes</h3>
        
        <?php if (empty($comments)): ?>
        <p style="color: #999; font-style: italic; padding: 20px; text-align: center; background: white; border-radius: 6px;">No notes yet</p>
        <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 15px;">
            <?php foreach($comments as $comment): ?>
            <div style="padding: 20px; background: white; border-left: 4px solid <?=$comment['a_role']=='Admin'?'#6b46c1':'#9f7aea'?>; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                    <div>
                        <strong style="color: <?=$comment['a_role']=='Admin'?'#6b46c1':'#666'?>; font-size: 15px;">
                            <?=htmlspecialchars($comment['full_name'], ENT_QUOTES)?>
                        </strong>
                        <span style="color: #999; font-size: 12px; margin-left: 10px;">
                            <?=$comment['a_role']=='Admin'?'(Admin)':''?>
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
        <!-- Add New Note Form -->
        <form action="" method="post" style="margin-top: 25px; padding: 25px; background: #f8f4ff; border: 2px solid #9f7aea; border-radius: 8px;">
            <h4 style="margin: 0 0 15px 0; color: #6b46c1; font-size: 16px;">Add New Note</h4>
            
            <?php if ($msg): ?>
            <div style="padding: 12px; background: #fee; border: 1px solid #fcc; border-radius: 6px; color: #c00; margin-bottom: 15px;">
                <?=$msg?>
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="new-comment">Note / Comment <span class="required">*</span></label>
                <textarea id="new-comment" name="new-comment" rows="5" placeholder="Enter your note or comment..." maxlength="<?=max_msg_length?>" required></textarea>
            </div>

            <div class="form-group" style="margin-top: 20px;">
                <label><strong>Update Status</strong></label>
                <div style="display: flex; gap: 20px; flex-wrap: wrap; padding: 15px; background: white; border-radius: 6px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 8px 12px; border: 2px solid #ddd; border-radius: 6px; transition: all 0.3s;" onmouseover="this.style.borderColor='#6b46c1'" onmouseout="if(!this.querySelector('input').checked) this.style.borderColor='#ddd'">
                        <input type="radio" name="status" value="open" checked style="width: 18px; height: 18px; cursor: pointer;">
                        <span style="font-weight: 500;">Keep Open</span>
                    </label>
                    
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 8px 12px; border: 2px solid #ddd; border-radius: 6px; transition: all 0.3s;" onmouseover="this.style.borderColor='#4a90e2'" onmouseout="if(!this.querySelector('input').checked) this.style.borderColor='#ddd'">
                        <input type="radio" name="status" value="resolved" style="width: 18px; height: 18px; cursor: pointer;">
                        <span style="font-weight: 500; color: #4a90e2;">Mark Resolved</span>
                    </label>
                    
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 8px 12px; border: 2px solid #ddd; border-radius: 6px; transition: all 0.3s;" onmouseover="this.style.borderColor='#c00'" onmouseout="if(!this.querySelector('input').checked) this.style.borderColor='#ddd'">
                        <input type="radio" name="status" value="closed" style="width: 18px; height: 18px; cursor: pointer;" onclick="return confirm('Are you sure you want to permanently close this ticket?')">
                        <span style="font-weight: 500; color: #c00;">Close Permanently</span>
                    </label>
                </div>
            </div>

            <div class="form-actions" style="margin-top: 20px;">
                <button type="submit" class="btn btn-success">Post Note</button>
            </div>
        </form>
        <?php endif; ?>
    </div>

</div>

<?=template_admin_footer()?> 