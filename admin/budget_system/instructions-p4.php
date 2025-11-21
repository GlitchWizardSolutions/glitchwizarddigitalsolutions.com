 <?php
include_once 'assets/includes/admin_config.php';
// Connect to MySQL database
$budget_pdo = pdo_connect_budget_db($db_host, $db_name7, $db_user, $db_pass);
// Error message
$error_msg = ''; 
// Success message
$success_msg = NULL;
$date = date("Y-m-d",strtotime(date("Y-m-d"))); 
$stmt =$budget_pdo->prepare('SELECT * FROM hancock WHERE date_updated >= ? ORDER BY date DESC' );
$stmt->execute([$date]);
$hancocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

//YOU MUST CLICK SUBMIT TO RUN THIS PROCESS

if(isset($_POST['submit']) ){
$stmt =$budget_pdo->prepare('DELETE FROM csv_upload' );
$stmt->execute();
$stmt =$budget_pdo->prepare('DELETE FROM csv_process' );
$stmt->execute();
$stmt =$budget_pdo->prepare('SELECT * FROM csv_upload' );
$stmt->execute();
$csv_upload = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt =$budget_pdo->prepare('SELECT * FROM csv_process' );
$stmt->execute();
$csv_process = $stmt->fetchAll(PDO::FETCH_ASSOC);
if(!$csv_process && !$csv_upload){
$success_msg = 'Deleted Successfully!';
}else{
$error_msg = 'Delete Failed!';    
}
}//End Submit Form
?>
<?=template_admin_header('Budget System', 'budget', 'process')?>
<div class="content read">
	<div class="page-title">
		<i class="fa-regular fa-address-book fa-lg"></i>
		<div class="wrap">
			<h2>Instructions</h2>
			<p>STEP 4 - Verify the results are as expected.</p>
		</div>
	</div>
<h3><strong>1. View today's uploaded results in the hancock table.</strong></h3><br>
		<div class="table">
			<table>
				<thead>
					<tr>
					    <td>Id</td>
 					    <td>Date</td>
                        <td class="responsive-hidden">Description</td>	
                        <td>Comment</td>
					    <td>Debits</td>
					    <td>Credits</td>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($hancocks)): ?>
					<tr>
						<td colspan="10" style="text-align:center;">There are no records.</td>
					</tr>
					<?php endif; ?>
					<?php foreach ($hancocks as $result): ?>
			 
					<tr>
					    <td class="id left"><?=htmlspecialchars($result['id']?? '', ENT_QUOTES)?></td>
						<td class="date">&nbsp;<?=date("m/d/y", strtotime($result['date'])?? '')?></td> 
					    <td class="responsive-hidden"><?=htmlspecialchars($result['description']?? '', ENT_QUOTES)?></td>
					    <td class="comment right"><?=htmlspecialchars($result['comment']?? '', ENT_QUOTES)?></td>
					    <td class="debits right"><?=htmlspecialchars($result['debits']?? '', ENT_QUOTES)?></td>
					    <td class="credits right"><?=htmlspecialchars($result['credits']?? '', ENT_QUOTES)?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

<br><h3><strong>2. The red button below will delete the records from the database tables csv_uploads, csv_process.</strong></h3>                                     
 
    <form action="" method="post" class="crud-form">
      <?php if ($error_msg): ?>
        <p class="msg-error"><?=$error_msg?></p>
        <?php endif; ?>

        <?php if ($success_msg): ?>
        <p class="msg-success"><?=$success_msg?></p>
        <?php endif; ?>
 <a href='<?=budget_link_path?>instructions-p3.php' style='background:grey; color:white' class='btn btn-sm'><<< BACK <<<<</a>&nbsp;&nbsp;        
 <button type="submit" name="submit" class="btn" style='background:red'>Delete the records!</button>&nbsp;&nbsp; 
 <a href='<?=budget_link_path?>reports/mom-hancock-report.php' style='background:yellow; color:black' class='btn btn-sm'>>>> REPORTING SYSTEM >>></a> 
    </form>
</div>
<style>
    .right{
        text-align: right;
    }
</style>
<?=template_admin_footer()?>