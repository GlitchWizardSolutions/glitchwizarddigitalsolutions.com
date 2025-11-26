<?php
//10-27-2024
require 'assets/includes/admin_config.php';
/*Dashboard Data*/
// Current date in MySQL DATETIME format
$date = date('Y-m-d H:i:s');

// SQL query that will get all invoices created today
$stmt = $pdo->prepare('SELECT i.*, c.first_name, c.last_name, c.email, (SELECT COUNT(*) FROM invoice_items ii WHERE ii.invoice_number = i.invoice_number) AS total_items FROM invoices i LEFT JOIN invoice_clients c ON c.id = i.client_id WHERE cast(i.created as DATE) = cast("' . $date . '" as DATE) ORDER BY i.created DESC');
$stmt->execute();
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get overdue invoices
$stmt = $pdo->prepare('SELECT i.*, c.first_name, c.last_name, c.email, (SELECT COUNT(*) FROM invoice_items ii WHERE ii.invoice_number = i.invoice_number) AS total_items FROM invoices i LEFT JOIN invoice_clients c ON c.id = i.client_id WHERE i.due_date < "' . $date . '" AND i.payment_status = "Unpaid" ORDER BY i.due_date ASC');
$stmt->execute();

$invoices_overdue = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Get earnings for last 7 days and 30 days
$stmt = $pdo->prepare('SELECT SUM(payment_amount+tax_total) AS earnings FROM invoices WHERE payment_status = "Paid" AND created >= DATE_SUB(cast("' . $date . '" as DATE), INTERVAL 30 DAY)');
$stmt->execute();
$earnings_30 = $stmt->fetchColumn();

// Get the total number of invoices
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM invoices');
$stmt->execute();
$invoices_total = $stmt->fetchColumn();

// Get the total number of invoice_clients
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM invoice_clients');
$stmt->execute();
$clients_total = $stmt->fetchColumn();

// New accounts created on the current date
//$accounts = $pdo->query('SELECT * FROM accounts WHERE cast(registered as DATE) = cast(now() as DATE) ORDER BY registered DESC')->fetchAll(PDO::FETCH_ASSOC);
$accounts = $pdo->query('SELECT * FROM accounts WHERE registered < date_sub(now(), interval 1 month) ORDER BY registered DESC')->fetchAll(PDO::FETCH_ASSOC);

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

//Open Tickets Data
$stmt = $pdo->prepare('SELECT t.*, (SELECT count(*) FROM tickets_comments tc WHERE t.id = tc.ticket_id) AS msgs, c.title AS category FROM tickets t LEFT JOIN categories c ON c.id = t.category_id WHERE t.ticket_status = "open" ORDER BY t.priority DESC, t.created DESC');
$stmt->execute();
$open_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

//Open Tickets Total Count
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM tickets WHERE ticket_status = "open"');
$stmt->execute();
$open_tickets_total = $stmt->fetchColumn();

//Resolved Tickets Total Count
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM tickets WHERE ticket_status = "resolved"');
$stmt->execute();
$resolved_tickets_total = $stmt->fetchColumn();

//Today's Tickets Data
$stmt = $pdo->prepare('SELECT t.*, (SELECT COUNT(tc.id) FROM tickets_comments tc WHERE tc.ticket_id = t.id) AS num_comments, (SELECT GROUP_CONCAT(tu.filepath) FROM tickets_uploads tu WHERE tu.ticket_id = t.id) AS imgs, c.title AS category, a.full_name AS p_full_name, a.email AS a_email FROM tickets t LEFT JOIN categories c ON c.id = t.category_id LEFT JOIN accounts a ON t.acc_id = a.id WHERE cast(t.created as DATE) = cast(now() as DATE) ORDER BY t.priority DESC, t.created DESC');
$stmt->execute();
$new_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Awaiting Response
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM tickets WHERE (ticket_status = "open" AND last_comment = "1")');
$stmt->execute();
$comment_awaiting_my_response = $stmt->fetchColumn();

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

/* ******************************************************************* 

    THERE ARE TWO TICKETING SYSTEMS.  THIS ONE IS FOR PROJECTS ONLY 
    
*********************************************************************/

//Open project_Tickets Data
$stmt = $pdo->prepare('SELECT t.*, (SELECT count(*) FROM project_tickets_comments tc WHERE t.id = tc.ticket_id) AS msgs, c.title AS category FROM tickets t LEFT JOIN categories c ON c.id = t.category_id WHERE t.ticket_status = "open" ORDER BY t.priority DESC, t.created DESC');
$stmt->execute();
$project_open_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

//Open project_Tickets Total Count
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM project_tickets WHERE ticket_status = "open"');
$stmt->execute();
$project_open_tickets_total = $stmt->fetchColumn();

//Resolved project_Tickets Total Count
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM project_tickets WHERE ticket_status = "resolved" AND cast(last_update as DATE) = cast(now() as DATE)');
$stmt->execute();
$project_resolved_tickets_total = $stmt->fetchColumn();

