<?php
require 'assets/includes/admin_config.php';
// New accounts created on the current date
$accounts = $pdo->query('SELECT * FROM accounts WHERE cast(registered as DATE) = cast(now() as DATE) ORDER BY registered DESC')->fetchAll(PDO::FETCH_ASSOC);
// Total accounts
$accounts_total = $pdo->query('SELECT COUNT(*) AS total FROM accounts')->fetchColumn();
// Total accounts that were last active over a month ago
$inactive_accounts = $pdo->query('SELECT COUNT(*) AS total FROM accounts WHERE last_seen < date_sub(now(), interval 1 month)')->fetchColumn();
// Accounts that are active in the last day
$active_accounts = $pdo->query('SELECT * FROM accounts WHERE last_seen > date_sub(now(), interval 1 day) ORDER BY last_seen DESC')->fetchAll(PDO::FETCH_ASSOC);
// Total accounts that are active in the last month
$active_accounts2 = $pdo->query('SELECT COUNT(*) AS total FROM accounts WHERE last_seen > date_sub(now(), interval 1 month)')->fetchColumn();
// Get the directory size
function dirSize($directory) {
    $size = 0;
    foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file){
        $size+=$file->getSize();
    }
    return $size;
}
// Retrieve all media uploaded on the current day
$stmt = $pdo->prepare('SELECT m.*, a.email FROM media m LEFT JOIN accounts a ON a.id = m.acc_id WHERE cast(m.uploaded_date as DATE) = cast(now() as DATE) ORDER BY m.uploaded_date DESC');
$stmt->execute();
$media = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Get the total number of media
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM media');
$stmt->execute();
$media_total = $stmt->fetchColumn();
// Media awaiting approval
$stmt = $pdo->prepare('SELECT m.*, a.email FROM media m LEFT JOIN accounts a ON a.id = m.acc_id WHERE m.approved = 0 ORDER BY m.uploaded_date DESC');
$stmt->execute();
$media_awaiting_approval = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Retrieve today's tickets
$stmt = $pdo->prepare('SELECT t.*, (SELECT COUNT(tc.id) FROM tickets_comments tc WHERE tc.ticket_id = t.id) AS num_comments, (SELECT GROUP_CONCAT(tu.filepath) FROM tickets_uploads tu WHERE tu.ticket_id = t.id) AS imgs, c.title AS category, a.full_name AS p_full_name, a.email AS a_email FROM tickets t LEFT JOIN categories c ON c.id = t.category_id LEFT JOIN accounts a ON t.acc_id = a.id WHERE cast(t.created as DATE) = cast(now() as DATE) ORDER BY t.priority DESC, t.created DESC');
$stmt->execute();
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Get the total number of reviews
$stmt = $pdo->prepare('SELECT t.*, (SELECT COUNT(tc.id) FROM tickets_comments tc WHERE tc.ticket_id = t.id) AS num_comments, (SELECT GROUP_CONCAT(tu.filepath) FROM tickets_uploads tu WHERE tu.ticket_id = t.id) AS imgs, c.title AS category, a.full_name AS p_full_name, a.email AS a_email FROM tickets t LEFT JOIN categories c ON c.id = t.category_id LEFT JOIN accounts a ON t.acc_id = a.id WHERE t.approved = 0');
$stmt->execute();
$awaiting_approval = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Retrieve all open tickets
$stmt = $pdo->prepare('SELECT t.*, (SELECT count(*) FROM tickets_comments tc WHERE t.id = tc.ticket_id) AS msgs, c.title AS category FROM tickets t LEFT JOIN categories c ON c.id = t.category_id WHERE t.ticket_status = "open" ORDER BY t.priority DESC, t.created DESC');
$stmt->execute();
$open_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Retrieve the total number of tickets
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM tickets');
$stmt->execute();
$tickets_total = $stmt->fetchColumn();
// Retrieve the total number of open tickets
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM tickets WHERE ticket_status = "open"');
$stmt->execute();
$open_tickets_total = $stmt->fetchColumn();
// Retrieve the total number of resolved tickets
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM tickets WHERE ticket_status = "resolved"');
$stmt->execute();
$resolved_tickets_total = $stmt->fetchColumn();
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
<?=template_admin_header('Dashboard', 'dashboard')?>
<!--commenting out some, trying to get a better dashboard overview of what's important only.  Can always see individual dashboards later -->
<div class="content-title mb-3">
    <div class="title">
        <i class="fa-solid fa-gauge-high"></i>
        <div class="txt">
            <h2>Dashboard</h2>
            <p>View your entire domain, and perform administrative tasks.</p>
        </div>
    </div>
