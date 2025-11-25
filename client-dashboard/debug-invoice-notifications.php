<?php
session_start();
include '../../private/config.php';
$pdo = pdo_connect_mysql();

// Get account info
$stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
$stmt->execute([ $_SESSION['id'] ]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h2>Debug Invoice Notifications</h2>";
echo "<p>Logged in as Account ID: " . $account['id'] . " (" . htmlspecialchars($account['username']) . ")</p>";
echo "<p>Role: " . htmlspecialchars($account['role']) . "</p>";

// Get all client_ids for this account
$stmt = $pdo->prepare('SELECT id, business_name FROM invoice_clients WHERE acc_id = ?');
$stmt->execute([ $account['id'] ]);
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Business Profiles for this Account:</h3>";
if ($clients) {
    echo "<ul>";
    foreach ($clients as $client) {
        echo "<li>Client ID: " . $client['id'] . " - " . htmlspecialchars($client['business_name']) . "</li>";
    }
    echo "</ul>";
    
    $client_ids = array_column($clients, 'id');
    
    // Get invoices for these clients
    $placeholders = str_repeat('?,', count($client_ids) - 1) . '?';
    $stmt = $pdo->prepare("SELECT id, invoice_number, client_id, payment_status FROM invoices WHERE client_id IN ($placeholders) ORDER BY id DESC");
    $stmt->execute($client_ids);
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Invoices for these Business Profiles:</h3>";
    if ($invoices) {
        echo "<ul>";
        foreach ($invoices as $inv) {
            echo "<li>Invoice #" . htmlspecialchars($inv['invoice_number']) . " (ID: " . $inv['id'] . ") - Client ID: " . $inv['client_id'] . " - Status: " . htmlspecialchars($inv['payment_status']) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No invoices found</p>";
    }
    
    // Get notifications
    $stmt = $pdo->prepare("SELECT * FROM client_notifications WHERE client_id IN ($placeholders) ORDER BY created_at DESC");
    $stmt->execute($client_ids);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>All Notifications for these Business Profiles:</h3>";
    if ($notifications) {
        echo "<ul>";
        foreach ($notifications as $notif) {
            echo "<li>ID: " . $notif['id'] . " | Client ID: " . $notif['client_id'] . " | Invoice ID: " . $notif['invoice_id'] . " | Read: " . ($notif['is_read'] ? 'Yes' : 'No') . " | Message: " . htmlspecialchars($notif['message']) . " | Created: " . $notif['created_at'] . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No notifications found</p>";
    }
    
    // Count unread
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM client_notifications WHERE client_id IN ($placeholders) AND is_read = 0");
    $stmt->execute($client_ids);
    $unread_count = $stmt->fetchColumn();
    
    echo "<h3>Unread Notification Count: " . $unread_count . "</h3>";
    
} else {
    echo "<p>No business profiles found for this account</p>";
}
?>
