<?php
require 'assets/includes/admin_config.php';
include_once '../assets/includes/components.php';

// Num format function for newsletter system
if (!function_exists('num_format')) {
    function num_format($num, $decimals = 0, $decimal_separator = '.', $thousands_separator = ',') {
        return number_format(empty($num) || $num == null || !is_numeric($num) ? 0 : $num, $decimals, $decimal_separator, $thousands_separator);
    }
}
// Current date
$date = date('Y-m-d');
// Get the total number of new subscribers within the last day
$stmt = $pdo->prepare('SELECT 
    s.*, 
    ((SELECT COUNT(*) FROM campaign_items ci WHERE ci.subscriber_id = s.id AND ci.status = "Completed") / ((SELECT COUNT(*) FROM campaign_items ci WHERE ci.subscriber_id = s.id AND ci.status = "Completed") + (SELECT COUNT(*) FROM campaign_items ci WHERE ci.subscriber_id = s.id AND ci.status = "Failed")) * 100) AS percent_received,
    (SELECT GROUP_CONCAT(g.title, ",") FROM `groups` g JOIN group_subscribers gs ON gs.group_id = g.id AND gs.subscriber_id = s.id) AS `groups` 
    FROM subscribers s WHERE cast(s.date_subbed as DATE) = cast(? as DATE) ORDER BY s.date_subbed DESC');
$stmt->execute([ $date ]);
$subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);
// SQL query to get all campaigns from the "campaigns" table
$campaigns = $pdo->query('SELECT 
    c.*, 
    (SELECT COUNT(*) FROM campaign_items ci WHERE ci.campaign_id = c.id) AS total_items,
    (SELECT COUNT(*) FROM campaign_items ci WHERE ci.campaign_id = c.id AND (ci.status = "Completed" OR ci.status = "Cancelled")) AS total_completed_items,
    (SELECT COUNT(*) FROM campaign_items ci WHERE ci.campaign_id = c.id AND ci.status = "Failed") AS total_failed_items,
    (SELECT COUNT(*) FROM campaign_clicks cc WHERE cc.campaign_id = c.id) AS total_clicks,
    (SELECT COUNT(*) FROM campaign_unsubscribes cu WHERE cu.campaign_id = c.id) AS total_unsubscribes,
    (SELECT COUNT(*) FROM campaign_opens co WHERE co.campaign_id = c.id) AS total_opens  
    FROM campaigns c WHERE c.status = "Active"
')->fetchAll(PDO::FETCH_ASSOC);
?>
<?=template_admin_header('Dashboard', 'dashboard')?>

<?=generate_breadcrumbs([
    ['label' => 'Newsletter Dashboard']
])?>

<div class="content-title">
    <div class="icon alt"><?=svg_icon_dashboard()?></div>
    <div class="txt">
        <h2>Newsletter Dashboard</h2>
        <p class="subtitle">Overview of subscribers, campaigns, and newsletter statistics</p>
    </div>
</div>

<div class="dashboard">
    <div class="content-block stat">
        <div class="data">
            <h3>New Subscribers <span>today</span></h3>
            <p><?=num_format(count($subscribers))?></p>
        </div>
        <div class="icon">
            <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M15,14C12.33,14 7,15.33 7,18V20H23V18C23,15.33 17.67,14 15,14M6,10V7H4V10H1V12H4V15H6V12H9V10M15,12A4,4 0 0,0 19,8A4,4 0 0,0 15,4A4,4 0 0,0 11,8A4,4 0 0,0 15,12Z" /></svg>
        </div>    
        <div class="footer">
            <svg width="11" height="11" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M105.1 202.6c7.7-21.8 20.2-42.3 37.8-59.8c62.5-62.5 163.8-62.5 226.3 0L386.3 160H352c-17.7 0-32 14.3-32 32s14.3 32 32 32H463.5c0 0 0 0 0 0h.4c17.7 0 32-14.3 32-32V80c0-17.7-14.3-32-32-32s-32 14.3-32 32v35.2L414.4 97.6c-87.5-87.5-229.3-87.5-316.8 0C73.2 122 55.6 150.7 44.8 181.4c-5.9 16.7 2.9 34.9 19.5 40.8s34.9-2.9 40.8-19.5zM39 289.3c-5 1.5-9.8 4.2-13.7 8.2c-4 4-6.7 8.8-8.1 14c-.3 1.2-.6 2.5-.8 3.8c-.3 1.7-.4 3.4-.4 5.1V432c0 17.7 14.3 32 32 32s32-14.3 32-32V396.9l17.6 17.5 0 0c87.5 87.4 229.3 87.4 316.7 0c24.4-24.4 42.1-53.1 52.9-83.7c5.9-16.7-2.9-34.9-19.5-40.8s-34.9 2.9-40.8 19.5c-7.7 21.8-20.2 42.3-37.8 59.8c-62.5 62.5-163.8 62.5-226.3 0l-.1-.1L125.6 352H160c17.7 0 32-14.3 32-32s-14.3-32-32-32H48.4c-1.6 0-3.2 .1-4.8 .3s-3.1 .5-4.6 1z"/></svg>
            Total subscribers for today
        </div>
    </div>

    <div class="content-block stat green">
        <div class="data">
            <h3>Total Subscribers</h3>
            <p><?=num_format($subscribers_total)?></p>
        </div>
        <div class="icon">
            <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M16 17V19H2V17S2 13 9 13 16 17 16 17M12.5 7.5A3.5 3.5 0 1 0 9 11A3.5 3.5 0 0 0 12.5 7.5M15.94 13A5.32 5.32 0 0 1 18 17V19H22V17S22 13.37 15.94 13M15 4A3.39 3.39 0 0 0 13.07 4.59A5 5 0 0 1 13.07 10.41A3.39 3.39 0 0 0 15 11A3.5 3.5 0 0 0 15 4Z" /></svg>
        </div>    
        <div class="footer">
            <svg width="11" height="11" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M105.1 202.6c7.7-21.8 20.2-42.3 37.8-59.8c62.5-62.5 163.8-62.5 226.3 0L386.3 160H352c-17.7 0-32 14.3-32 32s14.3 32 32 32H463.5c0 0 0 0 0 0h.4c17.7 0 32-14.3 32-32V80c0-17.7-14.3-32-32-32s-32 14.3-32 32v35.2L414.4 97.6c-87.5-87.5-229.3-87.5-316.8 0C73.2 122 55.6 150.7 44.8 181.4c-5.9 16.7 2.9 34.9 19.5 40.8s34.9-2.9 40.8-19.5zM39 289.3c-5 1.5-9.8 4.2-13.7 8.2c-4 4-6.7 8.8-8.1 14c-.3 1.2-.6 2.5-.8 3.8c-.3 1.7-.4 3.4-.4 5.1V432c0 17.7 14.3 32 32 32s32-14.3 32-32V396.9l17.6 17.5 0 0c87.5 87.4 229.3 87.4 316.7 0c24.4-24.4 42.1-53.1 52.9-83.7c5.9-16.7-2.9-34.9-19.5-40.8s-34.9 2.9-40.8 19.5c-7.7 21.8-20.2 42.3-37.8 59.8c-62.5 62.5-163.8 62.5-226.3 0l-.1-.1L125.6 352H160c17.7 0 32-14.3 32-32s-14.3-32-32-32H48.4c-1.6 0-3.2 .1-4.8 .3s-3.1 .5-4.6 1z"/></svg>
            Total subscribers
        </div>
    </div>

    <div class="content-block stat cyan">
        <div class="data">
            <h3>Active Campaigns</h3>
            <p><?=num_format(count($campaigns))?></p>
        </div>
        <div class="icon">
            <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12,18A6,6 0 0,1 6,12C6,11 6.25,10.03 6.7,9.2L5.24,7.74C4.46,8.97 4,10.43 4,12A8,8 0 0,0 12,20V23L16,19L12,15M12,4V1L8,5L12,9V6A6,6 0 0,1 18,12C18,13 17.75,13.97 17.3,14.8L18.76,16.26C19.54,15.03 20,13.57 20,12A8,8 0 0,0 12,4Z" /></svg>
        </div>
        <div class="footer">
            <svg width="11" height="11" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M105.1 202.6c7.7-21.8 20.2-42.3 37.8-59.8c62.5-62.5 163.8-62.5 226.3 0L386.3 160H352c-17.7 0-32 14.3-32 32s14.3 32 32 32H463.5c0 0 0 0 0 0h.4c17.7 0 32-14.3 32-32V80c0-17.7-14.3-32-32-32s-32 14.3-32 32v35.2L414.4 97.6c-87.5-87.5-229.3-87.5-316.8 0C73.2 122 55.6 150.7 44.8 181.4c-5.9 16.7 2.9 34.9 19.5 40.8s34.9-2.9 40.8-19.5zM39 289.3c-5 1.5-9.8 4.2-13.7 8.2c-4 4-6.7 8.8-8.1 14c-.3 1.2-.6 2.5-.8 3.8c-.3 1.7-.4 3.4-.4 5.1V432c0 17.7 14.3 32 32 32s32-14.3 32-32V396.9l17.6 17.5 0 0c87.5 87.4 229.3 87.4 316.7 0c24.4-24.4 42.1-53.1 52.9-83.7c5.9-16.7-2.9-34.9-19.5-40.8s-34.9 2.9-40.8 19.5c-7.7 21.8-20.2 42.3-37.8 59.8c-62.5 62.5-163.8 62.5-226.3 0l-.1-.1L125.6 352H160c17.7 0 32-14.3 32-32s-14.3-32-32-32H48.4c-1.6 0-3.2 .1-4.8 .3s-3.1 .5-4.6 1z"/></svg>
            Total active campaigns
        </div>
    </div>

    <div class="content-block stat red">
        <div class="data">
            <h3>Total Newsletters</h3>
            <p><?=num_format($newsletters_total)?></p>
        </div>
        <div class="icon">
            <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M22,6V4L14,9L6,4V6L14,11L22,6M22,2A2,2 0 0,1 24,4V16A2,2 0 0,1 22,18H6C4.89,18 4,17.1 4,16V4C4,2.89 4.89,2 6,2H22M2,6V20H20V22H2A2,2 0 0,1 0,20V6H2Z" /></svg>
        </div>    
        <div class="footer">
            <svg width="11" height="11" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M105.1 202.6c7.7-21.8 20.2-42.3 37.8-59.8c62.5-62.5 163.8-62.5 226.3 0L386.3 160H352c-17.7 0-32 14.3-32 32s14.3 32 32 32H463.5c0 0 0 0 0 0h.4c17.7 0 32-14.3 32-32V80c0-17.7-14.3-32-32-32s-32 14.3-32 32v35.2L414.4 97.6c-87.5-87.5-229.3-87.5-316.8 0C73.2 122 55.6 150.7 44.8 181.4c-5.9 16.7 2.9 34.9 19.5 40.8s34.9-2.9 40.8-19.5zM39 289.3c-5 1.5-9.8 4.2-13.7 8.2c-4 4-6.7 8.8-8.1 14c-.3 1.2-.6 2.5-.8 3.8c-.3 1.7-.4 3.4-.4 5.1V432c0 17.7 14.3 32 32 32s32-14.3 32-32V396.9l17.6 17.5 0 0c87.5 87.4 229.3 87.4 316.7 0c24.4-24.4 42.1-53.1 52.9-83.7c5.9-16.7-2.9-34.9-19.5-40.8s-34.9 2.9-40.8 19.5c-7.7 21.8-20.2 42.3-37.8 59.8c-62.5 62.5-163.8 62.5-226.3 0l-.1-.1L125.6 352H160c17.7 0 32-14.3 32-32s-14.3-32-32-32H48.4c-1.6 0-3.2 .1-4.8 .3s-3.1 .5-4.6 1z"/></svg>
            Total newsletters
        </div>
    </div>
</div>

<div class="content-title">
    <div class="title">
        <div class="icon alt">
            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12,18A6,6 0 0,1 6,12C6,11 6.25,10.03 6.7,9.2L5.24,7.74C4.46,8.97 4,10.43 4,12A8,8 0 0,0 12,20V23L16,19L12,15M12,4V1L8,5L12,9V6A6,6 0 0,1 18,12C18,13 17.75,13.97 17.3,14.8L18.76,16.26C19.54,15.03 20,13.57 20,12A8,8 0 0,0 12,4Z" /></svg>
        </div>
        <div class="txt">
            <h2>Active Campaigns</h2>
            <p>List of active campaigns.</p>
        </div>
    </div>
</div>

<div class="content-block no-pad">
    <div class="table ajax-update">
        <table>
            <thead>
                <tr>
                    <td>Title</td>
                    <td>Sent</td>
                    <td class="responsive-hidden">Opens</td>
                    <td class="responsive-hidden">Clicks</td>
                    <td class="responsive-hidden">Fails</td>
                    <td class="responsive-hidden">Unsubscribes</td>
                    <td class="responsive-hidden">Status</td>
                    <td>Date Scheduled</td>
                    <td>Actions</td>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($campaigns)): ?>
                <tr>
                    <td colspan="20" class="no-results">There are no active campaigns.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($campaigns as $campaign): ?>
                <tr>
                    <td><a href="campaign_view.php?id=<?=$campaign['id']?>" class="link3"><?=htmlspecialchars($campaign['title'], ENT_QUOTES)?></a></td>
                    <td>
                        <div class="progress">
                            <span class="txt"><?=$campaign['total_completed_items']?> of <?=$campaign['total_items']?></span>
                            <div class="bot">
                                <span class="per"><?=$campaign['total_items'] ? num_format(($campaign['total_completed_items'] * 100) / $campaign['total_items']) : 0?>%</span>
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
                                <span class="per"><?=$campaign['total_items'] ? num_format(($campaign['total_opens'] * 100) / $campaign['total_items']) : 0?>%</span>
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
                                <span class="per"><?=$campaign['total_items'] ? num_format(($campaign['total_clicks'] * 100) / $campaign['total_items']) : 0?>%</span>
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
                                <span class="per"><?=$campaign['total_items'] ? num_format(($campaign['total_failed_items'] * 100) / $campaign['total_items']) : 0?>%</span>
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
                                <span class="per"><?=$campaign['total_items'] ? num_format(($campaign['total_unsubscribes'] * 100) / $campaign['total_items']) : 0?>%</span>
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

