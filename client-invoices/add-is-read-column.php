<?php
// Migration: Add is_read column to client_notifications table
// Run this once to update the database schema

include 'main.php';

try {
    // Check if is_read column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM client_notifications LIKE 'is_read'");
    $column_exists = $stmt->fetch();
    
    if (!$column_exists) {
        // Add is_read column with default value 0 (unread)
        $pdo->exec("ALTER TABLE client_notifications ADD COLUMN is_read TINYINT(1) DEFAULT 0 AFTER created_at");
        echo "✓ Successfully added 'is_read' column to client_notifications table\n";
    } else {
        echo "✓ Column 'is_read' already exists in client_notifications table\n";
    }
    
    // Set all existing notifications as read (optional - you can comment this out if you want old ones to show)
    // $pdo->exec("UPDATE client_notifications SET is_read = 1 WHERE is_read IS NULL");
    // echo "✓ Marked all existing notifications as read\n";
    
    echo "\n✅ Migration completed successfully!";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
