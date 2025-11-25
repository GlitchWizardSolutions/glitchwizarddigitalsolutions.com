<?php
// Debug: Check client notifications
include 'main.php';

echo "<h2>Client Notifications Debug</h2>";

// Check if table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'client_notifications'");
    $table_exists = $stmt->fetch();
    
    if (!$table_exists) {
        echo "❌ Table 'client_notifications' does not exist!<br>";
        exit;
    }
    echo "✓ Table 'client_notifications' exists<br><br>";
    
    // Check if is_read column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM client_notifications LIKE 'is_read'");
    $column_exists = $stmt->fetch();
    
    if (!$column_exists) {
        echo "❌ Column 'is_read' does not exist! Run the migration script.<br>";
    } else {
        echo "✓ Column 'is_read' exists<br><br>";
    }
    
    // Show all notifications
    $stmt = $pdo->query("SELECT * FROM client_notifications ORDER BY created_at DESC LIMIT 10");
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Recent Notifications (last 10):</h3>";
    if (empty($notifications)) {
        echo "<p>No notifications found in database.</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Client ID</th><th>Invoice ID</th><th>Message</th><th>Is Read</th><th>Created</th></tr>";
        foreach ($notifications as $n) {
            echo "<tr>";
            echo "<td>{$n['id']}</td>";
            echo "<td>{$n['client_id']}</td>";
            echo "<td>" . ($n['invoice_id'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($n['message']) . "</td>";
            echo "<td>" . (isset($n['is_read']) ? ($n['is_read'] ? 'Yes' : 'No') : 'N/A') . "</td>";
            echo "<td>{$n['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Show table structure
    echo "<br><h3>Table Structure:</h3><pre>";
    $stmt = $pdo->query("DESCRIBE client_notifications");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($columns);
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
