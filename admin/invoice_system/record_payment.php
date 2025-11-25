<?php
// Record Payment for Invoice
// Phase 2: Payment Recording System
// Created: November 24, 2025

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once 'assets/includes/admin_config.php';
include_once '../assets/includes/components.php';

// Check if user is authenticated
if (!isset($_SESSION['loggedin'])) {
    header('Location: ../../index.php');
    exit;
}

// Get admin user ID for recording
$admin_id = $_SESSION['id'] ?? null;

// Initialize variables
$invoice = null;
$client = null;
$error = '';
$success = '';

// Get invoice ID from URL
if (!isset($_GET['invoice_id'])) {
    header('Location: invoices.php');
    exit;
}

$invoice_id = (int)$_GET['invoice_id'];

// Fetch invoice details
$stmt = $pdo->prepare('SELECT * FROM invoices WHERE id = ?');
$stmt->execute([$invoice_id]);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invoice) {
    header('Location: invoices.php?error_msg=Invoice not found');
    exit;
}

// Fetch client details
$stmt = $pdo->prepare('SELECT * FROM invoice_clients WHERE id = ?');
$stmt->execute([$invoice['client_id']]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculate current balance
$total = $invoice['payment_amount'] + $invoice['tax_total'];
$balance_due = $total - $invoice['paid_total'];

// Process payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_payment'])) {
    $amount_paid = floatval($_POST['amount_paid']);
    $payment_method = trim($_POST['payment_method']);
    $payment_reference = trim($_POST['payment_reference'] ?? '');
    $transaction_id = trim($_POST['transaction_id'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $payment_date = $_POST['payment_date'] ?? date('Y-m-d H:i:s');
    
    // Validation
    if ($amount_paid <= 0) {
        $error = 'Payment amount must be greater than zero.';
    } elseif ($amount_paid > $balance_due) {
        $error = sprintf('Payment amount ($%.2f) cannot exceed balance due ($%.2f).', $amount_paid, $balance_due);
    } elseif (empty($payment_method)) {
        $error = 'Payment method is required.';
    } else {
        try {
            $pdo->beginTransaction();
            
            // 1. Insert payment history record
            $stmt = $pdo->prepare('
                INSERT INTO payment_history 
                (invoice_id, invoice_number, payment_date, amount_paid, payment_method, 
                 payment_reference, transaction_id, notes, recorded_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $invoice_id,
                $invoice['invoice_number'],
                $payment_date,
                $amount_paid,
                $payment_method,
                $payment_reference,
                $transaction_id,
                $notes,
                $admin_id
            ]);
            
            // 2. Update invoice paid_total
            $new_paid_total = $invoice['paid_total'] + $amount_paid;
            $stmt = $pdo->prepare('UPDATE invoices SET paid_total = ? WHERE id = ?');
            $stmt->execute([$new_paid_total, $invoice_id]);
            
            // 3. Update payment status based on new balance
            $new_balance = $total - $new_paid_total;
            $new_status = $invoice['payment_status'];
            
            if ($new_balance <= 0) {
                // Fully paid
                $new_status = 'Pending'; // Awaiting clearance
            } elseif ($new_paid_total > 0 && $new_balance > 0) {
                // Partial payment
                $new_status = 'Balance';
            }
            
            $stmt = $pdo->prepare('UPDATE invoices SET payment_status = ? WHERE id = ?');
            $stmt->execute([$new_status, $invoice_id]);
            
            // 4. Create client notification
            $notification_message = sprintf(
                'Payment of $%.2f received for invoice %s. %s',
                $amount_paid,
                $invoice['invoice_number'],
                $new_balance > 0 ? sprintf('Balance remaining: $%.2f', $new_balance) : 'Invoice fully paid.'
            );
            
            $stmt = $pdo->prepare('
                INSERT INTO client_notifications 
                (client_id, notification_type, invoice_id, message, amount, is_read) 
                VALUES (?, ?, ?, ?, ?, 0)
            ');
            $stmt->execute([
                $invoice['client_id'],
                'payment_received',
                $invoice_id,
                $notification_message,
                $new_balance > 0 ? $new_balance : null
            ]);
            
            $pdo->commit();
            
            // Redirect to payment history with success message
            header('Location: payment_history.php?invoice_id=' . $invoice_id . '&success_msg=Payment of $' . number_format($amount_paid, 2) . ' recorded successfully');
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Error recording payment: ' . $e->getMessage();
            error_log('Payment recording error: ' . $e->getMessage());
        }
    }
}

// Get existing payment history for this invoice
$stmt = $pdo->prepare('
    SELECT ph.*, a.username as recorded_by_name 
    FROM payment_history ph 
    LEFT JOIN accounts a ON ph.recorded_by = a.id 
    WHERE ph.invoice_id = ? 
    ORDER BY ph.payment_date DESC
');
$stmt->execute([$invoice_id]);
$payment_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<?=template_admin_header('Record Payment - Invoice #' . $invoice['invoice_number'], 'invoices', 'invoices')?>

<?=generate_breadcrumbs([
    ['label' => 'Invoices', 'url' => 'invoices.php'],
    ['label' => 'Invoice #' . $invoice['invoice_number'], 'url' => 'invoice.php?id=' . $invoice_id],
    ['label' => 'Record Payment']
])?>

<style>
    <style>
        .payment-form-container {
            max-width: 800px;
            margin: 20px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .invoice-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 30px;
            border-left: 4px solid #6b46c1;
        }
        
        .invoice-summary h3 {
            margin-top: 0;
            color: #6b46c1;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .summary-row:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 1.1em;
            color: #6b46c1;
            margin-top: 10px;
            padding-top: 15px;
            border-top: 2px solid #6b46c1;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #6b46c1;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #8e44ad;
            box-shadow: 0 0 0 3px rgba(107, 70, 193, 0.15);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary {
            background: #6b46c1;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a3aa0;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .payment-history {
            margin-top: 40px;
            padding-top: 40px;
            border-top: 2px solid #dee2e6;
        }
        
        .payment-history h3 {
            color: #6b46c1;
            margin-bottom: 20px;
        }
        
        .payment-history table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .payment-history th,
        .payment-history td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        .payment-history th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        
        .payment-history tr:hover {
            background: #f8f9fa;
        }
        
        .no-payments {
            text-align: center;
            padding: 40px;
            color: #6c757d;
            font-style: italic;
        }
    </style>

<div class="payment-form-container">
    <h1>Record Payment</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <!-- Invoice Summary -->
        <div class="invoice-summary">
            <h3>Invoice #<?= htmlspecialchars($invoice['invoice_number']) ?></h3>
            <div class="summary-row">
                <span>Client:</span>
                <strong><?= htmlspecialchars($client['business_name']) ?></strong>
            </div>
            <div class="summary-row">
                <span>Invoice Total:</span>
                <span>$<?= number_format($total, 2) ?></span>
            </div>
            <div class="summary-row">
                <span>Amount Paid:</span>
                <span>$<?= number_format($invoice['paid_total'], 2) ?></span>
            </div>
            <div class="summary-row">
                <span>Balance Due:</span>
                <strong>$<?= number_format($balance_due, 2) ?></strong>
            </div>
        </div>
        
        <!-- Payment Form -->
        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label for="amount_paid">Payment Amount *</label>
                    <input type="number" 
                           id="amount_paid" 
                           name="amount_paid" 
                           step="0.01" 
                           min="0.01" 
                           max="<?= number_format($balance_due, 2, '.', '') ?>" 
                           value="<?= number_format($balance_due, 2, '.', '') ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="payment_method">Payment Method *</label>
                    <select id="payment_method" name="payment_method" required>
                        <option value="">Select Method</option>
                        <option value="Cash">Cash</option>
                        <option value="Check">Check</option>
                        <option value="PayPal">PayPal</option>
                        <option value="Credit Card">Credit Card</option>
                        <option value="Debit Card">Debit Card</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Zelle">Zelle</option>
                        <option value="Venmo">Venmo</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="payment_date">Payment Date *</label>
                    <input type="datetime-local" 
                           id="payment_date" 
                           name="payment_date" 
                           value="<?= date('Y-m-d\TH:i') ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="payment_reference">Reference (Check #, Confirmation, etc.)</label>
                    <input type="text" 
                           id="payment_reference" 
                           name="payment_reference" 
                           placeholder="e.g., Check #1234">
                </div>
            </div>
            
            <div class="form-group">
                <label for="transaction_id">Transaction ID (PayPal, Bank Reference, etc.)</label>
                <input type="text" 
                       id="transaction_id" 
                       name="transaction_id" 
                       placeholder="e.g., TXN12345678">
            </div>
            
            <div class="form-group">
                <label for="notes">Notes (Optional)</label>
                <textarea id="notes" 
                          name="notes" 
                          placeholder="Add any additional notes about this payment..."></textarea>
            </div>
            
            <div class="btn-group">
                <button type="submit" name="record_payment" class="btn btn-primary">Record Payment</button>
                <a href="invoice.php?id=<?= $invoice_id ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
        
        <!-- Payment History -->
        <?php if (count($payment_history) > 0): ?>
        <div class="payment-history">
            <h3>Payment History</h3>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th>Recorded By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payment_history as $payment): ?>
                    <tr>
                        <td><?= date('M d, Y g:i A', strtotime($payment['payment_date'])) ?></td>
                        <td>$<?= number_format($payment['amount_paid'], 2) ?></td>
                        <td><?= htmlspecialchars($payment['payment_method']) ?></td>
                        <td><?= htmlspecialchars($payment['payment_reference'] ?: '—') ?></td>
                        <td><?= htmlspecialchars($payment['recorded_by_name'] ?: 'System') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="payment-history">
            <h3>Payment History</h3>
            <div class="no-payments">No payments recorded yet.</div>
        </div>
        <?php endif; ?>
    </div>

<?=template_admin_footer()?>
