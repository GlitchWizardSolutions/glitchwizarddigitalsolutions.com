<?php
//11/11/2024 Find out how to process the transactions in the csv_upload table, by bill_id.
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
$reference_id='';
$flags_id='';
$description='';
$autopay_flag='';
//UPDATES TO THE BILLS TABLE, IF ANY
$bills_table_updated='N';
$bills_table_id=0;
//
$hancock_table_match='No';
$budget_updated='No';
$prior_balance=0;
$stmt =$budget_pdo->prepare('SELECT * FROM bills');
$stmt->execute();
$bills = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $budget_pdo->prepare('SELECT * FROM hancock');
$stmt->execute();
$bank_reference = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $budget_pdo->prepare('SELECT * FROM flags');
$stmt->execute();
$flags = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $budget_pdo->prepare('SELECT * FROM budget');
$stmt->execute();
$budget = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get the transactions from the csv table
$stmt = $budget_pdo->prepare('SELECT * FROM csv_upload');
$stmt->execute();
$transactions  = $stmt->fetchAll(PDO::FETCH_ASSOC);

 if(isset($_POST['submit']) ){
//LOOP THROUGH CSV_UPLOAD TABLE 
foreach($transactions as $row){
$date=$row['date']; 
$check_number=$row['check_number'];
$transaction_type=$row['transaction_type'];
$description=$row['description'];
$debits=$row['debits'];
$credits=$row['credits'];
$amount=$row['credits']+$row['debits'];
$reconciled='New';
$reimbursement='';
$comment= 'No matching bill found';
$flags_id= 3;
$bill_id=25;
$budget_id=23;
$notes='Automagically Updated';
$stmt = $budget_pdo->prepare('SELECT * FROM bills WHERE hancock = ? limit 1');
$stmt->execute([$description]);
$bill_reference = $stmt->fetch(PDO::FETCH_ASSOC);

   if($bill_reference) {
                $comment= $bill_reference['bill'];
                $flags_id= $bill_reference['flags_id'];
                $reconciled='No';
                $bill_id=$bill_reference['id'];
                $budget_id=$bill_reference['budget_id'];
                $notes='Automagically Updated by bill id';
                //CAPTURE UPDATES TO THE BILLS TABLE, IF ANY
                $bills_table_updated='Y';
                $bills_table_id=$bill_reference['id'];
                // Update the record
                 $stmt = $budget_pdo->prepare('UPDATE bills 
                    SET
                     next_due_amount = ?, 
                     last_paid_date  = ?,
                     last_paid_amount= ?             
                     WHERE id        = ?');
                     $stmt->execute([
                        $debits,
                        $date,
                        $debits,
                        $bill_reference['id'] ]);               
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
  }//END IF STRINGS ARE THE SAME OR NOT WITH THE NEW TABLE
 }//END ELSE FOR EACH BANK REFERENCE AS BANK ROW
        //update balance for budget id
         
         $budget_updated='Y';
         $stmt = $budget_pdo->prepare('SELECT balance FROM budget WHERE id = ?');
         $stmt->execute([ $budget_id ]);
         $balance= $stmt->fetchColumn(); 
         $prior_balance=$balance;
         $balance+=$amount;
         $stmt = $budget_pdo->prepare('UPDATE budget 
            SET
           balance=?
            WHERE id = ?');
         $stmt->execute([ $balance, $budget_id]);

        //update balance for budget id
         $stmt = $budget_pdo->prepare('SELECT balance FROM budget WHERE id = ?');
         $stmt->execute([ $budget_id ]);
         $balance= $stmt->fetchColumn(); 
         $balance+=$amount;
         $stmt = $budget_pdo->prepare('UPDATE budget 
            SET
           balance=?
            WHERE id = ?');
         $stmt->execute([ $balance, $budget_id]);

        // Insert the transaction into the database table 'update_results'
         $stmt = $budget_pdo->prepare('INSERT INTO update_results 
         ( 
         updated_balance,
         prior_balance,
         budget_updated,
         bills_table_updated,
         hancock_table_match,
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
         balance_type
         ) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
         $stmt->execute([
            $balance,
            $prior_balance,
            $budget_updated,
            $bills_table_updated,
            $hancock_table_match,
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
            $balance_type]);

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
         notes, 
         balance_type
         ) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
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
            $notes,
            $balance_type]);
       // Output message
        $_POST = array();
        $success_msg = 'Created Successfully!';
        $error_msg= 'Remember to EMPTY the table when you are done.';
 }//END FOR EACH TRANSACTIONS AS ROW
}
?>
<?=template_admin_header('Upload CSV file', 'budget', 'create')?>

<div class="content update">

    <div class="page-title">
		<i class="fa-solid fa-user fa-lg"></i>
		<div class="wrap">
			<h2>Upload the CSV Table to the Transaction Table 'hancock'</h2>
			<p>Remember to follow the steps</p>
		</div>
	</div>
    <form action="" method="post" class="crud-form">

   	<div class="table">
	  <table>
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
	<tbody>
<tr>
<td>   			
		 <ol>
		     <li>
		         Check last date loaded, select all transactions except current date, to download into csv file from hancock bank.
		     </li>
		      <li>
		         Open the file and delete the first line.
		     </li>
		      <li>
		         Change the format of the date column to be 'yyyy-mmdd' and save.
		     </li>
		      <li>
		         Change the empty values in debits and credits columns to be 0.00 where.
		     </li>
		     <li>
		        copy the following line to your clipboard: <br>
		        date,check_number,transaction_type,description,debits,credits
		     </li>
		     <li>
		         Open the csv_upload table in phpmyadmin, and go to the import tab.
		     </li>
		     <li>
		         Select the csv file, then add the copied line above to the bottom form field, and submit.
		     </li>
		     <li>
		         Click submit, below.
		     </li>
		     <li>
		         Remember to EMPTY the table when you are done.
		     </li>
		 </ol>

</td>
</tr>
  </tbody>
 </table>
</div>
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
     <button type="submit" name="submit" class="btn">Start the Process!</button>
    
</tr>
    </form>
</div>
<style>
    .right{
        text-align: right;
    }
</style>
<?=template_admin_footer()?>