</div>
<!--TICKETING SYSTEM 0VERVIEW-->
<div id="ticketing system" class="dashboard">
    <div class="content-block stat">
        <div class="data">
            <h3>New Tickets</h3>
            <p><?=number_format(count($tickets))?></p>
        </div>
        <i class="fa-solid fa-ticket"></i>
        <div class="footer">
            <i class="fa-solid fa-rotate fa-xs"></i>Total tickets  (&lt;1 day)
        </div>
    </div>
<!--
    <div class="content-block stat">
        <div class="data">
            <h3>Awaiting Approval</h3>
            <p><?=number_format(count($awaiting_approval))?></p>
        </div>
        <i class="fas fa-clock-rotate-left"></i>
        <div class="footer">
            <i class="fa-solid fa-rotate fa-xs"></i>Tickets awaiting approval
        </div>
    </div>
-->
    <div class="content-block stat">
        <div class="data">
            <h3>Open Tickets</h3>
            <p><?=number_format($open_tickets_total)?></p>
        </div>
        <i class="fa-solid fa-pen-to-square"></i>
        <div class="footer">
            <i class="fa-solid fa-rotate fa-xs"></i>Total open tickets
        </div>
    </div>
<!--
    <div class="content-block stat">
        <div class="data">
            <h3>Total Tickets</h3>
            <p><?=number_format($tickets_total)?></p>
        </div>
        <i class="fa-solid fa-list"></i>
        <div class="footer">
            <i class="fa-solid fa-rotate fa-xs"></i>Total tickets
        </div>
    </div>-->
</div>
</div>
<!--ACCOUNT SYSTEM 0VERVIEW-->
<div id="accounts" class="dashboard">
    <div class="content-block stat">
        <div class="data">
            <h3>New Accounts</h3>
            <p><?=number_format(count($accounts))?></p>
        </div>
        <i class="fas fa-user-plus"></i>
        <div class="footer">
            <i class="fa-solid fa-rotate fa-xs"></i>Total accounts (&lt;1 day)
        </div>
    </div>
<!--
    <div class="content-block stat">
        <div class="data">
            <h3>Total Accounts</h3>
            <p><?=number_format($accounts_total)?></p>
        </div>
        <i class="fas fa-users"></i>
        <div class="footer">
            <i class="fa-solid fa-rotate fa-xs"></i>Total accounts
        </div>
    </div>-->

    <div class="content-block stat">
        <div class="data">
            <h3>Active Accounts </h3>
            <p><?=number_format($active_accounts2)?></p>
        </div>
        <i class="fas fa-user-clock"></i>
        <div class="footer">
            <i class="fa-solid fa-rotate fa-xs"></i>Total accounts active (&lt;30 days)
        </div>
    </div>
<!--
    <div class="content-block stat">
        <div class="data">
            <h3>Inactive Accounts </h3>
            <p><?=number_format($inactive_accounts)?></p>
        </div>
        <i class="fas fa-user-clock"></i>
        <div class="footer">
            <i class="fa-solid fa-rotate fa-xs"></i>Total accounts inactive (&gt;30 days)
        </div>
    </div>-->
</div>
<div class="dashboard">
    <div class="content-block stat">
        <div class="data">
            <h3>Today's Media</h3>
            <p><?=number_format(count($media))?></p>
        </div>
        <i class="fa-solid fa-photo-film"></i>
        <div class="footer">
            <i class="fa-solid fa-rotate fa-xs"></i>Total media for today
        </div>
    </div>
