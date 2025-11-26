<?php
require 'assets/includes/admin_config.php';
include_once '../assets/includes/components.php';
// Default subscriber values
$subscriber = [
    'email' => '',
    'date_subbed' => date('Y-m-d H:i:s'),
    'confirmed' => 1,
    'status' => 'Subscribed',
    'groups' => []
];
// Get groups
$groups = $pdo->query('SELECT * FROM `groups` ORDER BY title ASC')->fetchAll();
// Add subscriber groups to the database
function addSubscriberGroups($pdo, $subscriber_id) {
    if (isset($_POST['groups']) && is_array($_POST['groups']) && count($_POST['groups']) > 0) {
        $in  = str_repeat('?,', count($_POST['groups']) - 1) . '?';
        $stmt = $pdo->prepare('DELETE FROM group_subscribers WHERE subscriber_id = ? AND group_id NOT IN (' . $in . ')');
        $stmt->execute(array_merge([ $subscriber_id ], $_POST['groups']));
        foreach ($_POST['groups'] as $group) {
            $stmt = $pdo->prepare('INSERT IGNORE INTO group_subscribers (subscriber_id,group_id) VALUES (?,?)');
            $stmt->execute([ $subscriber_id, $group ]);
        }
    } else {
        $stmt = $pdo->prepare('DELETE FROM group_subscribers WHERE subscriber_id = ?');
        $stmt->execute([ $subscriber_id ]);       
    }
}
// Check if the ID param exists
if (isset($_GET['id'])) {
    // Retrieve the subscriber from the database
    $stmt = $pdo->prepare('SELECT * FROM subscribers WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $subscriber = $stmt->fetch(PDO::FETCH_ASSOC);
    // Get groups
    $stmt = $pdo->prepare('SELECT g.* FROM `groups` g JOIN group_subscribers gs ON gs.group_id = g.id AND gs.subscriber_id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $subscriber['groups'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // ID param exists, edit an existing subscriber
    $page = 'Edit';
    if (isset($_POST['submit'])) {
        // Check to see if email already exists
        $stmt = $pdo->prepare('SELECT id FROM subscribers WHERE email = ? AND email != ?');
        $stmt->execute([ $_POST['email'], $subscriber['email'] ]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            $error_msg = 'Email already exists!';
        }
        if (!isset($error_msg)) {
            // Update the subscriber
            $stmt = $pdo->prepare('UPDATE subscribers SET email = ?, date_subbed = ?, confirmed = ?, status = ? WHERE id = ?');
            $stmt->execute([ $_POST['email'], date('Y-m-d H:i:s', strtotime($_POST['date_subbed'])), $_POST['confirmed'], $_POST['status'], $_GET['id'] ]);
            addSubscriberGroups($pdo, $_GET['id']);
            header('Location: subscribers.php?success_msg=2');
            exit;
        } else {
            // Save the submitted values
            $subscriber = [
                'email' => $_POST['email'],
                'date_subbed' => $_POST['date_subbed'],
                'confirmed' => $_POST['confirmed'],
                'status' => $_POST['status']
            ];
        }
    }
    if (isset($_POST['delete'])) {
        // Redirect and delete subscriber
        header('Location: subscribers.php?delete=' . $_GET['id']);
        exit;
    }
} else {
    // Create a new subscriber
    $page = 'Create';
    if (isset($_POST['submit'])) {
        // check if subscriber already exists
        $stmt = $pdo->prepare('SELECT id FROM subscribers WHERE email = ?');
        $stmt->execute([ $_POST['email'] ]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            $error_msg = 'Email already exists!';
        }
        if (!isset($error_msg)) {
            $stmt = $pdo->prepare('INSERT INTO subscribers (email,date_subbed,confirmed,status) VALUES (?,?,?,?)');
            $stmt->execute([ $_POST['email'], date('Y-m-d H:i:s', strtotime($_POST['date_subbed'])), $_POST['confirmed'], $_POST['status'] ]);
            addSubscriberGroups($pdo, $pdo->lastInsertId());
            header('Location: subscribers.php?success_msg=1');
            exit;
        } else {
            // Save the submitted values
            $subscriber = [
                'email' => $_POST['email'],
                'date_subbed' => $_POST['date_subbed'],
                'confirmed' => $_POST['confirmed'],
                'status' => $_POST['status']
            ];
        }
    }
}
?>
<?=template_admin_header($page . ' Subscriber', 'newsletters', 'subscribers')?>

<?=generate_breadcrumbs([
    ['label' => 'Newsletter System', 'url' => 'index.php'],
    ['label' => 'Subscribers', 'url' => 'subscribers.php'],
    ['label' => $page . ' Subscriber']
])?>

<div class="content-title mb-3">
    <div class="title">
        <i class="fa-solid fa-user"></i>
        <div class="txt">
            <h2><?=$page?> Subscriber</h2>
            <p><?=$page == 'Edit' ? 'Modify subscriber details' : 'Add new newsletter subscriber'?></p>
        </div>
    </div>
</div>

<form method="post">

    <div class="form-professional">
        
        <?php if (isset($error_msg)): ?>
        <div class="msg error">
            <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zm0-384c13.3 0 24 10.7 24 24V264c0 13.3-10.7 24-24 24s-24-10.7-24-24V152c0-13.3 10.7-24 24-24zM224 352a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z"/></svg>
            <p><?=$error_msg?></p>
            <svg class="close" width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg>
        </div>
        <?php endif; ?>
        
        <div class="form-section">
            <h3 class="section-title">Subscriber Details</h3>

            <div class="form-group">
                <label for="email"><span class="required">*</span> Email</label>
                <input id="email" type="email" name="email" placeholder="Email" value="<?=htmlspecialchars($subscriber['email'], ENT_QUOTES)?>" required>
            </div>

            <div class="form-group">
                <label for="confirmed"><span class="required">*</span> Confirmed</label>
                <select id="confirmed" name="confirmed" required>
                    <option value="1"<?=$subscriber['confirmed']==1?' selected':''?>>Yes</option>
                    <option value="0"<?=$subscriber['confirmed']==0?' selected':''?>>No</option>
                </select>
            </div>

            <div class="form-group">
                <label for="status"><span class="required">*</span> Status</label>
                <select id="status" name="status" required>
                    <option value="Subscribed"<?=$subscriber['status']=='Subscribed'?' selected':''?>>Subscribed</option>
                    <option value="Unsubscribed"<?=$subscriber['status']=='Unsubscribed'?' selected':''?>>Unsubscribed</option>
                </select>
            </div>

            <div class="form-group">
                <label for="groups">Groups</label>
                <div class="multiselect" data-name="groups[]">
                    <?php foreach ($subscriber['groups'] as $group): ?>
                    <span class="item" data-value="<?=$group['id']?>">
                        <i class="remove">&times;</i><?=$group['title']?>
                        <input type="hidden" name="groups[]" value="<?=$group['id']?>">
                    </span>
                    <?php endforeach; ?>
                    <input type="text" class="search" id="group" placeholder="Groups">
                    <div class="list">
                        <?php foreach ($groups as $group): ?>
                        <span data-value="<?=$group['id']?>"><?=$group['title']?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="date_subbed"><span class="required">*</span> Date Subscribed</label>
                <input id="date_subbed" type="datetime-local" name="date_subbed" value="<?=date('Y-m-d\TH:i', strtotime($subscriber['date_subbed']))?>" required>
            </div>
        </div>
        
        <div class="form-actions">
            <a href="subscribers.php" class="btn btn-secondary">Cancel</a>
            <?php if ($page == 'Edit'): ?>
            <input type="submit" name="delete" value="Delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this subscriber?')">
            <?php endif; ?>
            <input type="submit" name="submit" value="Save" class="btn btn-success">
        </div>

    </div>
</form>

<?=template_admin_footer()?>