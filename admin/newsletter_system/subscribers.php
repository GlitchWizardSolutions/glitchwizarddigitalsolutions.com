<?php
require 'assets/includes/admin_config.php';
// Retrieve the GET request parameters (if specified)
$pagination_page = isset($_GET['pagination_page']) ? $_GET['pagination_page'] : 1;
$search = isset($_GET['search_query']) ? $_GET['search_query'] : '';
// Order by column
$order = isset($_GET['order']) && $_GET['order'] == 'DESC' ? 'DESC' : 'ASC';
// Add/remove columns to the whitelist array
$order_by_whitelist = ['id','email','date_subbed','confirmed','status','percent_received'];
$order_by = isset($_GET['order_by']) && in_array($_GET['order_by'], $order_by_whitelist) ? $_GET['order_by'] : 'id';
// Number of results per pagination page
$results_per_page = 15;
// Declare query param variables
$param1 = ($pagination_page - 1) * $results_per_page;
$param2 = $results_per_page;
$param3 = '%' . $search . '%';
// SQL where clause
$where = '';
$where .= $search ? 'WHERE (email LIKE :search) ' : '';
// Filters
$status = isset($_GET['status']) ? $_GET['status'] : '';
if ($status) {
    $where .= ($where ? 'AND ' : 'WHERE ') . 'status = :status ';
}
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
if ($date_from) {
    $where .= ($where ? 'AND ' : 'WHERE ') . 'date_subbed >= :date_from ';
}
if ($date_to) {
    $where .= ($where ? 'AND ' : 'WHERE ') . 'date_subbed <= :date_to ';
}
$confirmed = isset($_GET['confirmed']) ? $_GET['confirmed'] : '';
if ($confirmed !== '') {
    $where .= ($where ? 'AND ' : 'WHERE ') . 'confirmed = :confirmed ';
}
$group = isset($_GET['group']) ? $_GET['group'] : '';
if ($group) {
    $where .= ($where ? 'AND ' : 'WHERE ') . 'id IN (SELECT subscriber_id FROM group_subscribers WHERE group_id = :group) ';
}
// Get groups
$groups = $pdo->query('SELECT * FROM groups')->fetchAll();
// Retrieve the total number of subscribers
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM subscribers ' . $where);
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
if ($status) $stmt->bindParam('status', $status, PDO::PARAM_STR);
if ($date_from) $stmt->bindParam('date_from', $date_from, PDO::PARAM_STR);
if ($date_to) $stmt->bindParam('date_to', $date_to, PDO::PARAM_STR);
if ($confirmed != '') $stmt->bindParam('confirmed', $confirmed, PDO::PARAM_INT);
if ($group) $stmt->bindParam('group', $group, PDO::PARAM_INT);
$stmt->execute();
$subscribers_total = $stmt->fetchColumn();
// SQL query to get all subscribers from the "subscribers" table
$stmt = $pdo->prepare('SELECT 
    s.*, 
    ((SELECT COUNT(*) FROM campaign_items ci WHERE ci.subscriber_id = s.id AND ci.status = "Completed") / ((SELECT COUNT(*) FROM campaign_items ci WHERE ci.subscriber_id = s.id AND ci.status = "Completed") + (SELECT COUNT(*) FROM campaign_items ci WHERE ci.subscriber_id = s.id AND ci.status = "Failed")) * 100) AS percent_received,
    (SELECT GROUP_CONCAT(g.title, ",") FROM groups g JOIN group_subscribers gs ON gs.group_id = g.id AND gs.subscriber_id = s.id) AS groups 
    FROM subscribers s ' . $where . ' ORDER BY ' . $order_by . ' ' . $order . ' LIMIT :start_results,:num_results');
// Bind params
$stmt->bindParam('start_results', $param1, PDO::PARAM_INT);
$stmt->bindParam('num_results', $param2, PDO::PARAM_INT);
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
if ($status) $stmt->bindParam('status', $status, PDO::PARAM_STR);
if ($date_from) $stmt->bindParam('date_from', $date_from, PDO::PARAM_STR);
if ($date_to) $stmt->bindParam('date_to', $date_to, PDO::PARAM_STR);
if ($confirmed != '') $stmt->bindParam('confirmed', $confirmed, PDO::PARAM_INT);
if ($group) $stmt->bindParam('group', $group, PDO::PARAM_INT);
$stmt->execute();
// Retrieve query results
$subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Delete subscriber
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare('DELETE s, gs FROM subscribers s LEFT JOIN group_subscribers gs ON gs.subscriber_id = s.id WHERE s.id = ?');
    $stmt->execute([ $_GET['delete'] ]);
    header('Location: subscribers.php?success_msg=3');
    exit;
}
// Handle success messages
if (isset($_GET['success_msg'])) {
    if ($_GET['success_msg'] == 1) {
        $success_msg = 'Subscriber created successfully!';
    }
    if ($_GET['success_msg'] == 2) {
        $success_msg = 'Subscriber updated successfully!';
    }
    if ($_GET['success_msg'] == 3) {
        $success_msg = 'Subscriber deleted successfully!';
    }
    if ($_GET['success_msg'] == 4) {
        $success_msg = 'Item(s) imported successfully! ' . $_GET['imported'] . ' item(s) were imported.';
    }
}
// Determine the URL
$url = 'subscribers.php?search_query=' . $search . (isset($_GET['status']) ? '&status=' . $_GET['status'] : '') . (isset($_GET['date_from']) ? '&date_from=' . $_GET['date_from'] : '') . (isset($_GET['date_to']) ? '&date_to=' . $_GET['date_to'] : '') . (isset($_GET['confirmed']) ? '&confirmed=' . $_GET['confirmed'] : '');
?>
<?=template_admin_header('Subscribers', 'subscribers', 'view')?>