<div class="content-title" style="padding-top:40px">
    <div class="title">
        <div class="icon alt">
            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M16 17V19H2V17S2 13 9 13 16 17 16 17M12.5 7.5A3.5 3.5 0 1 0 9 11A3.5 3.5 0 0 0 12.5 7.5M15.94 13A5.32 5.32 0 0 1 18 17V19H22V17S22 13.37 15.94 13M15 4A3.39 3.39 0 0 0 13.07 4.59A5 5 0 0 1 13.07 10.41A3.39 3.39 0 0 0 15 11A3.5 3.5 0 0 0 15 4Z" /></svg>
        </div>
        <div class="txt">
            <h2>New Subscribers</h2>
            <p>List of new subscribers.</p>
        </div>
    </div>
</div>

<div class="content-block no-pad">
    <div class="table">
        <table>
            <thead>
                <tr>
                    <td colspan="2">Email</td>
                    <td class="responsive-hidden">Groups</td>
                    <td>Status</td>
                    <td>Confirmed</td>
                    <td class="responsive-hidden">Completion Rate</td>
                    <td class="responsive-hidden">Date Subbed</td>
                    <td>Actions</td>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($subscribers)): ?>
                <tr>
                    <td colspan="20" class="no-results">There are no new subscribers.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($subscribers as $subscriber): ?>
                <tr>
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

<?=template_admin_footer()?>