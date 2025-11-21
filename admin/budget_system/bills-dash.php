<?php
//Bills Dash 12/15/2025
include_once 'assets/includes/admin_config.php';
// Connect to MySQL database
$budget_pdo = pdo_connect_budget_db($db_host, $db_name7, $db_user, $db_pass);
$stmt = $budget_pdo->prepare('SELECT * FROM bills WHERE reporting_flag = 1 ORDER BY next_due_date ASC' );
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?=template_admin_header('Bills Tracking', 'budget', 'tracking')?>
<div class="content read">

	<div class="page-title">
		<i class="fa-regular fa-address-book fa-lg"></i>
		<div class="wrap">
			<h2>View Bills</h2>
			<p></p>
		</div>
	</div>
<a href="bills-create.php" class="btn">
        <svg class="icon-left" width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 80c0-17.7-14.3-32-32-32s-32 14.3-32 32V224H48c-17.7 0-32 14.3-32 32s14.3 32 32 32H192V432c0 17.7 14.3 32 32 32s32-14.3 32-32V288H400c17.7 0 32-14.3 32-32s-14.3-32-32-32H256V80z"/></svg>
       Create a New Bill
    </a>
	<form action="" method="get" class="crud-form">

		<div class="top">
		
		</div>

		<div class="table">
			<table>
				<thead>
					<tr>
					    
					    	<td>Due Date</td>
 						<td>Amount Due</td>
						
					
							<td>Bill</td>
							
						<td>Notes</td>
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
					    <td class="next_due_date"><?=date("m/d/y", strtotime($result['next_due_date'])?? '')?></td> 
					    <td  class="next_due_amount right" style='padding-right:10px;'><?=htmlspecialchars($result['next_due_amount']?? '', ENT_QUOTES)?></td>
                        <td class="bill"><?=htmlspecialchars($result['bill']?? '', ENT_QUOTES)?></td>
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
		</div>
	</form>
</div>
<style>
    .right{
        text-align: right;
    }
</style>
<?=template_admin_footer()?>