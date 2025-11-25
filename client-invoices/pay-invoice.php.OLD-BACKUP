<?php
// production  9/13/24
// refreshed   6/14/25
// re-deployed 6/15/25

// Conditionally load Coinbase Commerce classes if library exists
if (file_exists('lib/vendor/autoload.php')) {
    require_once 'lib/vendor/autoload.php';
}

// Use fully qualified class names to avoid static analysis errors
// use CoinbaseCommerce\ApiClient;
// use CoinbaseCommerce\Resources\Charge;

include 'main.php';
// Connect to the database
try {
    $pdo = new PDO('mysql:host=' . db_host . ';dbname=' . db_name . ';charset=' . db_charset, db_user, db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
    // If there is an error with the connection, stop the script and display the error.
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
// Get payment methods
$payment_methods = explode(', ', $invoice['payment_methods']);
// If payment methods is empty, use all available methods as fallback
if (empty($invoice['payment_methods']) || (count($payment_methods) == 1 && empty($payment_methods[0]))) {
    $payment_methods = [];
    if (paypal_enabled) $payment_methods[] = 'PayPal';
    if (stripe_enabled) $payment_methods[] = 'Stripe';
    if (coinbase_enabled) $payment_methods[] = 'Coinbase';
    $payment_methods[] = 'Cash';
}
// Process paypal payment
if (isset($_POST['method']) && $_POST['method'] == 'paypal' && paypal_enabled && in_array('PayPal', $payment_methods)) {
    // Process paypal standard checkout
    $data = [
        'cmd' => '_xclick',
        'charset' => 'UTF-8',
        'business' => paypal_email,
        'notify_url' => paypal_ipn_url,
        'currency_code'	=> paypal_currency,
        'item_name' => 'Invoice ' . $invoice['invoice_number'] . ($invoice['payment_status'] == 'Balance' ? ' (Balance Due)' : ''),
        'item_number' => $invoice['invoice_number'],
        'amount' => $amount_to_charge,
        'no_shipping' => 1,
        'no_note' => 1,
        'return' => invoice_base_url . 'invoice.php?id=' . $invoice['invoice_number'] . '&success=true',
        'cancel_return' => invoice_base_url . 'invoice.php?id=' . $invoice['invoice_number'] . '&cancel=true',
        'custom' => $invoice['invoice_number']
    ];
    // Redirect to paypal
    header('Location: ' . (paypal_testmode ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr') . '?' . http_build_query($data));
    exit;
}
// Process stripe payment
if (isset($_POST['method']) && $_POST['method'] == 'stripe' && stripe_enabled && in_array('Stripe', $payment_methods)) {
    // Include the Stripe PHP library
    require_once 'lib/stripe/init.php';
    // Set the API key
    $stripe = new \Stripe\StripeClient(stripe_secret_key);
    // Check the webhook secret
    if (empty(stripe_webhook_secret)) {
        // No webhook secret, attempt to create one
        // Get the config.php file contents
        $contents = file_get_contents('config.php');
        if ($contents) {
            // Attempt to create the webhook and get the secret
            $webhook = $stripe->webhookEndpoints->create([
                'url' => stripe_ipn_url,
                'description' => 'invoicesystem', // Feel free to change this
                'enabled_events' => ['checkout.session.completed']
            ]);
            // Update the "stripe_webhook_secret" constant in the config.php file with the new secret
            $contents = preg_replace('/define\(\'stripe_webhook_secret\'\, ?(.*?)\)/s', 'define(\'stripe_webhook_secret\',\'' . $webhook['secret'] . '\')', $contents);
            if (!file_put_contents('config.php', $contents)) {
                // Could not write to config.php file
                exit('Failed to automatically assign the Stripe webhook secret! Please set it manually in the config.php file.');
            }
        } else {
            // Could not open config.php file
            exit('Failed to automatically assign the Stripe webhook secret! Please set it manually in the config.php file.');
        }
    }
    // Create the session
    $session = $stripe->checkout->sessions->create([
        'payment_method_types' => ['card'],
        'line_items' => [
            [
                'quantity' => 1,
                'price_data' => [
                    'currency' => stripe_currency,
                    'product_data' => [
                        'name' => 'Invoice #' . $invoice['invoice_number'] . ($invoice['payment_status'] == 'Balance' ? ' (Balance Due)' : ''),
                        'description' => 'Payment for invoice #' . $invoice['invoice_number'],
                    ],
                    'unit_amount' => $amount_to_charge * 100,
                ]
            ]
        ],
        'mode' => 'payment',
        'success_url' => invoice_base_url . 'invoice.php?id=' . $invoice['invoice_number'] . '&success=true',
        'cancel_url' => invoice_base_url . 'invoice.php?id=' . $invoice['invoice_number'] . '&cancel=true',
        'metadata' => [
            'invoice_id' => $invoice['invoice_number']
        ]
    ]);
    // Redirect to the checkout session
    header('Location:' . $session->url);
	exit;
}
// Process coinbase payment
if (isset($_POST['method']) && $_POST['method'] == 'coinbase' && coinbase_enabled && in_array('Coinbase', $payment_methods)) {
    $coinbase = \CoinbaseCommerce\ApiClient::init(coinbase_key);  
    // Create a charge
    $chargeData = [
        'name' => 'Invoice #' . $invoice['invoice_number'] . ($invoice['payment_status'] == 'Balance' ? ' (Balance Due)' : ''),
        'description' => 'Payment for invoice #' . $invoice['invoice_number'],
        'local_price' => [
            'amount' => $amount_to_charge,
            'currency' => coinbase_currency
        ],
        'pricing_type' => 'fixed_price',
        'metadata' => [
            'invoice_id' => $invoice['invoice_number']
        ],
        'redirect_url' => invoice_base_url . 'invoice.php?id=' . $invoice['invoice_number'] . '&success=true',
        'cancel_url' => invoice_base_url . 'invoice.php?id=' . $invoice['invoice_number'] . '&cancel=true'
    ];
    $charge = \CoinbaseCommerce\Resources\Charge::create($chargeData);
    // Redirect to the charge checkout
    header('Location: ' . $charge->hosted_url);
    exit;
}
// Process bank transfer or cash payment
if (isset($_POST['method']) && ($_POST['method'] == 'banktransfer' || $_POST['method'] == 'cash') && (in_array('Bank Transfer', $payment_methods) || in_array('Cash', $payment_methods))) {
    // Paid with
    $paid_with = $_POST['method'] == 'banktransfer' ? 'Bank Transfer' : 'Cash';
    // Generate unique transaction id
    $transaction_id = 'TXN' . time();
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Insert payment record
        $stmt = $pdo->prepare('INSERT INTO payment_history (invoice_id, amount_paid, payment_method, payment_date, reference_number, notes) VALUES (?, ?, ?, NOW(), ?, ?)');
        $stmt->execute([
            $invoice['id'],
            $amount_to_charge,
            $paid_with,
            $transaction_id,
            'Client submitted ' . strtolower($paid_with) . ' payment (pending verification)'
        ]);
        
        // Update invoice paid_total
        $new_paid_total = $invoice['paid_total'] + $amount_to_charge;
        $stmt = $pdo->prepare('UPDATE invoices SET paid_total = ? WHERE invoice_number = ?');
        $stmt->execute([$new_paid_total, $_GET['id']]);
        
        // Determine new payment status
        if ($new_paid_total >= $invoice_total) {
            // Full payment received - mark as Pending (awaiting verification)
            $new_status = 'Pending';
        } else {
            // Partial payment - keep as Balance
            $new_status = 'Balance';
        }
        
        // Update payment status
        $stmt = $pdo->prepare('UPDATE invoices SET payment_status = ? WHERE invoice_number = ?');
        $stmt->execute([$new_status, $_GET['id']]);
        
        // Create client notification
        $stmt = $pdo->prepare('INSERT INTO client_notifications (invoice_id, notification_type, message, created_at) VALUES (?, ?, ?, NOW())');
        $stmt->execute([
            $invoice['id'],
            'payment_received',
            'Payment of $' . number_format($amount_to_charge, 2) . ' received via ' . $paid_with . ' (pending verification)'
        ]);
        
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('Cash/Bank Transfer payment error: ' . $e->getMessage());
        exit('Payment processing failed. Please contact support.');
    }
    
    // Redirect to the invoice
    header('Location: invoice.php?id=' . $_GET['id'] . '&payment_submitted=true');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Invoice #<?=$invoice['invoice_number']?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 10px; font-size: 28px; }
        .invoice-info { background: #f9f9f9; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .invoice-info p { margin: 8px 0; color: #555; }
        .invoice-info strong { color: #333; }
        .payment-methods { margin: 30px 0; }
        .payment-method { display: block; padding: 15px; margin: 10px 0; background: #fff; border: 2px solid #ddd; border-radius: 5px; cursor: pointer; transition: all 0.3s; }
        .payment-method:hover { border-color: #4CAF50; background: #f0f9f0; }
        .payment-method input[type="radio"] { margin-right: 10px; }
        .payment-method label { cursor: pointer; font-size: 16px; }
        .btn { display: inline-block; padding: 12px 30px; background: #4CAF50; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; text-decoration: none; margin-top: 20px; }
        .btn:hover { background: #45a049; }
        .btn-secondary { background: #999; margin-left: 10px; }
        .btn-secondary:hover { background: #777; }
        .amount { font-size: 32px; color: #4CAF50; font-weight: bold; }
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
            <p><strong>Total Paid:</strong> <span style="color:#2ecc71;">$<?=number_format($invoice['paid_total'], 2)?></span></p>
            <p><strong>Balance Due:</strong> <span class="amount">$<?=number_format($amount_to_charge, 2)?></span></p>
            <?php else: ?>
            <p><strong>Amount Due:</strong> <span class="amount">$<?=number_format($amount_to_charge, 2)?></span></p>
            <?php endif; ?>
            <p><strong>Due Date:</strong> <?=date('F d, Y', strtotime($invoice['due_date']))?></p>
        </div>

        <form action="pay-invoice.php?id=<?=$invoice['invoice_number']?>" method="post">
            <h2>Select Payment Method</h2>
            <!-- Debug: <?php echo 'Available: ' . htmlspecialchars(print_r($payment_methods, true)); ?> -->
            <div class="payment-methods">
                <?php if (paypal_enabled && in_array('PayPal', $payment_methods)): ?>
                <label class="payment-method">
                    <input type="radio" name="method" value="paypal" required>
                    <span>PayPal</span>
                </label>
                <?php endif; ?>
                
                <?php if (stripe_enabled && in_array('Stripe', $payment_methods)): ?>
                <label class="payment-method">
                    <input type="radio" name="method" value="stripe" required>
                    <span>Credit Card (Stripe)</span>
                </label>
                <?php endif; ?>
                
                <?php if (coinbase_enabled && in_array('Coinbase', $payment_methods)): ?>
                <label class="payment-method">
                    <input type="radio" name="method" value="coinbase" required>
                    <span>Cryptocurrency (Coinbase)</span>
                </label>
                <?php endif; ?>
                
                <?php if (in_array('Bank Transfer', $payment_methods)): ?>
                <label class="payment-method">
                    <input type="radio" name="method" value="banktransfer" required>
                    <span>Bank Transfer</span>
                </label>
                <?php endif; ?>
                
                <?php if (in_array('Cash', $payment_methods)): ?>
                <label class="payment-method">
                    <input type="radio" name="method" value="cash" required>
                    <span>Cash</span>
                </label>
                <?php endif; ?>
                
                <?php if (empty(array_filter($payment_methods))): ?>
                <p style="color: #999; padding: 20px; text-align: center;">No payment methods are available for this invoice.</p>
                <?php endif; ?>
            </div>
            
            <button type="submit" class="btn">Continue to Payment</button>
            <a href="invoice.php?id=<?=$invoice['invoice_number']?>" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html>