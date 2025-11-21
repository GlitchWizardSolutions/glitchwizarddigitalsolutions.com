<style>
    .right{
        text-align: right;
    }
</style>
<?php
//11/11/2024 Update to budget table as well as running_balance table during the creation of the transaction record.
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

$stmt =$budget_pdo->prepare('SELECT * FROM bills');
$stmt->execute();
$bills = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $budget_pdo->prepare('SELECT * FROM bank_reference');
$stmt->execute();
$bank_reference = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $budget_pdo->prepare('SELECT * FROM flags');
$stmt->execute();
$flags = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $budget_pdo->prepare('SELECT * FROM budget');
$stmt->execute();
$budget = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get the transactions from the running_balance table
$stmt = $budget_pdo->prepare('SELECT * FROM running_balance');
$stmt->execute();
$transactions  = $stmt->fetch(PDO::FETCH_ASSOC);
 
if (isset($_POST['balance_type'], $_POST['budget_id'], $_POST['reference_id'],
$_POST['amount'], $_POST['flags_id'], $_POST['remarks'], $_POST['description'], $_POST['bill_id'],
$_POST['reference_date'])) {
 // Validate form data
    $data = [
             'reimbursement'  =>    $_POST['reimbursement'],
             'balance_type' =>      $_POST['balance_type'],
             'budget_id' =>         $_POST['budget_id'],
             'reference_id' =>      $_POST['reference_id'],
             'amount' =>            $_POST['amount'],
             'flags_id' =>          $_POST['flags_id'],
             'remarks' =>           $_POST['remarks'],
             'description' =>       $_POST['description'],
             'bill_id' =>           $_POST['bill_id'],
             'reference_date' =>    $_POST['reference_date']
    ];
    
    if (empty($data['balance_type'])){ $error_msg = 'Please fill out the balance type!';}
    elseif(empty($data['budget_id'])){ $error_msg = 'Please fill out the budget envelope!';}
    elseif(empty($data['flags_id'])){ $error_msg = 'Please fill out the flag!';}
    elseif(empty($data['description'])){ $error_msg = 'Please fill out the description!';}
    elseif(empty($data['bill_id'])){ $error_msg = 'Please fill out the bill!';}
    elseif(empty($data['amount'])){ $error_msg = 'Please fill out the amount!';}
    elseif(empty($data['reimbursement'])){ $error_msg = 'Please fill out the reimbursement type!';}
   
    // If no validation errors, proceed to insert the record(s) in the database
    if (!$error_msg) {
 
      if($data['balance_type'] ==1){
         //add to reserve balance for budget id
         $stmt = $budget_pdo->prepare('SELECT balance FROM budget WHERE id = ?');
         $stmt->execute([ $data['budget_id'] ]);
         $balance= $stmt->fetchColumn(); 
         $balance+=$data['amount'];
         $stmt = $budget_pdo->prepare('UPDATE budget 
            SET
           balance=?
            WHERE id = ?');
         $stmt->execute([ $balance,$data['budget_id']]);
      }elseif($data['balance_type']==2){
       //subtract amount from balance
         $stmt = $budget_pdo->prepare('SELECT balance FROM budget WHERE id = ?');
         $stmt->execute([ $data['budget_id'] ]);
         $balance= $stmt->fetchColumn(); 
         $balance-=$data['amount'];
         $stmt = $budget_pdo->prepare('UPDATE budget 
            SET
           balance=?
            WHERE id = ?');
         $stmt->execute([ $balance,$data['budget_id']]);
      }elseif($data['balance_type']==3){
       //add bulk to balances
         $stmt = $budget_pdo->query('UPDATE budget 
            SET
           balance= balance-amount');
      }
      
        // Insert the records
         $stmt = $budget_pdo->prepare('INSERT INTO running_balance 
         ( 
         reimbursement,
         budget_id, 
         reference_id, 
         amount, 
         flags_id, 
         remarks, 
         description, 
         bill_id, 
         reference_date, 
         balance_type) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
         $stmt->execute([
             $data['reimbursement'], 
             $data['budget_id'], 
             $data['reference_id'], 
             $data['amount'], 
             $data['flags_id'], 
             $data['remarks'], 
             $data['description'], 
             $data['bill_id'], 
             $data['reference_date'], 
             $data['balance_type']]);
       // Output message
        $_POST = array();
        $success_msg = 'Created Successfully!';
      
    }
 } 
?>
<?=template_admin_header('Create New Transaction', 'budget', 'create')?>

<div class="content update">

    <div class="page-title">
		<i class="fa-solid fa-user fa-lg"></i>
		<div class="wrap">
			<h2>Create New Transaction</h2>
			<p>Create a transaction in the database, fill in the form below and submit.</p>
		 
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
            <div class="form-control" style='width:25%'>
                <label for="balance_type">Type</label>
  	            <select name="balance_type" id="balance_type" required>
  	                 <option value='' selected='selected'>Select Transaction Type</option>
                     <option value='1' style='color:green' >Deposit</option>
                     <option value='2' >Expense</option>
                     <option value='3' style='color:red' >**Monthly Load**</option>
                </select>
            </div>
</td>
<td>    
             <div class="form-control" style='width:100%'>
                    <label for="description">Description</label>
                    <input type="text" list="bill_list" name= "description" id="description" required>
                    <datalist id="bill_list">
                        <?php foreach($bills as $row) :?>
                     <option value='<?php echo $row["bill"]; ?>'><?=$row["bill"]?>!</option>
                        <?php endforeach ?>
                     </datalist>
                </div>

</td>

</tr>
    <tr>
<td>
  	        <div class="form-control">
                <label for="amount">Amount</label>
                <input type="number" name="amount" value=0 id="amount"  required>
            </div>
</td>
<td>
            <div class="form-control">
                <label for="reference_date">Bank Transaction Date</label>
                <input type="datetime-local" name="reference_date" id="reference_date" placeholder="Reference Date" value="<?=date('Y-m-d\TH:i')?>" required>
            </div>
</td>
</tr><tr>
<td>
            <div class="form-control">
             <label for="reference_id">Bank Reference</label>
             <select name="reference_id" id="reference_id" required>
                 <option value='11' selected='selected'>See Description</option>
                 <?php foreach($bank_reference as $row) :?>
                <?php $value=$row['id'];?>
                   <option value='<?=$value;?>'><?=$row['bank_reference']?></option>
                 <?php endforeach ?>
             </select>
            </div> 
</td>      
<td>
   
        <div class="form-control" style='width:80%'>
                <label for="bill_id">Bill Paid</label>
                <select name="bill_id" id="bill_id" required>
               <option value=34 selected='selected'>Not a bill</option>
            <?php foreach($bills as $row) :?>
           <?php 
                $reference_id=  $row['reference_id'];
                $flags_id=      $row['flags_id'];
                $description=   $row['description'];
                    $value=$row['id'];
               ?>
                   <option value='<?=$value;?>'><?=$row['bill']?></option>
               <?php endforeach ?>
                </select>
            </div> 
</td>
</tr><tr>
<td>
            <div class="form-control">
             <label for="flags_id">Flag</label>
             <select name="flags_id" id="flags_id" required>
                 <option value=''></option>
                 <?php foreach($flags as $row) :?>
                <?php $value=$row['id'];?>
                   <option value='<?=$value;?>'><?=$row['description']?></option>
                 <?php endforeach ?>
             </select>
            </div> 
</td>
<td>
            <div class="form-control">
             <label for="budget_id">Budget Balance</label>
             <select name="budget_id" id="budget_id" required>
                 <option value=''></option>
                 <?php foreach($budget as $row) :?>
                <?php $value=$row['id'];?>
                   <option value='<?=$value;?>'> [ <?=$row['balance']?> ] <?=$row['budget']?></option>
                 <?php endforeach ?>
             </select>
            </div> 
</td>
</tr>
<tr>
    <td>     
            <div class="form-control" style='width:25%'>
                <label for="reimbursement">Balance Type</label>
  	            <select name="reimbursement" id="reimbursement" required>
  	                 <option value='' selected='selected'>Select Balance Type</option>
                     <option value='Budgeted' style='color:green' >Budgeted</option>
                     <option value='Business' style='color:red'>Incidental</option>
                     <option value='Mom' style='color:green'>Mom</option>
                     <option value='Joey' style='color:red'>Joey</option>
                     <option value='Business' style='color:red'>Business</option>
                </select>
            </div>
</td>
    
    
    <td>     
            <div class="form-control" style='width:80%'>
                <label for="remarks">Notes</label>
                <input type="text" name="remarks" id="remarks">
             </div>
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
     <button type="submit" name="submit" class="btn">Save Record</button>
    
</tr>
    </form>
 
</div>
<?=template_admin_footer()?>