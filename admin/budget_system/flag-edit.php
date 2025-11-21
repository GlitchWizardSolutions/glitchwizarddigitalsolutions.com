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
$flag='';
$flags_id='';
$description='';
$autopay_flag='';
//default values
$flags = [  
          'flag' =>      NULL,
          'description' =>       NULL,
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
$stmt = $budget_pdo->prepare('SELECT * FROM flags  WHERE id = ?');
$stmt->execute([$_GET['id']]);
$flag  = $stmt->fetch(PDO::FETCH_ASSOC);
 

if (!$flag) {
    exit('flag doesn\'t exist with that ID!');
}
//if parm exists, edit the transaction
if (isset($_POST['description'])){

     // Validate form data
    if (empty($_POST['description'])) {
        $error_msg = 'Please fill out the form!';
    } 

     // If no validation errors, proceed to insert the record(s) in the database
    if (!$error_msg) {

        // Update the record
            $stmt = $budget_pdo->prepare('UPDATE flags 
            SET
            flag          = ?,
            description   = ?
            WHERE id      = ?');
            $stmt->execute([
            $_POST['flag'],
            $_POST['description'],
            $_GET['id'] ]);
        // Get the updated record from the flags table
        $stmt = $budget_pdo->prepare('SELECT * FROM flags WHERE id = ?');
        $stmt->execute([ $_GET['id'] ]);
        $result= $stmt->fetch(PDO::FETCH_ASSOC);
        // Output message
        $success_msg = 'Updated Successfully!';
   }
    header("Location: flags-browse.php");
    exit;

}  

?>
<?=template_admin_header('Budget System', 'budget', 'flags')?>
<div class="content update">
<div class="page-title">
		<i class="fa-solid fa-user fa-lg"></i>
		<div class="wrap">
		 <h2>Update  <?=$flag['description']?></h2> 
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
        <label for="flag">Flag</label>
        <input type="text" name="flag" id="flag" value="<?=htmlspecialchars($flag['flag'], ENT_QUOTES)?>" required>
      </div>
</td>      

<td>
      <div class="form-control" style='width:80%'>
        <label for="description">Description</label>
        <input type="text" name="description" id="description" value="<?=htmlspecialchars($flag['description'], ENT_QUOTES)?>" required>
        </div>
</td>
</tr>

</tbody>
</table>
<a href="flags-browse.php" class="btn alt mar-right-2">Cancel</a>
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