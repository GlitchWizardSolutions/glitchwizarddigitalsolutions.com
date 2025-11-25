<?php
/*
Created: 2025-11-25
Purpose: Mark all invoice notifications as read for the logged-in user
*/
include 'assets/includes/user-config.php';

// Get all client_ids for this account
$stmt = $pdo->prepare('SELECT id FROM invoice_clients WHERE acc_id = ?');
$stmt->execute([ $_SESSION['id'] ]);
$client_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (!empty($client_ids)) {
    $placeholders = str_repeat('?,', count($client_ids) - 1) . '?';
    
    // Mark all NEW and PAID notifications as read (keep PARTIAL and PAST DUE visible)
    $stmt = $pdo->prepare("
        UPDATE client_notifications 
        SET is_read = 1 
        WHERE client_id IN ($placeholders) 
        AND is_read = 0
        AND message NOT LIKE 'PARTIAL -%'
        AND message NOT LIKE 'PAST DUE -%'
    ");
    $stmt->execute($client_ids);
}

// Redirect back to referring page or dashboard
$redirect = $_SERVER['HTTP_REFERER'] ?? 'index.php';
header('Location: ' . $redirect);
exit;
?>
