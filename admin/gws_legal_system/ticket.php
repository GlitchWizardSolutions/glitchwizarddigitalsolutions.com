<?php
require 'assets/includes/admin_config.php';
// Default ticket values
$page = 'Edit';
$ticket = [
    'title' => '',
    'msg' => '',
    'full_name' => '',
    'email' => '',
    'created' => date('Y-m-d H:i:s'),
    'ticket_status' => 'open',
    'priority' => 'low',
    'category_id' => 1,
    'private' => 1,
    'acc_id' => 0,
    'approved' => 1
];
// Retrieve accounts from the database
$accounts = $pdo->query('SELECT * FROM accounts')->fetchAll(PDO::FETCH_ASSOC);
// Retrieve categories from the database
$categories = $pdo->query('SELECT * FROM gws_legal_categories')->fetchAll(PDO::FETCH_ASSOC);
// Check whether the ticket ID is specified
if (isset($_GET['id'])) {
    // Retrieve the ticket from the database
    $stmt = $pdo->prepare('SELECT * FROM gws_legal WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    // ID param exists, edit an existing ticket
   
    if (isset($_POST['submit'])) {
        // Update the ticket
        $stmt = $pdo->prepare('UPDATE gws_legal SET title = ?, msg = ?, created = ?, ticket_status = ?, priority = ?, category_id = ?, approved = ? WHERE id = ?');
        $stmt->execute([ $_POST['title'], $_POST['msg'], date('Y-m-d H:i:s', strtotime($_POST['created'])), $_POST['ticket_status'], $_POST['priority'], $_POST['category_id'], 1, $_GET['id'] ]);
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
        $stmt = $pdo->prepare('INSERT INTO gws_legal (title, msg, created, ticket_status, priority, category_id, approved) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([ $_POST['title'], $_POST['msg'], date('Y-m-d H:i:s', strtotime($_POST['created'])), $_POST['ticket_status'], $_POST['priority'], $_POST['category_id'], 1 ]);
        header('Location: tickets.php?success_msg=1');
        exit;
    }
}
?>
<?=template_admin_header($page . ' GWS Legal Requirement', 'ticketing', 'legal')?>

<div class="content-title">
    <div class="title">
        <div class="icon alt">
            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z" /></svg>
        </div>
        <div class="txt">
            <h2 class="responsive-width-100"><?=$page?> Legal Requirement</h2>
            <p>Manage legal filings and compliance documents</p>
        </div>
    </div>
</div>

<form action="" method="post">
    <div class="form-professional">
        
        <!-- Document Information Section -->
        <div class="form-section">
            <h3 class="section-title">Document Information</h3>
            
            <div class="form-group">
                <label for="title">Document Title <span class="required">*</span></label>
                <input id="title" type="text" name="title" placeholder="Enter legal document title" value="<?=htmlspecialchars($ticket['title'], ENT_QUOTES)?>" required>
            </div>

            <div class="form-group">
                <label for="msg">Description / Notes <span class="required">*</span></label>
                <textarea id="msg" name="msg" placeholder="Enter document description or notes..." rows="6" required><?=htmlspecialchars($ticket['msg'], ENT_QUOTES)?></textarea>
            </div>
        </div>

        <!-- Filing Details Section -->
        <div class="form-section">
            <h3 class="section-title">Filing Details</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="category_id">Category <span class="required">*</span></label>
                    <select id="category_id" name="category_id" required>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?=$category['id']?>"<?=$ticket['category_id']==$category['id']?' selected':''?>><?=$category['title']?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="priority">Priority <span class="required">*</span></label>
                    <select id="priority" name="priority" required>
                        <option value="low"<?=$ticket['priority']=='low'?' selected':''?>>Low</option>
                        <option value="medium"<?=$ticket['priority']=='medium'?' selected':''?>>Medium</option>
                        <option value="high"<?=$ticket['priority']=='high'?' selected':''?>>High</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="ticket_status">Status <span class="required">*</span></label>
                    <select id="ticket_status" name="ticket_status" required>
                        <option value="open"<?=$ticket['ticket_status']=='open'?' selected':''?>>Open</option>
                        <option value="resolved"<?=$ticket['ticket_status']=='resolved'?' selected':''?>>Resolved</option>
                        <option value="paused"<?=$ticket['ticket_status']=='paused'?' selected':''?>>Paused</option>
                        <option value="closed"<?=$ticket['ticket_status']=='closed'?' selected':''?>>Closed</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="created">Created Date <span class="required">*</span></label>
                    <input id="created" type="datetime-local" name="created" value="<?=date('Y-m-d\TH:i', strtotime($ticket['created']))?>" required>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="form-actions">
            <a href="tickets.php" class="btn btn-secondary">Cancel</a>
            <?php if ($page == 'Edit'): ?>
            <input type="submit" name="delete" value="Delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this legal requirement?')">
            <?php endif; ?>
            <input type="submit" name="submit" value="<?=$page == 'Edit' ? 'Update' : 'Create'?> Requirement" class="btn btn-primary">
        </div>

    </div>
</form>

<?=template_admin_footer()?>