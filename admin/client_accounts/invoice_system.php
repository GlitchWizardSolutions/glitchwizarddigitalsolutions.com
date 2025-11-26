<?php
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
require 'assets/includes/admin_config.php';
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
    
$copy="";
?>
    <?=template_admin_header('Accounts', 'accounts', 'view')?>

<?=generate_breadcrumbs([
    ['label' => 'Client Accounts', 'url' => 'accounts.php'],
    ['label' => htmlspecialchars($record['username'], ENT_QUOTES)]
])?>

<div class="content-title mb-3">
    <div class="title">
       <i class="fa-solid fa-file-invoice-dollar"></i>
        <div class="txt">
            <h2>Member <?=htmlspecialchars($record['username'], ENT_QUOTES)?></h2>
            <p><?=$page . ' Record # ' ?> <?=$record['id']?></p>
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
        <a href="accounts.php" class="btn btn-secondary">Return</a>
        <a href="account.php?id=<?=$record['id']?>" class="btn btn-primary">Edit</a>
        <a href="accounts.php?delete=<?=$record['id']?>" onclick="return confirm('Are you sure you want to delete this record?')" class="btn btn-danger">Delete</a>
    </div>
</div>
<div class="content-block"  style="background:#7F50AB">
    <div class="table"> 
        <table>
            <thead>
                <tr>
                    <td colspan=3 style='text-align:center; color:white; text-transform: uppercase'><strong><?=htmlspecialchars($record['full_name'], ENT_QUOTES)?></strong></td>
                </tr>
                <tr style='text-align:center'>
                <td style='color:white'></td>
                <td style='color:white'><?=htmlspecialchars($record['access_level'], ENT_QUOTES)?></td>
                <td style='color:white'>Active <?=time_elapsed_string($record['last_seen'])?></td>
                </tr>
                 
            </thead>
            <tbody>
       
               
            </tbody>
        </table>
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
<?=template_admin_footer()?>