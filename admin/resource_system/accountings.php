<?php
require 'assets/includes/admin_config.php';
// Connect to the Accounting Database using the PDO interface
try {
	$accounting_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name4 . ';charset=' . db_charset, db_user, db_pass);
	$accounting_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	// If there is an error with the connection, stop the script and display the error.
	exit('Failed to connect to the on the go database!');
}
 function formatDollars($dollars)
{
    if ($dollars===null){
        $dollars=0;
    }
    $formatted = "$" . number_format(sprintf('%0.2f', preg_replace("/[^0-9.]/", "", $dollars)), 2);
    return $dollars < 0 ? "({$formatted})" : "{$formatted}";
}
$stmt = $accounting_db->prepare('SELECT sum(balance) as total_balance FROM `categories` where status = 1');
$stmt->execute();
$cur_bal = 0;
while($row1 = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $cur_bal= $row1['total_balance'];
}
$stmt = $accounting_db->prepare("SELECT sum(amount) as total_entries_today FROM `running_balance` where category_id in (SELECT id FROM categories where status =1) and date(date_created) = '".(date("Y-m-d"))."' and balance_type = 1");
$stmt->execute();
$today_entries = 0;
while($row1 = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $today_entries= $row1['total_entries_today'];
}



?>
<?=template_admin_header('Expense Reports', 'resources', 'manage')?>

<div class="content-title">
    <div class="title">
     <i class="fa-solid fa-user-secret"></i>
        <div class="txt">
            <h2>Accounting Overview</h2>
        </div>
    </div>
</div>

<div class="content-block">
Current Overall Budget is <?php echo formatDollars($cur_bal); ?><br>
Budget Entries Today is <?php echo formatDollars($today_entries); ?>
</div>

<script src="assets/js/resource-system-script.js"></script>
<?=template_admin_footer()?>