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

// Get transactions from csv_upload table (ordered by date for display and processing)
$stmt = $budget_pdo->prepare('SELECT * FROM csv_upload ORDER BY date ASC');
$stmt->execute();
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

//YOU MUST CLICK SUBMIT TO RUN THIS PROCESS

if (isset($_POST['submit'])) {
    // Validate we have transactions to process
    if (empty($transactions)) {
        $error_msg = 'No transactions found in csv_upload table. Please upload a CSV file first.';
    } else {
        try {
            // Start database transaction for atomicity
            $budget_pdo->beginTransaction();
            
            // Clear csv_process table before processing new transactions
            // Note: Using DELETE instead of TRUNCATE to preserve transaction
            $budget_pdo->exec('DELETE FROM csv_process');
            
            // OPTIMIZATION: Load all budget balances once instead of querying in loop
            $budget_balances = get_all_budget_balances($budget_pdo);
            
            $processed_count = 0;
            
            //LOOP THROUGH CSV_UPLOAD TABLE 
            foreach ($transactions as $row) {
//SETS DEFAULT TRANSACTION VALUES TO REMAINING BALANCE.
//for transactions not found in bills or in hancock table.
$flags_id = FLAG_LEFT_TO_SPEND;
$budget_id = BUDGET_NONE;
$budget='None';
$bill_id = BILL_NONE; //BLANK DESCRIPTION
$bill='None';
$description='';
$autopay_flag='N';
$updated_balance=0;
$allowance_update='No';
$reconciled='New';
$reimbursement='';
$comment= 'No matching bill found';
$notes='Automagically Updated';
$hancock_table_match='No';
$hancock='';
$budget_updated='No';
$prior_balance=0;
$bills_table_updated='N';
$bills_table_id=0;
$budget_name='';    
$edited='No';
$frequency=0;
$next_due_date=null;    
$date=$row['date'];
$rollback_next_due_date=$date;
$rollback_next_due_amount=0;
$rollback_last_paid_date=$date;
$rollback_last_paid_amount=0;
$date_formatted= date('Y-m-d', strtotime($date));
$day = date('d', strtotime($date));
$month = date('m', strtotime($date));
$year = date('Y', strtotime($date));
$check_number=$row['check_number'];
$transaction_type=$row['transaction_type'];
//LIMITS THE DESCRIPTION TO ELIMINATE THE DATES COMING IN.
$description= substr($row['description']??'', 0, DESC_MATCH_LENGTH);
$reference=$description;
$hancock=$description;
$debits=$row['debits'];
$credits=$row['credits'];
//This gets a value, whether it's a credit or a debit, that we can use.
$amount=$row['credits']+$row['debits'];
$next_due_amount=$amount;
$last_paid_amount=$amount;
$last_paid_date=$date;
//CHECK TO SEE IF THE DESCRIPTION HAS A MATCH.
$stmt = $budget_pdo->prepare('SELECT * FROM bills WHERE hancock = ? limit 1');
$stmt->execute([$description]);
$bill_reference = $stmt->fetch(PDO::FETCH_ASSOC);
   if($bill_reference) {
                $rollback_next_due_date=$bill_reference['next_due_date'];
                $rollback_last_paid_date=$bill_reference['last_paid_date'];
                $rollback_next_due_amount=$bill_reference['next_due_amount'];
                $rollback_last_paid_amount=$bill_reference['last_paid_amount'];
                $bill= $bill_reference['bill'];
                $comment= $bill_reference['bill'];
                $flags_id= $bill_reference['flags_id'];
                $reconciled='No';
                $frequency=$bill_reference['frequency'];
                $bill_id=$bill_reference['id'];
                $budget_id=$bill_reference['budget_id'];
                $notes='Automagically Updated by bill id';
                //CAPTURE POTENTIAL UPDATES TO THE BILLS TABLE, IF ANY
                $bills_table_updated='M';
                $bills_table_id=$bill_reference['id'];
                
                // Use helper function for date calculation
                $next_due_date = calculate_next_due_date($date, $frequency, $bill_reference['next_due_date']);
}else{
        $stmt = $budget_pdo->prepare('SELECT * FROM hancock WHERE description = ? limit 1');
        $stmt->execute([$description]);
        $bank_reference = $stmt->fetch(PDO::FETCH_ASSOC);
        if($bank_reference) {
                $comment= $bank_reference['comment'];
                $flags_id= $bank_reference['flags_id'];
                $reconciled='No';
                $bill_id=$bank_reference['bill_id'];
                $budget_id=$bank_reference['budget_id'];
                $notes='Automagically Updated by hancock.description';
                $hancock_table_match='Y';
                $stmt = $budget_pdo->prepare('SELECT * FROM bills WHERE id = ?');
                $stmt->execute([$bank_reference['bill_id']]);
                $bill_reference = $stmt->fetch(PDO::FETCH_ASSOC);
                $frequency=$bill_reference['frequency'];
                $bill= $bill_reference['bill'];
                $rollback_next_due_date=$bill_reference['next_due_date'];
                $rollback_last_paid_date=$bill_reference['last_paid_date'];
                $rollback_next_due_amount=$bill_reference['next_due_amount'];
                $rollback_last_paid_amount=$bill_reference['last_paid_amount'];
                //CAPTURE POTENTIAL UPDATES TO THE BILLS TABLE, IF ANY
                $bills_table_updated='M';
                $bills_table_id=$bank_reference['bill_id'];
                
                // Use helper function for date calculation
                $next_due_date = calculate_next_due_date($date, $frequency, $bill_reference['next_due_date']);
        }else{ 
                //THIS IS FOR ALL TRANSACTIONS NOT FOUND IN BILLS OR HANCOCK TABLES.
                //FORCES BILL #25 (BILL_NONE constant)
                $stmt = $budget_pdo->prepare('SELECT * FROM bills WHERE id = ?');
                $stmt->execute([BILL_NONE]);
                $bill_reference = $stmt->fetch(PDO::FETCH_ASSOC);
                $bill= $bill_reference['bill'];
                $budget_id=$bill_reference['budget_id'];
                $next_due_amount=$bill_reference['last_paid_amount'];
                $next_due_date=$bill_reference['last_paid_date'];
                $frequency=$bill_reference['frequency'];
                //CAPTURE UPDATES TO THE BILLS TABLE, IF ANY
                $bills_table_updated='M';
                $bills_table_id=$bill_reference['id'];
                $frequency=0;
                $next_due_date= date('Y-m-d', strtotime($date. ' + ' . $frequency . ' days'));
                $rollback_next_due_date=$bill_reference['next_due_date'];
                $rollback_last_paid_date=$bill_reference['last_paid_date'];
                $rollback_next_due_amount=$bill_reference['next_due_amount'];
                $rollback_last_paid_amount=$bill_reference['last_paid_amount'];
            }//END IF bank reference
}//END if bill reference
//WHETHER OR NOT THE RECORD WAS FOUND IN BILLS OR IN HANCOCK, WE CAN UPDATE.  
//WE CAN USE DEFAULT VALUES IF THERE IS NO PRIOR TRANSACTION.
        //Pre-Update Balance for Budget Id - OPTIMIZED: Use pre-loaded array
         $prior_balance = $budget_balances[$budget_id]['balance'] ?? 0;
         $budget_name = $budget_balances[$budget_id]['budget'] ?? 'Unknown';
         $budget_updated='No';
         $updated_balance= $prior_balance + $amount;
//HANDLE BUDGET LOADING TRANSACTION, IF ANY.         
            if($description == DESC_ALLOWANCE_DEPOSIT){
                // Use helper function to process complex deposit logic
                $deposit_transactions = process_allowance_deposit(
                    $budget_pdo, 
                    $row, 
                    $description, 
                    $date, 
                    $month, 
                    $year, 
                    $check_number, 
                    $transaction_type, 
                    $hancock, 
                    $reference, 
                    $budget_balances
                );
                
                // Insert all deposit-related transactions
                foreach ($deposit_transactions as $deposit_data) {
                    insert_csv_process_row($budget_pdo, $deposit_data);
                }
                
                // Skip the normal insert for this row since we handled it above
                continue;
            }//if deposit from mom's account         
        // Insert the transaction into the database table 'csv_process' using helper function
        insert_csv_process_row($budget_pdo, [
            'date' => $date,
            'check_number' => $check_number,
            'transaction_type' => $transaction_type,
            'description' => $description,
            'debits' => $debits,
            'credits' => $credits,
            'edited' => $edited,
            'reconciled' => $reconciled,
            'comment' => $comment,
            'bill_id' => $bill_id,
            'bill' => $bill,
            'budget_id' => $budget_id,
            'budget_name' => $budget_name,
            'amount' => $amount,
            'flags_id' => $flags_id,
            'reimbursement' => $reimbursement,
            'notes' => $notes,
            'last_paid_amount' => $last_paid_amount,
            'last_paid_date' => $last_paid_date,
            'next_due_amount' => $next_due_amount,
            'next_due_date' => $next_due_date,
            'hancock' => $hancock,
            'frequency' => $frequency ?? 0,
            'bills_table_updated' => $bills_table_updated,
            'budget_updated' => $budget_updated,
            'prior_balance' => $prior_balance,
            'updated_balance' => $updated_balance,
            'reference' => $reference,
            'hancock_table_match' => $hancock_table_match,
            'rollback_next_due_date' => $rollback_next_due_date,
            'rollback_last_paid_date' => $rollback_last_paid_date,
            'rollback_next_due_amount' => $rollback_next_due_amount,
            'rollback_last_paid_amount' => $rollback_last_paid_amount
        ]);
        
        $processed_count++;
    }//END FOR EACH TRANSACTIONS AS ROW
    
            // Commit transaction - all or nothing
            $budget_pdo->commit();
            
            $success_msg = "Successfully processed {$processed_count} transaction(s) into csv_process table.";
            $error_msg = 'Remember to review in Step 3 before final commit.';
            
        } catch (Exception $e) {
            // Rollback on error
            if ($budget_pdo->inTransaction()) {
                $budget_pdo->rollBack();
            }
            $error_msg = 'Processing failed: ' . $e->getMessage();
            error_log('Budget processing error: ' . $e->getMessage());
        }
    }
}//End Submit Form
?>
<?=template_admin_header('Upload CSV file', 'budget', 'process')?>
<div class="content read">
	<div class="page-title">
		<i class="fa-regular fa-address-book fa-lg"></i>
		<div class="wrap">
			<h2>Instructions Page 2</h2>
			<p>STEP 2 - Data Processing from CSV Upload Table to the CSV Process Table</p>
		</div>
	</div>
