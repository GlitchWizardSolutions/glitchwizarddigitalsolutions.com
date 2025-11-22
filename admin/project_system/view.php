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
$ticket_uploads_path = public_path . "client-dashboard/communication/project-ticket-uploads/";
$ticket_uploads_url = BASE_URL . "client-dashboard/communication/project-ticket-uploads/";
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
<?=template_admin_header('Project Tickets', 'ticketing', 'project')?>
<div class="content-title">
    <div class="title">
        <div class="icon alt">
            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21 13.34C20.37 13.12 19.7 13 19 13V5H5V18.26L6 17.6L9 19.6L12 17.6L13.04 18.29C13 18.5 13 18.76 13 19C13 19.65 13.1 20.28 13.3 20.86L12 20L9 22L6 20L3 22V3H21V13.34M17 9V7H7V9H17M15 13V11H7V13H15M18 15V18H15V20H18V23H20V20H23V18H20V15H18Z" /></svg>
        </div>
        <div class="txt">
            <h2 class="responsive-width-100">View Project Ticket</h2>
            <p>Development project details and progress</p>
        </div>
    </div>
</div>

<div class="form-professional" style="max-width: 1200px;">
    
    <!-- Project Header Section -->
    <div class="form-section">
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px;">
            <div style="flex: 1;">
                <h2 style="margin: 0 0 10px 0; color: #6b46c1;"><?=htmlspecialchars($ticket['title'], ENT_QUOTES)?></h2>
                <p style="margin: 0; color: #666; font-size: 14px;">
                    <strong>Ticket #<?=$ticket['id']?></strong> • Created: <?=date('F dS, Y', strtotime($ticket['created']))?>
                    • Last Updated: <?=date('F dS, Y', strtotime($ticket['last_update']))?>
                </p>
                
                <?php $ticket_reminder_date = $ticket['reminder_date']?>
                <?php if($ticket['reminder_date']!='9999-12-31'): ?>
                <p style="margin: 10px 0 0 0; padding: 8px 12px; border-radius: 6px; display: inline-block; font-size: 13px; font-weight: 500;
                    <?php if ($current_date > $ticket['reminder_date']): ?>
                        background: #fee; color: #c00; border: 1px solid #fcc;
                    <?php elseif ($current_date < $ticket['reminder_date']): ?>
                        background: #e6f2ff; color: #0066cc; border: 1px solid #b3d9ff;
                    <?php else: ?>
                        background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7;
                    <?php endif; ?>">
                    <i class="fas fa-bell" style="margin-right: 6px;"></i>
                    <?php if ($current_date > $ticket['reminder_date']): ?>
                        Review was due <?=time_difference_string($ticket['reminder_date'])?>
                    <?php elseif ($current_date < $ticket['reminder_date']): ?>
                        Review due <?=time_difference_string($ticket['reminder_date'])?>
                    <?php else: ?>
                        Review due today
                    <?php endif; ?>
                    (<?=date('F dS, Y', strtotime($ticket_reminder_date))?>)
                </p>
                <?php else: ?>
                <p style="margin: 10px 0 0 0; color: #999; font-size: 13px;">
                    <i class="fas fa-info-circle" style="margin-right: 4px;"></i> No review reminder set
                </p>
                <?php endif; ?>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <a href="tickets.php" class="btn btn-secondary">← Back to List</a>
                <a href="ticket.php?id=<?=$ticket['id']?>" class="btn btn-primary">Edit</a>
                <a class="btn btn-danger" href="tickets.php?delete=<?=$ticket['id']?>" onclick="return confirm('Are you sure you want to delete this ticket?')">Delete</a>
            </div>
        </div>

        <div class="form-row" style="margin-top: 20px;">
            <div class="form-group">
                <label><strong>Status</strong></label>
                <div>
                    <?php if ($ticket['ticket_status'] == 'open'): ?>
                        <span class="green" style="display: inline-block; padding: 6px 12px; border-radius: 4px; font-weight: 500;">Open</span>
                    <?php elseif ($ticket['ticket_status'] == 'closed'): ?>
                        <span class="grey" style="display: inline-block; padding: 6px 12px; border-radius: 4px; font-weight: 500;">Closed</span>
                    <?php elseif ($ticket['ticket_status'] == 'paused'): ?>
                        <span style="display: inline-block; padding: 6px 12px; border-radius: 4px; font-weight: 500; background: #fff3e0; color: #e65100;">Paused</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label><strong>Priority</strong></label>
                <div>
                    <?php if ($ticket['priority'] == 'low'): ?>
                        <span class="grey" style="display: inline-block; padding: 6px 12px; border-radius: 4px; font-weight: 500;">Low</span>
                    <?php elseif ($ticket['priority'] == 'medium'): ?>
                        <span class="blue" style="display: inline-block; padding: 6px 12px; border-radius: 4px; font-weight: 500;">Medium</span>
                    <?php elseif ($ticket['priority'] == 'high'): ?>
                        <span style="display: inline-block; padding: 6px 12px; border-radius: 4px; font-weight: 500; background: #fff3e0; color: #e65100;">High</span>
                    <?php elseif ($ticket['priority'] == 'critical'): ?>
                        <span style="display: inline-block; padding: 6px 12px; border-radius: 4px; font-weight: 500; background: #fee; color: #c00;">CRITICAL</span>
                    <?php else: ?>
                        <span style="display: inline-block; padding: 6px 12px; border-radius: 4px; font-weight: 500;"><?=$ticket['priority']?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="form-group" style="margin-top: 15px;">
            <label><strong>Category</strong></label>
            <div>
                <?php if ($ticket['category'] == 'Bug Found'): ?>
                    <span style="color: #c00; font-weight: 500;">🐛 Bug Found</span>
                <?php elseif ($ticket['category'] == 'Request'): ?>
                    <span style="color: #c00; font-weight: 500;">📋 Client Request</span>
                <?php elseif ($ticket['category'] == 'Brand'): ?>
                    <span style="color: #c00; font-weight: 500;">🎨 Client Branding</span>
                <?php elseif ($ticket['category'] == 'Function'): ?>
                    <span style="color: #4a90e2; font-weight: 500;">⚙️ Functionality</span>
                <?php elseif ($ticket['category'] == 'Idea'): ?>
                    <span style="color: #999; font-weight: 500;">💡 Idea</span>
                <?php else: ?>
                    <span style="color: #666;"><?=$ticket['category']?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Description Section -->
    <div class="form-section">
        <h3 class="section-title">Project Description</h3>
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
        <h3 class="section-title">Project Notes</h3>
        
        <?php if (empty($comments)): ?>
        <p style="color: #999; font-style: italic; padding: 20px; text-align: center; background: white; border-radius: 6px;">No notes yet</p>
        <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 15px;">
            <?php foreach($comments as $comment): ?>
            <div style="padding: 20px; background: white; border-left: 4px solid #6b46c1; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                    <div>
                        <strong style="color: #6b46c1; font-size: 15px;">Development Team</strong>
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
            <h4 style="margin: 0 0 15px 0; color: #6b46c1; font-size: 16px;">Add Project Note</h4>
            
            <?php if ($msg): ?>
            <div style="padding: 12px; background: #fee; border: 1px solid #fcc; border-radius: 6px; color: #c00; margin-bottom: 15px;">
                <?=$msg?>
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="new-comment">Note / Update <span class="required">*</span></label>
                <textarea id="new-comment" name="new-comment" rows="5" placeholder="Enter project note or update..." maxlength="<?=max_msg_length?>" required></textarea>
            </div>

            <div class="form-group" style="margin-top: 20px;">
                <label><strong>Update Status</strong></label>
                <div style="display: flex; gap: 20px; flex-wrap: wrap; padding: 15px; background: white; border-radius: 6px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 8px 12px; border: 2px solid #ddd; border-radius: 6px; transition: all 0.3s;" onmouseover="this.style.borderColor='#6b46c1'" onmouseout="if(!this.querySelector('input').checked) this.style.borderColor='#ddd'">
                        <input type="radio" name="status" value="open" checked style="width: 18px; height: 18px; cursor: pointer;" onclick="return confirm('Are you sure you want to open/re-open the ticket?')">
                        <span style="font-weight: 500;">Keep Open</span>
                    </label>
                    
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 8px 12px; border: 2px solid #ddd; border-radius: 6px; transition: all 0.3s;" onmouseover="this.style.borderColor='#e65100'" onmouseout="if(!this.querySelector('input').checked) this.style.borderColor='#ddd'">
                        <input type="radio" name="status" value="paused" style="width: 18px; height: 18px; cursor: pointer;">
                        <span style="font-weight: 500; color: #e65100;">Pause Project</span>
                    </label>
                    
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 8px 12px; border: 2px solid #ddd; border-radius: 6px; transition: all 0.3s;" onmouseover="this.style.borderColor='#999'" onmouseout="if(!this.querySelector('input').checked) this.style.borderColor='#ddd'">
                        <input type="radio" name="status" value="closed" style="width: 18px; height: 18px; cursor: pointer;" onclick="return confirm('Are you sure you want to permanently close the ticket?')">
                        <span style="font-weight: 500; color: #999;">Close/Complete</span>
                    </label>
                </div>
            </div>

            <div class="form-actions" style="margin-top: 20px;">
                <button type="submit" class="btn btn-primary">Post Note</button>
            </div>
        </form>
        <?php endif; ?>
    </div>

</div>

<?=template_admin_footer()?> 