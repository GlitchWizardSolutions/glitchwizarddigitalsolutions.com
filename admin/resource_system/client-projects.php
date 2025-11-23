<?php
require 'assets/includes/admin_config.php';
include_once '../assets/includes/components.php';
$filename = '?';
$application= 'Resource System - Client Projects';
$inputs     = '';
$outputs    = '';
$noted      = 'public_html/admin/resource_system/client-projects.php';
// Connect to the login Database using the PDO interface
try {
	$login_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name . ';charset=' . db_charset, db_user, db_pass);
	$login_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	// If there is an error with the connection, stop the script and display the error.
	exit('! Failed to connect to the on the go database!');
}

// LEGACY CODE - Error database connection unused (db_name9/db_user9 constants not defined)
// The error_db variable is not used anywhere in this file
/*
// Connect to the Error Database using the PDO interface
try {
	$error_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name9 . ';charset=' . db_charset, db_user9, db_pass);
	$error_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	// If there is an error with the connection, stop the script and display the error.
	exit('! Failed to connect to the on the error handling database!');
}
 
//$stmt =$error_db->prepare('SELECT * FROM error_handling ORDER BY "id" LIMIT 1');
*/
//$stmt->execute();
//$errors = $stmt->fetch(PDO::FETCH_ASSOC);

// Delete record
if (isset($_GET['delete'])) {
    $stmt = $login_db->prepare('DELETE FROM client_projects WHERE id = ?');
    $stmt->execute([ $_GET['delete'] ]);
    $stmt = $login_db->prepare('DELETE FROM client_projects_logs WHERE client_projects_id = ?');
    $stmt->execute([ $_GET['delete'] ]);
    header('Location: client-projects.php?success_msg=3');
    exit;
}
// Retrieve the GET request parameters (if specified)
$pagination_page = isset($_GET['pagination_page']) ? $_GET['pagination_page'] : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';
// Filters
$status = isset($_GET['status']) ? $_GET['status'] : '';
// Order by column
$order = isset($_GET['order']) && $_GET['order'] == 'DESC' ? 'DESC' : 'ASC';
// Add/remove columns to the whitelist array
$order_by_whitelist = [ 'subject','client_quote','dev_comment'];
$order_by = isset($_GET['order_by']) && in_array($_GET['order_by'], $order_by_whitelist) ? $_GET['order_by'] : 'domain_id';
// Number of results per pagination page
$results_per_page = 15;
// Declare query param variables
$param1 = ($pagination_page - 1) * $results_per_page;
$param2 = $results_per_page;
$param3 = '%' . $search . '%';
// SQL where clause
$where = '';
$where .= $search ? 'WHERE (subject LIKE :search OR client_quote LIKE :search OR dev_comment LIKE :search) ' : '';
// Retrieve the total number of records from the database
$stmt = $login_db->prepare('SELECT COUNT(*) AS total FROM client_projects ' . $where);
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
$stmt->execute();
$records_total = $stmt->fetchColumn();
// SQL query to get all records from the "client-project" table
$stmt = $login_db->prepare('SELECT * FROM client_projects  ' . $where . ' ORDER BY ' . $order_by . ' ' . $order . ' LIMIT :start_results,:num_results');
// Bind params
$stmt->bindParam('start_results', $param1, PDO::PARAM_INT);
$stmt->bindParam('num_results', $param2, PDO::PARAM_INT);
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
$stmt->execute();
// Retrieve query results
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Handle success messages
if (isset($_GET['success_msg'])) {
    if ($_GET['success_msg'] == 1) {
        $success_msg = 'Record created successfully!';
    }
    if ($_GET['success_msg'] == 2) {
        $success_msg = 'Record updated successfully!';
    }
    if ($_GET['success_msg'] == 3) {
        $success_msg = 'Records deleted successfully from projects & logs!';
    }
    if ($_GET['success_msg'] == 4) {
        $success_msg = $_GET['imported'] . ' Record(s) imported successfully!';
    }
}
// Determine the URL
$url = 'client-projects.php?search=' . $search . (isset($_GET['page_id']) ? '&page_id=' . $_GET['page_id'] : '');
?>
<?=template_admin_header('Client Projects', 'resources', 'projects')?>

<?=generate_breadcrumbs([
    ['label' => 'Resource System', 'url' => 'index.php'],
    ['label' => 'Client Projects']
])?>

<div class="content-title">
    <div class="title">
       <i class="fa-solid fa-diagram-project"></i>
        <div class="txt">
            <h2>Client Projects</h2>
            <p>Manage client project records</p>
        </div>
    </div>
