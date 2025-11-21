<style>
    .right{
        text-align: right;
    }
</style>
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

$stmt = $budget_pdo->prepare('SELECT * FROM budget');
$stmt->execute();
$budget = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $budget_pdo->prepare('SELECT * FROM flags');
$stmt->execute();
$flags = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Error message
$error_msg = ''; 
// Success message
$success_msg = '';

 
if (isset($_POST['bill'])) {
    // Validate form data
    $data = [
             'bill' => $_POST['bill'],
             'description' => $_POST['description'],
             'budget_id' => $_POST['budget_id'],
            
             'flags_id' => $_POST['flags_id'],
             'remarks' => $_POST['remarks'],
             'autopay_flag' => $_POST['autopay_flag'],
             
             'next_due_date' => $_POST['next_due_date'],
             'next_due_amount' => $_POST['next_due_amount'],
             'last_paid_date' => date('Y-m-d H:i:s', strtotime($_POST['last_paid_date'])),
             'last_paid_amount' => $_POST['last_paid_amount']
    ];
   
    if (empty($data['bill']) || empty($data['description']) || empty($data['budget_id'])
         || empty($data['flags_id']) || empty($data['autopay_flag'])) 
        {
        $error_msg = 'Please fill out all required fields!';
    } 
    // If no validation errors, proceed to insert the record(s) in the database
    if (!$error_msg) {
  
        // Insert the records
         $stmt = $budget_pdo->prepare('INSERT INTO `bills`(bill, description, budget_id, flags_id, remarks, autopay_flag, next_due_date, next_due_amount, last_paid_date, last_paid_amount) VALUES (?, ?, ?, ?, ?,  ?, ?, ?, ? ,? )');
         $stmt->execute([$data['bill'], $data['description'], $data['budget_id'], $data['flags_id'], $data['remarks'], $data['autopay_flag'], $data['next_due_date'], $data['next_due_amount'], $data['last_paid_date'], $data['last_paid_amount']]);
       // Output message
        $success_msg = 'Created Successfully!';
    }
}   


?>
<?=template_admin_header('Budget System', 'budget', 'bills')?>
<div class="content update">

       <div class="page-title">
		<i class="fa-solid fa-user fa-lg"></i>
		<div class="wrap">
			<h2>Create New Bill</h2>
			<p>Create a bill in the database, fill in the form below and submit.</p>
		</div>
 </div>
    <form action="" method="post" class="crud-form" style='background:#F8F6F6'>
                <a href="bills-browse.php" class="btn mar-right-2">Return</a>
   	<div class="table">
	  <table>

	<tbody>
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
                <input  class="right" type="number" name="last_paid_amount" id="last_paid_amount" value="0" placeholder="" required>
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
                <input type="number" class="right" name="next_due_amount" id="next_due_amount" value="0" placeholder="">
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

</div>

<?=template_admin_footer()?>