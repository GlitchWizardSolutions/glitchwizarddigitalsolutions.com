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
$last_comment_admin = '0';
$last_comment_member = '1';
// Retrieve all accounts from the database
$accounts = $pdo->query('SELECT * FROM accounts')->fetchAll(PDO::FETCH_ASSOC);
// Retrieve all tickets from the database
$tickets = $pdo->query('SELECT * FROM tickets')->fetchAll(PDO::FETCH_ASSOC);
// Check whether the comment ID is specified
if (isset($_GET['id'])) {
    // Retrieve the comment from the database
    $stmt = $pdo->prepare('SELECT * FROM tickets_comments WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $comment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // ID param exists, edit an existing comment
    $page = 'Edit';
    if (isset($_POST['submit'])) {
        // Update the comment
        $stmt = $pdo->prepare('UPDATE tickets_comments SET msg = ?, ticket_id = ?, acc_id = ?, created = ?  WHERE id = ?');
        $stmt->execute([ $_POST['msg'], $_POST['ticket_id'], $_POST['acc_id'], date('Y-m-d H:i:s', strtotime($_POST['created'])), $_GET['id'] ]);
        $stmt = $pdo->prepare('UPDATE tickets SET last_comment = ? WHERE id = ?');
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
        $stmt = $pdo->prepare('INSERT INTO tickets_comments (msg,ticket_id,acc_id,created,last_comment) VALUES (?,?,?,?,?)');
        $stmt->execute([ $_POST['msg'], $_POST['ticket_id'], $_POST['acc_id'], date('Y-m-d H:i:s', strtotime($_POST['created'])), $last_comment_admin ]);
        header('Location: comments.php?success_msg=1');
        exit;
    }
}
?>
<?=template_admin_header($page . 'Tickets', 'tickets', 'comments')?>

<form action="" method="post">

    <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
        <h2 class="responsive-width-100"><?=$page?> Comment</h2>
        <a href="comments.php" class="btn alt mar-right-2">Cancel</a>
        <?php if ($page == 'Edit'): ?>
        <input type="submit" name="delete" value="Delete" class="btn red mar-right-2" onclick="return confirm('Are you sure you want to delete this comment?')">
        <?php endif; ?>
        <input type="submit" name="submit" value="Save" class="btn">
    </div>

    <div class="content-block">

        <div class="form responsive-width-100">

            <label for="msg"><i class="required">*</i> Message</label>
            <textarea id="msg" name="msg" placeholder="Enter your message..." required><?=htmlspecialchars($comment['msg'], ENT_QUOTES)?></textarea>

            <label for="acc_id">Account</label>
            <select id="acc_id" name="acc_id" style="margin-bottom: 30px;">
                <option value="0">(none)</option>
                <?php foreach ($accounts as $a): ?>
                <option value="<?=$a['id']?>"<?=$a['id']==$comment['acc_id']?' selected':''?>><?=$a['id']?> - <?=$a['email']?></option>
                <?php endforeach; ?>
            </select>

            <label for="ticket_id">Ticket</label>
            <select id="ticket_id" name="ticket_id" style="margin-bottom: 30px;">
                <option value="0">(none)</option>
                <?php foreach ($tickets as $t): ?>
                <option value="<?=$t['id']?>"<?=$t['id']==$comment['ticket_id']?' selected':''?>><?=$t['id']?> - <?=$t['title']?></option>
                <?php endforeach; ?>
            </select>

            <label for="created"><i class="required">*</i> Created</label>
            <input id="created" type="datetime-local" name="created" value="<?=date('Y-m-d\TH:i', strtotime($comment['created']))?>" required>

        </div>

    </div>

</form>

<?=template_admin_footer()?>