<!--
    <div class="content-block stat">
        <div class="data">
            <h3>Awaiting Approval</h3>
            <p><?=number_format(count($media_awaiting_approval))?></p>
        </div>
        <i class="fas fa-clock"></i>
        <div class="footer">
            <i class="fa-solid fa-rotate fa-xs"></i>Media awaiting approval
        </div>
    </div>

    <div class="content-block stat">
        <div class="data">
            <h3>Total Media</h3>
            <p><?=number_format($media_total)?></p>
        </div>
        <i class="fa-solid fa-folder-open"></i>
        <div class="footer">
            <i class="fa-solid fa-rotate fa-xs"></i>Total media
        </div>
    </div>-->

    <div class="content-block stat">
        <div class="data">
            <h3>Total Size</h3>
            <p><?=convert_filesize(dirSize('../media'))?></p>
        </div>
        <i class="fas fa-file-alt"></i>
        <div class="footer">
            <i class="fa-solid fa-rotate fa-xs"></i>Total media file size
        </div>
    </div>
</div>

<div class="content-title mb-3">
    <div class="title">
        <i class="fa-solid fa-ticket alt"></i>
        <div class="txt">
            <h2>New Tickets</h2>
            <p>Tickets submitted in the last &lt;1 day.</p>
        </div>
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

<div class="content-title mb-3">
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

<div class="content-title mb-3">
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

<div class="content-block">
    <div class="table">
        <table>
            <thead>
                <tr>
                    <td>#</td>
                    <td colspan="2">User</td>
                    <td>Title</td>
                    <td>Status</td>
                    <td class="responsive-hidden">Has Comments</td>
                    <td class="responsive-hidden">Priority</td>
                    <td class="responsive-hidden">Category</td>
                    <td class="responsive-hidden">Private</td>
                    <td>Approved</td>
                    <td class="responsive-hidden">Date</td>
                    <td>Actions</td>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tickets)): ?>
                <tr>
                    <td colspan="20" style="text-align:center;">There are no recent tickets.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($tickets as $ticket): ?>
                <tr>
                    <td><?=$ticket['id']?></td>
                    <td class="img">
                        <span style="background-color:<?=color_from_string($ticket['p_full_name'] ?? $ticket['full_name'])?>"><?=strtoupper(substr($ticket['p_full_name'] ?? $ticket['full_name'], 0, 1))?></span>
                    </td>
                    <td class="user">
                        <?=htmlspecialchars($ticket['p_full_name'] ?? $ticket['full_name'], ENT_QUOTES)?>
                        <span><?=$ticket['p_email'] ?? $ticket['email']?></span>
                    </td>
                    <td><?=htmlspecialchars($ticket['title'], ENT_QUOTES)?></td>
                    <td><span class="<?=$ticket['ticket_status']=='resolved'?'green':($ticket['ticket_status']=='closed'?'red':'grey')?>"><?=ucwords($ticket['ticket_status'])?></span></td>
                    <td class="responsive-hidden"><?=$ticket['num_comments'] ? '<span class="mark yes"><i class="fa-solid fa-check"></i></span>' : '<span class="mark no"><i class="fa-solid fa-xmark"></i></span>'?></td>
                    <td class="responsive-hidden"><span class="<?=$ticket['priority']=='low'?'green':($ticket['priority']=='high'?'red':'orange')?>"><?=ucwords($ticket['priority'])?></span></td>
                    <td class="responsive-hidden"><span class="grey"><?=$ticket['category']?></span></td>
                    <td class="responsive-hidden"><?=$ticket['private'] ? '<span class="mark yes"><i class="fa-solid fa-check"></i></span>' : '<span class="mark no"><i class="fa-solid fa-xmark"></i></span>'?></td>
                    <td><?=$ticket['approved'] ? '<span class="mark yes"><i class="fa-solid fa-check"></i></span>' : '<span class="mark no"><i class="fa-solid fa-xmark"></i></span>'?></td>
                    <td class="responsive-hidden"><?=date('F j, Y H:ia', strtotime($ticket['created']))?></td>
                    <td>
                        <a href="../view.php?id=<?=$ticket['id']?>&code=<?=md5($ticket['id'] . $ticket['email'])?>" target="_blank" class="link1">View</a>
                        <a href="ticket.php?id=<?=$ticket['id']?>" class="link1">Edit</a>
                        <a href="tickets.php?delete=<?=$ticket['id']?>" class="link1" onclick="return confirm('Are you sure you want to delete this ticket?')">Delete</a>
                        <?php if ($ticket['approved'] != 1): ?>
                        <a href="tickets.php?approve=<?=$ticket['id']?>" class="link1" onclick="return confirm('Are you sure you want to approve this ticket?')">Approve</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="content-title" style="margin-top:40px">
    <div class="title">
        <i class="fa-solid fa-clock-rotate-left alt"></i>
        <div class="txt">
            <h2>Awaiting Approval</h2>
            <p>Tickets awaiting approval.</p>
        </div>
    </div>