//Today's project_Tickets Data
$stmt = $pdo->prepare('SELECT t.*, (SELECT COUNT(tc.id) FROM project_tickets_comments tc WHERE tc.ticket_id = t.id) AS num_comments, (SELECT GROUP_CONCAT(tu.filepath) FROM project_tickets_uploads tu WHERE tu.ticket_id = t.id) AS imgs, c.title AS category, a.full_name AS p_full_name, a.email AS a_email FROM project_tickets t LEFT JOIN project_categories c ON c.id = t.category_id LEFT JOIN accounts a ON t.acc_id = a.id WHERE cast(t.created as DATE) = cast(now() as DATE) ORDER BY t.priority DESC, t.created DESC');
$stmt->execute();
$project_new_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Awaiting Response
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM project_tickets WHERE (ticket_status = "open" AND last_comment = "1")');
$stmt->execute();
$project_comment_awaiting_my_response = $stmt->fetchColumn();

// Retrieve today's project_tickets
$stmt = $pdo->prepare('SELECT t.*, (SELECT COUNT(tc.id) FROM project_tickets_comments tc WHERE tc.ticket_id = t.id) AS num_comments, (SELECT GROUP_CONCAT(tu.filepath) FROM project_tickets_uploads tu WHERE tu.ticket_id = t.id) AS imgs, c.title AS category, a.full_name AS p_full_name, a.email AS a_email FROM project_tickets t LEFT JOIN project_categories c ON c.id = t.category_id LEFT JOIN accounts a ON t.acc_id = a.id WHERE cast(t.created as DATE) = cast(now() as DATE) ORDER BY t.priority DESC, t.created DESC');
$stmt->execute();
$project_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get the total number of reviews
$stmt = $pdo->prepare('SELECT t.*, (SELECT COUNT(tc.id) FROM project_tickets_comments tc WHERE tc.ticket_id = t.id) AS num_comments, (SELECT GROUP_CONCAT(tu.filepath) FROM project_tickets_uploads tu WHERE tu.ticket_id = t.id) AS imgs, c.title AS category, a.full_name AS p_full_name, a.email AS a_email FROM project_tickets t LEFT JOIN project_categories c ON c.id = t.category_id LEFT JOIN accounts a ON t.acc_id = a.id WHERE t.approved = 0');
$stmt->execute();
$project_awaiting_approval = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Retrieve all open tickets
$stmt = $pdo->prepare('SELECT t.*, (SELECT count(*) FROM project_tickets_comments tc WHERE t.id = tc.ticket_id) AS msgs, c.title AS category FROM project_tickets t LEFT JOIN project_categories c ON c.id = t.category_id WHERE t.ticket_status = "open" ORDER BY t.priority DESC, t.created DESC');
$stmt->execute();
$project_open_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Retrieve the total number of tickets
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM project_tickets');
$stmt->execute();
$project_tickets_total = $stmt->fetchColumn();

// Retrieve the total number of open tickets
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM project_tickets WHERE ticket_status = "open"');
$stmt->execute();
$project_open_tickets_total = $stmt->fetchColumn();

// Retrieve the total number of resolved tickets
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM project_tickets WHERE ticket_status = "resolved"');
$stmt->execute();
$resolved_tickets_total = $stmt->fetchColumn();

/* SUBSCRIBERS */
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

// Include components for breadcrumbs
include_once 'assets/includes/components.php';

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
?>
<?=template_admin_header('Dashboard', 'dashboard')?>

<div class="content-title mb-3">
    <div class="icon alt"><?=svg_icon_dashboard()?></div>
    <div class="txt">
        <h2>Admin Dashboard</h2>
        <p class="subtitle">Overview of all systems and recent activity</p>
    </div>
</div>

<h2>Action Items</h2>
<div class="dashboard">

    <div class="content-block stat green">
        <div class="data"> 
            <a href="ticket_system/tickets.php?search=&order=DESC&order_by=created" style="text-decoration:none"><h3>Tickets Today</h3></a>
           <p><?=$new_tickets ? number_format(count($new_tickets)) : 0?></p>
        </div>
    </div>

   <div class="content-block stat red">
        <div class="data">
           <a href="ticket_system/tickets.php?search=&order=DESC&order_by=last_comment" style="text-decoration:none"><h3>New Responses</h3></a>
           <p><?=number_format($comment_awaiting_my_response)?></p>
        </div>
    </div>
    <div class="content-block stat cyan">
        <div class="data">
            <a href="ticket_system/tickets.php?page=tickets&status=open&search=" style="text-decoration:none"><h3>Open Tickets</h3></a>
            <p><?=number_format($open_tickets_total)?></p>
        </div>
    </div>
    
    <div class="content-block stat red">
        <div class="data">
            <h3>Overdue Invoices</h3>
            <p><?=number_format(count($invoices_overdue))?></p>
        </div>
    </div>
