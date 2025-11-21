<?php
//Bills Edit 11/13/2024
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
$bill = [ 'bill' =>             '',
          'description' =>      '',
          'budget_id' =>        23,
          'hancock' =>          '',
          'flags_id' =>         3,
          'remarks' =>          '',
          'autopay_flag' =>     'No',
          'next_due_date' =>    '2099-12-30',
          'next_due_amount' =>  0,
          'last_paid_date' =>   '2099-12-30',
          'last_paid_amount' => 0
    ];     
$stmt =$budget_pdo->prepare('SELECT * FROM bills');
$stmt->execute();
$bills = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $budget_pdo->prepare('SELECT * FROM bank_reference');
$stmt->execute();
$bank_reference = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $budget_pdo->prepare('SELECT* FROM hancock WHERE bill_id=?');
$stmt->execute([$_GET['id']]);
$hancock = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $budget_pdo->prepare('SELECT * FROM budget');
$stmt->execute();
$budget = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $budget_pdo->prepare('SELECT * FROM flags');
$stmt->execute();
$flags = $stmt->fetchAll(PDO::FETCH_ASSOC);

//grab what's in the database
$stmt = $budget_pdo->prepare('SELECT * FROM bills WHERE id=?');
$stmt->execute([$_GET['id']]);
$bill_id  = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$bill_id) {
    exit('bill doesn\'t exist with that ID!');
}
//if parm exists, edit the bill
if (isset($_POST['submit'])){
     // Validate form data
    if (empty($_POST['bill'])) {
        $error_msg = 'Please fill out the name of the bill!';
    } 
    // If no validation errors, proceed to insert the record(s) in the database
    if (!$error_msg) {
        // Update the record
            $stmt = $budget_pdo->prepare('UPDATE bills 
            SET
            bill            = ?,
            description     = ?,
            budget_id       = ?,
            hancock         = ?,
            flags_id        = ?,
            remarks         = ?, 
            autopay_flag    = ?,
            next_due_date   = ?,
            next_due_amount = ?, 
            last_paid_date  = ?,
            last_paid_amount= ?             
            WHERE id        = ?');
            $stmt->execute([
            $_POST['bill'],
            $_POST['description'],
            $_POST['budget_id'],
            $_POST['hancock'],
            $_POST['flags_id'],
            $_POST['remarks'],
            $_POST['autopay_flag'],
            $_POST['next_due_date'],
            $_POST['next_due_amount'],
            $_POST['last_paid_date'],
            $_POST['last_paid_amount'],
            $_GET['id'] ]);
        // Get the updated bill from the bills table
        $stmt = $budget_pdo->prepare('SELECT * FROM bills WHERE id = ?');
        $stmt->execute([ $_GET['id'] ]);
        $bill_id= $stmt->fetch(PDO::FETCH_ASSOC);
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
		 <h2>Update <?=$bill_id['bill']?></h2> 
		 	<p>Fill in the form below and submit, to update bill # <?=$bill_id['id']?>.</p>
		</div>
	</div>
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
              <div class="form-control" style='width:80%'>
                    <label for="bill">Bill Name</label>
                    <input type="text" value='<?=$bill_id['bill']?>' name= "bill" id="bill" required>
                </div>
</td>
<td>               
            <div class="form-control" style='width:80%'>
                <label for="description">Description</label>
                <input type="text" name="description" id="description" value="<?=htmlspecialchars($bill_id['description'], ENT_QUOTES)?>" required>
             </div>
</td>
</tr>
<tr>  
 <td>   
              <div class="form-control" style='width:80%'>
                <label for="hancock">Bank Reference</label>
                <select name="hancock" id="hancock"> 
            <option value='<?=$bill_id['hancock'] ?>' selected><?=$bill_id['hancock']?></option>
            <?php foreach($hancock as $row) :?>
             <?php 
                    $selected='';
                    $value=$row['description'];
                    $match=$bill_id['id'];
                    $match1=$row['bill_id'];
                    if($match1==$match){
                       $selected='selected';
                    }
               ?>
             <option value='<?=$row['description'] ?>'<?=$selected;?>><?=$row['description']?></option>
            <?php endforeach ?>
                </select>
            </div>
 </td>

</td>
           <td 
           <div class="form-control" style='width:80%'>
                <label for="flags_id">Flag</label>
                <select name="flags_id" id="flags_id" required>
            <?php foreach($flags as $row) :?>
            <?php 
                    $selected='';
                    $value=$row['id'];
                    $match=$bill_id['flags_id'];
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
                <label for="budget_id">Budget Bucket</label>
                <select name="budget_id" id="budget_id" required> 
                
            <?php foreach($budget as $row) :?>
             <?php 
                    $selected='';
                    $value=$row['id'];
                    $match=$bill_id['budget_id'];
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
          <div class="form-control" style='width:25%'>
                <label for="autopay_flag">AutoPay</label>
                <select name="autopay_flag" id="autopay_flag"required>
                    <option value="Y"<?=$bill_id['autopay_flag']=='Y'?' selected':''?>>Yes</option>
                    <option value="N"<?=$bill_id['autopay_flag']=='N'?' selected':''?>>No</option>
                </select>
            </div> 
</td>

<tr>
    <td>
              <div class="form-control">
                <label for="last_paid_date">Last Paid Date</label>
                <input type="datetime-local" name="last_paid_date"  id="last_paid_date" value="<?=date('Y-m-d\TH:i', strtotime($bill_id['last_paid_date']))?>" required>
              </div>
</td>
<td>
         	<div class="form-control" style='width:50%'>
                <label for="last_paid_amount">Last Paid $</label>
                <input type="number" name="last_paid_amount"  id="last_paid_amount" value="<?=$bill_id['last_paid_amount']?>" placeholder="" required>
             </div>
</td>

 </tr>
 <tr>
     <td>
            <div class="form-control">
                <label for="next_due_date">Next Due Date</label>
               <input type="datetime-local" name="next_due_date"  id="next_due_date" placeholder="Next Due Date"  value="<?=date('Y-m-d\TH:i', strtotime($bill_id['next_due_date']))?>">
            </div>
</td>
<td>
      	  <div class="form-control" style='width:50%'>
                <label for="next_due_amount">Next Due Amount</label>
                <input type="number" name="next_due_amount"  id="next_due_amount"value="<?=$bill_id['next_due_amount']?>" placeholder="" required>
             </div>
</td>
 

</tr>

 

<tr>
 <td colspan=2> 
           <div class="form-control" style='width:100%; text-align:center; background:gray; color:white'>
                <label for="remarks">Notes</label>
                <input type="text" name="remarks" id="remarks" style='margin:20px' value="<?=$bill_id['remarks']?>"> 
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
   <a href="bills_browse.php" class="btn alt mar-right-2">Cancel</a>
        <button type="submit" name="submit" class="btn">Save Record</button>
</div>
</form>
</div>
<?=template_admin_footer()?>