<div class="content-title">
    <div class="title">
        <div class="icon">
            <svg width="22" height="22" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M16 17V19H2V17S2 13 9 13 16 17 16 17M12.5 7.5A3.5 3.5 0 1 0 9 11A3.5 3.5 0 0 0 12.5 7.5M15.94 13A5.32 5.32 0 0 1 18 17V19H22V17S22 13.37 15.94 13M15 4A3.39 3.39 0 0 0 13.07 4.59A5 5 0 0 1 13.07 10.41A3.39 3.39 0 0 0 15 11A3.5 3.5 0 0 0 15 4Z" /></svg>
        </div>
        <div class="txt">
            <h2>Subscribers</h2>
            <p>View, create and manage subscribers.</p>
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
    <a href="subscriber.php" class="btn">
        <svg class="icon-left" width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 80c0-17.7-14.3-32-32-32s-32 14.3-32 32V224H48c-17.7 0-32 14.3-32 32s14.3 32 32 32H192V432c0 17.7 14.3 32 32 32s32-14.3 32-32V288H400c17.7 0 32-14.3 32-32s-14.3-32-32-32H256V80z"/></svg>
        Create Subscriber
    </a>
    <form action="subscribers.php" method="get">
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
                    <option value="Subscribed"<?=$status=='Subscribed'?' selected':''?>>Subscribed</option>
                    <option value="Unsubscribed"<?=$status=='Unsubscribed'?' selected':''?>>Unsubscribed</option>
                </select>
                <label for="confirmed">Confirmed</label>
                <select name="confirmed" id="confirmed">
                    <option value=""<?=$confirmed==''?' selected':''?>>All</option>
                    <option value="1"<?=$confirmed==1?' selected':''?>>Yes</option>
                    <option value="0"<?=$confirmed==0?' selected':''?>>No</option>
                </select>
                <label for="group">Group</label>
                <select name="group" id="group">
                    <option value=""<?=$group==''?' selected':''?>>All</option>
                    <?php foreach ($groups as $g): ?>
                    <option value="<?=$g['id']?>"<?=$group==$g['id']?' selected':''?>><?=$g['title']?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Apply</button>
            </div>
        </div>
        <div class="search">
            <label for="search_query">
                <input id="search_query" type="text" name="search_query" placeholder="Search subscriber..." value="<?=htmlspecialchars($search, ENT_QUOTES)?>" class="responsive-width-100">
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
    <?php if ($confirmed != ''): ?>
    <div class="filter">
        <a href="<?=remove_url_param($url, 'confirmed')?>"><svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free --><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg></a>
        Confirmed : <?=$confirmed==1?'Yes':'No'?>
    </div>
    <?php endif; ?>
    <?php if ($group): ?>
    <div class="filter">
        <a href="<?=remove_url_param($url, 'group')?>"><svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free --><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg></a>
        <?php foreach ($groups as $g): ?>
        <?php if ($g['id'] == $group): ?>
        Group : <?=htmlspecialchars($g['title'], ENT_QUOTES)?>
        <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<div class="content-block no-pad">
    <div class="table">
        <table>
            <thead>
                <tr>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=id'?>">#<?=$order_by=='id' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td colspan="2"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=email'?>">Email<?=$order_by=='email' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td class="responsive-hidden">Groups</td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=status'?>">Status<?=$order_by=='status' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=confirmed'?>">Confirmed<?=$order_by=='confirmed' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=percent_received'?>">Completion Rate<?=$order_by=='percent_received' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=date_subbed'?>">Date Subbed<?=$order_by=='date_subbed' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td>Actions</td>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($subscribers)): ?>
                <tr>
                    <td colspan="20" class="no-results">There are no subscribers.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($subscribers as $subscriber): ?>
                <tr>
                    <td class="responsive-hidden alt"><?=$subscriber['id']?></td>
                    <td class="img">
                        <div class="profile-img">
                            <span style="background-color:<?=color_from_string($subscriber['email'])?>"><?=strtoupper(substr($subscriber['email'], 0, 1))?></span>
                        </div>
                    </td>
                    <td><?=htmlspecialchars($subscriber['email'], ENT_QUOTES)?></td>
                    <td class="responsive-hidden">
                        <?php foreach (array_filter(explode(',', $subscriber['groups'])) as $group): ?>
                        <span class="grey"><?=htmlspecialchars($group, ENT_QUOTES)?></span>
                        <?php endforeach; ?>
                    </td>
                    <td>
                        <span class="<?=str_replace(['Unsubscribed', 'Subscribed'], ['red', 'blue'], $subscriber['status'])?>">
                            <?=$subscriber['status']?>
                            <?=$subscriber['status']=='Unsubscribed' && $subscriber['unsub_reason']?' <a href="#" class="recipient-error unsub-msg" title="' . nl2br(htmlspecialchars($subscriber['unsub_reason'], ENT_QUOTES)) . '"><svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM169.8 165.3c7.9-22.3 29.1-37.3 52.8-37.3l58.3 0c34.9 0 63.1 28.3 63.1 63.1c0 22.6-12.1 43.5-31.7 54.8L280 264.4c-.2 13-10.9 23.6-24 23.6c-13.3 0-24-10.7-24-24l0-13.5c0-8.6 4.6-16.5 12.1-20.8l44.3-25.4c4.7-2.7 7.6-7.7 7.6-13.1c0-8.4-6.8-15.1-15.1-15.1l-58.3 0c-3.4 0-6.4 2.1-7.5 5.3l-.4 1.2c-4.4 12.5-18.2 19-30.6 14.6s-19-18.2-14.6-30.6l.4-1.2zM224 352a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z"/></svg></a>':''?>
                        </span>
                    </td>
                    <td>
                        <?php if ($subscriber['confirmed']): ?>
                        <svg stroke="#34aa6b" fill="#34aa6b" width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21,7L9,19L3.5,13.5L4.91,12.09L9,16.17L19.59,5.59L21,7Z" /></svg>
                        <?php else: ?>
                        <svg stroke="#b64343" fill="#b64343" width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z" /></svg>
                        <?php endif; ?>
                    </td>
                    <td class="responsive-hidden">
                        <?php if ($subscriber['percent_received'] == '' || $subscriber['percent_received'] == null): ?>
                        --
                        <?php elseif (intval($subscriber['percent_received']) >= 90): ?>
                        <span class="green small"><?=num_format($subscriber['percent_received'], 2) . '%'?></span>
                        <?php elseif (intval($subscriber['percent_received']) >= 60): ?>
                        <span class="orange small"><?=num_format($subscriber['percent_received'], 2) . '%'?></span>
                        <?php else: ?>
                        <span class="red small"><?=num_format($subscriber['percent_received'], 2) . '%'?></span>
                        <?php endif; ?>
                    </td>
                    <td class="responsive-hidden alt"><?=date('F j, Y H:ia', strtotime($subscriber['date_subbed']))?></td>
                    <td class="actions">
                        <div class="table-dropdown">
                            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M8 256a56 56 0 1 1 112 0A56 56 0 1 1 8 256zm160 0a56 56 0 1 1 112 0 56 56 0 1 1 -112 0zm216-56a56 56 0 1 1 0 112 56 56 0 1 1 0-112z"/></svg>
                            <div class="table-dropdown-items">
                                <a href="subscriber.php?id=<?=$subscriber['id']?>">
                                    <span class="icon">
                                        <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M471.6 21.7c-21.9-21.9-57.3-21.9-79.2 0L362.3 51.7l97.9 97.9 30.1-30.1c21.9-21.9 21.9-57.3 0-79.2L471.6 21.7zm-299.2 220c-6.1 6.1-10.8 13.6-13.5 21.9l-29.6 88.8c-2.9 8.6-.6 18.1 5.8 24.6s15.9 8.7 24.6 5.8l88.8-29.6c8.2-2.7 15.7-7.4 21.9-13.5L437.7 172.3 339.7 74.3 172.4 241.7zM96 64C43 64 0 107 0 160V416c0 53 43 96 96 96H352c53 0 96-43 96-96V320c0-17.7-14.3-32-32-32s-32 14.3-32 32v96c0 17.7-14.3 32-32 32H96c-17.7 0-32-14.3-32-32V160c0-17.7 14.3-32 32-32h96c17.7 0 32-14.3 32-32s-14.3-32-32-32H96z"/></svg>
                                    </span>
                                    Edit
                                </a>
                                <a class="red" href="subscribers.php?delete=<?=$subscriber['id']?>" onclick="return confirm('Are you sure you want to delete this subscriber?')">
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
    <span>Page <?=$pagination_page?> of <?=ceil($subscribers_total / $results_per_page) == 0 ? 1 : ceil($subscribers_total / $results_per_page)?></span>
    <?php if ($pagination_page * $results_per_page < $subscribers_total): ?>
    <a href="<?=$url?>&pagination_page=<?=$pagination_page+1?>&order=<?=$order?>&order_by=<?=$order_by?>">Next</a>
    <?php endif; ?>
</div>

<?=template_admin_footer()?>