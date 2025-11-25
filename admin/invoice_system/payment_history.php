<?php
// Payment History Viewer
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

if (!$client) {
    header('Location: invoices.php?error_msg=Client not found');
    exit;
}

// Calculate totals - use balance_due if available, otherwise calculate
$total = $invoice['payment_amount'] + $invoice['tax_total'];
$balance_due = isset($invoice['balance_due']) ? $invoice['balance_due'] : ($total - $invoice['paid_total']);

// Get payment history
$stmt = $pdo->prepare('
    SELECT ph.*, a.username as recorded_by_name 
    FROM payment_history ph 
    LEFT JOIN accounts a ON ph.recorded_by = a.id 
    WHERE ph.invoice_id = ? 
    ORDER BY ph.payment_date DESC
');
$stmt->execute([$invoice_id]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<?=template_admin_header('Payment History - Invoice #' . $invoice['invoice_number'], 'invoices', 'invoices')?>

<?=generate_breadcrumbs([
    ['label' => 'Invoices', 'url' => 'invoices.php'],
    ['label' => 'Invoice #' . $invoice['invoice_number'], 'url' => 'invoice.php?id=' . $invoice_id],
    ['label' => 'Payment History']
])?>

<style>
    <style>
        .history-container {
            max-width: 1200px;
            margin: 20px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #6b46c1;
        }
        
        .header-section h1 {
            margin: 0;
            color: #6b46c1;
        }
        
        .invoice-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .summary-item {
            text-align: center;
        }
        
        .summary-item .label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        
        .summary-item .value {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        
        .summary-item.balance .value {
            color: #6b46c1;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
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
        
        .btn-danger {
            background: #dc3545;
            color: white;
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .payments-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .payments-table th,
        .payments-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        .payments-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #6b46c1;
        }
        
        .payments-table tr:hover {
            background: #f8f9fa;
        }
        
        .payment-amount {
            font-weight: bold;
            color: #28a745;
            font-size: 16px;
        }
        
        .no-payments {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .no-payments i {
            font-size: 48px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .payment-notes {
            font-size: 13px;
            color: #6c757d;
            font-style: italic;
            margin-top: 5px;
        }
        
        .payment-details {
            font-size: 13px;
            color: #6c757d;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 30px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            margin-bottom: 20px;
        }
        
        .modal-header h2 {
            margin: 0;
            color: #dc3545;
        }
        
        .modal-footer {
            margin-top: 30px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #000;
        }
    </style>

<div class="history-container">
    <?php if (isset($_GET['success_msg'])): ?>
        <div class="alert alert-success">
            <?php 
            if ($_GET['success_msg'] == '1') {
                echo 'Payment deleted successfully.';
            } else {
                echo htmlspecialchars($_GET['success_msg']);
            }
            ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error_msg'])): ?>
        <div class="alert alert-error">
            <?= htmlspecialchars($_GET['error_msg']) ?>
        </div>
        <?php endif; ?>
        
        <div class="header-section">
            <div>
                <h1>Payment History</h1>
                <p>Invoice #<?= htmlspecialchars($invoice['invoice_number']) ?> - <?= htmlspecialchars($client['business_name']) ?></p>
            </div>
            <div>
                <a href="record_payment.php?invoice_id=<?= $invoice_id ?>" class="btn btn-primary">+ Record Payment</a>
                <a href="invoice.php?id=<?= $invoice_id ?>" class="btn btn-secondary">Back to Invoice</a>
            </div>
        </div>
        
        <!-- Summary Cards -->
        <div class="invoice-summary">
            <div class="summary-item">
                <div class="label">Invoice Total</div>
                <div class="value">$<?= number_format($total, 2) ?></div>
            </div>
            <div class="summary-item">
                <div class="label">Total Paid</div>
                <div class="value">$<?= number_format($invoice['paid_total'], 2) ?></div>
            </div>
            <div class="summary-item balance">
                <div class="label">Balance Due</div>
                <div class="value">$<?= number_format($balance_due, 2) ?></div>
            </div>
            <div class="summary-item">
                <div class="label">Status</div>
                <div class="value"><?= htmlspecialchars($invoice['payment_status']) ?></div>
            </div>
        </div>
        
        <!-- Payments Table -->
        <?php if (count($payments) > 0): ?>
        <table class="payments-table">
            <thead>
                <tr>
                    <th>Payment Date</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Reference / Transaction ID</th>
                    <th>Recorded By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $payment): ?>
                <tr>
                    <td>
                        <?= date('M d, Y', strtotime($payment['payment_date'])) ?><br>
                        <span class="payment-details"><?= date('g:i A', strtotime($payment['payment_date'])) ?></span>
                    </td>
                    <td class="payment-amount">
                        $<?= number_format($payment['amount_paid'], 2) ?>
                    </td>
                    <td><?= htmlspecialchars($payment['payment_method']) ?></td>
                    <td>
                        <?php if ($payment['payment_reference']): ?>
                            <strong>Ref:</strong> <?= htmlspecialchars($payment['payment_reference']) ?><br>
                        <?php endif; ?>
                        <?php if ($payment['transaction_id']): ?>
                            <strong>TXN:</strong> <?= htmlspecialchars($payment['transaction_id']) ?>
                        <?php endif; ?>
                        <?php if (!$payment['payment_reference'] && !$payment['transaction_id']): ?>
                            <span class="payment-details">—</span>
                        <?php endif; ?>
                        <?php if ($payment['notes']): ?>
                            <div class="payment-notes"><?= htmlspecialchars($payment['notes']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($payment['recorded_by_name'] ?: 'System') ?><br>
                        <span class="payment-details"><?= date('M d, Y', strtotime($payment['created_at'])) ?></span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-danger" onclick="confirmDelete(<?= $payment['id'] ?>, '<?= htmlspecialchars($payment['payment_method']) ?>', <?= $payment['amount_paid'] ?>)">Delete</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="no-payments">
            <i class="fas fa-receipt"></i>
            <h3>No Payments Recorded</h3>
            <p>This invoice has no payment history yet.</p>
            <a href="record_payment.php?invoice_id=<?= $invoice_id ?>" class="btn btn-primary">Record First Payment</a>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closeModal()">&times;</span>
                <h2>Confirm Delete Payment</h2>
            </div>
            <p>Are you sure you want to delete this payment?</p>
            <p><strong id="deleteDetails"></strong></p>
            <p style="color: #dc3545; margin-top: 15px;">
                <strong>Warning:</strong> This will reduce the paid total and may change the invoice status.
            </p>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <a id="confirmDeleteBtn" href="#" class="btn btn-danger">Delete Payment</a>
            </div>
        </div>
    </div>
    
    <script>
        function confirmDelete(paymentId, method, amount) {
            const modal = document.getElementById('deleteModal');
            const details = document.getElementById('deleteDetails');
            const confirmBtn = document.getElementById('confirmDeleteBtn');
            
            details.textContent = '$' + amount.toFixed(2) + ' via ' + method;
            confirmBtn.href = 'delete_payment.php?id=' + paymentId + '&invoice_id=<?= $invoice_id ?>';
            
            modal.style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>

<?=template_admin_footer()?>
