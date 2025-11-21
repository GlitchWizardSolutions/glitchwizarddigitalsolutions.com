<?php
include_once 'assets/includes/admin_config.php';
// Connect to MySQL database
$budget_pdo = pdo_connect_budget_db($db_host, $db_name7, $db_user, $db_pass);
// Error message
$error_msg = ''; 
// Success message
$success_msg = '';
$value='';
$selected='';
$match='';

$stmt =$budget_pdo->prepare('SELECT * FROM bills');
$stmt->execute();
$bills = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $budget_pdo->prepare('SELECT * FROM bank_reference');
$stmt->execute();
$bank_reference = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $budget_pdo->prepare('SELECT * FROM budget');
$stmt->execute();
$budget = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $budget_pdo->prepare('SELECT * FROM flags');
$stmt->execute();
$flags = $stmt->fetchAll(PDO::FETCH_ASSOC);

 
if (isset($_POST['bill'])) {
    // Validate form data
    $data = [
             'bill' => $_POST['bill'],
             'description' => $_POST['description'],
             'budget_id' => $_POST['budget_id'],
             'reference_id' => $_POST['reference_id'],
           
             'flags_id' => $_POST['flags_id'],
             'remarks' => $_POST['remarks'],
             'autopay_flag' => $_POST['autopay_flag'],
         
             'next_due_date' => $_POST['next_due_date'],
             'next_due_amount' => $_POST['next_due_amount'],
             'last_paid_date' => date('Y-m-d H:i:s', strtotime($_POST['last_paid_date'])),
             'last_paid_amount' => $_POST['last_paid_amount']
    ];
   
    if (empty($data['bill']) || empty($data['description']) || empty($data['budget_id']) || empty($data['reference_id']
        || empty($data['flags_id']) || empty($data['autopay_flag'])) 
        ) {
        $error_msg = 'Please fill out all required fields!';
    } 
    // If no validation errors, proceed to insert the record(s) in the database
    if (!$error_msg) {
  
        // Insert the records
         $stmt = $budget_pdo->prepare('INSERT INTO `bills`(bill, description, budget_id, reference_id, flags_id, remarks, autopay_flag, next_due_date, next_due_amount,  last_paid_date, last_paid_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )');
         $stmt->execute([$data['bill'], $data['description'], $data['budget_id'], $data['reference_id'], $data['flags_id'], $data['remarks'], $data['autopay_flag'], $data['next_due_date'], $data['next_due_amount'], $data['last_paid_date'], $data['last_paid_amount']]);
       // Output message
        $success_msg = 'Created Successfully!';
    }
}   

