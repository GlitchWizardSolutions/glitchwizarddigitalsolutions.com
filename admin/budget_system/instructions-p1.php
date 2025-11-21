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
<br><h3><strong>3. Edit CSV file for importing into database.</strong></h3> 	                                        
                                         <ul>
                                             <li>
                                                <h5><strong>Open Downloads folder</strong></h5>
                                            </li>
                                             <li>
                                                <h5><strong>Delete first row</strong></h5>
                                            </li>
                                            <li>
                                                <h5><strong>Add 0 into blank fields of debits and credits.</strong></h5>
                                            <li>
                                                <h5><strong>Format date to YYYY-MM-DD.</strong></h5>
                                            </li>
                                            <li>
                                                <h5><strong>Close bank browser, close edited csv file.</strong></h5>
                                            </li>
                                         </ul>
                                   <br>
<br><h3><strong>4. Import the csv file into csv_upload table.</strong></h3>                                     
<a href='https://glitchwizardsolutions.com:2083/' target="_blank"  class='btn btn-sm'>Log into Database</a><br>   
                                    <ul>
                                        <li>
                                             <h4><strong>In glitchwizarddigi_budget_2025, clear the csv_upload table.</strong></h4>
                                        </li>
                                        <li>
                                            <h4><strong>In the same table, go to import, select the edited csv file.</strong></h4>
                                        </li> 
                                        <li>
                                            <h2><strong>Copy this to clipboard: <br><strong  style='color:blue'>date,check_number,transaction_type,description,debits,credits</strong><br></h2>
                                        </li> 
                                        <li>
                                         <h4><strong>Paste into bottom form field.  Submit.</strong></h4>
                                        </li>
                                    </ul>
<a href='<?=budget_link_path?>instructions-p2.php' style='background:yellow; color:black' class='btn btn-sm'>>>> NEXT >>></a><br>                                    
</div>
<style>
    .right{
        text-align: right;
    }
</style>
<?=template_admin_footer()?>