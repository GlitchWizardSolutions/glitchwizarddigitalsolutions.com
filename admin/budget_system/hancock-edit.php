<?php
// Ensure "ID" param exists
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
$date = strtotime(date("Y-m-d"));
$value='';
$rollback_balance=0;
$selected='';
$match='';
$hancock='';
$flags_id='';
$description='';
$autopay_flag='';
//default values
$hancock = [ 'date' =>        '2025-01-01',
          'check_number' =>      NULL,
          'transaction_type' =>  NULL,
          'description' =>       NULL,
          'debits' =>            0.00,
          'credits' =>           0.00,
          'reconciled' =>       'New',
          'comment' =>          NULL,
          'bill_id' =>          NULL,
          'budget_id' =>        NULL, 
          'flags_id' =>         NULL, 
          'reimbursement' =>    NULL,
          'notes' =>            NULL
           
    ];     
$stmt =$budget_pdo->prepare('SELECT * FROM bills');
$stmt->execute();
$bills = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $budget_pdo->prepare('SELECT* FROM hancock');
$stmt->execute();
$hancocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $budget_pdo->prepare('SELECT * FROM budget');
$stmt->execute();
$budget = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $budget_pdo->prepare('SELECT * FROM flags');
$stmt->execute();
$flags = $stmt->fetchAll(PDO::FETCH_ASSOC);

//grab what's in the database
$stmt = $budget_pdo->prepare('SELECT * FROM hancock  WHERE id = ?');
$stmt->execute([$_GET['id']]);
$hancock  = $stmt->fetch(PDO::FETCH_ASSOC);


$old_transaction_budget_id=$hancock['budget_id'];
$old_bill_id = $hancock['bill_id'];
$amount=$hancock['credits']+$hancock['debits'];

$stmt =$budget_pdo->prepare('SELECT * FROM bills WHERE id = ?');
$stmt->execute([$hancock['bill_id']]);
$bills2 = $stmt->fetch(PDO::FETCH_ASSOC);

$next_due_date=date('Y-m-d', strtotime($bills2['next_due_date']));
$rollback_next_due_date=date('Y-m-d', strtotime($bills2['rollback_next_due_date']));
$rollback_last_paid_date=date('Y-m-d', strtotime($bills2['rollback_last_paid_date']));
$rollback_next_due_amount = $bills2['next_due_amount'];
$rollback_last_paid_amount = $bills2['last_paid_amount'];



