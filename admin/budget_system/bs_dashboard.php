<?php
//Begin Transition to hancock table from running_balance table 11/19/2024
//Select month, year, sum(value) from mytable group by month, year
//select year, sum(value) from mytable where month <= selectedMonth group by year
//$time=strtotime($dateValue);
//$month=date("F",$time);
//$year=date("Y",$time);
include_once 'assets/includes/admin_config.php';
include_once '../assets/includes/components.php';
// Connect to MySQL database
$budget_pdo = pdo_connect_budget_db($db_host, $db_name7, $db_user, $db_pass);
$balance=0;
//dashboard
//PRIOR
/*flags type 2 is all money prior to September 2024
$stmt =$budget_pdo->prepare('SELECT b.budget, h.credits 
FROM `hancock` h, budget b 
WHERE 
flags_id=2 
AND b.id=h.budget_id 
ORDER BY `b`.`id` 
');
$stmt->execute();
$reserved_prior = $stmt->fetchAll(PDO::FETCH_ASSOC);
//This is the total of the monthy prior to September 2024
$stmt =$budget_pdo->prepare('SELECT SUM(credits) 
FROM `hancock` 
WHERE flags_id=2 ');
$stmt->execute();
$amount_reserved_prior  = $stmt->fetchColumn();*/
//CURRENT
$stmt =$budget_pdo->prepare('SELECT * FROM flags ORDER BY description DESC');
$stmt->execute();
$flags = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt =$budget_pdo->prepare('SELECT * FROM budget ORDER BY budget DESC');
$stmt->execute();
$budgets = $stmt->fetchAll(PDO::FETCH_ASSOC);

