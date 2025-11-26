<?php
// Quick test to check unsent invoice count
include_once 'assets/includes/admin_config.php';

echo "<h2>Unsent Invoices Debug Test</h2>";

// Check if column exists
$stmt = $pdo->query("SHOW COLUMNS FROM glitchwizarddigi_login_db.invoices LIKE 'email_sent'");
$has_column = $stmt->rowCount() > 0;

echo "<p><strong>Column 'email_sent' exists:</strong> " . ($has_column ? 'YES' : 'NO') . "</p>";

if ($has_column) {
    // Get count
    $stmt = $pdo->query("
        SELECT COUNT(*) as count
        FROM glitchwizarddigi_login_db.invoices 
        WHERE email_sent = 0
        AND payment_status != 'Paid'
    ");
    $count = $stmt->fetchColumn();
    
    echo "<p><strong>Count of unsent invoices:</strong> $count</p>";
    
    // Show the actual invoices
    $stmt = $pdo->query("
        SELECT id, invoice_number, payment_status, email_sent, created
        FROM glitchwizarddigi_login_db.invoices 
        WHERE email_sent = 0
        AND payment_status != 'Paid'
        ORDER BY created DESC
        LIMIT 10
    ");
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Unsent Invoices:</h3>";
    if ($invoices) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Invoice Number</th><th>Status</th><th>Email Sent</th><th>Created</th></tr>";
        foreach ($invoices as $inv) {
            echo "<tr>";
            echo "<td>{$inv['id']}</td>";
            echo "<td>{$inv['invoice_number']}</td>";
            echo "<td>{$inv['payment_status']}</td>";
            echo "<td>{$inv['email_sent']}</td>";
            echo "<td>{$inv['created']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No unsent invoices found.</p>";
    }
    
    // Show all recent invoices
    echo "<h3>All Recent Invoices (last 10):</h3>";
    $stmt = $pdo->query("
        SELECT id, invoice_number, payment_status, email_sent, created
        FROM glitchwizarddigi_login_db.invoices 
        ORDER BY created DESC
        LIMIT 10
    ");
    $all_invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($all_invoices) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Invoice Number</th><th>Status</th><th>Email Sent</th><th>Created</th></tr>";
        foreach ($all_invoices as $inv) {
            echo "<tr>";
            echo "<td>{$inv['id']}</td>";
            echo "<td>{$inv['invoice_number']}</td>";
            echo "<td>{$inv['payment_status']}</td>";
            echo "<td>{$inv['email_sent']}</td>";
            echo "<td>{$inv['created']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}
?>
