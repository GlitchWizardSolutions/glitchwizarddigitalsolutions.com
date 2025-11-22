<?php
require 'assets/includes/admin_config.php';
include_once 'assets/includes/components.php';
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
<?=template_admin_header('Client Content Dashboard', 'allmedia', 'dash')?>

<?=generate_breadcrumbs([
    ['label' => 'Content Dashboard']
])?>

<div class="content-title">
    <div class="icon alt"><?=svg_icon_content()?></div>
    <div class="txt">
        <h2>Client Content Dashboard</h2>
        <p class="subtitle">Media uploads and approval management</p>
    </div>
</div>

<div class="dashboard">
    <div class="content-block stat">
        <div class="data">
            <h3>Uploaded Today</h3>
            <p><?=number_format(count($media))?></p>
        </div>
        <i class="fa-solid fa-photo-film"></i>
        <div class="footer">
            <i class="fa-solid fa-rotate fa-xs"></i>Total media for today
        </div>
    </div>
 
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
    </div> 

    <div class="content-block stat">
        <div class="data">
            <h3>Total Size</h3>
           
        </div>
        <i class="fas fa-file-alt"></i>
        <div class="footer">
            <i class="fa-solid fa-rotate fa-xs"></i>Total media file size
        </div>
    </div>
</div>

<div class="content-title">
    <div class="title">
        <div class="txt">
            <h2>Today's Media</h2>
            <p><?=number_format(count($media))?> new media uploads.</p>
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
<?=template_admin_footer()?>