// Get the page via GET request (URL param: page), if non exists default the page to 1
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
// Number of records to show on each page
$records_per_page = isset($_GET['records_per_page']) && (is_numeric($_GET['records_per_page']) || $_GET['records_per_page'] == 'all') ? $_GET['records_per_page'] : $default_records_per_page;
// Column list
$columns = ['bill', 'next_due_date', 'next_due_amount' , 'last_paid_date' , 'last_paid_amount' ];
// Order by which column if specified (default to id)
$order_by = isset($_GET['order_by']) && in_array($_GET['order_by'], $columns) ? $_GET['order_by'] : 'bill';
// Sort by ascending or descending if specified (default to ASC)
$order_sort = isset($_GET['order_sort']) && $_GET['order_sort'] == 'DESC' ? 'DESC' : 'DESC';
$where_sql = '';
// Add search to SQL query (if search term exists)
if (isset($_GET['search']) && !empty($_GET['search'])) {
	$where_sql .= ($where_sql ? ' AND ' : ' WHERE ') .  'bill LIKE :search_query';
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
<?=template_admin_header('Bills', 'budget', 'create')?>
<div class="content update">

       <div class="page-title">
		<i class="fa-solid fa-user fa-lg"></i>
		<div class="wrap">
			<h2>Create New Bill</h2>
			<p>Create a bill in the database, fill in the form below and submit.</p>
		</div>
 </div>
    <form action="" method="post" class="crud-form" style='background:#F8F6F6'>
   	<div class="table">
	  <table>
	<tbody>
	    <tr>
    <td>
      <?php if ($error_msg): ?>
        <p class="msg-error"><?=$error_msg?></p>
        <?php endif; ?>

        <?php if ($success_msg): ?>
        <p class="msg-success"><?=$success_msg?></p>
        <?php endif; ?>
</td>
</tr>
<tr>						
<td>
           <div class="form-control" style='width:80%'>
                    <label for="bill">Bill</label>
                    <input type="text" list="billrow" name= "bill" id="bill" required>
                    <option value=''></option>
                    <datalist id="billrow">
                        <?php foreach($bills as $row) :?>
                            <?php $value=$row['bill'];?>
                                 <option value='<?=$value;?>'><?=$row['bill']?></option>
                        <?php endforeach ?>
                     </datalist>
                </div>
<td>
    
      	    <div class="form-control" style='width:80%'>
                <label for="description">Description</label>
                <input type="text" name="description" id="description" value="" placeholder="" required>
             </div>
</td>   
   
                
</tr>
<tr>
                    <td>
     <div class="form-control" style='width:80%'>
                <label for="reference_id">Bank Reference</label>
                <select name="reference_id" id="reference_id" required>
                     <option value='11' selected>See Description</option>
            <?php foreach($bank_reference as $row) :?>
             <?php $value=$row['id'];?>
                   <option value='<?=$value;?>'><?=$row['bank_reference']?></option>
            <?php endforeach ?>
                </select>
            </div>
</td>
                   <td>
     <div class="form-control" style='width:80%'>
                <label for="budget_id">Budget</label>
                <select name="budget_id" id="budget_id" required>
                     <option value='23' selected>Remaining Balance</option></option>
            <?php foreach($budget as $row) :?>
             <?php $value=$row['id'];?>
                   <option value='<?=$value;?>'><?=$row['budget']?>&nbsp; [<?=$row['balance']?> ]</option>
            <?php endforeach ?>
                </select>
            </div>
</td>
  </tr>

<tr>
    <td>
              <div class="form-control">
                <label for="last_paid_date">Last Paid Date</label>
                <input type="datetime-local" name="last_paid_date" id="last_paid_date" value="<?=date('Y-m-d\TH:i')?>" required>
              </div>
            
</td>
<td>
    
      	    <div class="form-control" style='width:25%'>
                <label for="last_paid_amount">Last Paid $ Amount</label>
                <input  class="right" type="number" name="last_paid_amount" id="last_paid_amount" value="0.00" placeholder="" required>
             </div>
</td>

</tr>
<tr> 
<td>
            <div class="form-control">
                <label for="next_due_date">Next Due Date</label>
               
               <input type="datetime-local" name="next_due_date" id="next_due_date" placeholder="Next Due Date" value="<?=date('Y-m-d\TH:i')?>" required>
            </div>
</td>
<td>
      	    <div class="form-control" style='width:25%'>
                <label for="next_due_amount">Next Due Amount</label>
                <input type="number" class="right" name="next_due_amount" id="next_due_amount" value="0.00" placeholder="">
             </div>
    
</td>
</tr>

<tr>
<td>             <div class="form-control" style='width:25%'>
                <label for="autopay_flag">Auto Pay?</label>
                <select name="autopay_flag" id="autopay_flag" required>
                   <option value='N' selected='selected'>No</option>
                   <option value="Y">Yes</option>
                 
                </select>
            </div>
</td>
<td>
             <div class="form-control" style='width:80%'>
             <label for="flags_id">Flag</label>
             <select name="flags_id" id="flags_id" required>
                  <option value='6' selected='selected'>N/A</option>
                 <?php foreach($flags as $row) :?>
                
                <?php $value=$row['id'];?>
                   <option value='<?=$value;?>'><?=$row['description']?></option>
                 <?php endforeach ?>
             </select>
            </div> 
</td>


</tr>

<tr>
    <td>
      <?php if ($error_msg): ?>
        <p class="msg-error"><?=$error_msg?></p>
        <?php endif; ?>

        <?php if ($success_msg): ?>
        <p class="msg-success"><?=$success_msg?></p>
        <?php endif; ?>
</td>
</tr>



<tr>
    <td> <button type="submit" name="submit" class="btn">Save Record</button></td>
    <td>
    
      	    <div class="form-control" style='width:80%'>
                <label for="remarks">Note</label>
                <input type="text" name="remarks" id="remarks" value="" placeholder="">
             </div>
</td> 
</tr>
 
  </tbody>
 </table>
</div>
    </form>
 </div>
<div class="content read">

	<div class="page-title">
		<i class="fa-regular fa-address-book fa-lg"></i>
		<div class="wrap">
			<h2>Browse Bills Table</h2>
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
 						<td<?=$order_by=='bill'?' class="active"':''?>>
							<a href="bills_create.php?page=1&records_per_page=<?=$records_per_page?>&order_by=bill&order_sort=<?=$order_sort == 'ASC' ? 'DESC' : 'ASC'?> <?=isset($_GET['search']) ? '&search=' . htmlentities($_GET['search'], ENT_QUOTES) : ''?>">
							 Bill
								<?php if ($order_by == 'bill'): ?>
								<i class="fa-solid fa-arrow-<?=str_replace(array('ASC', 'DESC'), array('up', 'down'), $order_sort)?>-long fa-sm"></i>
								<?php endif; ?>
							</a>
						</td>
							<td<?=$order_by=='next_due_date'?' class="active"':''?>>
							<a href="bills_create.php?page=1&records_per_page=<?=$records_per_page?>&order_by=next_due_date&order_sort=<?=$order_sort == 'ASC' ? 'DESC' : 'ASC'?> <?=isset($_GET['search']) ? '&search=' . htmlentities($_GET['search'], ENT_QUOTES) : ''?>">
								Next Due
								<?php if ($order_by == 'next_due_date'): ?>
								<i class="fa-solid fa-arrow-<?=str_replace(array('ASC', 'DESC'), array('up', 'down'), $order_sort)?>-long fa-sm"></i>
								<?php endif; ?>
							</a>
						</td>	
								<td<?=$order_by=='next_due_amount'?' class="active"':''?>>
							<a href="bills_create.php?page=1&records_per_page=<?=$records_per_page?>&order_by=next_due_amount&order_sort=<?=$order_sort == 'ASC' ? 'DESC' : 'ASC'?> <?=isset($_GET['search']) ? '&search=' . htmlentities($_GET['search'], ENT_QUOTES) : ''?>">
							   Next Due
								<?php if ($order_by == 'next_due_amount'): ?>
								<i class="fa-solid fa-arrow-<?=str_replace(array('ASC', 'DESC'), array('up', 'down'), $order_sort)?>-long fa-sm"></i>
								<?php endif; ?>
							</a>
						</td>	
							<td<?=$order_by=='last_paid_date'?' class="active"':''?>>
							<a href="bills_create.php?page=1&records_per_page=<?=$records_per_page?>&order_by=last_paid_date&order_sort=<?=$order_sort == 'ASC' ? 'DESC' : 'ASC'?> <?=isset($_GET['search']) ? '&search=' . htmlentities($_GET['search'], ENT_QUOTES) : ''?>">
								Next Due
								<?php if ($order_by == 'last_paid_date'): ?>
								<i class="fa-solid fa-arrow-<?=str_replace(array('ASC', 'DESC'), array('up', 'down'), $order_sort)?>-long fa-sm"></i>
								<?php endif; ?>
							</a>
						</td>	
								<td<?=$order_by=='last_paid_amount'?' class="active"':''?>>
							<a href="bills_create.php?page=1&records_per_page=<?=$records_per_page?>&order_by=last_paid_amount&order_sort=<?=$order_sort == 'ASC' ? 'DESC' : 'ASC'?> <?=isset($_GET['search']) ? '&search=' . htmlentities($_GET['search'], ENT_QUOTES) : ''?>">
							   Next Due
								<?php if ($order_by == 'last_paid_amount'): ?>
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
					     
						<td class="bill"><?=htmlspecialchars($result['bill']?? '', ENT_QUOTES)?></td>
						<td class="next_due_date"><?=date("m/d/y", strtotime($result['next_due_date'])?? '')?></td> 
						<td class="next_due_amount right"><?=htmlspecialchars($result['next_due_amount']?? '', ENT_QUOTES)?></td>
					 	<td class="last_paid_date"><?=date("m/d/y", strtotime($result['last_paid_date'])?? '')?></td> 
						<td class="last_paid_amount right"><?=htmlspecialchars($result['last_paid_amount']?? '', ENT_QUOTES)?></td>
					
					
						<td class="actions">
						<a href="bills_view.php?id=<?=$result['id']?>"   class="edit"> <i class="fa-solid far fa-eye fa-xs"></i> </a>
						<a href="bills_edit.php?id=<?=$result['id']?>" class="edit"> <i class="fa-solid fa-pen fa-xs"></i></a>
						<a href="bills_delete.php?id=<?=$result['id']?>" class="trash"> <i class="fa-solid fa-xmark fa-xs"></i></a>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
 
			<div class="pagination">
				<?php if ($records_per_page != 'all'): ?>
				<?php if ($page > 1): ?>
				<a href="bills_create.php?page=<?=$page-1?>&records_per_page=<?=$records_per_page?>&order_by=<?=$order_by?>&order_sort=<?=$order_sort?> <?=isset($_GET['search']) ? '&search=' . htmlentities($_GET['search'], ENT_QUOTES) : ''?>" class="prev">
					<i class="fa-solid fa-angle-left"></i> Prev
				</a>
				<?php endif; ?>
				<?php if ($page > 1): ?>
				<a href="bills_create.php?page=1&records_per_page=<?=$records_per_page?>&order_by=<?=$order_by?>&order_sort=<?=$order_sort?> <?=isset($_GET['search']) ? '&search=' . htmlentities($_GET['search'], ENT_QUOTES) : ''?>">1</a>
				<?php endif; ?>
				<?php if ($page > 2): ?>
				<div class="dots">...</div>
				<?php if ($page == ceil($num_results/$records_per_page) && ceil($num_results/$records_per_page) > 3): ?>
				<a href="bills_create.php?page=<?=$page-2?>&records_per_page=<?=$records_per_page?>&order_by=<?=$order_by?>&order_sort=<?=$order_sort?> <?=isset($_GET['search']) ? '&search=' . htmlentities($_GET['search'], ENT_QUOTES) : ''?>"><?=$page-2?></a>
				<?php endif; ?>
				<?php endif; ?>
				<?php if ($page-1 > 1): ?>
				<a href="bills_create.php?page=<?=$page-1?>&records_per_page=<?=$records_per_page?>&order_by=<?=$order_by?>&order_sort=<?=$order_sort?> <?=isset($_GET['search']) ? '&search=' . htmlentities($_GET['search'], ENT_QUOTES) : ''?>"><?=$page-1?></a>
				<?php endif; ?>
				<a href="bills_create.php?page=<?=$page?>&records_per_page=<?=$records_per_page?>&order_by=<?=$order_by?>&order_sort=<?=$order_sort?> <?=isset($_GET['search']) ? '&search=' . htmlentities($_GET['search'], ENT_QUOTES) : ''?>" class="selected"><?=$page?></a>
				<?php if ($page+1 < ceil($num_results/$records_per_page)): ?>
				<a href="bills_create.php?page=<?=$page+1?>&records_per_page=<?=$records_per_page?>&order_by=<?=$order_by?>&order_sort=<?=$order_sort?> <?=isset($_GET['search']) ? '&search=' . htmlentities($_GET['search'], ENT_QUOTES) : ''?>"><?=$page+1?></a>
				<?php if ($page == 1 && $page+2 < ceil($num_results/$records_per_page)): ?>
				<a href="bills_create.php?page=<?=$page+2?>&records_per_page=<?=$records_per_page?>&order_by=<?=$order_by?>&order_sort=<?=$order_sort?> <?=isset($_GET['search']) ? '&search=' . htmlentities($_GET['search'], ENT_QUOTES) : ''?>"><?=$page+2?></a>
				<?php endif; ?>
				<div class="dots">...</div>
				<?php endif; ?>
				<?php if ($page < ceil($num_results/$records_per_page)): ?>
				<a href="bills_create.php?page=<?=ceil($num_results/$records_per_page)?>&records_per_page=<?=$records_per_page?>&order_by=<?=$order_by?>&order_sort=<?=$order_sort?> <?=isset($_GET['search']) ? '&search=' . htmlentities($_GET['search'], ENT_QUOTES) : ''?>"><?=ceil($num_results/$records_per_page)?></a>
				<?php endif; ?>
				<?php if ($records_per_page != 'all' && $page < ceil($num_results/$records_per_page)): ?>
				<a href="bills_create.php?page=<?=$page+1?>&records_per_page=<?=$records_per_page?>&order_by=<?=$order_by?>&order_sort=<?=$order_sort?> <?=isset($_GET['search']) ? '&search=' . htmlentities($_GET['search'], ENT_QUOTES) : ''?>" class="next">
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