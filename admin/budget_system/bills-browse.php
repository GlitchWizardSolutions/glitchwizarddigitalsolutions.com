<?php
//Bills Browse 11/21/2024
include_once 'assets/includes/admin_config.php';
// Connect to MySQL database
$budget_pdo = pdo_connect_budget_db($db_host, $db_name7, $db_user, $db_pass);
// Error message
$error_msg = ''; 
// Success message
$success_msg = NULL;
//flags
/* 
2 is reserves prior to upgrade;
3 is incidentals
4 is reimbursement to minumum balance
5 is monthly base budget allowance
6 is reimbursement to savings
9 is budgeted
10 is refunded
*/
$stmt =$budget_pdo->prepare('SELECT * FROM bills');
$stmt->execute();
$bills = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $budget_pdo->prepare('SELECT * FROM budget');
$stmt->execute();
$budget = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt =$budget_pdo->prepare('SELECT * FROM hancock');
$stmt->execute();
$hancocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get the page via GET request (URL param: page), if non exists default the page to 1
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
// Number of records to show on each page
$records_per_page = isset($_GET['records_per_page']) && (is_numeric($_GET['records_per_page']) || $_GET['records_per_page'] == 'all') ? $_GET['records_per_page'] : $default_records_per_page;
// Column list
$columns = ['next_due_date', 'next_due_amount', 'bill', 'hancock','description'];
// Order by which column if specified (default to id)
$order_by = isset($_GET['order_by']) && in_array($_GET['order_by'], $columns) ? $_GET['order_by'] : 'next_due_date';
// Sort by ascending or descending if specified (default to ASC)
$order_sort = isset($_GET['order_sort']) && $_GET['order_sort'] == 'DESC' ? 'ASC' : 'ASC';
$where_sql = '';
// Add search to SQL query (if search term exists)
if (isset($_GET['search']) && !empty($_GET['search'])) {

	$where_sql .= ($where_sql ? ' AND ' : ' WHERE ') .  'bill LIKE :search_query OR hancock LIKE :search_query OR description LIKE :search_query OR next_due_amount LIKE :search_query OR next_due_date LIKE :search_query';
}
// Limit SQL
$limit_sql = '';
if ($records_per_page != 'all') {
	$limit_sql = ' LIMIT :current_page, :record_per_page ';
}
// SQL statement to get all references with search query
$stmt = $budget_pdo->prepare('SELECT * FROM bills ' . $where_sql . ' ORDER BY ' . $order_by . ' ' . $order_sort . $limit_sql);
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
$stmt = $budget_pdo->prepare('SELECT COUNT(*) FROM bills' . $where_sql);
// Bind the search query param to the SQL query
if (isset($_GET['search']) && !empty($_GET['search'])) {	
	$stmt->bindValue(':search_query', '%' . $_GET['search'] . '%');
}
$stmt->execute();
// Total number of results
$num_results = $stmt->fetchColumn();
?>
<?=template_admin_header('Budget System', 'budget', 'bills')?>
<div class="content read">

	<div class="page-title">
		<i class="fa-regular fa-address-book fa-lg"></i>
		<div class="wrap">
			<h2>Browse Bills Table</h2>
			<p></p>
		</div>
	</div>
