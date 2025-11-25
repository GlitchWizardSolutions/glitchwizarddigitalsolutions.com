<?php
// PayPal Smart Payment Buttons Integration
// Updated: November 2025
// Replaces old PayPal Standard, Stripe, Coinbase, and Cash payment methods

include 'main.php';

// Connect to the database
try {
    $pdo = new PDO('mysql:host=' . db_host . ';dbname=' . db_name . ';charset=' . db_charset, db_user, db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
    trigger_error("Failure to connect to the login database", E_USER_ERROR);
    exit('Failed to connect to database: ' . $exception->getMessage());
}

// Get ID
if (!isset($_GET['id'])) {
    exit('Invoice ID not specified!');
}

// Get the invoice
$stmt = $pdo->prepare('SELECT * FROM invoices WHERE invoice_number = ?');
$stmt->execute([ $_GET['id'] ]);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if invoice exists
if (!$invoice) {
    exit('Invoice does not exist!');
}

// Check if the invoice has been paid - redirect to invoice view
if ($invoice['payment_status'] == 'Paid') {
    header('Location: invoice.php?id=' . $invoice['invoice_number']);
    exit;
}

// Check if invoice can be paid (must be Unpaid or Balance)
if ($invoice['payment_status'] != 'Unpaid' && $invoice['payment_status'] != 'Balance') {
    exit('You cannot pay for this invoice!');
}

// Calculate the amount to charge (balance_due for Balance status, full amount for Unpaid)
$invoice_total = $invoice['payment_amount'] + $invoice['tax_total'];
$balance_due = isset($invoice['balance_due']) ? $invoice['balance_due'] : ($invoice_total - $invoice['paid_total']);
$amount_to_charge = ($invoice['payment_status'] == 'Balance' && $balance_due > 0) ? $balance_due : $invoice_total;

// Get the client
$stmt = $pdo->prepare('SELECT * FROM invoice_clients WHERE id = ?');
$stmt->execute([ $invoice['client_id'] ]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

// Validate client
if (!$client) {
    exit('Could not retrieve client details!');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Pay Invoice #<?=$invoice['invoice_number']?></title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: #f5f7fa;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            margin-top: 0;
            border-bottom: 3px solid #3498db;
            padding-bottom: 15px;
        }
        .invoice-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .invoice-info p {
            margin: 10px 0;
            line-height: 1.6;
        }
        .amount {
            font-size: 32px;
            color: #27ae60;
            font-weight: bold;
            display: block;
            margin: 10px 0;
        }
        .balance-breakdown {
            font-size: 14px;
            color: #7f8c8d;
            margin-top: 5px;
        }
        #paypal-button-container {
            margin: 30px 0;
            min-height: 50px;
        }
        .payment-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
            text-align: center;
        }
        .payment-header h2 {
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        .payment-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 14px;
        }
        .btn-secondary {
            display: inline-block;
            padding: 12px 24px;
            background: #95a5a6;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.3s;
            text-align: center;
        }
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        .loading {
            text-align: center;
            color: #7f8c8d;
            padding: 20px;
        }
        .secure-badge {
            text-align: center;
            color: #95a5a6;
            font-size: 12px;
            margin-top: 20px;
            padding: 10px;
            border-top: 1px solid #ecf0f1;
        }
        .secure-badge svg {
            width: 16px;
            height: 16px;
            vertical-align: middle;
            margin-right: 5px;
        }
        .partial-payment {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .partial-payment h3 {
            margin: 0 0 15px 0;
            color: #856404;
            font-size: 18px;
        }
        .partial-payment label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .amount-input-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }
        .amount-input-wrapper::before {
            content: '$';
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }
        .partial-payment input[type="text"] {
            width: 100%;
            padding: 15px 15px 15px 35px;
            font-size: 24px;
            border: 2px solid #ffc107;
            border-radius: 4px;
            box-sizing: border-box;
            font-weight: 700;
            background: #fff;
            color: #333;
            text-align: left;
            cursor: default;
        }
        .partial-payment input[type="text"]:focus {
            outline: none;
        }
        .partial-payment .hint {
            display: block;
            margin-top: 8px;
            font-size: 13px;
            color: #856404;
        }
        .quick-amounts {
            margin-top: 15px;
        }
        .quick-amounts button {
            padding: 8px 16px;
            margin: 5px 5px 5px 0;
            background: #fff;
            border: 2px solid #ffc107;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            color: #856404;
            transition: all 0.2s;
        }
        .quick-amounts button:hover {
            background: #ffc107;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Pay Invoice</h1>
        
        <div class="invoice-info">
            <p><strong>Invoice Number:</strong> <?=$invoice['invoice_number']?></p>
            <p><strong>Client:</strong> <?=$client['first_name']?> <?=$client['last_name']?></p>
            <?php if ($invoice['payment_status'] == 'Balance'): ?>
            <p><strong>Invoice Total:</strong> $<?=number_format($invoice_total, 2)?></p>
            <p><strong>Total Paid:</strong> <span style="color:#27ae60;">$<?=number_format($invoice['paid_total'], 2)?></span></p>
            <p><strong>Balance Due:</strong> <span class="amount">$<?=number_format($amount_to_charge, 2)?></span></p>
            <?php else: ?>
            <p><strong>Amount Due:</strong> <span class="amount">$<?=number_format($amount_to_charge, 2)?></span></p>
            <?php endif; ?>
            <p><strong>Due Date:</strong> <?=date('F d, Y', strtotime($invoice['due_date']))?></p>
        </div>

        <div class="payment-header">
            <h2>💳 Secure Payment</h2>
            <p>Pay with PayPal, Credit Card, Debit Card, or Buy Now Pay Later</p>
        </div>

        <!-- Partial Payment Option -->
        <div class="partial-payment">
            <h3>💰 Payment Amount</h3>
            <label for="payment-amount">
                <?php if ($invoice['payment_status'] == 'Balance'): ?>
                Select payment amount below:
                <?php else: ?>
                Select payment amount below:
                <?php endif; ?>
            </label>
            <div class="amount-input-wrapper">
                <input type="text" 
                       id="payment-amount" 
                       value="<?=number_format($amount_to_charge, 2)?>"
                       readonly>
            </div>
            <span class="hint">
                Maximum: $<?=number_format($amount_to_charge, 2)?>
            </span>
            
            <!-- Quick Amount Buttons -->
            <div class="quick-amounts">
                <strong style="display: block; margin-bottom: 8px; color: #856404;">Payment Options:</strong>
                <button type="button" onclick="setPaymentAmount(<?=$amount_to_charge?>)">
                    Full Balance ($<?=number_format($amount_to_charge, 2)?>)
                </button>
                <?php if ($amount_to_charge >= 2): ?>
                <button type="button" onclick="setPaymentAmount(<?=$amount_to_charge / 2?>)">
                    Half ($<?=number_format($amount_to_charge / 2, 2)?>)
                </button>
                <?php endif; ?>
                <?php if ($amount_to_charge >= 4): ?>
                <button type="button" onclick="setPaymentAmount(<?=$amount_to_charge / 4?>)">
                    Quarter ($<?=number_format($amount_to_charge / 4, 2)?>)
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- PayPal Smart Payment Buttons Container -->
        <div id="paypal-button-container"></div>
        
        <div style="text-align: center; margin: 20px 0;">
            <a href="invoice.php?id=<?=$invoice['invoice_number']?>" class="btn-secondary">← Back to Invoice</a>
        </div>

        <div class="secure-badge">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            Secure payment processing by PayPal
        </div>
    </div>

    <!-- PayPal SDK -->
    <script src="https://www.paypal.com/sdk/js?client-id=<?=paypal_client_id?>&currency=<?=paypal_currency?>"></script>
    
    <script>
        // Helper function to set payment amount
        function setPaymentAmount(amount) {
            const maxAmount = <?=$amount_to_charge?>;
            const validAmount = Math.min(parseFloat(amount), maxAmount);
            // Update the display field with formatted amount
            document.getElementById('payment-amount').value = validAmount.toFixed(2);
        }
        
        // Get the payment amount from the display field
        function getPaymentAmount() {
            const input = document.getElementById('payment-amount');
            const amount = parseFloat(input.value.replace(/,/g, ''));
            const maxAmount = <?=$amount_to_charge?>;
            
            // Validate amount
            if (isNaN(amount) || amount <= 0) {
                alert('Please select a payment amount.');
                return null;
            }
            
            if (amount > maxAmount) {
                alert('Payment amount cannot exceed the balance due of $' + maxAmount.toFixed(2));
                return null;
            }
            
            return amount.toFixed(2);
        }
        
        paypal.Buttons({
            // Set up the transaction
            createOrder: function(data, actions) {
                const paymentAmount = getPaymentAmount();
                if (!paymentAmount) {
                    return Promise.reject('Invalid payment amount');
                }
                
                return actions.order.create({
                    purchase_units: [{
                        description: 'Invoice <?=$invoice['invoice_number']?><?=($invoice['payment_status'] == 'Balance' ? ' (Partial Payment)' : '')?>',
                        amount: {
                            value: paymentAmount
                        },
                        invoice_id: '<?=$invoice['invoice_number']?>',
                        custom_id: '<?=$invoice['invoice_number']?>'
                    }]
                });
            },
            
            // Handle successful payment
            onApprove: function(data, actions) {
                console.log('PayPal onApprove triggered', data);
                // Send payment details to server for verification and recording
                return fetch('paypal-capture.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        orderID: data.orderID,
                        invoice_number: '<?=$invoice['invoice_number']?>'
                    })
                })
                .then(response => {
                    console.log('Server response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Server response data:', data);
                    if (data.success) {
                        // Redirect to invoice with success message
                        window.location.href = 'invoice.php?id=<?=$invoice['invoice_number']?>&payment_success=true';
                    } else {
                        alert('Payment recorded but there was an issue: ' + data.message);
                        window.location.href = 'invoice.php?id=<?=$invoice['invoice_number']?>';
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    alert('Error communicating with server: ' + error.message);
                });
            },
            
            // Handle errors
            onError: function(err) {
                console.error('PayPal Error:', err);
                alert('An error occurred during payment. Please try again or contact support.');
            },
            
            // Handle cancellation
            onCancel: function(data) {
                // Redirect back to invoice
                window.location.href = 'invoice.php?id=<?=$invoice['invoice_number']?>&payment_cancelled=true';
            },
            
            // Styling
            style: {
                layout: 'vertical',
                color: 'blue',
                shape: 'rect',
                label: 'paypal'
            }
        }).render('#paypal-button-container');
    </script>
</body>
</html>
