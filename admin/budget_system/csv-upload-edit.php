<?php
//CSV Edit 11/13/2024  
// Ensure csv_upload "ID" param exists
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

$stmt =$budget_pdo->prepare('SELECT * FROM csv_upload');
$stmt->execute();
$csv_upload = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $budget_pdo->prepare('SELECT * FROM budget');
$stmt->execute();
$budget = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $budget_pdo->prepare('SELECT * FROM flags');
$stmt->execute();
$flags = $stmt->fetchAll(PDO::FETCH_ASSOC);

//grab what's in the database
$stmt = $budget_pdo->prepare('SELECT * FROM csv_upload WHERE id=?');
$stmt->execute([$_GET['id']]);
$csv_upload_id  = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$csv_upload_id) {
    exit('bill doesn\'t exist with that ID!');
}
$date               = '2099-12-30';
$check_number       = '';
$transaction_type   = '';
$description        = 'No description';
$debits             = 0.00;
$credits            = 0.00;
//default values
$csv_upload = [ 'date' =>        $date,
          'check_number' =>      $check_number,
          'transaction_type' =>  $transaction_type,
          'description' =>       $description,
          'debits' =>            $debits,
          'credits' =>           $credits,
    ];     
//if parm exists, edit the bill
if (isset($_POST['description'])){
     // Validate form data
    if (empty($_POST['description'])) {
        $error_msg = 'Please fill out the description!';
    } 
    // If no validation errors, proceed to insert the record(s) in the database
    if (!$error_msg) {
        // Update the record
            $stmt = $budget_pdo->prepare('UPDATE csv_upload 
            SET
            date                = ?,
            check_number        = ?,
            transaction_type    = ?,
            description         = ?,
            debits              = ?,
            credits             = ?
            WHERE id            = ?');
            $stmt->execute([
            $_POST['date'],
            $_POST['check_number'],
            $_POST['transaction_type'],            
            $_POST['description'],
            $_POST['debits'],
            $_POST['credits'],
            $_GET['id'] ]);
        // Get the updated bill from the csv_upload table
        $stmt = $budget_pdo->prepare('SELECT * FROM csv_upload WHERE id = ?');
        $stmt->execute([ $_GET['id'] ]);
        $csv_upload_id= $stmt->fetch(PDO::FETCH_ASSOC);
          // Output message
        $success_msg = 'Updated Successfully!';
   }
 }  
?>
<?=template_admin_header('View csv_upload', 'budget', 'manage')?>
<div class="content update">
    <div class="page-title">
		<i class="fa-solid fa-user fa-lg"></i>
		<div class="wrap">
		 <h2>Update <?=$csv_upload_id['description']?></h2> 
		 	<p>Fill in the form below and submit, to update bill # <?=$csv_upload_id['id']?>.</p>
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
<?php
$description = substr($csv_upload_id['description'], 0, 15);
?>
<tr>
<td>
       <div class="form-control" style='width:20%'>
        <label for="date">Transaction Date</label>
        <input type="datetime-local" name="date" id="date" value="<?=date('Y-m-d\TH:i', strtotime($csv_upload_id['date']??'2025-01-30'))?>" required>
      </div>
</td>  
<td>               
            <div class="form-control" style='width:80%'>
                <label for="description">Bank Description</label>
                <input type="text" name="description" id="description" value="<?=$description ?>" required>
             </div>
</td>
</tr>

<tr>	
<td>
              <div class="form-control">
                <label for="date">Transaction Type</label>
                <input type="text" name="transaction_type"  id="transaction_type" value="<?=$csv_upload_id['transaction_type'] ?>">
              </div>
</td>
 
<td>               
            <div class="form-control" style='width:80%'>
                <label for="description">Check Number</label>
                <input type="text" name="check_number" id="check_number" value='<?=$csv_upload_id['check_number']?>' >
             </div>
</td>


</tr>
 
<tr>  
<td>
         	<div class="form-control" style='width:50%'>
                <label for="debits">Debits</label>
                <input type="number" name="debits"  id="debits" value="<?=$csv_upload_id['debits']?>" placeholder="" required>
             </div>
</td>
<td>
         	<div class="form-control" style='width:50%'>
                <label for="credits">Credits</label>
                <input type="number" name="credits"  id="credits" value="<?=$csv_upload_id['credits']?>" placeholder="" required>
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
   <a href="csv-upload-browse.php" class="btn alt mar-right-2">Cancel</a>
        <button type="submit" name="submit" class="btn">Save Record</button>
</div>
</form>
</div>
<?=template_admin_footer()?>