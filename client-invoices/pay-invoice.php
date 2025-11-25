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
    <link href="<?=invoice_base_url?>style.css" rel="stylesheet" type="text/css">
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
        paypal.Buttons({
            // Set up the transaction
            createOrder: function(data, actions) {
                return actions.order.create({
                    purchase_units: [{
                        description: 'Invoice <?=$invoice['invoice_number']?><?=($invoice['payment_status'] == 'Balance' ? ' (Balance Due)' : '')?>',
                        amount: {
                            value: '<?=number_format($amount_to_charge, 2, '.', '')?>'
                        },
                        invoice_id: '<?=$invoice['invoice_number']?>',
                        custom_id: '<?=$invoice['invoice_number']?>'
                    }]
                });
            },
            
            // Handle successful payment
            onApprove: function(data, actions) {
                console.log('PayPal onApprove triggered', data);
                return actions.order.capture().then(function(details) {
                    console.log('Payment captured', details);
                    // Send payment details to server for verification and recording
                    return fetch('paypal-capture.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            orderID: data.orderID,
                            invoice_number: '<?=$invoice['invoice_number']?>',
                            details: details
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
