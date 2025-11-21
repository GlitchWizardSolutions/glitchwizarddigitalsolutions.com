<?php
//CSV Process Transactions Edit 11/21/2024
// Ensure bill "ID" param exists
if (!isset($_GET['id'])) {
    exit('No ID specified!');
}
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
$hancock='';
$flags_id='';
$description='';
$autopay_flag='';
//default values
$csv_process = [ 
         'date' =>    '2099-12-30',
         'check_number', 
         'transaction_type', 
         'description' =>      '', 
         'debits', 
         'credits',
         'edited',
         'reconciled', 
         'comment', 
         'bill_id',
         'bill' =>             '',
         'budget_id' =>        23,
         'budget',
         'amount',
         'balance',
         'flags_id' =>         3,
         'reimbursement',
         'notes',
         'last_paid_amount' => 0,
         'last_paid_date'   => '2099-12-30',
         'next_due_amount'  => 0,
         'next_due_date'    => '2099-12-30',
         'hancock'          => '',
         'frequency'        => 0,
         'bills_table_updated' => 'No',
         'budget_updated'   => 'No',
         'prior_balance'    => 0,
         'updated_balance'  => 0,
         'reference'        => ' ',
         'hancock_table_match' => ' ' 
    ];     
$stmt =$budget_pdo->prepare('SELECT * FROM bills');
$stmt->execute();
$bills = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $budget_pdo->prepare('SELECT * FROM hancock');
$stmt->execute();
$hancock = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $budget_pdo->prepare('SELECT * FROM budget');
$stmt->execute();
$budget = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $budget_pdo->prepare('SELECT * FROM flags');
$stmt->execute();
$flags = $stmt->fetchAll(PDO::FETCH_ASSOC);