</div>

<?php if (isset($success_msg)): ?>
<div class="msg success">
    <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM369 209L241 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L335 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/></svg>
    <p><?=$success_msg?></p>
    <svg class="close" width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg>
</div>
<?php endif; ?>

<div class="content-header responsive-flex-column pad-top-5">
    <a href="client-project.php" class="btn btn-primary">
        <svg class="icon-left" width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 80c0-17.7-14.3-32-32-32s-32 14.3-32 32V224H48c-17.7 0-32 14.3-32 32s14.3 32 32 32H192V432c0 17.7 14.3 32 32 32s32-14.3 32-32V288H400c17.7 0 32-14.3 32-32s-14.3-32-32-32H256V80z"/></svg>
        Create New Project
    </a>
    <form action="" method="get">
        <div class="search">
            <label for="search">
                <input id="search" type="text" name="search" placeholder="Search records..." value="<?=htmlspecialchars($search, ENT_QUOTES)?>" class="responsive-width-100">
                <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"/></svg>
            </label>
        </div>
    </form>
</div>

<div class="content-block">
    <div class="table">
        <table>
            <thead>
                <tr>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=project_type_id'?>">Type<?php if ($order_by=='project_type_id'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=domain_id'?>">Domain<?php if ($order_by=='domain_id'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=subject'?>">Subject<?php if ($order_by=='subject'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=date_updated'?>">Updated<?php if ($order_by=='date_updated'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td>Actions</td>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)): ?>
                <tr>
                    <td colspan="20" style="text-align:center;">There are no records.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($records as $record): ?>
                <?php
                $stmt = $pdo->prepare('SELECT domain FROM domains WHERE id = ?');
                $stmt->execute([$record['domain_id'] ]);
                $domain_id = $stmt->fetch(PDO::FETCH_ASSOC); 
                
                $stmt = $pdo->prepare('SELECT name FROM project_types WHERE id = ?');
                $stmt->execute([$record['project_type_id'] ]);
                $project_type_id = $stmt->fetch(PDO::FETCH_ASSOC); 
                ?>
                <tr>
                    <td class="alt"><?=htmlspecialchars($project_type_id['name'], ENT_QUOTES)?></td>
                    <td class="alt"><?=htmlspecialchars($domain_id['domain'], ENT_QUOTES)?></td>
                    <td class="alt"><?=htmlspecialchars($record['subject'], ENT_QUOTES)?></td>
                    <td style="text-align:center" class="alt responsive-hidden"><?=time_elapsed_string($record['date_updated'])?></td>
                     <td class="actions">
                        <div class="table-dropdown">
                            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M8 256a56 56 0 1 1 112 0A56 56 0 1 1 8 256zm160 0a56 56 0 1 1 112 0 56 56 0 1 1 -112 0zm216-56a56 56 0 1 1 0 112 56 56 0 1 1 0-112z"/></svg>
                            <div class="table-dropdown-items">
                                <a href="client-project-use.php?id=<?=$record['id']?>" style="color:green">
                                    <span class="icon">
                                        <i class="fa-regular fa-credit-card fa-xs"></i>
                                    </span>
                                    Use
                                </a>
                                 <a href="client-project-view.php?id=<?=$record['id']?>" style="color:blue">
                                    <span class="icon">
                                      <i class="fa-regular fa-eye fa-xs"></i>
                                    </span>
                                    View
                                </a>
                                <a href="client-project.php?id=<?=$record['id']?>" style='color:orange'>
                                    <span class="icon">
                                     <i class="fa-regular fa-pen-to-square fa-xs"></i>
                                    </span>
                                    Edit
                                </a>
                                <a class="red" href="client-projects.php?delete=<?=$record['id']?>" onclick="return confirm('Are you sure you want to delete this project and ALL logs?')">
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

<div class="pagination">
    <?php if ($pagination_page > 1): ?>
    <a href="<?=$url?>&pagination_page=<?=$pagination_page-1?>&order=<?=$order?>&order_by=<?=$order_by?>">Prev</a>
    <?php endif; ?>
    <span>Page <?=$pagination_page?> of <?=ceil($records_total / $results_per_page) == 0 ? 1 : ceil($records_total / $results_per_page)?></span>
    <?php if ($pagination_page * $results_per_page < $records_total): ?>
    <a href="<?=$url?>&pagination_page=<?=$pagination_page+1?>&order=<?=$order?>&order_by=<?=$order_by?>">Next</a>
    <?php endif; ?>
</div>
<script src="assets/js/resource-system-script.js"></script>
<?=template_admin_footer()?>