</div>

<div class="content-block">
    <div class="table">
        <table>
            <thead>
                <tr>
                    <td>#</td>
                    <td colspan="2">User</td>
                    <td>Title</td>
                    <td>Status</td>
                    <td class="responsive-hidden">Has Comments</td>
                    <td class="responsive-hidden">Priority</td>
                    <td class="responsive-hidden">Category</td>
                    <td class="responsive-hidden">Private</td>
                    <td>Approved</td>
                    <td class="responsive-hidden">Date</td>
                    <td>Actions</td>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($awaiting_approval)): ?>
                <tr>
                    <td colspan="20" style="text-align:center;">There are no tickets awaiting approval.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($awaiting_approval as $tick): ?>
                <tr>
                    <td><?=$ticket['id']?></td>
                    <td class="img">
                        <span style="background-color:<?=color_from_string($ticket['p_full_name'] ?? $ticket['full_name'])?>"><?=strtoupper(substr($ticket['p_full_name'] ?? $ticket['full_name'], 0, 1))?></span>
                    </td>
                    <td class="user">
                        <?=htmlspecialchars($ticket['p_full_name'] ?? $ticket['full_name'], ENT_QUOTES)?>
                        <span><?=$ticket['p_email'] ?? $ticket['email']?></span>
                    </td>
                    <td><?=htmlspecialchars($ticket['title'], ENT_QUOTES)?></td>
                    <td><span class="<?=$ticket['ticket_status']=='resolved'?'green':($ticket['ticket_status']=='closed'?'red':'grey')?>"><?=ucwords($ticket['ticket_status'])?></span></td>
                    <td class="responsive-hidden"><?=$ticket['num_comments'] ? '<span class="mark yes"><i class="fa-solid fa-check"></i></span>' : '<span class="mark no"><i class="fa-solid fa-xmark"></i></span>'?></td>
                    <td class="responsive-hidden"><span class="<?=$ticket['priority']=='low'?'green':($ticket['priority']=='high'?'red':'orange')?>"><?=ucwords($ticket['priority'])?></span></td>
                    <td class="responsive-hidden"><span class="grey"><?=$ticket['category']?></span></td>
                    <td class="responsive-hidden"><?=$ticket['private'] ? '<span class="mark yes"><i class="fa-solid fa-check"></i></span>' : '<span class="mark no"><i class="fa-solid fa-xmark"></i></span>'?></td>
                    <td><?=$ticket['approved'] ? '<span class="mark yes"><i class="fa-solid fa-check"></i></span>' : '<span class="mark no"><i class="fa-solid fa-xmark"></i></span>'?></td>
                    <td class="responsive-hidden"><?=date('F j, Y H:ia', strtotime($ticket['created']))?></td>
                    <td>
                        <a href="../view.php?id=<?=$ticket['id']?>&code=<?=md5($ticket['id'] . $ticket['email'])?>" target="_blank" class="link1">View</a>
                        <a href="ticket.php?id=<?=$ticket['id']?>" class="link1">Edit</a>
                        <a href="tickets.php?delete=<?=$ticket['id']?>" class="link1" onclick="return confirm('Are you sure you want to delete this ticket?')">Delete</a>
                        <?php if ($ticket['approved'] != 1): ?>
                        <a href="tickets.php?approve=<?=$ticket['id']?>" class="link1" onclick="return confirm('Are you sure you want to approve this ticket?')">Approve</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="content-title mb-3">
    <div class="title">
        <i class="fa-solid fa-photo-film alt"></i>
        <div class="txt">
            <h2>Today's Media</h2>
            <p><?=number_format(count($media))?> new media uploads.</p>
        </div>
    </div>
</div>

<div class="content-title mb-3">
    <div class="title">
        <i class="fas fa-user-plus alt"></i>
        <div class="txt">
            <h2>New Accounts</h2>
            <p>Accounts created in the last &lt;1 day.</p>
        </div>
    </div>
</div>

