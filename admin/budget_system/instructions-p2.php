<?php
include_once 'assets/includes/admin_config.php';
// Connect to MySQL database
$budget_pdo = pdo_connect_budget_db($db_host, $db_name7, $db_user, $db_pass);
// Error message
$error_msg = ''; 
// Success message
$success_msg = NULL;
$stmt =$budget_pdo->prepare('SELECT * FROM csv_upload ORDER BY date DESC');
$stmt->execute();
$uploads = $stmt->fetchAll(PDO::FETCH_ASSOC);

//CSV_UPLOAD TABLE
// Get the transactions from the csv table
$stmt = $budget_pdo->prepare('SELECT * FROM csv_upload');
$stmt->execute();
$transactions  = $stmt->fetchAll(PDO::FETCH_ASSOC);

//YOU MUST CLICK SUBMIT TO RUN THIS PROCESS

if(isset($_POST['submit']) ){
//LOOP THROUGH CSV_UPLOAD TABLE 
foreach($transactions as $row){
//SETS DEFAULT TRANSACTION VALUES TO REMAINING BALANCE.
//for transactions not found in bills or in hancock table.
$remainder_flag = 'No';
$flags_id=3;
$budget_id=23;
$budget='None';
$bill_id=25;//BLANK DESCRIPTION
$bill='None';
$description='';
$autopay_flag='N';
$updated_balance=0;
$allowance_update='No';
$reconciled='New';
$reimbursement='';
$allowance_split='No';
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
$description= substr($row['description']??'', 0, 15);
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
               
                if($frequency !=0){
                $next_due_date= date('Y-m-d', strtotime($date. ' + ' . $frequency . ' days'));
                }else{
                $next_due_date= $bill_reference['next_due_date'];    
                }
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
                 if($frequency !=0){
                $next_due_date= date('Y-m-d', strtotime($date. ' + ' . $frequency . ' days'));
                }else{
                $next_due_date= $bill_reference['next_due_date'];    
                }
        }else{ 
                //THIS IS FOR ALL TRANSACTIONS NOT FOUND IN BILLS OR HANCOCK TABLES.
                //FORCES BILL #25 
                $stmt = $budget_pdo->prepare('SELECT * FROM bills WHERE id = ?');
                $stmt->execute([25]);
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
        //Pre-Update Balance for Budget Id
         $prior_balance = 0;
         $budget_updated='No';
         $stmt = $budget_pdo->prepare('SELECT balance FROM budget WHERE id = ?');
         $stmt->execute([ $budget_id ]);
         $balance= $stmt->fetchColumn(); 
         $stmt = $budget_pdo->prepare('SELECT budget FROM budget WHERE id = ?');
         $stmt->execute([ $budget_id ]);
         $budget_name= $stmt->fetchColumn(); 
         $prior_balance=$balance;
         $updated_balance= $balance+$amount;
//HANDLE BUDGET LOADING TRANSACTION, IF ANY.         
            if($description=='OLB XFER FROM 9'){
                //These are processed differently, and can override input.
                if($row['credits']==3500){
                    $amount=0;
                    $next_due_amount=$row['credits'];;
                    $last_paid_amount=$row['credits'];;
                    $last_paid_date=$date;
                    $allowance_update='Yes';
                    $allowance_split='No';
                            $credits                = $row['credits'];
                            $updated_balance        = 0;
                            $prior_balance          = 0;
                            $budget_updated         = "No";
                            $bills_table_updated    = "No";
                            $hancock_table_match    = "No";
                            $reconciled             = "No"; 
                            $comment                = $month . '/' . $year . ' Budget Deposit';
                            $bill_id                = 36;
                            $budget_id              = 36;
                            $budget_name            = 'Fill Budget Buckets';
                            $flags_id               = '5';
                            $reimbursement          = '';
                            $notes                  = 'Budget buckets have been loaded.';
                  // End if that deposit is exactly $3500.00          
                }else if($row['credits'] > 3500.00){
                $stmt = $budget_pdo->prepare('SELECT * FROM bills WHERE id = ?');
                $stmt->execute([36]);
                $bill_reference2 = $stmt->fetch(PDO::FETCH_ASSOC);
                $bill= $bill_reference2['bill'];
                $frequency=$bill_reference2['frequency'];
                if($frequency !=0){
                $next_due_date= date('Y-m-d', strtotime($date . ' + ' . $frequency . ' days'));
                }else{
                $next_due_date= $bill_reference['next_due_date'];    
                }
                            $amount=0;
                            $next_due_amount=3500.00;
                            $last_paid_amount=3500.00;
                            $last_paid_date=$date;
                            $credits                = 3500.00;
                            $updated_balance        = 0;
                            $prior_balance          = 0;
                            $budget_updated         = "No";
                            $bills_table_updated    = "No";
                            $hancock_table_match    = "No";
                            $reconciled             = "No"; 
                            $comment                = $month . '/' . $year . ' Budget Deposit';
                            $bill_id                = 36;
                            $budget_id              = 36;
                            $budget_name            = 'Fill Budget Buckets';
                            $flags_id               = '5';
                            $reimbursement          = '';
                            $notes                  = 'Budget Deposit';
                            $allowance_split='Yes';
                            $split_amount = $row['credits'] - 3500.00;

                $stmt = $budget_pdo->prepare('SELECT balance FROM budget WHERE id = ?');
                $stmt->execute([ 24 ]);//MINIMUM BALANCE TABLE NEEDS TO BE $5000.
                $split_prior_balance= $stmt->fetchColumn();   
                $split_updated_balance = $split_prior_balance + $split_amount;
                $pre_split_updated_balance = $split_updated_balance;
                if($split_updated_balance > 5000.00){
                   $split_updated_balance = 5000.00;
                   $remainder = $pre_split_updated_balance-$split_updated_balance;
                   $split_amount=$split_amount-$remainder;
                   if($remainder > 0){
                       $remainder_amount = $remainder;
                       $stmt = $budget_pdo->prepare('SELECT balance FROM budget WHERE id = ?');
                       $stmt->execute([ 25 ]);//SAVINGS.
                       $remainder_prior_balance = $stmt->fetchColumn();   
                       $remainder_updated_balance = $remainder_prior_balance + $remainder_amount;
                        $stmt = $budget_pdo->prepare('SELECT * FROM bills WHERE id = ?');
                        $stmt->execute([43]);//savings
                        $bill_savings = $stmt->fetch(PDO::FETCH_ASSOC);
                        $remainder_bill= $bill_savings['bill'];
                        $remainder_frequency=$bill_savings['frequency'];
                        if($remainder_frequency !=0){
                        $remainder_next_due_date= date('Y-m-d', strtotime($date . ' + ' . $frequency . ' days'));
                        }else{
                        $remainder_next_due_date= $bill_savings['next_due_date'];    
                        }
                            $remainder_next_due_amount          = 0;
                            $remainder_last_paid_amount         = $remainder_amount;
                            $remainder_last_paid_date           = $date;
                            $remainder_credits                  = $remainder_amount;
                            $remainder_budget_updated           = "No";
                            $remainder_bills_table_updated      = "No";
                            $remainder_hancock_table_match      = "No";
                            $remainder_reconciled               = "No"; 
                            $remainder_comment                  = $month . '/' . $year . ' Min. Balance Deposit';
                            $remainder_bill_id                  = 43;
                            $remainder_budget_id                = 25;
                            $remainder_budget_name              = 'Savings';
                            $remainder_flags_id                 = '6';
                            $remainder_reimbursement            = 'Mom';
                            $remainder_notes                    = 'Remainder of Deposit';
                            $remainder_flag                     = 'Yes';
                   }
                }
                $stmt = $budget_pdo->prepare('SELECT * FROM bills WHERE id = ?');
                $stmt->execute([44]);
                $bill_reference3 = $stmt->fetch(PDO::FETCH_ASSOC);
                $split_frequency=$bill_reference3['frequency'];
                if( $split_frequency !=0){
                $split_next_due_date= date('Y-m-d', strtotime($date. ' + ' .  $split_frequency . ' days'));
                }else{
                $split_next_due_date= $bill_reference3['next_due_date'];    
                }
                            $split_next_due_amount          = 0;
                            $split_last_paid_amount         = $split_amount;
                            $split_last_paid_date           = $date;
                            $split_bill                     = $bill_reference3['bill'];
                            $split_credits                  = $split_amount;
                            $split_budget_updated           = "No";
                            $split_bills_table_updated      = "No";
                            $split_hancock_table_match      = "No";
                            $split_reconciled               = "No"; 
                            $split_comment                  = $month . '/' . $year . ' Min. Balance Deposit';
                            $split_bill_id                  = 44;
                            $split_budget_id                = 24;
                            $split_budget_name              = 'Reimbursements';
                            $split_flags_id                 = '4';
                            $split_reimbursement            = 'Mom';
                            $split_notes                    = 'Minimum Balance Deposit';
                }else if($row['credits'] < 3500){
                $stmt = $budget_pdo->prepare('SELECT balance FROM budget WHERE id = ?');
                $stmt->execute([ 24 ]);
                $prior_balance= $stmt->fetchColumn();   
                $stmt = $budget_pdo->prepare('SELECT * FROM bills WHERE id = ?');
                $stmt->execute([44]);
                $bill_reference4 = $stmt->fetch(PDO::FETCH_ASSOC);
                $frequency=$bill_reference4['frequency'];
                if($frequency !=0){
                $next_due_date= date('Y-m-d', strtotime($date. ' + ' . $frequency . ' days'));
                }else{
                $next_due_date= $bill_reference['next_due_date'];    
                }
                            $updated_balance = $prior_balance + $row['credits'];
                            $allowance_split='No';
                            $budget_updated         = "M";
                            $bills_table_updated    = "N";
                            $hancock_table_match    = "N";
                            $reconciled             = "No"; 
                            $comment                = $month . '/' . $year . 'Budget Deposit';
                            $bill_id                = 44;
                            $budget_id              = 24;
                            $budget_name            = 'Reimbursements';
                            $flags_id               = '4';
                            $reimbursement          = 'Mom';
                            $notes                  = 'Minimum Balance Deposit';
                }
            }//if deposit from mom's account         
        // Insert the transaction into the database table 'csv_process'


      if($allowance_split=='Yes'){
           $stmt = $budget_pdo->prepare('INSERT INTO csv_process 
         ( 
         date,
         check_number, 
         transaction_type, 
         description, 
         debits, 
         credits,
         edited,
         reconciled, 
         comment, 
         bill_id,
         bill,
         budget_id,
         budget,
         amount,
         flags_id,
         reimbursement,
         notes,
         last_paid_amount,
         last_paid_date,
         next_due_amount,
         next_due_date,
         hancock,
         frequency,
         bills_table_updated,
         budget_updated,
         prior_balance,
         updated_balance,
         reference,
         hancock_table_match,
         rollback_next_due_date, 
         rollback_last_paid_date,  
         rollback_next_due_amount,   
         rollback_last_paid_amount   
         ) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
         $stmt->execute([
            $date,
            $check_number, 
            $transaction_type, 
            $description, 
            $debits, 
            $split_credits,
            $edited,
            $split_reconciled,    
            $split_comment,
            $split_bill_id,
            $split_bill,
            $split_budget_id,
            $split_budget_name,
            $split_amount,
            $split_flags_id,
            $split_reimbursement,
            $split_notes,
            $split_amount,                
            $split_last_paid_date, 
            0,
            $split_next_due_date,
            $hancock,
            $split_frequency,
            $split_bills_table_updated,
            $split_budget_updated,
            $split_prior_balance,
            $split_updated_balance, 
            $reference,
            $split_hancock_table_match,
            $split_next_due_date,
            $split_last_paid_date,
            $split_next_due_amount,
            $split_last_paid_amount
            ]);
       }
if($remainder_flag == 'Yes'){
     $stmt = $budget_pdo->prepare('INSERT INTO csv_process 
         ( 
         date,
         check_number, 
         transaction_type, 
         description, 
         debits, 
         credits,
         edited,
         reconciled, 
         comment, 
         bill_id,
         bill,
         budget_id,
         budget,
         amount,
         flags_id,
         reimbursement,
         notes,
         last_paid_amount,
         last_paid_date,
         next_due_amount,
         next_due_date,
         hancock,
         frequency,
         bills_table_updated,
         budget_updated,
         prior_balance,
         updated_balance,
         reference,
         hancock_table_match,
         rollback_next_due_date, 
         rollback_last_paid_date,  
         rollback_next_due_amount,   
         rollback_last_paid_amount   
         ) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
         $stmt->execute([
            $date,
            $check_number, 
            $transaction_type, 
            $description, 
            $debits, 
            $remainder_credits,
            'No',
            $remainder_reconciled,
            $remainder_comment,
            $remainder_bill_id,
            $remainder_bill,
            $remainder_budget_id,
            $remainder_budget_name,
            $remainder_amount,
            $remainder_flags_id,
            $remainder_reimbursement,
            $remainder_notes,
            $remainder_amount,
            $remainder_last_paid_date, 
            $remainder_next_due_amount,
            $remainder_next_due_date,
            $hancock,
            $remainder_frequency,
            $remainder_bills_table_updated,
            $remainder_budget_updated,
            $remainder_prior_balance,
            $remainder_updated_balance, 
            $reference,
            $remainder_hancock_table_match,
            $remainder_next_due_date,
            $remainder_last_paid_date,
            $remainder_next_due_amount,
            $remainder_last_paid_amount
            ]);
       }
    
    
    
                  
         $stmt = $budget_pdo->prepare('INSERT INTO csv_process 
         ( 
         date,
         check_number, 
         transaction_type, 
         description, 
         debits, 
         credits,
         edited,
         reconciled, 
         comment, 
         bill_id,
         bill,
         budget_id,
         budget,
         amount,
         flags_id,
         reimbursement,
         notes,
         last_paid_amount,
         last_paid_date,
         next_due_amount,
         next_due_date,
         hancock,
         frequency,
         bills_table_updated,
         budget_updated,
         prior_balance,
         updated_balance,
         reference,
         hancock_table_match,
         rollback_next_due_date, 
         rollback_last_paid_date,  
         rollback_next_due_amount,   
         rollback_last_paid_amount   
         ) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
         $stmt->execute([
            $date,
            $check_number, 
            $transaction_type, 
            $description, 
            $debits, 
            $credits,
            $edited,
            $reconciled,    
            $comment,
            $bill_id,
            $bill,
            $budget_id,
            $budget_name,
            $amount,
            $flags_id,
            $reimbursement,
            $notes,
            $last_paid_amount,                
            $last_paid_date, 
            $next_due_amount,
            $next_due_date,
            $hancock,
            $frequency,
            $bills_table_updated,
            $budget_updated,
            $prior_balance,
            $updated_balance, 
            $reference,
            $hancock_table_match,
            $rollback_next_due_date,
            $rollback_last_paid_date,
            $rollback_next_due_amount,
            $rollback_last_paid_amount
            ]);
        $success_msg = 'Created Successfully!';
        $error_msg= 'Remember to EMPTY the table when you are done.';
 }//END FOR EACH TRANSACTIONS AS ROW
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
					<?php if (empty($uploads)): ?>
					<tr>
						<td colspan="10" style="text-align:center;">There are no records.</td>
					</tr>
					<?php endif; ?>
					<?php foreach ($uploads as $result): ?>
			 
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
 <a href='<?=budget_link_path?>instructions-p3.php' style='background:yellow; color:black' class='btn btn-sm'>>>> NEXT >>></a> 
    </form>
</div>
<style>
    .right{
        text-align: right;
    }
</style>
<?=template_admin_footer()?>