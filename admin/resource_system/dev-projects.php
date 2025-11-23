<?php
require 'assets/includes/admin_config.php';
include_once '../assets/includes/components.php';
// Connect to the login accounts Database using the PDO interface
try {
	$login_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name . ';charset=' . db_charset, db_user, db_pass);
	$login_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	// If there is an error with the connection, stop the script and display the error.
	exit('Failed to connect to the login system database!');
}
// Connect to the invoice database using the PDO interface
try {
	$invoice_system = new PDO('mysql:host=' . db_host . ';dbname=' . db_name5 . ';charset=' . db_charset, db_user, db_pass);
	$invoice_system->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	// If there is an error with the connection, stop the script and display the error.
	exit('Failed to connect to the invoice system database!');
}
// Connect to the invoice system Database using the PDO interface
try {
	$onthego_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name2 . ';charset=' . db_charset, db_user, db_pass);
	$onthego_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	// If there is an error with the connection, stop the script and display the error.
	exit('Failed to connect to the on the go database!');
}
// Delete record
if (isset($_GET['delete'])) {
    $stmt = $onthego_db->prepare('DELETE FROM dev_projects WHERE id = ?');
    $stmt->execute([ $_GET['delete'] ]);
    header('Location: dev-projects.php?success_msg=3');
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
$order_by_whitelist = ['id', 'description'];
$order_by = isset($_GET['order_by']) && in_array($_GET['order_by'], $order_by_whitelist) ? $_GET['order_by'] : 'id';
// Number of results per pagination page
$results_per_page = 15;
// Declare query param variables
$param1 = ($pagination_page - 1) * $results_per_page;
$param2 = $results_per_page;
$param3 = '%' . $search . '%';
// SQL where clause
$where = '';
$where .= $search ? 'WHERE (description LIKE :search) ' : '';
// Retrieve the total number of records from the database
$stmt = $onthego_db->prepare('SELECT COUNT(*) AS total FROM dev_projects ' . $where);
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
$stmt->execute();
$records_total = $stmt->fetchColumn();
// SQL query to get all records from the "dev-project" table
$stmt = $onthego_db->prepare('SELECT * FROM dev_projects  ' . $where . ' ORDER BY ' . $order_by . ' ' . $order . ' LIMIT :start_results,:num_results');
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
        $success_msg = 'Record deleted successfully!';
    }
    if ($_GET['success_msg'] == 4) {
        $success_msg = $_GET['imported'] . ' Record(s) imported successfully!';
    }
}
// Determine the URL
$url = 'dev-projects.php?search=' . $search . (isset($_GET['page_id']) ? '&page_id=' . $_GET['page_id'] : '');
?>
<?=template_admin_header('Dev Projects', 'resources', 'financials')?>

<?=generate_breadcrumbs([
    ['label' => 'Resource System', 'url' => 'index.php'],
    ['label' => 'Dev Projects']
])?>

<div class="content-title">
    <div class="title">
       <i class="fa-solid fa-code"></i>
        <div class="txt">
            <h2>Dev Projects</h2>
            <p>Manage development project records</p>
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
        <a href="dev-project.php" class="btn btn-primary">+ Create Dev Project</a>
        <a href="dev-project-impt.php" class="btn btn-primary mar-left-1">Import</a>
        <a href="dev-project-expt.php" class="btn btn-primary mar-left-1">Export</a>
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
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=id'?>">#<?php if ($order_by=='id'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=bank'?>">Description<?php if ($order_by=='description'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td>Account</td>
                    <td>Domain</td>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)): ?>
                <tr>
                    <td colspan="20" style="text-align:center;">There are no records.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($records as $record): ?>
                <?php //select domain from the table in $invoice_system
                    $stmt = $invoice_system->prepare('SELECT domain FROM domains WHERE id = ?');
                    $stmt->execute([$record['domain_id']]);
                    $domain_name = $stmt->fetchColumn();
                    ?>
                     <?php //select account from the table in $login_db
                    $stmt = $invoice_system->prepare('SELECT email FROM accounts WHERE id = ?');
                    $stmt->execute([$record['account_id']]);
                    $email = $stmt->fetchColumn();
                    ?>
                <tr>
                    <td class="responsive-hidden"><?=htmlspecialchars($record['id'], ENT_QUOTES)?></td>
                    <td><?=htmlspecialchars($domain_name, ENT_QUOTES)?></td>
                    <td class="responsive-hidden"><?=htmlspecialchars($email, ENT_QUOTES)?></td>
                    <td class="actions">
                        <div class="table-dropdown">
                            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M8 256a56 56 0 1 1 112 0A56 56 0 1 1 8 256zm160 0a56 56 0 1 1 112 0 56 56 0 1 1 -112 0zm216-56a56 56 0 1 1 0 112 56 56 0 1 1 0-112z"/></svg>
                            <div class="table-dropdown-items">
                                <a href="dev-project-use.php?id=<?=$record['id']?>" style="color:green">
                                    <span class="icon">
                                        <i class="fa-regular fa-credit-card fa-xs"></i>
                                    </span>
                                    Use
                                </a>
                                 <a href="dev-project-view.php?id=<?=$record['id']?>" style="color:blue">
                                    <span class="icon">
                                      <i class="fa-regular fa-eye fa-xs"></i>
                                    </span>
                                    View
                                </a>
                                <a href="dev-project.php?id=<?=$record['id']?>" style='color:orange'>
                                    <span class="icon">
                                     <i class="fa-regular fa-pen-to-square fa-xs"></i>
                                    </span>
                                    Edit
                                </a>
                                <a class="red" href="dev-projects.php?delete=<?=$account['id']?>" onclick="return confirm('Are you sure you want to delete this account?')">
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