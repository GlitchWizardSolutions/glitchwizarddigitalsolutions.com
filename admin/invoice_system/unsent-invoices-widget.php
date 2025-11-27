<?php
/**
 * Admin Widget: Unsent Invoice Notifications
 * 
 * Purpose: Display count of invoices created but email not sent yet
 * Include this in admin dashboard or invoices page
 * 
 * Usage: include 'unsent-invoices-widget.php';
 */

// Get count of unsent invoices
$unsent_count = 0;
try {
    $stmt = $pdo->query("
        SELECT COUNT(*) as count
        FROM invoices 
        WHERE email_sent = 0
        AND payment_status != 'Paid'
    ");
    $unsent_count = $stmt->fetchColumn();
} catch (Exception $e) {
    // Query failed, don't show widget
    $unsent_count = 0;
}

if ($unsent_count > 0): ?>
<div class="alert alert-warning" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
    <div style="display: flex; align-items: center; gap: 10px;">
        <i class="fa-solid fa-envelope" style="font-size: 1.5em;"></i>
        <div>
            <strong><?=$unsent_count?> Invoice<?=$unsent_count != 1 ? 's' : ''?> Pending Email</strong>
            <p style="margin: 0; font-size: 0.9em;">
                <?=$unsent_count?> invoice<?=$unsent_count != 1 ? 's were' : ' was'?> created but email<?=$unsent_count != 1 ? 's have' : ' has'?> not been sent yet.
            </p>
        </div>
    </div>
    <a href="invoices.php?filter=unsent" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-eye"></i> View Unsent Invoices
    </a>
</div>
<?php endif; ?>
