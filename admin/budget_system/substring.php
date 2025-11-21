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
$reference_id='';
$flags_id='';
$description='';
$autopay_flag='';
// Get the transactions from the csv table
$stmt = $budget_pdo->prepare('SELECT * FROM csv_upload');
$stmt->execute();
$transactions  = $stmt->fetchAll(PDO::FETCH_ASSOC);

 if(isset($_POST['submit']) ){
//LOOP THROUGH csv table TABLE 
foreach($transactions as $row){
$description= substr($row['description']??'', 0, 15);
$id=$row['id'];
                // Update the record
                 $stmt = $budget_pdo->prepare('UPDATE csv_upload 
                    SET
                     description = ?
                     WHERE id = ?');
                     $stmt->execute([
                        $description,
                        $id]);               
 }//END FOR EACH TRANSACTIONS AS ROW
}//END SUBMIT
?>
<?=template_admin_header('Edit CSV file', 'budget', 'create')?>

<div class="content update">

    <div class="page-title">
		<i class="fa-solid fa-user fa-lg"></i>
		<div class="wrap">
			<h2>Truncates the bank description into 15 characters.</h2>
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

</tr>
  </tbody>
 </table>
</div>
<tr>
 
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