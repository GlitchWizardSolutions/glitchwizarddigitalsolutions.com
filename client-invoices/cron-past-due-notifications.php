<?php
/**
 * Cron Job: Create PAST DUE Notifications
 * 
 * Purpose: Automatically create PAST DUE notifications for invoices that are overdue
 * Schedule: Run daily (recommended at midnight or early morning)
 * URL: https://yourdomain.com/client-invoices/cron-past-due-notifications.php?cron_secret=YOUR_SECRET
 * 
 * Created: November 25, 2025
 */

include 'main.php';

// Initialize output array for debugging
$output = [];
$output[] = 'Past Due Notifications Cron started at: ' . date('Y-m-d H:i:s');

// Ensure the cron secret reflects the one in the configuration file
if (isset($_GET['cron_secret']) && $_GET['cron_secret'] == cron_secret) {
    
    try {
        // Find all invoices that are past due and don't have PAST DUE notifications yet
        $stmt = $pdo->query("
            SELECT i.*, ic.business_name
            FROM invoices i
            JOIN invoice_clients ic ON i.client_id = ic.id
            WHERE i.payment_status IN ('Unpaid', 'Partial')
            AND CAST(i.due_date AS DATE) < CURDATE()
            AND i.id NOT IN (
                SELECT invoice_id 
                FROM client_notifications 
                WHERE message LIKE 'PAST DUE -%'
            )
            ORDER BY i.due_date ASC
        ");
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $output[] = 'Found ' . count($invoices) . ' past due invoices without PAST DUE notifications';
        
        $created_count = 0;
        
        foreach ($invoices as $invoice) {
            $days_overdue = floor((time() - strtotime($invoice['due_date'])) / (60 * 60 * 24));
            
            // Calculate amount due
            if ($invoice['payment_status'] == 'Partial') {
                $amount_due = ($invoice['payment_amount'] + $invoice['tax_total']) - $invoice['paid_total'];
            } else {
                $amount_due = $invoice['payment_amount'] + $invoice['tax_total'];
            }
            
            // Create notification message
            $message = "PAST DUE - Invoice #{$invoice['invoice_number']}<br>"
                     . "<div style='display:flex;justify-content:space-between;max-width:200px'><span>Amount due:</span><span>$" . number_format($amount_due, 2) . "</span></div>"
                     . "<div style='display:flex;justify-content:space-between;max-width:200px'><span>Days overdue:</span><span>$days_overdue</span></div>";
            
            // Insert notification
            $stmt = $pdo->prepare('
                INSERT INTO client_notifications (client_id, invoice_id, message, is_read, created_at) 
                VALUES (?, ?, ?, 0, NOW())
            ');
            $stmt->execute([$invoice['client_id'], $invoice['id'], $message]);
            
            $output[] = "  → Created PAST DUE notification for Invoice #{$invoice['invoice_number']} - {$invoice['business_name']} - $days_overdue days overdue";
            $created_count++;
        }
        
        $output[] = '';
        $output[] = "Successfully created $created_count PAST DUE notifications";
        
    } catch (Exception $e) {
        $output[] = 'ERROR: ' . $e->getMessage();
    }
    
    $output[] = 'Cron job completed at: ' . date('Y-m-d H:i:s');
    
    // Output results
    echo '<pre>';
    echo implode("\n", $output);
    echo '</pre>';
    
    // Log to file
    $log_file = __DIR__ . '/cron-past-due-log.txt';
    file_put_contents($log_file, implode("\n", $output) . "\n\n", FILE_APPEND);
    
} else {
    exit('Invalid cron secret!');
}
?>
