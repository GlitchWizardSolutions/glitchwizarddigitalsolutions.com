<?php
include_once 'assets/includes/admin_config.php';
// Connect to MySQL database
$budget_pdo = pdo_connect_budget_db($db_host, $db_name7, $db_user, $db_pass);
// Error message
$error_msg = ''; 
// Success message
$success_msg = NULL;
$stmt =$budget_pdo->prepare('SELECT * FROM hancock ORDER BY date DESC  LIMIT 6');
$stmt->execute();
$hancocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?=template_admin_header('Budget System', 'budget', 'process')?>
<div class="content read">
	<div class="page-title">
		<i class="fa-regular fa-address-book fa-lg"></i>
		<div class="wrap">
			<h2>Instructions</h2>
			<p>STEP 1 - Preparation for Data Import</p>
		</div>
	</div>
<h3><strong>1. Compare last transaction dates in transaction table, below, to determine what the start date is for the csv file request.</strong></h3><br>
		<div class="table">
			<table>
				<thead>
					<tr>
 					    <td style="text-align: center;">Date</td>
                        <td>Description</td>	
                        <td>Comment</td>
					    <td style="text-align: center;">Debits</td>
					    <td style="text-align: center;">Credits</td>
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
						<td class="date"><?=date("m/d/y", strtotime($result['date'])?? '')?></td> 
					    <td><?=htmlspecialchars($result['description']?? '', ENT_QUOTES)?></td>
					    <td class="comment left"><?=htmlspecialchars($result['comment']?? '', ENT_QUOTES)?></td>
					    <td class="debits right"><?=htmlspecialchars($result['debits']?? '', ENT_QUOTES)?></td>
					    <td class="credits right"><?=htmlspecialchars($result['credits']?? '', ENT_QUOTES)?></td>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

<br><h3><strong>2. Download csv file of transactions.</strong></h3> 
                                <a href='https://www.hancockwhitney.com/' target="_blank" class='btn btn-sm'>Open Hancock Bank</a>
                                <ul>
                                    <li>
                                          <h5><strong>Login: Sys#1/B!H</strong></h5>
                                    </li>
                                     <li>
                                          <h5><strong>Request File: Name it YYYY-MON-dd-dd.csv and download to download folder.</strong></h5> 
                                     </li>
                                 </ul>        
                                   <br>
<br><h3><strong>3. Upload the csv file to the applicaiton.</strong></h3> 
<div style="background: #e8f5e9; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #4CAF50;">
    <h3 style="color: #2e7d32; margin-top: 0;">
        <i class="fa-solid fa-lightbulb"></i> <strong>NEW: Automated CSV Upload Available!</strong>
    </h3>
    <p style="font-size: 16px; margin: 10px 0;">
        Skip the manual Excel editing and phpMyAdmin steps. Upload your CSV directly and let the system handle everything!
    </p>
    <a href='<?=budget_link_path?>csv-upload-auto.php' class='btn' style='background:#4CAF50; color:white; padding: 12px 24px; font-size: 16px; text-decoration: none; border-radius: 4px; display: inline-block; margin-top: 10px;'>
        <i class="fa-solid fa-upload"></i> Use Automated CSV Upload
    </a>
</div>

<a href='<?=budget_link_path?>instructions-p2.php' style='background:yellow; color:black' class='btn btn-sm'>>>> NEXT >>></a><br>                                    
</div>
<style>
    .right{
        text-align: right;
    }
</style>
<?=template_admin_footer()?>