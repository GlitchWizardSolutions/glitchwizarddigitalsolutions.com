<?php
// Delete Payment Record
// Phase 2: Payment Recording System
// Created: November 24, 2025
include_once 'assets/includes/admin_config.php';

// Check if user is authenticated
if (!isset($_SESSION['loggedin'])) {
    header('Location: ../../index.php');
    exit;
}

// Get payment ID and invoice ID from URL
if (!isset($_GET['id']) || !isset($_GET['invoice_id'])) {
    header('Location: invoices.php?error_msg=Invalid request');
    exit;
}

$payment_id = (int)$_GET['id'];
$invoice_id = (int)$_GET['invoice_id'];

try {
    $pdo->beginTransaction();
    
    // 1. Get payment details before deleting
    $stmt = $pdo->prepare('SELECT * FROM payment_history WHERE id = ?');
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        throw new Exception('Payment not found');
    }
    
    // Verify payment belongs to the specified invoice
    if ($payment['invoice_id'] != $invoice_id) {
        throw new Exception('Payment does not belong to this invoice');
    }
    
    // 2. Get current invoice details
    $stmt = $pdo->prepare('SELECT * FROM invoices WHERE id = ?');
    $stmt->execute([$invoice_id]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$invoice) {
        throw new Exception('Invoice not found');
    }
    
    // 3. Delete the payment record
    $stmt = $pdo->prepare('DELETE FROM payment_history WHERE id = ?');
    $stmt->execute([$payment_id]);
    
    // 4. Update invoice paid_total (subtract the deleted payment)
    $new_paid_total = $invoice['paid_total'] - $payment['amount_paid'];
    $new_paid_total = max(0, $new_paid_total); // Ensure not negative
    
    $stmt = $pdo->prepare('UPDATE invoices SET paid_total = ? WHERE id = ?');
    $stmt->execute([$new_paid_total, $invoice_id]);
    
    // 5. Recalculate and update payment status
    $total = $invoice['payment_amount'] + $invoice['tax_total'];
    $new_balance = $total - $new_paid_total;
    $new_status = $invoice['payment_status'];
    
    // Determine new status based on balance
    if ($new_paid_total <= 0) {
        // No payments made
        $new_status = 'Unpaid';
    } elseif ($new_balance > 0 && $new_paid_total > 0) {
        // Partial payment
        $new_status = 'Balance';
    } elseif ($new_balance <= 0) {
        // Fully paid
        $new_status = 'Pending';
    }
    
    $stmt = $pdo->prepare('UPDATE invoices SET payment_status = ? WHERE id = ?');
    $stmt->execute([$new_status, $invoice_id]);
    
    // 6. Create client notification about payment deletion
    $notification_message = sprintf(
        'Payment of $%.2f has been removed from invoice %s. New balance: $%.2f',
        $payment['amount_paid'],
        $invoice['invoice_number'],
        $new_balance
    );
    
    $stmt = $pdo->prepare('
        INSERT INTO client_notifications 
        (client_id, notification_type, invoice_id, message, amount, is_read) 
        VALUES (?, ?, ?, ?, ?, 0)
    ');
    $stmt->execute([
        $invoice['client_id'],
        'balance_due',
        $invoice_id,
        $notification_message,
        $new_balance > 0 ? $new_balance : null
    ]);
    
    $pdo->commit();
    
    // Redirect with success message
    header('Location: payment_history.php?invoice_id=' . $invoice_id . '&success_msg=Payment deleted successfully');
    exit;
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('Payment deletion error: ' . $e->getMessage());
    header('Location: payment_history.php?invoice_id=' . $invoice_id . '&error_msg=' . urlencode($e->getMessage()));
    exit;
}
?>