<h3><strong>1. Inspect the imported file, below.</strong></h3><br>
	<form action="" method="get" class="crud-form">
		<div class="table">
			<table>
				<thead>
					<tr>
 					    <td>Date</td>
 					    <td>Type</td>
                        <td class="responsive-hidden">Description</td>	
					    <td style="text-align: center;">Debits</td>
					    <td style="text-align: center;">Credits</td>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($transactions)): ?>
					<tr>
						<td colspan="10" style="text-align:center;">There are no records.</td>
					</tr>
					<?php endif; ?>
					<?php foreach ($transactions as $result): ?>
			 
					<tr>
						<td class="date"><?=date("m/d/y", strtotime($result['date'])?? '')?></td> 
						<td class="transaction_type"><?=htmlspecialchars($result['transaction_type']?? '', ENT_QUOTES)?></td>
					    <td class="responsive-hidden"><?=htmlspecialchars($result['description']?? '', ENT_QUOTES)?></td>
					    <td class="debits right"><?=htmlspecialchars($result['debits']?? '', ENT_QUOTES)?></td>
					    <td class="credits right"><?=htmlspecialchars($result['credits']?? '', ENT_QUOTES)?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</form>
	
<br><h3><strong>2. The red button below will process the records.</strong></h3><br>	

    <form action="" method="post" class="crud-form">
      <?php if ($error_msg): ?>
        <p class="msg-error"><?=$error_msg?></p>
        <?php endif; ?>

        <?php if ($success_msg): ?>
        <p class="msg-success"><?=$success_msg?></p>
        <?php endif; ?>
 <a href='<?=budget_link_path?>instructions-p1.php' style='background:grey; color:white' class='btn btn-sm'><<< BACK <<<<</a>&nbsp;&nbsp;        
 <button type="submit" name="submit" class="btn" style='background:red'>Start the Process!</button>&nbsp;&nbsp; 
 <a href='<?=budget_link_path?>instructions-p2-5-reconcile.php' style='background:orange; color:white' class='btn btn-sm'>>>> NEXT: Reconcile >>></a> 
    </form>
</div>
<style>
    .right{
        text-align: right;
    }
</style>
<?=template_admin_footer()?>