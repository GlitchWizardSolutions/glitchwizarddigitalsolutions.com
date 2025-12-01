<?php
include_once 'assets/includes/admin_config.php';
include_once 'assets/includes/budget_constants.php';
include_once 'assets/includes/budget_helpers.php';

// Connect to MySQL database
$budget_pdo = pdo_connect_budget_db($db_host, $db_name7, $db_user, $db_pass);
// Error message
$error_msg = ''; 
// Success message
$success_msg = NULL;

// Get transactions from csv_process table (ordered by date for display and processing)
$stmt = $budget_pdo->prepare('SELECT * FROM csv_process ORDER BY date ASC');
$stmt->execute();
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pre-load flags for display (avoid N+1 query problem)
$stmt = $budget_pdo->prepare('SELECT id, description FROM flags');
$stmt->execute();
$flags_lookup = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $flag) {
    $flags_lookup[$flag['id']] = $flag['description'];
}

//YOU MUST CLICK SUBMIT TO RUN THIS PROCESS
if (isset($_POST['submit'])) {
    // Validate we have transactions to process
    if (empty($transactions)) {
        $error_msg = 'No transactions found in csv_process table. Please complete Step 2 first.';
    } else {
        try {
            // Start database transaction for atomicity
            $budget_pdo->beginTransaction();
            
            $processed_count = 0;
            $allowance_updated = false;
            
            //LOOP THROUGH CSV_PROCESS TABLE 
            foreach ($transactions as $row) {
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
                
                // Get current budget balance for this transaction
                $stmt = $budget_pdo->prepare('SELECT balance FROM budget WHERE id = ?');
                $stmt->execute([$budget_id]);
                $prior_balance              = $stmt->fetchColumn();
                $updated_balance            = $prior_balance + $amount;

                // Check if this is the monthly allowance deposit
                if ($description == DESC_ALLOWANCE_DEPOSIT && $credits == MONTHLY_ALLOWANCE) {
                    $stmt = $budget_pdo->prepare('SELECT id, balance, amount FROM budget');
                    $stmt->execute();
                    $budgets = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($budgets as $budget_row) { 
                        $budget_balance = $budget_row['balance'];
                        $amount_to_add = $budget_row['amount'];
                        $adjusted_balance = $budget_balance + $amount_to_add;
                        $budget_id_to_update = $budget_row['id'];
                        
                        $stmt = $budget_pdo->prepare('UPDATE budget SET balance = ? WHERE id = ?');
                        $stmt->execute([$adjusted_balance, $budget_id_to_update]); 
                    }
                    
                    $updated_balance        = 0;
                    $prior_balance          = 0;
                    $amount                 = 0;
                    $budget_updated         = "Yes";
                    $previously_processed   = 'Yes';
                }


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
            
                $processed_count++;
            }//END FOR EACH TRANSACTIONS AS ROW
            
            // Commit transaction - all or nothing
            $budget_pdo->commit();
            
            // Clear POST to prevent re-submission
            $_POST = array();
            
            $success_msg = "Successfully processed {$processed_count} transaction(s) to hancock and bills tables.";
            if ($allowance_updated) {
                $success_msg .= " Monthly allowance of $" . number_format(MONTHLY_ALLOWANCE, 2) . " distributed to budget envelopes.";
            }
            $error_msg = 'Processing complete. Remember to clear csv_process table when done.';
            
        } catch (Exception $e) {
            // Rollback on error
            if ($budget_pdo->inTransaction()) {
                $budget_pdo->rollBack();
            }
            $error_msg = 'Processing failed: ' . $e->getMessage();
            error_log('Budget Step 3 processing error: ' . $e->getMessage());
        }
    }
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
					<?php if (empty($transactions)): ?>
					<tr>
						<td colspan="10" style="text-align:center;">There are no records.</td>
					</tr>
					<?php endif; ?>
					<?php foreach ($transactions as $result): ?>
                    <?php
                        // Use pre-loaded flags lookup instead of query per row
                        $flag_description = $flags_lookup[$result['flags_id']] ?? 'Unknown';
                    ?>
					<tr>
						<td class="date" style="padding-right: 8px;"><?=date("m/d", strtotime($result['date'])?? '')?></td> 
					    <td class="description"><?=htmlspecialchars($result['description']?? '', ENT_QUOTES)?></td>
					    <td class="flag"><?=htmlspecialchars($flag_description, ENT_QUOTES)?></td>
					    <td class="comment"><?=substr($result['comment']??'', 0, 16);?></td>
					    <td class="amount right" style="padding-right: 8px;"><?=$result['amount']?? '' ?></td>
					    <td class="prior_balance right" style="padding-left: 8px;"><?=$result['prior_balance']?? '' ?></td>
					   	<td class="budget"><?=substr($result['budget']??'', 0, 16);?></td>
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
 <a href='<?=budget_link_path?>instructions-p2-5-reconcile.php' style='background:grey; color:white' class='btn btn-sm'><<< BACK: Reconcile <<<<</a>&nbsp;&nbsp;        
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