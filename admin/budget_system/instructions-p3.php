<?php
include_once 'assets/includes/admin_config.php';
// Connect to MySQL database
$budget_pdo = pdo_connect_budget_db($db_host, $db_name7, $db_user, $db_pass);
// Error message
$error_msg = ''; 
// Success message
$success_msg = NULL;
$stmt =$budget_pdo->prepare('SELECT * FROM csv_process ORDER BY date DESC');
$stmt->execute();
$csv_processs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt =$budget_pdo->prepare('SELECT * FROM bills');
$stmt->execute();
$bills = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $budget_pdo->prepare('SELECT * FROM budget');
$stmt->execute();
$budget = $stmt->fetchAll(PDO::FETCH_ASSOC);

$allowance_update='No';

//CSV_UPLOAD TABLE
//Get the transactions from the csv table
$stmt = $budget_pdo->prepare('SELECT * FROM csv_process');
$stmt->execute();
$transactions  = $stmt->fetchAll(PDO::FETCH_ASSOC);

//YOU MUST CLICK SUBMIT TO RUN THIS PROCESS
if(isset($_POST['submit']) ){
//LOOP THROUGH CSV_PROCESS TABLE 
foreach($transactions as $row){
$previously_processed       = 'No';    
$date                       = $row['date']; 
$check_number               = $row['check_number'];
$transaction_type           = $row['transaction_type'];
$description                = $row['description'];
$debits                     = $row['debits'];
$credits                    = $row['credits'];
$edited                     = $row['edited'];  
$amount                     = $row['amount'];
$reimbursement              = $row['reimbursement'];
$comment                    = $row['comment'];
$flags_id                   = $row['flags_id'];
$bill_id                    = $row['bill_id'];
$notes                      = $row['notes'];
$next_due_amount            = $row['next_due_amount'];
$next_due_date              = $row['next_due_date'];
$last_paid_amount           = $row['last_paid_amount'];
$last_paid_date             = $row['last_paid_date'];
$frequency                  = $row['frequency'];
$reconciled                 = "New"; 
$bills_table_updated        = 'Yes';
$hancock_table_match        = $row['hancock_table_match'];
$budget_id                  = $row['budget_id'];
$budget_updated             = $row['budget_updated'];
$rollback_next_due_date     = $row['rollback_next_due_date']; 
$rollback_last_paid_date    = $row['rollback_last_paid_date'];   
$rollback_next_due_amount   = $row['rollback_next_due_amount'];   
$rollback_last_paid_amount  = $row['rollback_last_paid_amount']; 
//This has to be a new pull due to records processed file using the same prior balance, otherwise.
$stmt = $budget_pdo->prepare('SELECT * FROM budget WHERE id = ?');
$stmt->execute([$budget_id]);
$budget_reference           = $stmt->fetch(PDO::FETCH_ASSOC);
$prior_balance              = $budget_reference['balance'];
$updated_balance            = $prior_balance + $amount;

if($description=='OLB XFER FROM 9'){
//IF THE TRANSACTION IS EXACTLY 3500.00:
if($row['credits'] == 3500.00) {
   $allowance_update        = 'Yes'; 
//process the monthly allowance
         $stmt = $budget_pdo->prepare('SELECT * FROM budget');
         $stmt->execute();//ALL ENVELOPES
         $budgets = $stmt->fetchAll(PDO::FETCH_ASSOC);
         foreach($budgets as $budget_row){ 
         $budget_balance=$budget_row['balance'];
         $amount_to_add = $budget_row['amount'];
         $adjusted_balance=$budget_balance + $amount_to_add;
         $budget_id=$budget_row['id'];
         $stmt = $budget_pdo->prepare('UPDATE budget 
                 SET
                 balance   = ?
                 WHERE 
                 id        = ?');
                   $stmt->execute([
                   $adjusted_balance,
                   $budget_id ]); 
            }
        $updated_balance        = 0;
        $prior_balance          = 0;
        $amount                 = 0;
        $budget_updated         = "Yes";
        $previously_processed    = 'Yes';
    }//$row['credits'] == 3500.00
}//if($description=='OLB XFER FROM 9')


// Update the bill in bills table to set dates.
   $stmt = $budget_pdo->prepare('UPDATE bills 
    SET
     next_due_date             = ?,
     next_due_amount           = ?, 
     last_paid_date            = ?,
     last_paid_amount          = ?,        
     rollback_next_due_date    = ?, 
     rollback_last_paid_date   = ?,  
     rollback_next_due_amount  = ?,   
     rollback_last_paid_amount = ?
     WHERE id        = ?');
       $stmt->execute([
          $next_due_date,
          $amount,
          $date,
          $amount,
          $rollback_next_due_date,
          $rollback_last_paid_date,
          $rollback_next_due_amount,   
          $rollback_last_paid_amount,
          $bill_id]); 
       $bills_table_updated    = "Yes";

//Update balance for budget id
         $stmt = $budget_pdo->prepare('UPDATE budget 
            SET
            balance=?
            WHERE id = ?');
         $stmt->execute([ $updated_balance, $budget_id]);

        // Insert the transaction into the database table 'update_results'
         $stmt = $budget_pdo->prepare('INSERT INTO update_results 
         ( 
         bills_table_updated,
         date,
         check_number, 
         transaction_type, 
         description, 
         debits, 
         credits, 
         reconciled, 
         comment, 
         bill_id, 
         budget_id,
         flags_id, 
         reimbursement, 
         notes,
         hancock_table_match,
         budget_updated,
         updated_balance,
         prior_balance,
         edited,
         last_paid_amount,
         last_paid_date,
         next_due_amount,
         next_due_date
         ) 
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
         $stmt->execute([
            $bills_table_updated,
            $date, 
            $check_number, 
            $transaction_type, 
            $description, 
            $debits, 
            $credits, 
            $reconciled, 
            $comment,
            $bill_id,
            $budget_id,
            $flags_id,
            $reimbursement,
            $notes,
            $hancock_table_match,
            $budget_updated,
            $updated_balance,
            $prior_balance,
            $edited,
            $last_paid_amount,
            $last_paid_date,
            $next_due_amount,
            $next_due_date]);
  
        // Insert the transaction into the database table 'hancock'
         $stmt = $budget_pdo->prepare('INSERT INTO hancock 
         ( 
         date,
         check_number, 
         transaction_type, 
         description, 
         debits, 
         credits, 
         reconciled, 
         comment, 
         bill_id, 
         budget_id,
         flags_id, 
         reimbursement, 
         notes  
         ) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
         $stmt->execute([
            $date, 
            $check_number, 
            $transaction_type, 
            $description, 
            $debits, 
            $credits, 
            $reconciled, 
            $comment,
            $bill_id,
            $budget_id,
            $flags_id,
            $reimbursement,
            $notes]);
       // Output message
        $_POST = array();
        $success_msg = 'Created Successfully!';
        $error_msg= 'Remember to EMPTY the table when you are done.';
   }//END FOR EACH TRANSACTIONS AS ROW
}
?>
<?=template_admin_header('Upload CSV file', 'budget', 'process')?>
 
<div class="content read">
	<div class="page-title">
		<i class="fa-regular fa-address-book fa-lg"></i>
		<div class="wrap">
			<h2>Instructions Page 3</h2>
			<p>STEP 3 - Validate and Edit the CSV Process Table Transactions</p>
		</div>
	</div>
	
<h3><strong>1. Inspect the processed records, below.</strong></h3><br>
		<div class="table" style='width:95%'>
			<table>
				<thead>
					<tr>
 						    <td> Date           </td>
							<td> Reference      </td>
							<td> Flag           </td>
							<td> Comment        </td>
							<td> Amt.           </td>    
							<td> Prior      	</td>	
						    <td> Budget  		</td>
						    <td> Bill 			</td>
						    <td> Edit   		</td>
						    <td> Actions        </td>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($csv_processs)): ?>
					<tr>
						<td colspan="10" style="text-align:center;">There are no records.</td>
					</tr>
					<?php endif; ?>
					<?php foreach ($csv_processs as $result): ?>
                    <?php		            
                        $stmt = $budget_pdo->prepare('SELECT description FROM flags WHERE id = ?');
                        $stmt->execute([$result['flags_id']]);
                        $flags = $stmt->fetch(PDO::FETCH_ASSOC);?>
					<tr>
						<td class="date"><?=date("m/d", strtotime($result['date'])?? '')?></td> 
					    <td class="description"><?=htmlspecialchars($result['description']?? '', ENT_QUOTES)?></td>
					    <td class="flag"><?=htmlspecialchars($flags['description']?? '', ENT_QUOTES)?></td>
					    <td class="comment"><?=substr($result['comment']??'', 0, 16);?></td>
					    <td class="amount right"><?=$result['amount']?? '' ?></td>
					    <td class="prior_balance right"><?=$result['prior_balance']?? '' ?>&nbsp;</td>
					   	<td class="budget">&nbsp;<?=substr($result['budget']??'', 0, 16);?></td>
					   	<td class="bill"><?=substr($result['bill']??'', 0, 16);?></td>
					   	<td class="edited"><?=htmlspecialchars($result['edited']?? '', ENT_QUOTES)?></td>
					 <td class="actions" style="text-align: center;">
                        <div class="table-dropdown">
                            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M8 256a56 56 0 1 1 112 0A56 56 0 1 1 8 256zm160 0a56 56 0 1 1 112 0 56 56 0 1 1 -112 0zm216-56a56 56 0 1 1 0 112 56 56 0 1 1 0-112z"/></svg>
                            <div class="table-dropdown-items">
                                <a href="csv-process-edit.php?id=<?=$result['id']?>" style='color:orange'>
                                    <span class="icon">
                                     <i class="fa-regular fa-pen-to-square fa-xs"></i>
                                    </span>
                                    Edit
                                </a>
                                <a class="red" href="csv-process-delete.php?id=<?=$result['id']?> onclick="return confirm('Are you sure you want to delete this account?')">
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
				</tbody>
			</table>
		</div>
<br><h3><strong>2. The red button below updates the database (transactions, budget balances, and due dates).</strong></h3><br>	

    <form action="" method="post" class="crud-form">
      <?php if ($error_msg): ?>
        <p class="msg-error"><?=$error_msg?></p>
        <?php endif; ?>

        <?php if ($success_msg): ?>
        <p class="msg-success"><?=$success_msg?></p>
        <?php endif; ?>
 <a href='<?=budget_link_path?>instructions-p2.php' style='background:grey; color:white' class='btn btn-sm'><<< BACK <<<<</a>&nbsp;&nbsp;        
 <button type="submit" name="submit" class="btn" style='background:red'>Start the Process!</button>&nbsp;&nbsp; 
 <a href='<?=budget_link_path?>instructions-p4.php' style='background:yellow; color:black' class='btn btn-sm'>>>> NEXT >>></a> 
    </form>
</div>


<style>
    .right{
        text-align: right;
    }
</style>
<?=template_admin_footer()?>