<div class="content-block">
    <div class="table">
        <table>
            <thead>
                <tr>
                    <td>#</td>
                    <td colspan="2">User</td>
                    <td>Title</td>
                    <td>Status</td>
                    <td class="responsive-hidden">Has Comments</td>
                    <td class="responsive-hidden">Priority</td>
                    <td class="responsive-hidden">Category</td>
                    <td class="responsive-hidden">Private</td>
                    <td>Approved</td>
                    <td class="responsive-hidden">Date</td>
                    <td>Actions</td>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tickets)): ?>
                <tr>
                    <td colspan="20" style="text-align:center;">There are no recent tickets.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($tickets as $ticket): ?>
                <tr>
                    <td><?=$ticket['id']?></td>
                    <td class="img">
                        <span style="background-color:<?=color_from_string($ticket['p_full_name'] ?? $ticket['full_name'])?>"><?=strtoupper(substr($ticket['p_full_name'] ?? $ticket['full_name'], 0, 1))?></span>
                    </td>
                    <td class="user">
                        <?=htmlspecialchars($ticket['p_full_name'] ?? $ticket['full_name'], ENT_QUOTES)?>
                        <span><?=$ticket['p_email'] ?? $ticket['email']?></span>
                    </td>
                    <td><?=htmlspecialchars($ticket['title'], ENT_QUOTES)?></td>
                    <td><span class="<?=$ticket['ticket_status']=='resolved'?'green':($ticket['ticket_status']=='closed'?'red':'grey')?>"><?=ucwords($ticket['ticket_status'])?></span></td>
                    <td class="responsive-hidden"><?=$ticket['num_comments'] ? '<span class="mark yes"><i class="fa-solid fa-check"></i></span>' : '<span class="mark no"><i class="fa-solid fa-xmark"></i></span>'?></td>
                    <td class="responsive-hidden"><span class="<?=$ticket['priority']=='low'?'green':($ticket['priority']=='high'?'red':'orange')?>"><?=ucwords($ticket['priority'])?></span></td>
                    <td class="responsive-hidden"><span class="grey"><?=$ticket['category']?></span></td>
                    <td class="responsive-hidden"><?=$ticket['private'] ? '<span class="mark yes"><i class="fa-solid fa-check"></i></span>' : '<span class="mark no"><i class="fa-solid fa-xmark"></i></span>'?></td>
                    <td><?=$ticket['approved'] ? '<span class="mark yes"><i class="fa-solid fa-check"></i></span>' : '<span class="mark no"><i class="fa-solid fa-xmark"></i></span>'?></td>
                    <td class="responsive-hidden"><?=date('F j, Y H:ia', strtotime($ticket['created']))?></td>
                    <td>
                        <a href="../view.php?id=<?=$ticket['id']?>&code=<?=md5($ticket['id'] . $ticket['email'])?>" target="_blank" class="link1">View</a>
                        <a href="ticket.php?id=<?=$ticket['id']?>" class="link1">Edit</a>
                        <a href="tickets.php?delete=<?=$ticket['id']?>" class="link1" onclick="return confirm('Are you sure you want to delete this ticket?')">Delete</a>
                        <?php if ($ticket['approved'] != 1): ?>
                        <a href="tickets.php?approve=<?=$ticket['id']?>" class="link1" onclick="return confirm('Are you sure you want to approve this ticket?')">Approve</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="content-title" style="margin-top:40px">
    <div class="title">
        <i class="fa-solid fa-clock-rotate-left alt"></i>
        <div class="txt">
            <h2>Awaiting Approval</h2>
            <p>Tickets awaiting approval.</p>
        </div>
    </div>
</div>