</div>
<div class="dashboard">

    <div class="content-block stat green">
        <div class="data"> 
            <a href="project_system/tickets.php?search=&order=DESC&order_by=created" style="text-decoration:none"><h3>Projects Today</h3></a>
           <p><?=$project_new_tickets ? number_format(count($project_new_tickets)) : 0?></p>
        </div>
    </div>

   <div class="content-block stat red">
        <div class="data">
           <a href="project_system/tickets.php?search=&order=DESC&order_by=last_comment" style="text-decoration:none"><h3>New Responses</h3></a>
           <p><?=number_format($project_comment_awaiting_my_response)?></p>
        </div>
    </div>
    <div class="content-block stat cyan">
        <div class="data">
            <a href="project_system/tickets.php?page=tickets&status=open&search=" style="text-decoration:none"><h3>Open Projects</h3></a>
            <p><?=number_format($project_open_tickets_total)?></p>
        </div>
    </div>
    
    <div class="content-block stat red">
        <div class="data">
            <h3>Resolved Today</h3>
            <p><?=number_format($project_resolved_tickets_total)?></p>
        </div>
    </div>
</div>
<h2>Budget Overview</h2>
<div class="dashboard">
    <div class="content-block stat red">
        <div class="data">
            <h3>Coming Soon</h3>
            <p><?=number_format(count($media))?></p>
        </div>
    </div>

    <div class="content-block stat blue">
        <div class="data">
            <h3>Coming Soon</h3>
            <p><?=number_format($media_total)?></p>
        </div>
    </div> 

      <div class="content-block stat green">
        <div class="data">
            <h3>Coming Soon</h3>
            <p><?=currency_code . ($earnings_30 ? number_format($earnings_30, 2) : '0.00')?></p>
        </div>
    </div>
    
    <div class="content-block stat">
        <div class="data">
            <h3>Coming Soon</h3>
            <p><?=convert_filesize(dirSize('../media'))?></p>
        </div>
        <i class="fas fa-file-alt"></i>
       </div>
    </div>
</div>

<h2>Pending Action Items</h2>
<div class="dashboard">
    <div class="content-block stat red">
        <div class="data">
            <h3>Uploaded Today</h3>
            <p><?=number_format(count($media))?></p>
        </div>
    </div>

    <div class="content-block stat blue">
        <div class="data">
            <h3>Total Media</h3>
            <p><?=number_format($media_total)?></p>
        </div>
    </div> 

      <div class="content-block stat green">
        <div class="data">
            <h3>Earnings <span>30 days</span></h3>
            <p><?=currency_code . ($earnings_30 ? number_format($earnings_30, 2) : '0.00')?></p>
        </div>
    </div>
    
    <div class="content-block stat">
        <div class="data">
            <h3>Total Size</h3>
            <p><?=convert_filesize(dirSize('../media'))?></p>
        </div>
        <i class="fas fa-file-alt"></i>
       </div>
    </div>
</div>
     <h2>General Information</h2>
    <div id="accounts" class="dashboard">
           <div class="content-block stat green">
        <div class="data">
            <h3>Earnings <span>30 days</span></h3>
            <p><?=currency_code . ($earnings_30 ? number_format($earnings_30, 2) : '0.00')?></p>
        </div>
    </div>
 
    <div class="content-block stat blue">
        <div class="data">
            <h3>Today's Invoices</h3>
            <p><?=$invoices ? number_format(count($invoices)) : 0?></p>
        </div>
       </div>
       
    <div class="content-block stat">
        <div class="data">
            <h3>Resolved Tickets</h3>
            <p><?=number_format($resolved_tickets_total)?></p>
        </div>
    </div>
</div>

 <div id="accounts" class="dashboard">
    <div class="content-block stat green">
        <div class="data">
            <h3>New Accounts <span>&lt;30 days</span></h3>
            <p><?=number_format(count($accounts))?></p>
        </div>
      </div>
      
    <div class="content-block stat blue">
        <div class="data">
            <h3>Active Accounts <span> &lt;30 days</span></h3>
            <p><?=number_format($active_accounts2)?></p>
        </div>
    </div>
    
    <div class="content-block stat red">
        <div class="data">
            <h3>Inactive Accounts<span>&gt;30 days</span> </h3>
            <p><?=number_format($inactive_accounts)?></p>
        </div>
    </div>
    
    <div class="content-block stat cyan">
        <div class="data">
            <h3>Total Accounts</h3>
            <p><?=number_format($accounts_total)?></p>
        </div>
        </div>
    </div>
    
     <div id="accounts" class="dashboard">
  <div class="content-block stat red">
        <div class="data">
            <h3>Total Clients</h3>
            <p><?=$clients_total ? number_format($clients_total) : 0?></p>
        </div>
    </div>
</div>
<?=template_admin_footer()?>