<a href="bills-create.php" class="btn">
        <svg class="icon-left" width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 80c0-17.7-14.3-32-32-32s-32 14.3-32 32V224H48c-17.7 0-32 14.3-32 32s14.3 32 32 32H192V432c0 17.7 14.3 32 32 32s32-14.3 32-32V288H400c17.7 0 32-14.3 32-32s-14.3-32-32-32H256V80z"/></svg>
       Create a New Bill
    </a>
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
					    <td<?=$order_by=='flags_id'?' class="active"':''?>>
							<a href="bills-browse.php?page=1&records_per_page=<?=$records_per_page?>&order_by=flags_id&order_sort=<?=$order_sort == 'ASC' ? 'DESC' : 'ASC'?> <?=isset($_GET['search']) ? '&search=' . htmlentities($_GET['search'], ENT_QUOTES) : ''?>">
							 Flag Id
								<?php if ($order_by == 'flags_id'): ?>
								<i class="fa-solid fa-arrow-<?=str_replace(array('ASC', 'DESC'), array('up', 'down'), $order_sort)?>-long fa-sm"></i>
								<?php endif; ?>
							</a>
						</td>
					    	<td<?=$order_by=='next_due_date'?' class="active"':''?>>
							<a href="bills-browse.php?page=1&records_per_page=<?=$records_per_page?>&order_by=next_due_date&order_sort=<?=$order_sort == 'ASC' ? 'DESC' : 'ASC'?> <?=isset($_GET['search']) ? '&search=' . htmlentities($_GET['search'], ENT_QUOTES) : ''?>">
							 Due Date
								<?php if ($order_by == 'next_due_date'): ?>
								<i class="fa-solid fa-arrow-<?=str_replace(array('ASC', 'DESC'), array('up', 'down'), $order_sort)?>-long fa-sm"></i>
								<?php endif; ?>
							</a>
						</td>
 						<td<?=$order_by=='next_due_amount'?' class="active"':''?>>
							<a href="bills-browse.php?page=1&records_per_page=<?=$records_per_page?>&order_by=next_due_amount&order_sort=<?=$order_sort == 'ASC' ? 'DESC' : 'ASC'?> <?=isset($_GET['search']) ? '&search=' . htmlentities($_GET['search'], ENT_QUOTES) : ''?>">
							 Amount Due
								<?php if ($order_by == 'next_due_amount'): ?>
								<i class="fa-solid fa-arrow-<?=str_replace(array('ASC', 'DESC'), array('up', 'down'), $order_sort)?>-long fa-sm"></i>
								<?php endif; ?>
							</a>
						</td>
						
					
							<td class="responsive-hidden"<?=$order_by=='bill'?' class="active"':''?>>
							<a href="bills-browse.php?page=1&records_per_page=<?=$records_per_page?>&order_by=bill&order_sort=<?=$order_sort == 'ASC' ? 'DESC' : 'ASC'?> <?=isset($_GET['search']) ? '&search=' . htmlentities($_GET['search'], ENT_QUOTES) : ''?>">
								Bill
								<?php if ($order_by == 'bill'): ?>
								<i class="fa-solid fa-arrow-<?=str_replace(array('ASC', 'DESC'), array('up', 'down'), $order_sort)?>-long fa-sm"></i>
								<?php endif; ?>
							</a>
						</td>	
								<td<?=$order_by=='hancock'?' class="active"':''?>>
							<a href="bills-browse.php?page=1&records_per_page=<?=$records_per_page?>&order_by=hancock&order_sort=<?=$order_sort == 'ASC' ? 'DESC' : 'ASC'?> <?=isset($_GET['search']) ? '&search=' . htmlentities($_GET['search'], ENT_QUOTES) : ''?>">
							   Bank Reference
								<?php if ($order_by == 'hancock'): ?>
								<i class="fa-solid fa-arrow-<?=str_replace(array('ASC', 'DESC'), array('up', 'down'), $order_sort)?>-long fa-sm"></i>
								<?php endif; ?>
							</a>
						</td>	
							
						<td<?=$order_by=='description'?' class="active"':''?>>
							<a href="bills-browse.php?page=1&records_per_page=<?=$records_per_page?>&order_by=description&order_sort=<?=$order_sort == 'ASC' ? 'DESC' : 'ASC'?> <?=isset($_GET['search']) ? '&search=' . htmlentities($_GET['search'], ENT_QUOTES) : ''?>">
						Notes
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
			 <?php
			    $stmt = $budget_pdo->prepare('SELECT description FROM flags WHERE id=?');
                $stmt->execute([$result['flags_id']]);
                $flags = $stmt->fetchColumn(); ?>
					<tr>
					    <td class="flags_id"><?=$flags?></td> 
					    <td class="next_due_date"><?=date("m/d/y", strtotime($result['next_due_date'])?? '')?></td> 
					    <td class="next_due_amount right"><?=htmlspecialchars($result['next_due_amount']?? '', ENT_QUOTES)?></td>
                        <td class="bill"><?=htmlspecialchars($result['bill']?? '', ENT_QUOTES)?></td>
					    <td class="hancock"><?=htmlspecialchars($result['hancock']?? '', ENT_QUOTES)?></td>
					    <td class="description"><?=htmlspecialchars($result['description']?? '', ENT_QUOTES)?></td>
						<td class="actions">
						<a href="bills-view.php?id=<?=$result['id']?>"   class="edit"> <i class="fa-solid far fa-eye fa-xs"></i> </a>
						<a href="bills-edit.php?id=<?=$result['id']?>" class="edit"> <i class="fa-solid fa-pen fa-xs"></i></a>
						<a href="bills-delete.php?id=<?=$result['id']?>" class="trash"> <i class="fa-solid fa-xmark fa-xs"></i></a>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
 
			<div class="pagination">
				<?php if ($records_per_page != 'all'): ?>
				<?php if ($page > 1): ?>
				<a href="bills-browse.php?page=<?=$page-1?>&records_per_page=<?=$records_per_page?>&order_by=<?=$order_by?>&order_sort=<?=$order_sort?> <?=isset($_GET['search']) ? '&search=' . htmlentities($_GET['search'], ENT_QUOTES) : ''?>" class="prev">
					<i class="fa-solid fa-angle-left"></i> Prev
				</a>
				<?php endif; ?>
				<?php if ($page > 1): ?>
				<a href="bills-browse.php?page=1&records_per_page=<?=$records_per_page?>&order_by=<?=$order_by?>&order_sort=<?=$order_sort?> <?=isset($_GET['search']) ? '&search=' . htmlentities($_GET['search'], ENT_QUOTES) : ''?>">1</a>
				<?php endif; ?>
				<?php if ($page > 2): ?>
				<div class="dots">...</div>
				<?php if ($page == ceil($num_results/$records_per_page) && ceil($num_results/$records_per_page) > 3): ?>
				<a href="bills-browse.php?page=<?=$page-2?>&records_per_page=<?=$records_per_page?>&order_by=<?=$order_by?>&order_sort=<?=$order_sort?> <?=isset($_GET['search']) ? '&search=' . htmlentities($_GET['search'], ENT_QUOTES) : ''?>"><?=$page-2?></a>
				<?php endif; ?>
				<?php endif; ?>
				<?php if ($page-1 > 1): ?>
				<a href="bills-browse.php?page=<?=$page-1?>&records_per_page=<?=$records_per_page?>&order_by=<?=$order_by?>&order_sort=<?=$order_sort?> <?=isset($_GET['search']) ? '&search=' . htmlentities($_GET['search'], ENT_QUOTES) : ''?>"><?=$page-1?></a>
				<?php endif; ?>
				<a href="bills-browse.php?page=<?=$page?>&records_per_page=<?=$records_per_page?>&order_by=<?=$order_by?>&order_sort=<?=$order_sort?> <?=isset($_GET['search']) ? '&search=' . htmlentities($_GET['search'], ENT_QUOTES) : ''?>" class="selected"><?=$page?></a>
				<?php if ($page+1 < ceil($num_results/$records_per_page)): ?>
				<a href="bills-browse.php?page=<?=$page+1?>&records_per_page=<?=$records_per_page?>&order_by=<?=$order_by?>&order_sort=<?=$order_sort?> <?=isset($_GET['search']) ? '&search=' . htmlentities($_GET['search'], ENT_QUOTES) : ''?>"><?=$page+1?></a>
				<?php if ($page == 1 && $page+2 < ceil($num_results/$records_per_page)): ?>
				<a href="bills-browse.php?page=<?=$page+2?>&records_per_page=<?=$records_per_page?>&order_by=<?=$order_by?>&order_sort=<?=$order_sort?> <?=isset($_GET['search']) ? '&search=' . htmlentities($_GET['search'], ENT_QUOTES) : ''?>"><?=$page+2?></a>
				<?php endif; ?>
				<div class="dots">...</div>
				<?php endif; ?>
				<?php if ($page < ceil($num_results/$records_per_page)): ?>
				<a href="bills-browse.php?page=<?=ceil($num_results/$records_per_page)?>&records_per_page=<?=$records_per_page?>&order_by=<?=$order_by?>&order_sort=<?=$order_sort?> <?=isset($_GET['search']) ? '&search=' . htmlentities($_GET['search'], ENT_QUOTES) : ''?>"><?=ceil($num_results/$records_per_page)?></a>
				<?php endif; ?>
				<?php if ($records_per_page != 'all' && $page < ceil($num_results/$records_per_page)): ?>
				<a href="bills-browse.php?page=<?=$page+1?>&records_per_page=<?=$records_per_page?>&order_by=<?=$order_by?>&order_sort=<?=$order_sort?> <?=isset($_GET['search']) ? '&search=' . htmlentities($_GET['search'], ENT_QUOTES) : ''?>" class="next">
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