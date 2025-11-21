<?php
require 'assets/includes/admin_config.php';
// Delete campaign
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare('DELETE c, ci, cc, co, cu FROM campaigns c LEFT JOIN campaign_items ci ON ci.campaign_id = c.id LEFT JOIN campaign_clicks cc ON cc.campaign_id = c.id LEFT JOIN campaign_opens co ON co.campaign_id = c.id LEFT JOIN campaign_unsubscribes cu ON cu.campaign_id = c.id WHERE c.id = ?');
    $stmt->execute([ $_GET['delete'] ]);
    header('Location: campaigns.php?success_msg=3');
    exit;
}
// Update campaign
if (isset($_GET['update'], $_GET['status'])) {
    $stmt = $pdo->prepare('UPDATE campaigns SET status = ? WHERE id = ?');
    $stmt->execute([ $_GET['status'], $_GET['update'] ]);
    exit('success');  
}
// Retrieve the GET request parameters (if specified)
$pagination_page = isset($_GET['pagination_page']) ? $_GET['pagination_page'] : 1;
$search = isset($_GET['search_query']) ? $_GET['search_query'] : '';
// Order by column
$order = isset($_GET['order']) && $_GET['order'] == 'DESC' ? 'DESC' : 'ASC';
// Add/remove columns to the whitelist array
$order_by_whitelist = ['id','title','total_items','total_completed_items','total_failed_items','total_clicks','total_unsubscribes','total_opens','status','submit_date'];
$order_by = isset($_GET['order_by']) && in_array($_GET['order_by'], $order_by_whitelist) ? $_GET['order_by'] : 'submit_date';
// Number of results per pagination page
$results_per_page = 10;
// Declare query param variables
$param1 = ($pagination_page - 1) * $results_per_page;
$param2 = $results_per_page;
$param3 = '%' . $search . '%';
// SQL where clause
$where = '';
$where .= $search ? 'WHERE (title LIKE :search) ' : '';
// Filters
$status = isset($_GET['status']) ? $_GET['status'] : '';
if ($status) {
    $where .= ($where ? 'AND ' : 'WHERE ') . 'status = :status ';
}
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
if ($date_from) {
    $where .= ($where ? 'AND ' : 'WHERE ') . 'submit_date >= :date_from ';
}
if ($date_to) {
    $where .= ($where ? 'AND ' : 'WHERE ') . 'submit_date <= :date_to ';
}
// Retrieve the total number of campaigns
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM campaigns ' . $where);
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
if ($status) $stmt->bindParam('status', $status, PDO::PARAM_STR);
if ($date_from) $stmt->bindParam('date_from', $date_from, PDO::PARAM_STR);
if ($date_to) $stmt->bindParam('date_to', $date_to, PDO::PARAM_STR);
$stmt->execute();
$campaigns_total = $stmt->fetchColumn();
// SQL query to get all campaigns from the "campaigns" table
$stmt = $pdo->prepare('SELECT 
    c.*, 
    (SELECT COUNT(*) FROM campaign_items ci WHERE ci.campaign_id = c.id) AS total_items,
    (SELECT COUNT(*) FROM campaign_items ci WHERE ci.campaign_id = c.id AND (ci.status = "Completed" OR ci.status = "Cancelled")) AS total_completed_items,
    (SELECT COUNT(*) FROM campaign_items ci WHERE ci.campaign_id = c.id AND ci.status = "Failed") AS total_failed_items,
    (SELECT COUNT(*) FROM campaign_clicks cc WHERE cc.campaign_id = c.id) AS total_clicks,
    (SELECT COUNT(*) FROM campaign_unsubscribes cu WHERE cu.campaign_id = c.id) AS total_unsubscribes,
    (SELECT COUNT(*) FROM campaign_opens co WHERE co.campaign_id = c.id) AS total_opens  
    FROM campaigns c ' . $where . ' ORDER BY ' . $order_by . ' ' . $order . ' LIMIT :start_results,:num_results
');
// Bind params
$stmt->bindParam('start_results', $param1, PDO::PARAM_INT);
$stmt->bindParam('num_results', $param2, PDO::PARAM_INT);
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
if ($status) $stmt->bindParam('status', $status, PDO::PARAM_STR);
if ($date_from) $stmt->bindParam('date_from', $date_from, PDO::PARAM_STR);
if ($date_to) $stmt->bindParam('date_to', $date_to, PDO::PARAM_STR);
$stmt->execute();
// Retrieve query results
$campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Handle success messages
if (isset($_GET['success_msg'])) {
    if ($_GET['success_msg'] == 1) {
        $success_msg = 'Campaign created successfully!';
    }
    if ($_GET['success_msg'] == 2) {
        $success_msg = 'Campaign updated successfully!';
    }
    if ($_GET['success_msg'] == 3) {
        $success_msg = 'Campaign deleted successfully!';
    }
    if ($_GET['success_msg'] == 4) {
        $success_msg = 'Item(s) imported successfully! ' . $_GET['imported'] . ' item(s) were imported.';
    }
}
// Determine the URL
$url = 'campaigns.php?search_query=' . $search . (isset($_GET['status']) ? '&status=' . $_GET['status'] : '') . (isset($_GET['date_from']) ? '&date_from=' . $_GET['date_from'] : '') . (isset($_GET['date_to']) ? '&date_to=' . $_GET['date_to'] : '');
?>
<?=template_admin_header('Campaigns', 'campaigns', 'view')?>

<div class="content-title">
    <div class="title">
        <div class="icon">
            <svg width="22" height="22" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19 3H5C3.9 3 3 3.9 3 5V19C3 20.1 3.9 21 5 21H19C20.1 21 21 20.1 21 19V5C21 3.9 20.1 3 19 3M7 7H9V9H7V7M7 11H9V13H7V11M7 15H9V17H7V15M17 17H11V15H17V17M17 13H11V11H17V13M17 9H11V7H17V9Z" /></svg>
        </div>
        <div class="txt">
            <h2>Campaigns</h2>
            <p>View, create and manage campaigns.</p>
        </div>
    </div>
</div>

<?php if (isset($success_msg)): ?>
<div class="msg success">
    <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM369 209L241 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L335 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/></svg>
    <p><?=$success_msg?></p>
    <svg class="close" width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg>
</div>
<?php endif; ?>

<div class="content-header responsive-flex-column pad-top-5">
    <a href="campaign.php" class="btn">
        <svg class="icon-left" width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 80c0-17.7-14.3-32-32-32s-32 14.3-32 32V224H48c-17.7 0-32 14.3-32 32s14.3 32 32 32H192V432c0 17.7 14.3 32 32 32s32-14.3 32-32V288H400c17.7 0 32-14.3 32-32s-14.3-32-32-32H256V80z"/></svg>
        Create Campaign
    </a>
    <form action="campaigns.php" method="get">
        <div class="filters">
            <a href="#">
                <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M0 416c0 17.7 14.3 32 32 32l54.7 0c12.3 28.3 40.5 48 73.3 48s61-19.7 73.3-48L480 448c17.7 0 32-14.3 32-32s-14.3-32-32-32l-246.7 0c-12.3-28.3-40.5-48-73.3-48s-61 19.7-73.3 48L32 384c-17.7 0-32 14.3-32 32zm128 0a32 32 0 1 1 64 0 32 32 0 1 1 -64 0zM320 256a32 32 0 1 1 64 0 32 32 0 1 1 -64 0zm32-80c-32.8 0-61 19.7-73.3 48L32 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l246.7 0c12.3 28.3 40.5 48 73.3 48s61-19.7 73.3-48l54.7 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-54.7 0c-12.3-28.3-40.5-48-73.3-48zM192 128a32 32 0 1 1 0-64 32 32 0 1 1 0 64zm73.3-64C253 35.7 224.8 16 192 16s-61 19.7-73.3 48L32 64C14.3 64 0 78.3 0 96s14.3 32 32 32l86.7 0c12.3 28.3 40.5 48 73.3 48s61-19.7 73.3-48L480 128c17.7 0 32-14.3 32-32s-14.3-32-32-32L265.3 64z"/></svg>
                Filters
            </a>
            <div class="list">
                <label for="date_from">Date From</label>
                <input type="datetime-local" name="date_from" id="date_from" value="<?=$date_from ? date('Y-m-d\TH:i', strtotime($date_from)) : ''?>">
                <label for="date_to">Date To</label>
                <input type="datetime-local" name="date_to" id="date_to" value="<?=$date_to ? date('Y-m-d\TH:i', strtotime($date_to)) : ''?>">
                <label for="status">Status</label>
                <select name="status" id="status">
                    <option value=""<?=$status==''?' selected':''?>>All</option>
                    <option value="Active"<?=$status=='Active'?' selected':''?>>Active</option>
                    <option value="Inactive"<?=$status=='Inactive'?' selected':''?>>Inactive</option>
                    <option value="Paused"<?=$status=='Paused'?' selected':''?>>Paused</option>
                    <option value="Completed"<?=$status=='Completed'?' selected':''?>>Completed</option>
                    <option value="Cancelled"<?=$status=='Cancelled'?' selected':''?>>Cancelled</option>
                </select>
                <button type="submit">Apply</button>
            </div>
        </div>
        <div class="search">
            <label for="search_query">
                <input id="search_query" type="text" name="search_query" placeholder="Search campaign..." value="<?=htmlspecialchars($search, ENT_QUOTES)?>" class="responsive-width-100">
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
</div>

<div class="content-block no-pad">
    <div class="table">
        <table class="ajax-update">
            <thead>
                <tr>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=title'?>">Title<?=$order_by=='title' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=total_completed_items'?>">Sent<?=$order_by=='total_completed_items' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=total_opens'?>">Opens<?=$order_by=='total_opens' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=total_clicks'?>">Clicks<?=$order_by=='total_clicks' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=total_failed_items'?>">Fails<?=$order_by=='total_failed_items' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=total_unsubscribes'?>">Unsubscribes<?=$order_by=='total_unsubscribes' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=status'?>">Status<?=$order_by=='status' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=submit_date'?>">Date Scheduled<?=$order_by=='submit_date' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td>Actions</td>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($campaigns)): ?>
                <tr>
                    <td colspan="20" class="no-results">There are no campaigns</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($campaigns as $campaign): ?>
                <tr>
                    <td><a href="campaign_view.php?id=<?=$campaign['id']?>" class="link3"><?=htmlspecialchars($campaign['title'], ENT_QUOTES)?></a></td>
                    <td>
                        <div class="progress">
                            <span class="txt"><?=$campaign['total_completed_items']?> of <?=$campaign['total_items']?></span>
                            <div class="bot">
                                <span class="per"><?=$campaign['total_items'] ? number_format(($campaign['total_completed_items'] * 100) / $campaign['total_items']) : 0?>%</span>
                                <div class="bar">
                                    <span style="width:<?=$campaign['total_items'] ? ($campaign['total_completed_items'] * 100) / $campaign['total_items'] : 0?>%"></span>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="responsive-hidden">
                        <div class="progress">
                            <span class="txt"><?=$campaign['total_opens']?> of <?=$campaign['total_items']?></span>
                            <div class="bot">
                                <span class="per"><?=$campaign['total_items'] ? number_format(($campaign['total_opens'] * 100) / $campaign['total_items']) : 0?>%</span>
                                <div class="bar">
                                    <span style="width:<?=$campaign['total_items'] ? ($campaign['total_opens'] * 100) / $campaign['total_items'] : 0?>%"></span>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="responsive-hidden">
                        <div class="progress">
                            <span class="txt"><?=$campaign['total_clicks']?> of <?=$campaign['total_items']?></span>
                            <div class="bot">
                                <span class="per"><?=$campaign['total_items'] ? number_format(($campaign['total_clicks'] * 100) / $campaign['total_items']) : 0?>%</span>
                                <div class="bar">
                                    <span style="width:<?=$campaign['total_items'] ? ($campaign['total_clicks'] * 100) / $campaign['total_items'] : 0?>%"></span>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="responsive-hidden">
                        <div class="progress">
                            <span class="txt"><?=$campaign['total_failed_items']?> of <?=$campaign['total_items']?></span>
                            <div class="bot">
                                <span class="per"><?=$campaign['total_items'] ? number_format(($campaign['total_failed_items'] * 100) / $campaign['total_items']) : 0?>%</span>
                                <div class="bar">
                                    <span class="red" style="width:<?=$campaign['total_items'] ? ($campaign['total_failed_items'] * 100) / $campaign['total_items'] : 0?>%"></span>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="responsive-hidden">
                        <div class="progress">
                            <span class="txt"><?=$campaign['total_unsubscribes']?> of <?=$campaign['total_items']?></span>
                            <div class="bot">
                                <span class="per"><?=$campaign['total_items'] ? number_format(($campaign['total_unsubscribes'] * 100) / $campaign['total_items']) : 0?>%</span>
                                <div class="bar">
                                    <span class="red" style="width:<?=$campaign['total_items'] ? ($campaign['total_unsubscribes'] * 100) / $campaign['total_items'] : 0?>%"></span>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="responsive-hidden status-container">
                        <?php if ($campaign['status'] == 'Completed'): ?>
                        <span class="green small">Completed</span>
                        <?php else: ?>
                        <div class="status" data-id="<?=$campaign['id']?>">
                            <span title="<?=htmlspecialchars($campaign['status'], ENT_QUOTES)?>" class="<?=strtolower(htmlspecialchars($campaign['status'], ENT_QUOTES))?>"></span>
                            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M7,10L12,15L17,10H7Z" /></svg>
                            <div class="status-dropdown">
                                <a href="#" data-value="Active" data-id="<?=$campaign['id']?>">Active</a>
                                <a href="#" data-value="Inactive" data-id="<?=$campaign['id']?>">Inactive</a>
                                <a href="#" data-value="Paused" data-id="<?=$campaign['id']?>">Pause</a>
                                <a href="#" data-value="Cancelled" data-id="<?=$campaign['id']?>" class="red">Cancel</a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td class="alt"><?=date('F j, Y H:ia', strtotime($campaign['submit_date']))?></td>
                    <td class="actions">
                        <div class="table-dropdown">
                            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M8 256a56 56 0 1 1 112 0A56 56 0 1 1 8 256zm160 0a56 56 0 1 1 112 0 56 56 0 1 1 -112 0zm216-56a56 56 0 1 1 0 112 56 56 0 1 1 0-112z"/></svg>
                            <div class="table-dropdown-items">
                                <a href="campaign_view.php?id=<?=$campaign['id']?>">
                                    <span class="icon">
                                        <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M288 32c-80.8 0-145.5 36.8-192.6 80.6C48.6 156 17.3 208 2.5 243.7c-3.3 7.9-3.3 16.7 0 24.6C17.3 304 48.6 356 95.4 399.4C142.5 443.2 207.2 480 288 480s145.5-36.8 192.6-80.6c46.8-43.5 78.1-95.4 93-131.1c3.3-7.9 3.3-16.7 0-24.6c-14.9-35.7-46.2-87.7-93-131.1C433.5 68.8 368.8 32 288 32zM144 256a144 144 0 1 1 288 0 144 144 0 1 1 -288 0zm144-64c0 35.3-28.7 64-64 64c-7.1 0-13.9-1.2-20.3-3.3c-5.5-1.8-11.9 1.6-11.7 7.4c.3 6.9 1.3 13.8 3.2 20.7c13.7 51.2 66.4 81.6 117.6 67.9s81.6-66.4 67.9-117.6c-11.1-41.5-47.8-69.4-88.6-71.1c-5.8-.2-9.2 6.1-7.4 11.7c2.1 6.4 3.3 13.2 3.3 20.3z"/></svg>
                                    </span>
                                    View
                                </a>
                                <a href="campaign.php?id=<?=$campaign['id']?>">
                                    <span class="icon">
                                        <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M471.6 21.7c-21.9-21.9-57.3-21.9-79.2 0L362.3 51.7l97.9 97.9 30.1-30.1c21.9-21.9 21.9-57.3 0-79.2L471.6 21.7zm-299.2 220c-6.1 6.1-10.8 13.6-13.5 21.9l-29.6 88.8c-2.9 8.6-.6 18.1 5.8 24.6s15.9 8.7 24.6 5.8l88.8-29.6c8.2-2.7 15.7-7.4 21.9-13.5L437.7 172.3 339.7 74.3 172.4 241.7zM96 64C43 64 0 107 0 160V416c0 53 43 96 96 96H352c53 0 96-43 96-96V320c0-17.7-14.3-32-32-32s-32 14.3-32 32v96c0 17.7-14.3 32-32 32H96c-17.7 0-32-14.3-32-32V160c0-17.7 14.3-32 32-32h96c17.7 0 32-14.3 32-32s-14.3-32-32-32H96z"/></svg>
                                    </span>
                                    Edit
                                </a>
                                <a href="campaign.php?duplicate=<?=$campaign['id']?>">
                                    <span class="icon">
                                        <svg width="15" height="15" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M11,17H4A2,2 0 0,1 2,15V3A2,2 0 0,1 4,1H16V3H4V15H11V13L15,16L11,19V17M19,21V7H8V13H6V7A2,2 0 0,1 8,5H19A2,2 0 0,1 21,7V21A2,2 0 0,1 19,23H8A2,2 0 0,1 6,21V19H8V21H19Z" /></svg>
                                    </span>
                                    Duplicate    
                                </a>
                                <a class="red" href="campaigns.php?delete=<?=$campaign['id']?>" onclick="return confirm('Are you sure you want to delete this campaign?')">
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
    <span>Page <?=$pagination_page?> of <?=ceil($campaigns_total / $results_per_page) == 0 ? 1 : ceil($campaigns_total / $results_per_page)?></span>
    <?php if ($pagination_page * $results_per_page < $campaigns_total): ?>
    <a href="<?=$url?>&pagination_page=<?=$pagination_page+1?>&order=<?=$order?>&order_by=<?=$order_by?>">Next</a>
    <?php endif; ?>
</div>

<?=template_admin_footer()?>