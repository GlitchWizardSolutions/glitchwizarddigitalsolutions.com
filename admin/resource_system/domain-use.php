<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'assets/includes/admin_config.php';
try {
	$login_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name . ';charset=' . db_charset, db_user, db_pass); 
	$login_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	// If there is an error with the connection, stop the script and display the error.
	exit('Failed to connect to the login system database: ' . $exception->getMessage());
}
$page = 'View';

// Check whether the record ID is specified
if (isset($_GET['id'])) {
    // Retrieve the records from the database
    try {
        $stmt = $login_db->prepare('SELECT d.*, i.business_name, a.full_name FROM domains d LEFT JOIN invoice_clients i ON d.invoice_client_id = i.id LEFT JOIN accounts a ON d.account_id = a.id WHERE d.id = ?');
        $stmt->execute([ $_GET['id'] ]);
        $records = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if record exists
        if (!$records) {
            exit('Domain not found! ID: ' . htmlspecialchars($_GET['id']));
        }
        
        $stmt = $login_db->prepare('SELECT * FROM client_projects WHERE domain_id = ?');
        $stmt->execute([ $_GET['id'] ]);
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        exit('Database error: ' . $e->getMessage());
    }
    
} else {
    exit('No ID specified!');
}
$copy="";
?>
    <?=template_admin_header($page . 'Domains', 'resources', 'domains')?>
    
<div class="content-title mb-3">
    <div class="title">
       <i class="fa-solid fa-circle-info"></i>
        <div class="txt">
            <h2><?=htmlspecialchars($records['domain'], ENT_QUOTES)?></h2>
            <p>Use this information to renew the domain's registration.</p>
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
        <a href="domains.php" class="btn btn-secondary">Return</a>
        <a href="domain.php?id=<?=$records['id']?>" class="btn btn-primary">Edit</a>
        <a href="domains.php?delete=<?=$records['id']?>" onclick="return confirm('Are you sure you want to delete this record?')" class="btn btn-danger">Delete</a>
    </div>
</div>

<div class="content-block">
    <div class="table"style="width:100%">
        <table>
              <td class="title">Domain Name: </td><td><?=htmlspecialchars($records['domain'] ?? '', ENT_QUOTES)?></td>
                </tr>
                 <tr>
                <td class="title">Status: </td><td><?=htmlspecialchars($records['status'] ?? '', ENT_QUOTES)?></td>
                </tr>
                </tr>
                <tr>
                <td class="title">Business: </td><td><?=htmlspecialchars($records['business_name'] ?? '', ENT_QUOTES)?></td>
                </tr>
                <tr>
                <tr>
               <td class="title">Member Name</td> <td><?=htmlspecialchars($records['full_name'] ?? '', ENT_QUOTES)?></td>
                </tr>
                <tr>
               <td class="title">Date Due:</td> <td><?=!empty($records['due_date']) ? date("m/d/y", strtotime($records['due_date'])) : ''?></td>
                </tr>
                <tr>
               <td class="title">Amount Due:</td> <td>$<?=htmlspecialchars($records['amount'] ?? '', ENT_QUOTES)?></td>
                </tr>
              <tr>
               <td class="title">Renewal Host: </td><td><?=htmlspecialchars($records['host_url'] ?? '', ENT_QUOTES)?></td>
                    </tr>
                <tr>
               <td class="title">Host Login: </td><td><?=htmlspecialchars($records['host_login'] ?? '', ENT_QUOTES)?></td>
                </tr>
                <tr>
               <td class="title">Host Password:</td> <td><?=htmlspecialchars($records['host_password'] ?? '', ENT_QUOTES)?></td>
                </tr>
                <tr>
               <td class="title">Instructions:</td> <td><?=htmlspecialchars($records['notes'] ?? '', ENT_QUOTES)?></td>
                </tr>
            <tbody>
                <tr>

                </tr>
            <tbody>
                <tr>

                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="content-block">
    <div class="table">
        <table>
            <thead> 
                <tr>
                    <td>Project Type</td>
                    <td>Project</td>
                    <td>Last Update</td>
                     <td>Status</td>
                    <td style="text-align: center;">Actions</td>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($projects)): ?>
                <tr>
                    <td colspan="20" style="text-align:center;">There are no projects.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($projects as $project): ?>
                <?php      $stmt = $login_db ->prepare('SELECT * FROM project_types WHERE id = ?');
                           $stmt->execute([ $project['project_type_id'] ]);
                           $type = $stmt->fetch(PDO::FETCH_ASSOC); ?>
                <tr>
                    <td><?=htmlspecialchars($type['name'], ENT_QUOTES)?></td>
                    <td><?=htmlspecialchars($project['subject'], ENT_QUOTES)?></td>
                    <td><?=time_elapsed_string($project['date_updated'])?>
                    <td><?=htmlspecialchars($project['project_status'], ENT_QUOTES)?></td>
            
                    <td class="actions" style="text-align: center;">
                        <div class="table-dropdown">
                            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M8 256a56 56 0 1 1 112 0A56 56 0 1 1 8 256zm160 0a56 56 0 1 1 112 0 56 56 0 1 1 -112 0zm216-56a56 56 0 1 1 0 112 56 56 0 1 1 0-112z"/></svg>
                            <div class="table-dropdown-items">
                                <a href="client-project-use.php?id=<?=$project['id']?>" style="color:green">
                                    <span class="icon">
                                        <i class="fa-regular fa-credit-card fa-xs"></i>
                                    </span>
                                    Use
                                </a>
                                 <a href="client-project-view.php?id=<?=$project['id']?>" style="color:blue">
                                    <span class="icon">
                                      <i class="fa-regular fa-eye fa-xs"></i>
                                    </span>
                                    View
                                </a>
                                <a href="client_project.php?id=<?=$project['id']?>" style='color:orange'>
                                    <span class="icon">
                                     <i class="fa-regular fa-pen-to-square fa-xs"></i>
                                    </span>
                                    Edit
                                </a>
                                <a class="red" href="client-projects.php?delete=<?=$project['id']?>" onclick="return confirm('Are you sure you want to delete this account?')">
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
<style>
.title  {
font-weight:bold;
width:30%; 
}
</style>
<script src="assets/js/resource-system-script.js"></script>
<?=template_admin_footer()?>