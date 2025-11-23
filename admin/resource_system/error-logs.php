<?php
require 'assets/includes/admin_config.php';
include_once '../assets/includes/components.php';
$application= 'Resource System - Error Logs';
$noted      = 'public_html/admin/resource_system/error-logs.php';

// Connect to the Error Handling Database using the PDO interface
try {
	$error_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name9 . ';charset=' . db_charset, db_user, db_pass);
	$error_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	// If there is an error with the connection, stop the script and display the error.
	exit('Failed to connect to the error handling database: ' . $exception->getMessage());
}
 
//$stmt =$error_db->prepare('SELECT * FROM error_handling ORDER BY "id" LIMIT 1');
//$stmt->execute();
//$errors = $stmt->fetch(PDO::FETCH_ASSOC);

// Delete record
if (isset($_GET['delete'])) {
    $stmt = $error_db->prepare('DELETE FROM error_handling WHERE id = ?');
    $stmt->execute([ $_GET['delete'] ]);
    header('Location: error-logs.php?success_msg=3');
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
$order_by_whitelist = ['id','application','pagename','path','section','error_type','severity','error_code','thrown','inputs','outputs','noted','status','timestamp'];
$order_by = isset($_GET['order_by']) && in_array($_GET['order_by'], $order_by_whitelist) ? $_GET['order_by'] : 'timestamp';
// Number of results per pagination page
$results_per_page = 15;
// Declare query param variables
$param1 = ($pagination_page - 1) * $results_per_page;
$param2 = $results_per_page;
$param3 = '%' . $search . '%';
// SQL where clause
$where = '';
$where .= $search ? 'WHERE (application LIKE :search OR pagename LIKE :search OR section LIKE :search OR error_type LIKE :search OR severity LIKE :search OR thrown LIKE :search OR noted LIKE :search OR timestamp LIKE :search) ' : '';
// Retrieve the total number of records from the database
$stmt = $error_db->prepare('SELECT COUNT(*) AS total FROM error_handling ' . $where);
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
$stmt->execute();
$records_total = $stmt->fetchColumn();
// SQL query to get all records from the "error-log" table
$stmt = $error_db->prepare('SELECT * FROM error_handling  ' . $where . ' ORDER BY ' . $order_by . ' ' . $order . ' LIMIT :start_results,:num_results');
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
$url = 'error-logs.php?search=' . $search . (isset($_GET['page_id']) ? '&page_id=' . $_GET['page_id'] : '');
?>
<?=template_admin_header('Error Logs', 'resources', 'errors')?>

<?=generate_breadcrumbs([
    ['label' => 'Resource System', 'url' => 'index.php'],
    ['label' => 'Error Logs']
])?>

<div class="content-title">
    <div class="title">
       <i class="fa-solid fa-triangle-exclamation"></i>
        <div class="txt">
            <h2>Error Logs</h2>
            <p>System error tracking and monitoring</p>
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
    
    <form action="" method="get">
        <div class="search">
            <label for="search">
                <input id="search" type="text" name="search" placeholder="Search records..." value="<?=htmlspecialchars($search, ENT_QUOTES)?>" class="responsive-width-100">
                <i class="fas fa-search"></i>
            </label>
        </div>
    </form>
</div>

<div class="content-block">
    <div class="table">
        <table>
            <thead>
                <tr>  
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=timestamp'?>">Timestamp<?php if ($order_by=='timestamp'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=severity'?>">Severity<?php if ($order_by=='severity'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=application'?>">Application<?php if ($order_by=='application'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=pagename'?>">Page<?php if ($order_by=='pagename'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=section'?>">Section<?php if ($order_by=='section'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=thrown'?>">Error<?php if ($order_by=='thrown'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
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
                <tr>
                     <td><?=date('m-d-y h:ia', strtotime($record['timestamp']))?></td>
                     <td>
                        <?php
                        $severity_class = [
                            'Critical' => 'red',
                            'Error' => 'red',
                            'Warning' => 'orange',
                            'Notice' => 'gray',
                            'Info' => 'green'
                        ];
                        $class = $severity_class[$record['severity']] ?? 'gray';
                        ?>
                        <span class="<?=$class?>"><?=htmlspecialchars($record['severity'], ENT_QUOTES)?></span>
                     </td>
                     <td><?=htmlspecialchars($record['application'], ENT_QUOTES)?></td>
                     <td><?=htmlspecialchars($record['pagename'], ENT_QUOTES)?></td>
                     <td><?=htmlspecialchars($record['section'], ENT_QUOTES)?></td>
                     <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?=htmlspecialchars($record['thrown'], ENT_QUOTES)?>"><?=htmlspecialchars(substr($record['thrown'], 0, 100), ENT_QUOTES)?><?=strlen($record['thrown']) > 100 ? '...' : ''?></td>
                     
                     <td class="actions">
                        <div class="table-dropdown">
                            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M8 256a56 56 0 1 1 112 0A56 56 0 1 1 8 256zm160 0a56 56 0 1 1 112 0 56 56 0 1 1 -112 0zm216-56a56 56 0 1 1 0 112 56 56 0 1 1 0-112z"/></svg>
                            <div class="table-dropdown-items">
                                
                                 <a href="error-log-view.php?id=<?=$record['id']?>" style="color:blue">
                                    <span class="icon">
                                      <i class="fa-regular fa-eye fa-xs"></i>
                                    </span>
                                    View
                                </a>
                                
                                <a class="red" href="error-logs.php?delete=<?=$record['id']?>" onclick="return confirm('Are you sure you want to delete this project and ALL logs?')">
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