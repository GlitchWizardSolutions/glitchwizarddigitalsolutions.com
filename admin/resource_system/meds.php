<?php
require 'assets/includes/admin_config.php';
$filename = '?';
$application= 'Resource System - Medications';
$inputs     = '';
$outputs    = '';
$noted      = 'public_html/admin/resource_system/meds.php';
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

try {
	$onthego_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name2 . ';charset=' . db_charset, db_user, db_pass);
	$onthego_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	// If there is an error with the connection, stop the script and display the error.
	exit('Failed to connect to the on the go database!');
}

// Delete record
if (isset($_GET['delete'])) {
    $stmt = $onthego_db->prepare('DELETE FROM meds WHERE id = ?');
    $stmt->execute([ $_GET['delete'] ]);
    header('Location: meds.php?success_msg=3');
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
$order_by_whitelist = [ 'patient', 'name', 'dosage', 'type', 'frequency', 'notes', 'status'];
$order_by = isset($_GET['order_by']) && in_array($_GET['order_by'], $order_by_whitelist) ? $_GET['order_by'] : 'patient';
// Number of results per pagination page
$results_per_page = 15;
// Declare query param variables
$param1 = ($pagination_page - 1) * $results_per_page;
$param2 = $results_per_page;
$param3 = '%' . $search . '%';
// SQL where clause
$where = '';

$where .= $search ? 'WHERE (patient LIKE :search OR name LIKE :search OR frequency LIKE :search OR notes LIKE :search) ' : '';
// Retrieve the total number of records from the database
$stmt = $onthego_db->prepare('SELECT COUNT(*) AS total FROM meds ' . $where);
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
$stmt->execute();
$records_total = $stmt->fetchColumn();
// SQL query to get all records from the "client-project" table
$stmt = $onthego_db->prepare('SELECT * FROM meds  ' . $where . ' ORDER BY ' . $order_by . ' ' . $order . ' LIMIT :start_results,:num_results');
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
$url = 'meds.php?search=' . $search . (isset($_GET['page_id']) ? '&page_id=' . $_GET['page_id'] : '');
?>
<?=template_admin_header('Medications', 'resources', 'meds')?>

<div class="content-title mb-3">
    <div class="title">
    <i class="fa-regular fa-handshake"></i>
        <div class="txt">
            <h2>Medications</h2>
            <p>All Household Medications</p>
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
        <a href="med.php" class="btn">+ Create New Medication Record</a>
    </div>
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
      
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=patient'?>">Patient&nbsp; &nbsp;<?php if ($order_by=='patient'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=name'?>">Name<?php if ($order_by=='name'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=dosage'?>">Dosage<?php if ($order_by=='dosage'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td> 
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=frequency'?>">Frequency<?php if ($order_by=='frequency'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                  
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=notes'?>">Notes<?php if ($order_by=='notes'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=status'?>">Status&nbsp; &nbsp;<?php if ($order_by=='status'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
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
                      <td> <?=substr($record['patient'], 0, 7)?>&nbsp; &nbsp; </td> 
                     <td> <?=htmlspecialchars($record['name'], ENT_QUOTES)?> </td>
                     <td> <?=htmlspecialchars($record['dosage'], ENT_QUOTES)?> </td>
                     <td> <?=htmlspecialchars($record['frequency'], ENT_QUOTES)?> </td>
                     <td class="responsive-hidden"> <?=htmlspecialchars($record['notes'], ENT_QUOTES)?> &nbsp;</td>
                     <td> <?=htmlspecialchars($record['status'])?>&nbsp; &nbsp;</td>
                     <td class="actions">
                        <div class="table-dropdown">
                            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M8 256a56 56 0 1 1 112 0A56 56 0 1 1 8 256zm160 0a56 56 0 1 1 112 0 56 56 0 1 1 -112 0zm216-56a56 56 0 1 1 0 112 56 56 0 1 1 0-112z"/></svg>
                            <div class="table-dropdown-items">
                          <a href="meds-barbara.php?id=<?=$record['id']?>" style="color:purple">
                                    <span class="icon">
                                      <i class="fa-regular fa-eye fa-xs"></i>
                                    </span>
                                    Barbara
                                </a>
                                <a href="meds-joseph.php?id=<?=$record['id']?>" style="color:purple">
                                    <span class="icon">
                                      <i class="fa-regular fa-eye fa-xs"></i>
                                    </span>
                                    Joseph
                                </a>
                                <a href="meds-dio.php?id=<?=$record['id']?>" style="color:purple">
                                    <span class="icon">
                                      <i class="fa-regular fa-eye fa-xs"></i>
                                    </span>
                                    Dio
                                </a>
                                <a href="meds-max.php?id=<?=$record['id']?>" style="color:purple">
                                    <span class="icon">
                                      <i class="fa-regular fa-eye fa-xs"></i>
                                    </span>
                                    Max
                                </a>
                                <a href="med-use.php?id=<?=$record['id']?>" style="color:green">
                                    <span class="icon">
                                      <i class="fa-solid fa-bolt fa-xs"></i>
                                    </span>
                                    Use
                                </a>
                                 <a href="med-view.php?id=<?=$record['id']?>" style="color:blue">
                                    <span class="icon">
                                      <i class="fa-regular fa-eye fa-xs"></i>
                                    </span>
                                    View
                                </a>
                                <a href="med.php?id=<?=$record['id']?>" style='color:orange'>
                                    <span class="icon">
                                     <i class="fa-regular fa-pen-to-square fa-xs"></i>
                                    </span>
                                    Edit
                                </a>
                                <a class="red" href="meds.php?delete=<?=$record['id']?>" onclick="return confirm('Are you sure you want to delete this project and ALL logs?')">
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