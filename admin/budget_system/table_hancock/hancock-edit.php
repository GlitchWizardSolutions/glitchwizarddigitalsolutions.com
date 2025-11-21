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
$value='';
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
          'notes' =>            NULL,
          'balance_type' =>     0          
    ];     
$stmt =$budget_pdo->prepare('SELECT * FROM bills');
$stmt->execute();
$bills = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $budget_pdo->prepare('SELECT * FROM bank_reference');
$stmt->execute();
$bank_reference = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

if (!$hancock) {
    exit('transaction doesn\'t exist with that ID!');
}
//if parm exists, edit the transaction
if (isset($_POST['description'])){
     // Validate form data
    if (empty($_POST['description'])) {
        $error_msg = 'Please fill out the form!';
    } 
     // If no validation errors, proceed to insert the record(s) in the database
    if (!$error_msg) {
        echo $_POST['bill_id'] . ' ' . $_POST['notes'];
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
            notes         = ?,
            balance_type  = ?             
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
            $_POST['balance_type'],            
            $_GET['id'] ]);
        // Get the updated record from the hancock table
        $stmt = $budget_pdo->prepare('SELECT * FROM hancock WHERE id = ?');
        $stmt->execute([ $_GET['id'] ]);
        $result= $stmt->fetch(PDO::FETCH_ASSOC);
        // Output message
        $success_msg = 'Updated Successfully!';
   }
}  
?>
<?=template_admin_header('View hancock', 'budget', 'manage')?>
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
                   <option value="Reserves"<?=$hancock['reimbursement']=='Reserves'?' selected':''?>>Reserves</option>
                </select>
              </div>
</td>
 <td>
            <div class="form-control" style='width:15%'>
                <label for="balance_type">Balance Type</label>
                <select name="balance_type" id="balance_type"required>
                    <option value="1"<?=$hancock['balance_type']=='1'?' selected':''?>>Credit</option>
                    <option value="2"<?=$hancock['balance_type']=='2'?' selected':''?>>Debit</option>
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
                <input type="number" name="debits"  id="debits" value="<?=$hancock['debits']?>"required>
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
                <input type="number" name="credits"  id="credits" value="<?=$hancock['credits']?>"required>
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
<a href="bs_view_bills.php" class="btn alt mar-right-2">Cancel</a>
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