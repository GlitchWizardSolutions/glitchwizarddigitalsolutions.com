<?php
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
require 'assets/includes/admin_config.php';
include_once '../assets/includes/components.php';
// Check if the user is logged-in
check_loggedin($pdo, '../../index.php');
// Fetch account details associated with the logged-in user
$stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
$stmt->execute([ $_SESSION['id'] ]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);
// Check if the user is an admin...
if ($account['role'] != 'Admin') {
    exit('You do not have permission to access this page!');
}
// Connect to the login Database using the PDO interface
try {
	$logon_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name . ';charset=' . db_charset, db_user, db_pass);
	$logon_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	// If there is an error with the connection, stop the script and display the error.
	exit('Failed to connect to the logon database!');
}
$page = 'View';
// Retrieve records from the database
$records = $logon_db->query('SELECT * FROM accounts')->fetchAll(PDO::FETCH_ASSOC);
// Check whether the record ID is specified
if (isset($_GET['id'])) {
    // Retrieve the record from the database
    $stmt = $logon_db->prepare('SELECT * FROM accounts WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    exit('No ID specified!');
}
// Fetch $domains details associated with the logged-in user
$stmt = $pdo->prepare('SELECT * FROM domains WHERE account_id = ? ORDER BY domain');
$stmt->execute([ $_GET['id'] ]);
$domains = $stmt->fetchALL(PDO::FETCH_ASSOC);

// Fetch $clients details associated with the logged-in user
$stmt = $pdo->prepare('SELECT c.*, (SELECT COUNT(*) FROM invoices i WHERE i.client_id = c.id) AS total_invoices FROM invoice_clients c WHERE acc_id = ? ORDER BY total_invoices');
$stmt->execute([ $_GET['id'] ]);
$clients = $stmt->fetchALL(PDO::FETCH_ASSOC);

// Fetch $projects details associated with the logged-in user
$stmt = $pdo->prepare('SELECT * FROM client_projects WHERE acc_id = ? ORDER BY date_updated');
$stmt->execute([ $_GET['id'] ]);
$client_projects = $stmt->fetchALL(PDO::FETCH_ASSOC);

// Retrieve the ticket from the database
$stmt = $pdo->prepare('SELECT * FROM tickets WHERE acc_id = ? ORDER BY last_update');
$stmt->execute([$_GET['id'] ]);
$tickets = $stmt->fetchALL(PDO::FETCH_ASSOC);

// Fetch invoices for all businesses owned by this account
$stmt = $pdo->prepare('
    SELECT i.*, ic.business_name 
    FROM invoices i 
    JOIN invoice_clients ic ON i.client_id = ic.id 
    WHERE ic.acc_id = ? 
    ORDER BY i.due_date DESC
');
$stmt->execute([ $_GET['id'] ]);
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch recent project logs for all projects owned by this account
$stmt = $pdo->prepare('
    SELECT cpl.*, cp.subject, d.domain, pt.name as project_type
    FROM client_projects_logs cpl
    JOIN client_projects cp ON cpl.client_projects_id = cp.id
    LEFT JOIN domains d ON cp.domain_id = d.id
    LEFT JOIN project_types pt ON cp.project_type_id = pt.id
    WHERE cp.acc_id = ?
    ORDER BY cpl.date_created DESC
    LIMIT 20
');
$stmt->execute([ $_GET['id'] ]);
$project_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate quick stats
$total_businesses = count($clients);
$total_domains = count($domains);

$stmt = $pdo->prepare('SELECT COUNT(*) FROM client_projects WHERE acc_id = ? AND project_status = "active"');
$stmt->execute([$_GET['id']]);
$active_projects = $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT SUM(i.payment_amount) FROM invoices i JOIN invoice_clients ic ON i.client_id = ic.id WHERE ic.acc_id = ?');
$stmt->execute([$_GET['id']]);
$total_billed = $stmt->fetchColumn() ?? 0;

$stmt = $pdo->prepare('SELECT SUM(i.payment_amount - i.paid_total) FROM invoices i JOIN invoice_clients ic ON i.client_id = ic.id WHERE ic.acc_id = ? AND i.payment_status != "Paid"');
$stmt->execute([$_GET['id']]);
$outstanding_balance = $stmt->fetchColumn() ?? 0;

$stmt = $pdo->prepare('SELECT COUNT(*) FROM tickets WHERE acc_id = ? AND ticket_status != "Closed"');
$stmt->execute([$_GET['id']]);
$open_tickets = $stmt->fetchColumn();

// Activity Timeline - Combined feed of recent events
$activities = [];

// Get recent invoices (created and paid)
$stmt = $pdo->prepare('
    SELECT i.created as event_date, 
           CASE WHEN i.payment_status = "Paid" THEN "Invoice Paid" ELSE "Invoice Created" END as event_type,
           CONCAT("Invoice #", i.id, " - ", ic.business_name, " - $", i.payment_amount) as event_description,
           CASE WHEN i.payment_status = "Paid" THEN "check-circle" ELSE "file-invoice-dollar" END as icon,
           CASE WHEN i.payment_status = "Paid" THEN "#00c851" ELSE "#28a745" END as color
    FROM invoices i
    JOIN invoice_clients ic ON i.client_id = ic.id
    WHERE ic.acc_id = ?
    ORDER BY i.created DESC
    LIMIT 10
');
$stmt->execute([$_GET['id']]);
$activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));

// Get recent project log entries
$stmt = $pdo->prepare('
    SELECT cpl.date_created as event_date, "Project Update" as event_type,
           CONCAT(cp.subject, " - ", cpl.area, " (", cpl.status, ")") as event_description,
           "project-diagram" as icon, "#7F50AB" as color
    FROM client_projects_logs cpl
    JOIN client_projects cp ON cpl.client_projects_id = cp.id
    WHERE cp.acc_id = ?
    ORDER BY cpl.date_created DESC
    LIMIT 5
');
$stmt->execute([$_GET['id']]);
$activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));

// Get recent tickets
$stmt = $pdo->prepare('
    SELECT last_update as event_date, "Ticket Activity" as event_type,
           CONCAT("Ticket #", id, " - ", title, " (", ticket_status, ")") as event_description,
           "ticket-alt" as icon, "#ff8800" as color
    FROM tickets
    WHERE acc_id = ?
    ORDER BY last_update DESC
    LIMIT 3
');
$stmt->execute([$_GET['id']]);
$activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));

