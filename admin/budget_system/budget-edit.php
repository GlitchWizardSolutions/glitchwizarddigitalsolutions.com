<?php
//budgets Edit 11/13/2024
// Ensure budget "ID" param exists
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
$budget = [ 'monthly_reserve_flag' => 1,
            'budget'               => 'Error',
            'amount'               => 0.00,
            'balance'              => 0.00,
            'description'          => '',
            'reference'            => ''
    ];     

$stmt = $budget_pdo->prepare('SELECT DISTINCT description FROM hancock WHERE budget_id=? ORDER BY description');
$stmt->execute([$_GET['id']]);
$hancock = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $budget_pdo->prepare('SELECT * FROM budget');
$stmt->execute();
$budget = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $budget_pdo->prepare('SELECT * FROM flags');
$stmt->execute();
$flags = $stmt->fetchAll(PDO::FETCH_ASSOC);

//grab what's in the database
$stmt = $budget_pdo->prepare('SELECT * FROM budget WHERE id=?');
$stmt->execute([$_GET['id']]);
$budget_id  = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$budget_id) {
    exit('budget doesn\'t exist with that ID!');
}
//if parm exists, edit the budget
if (isset($_POST['submit'])){
     // Validate form data
    if (empty($_POST['budget'])) {
        $error_msg = 'Please fill out the name of the budget!';
    } 
    // If no validation errors, proceed to insert the record(s) in the database
    if (!$error_msg) {
        // Update the record
            $stmt = $budget_pdo->prepare('UPDATE budget 
            SET
            monthly_reserve_flag = ?,
            budget      = ?,
            amount      = ?,
            balance     = ?,
            description = ?
            WHERE id    = ?');
            $stmt->execute([
            $_POST['monthly_reserve_flag'],
            $_POST['budget'],
            $_POST['amount'],
            $_POST['balance'],
            $_POST['description'],
            $_GET['id'] ]);
        // Get the updated budget from the budget table
        $stmt = $budget_pdo->prepare('SELECT * FROM budget WHERE id = ?');
        $stmt->execute([ $_GET['id'] ]);
        $budget_id= $stmt->fetch(PDO::FETCH_ASSOC);
          // Output message
        $success_msg = 'Updated Successfully!';
   }
}  
?>
<?=template_admin_header('View budgets', 'budget', 'manage')?>
<div class="content update">
    <div class="page-title">
		<i class="fa-solid fa-user fa-lg"></i>
		<div class="wrap">
		 <h2>Update <?=$budget_id['budget']?></h2> 
		 	<p>Fill in the form below and submit, to update budget # <?=$budget_id['id']?>.</p>
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
<tr><td>
              <div class="form-control" style='width:25%'>
                <label for="monthly_reserve_flag">Display on Reports</label>
                <select name="monthly_reserve_flag" id="monthly_reserve_flag"required>
                    <option value="1"<?=$budget_id['monthly_reserve_flag']=='1'?' selected':''?>>Yes</option>
                    <option value="0"<?=$budget_id['monthly_reserve_flag']=='0'?' selected':''?>>No</option>
                </select>
            </div> 
</td> 	
    <td>    
              <div class="form-control" style='width:80%'>
                    <label for="budget">budget Name</label>
                    <input type="text" value='<?=$budget_id['budget']?>' name= "budget" id="budget" required>
                </div>
</td>
</tr>
<tr>  
<td>   
              <div class="form-control" style='width:80%'>
                <label for="hancock">Bank Reference</label>
                <select name="hancock" id="hancock"> 
            <option value='<?=isset($budget_id['hancock']) ? $budget_id['hancock'] : '' ?>' selected><?=isset($budget_id['hancock']) ? $budget_id['hancock'] : 'Select...'?></option>
            <?php foreach($hancock as $row) :?>
             <?php 
                    $selected='';
                    $value=$row['description'];
                    $match=$budget_id['id'];
                    $match1=$row['budget_id'];
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
</tr>
<tr>
<tr>
<td>
         	<div class="form-control" style='width:50%'>
                <label for="amount">Monthly Amount</label>
                <input type="number" name="amount"  id="amount" value="<?=$budget_id['amount']?>" step="0.01" required>
             </div>
</td>
</tr>
<tr>
 <td><p style='color:red'>This is updated via web applications.</p>
      	  <div class="form-control" style='width:50%'>
                <label for="balance">Remaining Balance</label>
                <input type="number" name="balance"  id="balance"value="<?=$budget_id['balance']?>" step="0.01" required>
             </div>
</td>
</tr>
<tr>
<td colspan=2>              
            <div class="form-control" style='width:100%; text-align:center; background:gray; color:white'>
                <label for="description">Description</label>
                <input type="text" name="description" id="description" value="<?=htmlspecialchars($budget_id['description'], ENT_QUOTES)?>" required>
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
   <a href="budget-browse.php" class="btn alt mar-right-2">Cancel</a>
        <button type="submit" name="submit" class="btn">Save Record</button>
</div>
</form>
</div>
<?=template_admin_footer()?>