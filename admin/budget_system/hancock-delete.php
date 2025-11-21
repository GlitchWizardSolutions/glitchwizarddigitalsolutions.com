<?php
include_once 'assets/includes/admin_config.php';
// Connect to MySQL database
$budget_pdo = pdo_connect_budget_db($db_host, $db_name7, $db_user, $db_pass);
// Output message
$msg = '';
// Check that the Transaction ID exists
if (isset($_GET['id'])) {
    // Select the record that is going to be deleted
    $stmt = $budget_pdo->prepare('SELECT * FROM hancock WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$transaction) {
        exit('Transaction doesn\'t exist with that ID!');
    }
    // Make sure the user confirms before deletion
    if (isset($_GET['confirm'])) {
        if ($_GET['confirm'] == 'yes') {
            // User clicked the "Yes" button, delete record
            $stmt = $budget_pdo->prepare('DELETE FROM hancock WHERE id = ?');
            $stmt->execute([ $_GET['id'] ]);
            $msg = 'You have deleted the transaction!';
             header('Location: hancock-browse.php');
            exit;
        } else {
            // User clicked the "No" button, redirect them back to the read page
            header('Location: hancock-browse.php');
            exit;
        }
    }
} else {
    exit('No ID specified!');
}
?>
<?=template_admin_header('View Transactions', 'budget', 'hancock')?>

<div class="content delete">

    <div class="page-title">
		<i class="fa-solid fa-user fa-lg"></i>
		<div class="wrap">
			<h2>Delete Transaction #<?=$transaction['id']?></h2>
			<p>The transaction will be permanently deleted from the database.</p>
		</div>
	</div>

    <form action="" method="get" class="crud-form">

        <input type="hidden" name="id" value="<?=$transaction['id']?>">

        <?php if ($msg): ?>
        <p class="msg-success"><?=$msg?></p>
        <?php else: ?>
        <p>Are you sure you want to delete transaction #<?=$transaction['id']?>?</p>
        <div class="btns">
            <button type="submit" name="confirm" value="yes" class="btn red">Yes</button>
            <button type="submit" name="confirm" value="no" class="btn">No</button>
        </div>
        <?php endif; ?>

    </form>

</div>

<?=template_admin_footer()?>