<div class="content-block">
    <div class="table">
        <table>
            <thead>
                <tr>
                    <td>#</td>
                    <td colspan="2">User</td>
                    <td>Title</td>
                    <td>Status</td>
                    <td class="responsive-hidden">Has Comments</td>
                    <td class="responsive-hidden">Priority</td>
                    <td class="responsive-hidden">Category</td>
                    <td class="responsive-hidden">Private</td>
                    <td>Approved</td>
                    <td class="responsive-hidden">Date</td>
                    <td>Actions</td>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($awaiting_approval)): ?>
                <tr>
                    <td colspan="20" style="text-align:center;">There are no tickets awaiting approval.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($awaiting_approval as $tick): ?>
                <tr>
                    <td><?=$ticket['id']?></td>
                    <td class="img">
                        <span style="background-color:<?=color_from_string($ticket['p_full_name'] ?? $ticket['full_name'])?>"><?=strtoupper(substr($ticket['p_full_name'] ?? $ticket['full_name'], 0, 1))?></span>
                    </td>
                    <td class="user">
                        <?=htmlspecialchars($ticket['p_full_name'] ?? $ticket['full_name'], ENT_QUOTES)?>
                        <span><?=$ticket['p_email'] ?? $ticket['email']?></span>
                    </td>
                    <td><?=htmlspecialchars($ticket['title'], ENT_QUOTES)?></td>
                    <td><span class="<?=$ticket['ticket_status']=='resolved'?'green':($ticket['ticket_status']=='closed'?'red':'grey')?>"><?=ucwords($ticket['ticket_status'])?></span></td>
                    <td class="responsive-hidden"><?=$ticket['num_comments'] ? '<span class="mark yes"><i class="fa-solid fa-check"></i></span>' : '<span class="mark no"><i class="fa-solid fa-xmark"></i></span>'?></td>
                    <td class="responsive-hidden"><span class="<?=$ticket['priority']=='low'?'green':($ticket['priority']=='high'?'red':'orange')?>"><?=ucwords($ticket['priority'])?></span></td>
                    <td class="responsive-hidden"><span class="grey"><?=$ticket['category']?></span></td>
                    <td class="responsive-hidden"><?=$ticket['private'] ? '<span class="mark yes"><i class="fa-solid fa-check"></i></span>' : '<span class="mark no"><i class="fa-solid fa-xmark"></i></span>'?></td>
                    <td><?=$ticket['approved'] ? '<span class="mark yes"><i class="fa-solid fa-check"></i></span>' : '<span class="mark no"><i class="fa-solid fa-xmark"></i></span>'?></td>
                    <td class="responsive-hidden"><?=date('F j, Y H:ia', strtotime($ticket['created']))?></td>
                    <td>
                        <a href="../view.php?id=<?=$ticket['id']?>&code=<?=md5($ticket['id'] . $ticket['email'])?>" target="_blank" class="link1">View</a>
                        <a href="ticket.php?id=<?=$ticket['id']?>" class="link1">Edit</a>
                        <a href="tickets.php?delete=<?=$ticket['id']?>" class="link1" onclick="return confirm('Are you sure you want to delete this ticket?')">Delete</a>
                        <?php if ($ticket['approved'] != 1): ?>
                        <a href="tickets.php?approve=<?=$ticket['id']?>" class="link1" onclick="return confirm('Are you sure you want to approve this ticket?')">Approve</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="content-block">
    <div class="table">
        <table>
            <thead>
                <tr>
                    <td>Username</td>
                    <td class="responsive-hidden">Email</td>
                    <td class="responsive-hidden">Role</td>
                    <td class="responsive-hidden">Last Seen</td>
                    <td>Actions</td>
                </tr>
            </thead>
            <tbody>
                <?php if (!$accounts): ?>
                <tr>
                    <td colspan="8" style="text-align:center;">There are no newly registered accounts</td>
                </tr>
                <?php endif; ?>
                <?php foreach ($accounts as $account): ?>
                <tr>
                    <td><?=htmlspecialchars($account['username'], ENT_QUOTES)?></td>
                    <td class="responsive-hidden"><?=htmlspecialchars($account['email'], ENT_QUOTES)?></td>
                    <td class="responsive-hidden"><?=$account['role']?></td>
                    <td class="responsive-hidden" title="<?=$account['last_seen']?>"><?=time_elapsed_string($account['last_seen'])?></td>
                    <td>
                        <a href="account.php?id=<?=$account['id']?>" class="link1">Edit</a>
                        <a href="accounts.php?delete=<?=$account['id']?>" class="link1" onclick="return confirm('Are you sure you want to delete this account?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="content-block">
    <div class="table">
        <table>
            <thead>
                <tr>
                    <td>Media</td>
                    <td class="responsive-hidden">Description</td>
                    <td class="responsive-hidden">Account</td>
                    <td class="responsive-hidden">Type</td>
                    <td>Approved</td>
                    <td class="responsive-hidden">Date</td>
                    <td>Actions</td>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($media)): ?>
                <tr>
                    <td colspan="8" style="text-align:center;">There are no recent media files</td>
                </tr>
                <?php else: ?>
                <?php foreach ($media as $m): ?>
                <tr>
                    <td>
                        <div class="media">
                            <a href="../<?=$m['filepath']?>" class="media-img" target="_blank" title="<?=convert_filesize(filesize('../' . $m['filepath']))?>">
                            <?php if ($m['type'] == 'image'): ?>
                            <img src="../<?=$m['filepath']?>" alt="<?=htmlspecialchars($m['description'], ENT_QUOTES)?>" width="40" height="40">
                            <?php elseif ($m['type'] == 'video'): ?>

                            <?php if (empty($m['thumbnail'])): ?>
                            <span class="placeholder">
                                <i class="fas fa-film"></i>
                            </span>
                            <?php else: ?>
                            <img src="../<?=$m['thumbnail']?>" alt="<?=htmlspecialchars($m['description'], ENT_QUOTES)?>" width="40" height="40">
                            <?php endif; ?>

                            <?php elseif ($m['type'] == 'audio'): ?>

                            <?php if (empty($m['thumbnail'])): ?>
                            <span class="placeholder">
                                <i class="fas fa-music"></i>
                            </span>
                            <?php else: ?>
                            <img src="../<?=$m['thumbnail']?>" alt="<?=htmlspecialchars($m['description'], ENT_QUOTES)?>" width="40" height="40">
                            <?php endif; ?>

                            <?php endif; ?>
                            </a>
                            <a href="../<?=$m['filepath']?>" target="_blank" class="link1" title="<?=convert_filesize(filesize('../' . $m['filepath']))?>"><?=htmlspecialchars($m['title'], ENT_QUOTES)?></a>
                        </div>
                    </td>
                    <td class="responsive-hidden"><div class="truncate"><?=htmlspecialchars($m['description'], ENT_QUOTES)?></div></td>
                    <td class="responsive-hidden"><?=$m['acc_id'] ? '<a class="link1" href="account.php?id=' . htmlspecialchars($m['acc_id'], ENT_QUOTES) . '">' . htmlspecialchars($m['acc_id'], ENT_QUOTES) . ' - ' . htmlspecialchars($m['email'], ENT_QUOTES) . '</a></td>' : '--'; ?></td>
                    <td class="responsive-hidden"><?=ucfirst($m['type'])?></td>
                    <td style="font-weight:500;color:<?=$m['approved']?'green':'red'?>"><?=$m['approved']?'Yes':'No'?></td>
                    <td class="responsive-hidden"><?=date('F j, Y H:ia', strtotime($m['uploaded_date']))?></td>
                    <td>
                        <a href="media.php?id=<?=$m['id']?>" class="link1">Edit</a>
                        <a href="allmedia.php?delete=<?=$m['id']?>" class="link1" onclick="return confirm('Are you sure you want to delete this media?')">Delete</a>
                        <?php if (!$m['approved']): ?>
                        <a href="allmedia.php?approve=<?=$m['id']?>" class="link1" onclick="return confirm('Are you sure you want to approve this media?')">Approve</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="content-title" style="margin-top:40px">
    <div class="title">
        <i class="fa-solid fa-clock-rotate-left alt"></i>
        <div class="txt">
            <h2>Awaiting Approval</h2>
            <p><?=number_format(count($media_awaiting_approval))?> media awaiting approval.</p>
        </div>
    </div>
