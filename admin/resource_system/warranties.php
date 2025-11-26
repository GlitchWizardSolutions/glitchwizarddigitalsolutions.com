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
 
try {
	$onthego_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name2 . ';charset=' . db_charset, db_user, db_pass);
	$onthego_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	// If there is an error with the connection, stop the script and display the error.
	exit('Failed to connect to the on the go database!');
}
// Delete record
if (isset($_GET['delete'])) {
    $stmt = $onthego_db->prepare('DELETE FROM warranty_tickets WHERE id = ?');
    $stmt->execute([ $_GET['delete'] ]);
    header('Location: warranties.php?success_msg=3');
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
$order_by_whitelist = ['title', 'ticket_status', 'owner'];
$order_by = isset($_GET['order_by']) && in_array($_GET['order_by'], $order_by_whitelist) ? $_GET['order_by'] : 'id';
// Number of results per pagination page
$results_per_page = 15;
// Declare query param variables
$param1 = ($pagination_page - 1) * $results_per_page;
$param2 = $results_per_page;
$param3 = '%' . $search . '%';
// SQL where clause
$where = '';
$where .= $search ? 'WHERE (title LIKE :search OR owner LIKE :search OR msg LIKE :search) ' : '';
// Retrieve the total number of records from the database
$stmt = $onthego_db->prepare('SELECT COUNT(*) AS total FROM warranty_tickets ' . $where);
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
$stmt->execute();
$records_total = $stmt->fetchColumn();
// SQL query to get all records from the "dev-project" table
$stmt = $onthego_db->prepare('SELECT * FROM warranty_tickets  ' . $where . ' ORDER BY ' . $order_by . ' ' . $order . ' LIMIT :start_results,:num_results');
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
}
// Determine the URL
$url = 'warranties.php?search=' . $search . (isset($_GET['page_id']) ? '&page_id=' . $_GET['page_id'] : '');
?>
<?=template_admin_header('Warranties', 'resources', 'warranties')?>

<?=generate_breadcrumbs([
    ['label' => 'Resource System', 'url' => 'index.php'],
    ['label' => 'Warranties']
])?>

<div class="content-title mb-3">
    <div class="title">
        <i class="fa-solid fa-shield-halved"></i>
        <div class="txt">
            <h2>Warranties</h2>
            <p>Manage product warranties and support contracts</p>
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
        <a href="warranty.php" class="btn btn-primary">Create Warranty Record</a>
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
                    <td class="responsive-hidden">Image</td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=title'?>">Title<?php if ($order_by=='title'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=msg'?>">Message<?php if ($order_by=='msg'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td class="responsive-hidden">Status&nbsp;&nbsp;</td>
                    <td class="responsive-hidden">Purchased&nbsp;&nbsp;</td> 
                    <td class="responsive-hidden">Expires&nbsp;&nbsp;</td> 
                    <td>Type&nbsp;&nbsp;</td>
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
                    $stmt = $onthego_db->prepare('SELECT * FROM warranty_types WHERE id = ?');
                    $stmt->execute([$record['warranty_type_id']]);
                    $waranty_type_id = $stmt->fetch();
                    
                    // Get first uploaded image for thumbnail
                    $stmt = $onthego_db->prepare('SELECT * FROM warranty_tickets_uploads WHERE ticket_id = ? LIMIT 1');
                    $stmt->execute([$record['id']]);
                    $first_upload = $stmt->fetch(PDO::FETCH_ASSOC);
                    ?>
                     
                <tr>
                    <td class="responsive-hidden">
                        <?php if ($first_upload): ?>
                        <?php
                        // Handle old database paths that include 'warranty-ticket-uploads/' prefix
                        $clean_filepath = str_replace('warranty-ticket-uploads/', '', $first_upload['filepath']);
                        $file_path = warranty_resource_uploads_path . $clean_filepath;
                        $file_url = warranty_resource_uploads_url . $clean_filepath;
                        $is_image = @getimagesize($file_path) !== false;
                        ?>
                        <?php if ($is_image): ?>
                        <a href="warranty-view.php?id=<?=$record['id']?>">
                            <img src="<?=$file_url?>" width="50" height="50" style="object-fit: cover; border-radius: 4px; border: 1px solid #ddd;" alt="Thumbnail">
                        </a>
                        <?php else: ?>
                        <a href="warranty-view.php?id=<?=$record['id']?>">
                            <i class="fas fa-file" style="font-size: 30px; color: #6b46c1;"></i>
                        </a>
                        <?php endif; ?>
                        <?php else: ?>
                        <i class="fas fa-image" style="font-size: 30px; color: #ccc;"></i>
                        <?php endif; ?>
                    </td>
                    <td><?=htmlspecialchars($record['title'], ENT_QUOTES)?></td>
                     <td class="responsive-hidden"><?=htmlspecialchars($record['msg'], ENT_QUOTES)?></td>
                       <td class="responsive-hidden"><?=htmlspecialchars($record['ticket_status'], ENT_QUOTES)?></td>&nbsp;&nbsp;
                         <td class="responsive-hidden"><?=date('m/y', strtotime($record['purchase_date']))?></td>
                          <td class="responsive-hidden"><?=date('m/y', strtotime($record['warranty_expiration_date']))?></td>
                            <td class="responsive-hidden"><?=htmlspecialchars($waranty_type_id['name'], ENT_QUOTES)?></td>
                 
                    <td class="actions">
                        <div class="table-dropdown">
                            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M8 256a56 56 0 1 1 112 0A56 56 0 1 1 8 256zm160 0a56 56 0 1 1 112 0 56 56 0 1 1 -112 0zm216-56a56 56 0 1 1 0 112 56 56 0 1 1 0-112z"/></svg>
                            <div class="table-dropdown-items">
                                <a href="warranty-use.php?id=<?=$record['id']?>" style="color:green">
                                    <span class="icon">
                                        <i class="fa-regular fa-credit-card fa-xs"></i>
                                    </span>
                                    Use
                                </a>
                                 <a href="warranty-view.php?id=<?=$record['id']?>" style="color:blue">
                                    <span class="icon">
                                      <i class="fa-regular fa-eye fa-xs"></i>
                                    </span>
                                    View
                                </a>
                                <a href="warranty.php?id=<?=$record['id']?>" style='color:orange'>
                                    <span class="icon">
                                     <i class="fa-regular fa-pen-to-square fa-xs"></i>
                                    </span>
                                    Edit
                                </a>
                                <a class="red" href="warranties.php?delete=<?=$account['id']?>" onclick="return confirm('Are you sure you want to delete this warranty product?')">
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