//grab what's in the database
$stmt = $budget_pdo->prepare('SELECT * FROM csv_process WHERE id=?');
$stmt->execute([$_GET['id']]);
$csv_process  = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$csv_process) {
    exit('record doesn\'t exist with that ID!');
}
//if parm exists, edit the transaction
if (isset($_POST['submit'])){
     // Validate form data
    if (empty($_POST['comment'])) {
        $error_msg = 'Please fill out the description of the transaction!';
    } 
    // If no validation errors, proceed to insert the record(s) in the database
    if (!$error_msg) {
                $stmt = $budget_pdo->prepare('SELECT * FROM bills WHERE id = ?');
                $stmt->execute([$_POST['bill_id']]);
                $bill_reference = $stmt->fetch(PDO::FETCH_ASSOC);
                $frequency=$bill_reference['frequency'];
                $bill= $bill_reference['bill'];
                if($frequency !=0){
                $next_due_date= date('Y-m-d', strtotime($csv_process['date'] . ' + ' . $frequency . ' days'));
                }else{
                $next_due_date = $bill_reference['next_due_date'];    
                }
                $amount= $csv_process['credits'] + $csv_process['debits'];
                $next_due_amount = $amount;
                $last_paid_amount = $amount;
                $last_paid_date = $csv_process['date'];

                $stmt = $budget_pdo->prepare('SELECT * FROM budget WHERE id = ?');
                $stmt->execute([$_POST['budget_id']]);
                $budget_reference = $stmt->fetch(PDO::FETCH_ASSOC);
                $budget_name=$budget_reference['budget'];
                $prior_balance= $budget_reference['balance'];
                $updated_balance= $prior_balance+$amount;
// Update the record
            $stmt = $budget_pdo->prepare('UPDATE csv_process 
            SET
         date                               = ?,
         description                        = ?,
         debits                             = ?, 
         credits                            = ?,
         edited                             = ?,
         comment                            = ?,
         bill_id                            = ?,
         budget_id                          = ?,
         flags_id                           = ?,
         reimbursement                      = ?,
         notes                              = ?,
         last_paid_amount                   = ?,
         last_paid_date                     = ?,
         next_due_amount                    = ?,
         next_due_date                      = ?,
         budget                             = ?,
         prior_balance                      = ?,
         amount                             = ?,
         updated_balance                    = ?,
         bill                               = ?,
         frequency                          = ?         
            WHERE id        = ?');
            $stmt->execute([
            $csv_process['date'],
            $csv_process['description'],
            $csv_process['debits'],
            $csv_process['credits'],
            $_POST['edited'],
            $_POST['comment'],
            $_POST['bill_id'],
            $_POST['budget_id'],
            $_POST['flags_id'],
            $_POST['reimbursement'],
            $_POST['notes'],
            $amount,
            $next_due_date,
            $last_paid_amount,
            $next_due_date,
            $budget_name,
            $prior_balance,
            $amount,
            $updated_balance,
            $bill,
            $frequency,            
            $_GET['id'] ]);
        // Get the updated values from the csv_process table.
        $stmt = $budget_pdo->prepare('SELECT * FROM csv_process WHERE id = ?');
        $stmt->execute([ $_GET['id'] ]);
        $csv_process = $stmt->fetch(PDO::FETCH_ASSOC);
          // Output message
        $success_msg = 'Updated Successfully!';
   }
}  
?>
<?=template_admin_header('View bills', 'budget', 'manage')?>
<div class="content update">
    <div class="page-title">
		<i class="fa-solid fa-user fa-lg"></i>
		<div class="wrap">
		 <h2>Update <?=$csv_process['hancock']?></h2> 
		 	<p>Fill in the form below and submit, to update transaction # <?=$csv_process['id']?>.</p>
		</div>
	</div>
 
<a href="instructions-p3.php" class="btn">
        <svg class="icon-left" width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 80c0-17.7-14.3-32-32-32s-32 14.3-32 32V224H48c-17.7 0-32 14.3-32 32s14.3 32 32 32H192V432c0 17.7 14.3 32 32 32s32-14.3 32-32V288H400c17.7 0 32-14.3 32-32s-14.3-32-32-32H256V80z"/></svg>
       Browse Transactions
    </a>	
    <form action="" method="post" class="crud-form">
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
        Budget= <?=$csv_process['budget'] ?><br>
        Prior Balance= <?=$csv_process['prior_balance'] ?><br>
        Amount= <?=$csv_process['amount'] ?> <br>
        Updated Balance= <?=$csv_process['updated_balance'] ?>
        
    </td>
    <td>
        Bill=<?=$csv_process['bill']?> <br>
        Frequency= <?=$csv_process['frequency'] ?><br>
    </td>
    <td>    
        Hancock Table Match=<?=$csv_process['hancock_table_match']?> <br>
        Bills table updated= <?=$csv_process['bills_table_updated']?> <br>
        Budget table updated= <?=$csv_process['budget_updated']?><br>
    </td>
</tr>
<tr>
    <td>
              <div class="form-control">
                <label for="date">Date</label>
                <input type="datetime-local" name="date"  id="date" value="<?=$csv_process['date'] ?>" disabled>
              </div>
</td>
<td>
         	<div class="form-control" style='width:50%'>
                <label for="debits">Debits</label>
                <input type="number" name="debits"  id="debits" value="<?=$csv_process['debits'] ?>" placeholder="" disabled>
             </div>
</td>
<td>
         	<div class="form-control" style='width:50%'>
                <label for="credits">Credits</label>
                <input type="number" name="credits"  id="credits" value="<?=$csv_process['credits'] ?>" placeholder="" disabled>
             </div>
</td>

</tr>
<tr>

 </tr>
 
 
<tr>	
    <td>        <div class="form-control" style='width:80%'>
                <label for="bill_id">Bill</label>
                <select name="bill_id" id="bill_id" required> 
            
            <?php foreach($bills as $row) :?>
             <?php 
                    $selected='';
                    $value=$row['id'];
                    $match=$csv_process['bill_id'];
                    if($value==$match){
                       $selected='selected';
                    }
               ?>
             <option value='<?=$value?>'<?=$selected;?>><?=$value?>&nbsp;<?=$row['bill'] ?></option>
            
            <?php endforeach ?>
                </select>
            </div>
          
   </td>

<td>               
            <div class="form-control" style='width:80%'>
                <label for="description">Bank Reference</label>
                <input type="text" name="description" id="description" value="<?=htmlspecialchars($csv_process['description'], ENT_QUOTES)?>" disabled>
             </div>
</td>
<td>               
            <div class="form-control" style='width:80%'>
                <label for="comment">Description</label>
                <input type="text" name="comment" id="comment" value="<?=htmlspecialchars($csv_process['comment'], ENT_QUOTES)?>" required>
             </div>
</td>
</tr>
<tr>  


</td>
           <td 
           <div class="form-control" style='width:80%'>
                <label for="flags_id">Flag</label>
                <select name="flags_id" id="flags_id" required>
            <?php foreach($flags as $row) :?>
            <?php 
                    $selected='';
                    $value=$row['id'];
                    $match=$csv_process['flags_id'];
                    if($value==$match){
                       $selected='selected';
                    }
               ?>
             <option value='<?=$value;?>'<?=$selected;?>><?=$row['description']?></option>
            <?php endforeach ?>
                </select>
            </div>
</td>
    <td>   
     <div class="form-control" style='width:80%'>
                <label for="reimbursement">Reimbursement By</label>
                <select name="reimbursement" id="reimbursement" required> 
                   <option value="None"<?=$csv_process['reimbursement']=='None'?' selected':''?>>None</option>
                   <option value="Mom"<?=$csv_process['reimbursement']=='Mom'?' selected':''?>>Mom</option>
                   <option value="Joey"<?=$csv_process['reimbursement']=='Joey'?' selected':''?>>Joey</option>
                   <option value="Business"<?=$csv_process['reimbursement']=='Business'?' selected':''?>>Business</option>
                   <option value="Donna"<?=$csv_process['reimbursement']=='Donna'?' selected':''?>>Donna</option>
                   <option value="Barbara"<?=$csv_process['reimbursement']=='Barbara'?' selected':''?>>Barbara</option>
                   <option value="Reserves"<?=$csv_process['reimbursement']=='Reserves'?' selected':''?>>Reserves</option>
                   <option value="See Description"<?=$csv_process['reimbursement']=='See Description'?' selected':''?>>See Description</option>
                </select>
              </div>
</td>
</tr>
<tr>
     <td>
              <div class="form-control" style='width:80%'>
                <label for="budget_id">Budget Bucket</label>
                <select name="budget_id" id="budget_id" required> 
                
            <?php foreach($budget as $row) :?>
             <?php 
                    $selected='';
                    $value=$row['id'];
                    $match=$csv_process['budget_id'];
                    if($value==$match){
                       $selected='selected';
                    }
               ?>
             <option value='<?=$value;?>'<?=$selected;?>><?=$row['budget']?></option>
            <?php endforeach ?>
                </select>
            </div>
</td>
 <tr>
    <td>
              <div class="form-control">
                <label for="last_paid_date">Last Paid Date</label>
                <input type="datetime-local" name="last_paid_date"  id="last_paid_date" value="<?=date('Y-m-d\TH:i', strtotime($csv_process['last_paid_date']))?>" disabled>
              </div>
</td>
<td>
         	<div class="form-control" style='width:50%'>
                <label for="last_paid_amount">Last Paid $</label>
                <input type="number" name="last_paid_amount"  id="last_paid_amount" value="<?=$csv_process['last_paid_amount']?>" placeholder="" disabled>
             </div>
</td>

 </tr>
 <tr>
     <td>
            <div class="form-control">
                <label for="next_due_date">Next Due Date</label>
               <input type="datetime-local" name="next_due_date"  id="next_due_date" placeholder="Next Due Date"  value="<?=date('Y-m-d\TH:i', strtotime($csv_process['next_due_date']))?>" disabled>
            </div>
</td>
<td>
      	  <div class="form-control" style='width:50%'>
                <label for="next_due_amount">Next Due $</label>
                <input type="number" name="next_due_amount"  id="next_due_amount"value="<?=$csv_process['next_due_amount']?>" placeholder="" disabled>
             </div>
</td>
 

</tr>

 

<tr>
<td>               
            <div class="form-control" style='width:90%'>
                <label for="notes">Notes</label>
                <input type="text" name="notes" id="notes" value="<?=htmlspecialchars($csv_process['notes'], ENT_QUOTES)?>" required>
             </div>
</td>
 <td> 
             <div class="form-control" style='width:80%'>
                <label for="edited">Edited?</label>
                <select name="edited" id="edited"> 
                   <option value="Yes"<?=$csv_process['edited']=='Yes'?' selected':''?>>Yes</option>
                   <option value="No"<?=$csv_process['edited']=='No'?' selected':''?>>No</option>
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
  </tbody>
 </table>
   <a href="bills-browse.php" class="btn alt mar-right-2">Cancel</a>
        <button type="submit" name="submit" class="btn">Save Record</button>
</div>
</form>
</div>
<?=template_admin_footer()?>