<?php
require 'assets/includes/admin_config.php';
include_once '../assets/includes/components.php';
// Get the total number of new subscribers within the last day
$stmt = $pdo->prepare('SELECT * FROM subscribers WHERE cast(date_subbed as DATE) = cast(now() as DATE) ORDER BY date_subbed DESC');
$stmt->execute();
$subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Get the total number of subscribers
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM subscribers');
$stmt->execute();
$subscribers_total = $stmt->fetchColumn();
// Get the total number of newsletters
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM newsletters');
$stmt->execute();
$newsletters_total = $stmt->fetchColumn();
// SQL query to get all campaigns from the "campaigns" table
$stmt = $pdo->prepare('SELECT 
    c.*, 
    (SELECT COUNT(*) FROM campaign_items ci WHERE ci.campaign_id = c.id) AS total_items,
    (SELECT COUNT(*) FROM campaign_items ci WHERE ci.campaign_id = c.id AND (ci.status = "Completed" OR ci.status = "Cancelled")) AS total_completed_items,
    (SELECT COUNT(*) FROM campaign_items ci WHERE ci.campaign_id = c.id AND ci.status = "Failed") AS total_failed_items,
    (SELECT COUNT(*) FROM campaign_clicks cc WHERE cc.campaign_id = c.id) AS total_clicks,
    (SELECT COUNT(*) FROM campaign_unsubscribes cu WHERE cu.campaign_id = c.id) AS total_unsubscribes,
    (SELECT COUNT(*) FROM campaign_opens co WHERE co.campaign_id = c.id) AS total_opens  
    FROM campaigns c WHERE c.status = "Active"
');
$stmt->execute();
// Retrieve query results
$campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?=template_admin_header('Newsletters', 'newsletters')?>

<?=generate_breadcrumbs([
    ['label' => 'Newsletter Overview']
])?>

<div class="content-title">
    <div class="icon alt"><?=svg_icon_newsletter()?></div>
    <div class="txt">
        <h2 class="responsive-width-100">Newsletter System</h2>
        <p class="subtitle">Complete overview of newsletter operations</p>
    </div>
</div>
<div class="dashboard">
    <div class="content-block stat">
        <div class="data">
            <h3>New Subscribers</h3>
            <p><?=number_format(count($subscribers))?></p>
        </div>
        <i class="fa-solid fa-user-clock"></i>
        <div class="footer">
            <i class="fa-solid fa-rotate fa-xs"></i>Total subscribers for today
        </div>
    </div>

    <div class="content-block stat">
        <div class="data">
            <h3>Total Subscribers</h3>
            <p><?=number_format($subscribers_total)?></p>
        </div>
        <i class="fa-solid fa-users"></i>
        <div class="footer">
            <i class="fa-solid fa-rotate fa-xs"></i>Total subscribers
        </div>
    </div>

    <div class="content-block stat">
        <div class="data">
            <h3>Active Campaigns</h3>
            <p><?=number_format(count($campaigns))?></p>
        </div>
        <i class="fa-solid fa-list"></i>
        <div class="footer">
            <i class="fa-solid fa-rotate fa-xs"></i>Total campaigns
        </div>
    </div>

    <div class="content-block stat">
        <div class="data">
            <h3>Total Newsletters</h3>
            <p><?=number_format($newsletters_total)?></p>
        </div>
        <i class="fas fa-envelope"></i>
        <div class="footer">
            <i class="fa-solid fa-rotate fa-xs"></i>Total newsletters
        </div>
    </div>
</div>

<div class="content-title">
    <h2>Active Campaigns</h2>
</div>

<div class="content-block">
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
                    <td>Status</td>
                    <td>Date Scheduled</td>
                    <td>Actions</td>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($campaigns)): ?>
                <tr>
                    <td colspan="10" style="text-align:center;">There are no active campaigns</td>
                </tr>
                <?php else: ?>
                <?php foreach ($campaigns as $campaign): ?>
                <tr>
                    <td><?=htmlspecialchars($campaign['title'], ENT_QUOTES)?></td>
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
                    <td class="status-container">
                        <div class="status" data-id="<?=$campaign['id']?>">
                            <span title="<?=htmlspecialchars($campaign['status'], ENT_QUOTES)?>" class="<?=strtolower(htmlspecialchars($campaign['status'], ENT_QUOTES))?>"></span>
                            <i class="fa-solid fa-caret-down"></i>
                            <div class="dropdown">
                                <a href="#" data-value="Active">Active</a>
                                <a href="#" data-value="Inactive">Inactive</a>
                                <a href="#" data-value="Paused">Pause</a>
                                <a href="#" data-value="Cancelled">Cancel</a>
                            </div>
                        </div>
                    </td>
                    <td><?=date('F j, Y H:ia', strtotime($campaign['submit_date']))?></td>
                    <td>
                        <a href="campaign_view.php?id=<?=$campaign['id']?>" class="link1">View</a>
                        <a href="campaign.php?id=<?=$campaign['id']?>" class="link1">Edit</a>
                        <a href="campaigns.php?delete=<?=$campaign['id']?>" class="link1" onclick="return confirm('Are you sure you want to delete this campaign?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<br><br>

<div class="content-title">
    <h2>New Subscribers</h2>
</div>

<div class="content-block">
    <div class="table">
        <table>
            <thead>
                <tr>
                    <td colspan="2">Email</td>
                    <td>Status</td>
                    <td>Confirmed</td>
                    <td>Date Subbed</td>
                    <td>Actions</td>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($subscribers)): ?>
                <tr>
                    <td colspan="8" style="text-align:center;">There are no new subscribers</td>
                </tr>
                <?php else: ?>
                <?php foreach ($subscribers as $subscriber): ?>
                <tr>
                    <td class="img">
                        <span style="background-color:<?=color_from_string($subscriber['email'])?>"><?=strtoupper(substr($subscriber['email'], 0, 1))?></span>
                    </td>
                    <td><?=htmlspecialchars($subscriber['email'], ENT_QUOTES)?></td>
                    <td><?=$subscriber['status']?></td>
                    <td><?=$subscriber['confirmed']?'Yes':'No'?></td>
                    <td><?=date('F j, Y H:ia', strtotime($subscriber['date_subbed']))?></td>
                    <td>
                        <a href="subscriber.php?id=<?=$subscriber['id']?>" class="link1">Edit</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?=template_admin_footer()?>