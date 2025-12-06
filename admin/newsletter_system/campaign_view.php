<?php
require 'assets/includes/admin_config.php';
include_once '../assets/includes/components.php';

// Ensure Graph API email functions are loaded
$graph_email_file = __DIR__ . '/../../lib/graph-email-system.php';
if (file_exists($graph_email_file)) {
    require_once $graph_email_file;
}

// Num format function for newsletter system
if (!function_exists('num_format')) {
    function num_format($num, $decimals = 0, $decimal_separator = '.', $thousands_separator = ',') {
        return number_format(empty($num) || $num == null || !is_numeric($num) ? 0 : $num, $decimals, $decimal_separator, $thousands_separator);
    }
}

// Retrieve the campaign from the database
$stmt = $pdo->prepare('SELECT 
    c.*, 
    (SELECT COUNT(*) FROM campaign_items ci WHERE ci.campaign_id = c.id) AS total_items,
    (SELECT COUNT(*) FROM campaign_items ci WHERE ci.campaign_id = c.id AND (ci.status = "Completed" OR ci.status = "Cancelled")) AS total_completed_items,
    (SELECT COUNT(*) FROM campaign_items ci WHERE ci.campaign_id = c.id AND ci.status = "Failed") AS total_failed_items,
    (SELECT COUNT(*) FROM campaign_clicks cc WHERE cc.campaign_id = c.id) AS total_clicks,
    (SELECT COUNT(*) FROM campaign_unsubscribes cu WHERE cu.campaign_id = c.id) AS total_unsubscribes,
    (SELECT COUNT(*) FROM campaign_opens co WHERE co.campaign_id = c.id) AS total_opens  
    FROM campaigns c WHERE c.id = ?
');
$stmt->execute([ $_GET['id'] ]);
$campaign = $stmt->fetch(PDO::FETCH_ASSOC); 
if (!$campaign) {
    exit('Invalid campaign ID!');
}
// Delete campaign
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare('DELETE FROM campaign_items WHERE id = ?');
    $stmt->execute([ $_GET['delete'] ]);
    header('Location: campaign_view.php?id=' . $_GET['id'] . '&success_msg=1');
    exit;
}
// Retrieve the GET request parameters (if specified)
$pagination_page = isset($_GET['pagination_page']) ? $_GET['pagination_page'] : 1;
$search = isset($_GET['search_query']) ? $_GET['search_query'] : '';
// Order by column
$order = isset($_GET['order']) && $_GET['order'] == 'DESC' ? 'DESC' : 'ASC';
// Add/remove columns to the whitelist array
$order_by_whitelist = ['email','clicked','unsubscribed','is_read','status','update_date'];
$order_by = isset($_GET['order_by']) && in_array($_GET['order_by'], $order_by_whitelist) ? $_GET['order_by'] : 'id';
// Number of results per pagination page
$results_per_page = 10;
// Declare query param variables
$param1 = ($pagination_page - 1) * $results_per_page;
$param2 = $results_per_page;
$param3 = '%' . $search . '%';
// SQL where clause
$where = '';
$where .= $search ? 'AND (s.email LIKE :search) ' : '';
// Date filters
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$where .= $date_from ? 'AND ci.update_date >= :date_from ' : '';
$where .= $date_to ? 'AND ci.update_date <= :date_to ' : '';
// Status filter
$status = isset($_GET['status']) ? $_GET['status'] : '';
$where .= $status ? 'AND ci.status = :status ' : '';
// Read filter
$read = isset($_GET['read']) ? $_GET['read'] : '';
$where .= $read !== '' ? 'AND (SELECT COUNT(*) FROM campaign_opens co WHERE co.campaign_id = ci.campaign_id AND co.subscriber_id = ci.subscriber_id) = :read ' : '';
// Clicked filter
$clicked = isset($_GET['clicked']) ? $_GET['clicked'] : '';
$where .= $clicked !== '' ? 'AND (SELECT COUNT(*) FROM campaign_clicks cc WHERE cc.campaign_id = ci.campaign_id AND cc.subscriber_id = ci.subscriber_id) = :clicked ' : '';
// Unsubscribed filter
$unsubscribed = isset($_GET['unsubscribed']) ? $_GET['unsubscribed'] : '';
$where .= $unsubscribed !== '' ? 'AND (SELECT COUNT(*) FROM campaign_unsubscribes cu WHERE cu.campaign_id = ci.campaign_id AND cu.subscriber_id = ci.subscriber_id) = :unsubscribed ' : '';
// Get total number of recipients
$stmt = $pdo->prepare('SELECT COUNT(*) FROM campaign_items ci LEFT JOIN subscribers s ON ci.subscriber_id = s.id WHERE ci.campaign_id = :campaign_id ' . $where);
// Bind variables
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
if ($date_from) $stmt->bindParam('date_from', $date_from, PDO::PARAM_STR);
if ($date_to) $stmt->bindParam('date_to', $date_to, PDO::PARAM_STR);
if ($status) $stmt->bindParam('status', $status, PDO::PARAM_STR);
if ($read !== '') $stmt->bindParam('read', $read, PDO::PARAM_INT);
if ($clicked !== '') $stmt->bindParam('clicked', $clicked, PDO::PARAM_INT);
if ($unsubscribed !== '') $stmt->bindParam('unsubscribed', $unsubscribed, PDO::PARAM_INT);
$stmt->bindParam('campaign_id', $campaign['id'], PDO::PARAM_INT);
$stmt->execute();
$recipients_total = $stmt->fetchColumn();
// Get recipients
$stmt = $pdo->prepare('SELECT 
    ci.*, 
    s.email AS email,
    s.status AS subscriber_status, 
    (SELECT COUNT(*) FROM campaign_clicks cc WHERE cc.campaign_id = ci.campaign_id AND cc.subscriber_id = ci.subscriber_id) AS clicked,
    (SELECT COUNT(*) FROM campaign_unsubscribes cu WHERE cu.campaign_id = ci.campaign_id AND cu.subscriber_id = ci.subscriber_id) AS unsubscribed,
    (SELECT COUNT(*) FROM campaign_opens co WHERE co.campaign_id = ci.campaign_id AND co.subscriber_id = ci.subscriber_id) AS is_read 
    FROM campaign_items ci 
    LEFT JOIN subscribers s ON ci.subscriber_id = s.id 
    WHERE ci.campaign_id = :campaign_id ' . $where . ' 
    ORDER BY ' . $order_by . ' ' . $order . '  LIMIT :start_results,:num_results
');
// Bind variables
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
if ($date_from) $stmt->bindParam('date_from', $date_from, PDO::PARAM_STR);
if ($date_to) $stmt->bindParam('date_to', $date_to, PDO::PARAM_STR);
if ($status) $stmt->bindParam('status', $status, PDO::PARAM_STR);
if ($read !== '') $stmt->bindParam('read', $read, PDO::PARAM_INT);
if ($clicked !== '') $stmt->bindParam('clicked', $clicked, PDO::PARAM_INT);
if ($unsubscribed !== '') $stmt->bindParam('unsubscribed', $unsubscribed, PDO::PARAM_INT);
$stmt->bindParam('campaign_id', $campaign['id'], PDO::PARAM_INT);
$stmt->bindParam('start_results', $param1, PDO::PARAM_INT);
$stmt->bindParam('num_results', $param2, PDO::PARAM_INT);
$stmt->execute();
$recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Send newsletter
if (isset($_GET['send'])) {
    // Include the functions file
    include 'functions.php';
    // Get the newsletter
    $stmt = $pdo->prepare('SELECT * FROM newsletters WHERE id = ?');
    $stmt->execute([ $campaign['newsletter_id'] ]);
    $newsletter = $stmt->fetch(PDO::FETCH_ASSOC);
    // Get all placeholders
    $placeholders = $pdo->query('SELECT * FROM custom_placeholders')->fetchAll(PDO::FETCH_ASSOC); 
    // Current date
    $date = date('Y-m-d H:i:s'); 
    // If the newsletter exists
    if ($newsletter) {
        // Loop through the recipients and send the newsletter
        foreach ($recipients as $recipient) {
            if ($_GET['send'] == $recipient['id'] && $recipient['subscriber_status'] == 'Subscribed') {
                // Generate the unqiue code
                $code = sha1($campaign['id'] . $recipient['id'] . $recipient['email']);
                // Replace the template placeholders with the respective code
                $content = str_replace([
                    '%open_tracking_code%',
                    '%unsubscribe_link%',
                    '%click_link%'
                ], [
                    '<img src="' . website_url . 'tracking.php?action=open&id=' . $code . '" width="1" height="1" alt="">',
                    website_url . 'unsubscribe.php?id=' . $code,
                    website_url . 'tracking.php?action=click&id=' . $code . '&url='
                ], $newsletter['content']);
                // Replace custom placeholders
                foreach ($placeholders as $placeholder) {
                    $content = str_replace($placeholder['placeholder_text'], $placeholder['placeholder_value'], $content);
                }
                // Attachments
                $attachments = array_filter(explode(',', $newsletter['attachments'] ?? ''));
                $attachments = array_map(function($attachment) {
                    return '../' . $attachment;
                }, $attachments);
                // Attempt to send the newsletter
                $response = admin_sendmail(mail_from, mail_from_name, $recipient['email'], $newsletter['title'], $content, $attachments);
                // If the newsletter was sent successfully
                if ($response == 'success') {
                    $stmt = $pdo->prepare('UPDATE campaign_items ci SET ci.update_date = ?, ci.status = "Completed", ci.fail_text = "" WHERE ci.id = ?');
                    $stmt->execute([ $date, $recipient['id'] ]);
                    $stmt = $pdo->prepare('UPDATE newsletters SET last_scheduled = ? WHERE id = ?');
                    $stmt->execute([ $date, $newsletter['id'] ]);
                    header('Location: campaign_view.php?id=' . $_GET['id'] . '&success_msg=2');
                } else {
                    // If the newsletter failed to send - truncate error to fit database column
                    $error_msg = substr($response, 0, 500); // Limit to 500 characters
                    $stmt = $pdo->prepare('UPDATE campaign_items ci SET ci.update_date = ?, ci.status = "Failed", ci.fail_text = ? WHERE ci.id = ?');
                    $stmt->execute([ $date, $error_msg, $recipient['id'] ]);        
                    header('Location: campaign_view.php?id=' . $_GET['id'] . '&success_msg=3');   
                }
            } else if ($_GET['send'] == $recipient['id'] && $recipient['subscriber_status'] == 'Unsubscribed') {
                // The user previously unsubscribed, so skip the user and mark the item as failed
                $msg = 'The user unsubscribed.';
                $stmt = $pdo->prepare('UPDATE campaign_items ci SET ci.update_date = ?, ci.status = "Failed", ci.fail_text = ? WHERE ci.id = ?');
                $stmt->execute([ $date, $msg, $recipient['id'] ]);  
                header('Location: campaign_view.php?id=' . $_GET['id'] . '&success_msg=3');  
            }
        }
    }
    exit;
}
// Handle preview newsletter
if (isset($_GET['preview'])) {
    // Include the functions file
    include 'functions.php';
    // Get the newsletter
    $stmt = $pdo->prepare('SELECT * FROM newsletters WHERE id = ?');
    $stmt->execute([ $campaign['newsletter_id'] ]);
    $newsletter = $stmt->fetch(PDO::FETCH_ASSOC);
    // Get all placeholders
    $placeholders = $pdo->query('SELECT * FROM custom_placeholders')->fetchAll(PDO::FETCH_ASSOC); 
    // Default content value
    $content = 'Could not load the newsletter content.';
    // If the newsletter exists
    if ($newsletter) {
        // Loop through the recipients and send the newsletter
        foreach ($recipients as $recipient) {
            if ($_GET['preview'] == $recipient['id']) {
                // Generate the unqiue code
                $code = sha1($campaign['id'] . $recipient['id'] . $recipient['email']);
                // Replace the template placeholders with the respective code
                $content = str_replace(['%open_tracking_code%','%unsubscribe_link%','%click_link%'], '', $newsletter['content']);
                // Replace custom placeholders
                foreach ($placeholders as $placeholder) {
                    $content = str_replace($placeholder['placeholder_text'], $placeholder['placeholder_value'], $content);
                }
                $content = replace_placeholders($content);
                // Update name placeholder
                $recipient_name = htmlspecialchars(explode('@', $recipient['email'])[0], ENT_QUOTES);
                $content = str_replace('%name%', $recipient_name, $content);
                $content = base_template($content, $newsletter['title']);
            }
        }
    }
    echo $content;
    exit;
}
// Determine the URL
$url = 'campaign_view.php?id=' . $campaign['id'] . '&search_query=' . $search;
// Handle success messages
if (isset($_GET['success_msg'])) {
    if ($_GET['success_msg'] == 1) {
        $success_msg = 'Recipient deleted successfully!';
    }
    if ($_GET['success_msg'] == 2) {
        $success_msg = 'Newsletter sent successfully!';
    }
    if ($_GET['success_msg'] == 3) {
        $success_msg = 'Failed to send newsletter! Please check the error message associated with the subscriber!';
    }
}
?>
<?=template_admin_header(htmlspecialchars($campaign['title'], ENT_QUOTES) . ' - Campaign', 'campaigns', 'view')?>

<?=generate_breadcrumbs([
    ['label' => 'Campaigns', 'url' => 'campaigns.php'],
    ['label' => htmlspecialchars($campaign['title'], ENT_QUOTES)]
])?>

<div class="content-title mb-3">
    <h2 class="responsive-width-100 normal">
        <a href="campaigns.php">
            Campaigns
            <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M5.59,7.41L7,6L13,12L7,18L5.59,16.59L10.17,12L5.59,7.41M11.59,7.41L13,6L19,12L13,18L11.59,16.59L16.17,12L11.59,7.41Z" /></svg>
        </a>
        <?=htmlspecialchars($campaign['title'], ENT_QUOTES)?>
        <span class="<?=str_replace(['Paused','Completed','Cancelled','Inactive','Active'], ['orange','green','red','grey','green'], $campaign['status'])?>"><?=$campaign['status']?></span>
    </h2>
    <div class="btns">
        <a href="campaigns.php" class="btn btn-secondary mar-right-1">Back</a>
        <a href="campaigns.php?delete=<?=$campaign['id']?>" class="btn btn-danger mar-right-1" onclick="return confirm('Are you sure you want to delete this campaign?')">Delete</a>
        <a href="campaign.php?id=<?=$campaign['id']?>" class="btn btn-primary">Edit</a>
    </div>
</div>

<?php if (isset($success_msg)): ?>
<div class="mar-top-4">
    <div class="msg <?=$_GET['success_msg']==3?'error':'success'?>">
        <?php if ($_GET['success_msg']==3): ?>
        <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zm0-384c13.3 0 24 10.7 24 24V264c0 13.3-10.7 24-24 24s-24-10.7-24-24V152c0-13.3 10.7-24 24-24zM224 352a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z"/></svg>
        <?php else: ?>
        <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM369 209L241 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L335 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/></svg>
        <?php endif; ?>
        <p><?=$success_msg?></p>
        <svg class="close" width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg>
    </div>
</div>
<?php endif; ?>

<div class="campaign-stats ajax-update">
    <div class="content-block stat">
        <div class="ratio" style="--ratio: <?=$campaign['total_items'] ? 1-((($campaign['total_completed_items'] * 100) / $campaign['total_items']) / 100) : 0?>;">
            <span class="percentage"><?=$campaign['total_items'] ? number_format(($campaign['total_completed_items'] * 100) / $campaign['total_items'], 1) : 0?>%</span>
        </div>
        <div class="data">
            <span class="title">Sent</span>
            <span class="val"><?=number_format($campaign['total_completed_items'])?> <span>/ <?=number_format($campaign['total_items'])?></span></span>
        </div>
    </div>
    <div class="content-block stat">
        <div class="ratio" style="--ratio: <?=$campaign['total_items'] ? 1-((($campaign['total_opens'] * 100) / $campaign['total_items']) / 100) : 0?>;">
            <span class="percentage"><?=$campaign['total_items'] ? number_format(($campaign['total_opens'] * 100) / $campaign['total_items'], 1) : 0?>%</span>
        </div>
        <div class="data">
            <span class="title">Opens</span>
            <span class="val"><?=number_format($campaign['total_opens'])?> <span>/ <?=number_format($campaign['total_items'])?></span></span>
        </div>
    </div>
    <div class="content-block stat">
        <div class="ratio" style="--ratio: <?=$campaign['total_items'] ? 1-((($campaign['total_clicks'] * 100) / $campaign['total_items']) / 100) : 0?>;">
            <span class="percentage"><?=$campaign['total_items'] ? number_format(($campaign['total_clicks'] * 100) / $campaign['total_items'], 1) : 0?>%</span>
        </div>
        <div class="data">
            <span class="title">Clicks</span>
            <span class="val"><?=number_format($campaign['total_clicks'])?> <span>/ <?=number_format($campaign['total_items'])?></span></span>
        </div>
    </div>
    <div class="content-block stat">
        <div class="ratio red" style="--ratio: <?=$campaign['total_items'] ? 1-((($campaign['total_failed_items'] * 100) / $campaign['total_items']) / 100) : 0?>;">
            <span class="percentage"><?=$campaign['total_items'] ? number_format(($campaign['total_failed_items'] * 100) / $campaign['total_items'], 1) : 0?>%</span>
        </div>
        <div class="data">
            <span class="title">Fails</span>
            <span class="val"><?=number_format($campaign['total_failed_items'])?> <span>/ <?=number_format($campaign['total_items'])?></span></span>
        </div>
    </div>
    <div class="content-block stat">
        <div class="ratio red" style="--ratio: <?=$campaign['total_items'] ? 1-((($campaign['total_unsubscribes'] * 100) / $campaign['total_items']) / 100) : 0?>;">
            <span class="percentage"><?=$campaign['total_items'] ? number_format(($campaign['total_unsubscribes'] * 100) / $campaign['total_items'], 1) : 0?>%</span>
        </div>
        <div class="data">
            <span class="title">Unsubscribes</span>
            <span class="val"><?=number_format($campaign['total_unsubscribes'])?> <span>/ <?=number_format($campaign['total_items'])?></span></span>
        </div>
    </div>     
</div>

<div class="content-title content-header responsive-flex-column">
    <div class="title">
        <div class="icon alt">
            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M16 17V19H2V17S2 13 9 13 16 17 16 17M12.5 7.5A3.5 3.5 0 1 0 9 11A3.5 3.5 0 0 0 12.5 7.5M15.94 13A5.32 5.32 0 0 1 18 17V19H22V17S22 13.37 15.94 13M15 4A3.39 3.39 0 0 0 13.07 4.59A5 5 0 0 1 13.07 10.41A3.39 3.39 0 0 0 15 11A3.5 3.5 0 0 0 15 4Z" /></svg>
        </div>
        <div class="txt">
            <h2>Recipients</h2>
            <p>View and manage the recipients of this campaign.</p>
        </div>
    </div>
    <form action="campaign_view.php" method="get">
        <div class="filters">
            <a href="#">
                <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M0 416c0 17.7 14.3 32 32 32l54.7 0c12.3 28.3 40.5 48 73.3 48s61-19.7 73.3-48L480 448c17.7 0 32-14.3 32-32s-14.3-32-32-32l-246.7 0c-12.3-28.3-40.5-48-73.3-48s-61 19.7-73.3 48L32 384c-17.7 0-32 14.3-32 32zm128 0a32 32 0 1 1 64 0 32 32 0 1 1 -64 0zM320 256a32 32 0 1 1 64 0 32 32 0 1 1 -64 0zm32-80c-32.8 0-61 19.7-73.3 48L32 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l246.7 0c12.3 28.3 40.5 48 73.3 48s61-19.7 73.3-48l54.7 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-54.7 0c-12.3-28.3-40.5-48-73.3-48zM192 128a32 32 0 1 1 0-64 32 32 0 1 1 0 64zm73.3-64C253 35.7 224.8 16 192 16s-61 19.7-73.3 48L32 64C14.3 64 0 78.3 0 96s14.3 32 32 32l86.7 0c12.3 28.3 40.5 48 73.3 48s61-19.7 73.3-48L480 128c17.7 0 32-14.3 32-32s-14.3-32-32-32L265.3 64z"/></svg>
                Filters
            </a>
            <div class="list">
                <input type="hidden" name="id" value="<?=$campaign['id']?>">
                <label for="date_from">Date From</label>
                <input type="datetime-local" name="date_from" id="date_from" value="<?=$date_from ? date('Y-m-d\TH:i', strtotime($date_from)) : ''?>">
                <label for="date_to">Date To</label>
                <input type="datetime-local" name="date_to" id="date_to" value="<?=$date_to ? date('Y-m-d\TH:i', strtotime($date_to)) : ''?>">
                <label for="status">Status</label>
                <select name="status" id="status">
                    <option value=""<?=$status==''?' selected':''?>>All</option>
                    <option value="Queued"<?=$status=='Queued'?' selected':''?>>Queued</option>
                    <option value="Completed"<?=$status=='Completed'?' selected':''?>>Completed</option>
                    <option value="Cancelled"<?=$status=='Cancelled'?' selected':''?>>Cancelled</option>
                    <option value="Failed"<?=$status=='Failed'?' selected':''?>>Failed</option>
                </select>
                <label for="read">Read</label>
                <select name="read" id="read">
                    <option value=""<?=$read==''?' selected':''?>>All</option>
                    <option value="1"<?=$read=='1'?' selected':''?>>Read</option>
                    <option value="0"<?=$read=='0'?' selected':''?>>Unread</option>
                </select>
                <label for="clicked">Clicked</label>
                <select name="clicked" id="clicked">
                    <option value=""<?=$clicked==''?' selected':''?>>All</option>
                    <option value="1"<?=$clicked=='1'?' selected':''?>>Clicked</option>
                    <option value="0"<?=$clicked=='0'?' selected':''?>>Not Clicked</option>
                </select>
                <label for="unsubscribed">Unsubscribed</label>
                <select name="unsubscribed" id="unsubscribed">
                    <option value=""<?=$unsubscribed==''?' selected':''?>>All</option>
                    <option value="1"<?=$unsubscribed=='1'?' selected':''?>>Unsubscribed</option>
                    <option value="0"<?=$unsubscribed=='0'?' selected':''?>>Not Unsubscribed</option>
                </select>
                <button type="submit">Apply</button>
            </div>
        </div>
        <div class="search pad-top-1">
            <label for="search_query">
                <input id="search_query" type="text" name="search_query" placeholder="Search..." value="<?=htmlspecialchars($search, ENT_QUOTES)?>" class="responsive-width-100">
                <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"/></svg>
            </label>
        </div>
    </form>
</div>

<div class="filter-list">
    <?php if ($status != ''): ?>
    <div class="filter">
        <a href="<?=remove_url_param($url, 'status')?>"><svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg></a>
        Status : <?=htmlspecialchars($status, ENT_QUOTES)?>
    </div>
    <?php endif; ?>
    <?php if ($search != ''): ?>
    <div class="filter">
        <a href="<?=remove_url_param($url, 'search_query')?>"><svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free --><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg></a>
        Search : <?=htmlspecialchars($search, ENT_QUOTES)?>
    </div>
    <?php endif; ?>   
    <?php if ($date_from != ''): ?>
    <div class="filter">
        <a href="<?=remove_url_param($url, 'date_from')?>"><svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free --><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg></a>
        Date From : <?=date('F j, Y H:ia', strtotime($date_from))?>
    </div>
    <?php endif; ?>
    <?php if ($date_to != ''): ?>
    <div class="filter">
        <a href="<?=remove_url_param($url, 'date_to')?>"><svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free --><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg></a>
        Date To : <?=date('F j, Y H:ia', strtotime($date_to))?>
    </div>
    <?php endif; ?>
    <?php if ($read !== ''): ?>
    <div class="filter">
        <a href="<?=remove_url_param($url, 'read')?>"><svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free --><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg></a>
        Read : <?=$read?'Read':'Unread'?>
    </div>
    <?php endif; ?>
    <?php if ($clicked !== ''): ?>
    <div class="filter">
        <a href="<?=remove_url_param($url, 'clicked')?>"><svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free --><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg></a>
        Clicked : <?=$clicked?'Clicked':'Not Clicked'?>
    </div>
    <?php endif; ?>
    <?php if ($unsubscribed !== ''): ?>
    <div class="filter">
        <a href="<?=remove_url_param($url, 'unsubscribed')?>"><svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free --><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg></a>
        Unsubscribed : <?=$unsubscribed?'Unsubscribed':'Not Unsubscribed'?>
    </div>
    <?php endif; ?>
</div>

<div class="content-block no-pad">
    <div class="table ajax-update">
        <table>
            <thead>
                <tr>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=id'?>">#<?=$order_by=='id' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td colspan="2"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=email'?>">Email<?=$order_by=='email' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=is_read'?>">Read<?=$order_by=='is_read' ? $table_icons[strtolower($order)] : ''?></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=clicked'?>">Clicked<?=$order_by=='clicked' ? $table_icons[strtolower($order)] : ''?></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=unsubscribed'?>">Unsubscribed<?=$order_by=='unsubscribed' ? $table_icons[strtolower($order)] : ''?></td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=status'?>">Status<?=$order_by=='status' ? $table_icons[strtolower($order)] : ''?></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=update_date'?>">Date Updated<?=$order_by=='update_date' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td>Actions</td>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recipients)): ?>
                <tr>
                    <td colspan="20" class="no-results">There are no recipients.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($recipients as $k => $recipient): ?>
                <tr>
                    <td class="alt"><?=$k+1?></td>
                    <td class="img">
                        <div class="profile-img">
                            <span style="background-color:<?=color_from_string($recipient['email'])?>"><?=strtoupper(substr($recipient['email'], 0, 1))?></span>
                        </div>
                    </td>
                    <td><?=htmlspecialchars($recipient['email'], ENT_QUOTES)?></td>
                    <td class="responsive-hidden"><?=$recipient['is_read']?'<span class="dot" title="Yes"></span>':'<span class="dot no" title="No"></span>'?></td>
                    <td class="responsive-hidden"><?=$recipient['clicked']?'<span class="dot" title="Yes"></span>':'<span class="dot no" title="No"></span>'?></td>
                    <td class="responsive-hidden"><?=$recipient['unsubscribed']?'<span class="dot" title="Yes"></span>':'<span class="dot no" title="No"></span>'?></td>
                    <td>
                        <span class="<?=str_replace(['Queued','Completed','Failed','Cancelled'], ['grey','green','red','red'], $recipient['status'])?>">
                            <?=$recipient['status']?>
                            <?=$recipient['fail_text']?' <a href="#" class="recipient-error" title="' . htmlspecialchars($recipient['fail_text'], ENT_QUOTES) . '"><svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM169.8 165.3c7.9-22.3 29.1-37.3 52.8-37.3l58.3 0c34.9 0 63.1 28.3 63.1 63.1c0 22.6-12.1 43.5-31.7 54.8L280 264.4c-.2 13-10.9 23.6-24 23.6c-13.3 0-24-10.7-24-24l0-13.5c0-8.6 4.6-16.5 12.1-20.8l44.3-25.4c4.7-2.7 7.6-7.7 7.6-13.1c0-8.4-6.8-15.1-15.1-15.1l-58.3 0c-3.4 0-6.4 2.1-7.5 5.3l-.4 1.2c-4.4 12.5-18.2 19-30.6 14.6s-19-18.2-14.6-30.6l.4-1.2zM224 352a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z"/></svg></a>':''?>
                        </span>
                    </td>
                    <td class="responsive-hidden alt"><?=$recipient['update_date']==null?'--':date('F j, Y H:ia', strtotime($recipient['update_date']))?></td>
                    <td class="actions">
                        <div class="table-dropdown">
                            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M8 256a56 56 0 1 1 112 0A56 56 0 1 1 8 256zm160 0a56 56 0 1 1 112 0 56 56 0 1 1 -112 0zm216-56a56 56 0 1 1 0 112 56 56 0 1 1 0-112z"/></svg>
                            <div class="table-dropdown-items">
                                <a href="campaign_view.php?id=<?=$_GET['id']?>&pagination_page=<?=$pagination_page?>&send=<?=$recipient['id']?>" onclick="return confirm('Are you sure you want to perform this action?')">
                                    <span class="icon">
                                        <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M2,21L23,12L2,3V10L17,12L2,14V21Z" /></svg>
                                    </span>
                                    Send
                                </a>
                                <a href="campaign_view.php?id=<?=$_GET['id']?>&pagination_page=<?=$pagination_page?>&preview=<?=$recipient['id']?>" class="preview-newsletter" onclick="previewNewsletter(event)">
                                    <span class="icon">
                                        <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12,9A3,3 0 0,0 9,12A3,3 0 0,0 12,15A3,3 0 0,0 15,12A3,3 0 0,0 12,9M12,17A5,5 0 0,1 7,12A5,5 0 0,1 12,7A5,5 0 0,1 17,12A5,5 0 0,1 12,17M12,4.5C7,4.5 2.73,7.61 1,12C2.73,16.39 7,19.5 12,19.5C17,19.5 21.27,16.39 23,12C21.27,7.61 17,4.5 12,4.5Z" /></svg>
                                    </span>
                                    Preview
                                </a>
                                <a class="red" href="campaign_view.php?id=<?=$_GET['id']?>&delete=<?=$recipient['id']?>" onclick="return confirm('Are you sure you want to delete this recipient?')">
                                    <span class="icon">
                                        <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z"/></svg>
                                    </span>    
                                    Delete
                                </a>                         
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="pagination">
    <?php if ($pagination_page > 1): ?>
    <a href="<?=$url?>&pagination_page=<?=$pagination_page-1?>&order=<?=$order?>&order_by=<?=$order_by?>">Prev</a>
    <?php endif; ?>
    <span>Page <?=$pagination_page?> of <?=ceil($recipients_total / $results_per_page) == 0 ? 1 : ceil($recipients_total / $results_per_page)?></span>
    <?php if ($pagination_page * $results_per_page < $recipients_total): ?>
    <a href="<?=$url?>&pagination_page=<?=$pagination_page+1?>&order=<?=$order?>&order_by=<?=$order_by?>">Next</a>
    <?php endif; ?>
</div>

<?=template_admin_footer()?>