// Sort all activities by date
usort($activities, function($a, $b) {
    return strtotime($b['event_date']) - strtotime($a['event_date']);
});

// Limit to 15 most recent
$activities = array_slice($activities, 0, 15);

$copy="";
?>
<style>
/* Mobile Responsive Styles for Client View */
@media (max-width: 768px) {
    /* Stack Quick Stats cards vertically on mobile */
    .content-block div[style*="grid-template-columns"] {
        grid-template-columns: 1fr !important;
    }
    
    /* Make Quick Actions stack vertically */
    .content-block div[style*="display: flex"] a {
        min-width: 100% !important;
        margin-bottom: 8px;
    }
    
    /* Make tables scrollable horizontally */
    .table {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    table {
        min-width: 600px;
    }
    
    /* Adjust font sizes for better mobile readability */
    .content-title h2 {
        font-size: 1.5em !important;
    }
    
    .content-title p {
        font-size: 0.85em !important;
    }
    
    /* Make stat cards more compact */
    .content-block div[style*="text-align: center"] {
        padding: 10px !important;
    }
    
    /* Enlarge touch targets for dropdowns */
    .table-dropdown svg {
        width: 28px !important;
        height: 28px !important;
    }
    
    /* Better spacing for buttons */
    .btns {
        flex-wrap: wrap;
        gap: 8px;
    }
    
    .btns a {
        flex: 1 1 auto;
        min-width: 120px;
    }
    
    /* Activity Timeline adjustments */
    .content-block table td {
        padding: 8px 4px !important;
    }
}

@media (max-width: 480px) {
    /* Even smaller screens */
    table {
        font-size: 0.85em;
    }
    
    .content-block {
        padding: 10px !important;
    }
    
    /* Stack action buttons fully */
    .btns {
        flex-direction: column;
    }
    
    .btns a {
        width: 100%;
    }
}
</style>
    <?=template_admin_header('Accounts', 'accounts', 'view')?>

<?=generate_breadcrumbs([
    ['label' => 'Client Accounts', 'url' => 'accounts.php'],
    ['label' => htmlspecialchars($record['username'], ENT_QUOTES)]
])?>

<div class="content-title mb-3">
    <div class="title">
       <i class="fa-solid fa-user"></i>
        <div class="txt">
            <h2><?=htmlspecialchars($record['full_name'], ENT_QUOTES)?></h2>
            <p>Account ID: <?=$record['id']?> | Username: <?=htmlspecialchars($record['username'], ENT_QUOTES)?></p>
        </div>
    </div>
</div>

<?php if (isset($success_msg)): ?>
<div class="msg success">
    <i class="fas fa-check-circle"></i>
    <p><?=$success_msg?></p>
    <i class="fas fa-times"></i>
</div>
<?php endif; ?>

<div class="content-header responsive-flex-column pad-top-5">
    <div class="btns">
        <a href="accounts.php" class="btn btn-secondary">Back</a>
        <a href="account.php?id=<?=$record['id']?>" class="btn btn-primary">Edit</a>
        <a href="accounts.php?delete=<?=$record['id']?>" onclick="return confirm('Are you sure you want to delete this record?')" class="btn btn-danger">Delete</a>
    </div>
</div>
<div class="content-block"  style="background:#7F50AB">
    <div class="table" style="background:transparent !important"> 
        <table style="background:transparent !important">
            <thead>
                <tr style="background:transparent !important">
                    <td colspan=3 style='text-align:center; color:white !important; text-transform: uppercase; background:transparent !important'><strong><?=htmlspecialchars($record['full_name'], ENT_QUOTES)?></strong></td>
                </tr>
                <tr style='text-align:center; background:transparent !important'>
                <td style='color:white !important; background:transparent !important'></td>
                <td style='color:white !important; background:transparent !important'><?=htmlspecialchars($record['access_level'], ENT_QUOTES)?></td>
                <td style='color:white !important; background:transparent !important'>Active <?=time_elapsed_string($record['last_seen'])?></td>
                </tr>
                 
            </thead>
            <tbody>
       
               
            </tbody>
        </table>
    </div>
</div>

<!-- Quick Stats Dashboard -->
<div class="content-block" style="background:#7F50AB; padding: 20px;">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
        <div style="text-align: center; background: rgba(255,255,255,0.1); padding: 15px; border-radius: 8px;">
            <div style="font-size: 2em; font-weight: bold; color: white;"><?=$total_businesses?></div>
            <div style="color: #EDE3FF; font-size: 0.9em;">Businesses</div>
        </div>
        <div style="text-align: center; background: rgba(255,255,255,0.1); padding: 15px; border-radius: 8px;">
            <div style="font-size: 2em; font-weight: bold; color: white;"><?=$total_domains?></div>
            <div style="color: #EDE3FF; font-size: 0.9em;">Domains</div>
        </div>
        <div style="text-align: center; background: rgba(255,255,255,0.1); padding: 15px; border-radius: 8px;">
            <div style="font-size: 2em; font-weight: bold; color: white;"><?=$active_projects?></div>
            <div style="color: #EDE3FF; font-size: 0.9em;">Active Projects</div>
        </div>
        <div style="text-align: center; background: rgba(255,255,255,0.1); padding: 15px; border-radius: 8px;">
            <div style="font-size: 2em; font-weight: bold; color: white;">$<?=number_format($total_billed, 2)?></div>
            <div style="color: #EDE3FF; font-size: 0.9em;">Total Billed</div>
        </div>
        <div style="text-align: center; background: rgba(255,255,255,0.1); padding: 15px; border-radius: 8px;">
            <div style="font-size: 2em; font-weight: bold; color: <?=$outstanding_balance > 0 ? '#FFD700' : 'white'?>;">$<?=number_format($outstanding_balance, 2)?></div>
            <div style="color: #EDE3FF; font-size: 0.9em;">Outstanding</div>
        </div>
        <div style="text-align: center; background: rgba(255,255,255,0.1); padding: 15px; border-radius: 8px;">
            <div style="font-size: 2em; font-weight: bold; color: white;"><?=$open_tickets?></div>
            <div style="color: #EDE3FF; font-size: 0.9em;">Open Tickets</div>
        </div>
    </div>
</div>

<!-- Quick Actions Panel -->
<div class="content-block" style="background:#7F50AB; padding: 20px;">
    <h3 style="color: white; margin-top: 0; margin-bottom: 15px;">
        <i class="fas fa-bolt"></i> Quick Actions
    </h3>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="../invoice_system/invoice.php?acc_id=<?=$record['id']?>" 
           class="btn btn-success" 
           style="flex: 1; min-width: 150px; text-align: center; background: #28a745; color: white; padding: 12px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; border: 2px solid white;">
            <i class="fas fa-file-invoice-dollar"></i> Create Invoice
        </a>
        <a href="../resource_system/client-project.php?acc_id=<?=$record['id']?>" 
           class="btn btn-primary" 
           style="flex: 1; min-width: 150px; text-align: center; background: #007bff; color: white; padding: 12px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; border: 2px solid white;">
            <i class="fas fa-project-diagram"></i> New Project
        </a>
        <a href="domain.php?acc_id=<?=$record['id']?>" 
           class="btn btn-info" 
           style="flex: 1; min-width: 150px; text-align: center; background: #17a2b8; color: white; padding: 12px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; border: 2px solid white;">
            <i class="fas fa-globe"></i> Add Domain
        </a>
        <a href="mailto:<?=htmlspecialchars($record['email'], ENT_QUOTES)?>" 
           class="btn btn-warning" 
           style="flex: 1; min-width: 150px; text-align: center; background: #ffc107; color: #333; padding: 12px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; border: 2px solid white;">
            <i class="fas fa-envelope"></i> Send Email
        </a>
    </div>
</div>

<div class="content-block" style="background:#EDE3FF">
    <div class="table" style="background:#FFF">
        <table>
            <thead>
                <tr><strong>
                        <td colspan=3 style="text-align:left; color:#7F50AB">CONTACT</td>
                    </strong>
                </tr>
            </thead>
            <tbody>
               
                 <tr>
                    <td><?=htmlspecialchars($record['email'], ENT_QUOTES)?></td>
                    <td><?=htmlspecialchars($record['phone'], ENT_QUOTES)?></td>
                    <td><?=htmlspecialchars($record['address_street'], ENT_QUOTES)?><br>
                     <?=htmlspecialchars($record['address_city'], ENT_QUOTES)?>,&nbsp; 
                     <?=htmlspecialchars($record['address_state'], ENT_QUOTES)?> <br>
                     <?=htmlspecialchars($record['address_zip'], ENT_QUOTES)?>,&nbsp; 
                     <?=htmlspecialchars($record['address_country'], ENT_QUOTES)?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Alerts Section -->
<?php
// Check for domains expiring soon
$expiring_domains = [];
foreach ($domains as $domain) {
    $days_until_expiry = (strtotime($domain['due_date']) - time()) / 86400;
    if ($days_until_expiry < 30 && $days_until_expiry > 0 && $domain['status'] == 'Active') {
        $expiring_domains[] = [
            'domain' => $domain['domain'],
            'days' => round($days_until_expiry),
            'due_date' => $domain['due_date']
        ];
    }
}

// Check for incomplete business profiles
$incomplete_profiles = [];
foreach ($clients as $client) {
    if ($client['incomplete'] == 'Yes') {
        $incomplete_profiles[] = $client;
    }
}

// Display alerts if any exist
if (!empty($expiring_domains) || !empty($incomplete_profiles)):
?>
<div class="content-block" style="background:#FFF3CD; border-left: 4px solid #FFC107; padding: 15px;">
    <h3 style="margin-top:0; color:#856404;"><i class="fa fa-exclamation-triangle"></i> Alerts</h3>
    
    <?php foreach ($expiring_domains as $exp_domain): ?>
    <div style="padding: 10px; margin-bottom: 10px; background: white; border-left: 3px solid #FF6B6B; border-radius: 4px;">
        <i class="fa fa-clock" style="color:#FF6B6B;"></i>
        <strong>Domain Expiring Soon:</strong> 
        <strong><?=htmlspecialchars($exp_domain['domain'], ENT_QUOTES)?></strong> 
        expires in <strong><?=$exp_domain['days']?> days</strong> 
        (<?=date('m/d/Y', strtotime($exp_domain['due_date']))?>)
    </div>
    <?php endforeach; ?>
    
    <?php foreach ($incomplete_profiles as $incomplete): ?>
    <div style="padding: 10px; margin-bottom: 10px; background: white; border-left: 3px solid #4A90E2; border-radius: 4px;">
        <i class="fa fa-info-circle" style="color:#4A90E2;"></i>
        <strong>Incomplete Profile:</strong> 
        <strong><?=htmlspecialchars($incomplete['business_name'], ENT_QUOTES)?></strong> 
        needs completion. 
        <a href="../invoice_system/client.php?id=<?=$incomplete['id']?>" style="color:#4A90E2;">Complete Profile →</a>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

 <div class="content-block" style="background:#EDE3FF">
    <div class="table" style="background:#FFF">
        <table>
            <thead>
                <tr><strong>
                        <td style="text-align:left; color:#7F50AB">DOMAINS</td>
                    </strong>
                    <td>Status</td>
                    <td>Due</td>
                    <td style="text-align:center">Actions</td></strong>
                </tr>
            </thead>
            <tbody>
               <?php if (empty($domains)): ?>
                <tr>
                    <td colspan="20" style="text-align:center;">There are no domains found for this account.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($domains as $domain): ?>
                <tr>
                     <td><?=htmlspecialchars($domain['domain'], ENT_QUOTES)?></td>
                     <td><?=htmlspecialchars($domain['status'], ENT_QUOTES)?></td>
                     <td><?=date("m/d/y", strtotime($domain['due_date'])?? '')?></td>
                     <td class="actions" style="text-align: center;">
                        <div class="table-dropdown">
                            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M8 256a56 56 0 1 1 112 0A56 56 0 1 1 8 256zm160 0a56 56 0 1 1 112 0 56 56 0 1 1 -112 0zm216-56a56 56 0 1 1 0 112 56 56 0 1 1 0-112z"/></svg>
                            <div class="table-dropdown-items">
                                <a href="../resource_system/domain-use.php?id=<?=$domain['id']?>" style="color:green">
                                    <span class="icon">
                                        <i class="fa-regular fa-credit-card fa-xs"></i>
                                    </span>
                                    Use
                                </a>
                                 <a href="../resource_system/domain-view.php?id=<?=$domain['id']?>" style="color:blue">
                                    <span class="icon">
                                      <i class="fa-regular fa-eye fa-xs"></i>
                                    </span>
                                    View
                                </a>
                                <a href="../resource_system/domain.php?id=<?=$domain['id']?>" style='color:orange'>
                                    <span class="icon">
                                     <i class="fa-regular fa-pen-to-square fa-xs"></i>
                                    </span>
                                    Edit
                                </a>
                                <a class="red" href="../resource_system/domains.php?delete=<?=$domain['id']?>" onclick="return confirm('Are you sure you want to delete this account?')">
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
 <div class="content-block" style="background:#EDE3FF">
    <div class="table" style="background:#FFF">
        <table>
            <thead>
                <tr><strong>
                        <td style="text-align:left; color:#7F50AB">BUSINESSES</td>
                    </strong>
                    <td style="text-align:center" class="responsive-hidden">Email</td>
                    <td style="text-align:left">Invoices</td> 
                    <td style="text-align:center">Actions</td>
                </tr>
            </thead>
            <tbody>
               <?php if (!$clients): ?>
                <tr>
                    <td colspan="20" class="no-results">There are no businesses found for this account.</td>
                </tr>
                <?php endif; ?>
                <?php foreach ($clients as $client): ?>
                <tr>
                   <td><?=htmlspecialchars($client['business_name'], ENT_QUOTES)?></td>
                    <td class="responsive-hidden"><?=htmlspecialchars($client['email'], ENT_QUOTES)?></td>
                    <td class="mrauto" style='text-align:right'><a href="../invoice_system/invoices.php?client_id=<?=$client['id']?>" class="link1"><?=$client['total_invoices']?></a></td>
                     <td class="actions" style="text-align: center;">
                        <div class="table-dropdown">
                            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M8 256a56 56 0 1 1 112 0A56 56 0 1 1 8 256zm160 0a56 56 0 1 1 112 0 56 56 0 1 1 -112 0zm216-56a56 56 0 1 1 0 112 56 56 0 1 1 0-112z"/></svg>
                            <div class="table-dropdown-items">
                                <a href="../invoice_system/client.php?id=<?=$client['id']?>">
                                    <span class="icon">
                                        <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M471.6 21.7c-21.9-21.9-57.3-21.9-79.2 0L362.3 51.7l97.9 97.9 30.1-30.1c21.9-21.9 21.9-57.3 0-79.2L471.6 21.7zm-299.2 220c-6.1 6.1-10.8 13.6-13.5 21.9l-29.6 88.8c-2.9 8.6-.6 18.1 5.8 24.6s15.9 8.7 24.6 5.8l88.8-29.6c8.2-2.7 15.7-7.4 21.9-13.5L437.7 172.3 339.7 74.3 172.4 241.7zM96 64C43 64 0 107 0 160V416c0 53 43 96 96 96H352c53 0 96-43 96-96V320c0-17.7-14.3-32-32-32s-32 14.3-32 32v96c0 17.7-14.3 32-32 32H96c-17.7 0-32-14.3-32-32V160c0-17.7 14.3-32 32-32h96c17.7 0 32-14.3 32-32s-14.3-32-32-32H96z"/></svg>
                                    </span>
                                    Edit
                                </a>
                                <a class="red" href="../invoice_system/clients.php?delete=<?=$client['id']?>" onclick="return confirm('Are you sure you want to delete this client?')">
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
            </tbody>
        </table>
    </div>
</div>
 <div class="content-block" style="background:#EDE3FF">
    <div class="table" style="background:#FFF">
        <table>
            <thead>
                <tr><strong>
                    <td style="text-align:left; color:#7F50AB">PROJECTS</td>
                    </strong>
                    <td style="text-align:left">Domain</td>
                    <td style="text-align:center" class="responsive-hidden">Note</td>
                    <td style="text-align:center" class="responsive-hidden">Updated</td>
                    <td style="text-align:center">Actions</td>
                </tr>
            </thead>
            <tbody>
               <?php if (empty($client_projects)): ?>
                <tr>
                    <td colspan="20" style="text-align:center;">There are no projects found for this account.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($client_projects as $client_project): ?>
                <?php
                $stmt = $pdo->prepare('SELECT domain FROM domains WHERE id = ?');
                $stmt->execute([$client_project['domain_id'] ]);
                $domain_id = $stmt->fetch(PDO::FETCH_ASSOC); 
                
                $stmt = $pdo->prepare('SELECT name FROM project_types WHERE id = ?');
                $stmt->execute([$client_project['project_type_id'] ]);
                $project_type_id = $stmt->fetch(PDO::FETCH_ASSOC); 
                ?>
                
                
                <tr>
                    <td class="alt"><?=htmlspecialchars($project_type_id['name'], ENT_QUOTES)?></td>
                    <td class="alt"><?=htmlspecialchars($domain_id['domain'], ENT_QUOTES)?></td>
                    <td style="text-align:center" class="alt responsive-hidden"><?=$client_project['subject'] ?></td>
                    <td style="text-align:center" class="alt responsive-hidden"><?=time_elapsed_string($client_project['date_updated'])?></td>
                    
                     <td class="actions" style="text-align: center;">
                        <div class="table-dropdown">
                            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M8 256a56 56 0 1 1 112 0A56 56 0 1 1 8 256zm160 0a56 56 0 1 1 112 0 56 56 0 1 1 -112 0zm216-56a56 56 0 1 1 0 112 56 56 0 1 1 0-112z"/></svg>
                            <div class="table-dropdown-items">
                                <a href="../resource_system/client-project-use.php?id=<?=$client_project['id']?>" style="color:green">
                                    <span class="icon">
                                        <i class="fa-regular fa-credit-card fa-xs"></i>
                                    </span>
                                    Use
                                </a>
                                 <a href="../resource_system/client-project-view.php?id=<?=$client_project['id']?>" style="color:blue">
                                    <span class="icon">
                                      <i class="fa-regular fa-eye fa-xs"></i>
                                    </span>
                                    View
                                </a>
                                <a href="../resource_system/client-project.php?id=<?=$client_project['id']?>" style='color:orange'>
                                    <span class="icon">
                                     <i class="fa-regular fa-pen-to-square fa-xs"></i>
                                    </span>
                                    Edit
                                </a>
                                <a class="red" href="../resource_system/client-projects.php?delete=<?=$client_project['id']?>" onclick="return confirm('Are you sure you want to delete this account?')">
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

<!-- Invoices Section -->
<div class="content-block" style="background:#EDE3FF">
    <div class="table" style="background:#FFF">
        <table>
            <thead>
                <tr>
                    <td style="text-align:left; color:#7F50AB"><strong>INVOICES</strong></td>
                    <td>Business</td>
                    <td style="text-align:right">Amount</td>
                    <td style="text-align:center">Status</td>
                    <td style="text-align:center">Due Date</td>
                    <td style="text-align:center">Actions</td>
                </tr>
            </thead>
            <tbody>
               <?php if (empty($invoices)): ?>
                <tr>
                    <td colspan="6" style="text-align:center;">There are no invoices found for this account.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($invoices as $invoice): ?>
                <tr>
                    <td><?=htmlspecialchars($invoice['invoice_number'], ENT_QUOTES)?></td>
                    <td><?=htmlspecialchars($invoice['business_name'], ENT_QUOTES)?></td>
                    <td style="text-align:right">$<?=number_format($invoice['payment_amount'], 2)?></td>
                    <td style="text-align:center">
                        <?php
                        $status_colors = [
                            'Paid' => 'green',
                            'Unpaid' => 'orange',
                            'Pending' => 'blue',
                            'Cancelled' => 'red'
                        ];
                        $color = $status_colors[$invoice['payment_status']] ?? 'gray';
                        ?>
                        <span style="background:<?=$color?>; color:white; padding:3px 8px; border-radius:4px; font-size:0.85em;">
                            <?=htmlspecialchars($invoice['payment_status'], ENT_QUOTES)?>
                        </span>
                    </td>
                    <td style="text-align:center"><?=date('m/d/Y', strtotime($invoice['due_date']))?></td>
                    <td class="actions" style="text-align:center;">
                        <div class="table-dropdown">
                            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M8 256a56 56 0 1 1 112 0A56 56 0 1 1 8 256zm160 0a56 56 0 1 1 112 0 56 56 0 1 1 -112 0zm216-56a56 56 0 1 1 0 112 56 56 0 1 1 0-112z"/></svg>
                            <div class="table-dropdown-items">
                                <a href="../invoice_system/view_invoice.php?id=<?=$invoice['id']?>" style="color:blue">
                                    <span class="icon">
                                        <i class="fa-regular fa-eye fa-xs"></i>
                                    </span>
                                    View
                                </a>
                                <a href="../invoice_system/invoice.php?id=<?=$invoice['id']?>" style="color:orange">
                                    <span class="icon">
                                        <i class="fa-regular fa-pen-to-square fa-xs"></i>
                                    </span>
                                    Edit
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

<!-- Project Logs Section -->
<div class="content-block" style="background:#EDE3FF">
    <div class="table" style="background:#FFF">
        <table>
            <thead>
                <tr>
                    <td style="text-align:left; color:#7F50AB"><strong>RECENT PROJECT ACTIVITY</strong></td>
                    <td>Project</td>
                    <td>Domain</td>
                    <td style="text-align:center">Area</td>
                    <td style="text-align:center">Status</td>
                    <td style="text-align:center">Date</td>
                    <td style="text-align:center">Actions</td>
                </tr>
            </thead>
            <tbody>
               <?php if (empty($project_logs)): ?>
                <tr>
                    <td colspan="7" style="text-align:center;">There are no project logs found for this account.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($project_logs as $log): ?>
                <tr>
                    <td><?=htmlspecialchars($log['project_type'], ENT_QUOTES)?></td>
                    <td><?=htmlspecialchars($log['subject'], ENT_QUOTES)?></td>
                    <td><?=htmlspecialchars($log['domain'] ?? 'N/A', ENT_QUOTES)?></td>
                    <td style="text-align:center">
                        <span style="background:#7F50AB; color:white; padding:3px 8px; border-radius:4px; font-size:0.85em;">
                            <?=htmlspecialchars($log['area'], ENT_QUOTES)?>
                        </span>
                    </td>
                    <td style="text-align:center">
                        <?php
                        $log_status_colors = [
                            'Not Started' => 'gray',
                            'Working On' => 'blue',
                            'Waiting On' => 'orange',
                            'Completed' => 'green'
                        ];
                        $log_color = $log_status_colors[$log['status']] ?? 'gray';
                        ?>
                        <span style="background:<?=$log_color?>; color:white; padding:3px 8px; border-radius:4px; font-size:0.85em;">
                            <?=htmlspecialchars($log['status'], ENT_QUOTES)?>
                        </span>
                    </td>
                    <td style="text-align:center"><?=time_elapsed_string($log['date_created'])?></td>
                    <td class="actions" style="text-align:center;">
                        <div class="table-dropdown">
                            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M8 256a56 56 0 1 1 112 0A56 56 0 1 1 8 256zm160 0a56 56 0 1 1 112 0 56 56 0 1 1 -112 0zm216-56a56 56 0 1 1 0 112 56 56 0 1 1 0-112z"/></svg>
                            <div class="table-dropdown-items">
                                <a href="../resource_system/client-project-log-view.php?id=<?=$log['id']?>" style="color:blue">
                                    <span class="icon">
                                        <i class="fa-regular fa-eye fa-xs"></i>
                                    </span>
                                    View
                                </a>
                                <a href="../resource_system/client-project-log.php?id=<?=$log['id']?>" style="color:orange">
                                    <span class="icon">
                                        <i class="fa-regular fa-pen-to-square fa-xs"></i>
                                    </span>
                                    Edit
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

<!-- Activity Timeline -->
<div class="content-block" style="background:#EDE3FF">
    <div class="table" style="background:#FFF">
        <table>
            <thead>
                <tr>
                    <td colspan="3" style="text-align:left; color:#7F50AB"><strong>ACTIVITY TIMELINE</strong></td>
                </tr>
            </thead>
            <tbody>
               <?php if (empty($activities)): ?>
                <tr>
                    <td colspan="3" style="text-align:center;">No recent activity found for this account.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($activities as $activity): ?>
                <tr>
                    <td style="width: 40px; text-align: center;">
                        <i class="fas fa-<?=$activity['icon']?>" style="color:<?=$activity['color']?>; font-size: 1.2em;"></i>
                    </td>
                    <td>
                        <div style="font-weight: bold; color: #333; margin-bottom: 3px;">
                            <?=htmlspecialchars($activity['event_type'], ENT_QUOTES)?>
                        </div>
                        <div style="color: #666; font-size: 0.9em;">
                            <?=htmlspecialchars($activity['event_description'], ENT_QUOTES)?>
                        </div>
                    </td>
                    <td style="width: 150px; text-align: right; color: #999; font-size: 0.85em;">
                        <?=time_elapsed_string($activity['event_date'])?>
                    </td>
                 </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?=template_admin_footer()?>