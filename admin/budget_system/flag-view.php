<?php
//BillsView 11/21/2024
// Ensure flags "ID" param exists
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
 
$stmt = $budget_pdo->prepare('SELECT * FROM flags');
$stmt->execute();
$flags = $stmt->fetchAll(PDO::FETCH_ASSOC);

//grab what's in the database
$stmt = $budget_pdo->prepare('SELECT * FROM flags WHERE id=?');
$stmt->execute([$_GET['id']]);
$flags  = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$flags) {
    exit('flag doesn\'t exist with that ID!');
}
?>
<?=template_admin_header('Budget System', 'budget', 'flags')?>
<div class="content update">
    <div class="page-title">
		<i class="fa-solid fa-user fa-lg"></i>
		<div class="wrap">
		 <h2>View <?=$flags['description']?></h2> 
		 	<p>Details of flag # <?=$flags['id']?>.</p>
		</div>
	</div>
    <form class="crud-form">
           <a href="flags-browse.php" class="btn mar-right-2">Return</a>
   	<div class="table">
 <table>
	<tbody>
<tr>	
    <td>    
              <div class="form-control" style='width:80%'>
                    <label for="flag">Flag</label>
                    <input type="text" value='<?=$flags['flag']?>' name= "flag" id="flag" disabled>
                </div>
</td>
<td>               
            <div class="form-control" style='width:80%'>
                <label for="description">Description</label>
                <input type="text" name="description" id="description" value="<?=htmlspecialchars($flags['description']??'', ENT_QUOTES)?>" disabled>
             </div>
</td>
</tr>
  </tbody>
 </table>
</div>
</form>
</div>
<?=template_admin_footer()?>