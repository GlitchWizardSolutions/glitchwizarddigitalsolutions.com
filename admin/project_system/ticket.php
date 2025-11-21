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
<form action="" method="post">
   <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
        <a href="tickets.php" class="btn mar-right-2">Cancel</a>
        <?php if ($page == 'Edit'): ?>
        <input type="submit" name="delete" value="Delete" class="btn btn-danger mar-right-2" onclick="return confirm('Are you sure you want to delete this ticket?')">
        <?php endif; ?>
        <input type="submit" name="submit" value="Save" class="btn btn-success">
    </div>

    <div class="content-block">

        <div class="form responsive-width-100">

            <label for="title"><i class="required">*</i> Title</label>
            <input id="title" type="text" name="title" placeholder="Title" value="<?=htmlspecialchars($ticket['title'], ENT_QUOTES)?>" required>

            <label for="msg"><i class="required">*</i> Message</label>
            <textarea id="msg" name="msg" placeholder="Write your ticket message..." required><?=htmlspecialchars($ticket['msg'], ENT_QUOTES)?></textarea>

            <label for="ticket_status"><i class="required">*</i> Status</label>
            <select id="ticket_status" name="ticket_status" required>
                <option value="new"<?=$ticket['ticket_status']=='new'?' selected':''?>>New</option>
                <option value="open"<?=$ticket['ticket_status']=='open'?' selected':''?>>Open</option>
                <option value="paused"<?=$ticket['ticket_status']=='paused'?' selected':''?>>Paused</option>
                 <option value="closed"<?=$ticket['ticket_status']=='closed'?' selected':''?>>Closed</option>
            </select>

            <label for="priority"><i class="required">*</i> Priority</label>
            <select id="priority" name="priority" required>
                <option value="low"<?=$ticket['priority']=='low'?' selected':''?>>Low</option>
                <option value="medium"<?=$ticket['priority']=='medium'?' selected':''?>>Medium</option>
                <option value="high"<?=$ticket['priority']=='high'?' selected':''?>>High</option>
                <option value="critical"<?=$ticket['priority']=='critical'?' selected':''?>>Critical</option>
                <option value="paused"<?=$ticket['priority']=='paused'?' selected':''?>>Paused</option>
                <option value="closed"<?=$ticket['priority']=='closed'?' selected':''?>>Closed</option>
            </select>

            <label for="category_id"><i class="required">*</i> Category</label>
            <select id="category_id" name="category_id" required>
                <?php foreach ($categories as $category): ?>
                <option value="<?=$category['id']?>"<?=$ticket['category_id']==$category['id']?' selected':''?>><?=$category['title']?></option>
                <?php endforeach; ?>
            </select>
            <label for="reminder_date" class="col-md-4 col-lg-3 col-form-label">Review Date</label> 
            <div class="col-md-3 col-lg-3 mt-auto">
                <?php if($ticket['reminder_date']){
                    $value=$ticket['reminder_date'];
                }else{
                $td= new DateTime();
                $td->modify('+1 year'); // Add one year
                $value=$td->format('Y-m-d'); // Format for SQL database (adjust format as needed
                }?>
           <input id="reminder_date" type="date" aria-labelledby="date to review" name="reminder_date" value="<?=$value?>" required>
               
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