</div>

<div class="content-block">
    <div class="table">
        <table>
            <thead>
                <tr>
                    <td>Media</td>
                    <td class="responsive-hidden">Description</td>
                    <td class="responsive-hidden">Account</td>
                    <td class="responsive-hidden">Type</td>
                    <td>Approved</td>
                    <td class="responsive-hidden">Date</td>
                    <td>Actions</td>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($media_awaiting_approval)): ?>
                <tr>
                    <td colspan="8" style="text-align:center;">There are no media files awaiting approval</td>
                </tr>
                <?php else: ?>
                <?php foreach ($media_awaiting_approval as $m): ?>
                <tr>
                    <td>
                        <div class="media">
                            <a href="../<?=$m['filepath']?>" class="media-img" target="_blank" title="<?=convert_filesize(filesize('../' . $m['filepath']))?>">
                            <?php if ($m['type'] == 'image'): ?>
                            <img src="../<?=$m['filepath']?>" alt="<?=htmlspecialchars($m['description'], ENT_QUOTES)?>" width="40" height="40">
                            <?php elseif ($m['type'] == 'video'): ?>

                            <?php if (empty($m['thumbnail'])): ?>
                            <span class="placeholder">
                                <i class="fas fa-film"></i>
                            </span>
                            <?php else: ?>
                            <img src="../<?=$m['thumbnail']?>" alt="<?=htmlspecialchars($m['description'], ENT_QUOTES)?>" width="40" height="40">
                            <?php endif; ?>

                            <?php elseif ($m['type'] == 'audio'): ?>

                            <?php if (empty($m['thumbnail'])): ?>
                            <span class="placeholder">
                                <i class="fas fa-music"></i>
                            </span>
                            <?php else: ?>
                            <img src="../<?=$m['thumbnail']?>" alt="<?=htmlspecialchars($m['description'], ENT_QUOTES)?>" width="40" height="40">
                            <?php endif; ?>

                            <?php endif; ?>
                            </a>
                            <a href="../<?=$m['filepath']?>" target="_blank" class="link1" title="<?=convert_filesize(filesize('../' . $m['filepath']))?>"><?=htmlspecialchars($m['title'], ENT_QUOTES)?></a>
                        </div>
                    </td>
                    <td class="responsive-hidden"><div class="truncate"><?=htmlspecialchars($m['description'], ENT_QUOTES)?></div></td>
                    <td class="responsive-hidden"><?=$m['acc_id'] ? '<a class="link1" href="account.php?id=' . htmlspecialchars($m['acc_id'], ENT_QUOTES) . '">' . htmlspecialchars($m['acc_id'], ENT_QUOTES) . ' - ' . htmlspecialchars($m['email'], ENT_QUOTES) . '</a></td>' : '--'; ?></td>
                    <td class="responsive-hidden"><?=ucfirst($m['type'])?></td>
                    <td style="font-weight:500;color:<?=$m['approved']?'green':'red'?>"><?=$m['approved']?'Yes':'No'?></td>
                    <td class="responsive-hidden"><?=date('F j, Y H:ia', strtotime($m['uploaded_date']))?></td>
                    <td>
                        <a href="media.php?id=<?=$m['id']?>" class="link1">Edit</a>
                        <a href="allmedia.php?delete=<?=$m['id']?>" class="link1" onclick="return confirm('Are you sure you want to delete this media?')">Delete</a>
                        <?php if (!$m['approved']): ?>
                        <a href="allmedia.php?approve=<?=$m['id']?>" class="link1" onclick="return confirm('Are you sure you want to approve this media?')">Approve</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="content-title" style="margin-top:40px">
    <div class="title">
        <i class="fas fa-user-clock alt"></i>
        <div class="txt">
            <h2>Active Accounts</h2>
            <p>Accounts active in the last &lt;1 day.</p>
        </div>
    </div>
