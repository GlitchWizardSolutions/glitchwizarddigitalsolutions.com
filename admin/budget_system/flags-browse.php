<?php
include_once 'assets/includes/admin_config.php';
// Connect to MySQL database
$budget_pdo = pdo_connect_budget_db($db_host, $db_name7, $db_user, $db_pass);
// Error message
$error_msg = ''; 
// Success message
$success_msg = NULL;

$stmt =$budget_pdo->prepare('SELECT * FROM bills');
$stmt->execute();
$bills = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $budget_pdo->prepare('SELECT * FROM budget');
$stmt->execute();
$budget = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $budget_pdo->prepare('SELECT * FROM flags');
$stmt->execute();
$flags = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt =$budget_pdo->prepare('SELECT * FROM notes');
$stmt->execute();
$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get the page via GET request (URL param: page), if non exists default the page to 1
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
// Number of records to show on each page
$records_per_page = isset($_GET['records_per_page']) && (is_numeric($_GET['records_per_page']) || $_GET['records_per_page'] == 'all') ? $_GET['records_per_page'] : $default_records_per_page;
// Column list
$columns = ['id', 'flag', 'description'];
// Order by which column if specified 
$order_by = isset($_GET['order_by']) && in_array($_GET['order_by'], $columns) ? $_GET['order_by'] : 'description';
// Sort by ascending or descending if specified (default to ASC)
$order_sort = isset($_GET['order_sort']) && $_GET['order_sort'] == 'DESC' ? 'DESC' : 'ASC';
$where_sql = '';
// Add search to SQL query (if search term exists)
if (isset($_GET['search']) && !empty($_GET['search'])) {
	$where_sql .= ($where_sql ? ' AND ' : ' WHERE ') .  'description LIKE :search_query OR flag LIKE :search_query';
}
// Limit SQL
$limit_sql = '';
if ($records_per_page != 'all') {
	$limit_sql = ' LIMIT :current_page, :record_per_page ';
}
// SQL statement to get all references with search query
$stmt = $budget_pdo->prepare('SELECT * FROM flags ' . $where_sql . ' ORDER BY ' . $order_by . ' ' . $order_sort . $limit_sql);
// Bind the search query param to the SQL query
if (isset($_GET['search']) && !empty($_GET['search'])) {	
	$stmt->bindValue(':search_query', '%' . $_GET['search'] . '%');
}
// Bind the page and records per page params to the SQL query
if ($records_per_page != 'all') {
	$stmt->bindValue(':current_page', ($page-1)*(int)$records_per_page, PDO::PARAM_INT);
	$stmt->bindValue(':record_per_page', (int)$records_per_page, PDO::PARAM_INT);
}
// Execute the prepared statement and fetch the results
$stmt->execute();
// Fetch the records so we can populate them in our template below.
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Get the total number of records, so we can determine whether there should be a next and previous button
$stmt = $budget_pdo->prepare('SELECT COUNT(*) FROM flags' . $where_sql);
// Bind the search query param to the SQL query
if (isset($_GET['search']) && !empty($_GET['search'])) {	
	$stmt->bindValue(':search_query', '%' . $_GET['search'] . '%');
}
$stmt->execute();
// Total number of results
$num_results = $stmt->fetchColumn();
?>
<?=template_admin_header('Budget System', 'budget', 'flags')?>
<div class="content read">

	<div class="page-title">
		<i class="fa-regular fa-address-book fa-lg"></i>
		<div class="wrap">
			<h2>Read flags Table</h2>
			<p></p>
		</div>
	</div>

	<form action="" method="get" class="crud-form">

		<div class="top">
		
			<div class="wrap">
				<div class="search">
					<input type="text" name="search" placeholder="Search..." value="<?=isset($_GET['search']) ? htmlentities($_GET['search'], ENT_QUOTES) : ''?>">
					<i class="fa-solid fa-magnifying-glass"></i>
				</div>
			</div>
		</div>

		<div class="table">
			<table>
				<thead>
					<tr>
					 
 						<td<?=$order_by=='id'?' class="active"':''?>>
							<a href="flags-browse.php?page=1&records_per_page=<?=$records_per_page?>&order_by=id&order_sort=<?=$order_sort == 'ASC' ? 'DESC' : 'ASC'?> <?=isset($_GET['search']) ? '&search=' . htmlentities($_GET['search'], ENT_QUOTES) : ''?>">
							 Id
								<?php if ($order_by == 'id'): ?>
								<i class="fa-solid fa-arrow-<?=str_replace(array('ASC', 'DESC'), array('up', 'down'), $order_sort)?>-long fa-sm"></i>
								<?php endif; ?>
							</a>
						</td>
					
						<td<?=$order_by=='flag'?' class="active"':''?>>
							<a href="flags-browse.php?page=1&records_per_page=<?=$records_per_page?>&order_by=flag&order_sort=<?=$order_sort == 'ASC' ? 'DESC' : 'ASC'?> <?=isset($_GET['search']) ? '&search=' . htmlentities($_GET['search'], ENT_QUOTES) : ''?>">
							 Flag
								<?php if ($order_by == 'flag'): ?>
								<i class="fa-solid fa-arrow-<?=str_replace(array('ASC', 'DESC'), array('up', 'down'), $order_sort)?>-long fa-sm"></i>
								<?php endif; ?>
							</a>
						</td>
						
					<td<?=$order_by=='description'?' class="active"':''?>>
							<a href="flags-browse.php?page=1&records_per_page=<?=$records_per_page?>&order_by=description&order_sort=<?=$order_sort == 'ASC' ? 'DESC' : 'ASC'?> <?=isset($_GET['search']) ? '&search=' . htmlentities($_GET['search'], ENT_QUOTES) : ''?>">
							 Description
								<?php if ($order_by == 'description'): ?>
								<i class="fa-solid fa-arrow-<?=str_replace(array('ASC', 'DESC'), array('up', 'down'), $order_sort)?>-long fa-sm"></i>
								<?php endif; ?>
							</a>
						</td>
						<td>Actions</td>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($results)): ?>
					<tr>
						<td colspan="10" style="text-align:center;">There are no records.</td>
					</tr>
					<?php endif; ?>
					<?php foreach ($results as $result): ?>
		 
					<tr>
						<td class="id responsive-hidden"><?=htmlspecialchars($result['id']?? '', ENT_QUOTES)?></td>
						<td class="flag responsive-hidden"><?=htmlspecialchars($result['flag']?? '', ENT_QUOTES)?></td>
					    <td class="description responsive-hidden"><?=htmlspecialchars($result['description']?? '', ENT_QUOTES)?></td>
					    
						<td class="actions">
						<a href="flag-view.php?id=<?=$result['id']?>"   class="edit"> <i class="fa-solid far fa-eye fa-xs"></i> </a>
						<a href="flag-edit.php?id=<?=$result['id']?>" class="edit"> <i class="fa-solid fa-pen fa-xs"></i></a>
						<a href="flag-delete.php?id=<?=$result['id']?>" class="trash"> <i class="fa-solid fa-xmark fa-xs"></i></a>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
 
			<div class="pagination">
				<?php if ($records_per_page != 'all'): ?>
				<?php if ($page > 1): ?>
				<a href="flags-browse.php?page=<?=$page-1?>&records_per_page=<?=$records_per_page?>&order_by=<?=$order_by?>&order_sort=<?=$order_sort?> <?=isset($_GET['search']) ? '&search=' . htmlentities($_GET['search'], ENT_QUOTES) : ''?>" class="prev">
					<i class="fa-solid fa-angle-left"></i> Prev
				</a>
				<?php endif; ?>
				<?php if ($page > 1): ?>
				<a href="flags-browse.php?page=1&records_per_page=<?=$records_per_page?>&order_by=<?=$order_by?>&order_sort=<?=$order_sort?> <?=isset($_GET['search']) ? '&search=' . htmlentities($_GET['search'], ENT_QUOTES) : ''?>">1</a>
				<?php endif; ?>
				<?php if ($page > 2): ?>
				<div class="dots">...</div>
				<?php if ($page == ceil($num_results/$records_per_page) && ceil($num_results/$records_per_page) > 3): ?>
				<a href="flags-browse.php?page=<?=$page-2?>&records_per_page=<?=$records_per_page?>&order_by=<?=$order_by?>&order_sort=<?=$order_sort?> <?=isset($_GET['search']) ? '&search=' . htmlentities($_GET['search'], ENT_QUOTES) : ''?>"><?=$page-2?></a>
				<?php endif; ?>
				<?php endif; ?>
				<?php if ($page-1 > 1): ?>
				<a href="flags-browse.php?page=<?=$page-1?>&records_per_page=<?=$records_per_page?>&order_by=<?=$order_by?>&order_sort=<?=$order_sort?> <?=isset($_GET['search']) ? '&search=' . htmlentities($_GET['search'], ENT_QUOTES) : ''?>"><?=$page-1?></a>
				<?php endif; ?>
				<a href="flags-browse.php?page=<?=$page?>&records_per_page=<?=$records_per_page?>&order_by=<?=$order_by?>&order_sort=<?=$order_sort?> <?=isset($_GET['search']) ? '&search=' . htmlentities($_GET['search'], ENT_QUOTES) : ''?>" class="selected"><?=$page?></a>
				<?php if ($page+1 < ceil($num_results/$records_per_page)): ?>
				<a href="flags-browse.php?page=<?=$page+1?>&records_per_page=<?=$records_per_page?>&order_by=<?=$order_by?>&order_sort=<?=$order_sort?> <?=isset($_GET['search']) ? '&search=' . htmlentities($_GET['search'], ENT_QUOTES) : ''?>"><?=$page+1?></a>
				<?php if ($page == 1 && $page+2 < ceil($num_results/$records_per_page)): ?>
				<a href="flags-browse.php?page=<?=$page+2?>&records_per_page=<?=$records_per_page?>&order_by=<?=$order_by?>&order_sort=<?=$order_sort?> <?=isset($_GET['search']) ? '&search=' . htmlentities($_GET['search'], ENT_QUOTES) : ''?>"><?=$page+2?></a>
				<?php endif; ?>
				<div class="dots">...</div>
				<?php endif; ?>
				<?php if ($page < ceil($num_results/$records_per_page)): ?>
				<a href="flags-browse.php?page=<?=ceil($num_results/$records_per_page)?>&records_per_page=<?=$records_per_page?>&order_by=<?=$order_by?>&order_sort=<?=$order_sort?> <?=isset($_GET['search']) ? '&search=' . htmlentities($_GET['search'], ENT_QUOTES) : ''?>"><?=ceil($num_results/$records_per_page)?></a>
				<?php endif; ?>
				<?php if ($records_per_page != 'all' && $page < ceil($num_results/$records_per_page)): ?>
				<a href="flags-browse.php?page=<?=$page+1?>&records_per_page=<?=$records_per_page?>&order_by=<?=$order_by?>&order_sort=<?=$order_sort?> <?=isset($_GET['search']) ? '&search=' . htmlentities($_GET['search'], ENT_QUOTES) : ''?>" class="next">
					Next <i class="fa-solid fa-angle-right"></i>
				</a>
				<?php endif; ?>
				<?php endif; ?>
			</div>
		</div>
	</form>
</div>
<style>
    .right{
        text-align: right;
    }
</style>
<?=template_admin_footer()?>