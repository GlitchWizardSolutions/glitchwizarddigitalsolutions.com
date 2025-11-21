<?php
include 'main.php';
// Retrieve today's tickets
$stmt = $pdo->prepare('SELECT t.*, (SELECT COUNT(tc.id) FROM tickets_comments tc WHERE tc.ticket_id = t.id) AS num_comments, (SELECT GROUP_CONCAT(tu.filepath) FROM tickets_uploads tu WHERE tu.ticket_id = t.id) AS imgs, c.title AS category, a.full_name AS p_full_name, a.email AS a_email FROM tickets t LEFT JOIN categories c ON c.id = t.category_id LEFT JOIN accounts a ON t.account_id = a.id WHERE cast(t.created as DATE) = cast(now() as DATE) ORDER BY t.priority DESC, t.created DESC');
$stmt->execute();
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Get the total number of reviews
$stmt = $pdo->prepare('SELECT t.*, (SELECT COUNT(tc.id) FROM tickets_comments tc WHERE tc.ticket_id = t.id) AS num_comments, (SELECT GROUP_CONCAT(tu.filepath) FROM tickets_uploads tu WHERE tu.ticket_id = t.id) AS imgs, c.title AS category, a.full_name AS p_full_name, a.email AS a_email FROM tickets t LEFT JOIN categories c ON c.id = t.category_id LEFT JOIN accounts a ON t.account_id = a.id WHERE t.approved = 0');
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
?>
<?=template_admin_header('Dashboard', 'dashboard')?>

<div class="content-title">
    <div class="title">
        <i class="fa-solid fa-gauge-high"></i>
        <div class="txt">
            <h2>Dashboard</h2>
            <p>View statistics, new tickets, and more.</p>
        </div>
    </div>
</div>

<div class="dashboard">
    <div class="content-block stat">
        <div class="data">
            <h3>New Tickets</h3>
            <p><?=number_format(count($tickets))?></p>
        </div>
        <i class="fa-solid fa-ticket"></i>
        <div class="footer">
            <i class="fa-solid fa-rotate fa-xs"></i>Total tickets for today
        </div>
    </div>

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

    <div class="content-block stat">
        <div class="data">
            <h3>Total Tickets</h3>
            <p><?=number_format($tickets_total)?></p>
        </div>
        <i class="fa-solid fa-list"></i>
        <div class="footer">
            <i class="fa-solid fa-rotate fa-xs"></i>Total tickets
        </div>
    </div>
</div>

<div class="content-title">
    <div class="title">
        <i class="fa-solid fa-ticket alt"></i>
        <div class="txt">
            <h2>New Tickets</h2>
            <p>Tickets submitted in the last &lt;1 day.</p>
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

<?=template_admin_footer()?>