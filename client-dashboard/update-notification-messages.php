<?php
// One-time script to update existing notification messages to new format
session_start();
include '../../private/config.php';
$pdo = pdo_connect_mysql();

// Get account info (must be admin to run this)
$stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
$stmt->execute([ $_SESSION['id'] ?? 0 ]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$account || $account['role'] !== 'Admin') {
    die('This script can only be run by Admin users.');
}

echo "<h2>Update Notification Messages</h2>";

// Get all notifications with old format (including recently created ones that need line break updates)
$stmt = $pdo->query("
    SELECT * FROM client_notifications 
    WHERE message LIKE 'New invoice%created%' 
    OR message LIKE 'Payment of%received for Invoice%'
    OR message LIKE 'PAID - %received. Fully paid.'
    OR message LIKE 'PARTIAL - %received. Balance:%'
    OR message LIKE 'PAID - Invoice%Received:%'
    OR message LIKE 'PARTIAL - Invoice%Received:%Balance:%'
    OR (message LIKE 'PARTIAL - Invoice%' AND message NOT LIKE '%<div%')
    OR (message LIKE 'PAID - Invoice%' AND message NOT LIKE '%<div%')
    ORDER BY id DESC
");
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Found " . count($notifications) . " notifications with old format</p>";

if ($notifications) {
    echo "<ul>";
    
    $stmt_update = $pdo->prepare('UPDATE client_notifications SET message = ? WHERE id = ?');
    
    foreach ($notifications as $notif) {
        $old_message = $notif['message'];
        $new_message = $old_message;
        
        // Update "New invoice #XXX created - Amount due: $YYY"
        if (preg_match('/New invoice #([^\s]+) created - Amount due: \$(.+)/', $old_message, $matches)) {
            $invoice_num = $matches[1];
            $amount = $matches[2];
            $new_message = "NEW - Invoice #{$invoice_num} - Amount due: $$amount";
        }
        // Update "Payment of $XXX received for Invoice #YYY. Invoice is now fully paid."
        elseif (preg_match('/Payment of \$([^\s]+) received for Invoice #([^\.]+)\. Invoice is now fully paid\./', $old_message, $matches)) {
            $amount = $matches[1];
            $invoice_num = $matches[2];
            $new_message = "PAID - Invoice #{$invoice_num}\nReceived: $$amount";
        }
        // Update "Payment of $XXX received for Invoice #YYY. Remaining balance: $ZZZ"
        elseif (preg_match('/Payment of \$([^\s]+) received for Invoice #([^\.]+)\. Remaining balance: \$(.+)/', $old_message, $matches)) {
            $amount = $matches[1];
            $invoice_num = $matches[2];
            $balance = $matches[3];
            $new_message = "PARTIAL - Invoice #{$invoice_num}<br><span style='display:inline-block;width:70px'>Received:</span>$$amount<br><span style='display:inline-block;width:70px'>Balance:</span>$$balance";
        }
        // Update newer PAID format to add line breaks
        elseif (preg_match('/PAID - Invoice #([^\s]+) - Payment of \$([^\s]+) received\. Fully paid\./', $old_message, $matches)) {
            $invoice_num = $matches[1];
            $amount = $matches[2];
            $new_message = "PAID - Invoice #{$invoice_num}<br><div style='display:flex;justify-content:space-between;max-width:200px'><span>Received:</span><span>$$amount</span></div>";
        }
        // Update current PAID format with inline-block spans to flex layout
        elseif (preg_match("/PAID - Invoice #([^<]+)<br><span[^>]*>Received:<\/span>\\$(.+)/", $old_message, $matches)) {
            $invoice_num = $matches[1];
            $amount = $matches[2];
            $new_message = "PAID - Invoice #{$invoice_num}<br><div style='display:flex;justify-content:space-between;max-width:200px'><span>Received:</span><span>$$amount</span></div>";
        }
        // Update newer PARTIAL format to add line breaks
        elseif (preg_match('/PARTIAL - Invoice #([^\s]+) - Payment of \$([^\s]+) received\. Balance: \$(.+)/', $old_message, $matches)) {
            $invoice_num = $matches[1];
            $amount = $matches[2];
            $balance = $matches[3];
            $new_message = "PARTIAL - Invoice #{$invoice_num}<br><div style='display:flex;justify-content:space-between;max-width:200px'><span>Received:</span><span>$$amount</span></div><div style='display:flex;justify-content:space-between;max-width:200px'><span>Balance:</span><span>$$balance</span></div>";
        }
        // Update current PARTIAL format with inline-block spans to flex layout
        elseif (preg_match("/PARTIAL - Invoice #([^<]+)<br><span[^>]*>Received:<\/span>\\$([^<]+)<br><span[^>]*>Balance:<\/span>\\$(.+)/", $old_message, $matches)) {
            $invoice_num = $matches[1];
            $amount = $matches[2];
            $balance = $matches[3];
            $new_message = "PARTIAL - Invoice #{$invoice_num}<br><div style='display:flex;justify-content:space-between;max-width:200px'><span>Received:</span><span>$$amount</span></div><div style='display:flex;justify-content:space-between;max-width:200px'><span>Balance:</span><span>$$balance</span></div>";
        }
        
        if ($new_message !== $old_message) {
            $stmt_update->execute([$new_message, $notif['id']]);
            echo "<li>✅ Updated notification ID {$notif['id']}<br>";
            echo "&nbsp;&nbsp;&nbsp;Old: <code>" . htmlspecialchars($old_message) . "</code><br>";
            echo "&nbsp;&nbsp;&nbsp;New: <code>" . htmlspecialchars($new_message) . "</code></li>";
        } else {
            echo "<li>⚠️ Skipped notification ID {$notif['id']} - no pattern match<br>";
            echo "&nbsp;&nbsp;&nbsp;Message: <code>" . htmlspecialchars($old_message) . "</code></li>";
        }
    }
    
    echo "</ul>";
    echo "<p><strong>Done!</strong></p>";
    echo "<p><a href='debug-invoice-notifications.php'>Check notifications</a> | <a href='index.php'>Back to Dashboard</a></p>";
} else {
    echo "<p>No notifications need updating.</p>";
    echo "<p><a href='index.php'>Back to Dashboard</a></p>";
}
?>