</div>

<div class="content-block">
    <div class="table">
        <table>
            <thead>
                <tr>
                    <td>Username</td>
                    <td class="responsive-hidden">Email</td>
                    <td class="responsive-hidden">Role</td>
                    <td class="responsive-hidden">Last Seen</td>
                    <td>Actions</td>
                </tr>
            </thead>
            <tbody>
                <?php if (!$active_accounts): ?>
                <tr>
                    <td colspan="8" style="text-align:center;">There are no active accounts</td>
                </tr>
                <?php endif; ?>
                <?php foreach ($active_accounts as $account): ?>
                <tr>
                    <td><?=htmlspecialchars($account['username'], ENT_QUOTES)?></td>
                    <td class="responsive-hidden"><?=htmlspecialchars($account['email'], ENT_QUOTES)?></td>
                    <td class="responsive-hidden"><?=$account['role']?></td>
                    <td class="responsive-hidden" title="<?=$account['last_seen']?>"><?=time_elapsed_string($account['last_seen'])?></td>
                    <td>
                        <a href="account.php?id=<?=$account['id']?>" class="link1">Edit</a>
                        <a href="accounts.php?delete=<?=$account['id']?>" class="link1" onclick="return confirm('Are you sure you want to delete this account?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?=template_admin_footer()?>