<?php
// One-time script to create notifications for existing unpaid/balance invoices
session_start();
include '../../private/config.php';
$pdo = pdo_connect_mysql();

// Get account info (must be admin to run this)
$stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
$stmt->execute([ $_SESSION['id'] ]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

if ($account['role'] !== 'Admin') {
    die('This script can only be run by Admin users.');
}

echo "<h2>Backfill Invoice Notifications</h2>";

// Get all unpaid/balance invoices that don't have notifications
$stmt = $pdo->query("
    SELECT i.* 
    FROM invoices i
    LEFT JOIN client_notifications cn ON cn.invoice_id = i.id
    WHERE (i.payment_status = 'Unpaid' OR i.payment_status = 'Balance')
    AND cn.id IS NULL
    ORDER BY i.id DESC
");
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Found " . count($invoices) . " invoices without notifications</p>";

if ($invoices) {
    echo "<ul>";
    
    $stmt_insert = $pdo->prepare('
        INSERT INTO client_notifications (client_id, invoice_id, message, created_at) 
        VALUES (?, ?, ?, NOW())
    ');
    
    foreach ($invoices as $invoice) {
        $total_amount = floatval($invoice['payment_amount']) + floatval($invoice['tax_total']);
        $message = "NEW - Invoice #{$invoice['invoice_number']} - Amount due: $" . number_format($total_amount, 2);
        
        $stmt_insert->execute([$invoice['client_id'], $invoice['id'], $message]);
        
        echo "<li>✅ Created notification for Invoice #{$invoice['invoice_number']} (ID: {$invoice['id']}) - Client ID: {$invoice['client_id']}</li>";
    }
    
    echo "</ul>";
    echo "<p><strong>Done! Created " . count($invoices) . " notifications.</strong></p>";
    echo "<p><a href='debug-invoice-notifications.php'>Check notifications</a> | <a href='index.php'>Back to Dashboard</a></p>";
} else {
    echo "<p>No invoices need notifications.</p>";
    echo "<p><a href='index.php'>Back to Dashboard</a></p>";
}
?>