//This tally should always equal the monthly allowance or else there is a problem.
$stmt =$budget_pdo->prepare('SELECT SUM(amount) 
FROM `budget`;');
$stmt->execute();
$total_budget_monthly  = $stmt->fetchColumn();


//This should be the amount reflected in Hancock Bank, minus pending and transactions yet to be loaded.
$stmt =$budget_pdo->prepare('SELECT SUM(balance) 
FROM `budget`;');
$stmt->execute();
$total_budget_balances  = $stmt->fetchColumn();

//This is what is remaining in the Remaining Balance.
$stmt =$budget_pdo->prepare('SELECT balance 
FROM `budget`
WHERE id=23;');
$stmt->execute();
$remaining_balance  = $stmt->fetchColumn();

//This tally is what is in savings.
$stmt =$budget_pdo->prepare('SELECT balance 
FROM `budget`
WHERE
id=25');
$stmt->execute();
$total_savings  = $stmt->fetchColumn();

//This is just the credits, need to update to show current year to date.
$stmt =$budget_pdo->prepare('SELECT SUM(credits) 
FROM `hancock`');
$stmt->execute();
$total_hancock_credits  = $stmt->fetchColumn();
//This is just the credits, need to update to show current year to date.
$stmt =$budget_pdo->prepare('SELECT SUM(debits) 
FROM `hancock`;');
$stmt->execute();
$total_hancock_debits  = $stmt->fetchColumn();
?>
<?=template_admin_header('Budget Dashboard', 'budget', 'dashboard')?>

<?=generate_breadcrumbs([
    ['label' => 'Budget System', 'url' => 'bs_dashboard.php'],
    ['label' => 'Dashboard']
])?>

<div class="content-title">
    <div class="icon"><?=svg_icon_budget()?></div>
    <div class="txt">
        <h2>Show me the MONEY!</h2>
        <p class="subtitle">Budget system dashboard and reports</p>
    </div>
</div>

<div class="content-header responsive-flex-wrap responsive-pad-bot-3">
    <a href='<?=budget_link_path?>instructions-p1.php' class='btn btn-primary'>Process Transactions</a>
    <a href='<?=budget_link_path?>reports/mom-hancock-report.php' class='btn btn-success'>View Monthly Report</a>
</div>

 <div id="budgets" class="dashboard">
    <div class="content-block stat cyan">
        <div class="data">
            <h3>Budgeted Monthly</h3>
            <p>$<?=number_format($total_budget_monthly,2)?></p>
        </div>
        <div class="footer">
            <svg width="11" height="11" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M105.1 202.6c7.7-21.8 20.2-42.3 37.8-59.8c62.5-62.5 163.8-62.5 226.3 0L386.3 160H352c-17.7 0-32 14.3-32 32s14.3 32 32 32H463.5c0 0 0 0 0 0h.4c17.7 0 32-14.3 32-32V80c0-17.7-14.3-32-32-32s-32 14.3-32 32v35.2L414.4 97.6c-87.5-87.5-229.3-87.5-316.8 0C73.2 122 55.6 150.7 44.8 181.4c-5.9 16.7 2.9 34.9 19.5 40.8s34.9-2.9 40.8-19.5zM39 289.3c-5 1.5-9.8 4.2-13.7 8.2c-4 4-6.7 8.8-8.1 14c-.3 1.2-.6 2.5-.8 3.8c-.3 1.7-.4 3.4-.4 5.1V432c0 17.7 14.3 32 32 32s32-14.3 32-32V396.9l17.6 17.5 0 0c87.5 87.4 229.3 87.4 316.7 0c24.4-24.4 42.1-53.1 52.9-83.7c5.9-16.7-2.9-34.9-19.5-40.8s-34.9 2.9-40.8 19.5c-7.7 21.8-20.2 42.3-37.8 59.8c-62.5 62.5-163.8 62.5-226.3 0l-.1-.1L125.6 352H160c17.7 0 32-14.3 32-32s-14.3-32-32-32H48.4c-1.6 0-3.2 .1-4.8 .3s-3.1 .5-4.6 1z"/></svg>
           Total Budget Envelopes
        </div>
    </div>
    

    <div class="content-block stat green">
        <div class="data">
            <h3>Budget Balance</h3>
            <p>$<?=number_format($total_budget_balances,2)?></p>
        </div>
        <div class="footer">
            <svg width="11" height="11" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M105.1 202.6c7.7-21.8 20.2-42.3 37.8-59.8c62.5-62.5 163.8-62.5 226.3 0L386.3 160H352c-17.7 0-32 14.3-32 32s14.3 32 32 32H463.5c0 0 0 0 0 0h.4c17.7 0 32-14.3 32-32V80c0-17.7-14.3-32-32-32s-32 14.3-32 32v35.2L414.4 97.6c-87.5-87.5-229.3-87.5-316.8 0C73.2 122 55.6 150.7 44.8 181.4c-5.9 16.7 2.9 34.9 19.5 40.8s34.9-2.9 40.8-19.5zM39 289.3c-5 1.5-9.8 4.2-13.7 8.2c-4 4-6.7 8.8-8.1 14c-.3 1.2-.6 2.5-.8 3.8c-.3 1.7-.4 3.4-.4 5.1V432c0 17.7 14.3 32 32 32s32-14.3 32-32V396.9l17.6 17.5 0 0c87.5 87.4 229.3 87.4 316.7 0c24.4-24.4 42.1-53.1 52.9-83.7c5.9-16.7-2.9-34.9-19.5-40.8s-34.9 2.9-40.8 19.5c-7.7 21.8-20.2 42.3-37.8 59.8c-62.5 62.5-163.8 62.5-226.3 0l-.1-.1L125.6 352H160c17.7 0 32-14.3 32-32s-14.3-32-32-32H48.4c-1.6 0-3.2 .1-4.8 .3s-3.1 .5-4.6 1z"/></svg>
            Calculated Right Now
        </div>
    </div>

    <div class="content-block stat blue">
        <div class="data">
            <h3>Remaining Balance</h3>
            <p>$<?=number_format($remaining_balance,2)?></p>
        </div>
        <div class="footer">
 <svg width="11" height="11" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M105.1 202.6c7.7-21.8 20.2-42.3 37.8-59.8c62.5-62.5 163.8-62.5 226.3 0L386.3 160H352c-17.7 0-32 14.3-32 32s14.3 32 32 32H463.5c0 0 0 0 0 0h.4c17.7 0 32-14.3 32-32V80c0-17.7-14.3-32-32-32s-32 14.3-32 32v35.2L414.4 97.6c-87.5-87.5-229.3-87.5-316.8 0C73.2 122 55.6 150.7 44.8 181.4c-5.9 16.7 2.9 34.9 19.5 40.8s34.9-2.9 40.8-19.5zM39 289.3c-5 1.5-9.8 4.2-13.7 8.2c-4 4-6.7 8.8-8.1 14c-.3 1.2-.6 2.5-.8 3.8c-.3 1.7-.4 3.4-.4 5.1V432c0 17.7 14.3 32 32 32s32-14.3 32-32V396.9l17.6 17.5 0 0c87.5 87.4 229.3 87.4 316.7 0c24.4-24.4 42.1-53.1 52.9-83.7c5.9-16.7-2.9-34.9-19.5-40.8s-34.9 2.9-40.8 19.5c-7.7 21.8-20.2 42.3-37.8 59.8c-62.5 62.5-163.8 62.5-226.3 0l-.1-.1L125.6 352H160c17.7 0 32-14.3 32-32s-14.3-32-32-32H48.4c-1.6 0-3.2 .1-4.8 .3s-3.1 .5-4.6 1z"/></svg>
 Calculated Right Now
        </div>
    </div>
    <div class="content-block stat red">
        <div class="data">
            <h3>Savings</h3>
            <p>$<?=number_format($total_savings,2)?></p>
        </div>
        <div class="footer">
      <svg width="11" height="11" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M105.1 202.6c7.7-21.8 20.2-42.3 37.8-59.8c62.5-62.5 163.8-62.5 226.3 0L386.3 160H352c-17.7 0-32 14.3-32 32s14.3 32 32 32H463.5c0 0 0 0 0 0h.4c17.7 0 32-14.3 32-32V80c0-17.7-14.3-32-32-32s-32 14.3-32 32v35.2L414.4 97.6c-87.5-87.5-229.3-87.5-316.8 0C73.2 122 55.6 150.7 44.8 181.4c-5.9 16.7 2.9 34.9 19.5 40.8s34.9-2.9 40.8-19.5zM39 289.3c-5 1.5-9.8 4.2-13.7 8.2c-4 4-6.7 8.8-8.1 14c-.3 1.2-.6 2.5-.8 3.8c-.3 1.7-.4 3.4-.4 5.1V432c0 17.7 14.3 32 32 32s32-14.3 32-32V396.9l17.6 17.5 0 0c87.5 87.4 229.3 87.4 316.7 0c24.4-24.4 42.1-53.1 52.9-83.7c5.9-16.7-2.9-34.9-19.5-40.8s-34.9 2.9-40.8 19.5c-7.7 21.8-20.2 42.3-37.8 59.8c-62.5 62.5-163.8 62.5-226.3 0l-.1-.1L125.6 352H160c17.7 0 32-14.3 32-32s-14.3-32-32-32H48.4c-1.6 0-3.2 .1-4.8 .3s-3.1 .5-4.6 1z"/></svg>
    Not Budgeted
        </div>
    </div>
</div><!--budgets--> 

 <div id="budgets" class="dashboard">
    <div class="content-block cyan">
        <div class="data">
          <div class="table">
			<table>
				<thead>
					<tr>
					    <td></td>
 					    <td>Flags </td>
 					    <td></td>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($flags)): ?>
					<tr>
						<td colspan="10" style="text-align:center;">There are no records.</td>
					</tr>
					<?php endif; ?>
					<?php foreach ($flags as $result): ?>
			 
					<tr>
						<td style="text-align: end;"><?=htmlspecialchars($result['id']?? '', ENT_QUOTES)?></td>
						<td></td>
					    <td><?=htmlspecialchars($result['description']?? '', ENT_QUOTES)?></td>
						</td>
					</tr>
					<?php endforeach; ?>
	   </tbody>
	  </table>
     </div>
    </div>
   </div> 
    <div class="content-block red">
        <div class="data">
          <div class="table">
			<table>
				<thead>
					<tr>
 					    <td colspan=2>Budget Bucket</td>
                        <td style="text-align: center;">Amount</td>
                        <td style="text-align: center;">Balance</td>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($budgets)): ?>
					<tr>
						<td colspan="10" style="text-align:center;">There are no records.</td>
					</tr>
					<?php endif; ?>
					<?php foreach ($budgets as $result): ?>
			 
					<tr>
						<td style="text-align: end;"><?=htmlspecialchars($result['id']?? '', ENT_QUOTES)?>&nbsp;&nbsp;&nbsp;&nbsp;</td>
					 
					    <td><?=htmlspecialchars($result['budget']?? '', ENT_QUOTES)?></td>
					    <td style="text-align: end;"><?=htmlspecialchars($result['amount']?? '', ENT_QUOTES)?></td>
					    <td style="text-align: end;"><?=htmlspecialchars($result['balance']?? '', ENT_QUOTES)?></td>
						</td>
					</tr>
					<?php endforeach; ?>
	   </tbody>
	  </table>
     </div>
    </div>
   </div> 
</div><!--budgets--> 
 
 </div><!--read-->
 <style>
    .right{
        text-align: right;
    }
</style>
<?=template_admin_footer()?>