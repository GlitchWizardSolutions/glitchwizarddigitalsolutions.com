<?php
require 'assets/includes/admin_config.php';
include_once '../assets/includes/components.php';
// Delete the newsletter
if (isset($_GET['delete'])) {
    // Delete the newsletter
    $stmt = $pdo->prepare('DELETE FROM newsletters WHERE id = ?');
    $stmt->execute([ $_GET['delete'] ]);
    header('Location: newsletters.php?success_msg=3');
    exit;
}
// Retrieve the GET request parameters (if specified)
$pagination_page = isset($_GET['pagination_page']) ? $_GET['pagination_page'] : 1;
$search = isset($_GET['search_query']) ? $_GET['search_query'] : '';
// Order by column
$order = isset($_GET['order']) && $_GET['order'] == 'DESC' ? 'DESC' : 'ASC';
// Add/remove columns to the whitelist array
$order_by_whitelist = ['id','title','last_scheduled','submit_date','sent_items','opens','clicks','unsubscribes'];
$order_by = isset($_GET['order_by']) && in_array($_GET['order_by'], $order_by_whitelist) ? $_GET['order_by'] : 'id';
// Number of results per pagination page
$results_per_page = 10;
// Declare query param variables
$param1 = ($pagination_page - 1) * $results_per_page;
$param2 = $results_per_page;
$param3 = '%' . $search . '%';
// SQL where clause
$where = '';
$where .= $search ? 'WHERE (n.title LIKE :search OR n.content LIKE :search) ' : '';
// Filters
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
if ($date_from) {
    $where .= ($where ? 'AND ' : 'WHERE ') . 'submit_date >= :date_from ';
}
if ($date_to) {
    $where .= ($where ? 'AND ' : 'WHERE ') . 'submit_date <= :date_to ';
}
// Retrieve the total number of newsletters
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM newsletters n ' . $where);
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
if ($date_from) $stmt->bindParam('date_from', $date_from, PDO::PARAM_STR);
if ($date_to) $stmt->bindParam('date_to', $date_to, PDO::PARAM_STR);
$stmt->execute();
$newsletters_total = $stmt->fetchColumn();
// SQL query to get all newsletters from the "newsletters" table
$stmt = $pdo->prepare('SELECT 
    n.*, 
    (SELECT COUNT(*) FROM campaigns c JOIN campaign_clicks cc ON cc.campaign_id = c.id WHERE c.newsletter_id = n.id) AS clicks,
    (SELECT COUNT(*) FROM campaigns c JOIN campaign_opens co ON co.campaign_id = c.id WHERE c.newsletter_id = n.id) AS opens, 
    (SELECT COUNT(*) FROM campaigns c JOIN campaign_unsubscribes cu ON cu.campaign_id = c.id WHERE c.newsletter_id = n.id) AS unsubscribes,   
    (SELECT COUNT(*) FROM campaigns c JOIN campaign_items ci ON ci.campaign_id = c.id AND ci.status = "Completed" WHERE c.newsletter_id = n.id) AS sent_items, 
    (SELECT COUNT(*) FROM campaigns c JOIN campaign_items ci ON ci.campaign_id = c.id WHERE c.newsletter_id = n.id) AS total_items   
    FROM newsletters n ' . $where . ' ORDER BY ' . $order_by . ' ' . $order . ' LIMIT :start_results,:num_results
');
// Bind params
$stmt->bindParam('start_results', $param1, PDO::PARAM_INT);
$stmt->bindParam('num_results', $param2, PDO::PARAM_INT);
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
if ($date_from) $stmt->bindParam('date_from', $date_from, PDO::PARAM_STR);
if ($date_to) $stmt->bindParam('date_to', $date_to, PDO::PARAM_STR);
$stmt->execute();
// Retrieve query results
$newsletters = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Handle success messages
if (isset($_GET['success_msg'])) {
    if ($_GET['success_msg'] == 1) {
        $success_msg = 'Newsletter created successfully!';
    }
    if ($_GET['success_msg'] == 2) {
        $success_msg = 'Newsletter updated successfully!';
    }
    if ($_GET['success_msg'] == 3) {
        $success_msg = 'Newsletter deleted successfully!';
    }
    if ($_GET['success_msg'] == 4) {
        $success_msg = 'Newsletter(s) imported successfully! ' . $_GET['imported'] . ' newsletter(s) were imported.';
    }
}
// Determine the URL
$url = 'newsletters.php?search_query=' . $search . (isset($_GET['date_from']) ? '&date_from=' . $_GET['date_from'] : '') . (isset($_GET['date_to']) ? '&date_to=' . $_GET['date_to'] : '');
?>
<?=template_admin_header('Newsletters', 'newsletters', 'newsletters')?>

<?=generate_breadcrumbs([
    ['label' => 'Newsletter System', 'url' => 'index.php'],
    ['label' => 'Newsletters']
])?>

<div class="content-title mb-3">
    <div class="title">
        <i class="fa-solid fa-newspaper"></i>
        <div class="txt">
            <h2>Newsletters</h2>
            <p>Manage email newsletter templates</p>
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
    <a href="newsletter.php" class="btn btn-success">
        <svg class="icon-left" width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 80c0-17.7-14.3-32-32-32s-32 14.3-32 32V224H48c-17.7 0-32 14.3-32 32s14.3 32 32 32H192V432c0 17.7 14.3 32 32 32s32-14.3 32-32V288H400c17.7 0 32-14.3 32-32s-14.3-32-32-32H256V80z"/></svg>
        Create Newsletter
    </a>
    <a href="newsletters_import.php" class="btn btn-primary">
        <svg class="icon-left" width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M288 109.3V352c0 17.7-14.3 32-32 32s-32-14.3-32-32V109.3l-73.4 73.4c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3l128-128c12.5-12.5 32.8-12.5 45.3 0l128 128c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L288 109.3zM64 352H192c0 35.3 28.7 64 64 64s64-28.7 64-64H448c35.3 0 64 28.7 64 64v32c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V416c0-35.3 28.7-64 64-64zM432 456a24 24 0 1 0 0-48 24 24 0 1 0 0 48z"/></svg>
        Bulk Import
    </a>
    <a href="newsletters_export.php" class="btn btn-primary">
        <svg class="icon-left" width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M288 32c0-17.7-14.3-32-32-32s-32 14.3-32 32V274.7l-73.4-73.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l128 128c12.5 12.5 32.8 12.5 45.3 0l128-128c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L288 274.7V32zM64 352c-35.3 0-64 28.7-64 64v32c0 35.3 28.7 64 64 64H448c35.3 0 64-28.7 64-64V416c0-35.3-28.7-64-64-64H346.5l-45.3 45.3c-25 25-65.5 25-90.5 0L165.5 352H64zm368 56a24 24 0 1 1 0 48 24 24 0 1 1 0-48z"/></svg>
        Bulk Export
    </a>
    <form action="newsletters.php" method="get">
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
                <button type="submit">Apply</button>
            </div>
        </div>
        <div class="search">
            <label for="search_query">
                <input id="search_query" type="text" name="search_query" placeholder="Search newsletter..." value="<?=htmlspecialchars($search, ENT_QUOTES)?>" class="responsive-width-100">
                <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"/></svg>
            </label>
        </div>
    </form>
</div>

<div class="filter-list">
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
        <table>
            <thead>
                <tr>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=id'?>">#<?=$order_by=='id' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=title'?>">Title<?=$order_by=='title' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=sent_items'?>">Sent<?=$order_by=='sent_items' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=opens'?>">Opens<?=$order_by=='opens' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=clicks'?>">Clicks<?=$order_by=='clicks' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=unsubscribes'?>">Unsubscribes<?=$order_by=='unsubscribes' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=last_scheduled'?>">Last Scheduled<?=$order_by=='last_scheduled' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=submit_date'?>">Created<?=$order_by=='submit_date' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td>Actions</td>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($newsletters)): ?>
                <tr>
                    <td colspan="20" class="no-results">There are no newsletters.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($newsletters as $newsletter): ?>
                <tr>
                    <td class="responsive-hidden alt"><?=$newsletter['id']?></td>
                    <td><?=htmlspecialchars($newsletter['title'], ENT_QUOTES)?></td>
                    <td>
                        <div class="progress">
                            <span class="txt"><?=$newsletter['sent_items']?> of <?=$newsletter['total_items']?></span>
                            <div class="bot">
                                <span class="per"><?=$newsletter['total_items'] ? number_format(($newsletter['sent_items'] * 100) / $newsletter['total_items']) : 0?>%</span>
                                <div class="bar">
                                    <span style="width:<?=$newsletter['total_items'] ? ($newsletter['sent_items'] * 100) / $newsletter['total_items'] : 0?>%"></span>
                                </div>
                            </div>
                        </div>    
                    </td>
                    <td>
                        <div class="progress">
                            <span class="txt"><?=$newsletter['opens']?> of <?=$newsletter['total_items']?></span>
                            <div class="bot">
                                <span class="per"><?=$newsletter['total_items'] ? number_format(($newsletter['opens'] * 100) / $newsletter['total_items']) : 0?>%</span>
                                <div class="bar">
                                    <span style="width:<?=$newsletter['total_items'] ? ($newsletter['opens'] * 100) / $newsletter['total_items'] : 0?>%"></span>
                                </div>
                            </div>
                        </div>  
                    </td>
                    <td>
                        <div class="progress">
                            <span class="txt"><?=$newsletter['clicks']?> of <?=$newsletter['total_items']?></span>
                            <div class="bot">
                                <span class="per"><?=$newsletter['total_items'] ? number_format(($newsletter['clicks'] * 100) / $newsletter['total_items']) : 0?>%</span>
                                <div class="bar">
                                    <span style="width:<?=$newsletter['total_items'] ? ($newsletter['clicks'] * 100) / $newsletter['total_items'] : 0?>%"></span>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="progress">
                            <span class="txt"><?=$newsletter['unsubscribes']?> of <?=$newsletter['total_items']?></span>
                            <div class="bot">
                                <span class="per"><?=$newsletter['total_items'] ? number_format(($newsletter['unsubscribes'] * 100) / $newsletter['total_items']) : 0?>%</span>
                                <div class="bar">
                                    <span class="red" style="width:<?=$newsletter['total_items'] ? ($newsletter['unsubscribes'] * 100) / $newsletter['total_items'] : 0?>%"></span>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="responsive-hidden alt"><?=$newsletter['last_scheduled']==null?'--':date('F j, Y H:ia', strtotime($newsletter['last_scheduled']))?></td>
                    <td class="responsive-hidden alt"><?=date('F j, Y H:ia', strtotime($newsletter['submit_date']))?></td>
                    <td class="actions">
                        <div class="table-dropdown">
                            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M8 256a56 56 0 1 1 112 0A56 56 0 1 1 8 256zm160 0a56 56 0 1 1 112 0 56 56 0 1 1 -112 0zm216-56a56 56 0 1 1 0 112 56 56 0 1 1 0-112z"/></svg>
                            <div class="table-dropdown-items">
                                <a href="newsletter.php?copy=<?=$newsletter['id']?>">
                                    <span class="icon">
                                        <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M11,17H4A2,2 0 0,1 2,15V3A2,2 0 0,1 4,1H16V3H4V15H11V13L15,16L11,19V17M19,21V7H8V13H6V7A2,2 0 0,1 8,5H19A2,2 0 0,1 21,7V21A2,2 0 0,1 19,23H8A2,2 0 0,1 6,21V19H8V21H19Z" /></svg>
                                    </span>
                                    Duplicate
                                </a>
                                <a href="newsletter.php?id=<?=$newsletter['id']?>">
                                    <span class="icon">
                                        <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M471.6 21.7c-21.9-21.9-57.3-21.9-79.2 0L362.3 51.7l97.9 97.9 30.1-30.1c21.9-21.9 21.9-57.3 0-79.2L471.6 21.7zm-299.2 220c-6.1 6.1-10.8 13.6-13.5 21.9l-29.6 88.8c-2.9 8.6-.6 18.1 5.8 24.6s15.9 8.7 24.6 5.8l88.8-29.6c8.2-2.7 15.7-7.4 21.9-13.5L437.7 172.3 339.7 74.3 172.4 241.7zM96 64C43 64 0 107 0 160V416c0 53 43 96 96 96H352c53 0 96-43 96-96V320c0-17.7-14.3-32-32-32s-32 14.3-32 32v96c0 17.7-14.3 32-32 32H96c-17.7 0-32-14.3-32-32V160c0-17.7 14.3-32 32-32h96c17.7 0 32-14.3 32-32s-14.3-32-32-32H96z"/></svg>
                                    </span>
                                    Edit
                                </a>
                                <a class="red" href="newsletters.php?delete=<?=$newsletter['id']?>" onclick="return confirm('Are you sure you want to delete this newsletter?')">
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
    <span>Page <?=$pagination_page?> of <?=ceil($newsletters_total / $results_per_page) == 0 ? 1 : ceil($newsletters_total / $results_per_page)?></span>
    <?php if ($pagination_page * $results_per_page < $newsletters_total): ?>
    <a href="<?=$url?>&pagination_page=<?=$pagination_page+1?>&order=<?=$order?>&order_by=<?=$order_by?>">Next</a>
    <?php endif; ?>
</div>

<?=template_admin_footer()?>