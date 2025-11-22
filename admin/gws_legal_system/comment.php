<?php
require 'assets/includes/admin_config.php';
// Default input comment values
$comment = [
    'msg' => '',
    'ticket_id' => 0,
    'acc_id' => 0,
    'new'  => 0,
    'created' => date('Y-m-d\TH:i:s')
];
$last_comment_admin = 'Admin';
$last_comment_member = 'Member';
// Retrieve all accounts from the database
$accounts = $pdo->query('SELECT * FROM accounts')->fetchAll(PDO::FETCH_ASSOC);
// Retrieve all tickets from the database
$tickets = $pdo->query('SELECT * FROM gws_legal')->fetchAll(PDO::FETCH_ASSOC);
// Check whether the comment ID is specified
if (isset($_GET['id'])) {
    // Retrieve the comment from the database
    $stmt = $pdo->prepare('SELECT * FROM gws_legal_comments WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $comment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // ID param exists, edit an existing comment
    $page = 'Edit';
    if (isset($_POST['submit'])) {
        // Update the comment
        $stmt = $pdo->prepare('UPDATE gws_legal_comments SET msg = ?, ticket_id = ?, acc_id = ?, created = ?  WHERE id = ?');
        $stmt->execute([ $_POST['msg'], $_POST['ticket_id'], $_POST['acc_id'], date('Y-m-d H:i:s', strtotime($_POST['created'])), $_GET['id'] ]);
        $stmt = $pdo->prepare('UPDATE gws_legal SET last_comment = ? WHERE id = ?');
        $stmt->execute([$last_comment_admin, $_POST['ticket_id'] ]);
        header('Location: comments.php?success_msg=2');
        exit;
    }
    if (isset($_POST['delete'])) {
        // Delete the comment
        header('Location: comments.php?delete=' . $_GET['id']);
        exit;
    }
} else {
    // Create a new comment
    $page = 'Create';
    if (isset($_POST['submit'])) {
        $stmt = $pdo->prepare('INSERT INTO gws_legal_comments (msg,ticket_id,acc_id,created,last_comment) VALUES (?,?,?,?,?)');
        $stmt->execute([ $_POST['msg'], $_POST['ticket_id'], $_POST['acc_id'], date('Y-m-d H:i:s', strtotime($_POST['created'])), $last_comment_admin ]);
        header('Location: comments.php?success_msg=1');
        exit;
    }
}
?>
<?=template_admin_header($page . ' Legal Comment', 'ticketing', 'legal')?>

<div class="content-title">
    <div class="title">
        <div class="icon alt">
            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M9,22A1,1 0 0,1 8,21V18H4A2,2 0 0,1 2,16V4C2,2.89 2.9,2 4,2H20A2,2 0 0,1 22,4V16A2,2 0 0,1 20,18H13.9L10.2,21.71C10,21.9 9.75,22 9.5,22V22H9Z" /></svg>
        </div>
        <div class="txt">
            <h2 class="responsive-width-100"><?=$page?> Legal Comment</h2>
            <p>Manage legal filing discussion notes</p>
        </div>
    </div>
</div>

<form action="" method="post">
    <div class="form-professional">
        
        <div class="form-section">
            <h3 class="section-title">Comment Details</h3>
            
            <div class="form-group">
                <label for="msg">Discussion Note <span class="required">*</span></label>
                <textarea id="msg" name="msg" rows="6" placeholder="Enter discussion note..." required><?=htmlspecialchars($comment['msg'], ENT_QUOTES)?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="acc_id">Account</label>
                    <select id="acc_id" name="acc_id">
                        <option value="0">(none)</option>
                        <?php foreach ($accounts as $a): ?>
                        <option value="<?=$a['id']?>"<?=$a['id']==$comment['acc_id']?' selected':''?>><?=$a['id']?> - <?=$a['email']?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="ticket_id">Legal Filing</label>
                    <select id="ticket_id" name="ticket_id">
                        <option value="0">(none)</option>
                        <?php foreach ($tickets as $t): ?>
                        <option value="<?=$t['id']?>"<?=$t['id']==$comment['ticket_id']?' selected':''?>><?=$t['id']?> - <?=$t['title']?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="created">Created Date <span class="required">*</span></label>
                <input id="created" type="datetime-local" name="created" value="<?=date('Y-m-d\TH:i', strtotime($comment['created']))?>" required>
            </div>
        </div>

        <div class="form-actions">
            <a href="comments.php" class="btn btn-secondary">Cancel</a>
            <?php if ($page == 'Edit'): ?>
            <input type="submit" name="delete" value="Delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this comment?')">
            <?php endif; ?>
            <input type="submit" name="submit" value="<?=$page == 'Edit' ? 'Update' : 'Create'?> Comment" class="btn btn-primary">
        </div>

    </div>
</form>

<?=template_admin_footer()?>