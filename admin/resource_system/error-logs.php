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

// Bulk delete records
if (isset($_POST['bulk_delete']) && !empty($_POST['selected_logs'])) {
    $selected_ids = $_POST['selected_logs'];
    
    // Check if "delete all" was selected
    if (isset($_POST['delete_all_records']) && $_POST['delete_all_records'] == '1') {
        // Delete all records matching current search/filter
        $where = '';
        $where .= $search ? 'WHERE (application LIKE :search OR pagename LIKE :search OR section LIKE :search OR error_type LIKE :search OR severity LIKE :search OR thrown LIKE :search OR noted LIKE :search OR timestamp LIKE :search) ' : '';
        
        $stmt = $error_db->prepare('DELETE FROM error_handling ' . $where);
        if ($search) {
            $param3 = '%' . $search . '%';
            $stmt->bindParam('search', $param3, PDO::PARAM_STR);
        }
        $stmt->execute();
    } else {
        // Delete only selected IDs
        // Filter out any non-numeric or empty values
        $selected_ids = array_filter($selected_ids, function($id) {
            return is_numeric($id) && $id > 0;
        });
        
        if (!empty($selected_ids)) {
            $placeholders = str_repeat('?,', count($selected_ids) - 1) . '?';
            $stmt = $error_db->prepare("DELETE FROM error_handling WHERE id IN ($placeholders)");
            $stmt->execute(array_values($selected_ids));
        }
    }
    
    // Redirect to page 1 to avoid empty page after deletion
    header('Location: error-logs.php?success_msg=3&pagination_page=1');
    exit;
}

// Retrieve the GET request parameters (if specified)
$pagination_page = isset($_GET['pagination_page']) ? $_GET['pagination_page'] : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';
// Filters
$status = isset($_GET['status']) ? $_GET['status'] : '';
// Order by column
$order = isset($_GET['order']) && $_GET['order'] == 'ASC' ? 'ASC' : 'DESC';
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

<div class="content-title mb-3">
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
    
    <div style="margin-left: auto;">
        <button type="button" id="bulk-delete-btn" class="btn" style="background: #dc3545; color: white; display: none;" onclick="confirmBulkDelete()">
            <i class="fas fa-trash"></i> Delete Selected
        </button>
        <button type="button" id="delete-all-btn" class="btn" style="background: #d9534f; color: white; margin-left: 10px;" onclick="confirmDeleteAll()">
            <i class="fas fa-trash-alt"></i> Delete All (<?=$records_total?>)
        </button>
    </div>
</div>

<form id="bulk-delete-form" method="post" action="">
<div class="content-block">
    <div class="table">
        <table>
            <thead>
                <tr>
                    <td style="width: 40px;">
                        <input type="checkbox" id="select-all" onclick="toggleSelectAll(this)">
                    </td>
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
                     <td>
                        <input type="checkbox" name="selected_logs[]" value="<?=$record['id']?>" class="log-checkbox" onchange="updateBulkDeleteButton()">
                     </td>
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
                                <a href="error-log-use.php?id=<?=$record['id']?>" style="color:green">
                                    <span class="icon">
                                      <i class="fa-solid fa-bolt fa-xs"></i>
                                    </span>
                                    Use
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
</form>

<div class="pagination">
    <?php if ($pagination_page > 1): ?>
    <a href="<?=$url?>&pagination_page=<?=$pagination_page-1?>&order=<?=$order?>&order_by=<?=$order_by?>">Prev</a>
    <?php endif; ?>
    <span>Page <?=$pagination_page?> of <?=ceil($records_total / $results_per_page) == 0 ? 1 : ceil($records_total / $results_per_page)?></span>
    <?php if ($pagination_page * $results_per_page < $records_total): ?>
    <a href="<?=$url?>&pagination_page=<?=$pagination_page+1?>&order=<?=$order?>&order_by=<?=$order_by?>">Next</a>
    <?php endif; ?>
</div>
<script>
function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.log-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
    updateBulkDeleteButton();
}

function updateBulkDeleteButton() {
    const checkboxes = document.querySelectorAll('.log-checkbox:checked');
    const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
    const selectAllCheckbox = document.getElementById('select-all');
    
    if (checkboxes.length > 0) {
        bulkDeleteBtn.style.display = 'inline-block';
        bulkDeleteBtn.textContent = `Delete Selected (${checkboxes.length})`;
    } else {
        bulkDeleteBtn.style.display = 'none';
        selectAllCheckbox.checked = false;
    }
}

function confirmBulkDelete() {
    const checkboxes = document.querySelectorAll('.log-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Please select at least one error log to delete.');
        return;
    }
    
    if (confirm(`Are you sure you want to delete ${checkboxes.length} error log(s)?`)) {
        // Add the bulk_delete input to the form
        const form = document.getElementById('bulk-delete-form');
        const bulkDeleteInput = document.createElement('input');
        bulkDeleteInput.type = 'hidden';
        bulkDeleteInput.name = 'bulk_delete';
        bulkDeleteInput.value = '1';
        form.appendChild(bulkDeleteInput);
        form.submit();
    }
}

function confirmDeleteAll() {
    const totalRecords = <?=$records_total?>;
    if (totalRecords === 0) {
        alert('There are no records to delete.');
        return;
    }
    
    if (confirm(`⚠️ WARNING: This will delete ALL ${totalRecords} error log(s) matching your current search/filter.\n\nThis action cannot be undone. Are you sure?`)) {
        const form = document.getElementById('bulk-delete-form');
        
        // Add hidden inputs
        const bulkDeleteInput = document.createElement('input');
        bulkDeleteInput.type = 'hidden';
        bulkDeleteInput.name = 'bulk_delete';
        bulkDeleteInput.value = '1';
        form.appendChild(bulkDeleteInput);
        
        const deleteAllInput = document.createElement('input');
        deleteAllInput.type = 'hidden';
        deleteAllInput.name = 'delete_all_records';
        deleteAllInput.value = '1';
        form.appendChild(deleteAllInput);
        
        // Add a dummy checkbox so the form has selected_logs
        const dummyCheckbox = document.createElement('input');
        dummyCheckbox.type = 'hidden';
        dummyCheckbox.name = 'selected_logs[]';
        dummyCheckbox.value = '1';
        form.appendChild(dummyCheckbox);
        
        form.submit();
    }
}
</script>
<script src="assets/js/resource-system-script.js"></script>
<?=template_admin_footer()?>