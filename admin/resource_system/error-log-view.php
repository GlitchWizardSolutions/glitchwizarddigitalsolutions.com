<?php
require 'assets/includes/admin_config.php';
// Check if the user is logged-in
check_loggedin($pdo, '../../index.php');
// Fetch account details associated with the logged-in user
$stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
$stmt->execute([ $_SESSION['id'] ]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);
// Check if the user is an admin...
if ($account['role'] != 'Admin') {
    exit('You do not have permission to access this page!');
}
// Connect to the login Database using the PDO interface
try {
	$logon_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name . ';charset=' . db_charset, db_user, db_pass);
	$logon_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	// If there is an error with the connection, stop the script and display the error.
	exit('Failed to connect to the logon database!');
}
// Connect to the Error Handling Database using the PDO interface
try {
	$error_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name9 . ';charset=' . db_charset, db_user, db_pass);
	$error_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	// If there is an error with the connection, stop the script and display the error.
	exit('Failed to connect to the error handling database: ' . $exception->getMessage());
}
$page = 'View';
// Retrieve records from the database
$records = $error_db->query('SELECT * FROM error_handling')->fetchAll(PDO::FETCH_ASSOC);
// Check whether the record ID is specified
if (isset($_GET['id'])) {
    // Retrieve the record from the database
    $stmt = $error_db->prepare('SELECT * FROM error_handling WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    exit('No ID specified!');
}
$copy="";
?>
    <?=template_admin_header($page . ' Error Log', 'resources', 'errors')?>
    
<div class="content-title mb-3">
    <div class="title">
       <i class="fa-solid fa-circle-info"></i>
        <div class="txt">
            <h2>Error Log</h2>
            <p><?=$page . ' Record' ?></p>
        </div>
    </div>
</div>

<?php if (isset($success_msg)): ?>
<div class="msg success">
    <i class="fas fa-check-circle"></i>
    <p><?=$success_msg?></p>
    <i class="fas fa-times"></i>
</div>
<?php endif; ?>

<div class="content-header responsive-flex-column pad-top-5">
    <div class="btns">
        <a href="error-logs.php" class="btn btn-secondary">Return</a>
        <a href="error-logs.php?delete=<?=$record['id']?>" onclick="return confirm('Are you sure you want to delete this record?')" class="btn btn-danger">Delete</a>
    </div>
</div>
<div class="content-block">
    <div class="table">
        <table>
            <thead> 
                <tr>
                    <td style='text-align:center; background:grey; color:white; text-transform: uppercase' colspan="2"><strong><?=htmlspecialchars($record['application'], ENT_QUOTES)?></strong></td>
                </tr>
                <tr>
                    <td style='text-align:center; background:#<?=$record['severity']=='Critical'?'8B0000':($record['severity']=='Error'?'DC143C':($record['severity']=='Warning'?'FFA500':'6c757d'))?>; color:white;'><strong><?=htmlspecialchars($record['severity'], ENT_QUOTES)?></strong></td>
                    <td style='text-align:center; background:grey; color:white;'><strong><?=date('M d, Y h:i A', strtotime($record['timestamp']?? ''))?></strong></td>
                </tr>
            </thead>
            <tbody>       
                <tr>
                    <td style='text-align: start; background:#F8F4FF'><strong>Page:</strong></td>
                    <td style='text-align: start; background:#F8F4FF'><?=htmlspecialchars($record['pagename'], ENT_QUOTES)?></td>
                </tr>    
                <tr>
                    <td style='text-align: start; background:#F5F5F5'><strong>Path:</strong></td>
                    <td style='text-align: start; background:#F5F5F5'><?=htmlspecialchars($record['path'], ENT_QUOTES)?></td>
                </tr>
                <tr>
                    <td style='text-align: start; background:#F8F4FF'><strong>Section:</strong></td>  
                    <td style='text-align: start; background:#F8F4FF'><?=htmlspecialchars($record['section'], ENT_QUOTES)?></td>
                </tr>
                <?php if (!empty($record['error_type'])): ?>
                <tr>
                    <td style='text-align: start; background:#F5F5F5'><strong>Error Type:</strong></td>  
                    <td style='text-align: start; background:#F5F5F5'><?=htmlspecialchars($record['error_type'], ENT_QUOTES)?></td>
                </tr>
                <?php endif; ?>
                <?php if (!empty($record['error_code'])): ?>
                <tr>
                    <td style='text-align: start; background:#F8F4FF'><strong>Error Code:</strong></td>  
                    <td style='text-align: start; background:#F8F4FF'><?=htmlspecialchars($record['error_code'], ENT_QUOTES)?></td>
                </tr>
                <?php endif; ?>
                <?php if (!empty($record['user_id'])): ?>
                <tr>
                    <td style='text-align: start; background:#F5F5F5'><strong>User ID:</strong></td>  
                    <td style='text-align: start; background:#F5F5F5'><?=htmlspecialchars($record['user_id'], ENT_QUOTES)?></td>
                </tr>
                <?php endif; ?>
                <?php if (!empty($record['ip_address'])): ?>
                <tr>
                    <td style='text-align: start; background:#F8F4FF'><strong>IP Address:</strong></td>  
                    <td style='text-align: start; background:#F8F4FF'><?=htmlspecialchars($record['ip_address'], ENT_QUOTES)?></td>
                </tr>
                <?php endif; ?>
                <?php if (!empty($record['request_method']) || !empty($record['request_uri'])): ?>
                <tr>
                    <td style='text-align: start; background:#F5F5F5'><strong>Request:</strong></td>  
                    <td style='text-align: start; background:#F5F5F5'><?=htmlspecialchars($record['request_method'], ENT_QUOTES)?> <?=htmlspecialchars($record['request_uri'], ENT_QUOTES)?></td>
                </tr>
                <?php endif; ?>
                <?php if (!empty($record['noted'])): ?>
                <tr>
                    <td style='text-align: start; background:#F8F4FF'><strong>Notes:</strong></td>  
                    <td style='text-align: start; background:#F8F4FF'><?=htmlspecialchars($record['noted'], ENT_QUOTES)?></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!empty($record['inputs'])): ?>
<div class="content-block">
    <div class="table">
        <table>
            <thead>
                <tr>
                    <td colspan="2"><strong>Request Parameters</strong></td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="2" style='text-align: start; background:#F5F5F5'>
                        <pre style="margin: 0; white-space: pre-wrap; font-family: monospace; font-size: 12px;"><?=htmlspecialchars($record['inputs'], ENT_QUOTES)?></pre>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="content-block">
    <div class="table">
        <table>
            <thead>
                <tr>
                    <td colspan="2"><strong>Error Message</strong></td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="2" style='text-align: start; background:#FFF0F5; color: #dc3545; font-weight: bold;'>
                        <?=nl2br(htmlspecialchars($record['thrown'] ?? '', ENT_QUOTES))?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php if (!empty($record['outputs'])): ?>
<div class="content-block">
    <div class="table">
        <table>
            <thead>
                <tr>
                    <td colspan="2"><strong>Stack Trace / Additional Output</strong></td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="2" style='text-align: start; background:#F5F5F5'>
                        <pre style="margin: 0; white-space: pre-wrap; font-family: monospace; font-size: 11px;"><?=htmlspecialchars($record['outputs'], ENT_QUOTES)?></pre>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?> 
 
<script src="assets/js/resource-system-script.js"></script>
<?=template_admin_footer()?>