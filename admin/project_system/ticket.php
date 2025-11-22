<?php
/* QA: Create, Import & Export 3/8/24 (Not tested for accessibility) 
*/
require 'assets/includes/admin_config.php';
// Default ticket values
$page = 'Edit';
$ticket = [
    'title' => '',
    'msg' => '',
    'ticket_status' => 'open',
    'priority' => 'low',
    'category_id' => 1,
    'reminder_date' => date('Y-m-d') 
];

// Retrieve categories from the database
$categories = $pdo->query('SELECT * FROM project_categories')->fetchAll(PDO::FETCH_ASSOC);
// Check whether the ticket ID is specified
if (isset($_GET['id'])) {
    // Retrieve the ticket from the database
    $stmt = $pdo->prepare('SELECT * FROM project_tickets WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    // ID param exists, edit an existing ticket
   
    if (isset($_POST['submit'])) {
        // Update the ticket
        if($_POST['ticket_status']=="closed"){
            $priority="closed";
            $reminder='9999-12-31';
        }elseif($_POST['ticket_status']=="paused"){
            $priority="paused";
            $reminder=$_POST['reminder_date'];
        }else{
            $priority=$_POST['priority'];
            $reminder=$_POST['reminder_date'];
        }
  
        $stmt = $pdo->prepare('UPDATE project_tickets SET  title = ?, msg = ?, ticket_status = ?, priority = ?, category_id = ?, reminder_date = ? WHERE id = ?');
        $stmt->execute([ $_POST['title'], $_POST['msg'], $_POST['ticket_status'],$priority, $_POST['category_id'],  $reminder, $_GET['id'] ]);
        header('Location: tickets.php?success_msg=2');
        exit;
    }
    if (isset($_POST['delete'])) {
        // Redirect and delete the ticket
        header('Location: tickets.php?delete=' . $_GET['id']);
        exit;
    }
} else {
    // Create a new ticket
    $page = 'Create';
    if (isset($_POST['submit'])) {
        $stmt = $pdo->prepare('INSERT INTO project_tickets (title, msg, ticket_status, priority, category_id, reminder_date) VALUES  ( ?, ?, ?, ?, ?, ?)');
        $stmt->execute([ $_POST['title'], $_POST['msg'], $_POST['ticket_status'], $_POST['priority'], $_POST['category_id'], $_POST['reminder_date'] ]);
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
        
        header('Location: tickets.php?success_msg=1');
        exit;
    }
}
?>
<?=template_admin_header($page . ' Project Ticket', 'projects', 'manage')?>

<div class="content-title">
    <div class="title">
    <i class="fa-solid fa-person-through-window  fa-lg"></i>
        <div class="txt">
            <h2 class="responsive-width-100"><?=$page?> Project Ticket &nbsp;
             <?php if($ticket['title']) :?>
                    - <?=$ticket['title']?> 
                <?php endif?>
            </h2>
        </div>
    </div>
</div>
<br><br>
<form action="" method="post" enctype="multipart/form-data">
   <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
        <a href="tickets.php" class="btn mar-right-2">Cancel</a>
        <?php if ($page == 'Edit'): ?>
        <input type="submit" name="delete" value="Delete" class="btn btn-danger mar-right-2" onclick="return confirm('Are you sure you want to delete this ticket?')">
        <?php endif; ?>
        <input type="submit" name="submit" value="Save" class="btn btn-success">
    </div>

    <div class="content-block">
        <div class="form-professional">
            
            <div class="form-section">
                <h3 class="section-title">Ticket Information</h3>
                
                <div class="form-group">
                    <label for="title"><i class="required">*</i> Title</label>
                    <input id="title" type="text" name="title" placeholder="Enter ticket title" value="<?=htmlspecialchars($ticket['title'], ENT_QUOTES)?>" required>
                </div>

                <div class="form-group">
                    <label for="msg"><i class="required">*</i> Message</label>
                    <textarea id="msg" name="msg" placeholder="Describe the ticket details..." required><?=htmlspecialchars($ticket['msg'], ENT_QUOTES)?></textarea>
                </div>
            </div>

            <div class="form-section">
                <h3 class="section-title">Ticket Settings</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="ticket_status"><i class="required">*</i> Status</label>
                        <select id="ticket_status" name="ticket_status" required>
                            <option value="new"<?=$ticket['ticket_status']=='new'?' selected':''?>>New</option>
                            <option value="open"<?=$ticket['ticket_status']=='open'?' selected':''?>>Open</option>
                            <option value="paused"<?=$ticket['ticket_status']=='paused'?' selected':''?>>Paused</option>
                            <option value="closed"<?=$ticket['ticket_status']=='closed'?' selected':''?>>Closed</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="priority"><i class="required">*</i> Priority</label>
                        <select id="priority" name="priority" required>
                            <option value="low"<?=$ticket['priority']=='low'?' selected':''?>>Low</option>
                            <option value="medium"<?=$ticket['priority']=='medium'?' selected':''?>>Medium</option>
                            <option value="high"<?=$ticket['priority']=='high'?' selected':''?>>High</option>
                            <option value="critical"<?=$ticket['priority']=='critical'?' selected':''?>>Critical</option>
                            <option value="paused"<?=$ticket['priority']=='paused'?' selected':''?>>Paused</option>
                            <option value="closed"<?=$ticket['priority']=='closed'?' selected':''?>>Closed</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="category_id"><i class="required">*</i> Category</label>
                        <select id="category_id" name="category_id" required>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?=$category['id']?>"<?=$ticket['category_id']==$category['id']?' selected':''?>><?=$category['title']?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="reminder_date"><i class="required">*</i> Review Date</label>
                        <?php 
                        if($ticket['reminder_date']){
                            $value=$ticket['reminder_date'];
                        }else{
                            $td= new DateTime();
                            $td->modify('+1 year');
                            $value=$td->format('Y-m-d');
                        }?>
                        <input id="reminder_date" type="date" name="reminder_date" value="<?=$value?>" required>
                    </div>
                </div>
            </div>

            <?php if (attachments && $page == 'Create'): ?>
            <div class="form-section">
                <h3 class="section-title">Attachments</h3>
                
                <div class="form-group">
                    <label for="attachments">Upload Files</label>
                    <div class="file-upload-wrapper">
                        <input type="file" name="attachments[]" id="attachments" accept=".<?=str_replace(',', ',.', attachments_allowed)?>" multiple>
                        <span class="file-upload-hint">Accepted formats: <?=str_replace(',', ', ', attachments_allowed)?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
  <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
        <a href="tickets.php" class="btn mar-right-2">Cancel</a>
        <?php if ($page == 'Edit'): ?>
        <input type="submit" name="delete" value="Delete" class="btn btn-danger mar-right-2" onclick="return confirm('Are you sure you want to delete this ticket?')">
        <?php endif; ?>
        <input type="submit" name="submit" value="Save" class="btn btn-success">
    </div>
</form>

<?=template_admin_footer()?>