if (!$hancock) {
    exit('transaction doesn\'t exist with that ID!');
}
//if parm exists, edit the transaction
if (isset($_POST['description'])){
    $new_budget_id=$_POST['budget_id'];
    $new_bill_id=$_POST['bill_id'];
    
    
     // Validate form data
    if (empty($_POST['description'])) {
        $error_msg = 'Please fill out the form!';
    } 
   
     //if the bill id has changed, remove the transaction from the bill, and redo the transaction to the new bill.
        if($old_bill_id != $new_bill_id){
        $reconciled='New';     
        $stmt = $budget_pdo->prepare('SELECT * FROM bills WHERE id = ?');
        $stmt->execute([$old_bill_id]);
        $bill_reference = $stmt->fetch(PDO::FETCH_ASSOC);
        // Update the bill in bills table to set dates.
            $stmt = $budget_pdo->prepare('UPDATE bills 
            SET
            next_due_date             = ?,
            next_due_amount           = ?, 
            last_paid_date            = ?,
            last_paid_amount          = ?,        
            rollback_next_due_date    = ?, 
            rollback_next_due_amount  = ?,             
            rollback_last_paid_date   = ?,  
            rollback_last_paid_amount = ?
            WHERE id        = ?');
            $stmt->execute([
                $bill_reference['rollback_next_due_date'],
                $bill_reference['rollback_next_due_amount'],
                $bill_reference['rollback_last_paid_date'],
                $bill_reference['rollback_last_paid_amount'],
                $bill_reference['rollback_next_due_date'],
                $bill_reference['rollback_next_due_amount'],
                $bill_reference['rollback_last_paid_date'],
                $bill_reference['rollback_last_paid_amount'],
                $old_bill_id ]);   

// Update the bill in bills table to set dates.
//BILLS TABLE
$stmt =$budget_pdo->prepare('SELECT * FROM bills WHERE id=?');
$stmt->execute([$new_bill_id]);
$bill_reference2 = $stmt->fetch(PDO::FETCH_ASSOC);
                $rollback_next_due_date     = $bill_reference2['next_due_date'];
                $rollback_last_paid_date    = $bill_reference2['last_paid_date'];
                $rollback_last_paid_amount  = $bill_reference2['next_due_amount'];
                $rollback_next_due_amount   = $bill_reference2['last_paid_amount'];
                $frequency                  = $bill_reference2['frequency'];
                if($frequency !=0){
                $next_due_date= date('Y-m-d', strtotime($date. ' + ' . $frequency . ' days'));
                }else{
                $next_due_date= $bill_reference2['next_due_date'];    
                }
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
          $hancock['date'],
          $amount,
          $rollback_next_due_date,
          $rollback_last_paid_date,
          $rollback_next_due_amount,   
          $rollback_last_paid_amount,
          $new_bill_id]);    
 
    }

        
    //if the budget bucket has changed, remove the transaction from the bucket, and redo the transaction to the new bucket.
  
    if($old_transaction_budget_id != $new_budget_id){
        $reconciled="New";
        $stmt = $budget_pdo->prepare('SELECT * FROM budget WHERE id = ?');
        $stmt->execute([$old_transaction_budget_id]);
        $budget_reference = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $prior_balance = $budget_reference['balance'];
        $rollback_balance = $prior_balance - $amount; 
        

        
        $stmt = $budget_pdo->prepare('UPDATE budget 
                 SET
                 balance   = ?
                 WHERE id        = ?');
                   $stmt->execute([
                   $rollback_balance,
                   $old_transaction_budget_id ]); 
                   
        $stmt = $budget_pdo->prepare('SELECT * FROM budget WHERE id = ?');
        $stmt->execute([$new_budget_id]);
        $budget_reference2 = $stmt->fetch(PDO::FETCH_ASSOC);
        $prior_balance = $budget_reference2['balance'];
        $new_balance = $prior_balance+$amount;           
        $stmt = $budget_pdo->prepare('UPDATE budget 
                 SET
                 balance   = ?
                 WHERE id        = ?');
                   $stmt->execute([
                   $new_balance,
                   $new_budget_id]);    
    }

     // If no validation errors, proceed to insert the record(s) in the database
    if (!$error_msg) {

        // Update the record
            $stmt = $budget_pdo->prepare('UPDATE hancock 
            SET
            date          = ?,
            description   = ?,
            debits        = ?,
            credits       = ?, 
            reconciled    = ?,
            comment       = ?,
            bill_id       = ?, 
            budget_id     = ?,
            flags_id      = ? ,
            reimbursement = ?, 
            notes         = ?
            WHERE id      = ?');
            $stmt->execute([
            $_POST['date'],
            $_POST['description'],
            $_POST['debits'],
            $_POST['credits'],
            $_POST['reconciled'],
            $_POST['comment'],
            $_POST['bill_id'],
            $_POST['budget_id'],
            $_POST['flags_id'],
            $_POST['reimbursement'],
            $_POST['notes'],
            $_GET['id'] ]);
        // Get the updated record from the hancock table
        $stmt = $budget_pdo->prepare('SELECT * FROM hancock WHERE id = ?');
        $stmt->execute([ $_GET['id'] ]);
        $result= $stmt->fetch(PDO::FETCH_ASSOC);
        // Output message
        $success_msg = 'Updated Successfully!';
   }
    header("Location: hancock-browse.php");
    exit;

}  

?>
<?=template_admin_header('View hancock', 'budget', 'hancock')?>
<div class="content update">
<div class="page-title">
		<i class="fa-solid fa-user fa-lg"></i>
		<div class="wrap">
		 <h2>Update  <?=$hancock['description']?></h2> 
		 	<p>Fill in the form below and submit, to update transaction # <?=$_GET['id']?>.</p>
		</div>
</div>

<form action="" method="post" class="crud-form">
<div class="table">
<table>
          <?php if ($error_msg): ?>
        <p class="msg-error"><?=$error_msg?></p>
        <?php endif; ?>

        <?php if ($success_msg): ?>
        <p class="msg-success"><?=$success_msg?></p>
        <?php endif; ?>
<tbody>
<!--row 1-->
<tr>
    
<td>
       <div class="form-control" style='width:20%'>
        <label for="date">Transaction Date</label>
        <input type="datetime-local" name="date" id="date" value="<?=date('Y-m-d\TH:i', strtotime($hancock['date']??'2025-01-30'))?>" required>
      </div>
</td>      
    <td>        
          <div class="form-control" style='width:15%'>
                <label for="reconciled">Reconciled</label>
                <select name="reconciled" id="reconciled"required>
                    <option value="Yes"<?=$hancock['reconciled']=='Yes'?' selected':''?>>Yes</option>
                    <option value="No"<?=$hancock['reconciled']=='No'?' selected':''?>>No</option>
                    <option value="New"<?=$hancock['reconciled']=='New'?' selected':''?>>New</option>
                </select>
            </div> 
 </td>  
</tr> 
<!--row 2-->
<tr>
<td>
      <div class="form-control" style='width:80%'>
        <label for="description">Description</label>
        <input type="text" name="description" id="description" value="<?=htmlspecialchars($hancock['description'], ENT_QUOTES)?>" required>
        </div>
</td>
<td>
           <div class="form-control" style='width:50%'>
                <label for="comment">Detail</label>
                <input type="text" name="comment" id="comment" value="<?=$hancock['comment']?>"> 
            </div>
</td>
</tr>
<!--row 3-->
<tr>  
<td> 
           <div class="form-control" style='width:80%'>
                <label for="flags_id">Flag</label>
                <select name="flags_id" id="flags_id" required>
            <?php foreach($flags as $row) :?>
            <?php 
                    $selected='';
                    $value=$row['id'];
                    $match=$hancock['flags_id'];
                    if($value==$match){
                       $selected='selected';
                    }
               ?>
             <option value='<?=$value;?>'<?=$selected;?>><?=$row['description']?></option>
            <?php endforeach ?>
                </select>
            </div>
</td>
</tr>
<tr>
    <td>   
     <div class="form-control" style='width:80%'>
                <label for="reimbursement">Reimbursement By</label>
                <select name="reimbursement" id="reimbursement">
                   <option value="None"<?=$hancock['reimbursement']=='None'?' selected':''?>>None</option>
                   <option value="Mom"<?=$hancock['reimbursement']=='Mom'?' selected':''?>>Mom</option>
                   <option value="Joey"<?=$hancock['reimbursement']=='Joey'?' selected':''?>>Joey</option>
                   <option value="Business"<?=$hancock['reimbursement']=='Business'?' selected':''?>>Business</option>
                   <option value="Donna"<?=$hancock['reimbursement']=='Donna'?' selected':''?>>Donna</option>
                   <option value="Barbara"<?=$hancock['reimbursement']=='Barbara'?' selected':''?>>Barbara</option>
                   <option value="Reserves"<?=$hancock['reimbursement']=='Reserves'?' selected':''?>>Reserves</option>
                </select>
              </div>
</td>

</tr>
<!--row 6-->
<tr>
    <td>
              <div class="form-control" style='width:80%'>
                <label for="budget_id">Budget Bucket</label>
                <select name="budget_id" id="budget_id" required> 
            <?php foreach($budget as $row) :?>
             <?php 
                    $selected='';
                    $value=$row['id'];
                    $match=$hancock['budget_id'];
                    if($value==$match){
                       $selected='selected';
                    }
               ?>
             <option value='<?=$value;?>'<?=$selected;?>><?=$row['budget']?></option>
            <?php endforeach ?>
                </select>
            </div>
</td>
<td>
             <div class="form-control" style='width:15%'>
                <label for="debits">Debits</label>
                <input type="number" name="debits"  step="any" id="debits" value="<?=$hancock['debits']?>"required>
              </div>
</td>
</tr>
<!--row 6-->
<tr>
    <td>
              <div class="form-control" style='width:80%'>
                <label for="bill_id">Bill Paid</label>
                <select name="bill_id" id="bill_id" required>
            <?php foreach($bills as $row) :?>
             <?php 
                    $selected='';
                    $value=$row['id'];
                    $match=$hancock['bill_id'];
                    if($value==$match){
                       $selected='selected';
                    }
               ?>
             <option value='<?=$value;?>'<?=$selected;?>><?=$row['bill']?></option>
            <?php endforeach ?>
                </select>
            </div>
</td>
<td>      
         
             <div class="form-control" style='width:15%'>
                <label for="credits">Credits</label>
                <input type="number" name="credits" step="any" id="credits" value="<?=$hancock['credits']?>"required>
              </div>
</td>
</tr>
<!--row 10-->
<tr>
<td colspan=2> 
           <div class="form-control" style='width:100%; text-align:center; background:gray; color:white'>
                <label for="notes">Notes</label>
                <input type="text" name="notes" id="notes" style='margin:20px' value="<?=$hancock['notes']?>"> 
            </div>
</td>
</tr>

</tbody>
</table>
<a href="hancock-browse.php" class="btn alt mar-right-2">Cancel</a>
<button type="submit" name="submit" class="btn">Save Record</button>
</div>
</form>
</div>
<style>
    .right{
        text-align: right;
    }
</style>
<?=template_admin_footer()?>