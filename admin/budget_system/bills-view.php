<?php
//BillsView 11/21/2024
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

$stmt =$budget_pdo->prepare('SELECT * FROM bills');
$stmt->execute();
$bills = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
?>
<?=template_admin_header('Budget System', 'budget', 'bills')?>
<div class="content update">
    <div class="page-title">
		<i class="fa-solid fa-user fa-lg"></i>
		<div class="wrap">
		 <h2>View <?=$bill_id['bill']?></h2> 
		 	<p>Details of bill # <?=$bill_id['id']?>.</p>
		</div>
	</div>
    <form class="crud-form">
           <a href="bills-browse.php" class="btn mar-right-2">Return</a>
   	<div class="table">
 <table>
	<tbody>
<tr>	
    <td>    
              <div class="form-control" style='width:80%'>
                    <label for="bill">Bill Name</label>
                    <input type="text" value='<?=$bill_id['bill']?>' name= "bill" id="bill" disabled>
                </div>
</td>
<td>               
            <div class="form-control" style='width:80%'>
                <label for="description">Description</label>
                <input type="text" name="description" id="description" value="<?=htmlspecialchars($bill_id['description']??'', ENT_QUOTES)?>" disabled>
             </div>
</td>
</tr>
<tr>  
<td>               
            <div class="form-control" style='width:80%'>
                <label for="hancock">Bank Reference</label>
                <input type="text" name="hancock" id="hancock" value="<?=htmlspecialchars($bill_id['hancock']??'', ENT_QUOTES)?>" disabled>
             </div>
</td>
 
<?php $stmt = $budget_pdo->prepare('SELECT * FROM flags WHERE id=?');
$stmt->execute([$bill_id['flags_id']]);
$flags = $stmt->fetch(PDO::FETCH_ASSOC); ?>
<td>               
            <div class="form-control" style='width:80%'>
                <label for="flags_id">Flag</label>
                <input type="text" name="flags_id" id="flags_id" value="<?=htmlspecialchars($flags['description']??'', ENT_QUOTES)?>" disabled>
             </div>
</td>

</tr>
<tr>
    <?php $stmt = $budget_pdo->prepare('SELECT * FROM budget WHERE id=?');
$stmt->execute([$bill_id['budget_id']]);
$budgets = $stmt->fetch(PDO::FETCH_ASSOC); ?>
<td>               
            <div class="form-control" style='width:80%'>
                <label for="budget_id">Budget</label>
                <input type="text" name="budget_id" id="budget_id" value="<?=htmlspecialchars($budgets['budget']??'', ENT_QUOTES)?>" disabled>
             </div>
</td>
 <td>               
            <div class="form-control" style='width:80%'>
                <label for="autopay_flag">AutoPay</label>
                <input type="text" name="autopay_flag" id="autopay_flag" value="<?=htmlspecialchars($bill_id['autopay_flag']??'', ENT_QUOTES)?>" disabled>
             </div>
</td>
  

<tr>
    <td>
              <div class="form-control">
                <label for="last_paid_date">Last Paid Date</label>
                <input type="datetime-local" name="last_paid_date"  id="last_paid_date" value="<?=date('Y-m-d\TH:i', strtotime($bill_id['last_paid_date']))?>" disabled>
              </div>
</td>
<td>
         	<div class="form-control" style='width:50%'>
                <label for="last_paid_amount">Last Paid $</label>
                <input type="number" name="last_paid_amount"  id="last_paid_amount" value="<?=$bill_id['last_paid_amount']??0 ?>" placeholder="" disabled>
             </div>
</td>

 </tr>
 <tr>
     <td>
            <div class="form-control">
                <label for="next_due_date">Next Due Date</label>
               <input type="datetime-local" name="next_due_date"  id="next_due_date" placeholder="Next Due Date"  value="<?=date('Y-m-d\TH:i', strtotime($bill_id['next_due_date']))?>" disabled>
            </div>
</td>
<td>
      	  <div class="form-control" style='width:50%'>
                <label for="next_due_amount">Next Due Amount</label>
                <input type="number" name="next_due_amount"  id="next_due_amount"value="<?=$bill_id['next_due_amount']??0 ?>" placeholder="" disabled>
             </div>
</td>
 

</tr>

 

<tr>
 <td colspan=2> 
           <div class="form-control" style='width:100%; text-align:center; background:gray; color:white'>
                <label for="remarks">Notes</label>
                <input type="text" name="remarks" id="remarks" style='margin:20px' value="<?=$bill_id['remarks']??0 ?>" disabled> 
            </div>
</td>
</tr>
  </tbody>
 </table>
   <a href="bills-browse.php" class="btn alt mar-right-2">Return</a>
</div>
</form>
</div>
<?=template_admin_footer()?>