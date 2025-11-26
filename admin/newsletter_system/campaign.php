<?php
require 'assets/includes/admin_config.php';
include_once '../assets/includes/components.php';

// Num format function for newsletter system
if (!function_exists('num_format')) {
    function num_format($num, $decimals = 0, $decimal_separator = '.', $thousands_separator = ',') {
        return number_format(empty($num) || $num == null || !is_numeric($num) ? 0 : $num, $decimals, $decimal_separator, $thousands_separator);
    }
}

// Default campaign values
$campaign = [
    'title' => '',
    'status' => 'Active',
    'groups' => '',
    'submit_date' => date('Y-m-d H:i:s'),
    'newsletter_id' => 0 
];
// Add campaign items to the database
function addCampaignItems($pdo, $campaign_id) {
    if (isset($_POST['recipients']) && is_array($_POST['recipients']) && count($_POST['recipients']) > 0) {
        $in  = str_repeat('?,', count($_POST['recipients']) - 1) . '?';
        $stmt = $pdo->prepare('DELETE FROM campaign_items WHERE campaign_id = ? AND subscriber_id NOT IN (' . $in . ')');
        $stmt->execute(array_merge([ $campaign_id ], $_POST['recipients']));
        foreach ($_POST['recipients'] as $r) {
            $stmt = $pdo->prepare('INSERT IGNORE INTO campaign_items (campaign_id,subscriber_id,status,update_date) VALUES (?,?,"Queued",NULL)');
            $stmt->execute([ $campaign_id, $r ]);
        }
    } else {
        $stmt = $pdo->prepare('DELETE FROM campaign_items WHERE campaign_id = ?');
        $stmt->execute([ $campaign_id ]);       
    }
}
// Retrieve subscribers from the database
$stmt = $pdo->prepare('SELECT * FROM subscribers WHERE status = "Subscribed" AND confirmed = 1 ORDER BY email ASC');
$stmt->execute();
$subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Retrieve groups
$groups = $pdo->query('SELECT g.*, (SELECT COUNT(*) FROM group_subscribers gs JOIN subscribers s ON s.id = gs.subscriber_id AND s.status = "Subscribed" WHERE gs.group_id = g.id) AS num_subscribers FROM `groups` g ORDER BY g.title ASC')->fetchAll(PDO::FETCH_ASSOC);
// Retrieve newsletters from the database
$stmt = $pdo->prepare('SELECT * FROM newsletters ORDER BY submit_date ASC');
$stmt->execute();
$newsletters = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Check if the duplicate param exists
if (isset($_GET['duplicate'])) {
    // Get the campaign to duplicate
    $stmt = $pdo->prepare('SELECT * FROM campaigns WHERE id = ?');
    $stmt->execute([ $_GET['duplicate'] ]);
    $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
    // Check if the campaign exists
    if ($campaign) {
        // Get the campaign items
        $stmt = $pdo->prepare('SELECT subscriber_id FROM campaign_items WHERE campaign_id = ?');    
        $stmt->execute([ $_GET['duplicate'] ]);
        $campaign_items = $stmt->fetchAll(PDO::FETCH_COLUMN);
        // Update the title
        $campaign['title'] = $campaign['title'] . ' copy';
    }
}
// Check if the campaign ID exists
if (isset($_GET['id'])) {
    // Retrieve the campaign from the database
    $stmt = $pdo->prepare('SELECT * FROM campaigns WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
    // Retrieve campaign items
    $stmt = $pdo->prepare('SELECT subscriber_id FROM campaign_items WHERE campaign_id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $campaign_items = $stmt->fetchAll(PDO::FETCH_COLUMN);
    // ID param exists, edit an existing campaign
    $page = 'Edit';
    if (isset($_POST['submit'])) {
        // Retrieve the groups
        $groups = isset($_POST['groups']) && is_array($_POST['groups']) ? $_POST['groups'] : [];
        // Update the campaign
        $stmt = $pdo->prepare('UPDATE campaigns SET title = ?, status = ?, submit_date = ?, newsletter_id = ?, `groups` = ? WHERE id = ?');
        $stmt->execute([ $_POST['title'], $_POST['status'], date('Y-m-d H:i:s', strtotime($_POST['start_date'])), $_POST['newsletter_id'], implode(',', $groups), $_GET['id'] ]);
        // add users from the selected groups to the recipients array
        if ($groups) {
            $stmt = $pdo->prepare('SELECT gs.subscriber_id FROM group_subscribers gs JOIN subscribers s ON s.id = gs.subscriber_id AND s.status = "Subscribed" WHERE gs.group_id IN (' . str_repeat('?,', count($groups) - 1) . '?)');
            $stmt->execute($groups);
            $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $_POST['recipients'] = isset($_POST['recipients']) && is_array($_POST['recipients']) ? array_merge($_POST['recipients'], $recipients) : $recipients;
            $_POST['recipients'] = array_unique($_POST['recipients']);
        }
        // Add campaign items
        addCampaignItems($pdo, $_GET['id']); 
        // Redirect to manage campaigns
        header('Location: campaigns.php?success_msg=2');
        exit;
    }
    if (isset($_POST['delete'])) {
        // Redirect and delete the campaign
        header('Location: campaigns.php?delete=' . $_GET['id']);
        exit;
    }
} else {
    // Create a new campaign
    $page = 'Create';
    if (isset($_POST['submit'])) {
        // Retrieve the groups
        $groups = isset($_POST['groups']) && is_array($_POST['groups']) ? $_POST['groups'] : [];
        // Insert the new campaign
        $stmt = $pdo->prepare('INSERT INTO campaigns (title,status,`groups`,submit_date,newsletter_id) VALUES (?,?,?,?,?)');
        $stmt->execute([ $_POST['title'], $_POST['status'], implode(',', $groups), date('Y-m-d H:i:s', strtotime($_POST['start_date'])), $_POST['newsletter_id'] ]);
        $campaign_id = $pdo->lastInsertId();
        // add users from the selected groups to the recipients array
        if ($groups) {
            $stmt = $pdo->prepare('SELECT gs.subscriber_id FROM group_subscribers gs JOIN subscribers s ON s.id = gs.subscriber_id AND s.status = "Subscribed" WHERE gs.group_id IN (' . str_repeat('?,', count($groups) - 1) . '?)');
            $stmt->execute($groups);
            $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $_POST['recipients'] = isset($_POST['recipients']) && is_array($_POST['recipients']) ? array_merge($_POST['recipients'], $recipients) : $recipients;
            $_POST['recipients'] = array_unique($_POST['recipients']);
        }
        // Add campaign items
        addCampaignItems($pdo, $campaign_id); 
        // Redirect to manage campaigns
        header('Location: campaigns.php?success_msg=1');
        exit;
    }
}
?>
<?=template_admin_header($page . ' Campaign', 'campaigns', 'manage')?>

<?=generate_breadcrumbs([
    ['label' => 'Campaigns', 'url' => 'campaigns.php'],
    ['label' => $page . ' Campaign']
])?>

<form method="post" class="form-professional">

    <div class="content-title mb-3">
        <div class="icon alt"><?=svg_icon_newsletter()?></div>
        <div class="txt">
            <h2><?=$page?> Campaign</h2>
            <p class="subtitle"><?=$page == 'Edit' ? 'Modify campaign details' : 'Create new email campaign'?></p>
        </div>
        <div class="btns">
            <a href="campaigns.php" class="btn btn-secondary mar-right-1">Cancel</a>
            <?php if ($page == 'Edit'): ?>
            <input type="submit" name="delete" value="Delete" class="btn btn-danger mar-right-1" onclick="return confirm('Are you sure you want to delete this campaign?')">
            <?php endif; ?>
            <input type="submit" name="submit" value="Save" class="btn btn-success">
        </div>
    </div>

    <div class="form-section">
        <h3 class="section-title">Campaign Details</h3>

            <label for="title"><span class="required">*</span> Title</label>
            <input id="title" type="text" name="title" placeholder="Title" value="<?=htmlspecialchars($campaign['title'], ENT_QUOTES)?>" required>

            <label for="start_date"><span class="required">*</span> Start Date</label>
            <input id="start_date" type="datetime-local" name="start_date" placeholder="Date" value="<?=date('Y-m-d\TH:i', strtotime($campaign['submit_date']))?>" required>

            <label for="newsletter_id"><span class="required">*</span> Newsletter</label>
            <select id="newsletter_id" name="newsletter_id" required>
                <option value="" disabled>(select newsletter)</option>
                <?php foreach ($newsletters as $newsletter): ?>
                <option value="<?=$newsletter['id']?>"<?=$campaign['newsletter_id']==$newsletter['id']?' selected':''?>><?=$newsletter['id']?> - <?=$newsletter['title']?></option>
                <?php endforeach; ?>
            </select>

            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="Active"<?=$campaign['status']=='Active'?' selected':''?>>Active</option>
                <option value="Inactive"<?=$campaign['status']=='Inactive'?' selected':''?>>Inactive</option>
                <option value="Paused"<?=$campaign['status']=='Paused'?' selected':''?>>Paused</option>
                <option value="Completed"<?=$campaign['status']=='Completed'?' selected':''?>>Completed</option>
                <option value="Cancelled"<?=$campaign['status']=='Cancelled'?' selected':''?>>Cancelled</option>
            </select>

            <label for="groups">Groups</label>
            <div class="multi-checkbox">
                <div class="item check-all">
                    <input id="check-all" type="checkbox">
                    <input type="text" placeholder="Search...">
                </div>
                <div class="con">
                    <?php foreach ($groups as $group): ?>
                    <div class="item">
                        <input id="checkbox-group-<?=$group['id']?>" type="checkbox" name="groups[]" value="<?=$group['id']?>"<?=isset($campaign['groups']) && in_array($group['id'], explode(',', $campaign['groups']))?' checked':''?>>
                        <label for="checkbox-group-<?=$group['id']?>"><?=$group['title']?> (<?=num_format($group['num_subscribers'])?> recipient<?=$group['num_subscribers'] != 1 ? 's' : ''?>)</label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <label for="recipients">Recipients</label>
            <div class="multi-checkbox">
                <div class="item check-all">
                    <input id="check-all" type="checkbox">
                    <input type="text" placeholder="Search...">
                </div>
                <div class="con">
                    <?php foreach ($subscribers as $subscriber): ?>
                    <div class="item">
                        <input id="checkbox-<?=$subscriber['id']?>" type="checkbox" name="recipients[]" value="<?=$subscriber['id']?>"<?=isset($campaign_items) && in_array($subscriber['id'], $campaign_items)?' checked':''?>>
                        <label for="checkbox-<?=$subscriber['id']?>"><?=$subscriber['email']?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>

    </div>

</form>

<?=template_admin_footer()?>