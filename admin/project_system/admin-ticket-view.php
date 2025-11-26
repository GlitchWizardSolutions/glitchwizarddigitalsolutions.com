<?php
/*******************************************************************************
PROJECT SYSTEM - tickets.php
LOCATION: /public_html/admin/
DESCRIBE: Admin dashboard to view, edit, delete, import, export, create tickets
INPUTREQ: 
LOGGEDIN: REQUIRED
REQUIRED:
  SYSTEM: DATABASE,LOGIN
   ADMIN: /public_html/admin/
   PAGES: tickets_import.php,tickets_export.php,ticket.php
   FILES: 
   PARMS: 
     OUT:
DATABASE: TABLES tickets,ticket_comments,ticket_uploads
LOG NOTE: PRODUCTION 2024-09-14 
*******************************************************************************/
require 'assets/includes/admin_config.php';
$last_comment_admin = '0';
$last_comment_member = '1';
// Connect to MySQL using the below function
$pdo = pdo_connect_mysql();
// Check if the ID param in the URL exists
if (!isset($_GET['id'])) {
    exit('No ID specified!');
}
// MySQL query that selects the ticket by the ID column, using the ID GET request variable
$stmt = $pdo->prepare('SELECT t.*, a.full_name AS a_name, a.email AS a_email, c.title AS category FROM project_tickets t LEFT JOIN project_categories c ON c.id = t.category_id LEFT JOIN accounts a ON a.id = t.acc_id WHERE t.id = ?');
$stmt->execute([ $_GET['id'] ]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

// Retrieve ticket uploads from the database
$stmt = $pdo->prepare('SELECT * FROM project_tickets_uploads WHERE ticket_id = ?');
$stmt->execute([ $_GET['id']]);
$ticket_uploads = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if ticket exists
if (!$ticket) {
    exit('Invalid ticket ID!');
}

// Retrieve the ticket comments from the database
$stmt = $pdo->prepare('SELECT tc.*, a.full_name, a.role FROM project_tickets_comments tc LEFT JOIN accounts a ON a.id = tc.acc_id WHERE tc.ticket_id = ? ORDER BY tc.created');
$stmt->execute([ $_GET['id'] ]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Update status
if (isset($_GET['status'], $_SESSION['loggedin']) && ($_SESSION['role'] == 'Admin' || $_SESSION['id'] == $ticket['acc_id']) && in_array($_GET['status'], ['closed', 'resolved']) && $ticket['ticket_status'] == 'open') {
    // Update ticket status in the database
    $stmt = $pdo->prepare('UPDATE project_tickets SET ticket_status = ? WHERE id = ?');
    $stmt->execute([ $_GET['status'], $_GET['id'] ]);
 /*   // Send updated ticket email to user
    send_ticket_email($ticket['email'], $ticket['id'], $ticket['title'], $ticket['msg'], $ticket['priority'], $ticket['category'], $ticket['private'], $_GET['status'], 'update');
  */  
    // Redirect to ticket page
    header('Location: admin-ticket-view.php?id=' . $_GET['id']);
    exit;
}
// Check if the comment form has been submitted
if (isset($_POST['msg'], $_SESSION['loggedin']) && !empty($_POST['msg']) && $ticket['ticket_status'] == 'open') {
    // Insert the new comment into the "project_tickets_comments" table
    $stmt = $pdo->prepare('INSERT INTO project_tickets_comments (ticket_id, msg, id) VALUES (?, ?, ?)');
    $stmt->execute([ $_GET['id'], $_POST['msg'], $_SESSION['id'] ]);
/*    // Send updated ticket email to user
    send_ticket_email($ticket['email'], $ticket['id'], $ticket['title'], $ticket['msg'], $ticket['priority'], $ticket['category'], $ticket['private'], $ticket['status'], 'comment');
*/    
    // Redirect to ticket page
    header('Location: admin-ticket-view.php?id=' . $_GET['id']);
    exit;
}
// Retrieve the ticket comments from the database
$stmt = $pdo->prepare('SELECT tc.*, a.full_name, a.role FROM project_tickets_comments tc LEFT JOIN accounts a ON a.id = tc.acc_id WHERE tc.ticket_id = ? ORDER BY tc.created');
$stmt->execute([ $_GET['id'] ]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
//Display webpage on browser
?>   
<?=template_admin_header('View Project Ticket', 'ticketing', 'projects')?>
<div class="content-title mb-3">
    <div class="title">
        <div class="icon alt">
            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21 13.34C20.37 13.12 19.7 13 19 13V5H5V18.26L6 17.6L9 19.6L12 17.6L13.04 18.29C13 18.5 13 18.76 13 19C13 19.65 13.1 20.28 13.3 20.86L12 20L9 22L6 20L3 22V3H21V13.34M17 9V7H7V9H17M15 13V11H7V13H15M18 15V18H15V20H18V23H20V20H23V18H20V15H18Z" /></svg>
        </div>
        <div class="txt">
                  <h2 class="responsive-width-100">Ticketing System</h2>
            <p>View Member Tickets</p>
        </div>
    </div>
</div>

<section class="section profile">
<div class="col-xl-12 col-lg-12 col-sm-12">
<div class="card">
<div class="card-body pt-3">
<div class="tab-content pt-2">
  <h5 class="card-title">Viewing Ticket</h5>
    <div class="row">
     <div class="col-lg-12">
       <div class="card">
            <div class="card-body">
              <h6 class="card-title"><?=$ticket['title'] ?></h6>
                    
    <div class="block tickets view"> 
  <h2</h2> 
       <?php if ($ticket['last_comment'] == $last_comment_admin and $ticket['ticket_status'] == 'open'): ?>
           <p><span class="<?=$ticket['ticket_status']?>">This ticket is currently being worked.</span></p>
           <p style="background-color: #FF5F15;"><strong>Please log in & respond to the message from your Webmaster.</strong></p>
           <a href='https://glitchwizarddigitalsolutions.com' class='btn btn-success'>LOG IN </a>
       <?php endif; ?>

       <?php if ($ticket['last_comment'] == $last_comment_member and $ticket['ticket_status'] == 'open'): ?>
           <p><span class="<?=$ticket['ticket_status']?>">This ticket is currently in the queue.</span>
           <br>We are generally able to respond and/or resolve tickets within 1-2 business days.<br>
           You must log in to add further comments to your ticket.</p>
            <a href='https://glitchwizarddigitalsolutions.com' class='btn btn-success'>LOG IN </a>
       <?php endif; ?>        

       <?php if ($ticket['ticket_status'] == 'resolved'): ?>
          <p><span class="<?=$ticket['ticket_status']?>">This ticket has been resolved.</span>
          <p>We generally close tickets that have been resolved within 1-2 business days. <br>
             If you require further action on our part, you must log in to respond & keep the ticket open.</p>
           <a href='https://glitchwizarddigitalsolutions.com' class='btn btn-success'>LOG IN </a>
       <?php endif; ?>
       
       <?php if ($ticket['ticket_status'] == 'closed'): ?>
          <p><span class="<?=$ticket['ticket_status']?>">This ticket has been completed.</span>
          <br>Thank you for being here! I hope something wonderful happens to you today!</p>
       <?php endif; ?>
 
	<h2><span class="<?=$ticket['ticket_status']?>"><?=$ticket['ticket_status']?></span></h2>

    <div class="profile">
        <div class="icon">
            <span style="background-color:<?=color_from_string($ticket['a_name'] ?? $ticket['full_name'])?>"><?=strtoupper(substr($ticket['a_name'] ?? $ticket['full_name'], 0, 1))?></span>
        </div><!--icon-->
        <div class="info">
            <p class="name"><?=htmlspecialchars($ticket['a_name'] ?? $ticket['full_name'], ENT_QUOTES)?></p>
            <?php if (isset($_SESSION['loggedin']) and $_SESSION['role'] == 'Admin'): ?>
            <p class="email"><?=$ticket['a_email'] ?? $ticket['email']?></p>
            <?php endif; ?>
        </div><!--info-->
    </div><!--profile-->

    <div class="ticket">
        <div>
            <p>
                <strong>Priority: </strong> <span class="priority label <?=$ticket['priority']?>" title="Priority"><?=$ticket['priority']?></span>
                <strong>Category: </strong> <span class="category" title="Category"><?=$ticket['category']?></span>
            </p>
            <p class="created"><?=date('m/d/y h:ia', strtotime($ticket['created']))?></p>
        </div><!--no name div tag close-->
        <p class="msg"><?=str_ireplace(['&lt;strong&gt;','&lt;/strong&gt;','&lt;u&gt;','&lt;/u&gt;','&lt;i&gt;','&lt;/i&gt;'], ['<strong>','</strong>','<u>','</u>','<i>','</i>'], nl2br(htmlspecialchars($ticket['msg'] ?? '', ENT_QUOTES)))?></p>
    </div><!--ticket-->

    <?php if (!empty($ticket_uploads)): ?>
    <h3 class="uploads-header">Attachment(s)</h3>
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

    <?php if (isset($_SESSION['loggedin']) and ($_SESSION['role'] == 'Admin' || $_SESSION['id'] == $ticket['acc_id'])): ?>
    <div class="btns">
        <?php if ($_SESSION['role'] == 'Admin'): ?><a href="admin/ticket.php?id=<?=$_GET['id']?>" target="_blank" class="btn btn-sm btn-primary">Edit</a>
             <?php if ($ticket['ticket_status'] == 'open'): ?><a href="admin-ticket-view.php?id=<?=$_GET['id']?>&status=resolved" class="btn btn-sm btn-success" onclick="return confirm('Are you sure you want to resolve the ticket?')">Resolve</a>
             <?php endif; ?>
        <?php endif; ?>
        
        <?php if ($ticket['ticket_status'] == 'resolved'): ?>
        <a href="admin-ticket-view.php?id=<?=$_GET['id']?>&status=closed" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to permenantly close the ticket?')">Approve Resolution & Close Ticket</a>
        <a href="admin-ticket-view.php?id=<?=$_GET['id']?>&status=open" class="btn btn-sm btn-warning" onclick="return confirm('Are you sure you want to reopen the ticket?')">Reopen Ticket</a>
        <?php endif; ?>
    </div><!--btns-->
    <?php endif; ?>

    <div class="comments">
        <?php foreach($comments as $comment): ?>
        <div class="comment">
            <div>
                <i class="fas fa-comment fa-2x"></i>
            </div>
            <p>
                <span class="comment-header">
                    <?php if ($comment['full_name']): ?>
                    <span class="name<?=$comment['role'] == 'Admin' ? ' is-admin' : ''?>"><?=htmlspecialchars($comment['full_name'] ?? '', ENT_QUOTES)?></span>
                    <?php endif; ?>
                    <span class="date"><?=date('m/d/y h:ia', strtotime($comment['created']))?></span>
                    <?php if (isset($_SESSION['loggedin']) and $_SESSION['role'] == 'Admin'): ?>
                    <a href="admin/comment.php?id=<?=$comment['id']?>" target="_blank" class="edit"><i class="fa-solid fa-pen fa-xs"></i></a>
                    <?php endif; ?>
                </span>
                <?=str_ireplace(['&lt;strong&gt;','&lt;/strong&gt;','&lt;u&gt;','&lt;/u&gt;','&lt;i&gt;','&lt;/i&gt;'], ['<strong>','</strong>','<u>','</u>','<i>','</i>'], nl2br(htmlspecialchars($comment['msg'] ?? '', ENT_QUOTES)))?>
            </p>
        </div><!--comment-->
        <?php endforeach; ?>
        
        <?php if (isset($_SESSION['loggedin']) and $ticket['ticket_status'] == 'open'): ?>
        <form action="" method="post">
            <div class="msg">
                <textarea name="msg" placeholder="Enter your comment..."  maxlength="<?=max_msg_length?>" required></textarea>
            </div>
            <button type="submit" class="btn btn-sm btn-success">Post Comment</button>
        </form>
        <?php endif; ?>
        
    </div><!--comments-->
  </div><!--block tickets-->
          </div><!--/body-->
         </div><!--/card-->
        </div><!--/column-6-->
       </div><!--/row-->
      </div><!--/overview-->
</div>
</div>
</div>
</div>
</div>
</section>
</main><!-- End #main -->
    <script>
    document.querySelectorAll('.content .toolbar .format-btn').forEach(element => element.onclick = () => {
        let textarea = document.querySelector('.content textarea');
        let text = '<strong></strong>';
        text = element.classList.contains('fa-italic') ? '<i></i>' : text;
        text = element.classList.contains('fa-underline') ? '<u></u>' : text;
        textarea.setRangeText(text, textarea.selectionStart, textarea.selectionEnd, 'select');
    });
    </script>
<?=template_admin_footer()?>