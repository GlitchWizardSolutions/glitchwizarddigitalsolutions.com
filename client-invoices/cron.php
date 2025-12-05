<?php
// production 9/13/24
// refreshed  6/14/25
// updated 11/20/25 - improved logging and date handling
include 'main.php';

// Initialize output array for debugging
$output = [];
$output[] = 'Cron job started at: ' . date('Y-m-d H:i:s');

// Ensure the cron secret reflects the one in the configuration file
if (isset($_GET['cron_secret']) && $_GET['cron_secret'] == cron_secret) {
    // Process recurring invoices
    // Get invoices where the next recurring date is 5 days from now OR already past
    $stmt = $pdo->prepare('SELECT * FROM invoices WHERE payment_status = "Paid" AND recurrence = 1 AND recurrence_period_type != ""');
    $stmt->execute();
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $output[] = 'Found ' . count($invoices) . ' recurring paid invoices';
    
    // Check if there are any invoices
    foreach ($invoices as $invoice) {
        // Get the next recurring date
        $next_date = date('Y-m-d H:i:s', strtotime($invoice['due_date'] . ' +' . $invoice['recurrence_period'] . ' ' . $invoice['recurrence_period_type']));
        $days_until_next = round((strtotime($next_date) - time()) / (60 * 60 * 24));
        
        $output[] = 'Invoice #' . $invoice['invoice_number'] . ' - Current due: ' . $invoice['due_date'] . ', Next due: ' . $next_date . ', Days until: ' . $days_until_next;
        
        // Check if the next recurring date is 5 days from now (or already past)
        if ($days_until_next <= 5) {
            $output[] = '  → Processing invoice #' . $invoice['invoice_number'];
            
            // Update the invoice
            $stmt = $pdo->prepare('UPDATE invoices SET due_date = ?, viewed = 0, payment_status = "Unpaid" WHERE id = ?');
            $stmt->execute([ $next_date, $invoice['id'] ]);
            
            $output[] = '  → Updated due date to: ' . $next_date;
            
            // Get client details
            $stmt = $pdo->prepare('SELECT * FROM invoice_clients WHERE id = ?');
            $stmt->execute([ $invoice['client_id'] ]);
            $client = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Send email and check result
            if ($client) {
                $email_sent = send_client_invoice_email($invoice, $client);
                if ($email_sent) {
                    $output[] = '  → Email sent to: ' . $client['email'];
                } else {
                    $output[] = '  → ⚠️ FAILED to send email to: ' . $client['email'];
                    $output[] = '      Check error log for details';
                }
            } else {
                $output[] = '  → ERROR: Client not found (ID: ' . $invoice['client_id'] . ')';
            }
        } else {
            $output[] = '  → Skipped (too far in future)';
        }
    }
    
    $output[] = '';
    $output[] = 'Processing overdue payment reminders...';
    
    // Send payment reminders to clients with overdue invoices
    $stmt = $pdo->prepare('SELECT * FROM invoices WHERE payment_status = "Unpaid" AND cast(due_date as DATE) < ?');
    $stmt->execute([ date('Y-m-d') ]);
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);    
    
    $output[] = 'Found ' . count($invoices) . ' overdue unpaid invoices';
    
    // Check if there are any invoices
    foreach ($invoices as $invoice) {
        // Get client details
        $stmt = $pdo->prepare('SELECT * FROM invoice_clients WHERE id = ?');
        $stmt->execute([ $invoice['client_id'] ]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($client) {
            // Send email and check result
            $email_sent = send_client_invoice_email($invoice, $client, 'Payment Reminder');
            if ($email_sent) {
                $output[] = '  → Reminder sent for invoice #' . $invoice['invoice_number'] . ' to: ' . $client['email'];
            } else {
                $output[] = '  → ⚠️ FAILED to send reminder for invoice #' . $invoice['invoice_number'] . ' to: ' . $client['email'];
                $output[] = '      Check error log for details';
            }
        } else {
            $output[] = '  → ERROR: Client not found for invoice #' . $invoice['invoice_number'];
        }
    }
    
    $output[] = '';
    $output[] = 'Cron job completed at: ' . date('Y-m-d H:i:s');
    
    // Output results
    echo '<pre>';
    echo implode("\n", $output);
    echo '</pre>';
    
} else {
    exit('Invalid cron secret!');
}
?>