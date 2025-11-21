<?php
// production 9/13/24
include 'main.php';
// Remove time limit
set_time_limit(0);
// Process PayPal IPN response
if (isset($_GET['method']) && $_GET['method'] == 'paypal') {
    // Get all input variables and convert them all to URL string variables
    $raw_post_data = file_get_contents('php://input');
    if (empty($raw_post_data)) exit;
    $raw_post_array = explode('&', $raw_post_data);
    $post_data = [];
    foreach ($raw_post_array as $keyval) {
        $keyval = explode('=', $keyval);
        if (count($keyval) == 2) {
            if ($keyval[0] === 'payment_date') {
                if (substr_count($keyval[1], '+') === 1) {
                    $keyval[1] = str_replace('+', '%2B', $keyval[1]);
                }
            }
            $post_data[$keyval[0]] = urldecode($keyval[1]);
        }
    }
    $req = 'cmd=_notify-validate';
    foreach ($post_data as $key => $value) {
        $value = urlencode($value);
        $req .= "&$key=$value";
    }
    // Below will verify the transaction with PayPal
    $ch = curl_init(paypal_testmode ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr');
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Connection: Close']);
    $res = curl_exec($ch);
    curl_close($ch);
    // Check if the transaction is verified
    if (strcmp($res, 'VERIFIED') == 0) {
        // Get invoice ID
        $invoice_id = $_POST['custom'];
        // check if payment status is completed
        if ($_POST['payment_status'] == 'Completed') {
            // Update payment status using prepared statement
            $stmt = $pdo->prepare('UPDATE invoices SET payment_status = ?, payment_ref = ?, paid_with = "paypal", paid_total = paid_total + ? WHERE invoice_number = ?');
            $stmt->execute([ 'Paid', $_POST['txn_id'], $_POST['mc_gross'], $invoice_id ]);
            // Retrieve the invoice details
            $stmt = $pdo->prepare('SELECT * FROM invoices WHERE invoice_number = ?');
            $stmt->execute([ $invoice_id ]);
            $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
            // Check if invoice exists
            if ($invoice) {
                // Retrieve the client details
                $stmt = $pdo->prepare('SELECT * FROM invoice_clients WHERE id = ?');
                $stmt->execute([ $invoice['client_id'] ]);
                $client = $stmt->fetch(PDO::FETCH_ASSOC);
                // Check if client exists
                send_admin_invoice_email($invoice, $client);
            }
        }
    }
}
// Process Stripe Webhook response
if (isset($_GET['method']) && $_GET['method'] == 'stripe') {
    // Include stripe lib
    require_once 'lib/stripe/init.php';
    \Stripe\Stripe::setApiKey(stripe_secret_key);
    if (!isset($_SERVER['HTTP_STRIPE_SIGNATURE'])) {
        exit('No signature specified!');
    }
    $payload = @file_get_contents('php://input');
    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
    $event = null;
    try {
        $event = \Stripe\Webhook::constructEvent($payload, $sig_header, stripe_webhook_secret);
    } catch(\UnexpectedValueException $e) {
        http_response_code(400);
        exit;
    } catch(\Stripe\Exception\SignatureVerificationException $e) {
        http_response_code(400);
        exit;
    }
    // Check whether the customer completed the checkout process
    if ($event->type == 'checkout.session.completed') {
        $intent = $event->data->object;
        $stripe = new \Stripe\StripeClient(stripe_secret_key);
        // Inovice ID
        $invoice_id = $intent->metadata->invoice_id;
        // Update payment status using prepared statement
        $stmt = $pdo->prepare('UPDATE invoices SET payment_status = ?, payment_ref = ?, paid_with = "stripe", paid_total = paid_total + ? WHERE invoice_number = ?');
        $stmt->execute([ 'Paid', $intent->payment_intent, $intent->amount_total / 100, $invoice_id ]);
        // Retrieve the invoice details
        $stmt = $pdo->prepare('SELECT * FROM invoices WHERE invoice_number = ?');
        $stmt->execute([ $invoice_id ]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        // Check if invoice exists
        if ($invoice) {
            // Retrieve the client details
            $stmt = $pdo->prepare('SELECT * FROM invoice_clients WHERE id = ?');
            $stmt->execute([ $invoice['client_id'] ]);
            $client = $stmt->fetch(PDO::FETCH_ASSOC);
            // Check if client exists
            send_admin_invoice_email($invoice, $client);
        }
    }
}
// Process Coinbase Webhook response
if (isset($_GET['method']) && $_GET['method'] == 'coinbase') {
    // Retrieve input data
    $coinbase = json_decode(file_get_contents('php://input'), true);
    // Validation
    if (isset($_GET['key'], $coinbase['event']) && isset($coinbase['event']['type']) && $_GET['key'] == coinbase_secret && ($coinbase['event']['type'] == 'charge:confirmed' || $coinbase['event']['type'] == 'charge:resolved')) {
        // Transaction is verified and successful...
        $invoice_id = $coinbase['event']['data']['metadata']['invoice_id'];   
        // Update payment status using prepared statement
        $stmt = $pdo->prepare('UPDATE invoices SET payment_status = ?, payment_ref = ?, paid_with = "coinbase", paid_total = paid_total + ? WHERE invoice_number = ?');
        $stmt->execute([ 'Paid', $coinbase['event']['data']['id'], floatval($coinbase['event']['data']['pricing']['local']['amount']), $invoice_id ]);
        // Retrieve the invoice details
        $stmt = $pdo->prepare('SELECT * FROM invoices WHERE invoice_number = ?');
        $stmt->execute([ $invoice_id ]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        // Check if invoice exists
        if ($invoice) {
            // Retrieve the client details
            $stmt = $pdo->prepare('SELECT * FROM invoice_clients WHERE id = ?');
            $stmt->execute([ $invoice['client_id'] ]);
            $client = $stmt->fetch(PDO::FETCH_ASSOC);
            // Check if client exists
            send_admin_invoice_email($invoice, $client);
        }
    }
}
?>