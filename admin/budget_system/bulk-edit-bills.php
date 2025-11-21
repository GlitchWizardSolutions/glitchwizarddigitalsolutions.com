<?php // does not work as intended.  all rows were the first one.
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

$stmt = $budget_pdo->prepare('SELECT hancock FROM bills');
$stmt->execute();
$bill_hancock = $stmt->fetchAll(PDO::FETCH_ASSOC);

$hancock = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $budget_pdo->prepare('SELECT * FROM budget');
$stmt->execute();
$budget = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $budget_pdo->prepare('SELECT * FROM flags');
$stmt->execute();
$flags = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (isset($_POST['submit'])){

        
        
        
        foreach ($bill_hancock as $row){
            $id_in = $row['id'];
            $flag_in = $row['flags_id'];
            $budget_in = $row['budget_id'];
            $hancock = $budget_pdo->query('SELECT description FROM hancock WHERE bill_id = $id_in and flags_id = $flag_in and budget_id = $budget_in Limit 1')->fetch(PDO::FETCH_ASSOC);
          
            $value = substr($hancock['description']??'none', 0, 15); 
            $stmt = $budget_pdo->prepare('UPDATE bills 
            SET
            hancock             = ?');
            $stmt->execute([
            $value
            ]);
            
        }
   }

?>
<?=template_admin_header('View csv_upload', 'budget', 'manage')?>
<div class="content update">
    <div class="page-title">
		<i class="fa-solid fa-user fa-lg"></i>
		<div class="wrap">
		 <h2>Bulk bill table Update</h2> 
		 	
		</div>
	</div>
    <form action="" method="post" class="crud-form">
<div class="table">
 <table>
	<tbody>

  </tbody>
 </table>
   <a href="bulk-edit-bills.php" class="btn alt mar-right-2">Cancel</a>
        <button type="submit" name="submit" class="btn">Submit</button>
</div>
</form>
</div>
<?=template_admin_footer()?>