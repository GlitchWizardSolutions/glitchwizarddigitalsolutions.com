<?php
require 'assets/includes/admin_config.php';
// Get the directory size
function dirSize($directory) {
    $size = 0;
    foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file){
        $size+=$file->getSize();
    }
    return $size;
}
// Retrieve today's Requirements
$stmt = $pdo->prepare('SELECT t.*, (SELECT COUNT(tc.id) FROM gws_legal_comments tc WHERE tc.ticket_id = t.id) AS num_comments, (SELECT GROUP_CONCAT(tu.filepath) FROM gws_legal_uploads tu WHERE tu.ticket_id = t.id) AS imgs, c.title AS category, a.full_name AS p_full_name, a.email AS a_email FROM gws_legal t LEFT JOIN gws_legal_categories c ON c.id = t.category_id LEFT JOIN accounts a ON t.acc_id = a.id WHERE cast(t.created as DATE) = cast(now() as DATE) ORDER BY t.priority DESC, t.created DESC');
$stmt->execute();
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Get the total number of reviews
$stmt = $pdo->prepare('SELECT t.*, (SELECT COUNT(tc.id) FROM gws_legal_comments tc WHERE tc.ticket_id = t.id) AS num_comments, (SELECT GROUP_CONCAT(tu.filepath) FROM gws_legal_uploads tu WHERE tu.ticket_id = t.id) AS imgs, c.title AS category, a.full_name AS p_full_name, a.email AS a_email FROM gws_legal t LEFT JOIN gws_legal_categories c ON c.id = t.category_id LEFT JOIN accounts a ON t.acc_id = a.id WHERE t.approved = 0');
$stmt->execute();
$awaiting_approval = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Retrieve all open Requirements
$stmt = $pdo->prepare('SELECT t.*, (SELECT count(*) FROM gws_legal_comments tc WHERE t.id = tc.ticket_id) AS msgs, c.title AS category FROM gws_legal t LEFT JOIN gws_legal_categories c ON c.id = t.category_id WHERE t.ticket_status = "open" ORDER BY t.priority DESC, t.created DESC');
$stmt->execute();
$open_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Retrieve the total number of Requirements
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM gws_legal');
$stmt->execute();
$tickets_total = $stmt->fetchColumn();
// Retrieve the total number of open Requirements
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM gws_legal WHERE ticket_status = "open"');
$stmt->execute();
$open_tickets_total = $stmt->fetchColumn();
// Retrieve the total number of resolved Requirements
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM gws_legal WHERE ticket_status = "resolved"');
$stmt->execute();
$resolved_tickets_total = $stmt->fetchColumn();
?>
<?=template_admin_header('Manage Legal Filings', 'ticketing', 'legal')?>

<div class="content-title">
    <h2>GWS Legal Requirements System</h2>
</div>

<div id="ticketing system" class="dashboard">
    <div class="content-block stat">
        <div class="data">
            <h3>New Legal Requirements</h3>
            <p><?=number_format(count($tickets))?></p>
        </div>
        <i class="fa-solid fa-ticket"></i>
        <div class="footer">
            <i class="fa-solid fa-rotate fa-xs"></i>Total requirements  (&lt;1 day)
        </div>
    </div>
    <div class="content-block stat">
        <div class="data">
            <h3>Awaiting Approval</h3>
            <p><?=number_format(count($awaiting_approval))?></p>
        </div>
        <i class="fas fa-clock-rotate-left"></i>
        <div class="footer">
            <i class="fa-solid fa-rotate fa-xs"></i>Requirements awaiting approval
        </div>
    </div>
    <div class="content-block stat">
        <div class="data">
            <h3>Open Requirements</h3>
            <p><?=number_format($open_tickets_total)?></p>
        </div>
        <i class="fa-solid fa-pen-to-square"></i>
        <div class="footer">
            <i class="fa-solid fa-rotate fa-xs"></i>Total open Requirements
        </div>
    </div>
    <div class="content-block stat">
        <div class="data">
            <h3>Total Requirements</h3>
            <p><?=number_format($tickets_total)?></p>
        </div>
        <i class="fa-solid fa-list"></i>
        <div class="footer">
            <i class="fa-solid fa-rotate fa-xs"></i>Total Requirements
        </div>
    </div>
</div>
</div>

<div class="content-block">
    <div class="table">
        <table>
            <thead>
                <tr>
                    <td>#</td>
                    <td>User</td>
                    <td>Title</td>
                    <td>Status</td>
                    <td class="responsive-hidden">Comments</td>
                    <td class="responsive-hidden">Priority</td>
                    <td class="responsive-hidden">Category</td>
                    <td class="responsive-hidden">Date</td>
                    <td>Actions</td>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tickets)): ?>
                <tr>
                    <td colspan="20" style="text-align:center;">There are no recent Requirements.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($tickets as $ticket): ?>
                <tr>
                    <td><?=$ticket['id']?></td>
                    <td class="img">
                        <span style="background-color:<?=color_from_string($ticket['p_full_name'] ?? $ticket['full_name'])?>"><?=strtoupper(substr($ticket['p_full_name'] ?? $ticket['full_name'], 0, 1))?></span>
                    </td>
                    <td class="responsive-hidden user"> 
                        <?=htmlspecialchars($ticket['p_full_name'] ?? $ticket['full_name'], ENT_QUOTES)?>
                        <span><?=$ticket['p_email'] ?? $ticket['email']?></span>
                    </td>
                    <td><?=htmlspecialchars($ticket['title'], ENT_QUOTES)?></td>
                    <td><span class="<?=$ticket['ticket_status']=='resolved'?'green':($ticket['ticket_status']=='closed'?'red':'grey')?>"><?=ucwords($ticket['ticket_status'])?></span></td>
                    <td class="responsive-hidden"><?=$ticket['num_comments'] ? '<span class="mark yes"><i class="fa-solid fa-check"></i></span>' : '<span class="mark no"><i class="fa-solid fa-xmark"></i></span>'?></td>
                    <td class="responsive-hidden"><span class="<?=$ticket['priority']=='low'?'green':($ticket['priority']=='high'?'red':'orange')?>"><?=ucwords($ticket['priority'])?></span></td>
                    <td class="responsive-hidden"><span class="grey"><?=$ticket['category']?></span></td>
                 
                    <td class="responsive-hidden"><?=date('m/d/y', strtotime($ticket['created']))?></td>
                    <td>
                        <a href="../view.php?id=<?=$ticket['id']?>&code=<?=md5($ticket['id'] . $ticket['email'])?>" target="_blank" class="link1">View</a>
                        <a href="ticket.php?id=<?=$ticket['id']?>" class="link1">Edit</a>
                        <a href="tickets.php?delete=<?=$ticket['id']?>" class="link1" onclick="return confirm('Are you sure you want to delete this ticket?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?=template_admin_footer()?>