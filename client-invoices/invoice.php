<?php
// production  9/13/24
// refreshed   6/14/25 VERIFIED
// re-deployed 6/15/25
include 'main.php';

// Check for payment success/error messages
$payment_message = '';
$payment_message_type = '';

if (isset($_GET['payment_success']) && $_GET['payment_success'] == 'true') {
    $payment_message = 'Payment successful! Thank you for your payment.';
    $payment_message_type = 'success';
} elseif (isset($_GET['payment_cancelled'])) {
    $payment_message = 'Payment was cancelled. No charges were made.';
    $payment_message_type = 'info';
} elseif (isset($_GET['payment_error'])) {
    $payment_message = 'There was an error processing your payment. Please try again or contact support.';
    $payment_message_type = 'error';
}

// Mark notification as read if coming from notification (and not in iframe)
if (isset($_GET['notification_id']) && (!isset($_SERVER['HTTP_SEC_FETCH_DEST']) || (isset($_SERVER['HTTP_SEC_FETCH_DEST']) && $_SERVER['HTTP_SEC_FETCH_DEST'] !== 'iframe'))) {
    $stmt = $pdo->prepare('UPDATE client_notifications SET is_read = 1 WHERE id = ?');
    $stmt->execute([ $_GET['notification_id'] ]);
}

// Check if invoice ID param exists
if (!isset($_GET['id'])) {
    exit('Invoice ID not specified!');
}
// Retrieve the invoice from the database
$stmt = $pdo->prepare('SELECT * FROM invoices WHERE invoice_number = ?');
$stmt->execute([ $_GET['id'] ]);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if invoice exists
if (!$invoice) {
    exit('Invoice does not exist!');
}

// Get invoice items
$stmt = $pdo->prepare('SELECT * FROM invoice_items WHERE invoice_number = ?');
$stmt->execute([ $invoice['invoice_number'] ]);
$invoice_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get client details
$stmt = $pdo->prepare('SELECT * FROM invoice_clients WHERE id = ?');
$stmt->execute([ $invoice['client_id'] ]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

// Get payment history
$stmt = $pdo->prepare('SELECT * FROM payment_history WHERE invoice_number = ? ORDER BY payment_date DESC');
$stmt->execute([ $invoice['invoice_number'] ]);
$payment_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Client address
$client_address = [
    $client['address_street'],
    $client['address_city'],
    $client['address_state'],
    $client['address_zip'],
    $client['address_country']
];

// remove any empty values
$client_address = array_filter($client_address);
// Get payment methods

$payment_methods = explode(', ', $invoice['payment_methods']);
// Determine correct ip address
if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ip = $_SERVER['HTTP_CLIENT_IP'];
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
} else {
    $ip = $_SERVER['REMOTE_ADDR'];
}

// Get ip addresses from the accounts table
$stmt = $pdo->prepare('SELECT ip FROM accounts');
$stmt->execute();
$ips = $stmt->fetchAll(PDO::FETCH_COLUMN);
// Update invoice viewed status and sure the IP doesn't match one of the ip address in the accounts table
if ($invoice['viewed'] == 0 && !in_array($ip, $ips)) {
    $stmt = $pdo->prepare('UPDATE invoices SET viewed = 1 WHERE invoice_number = ?');
    $stmt->execute([ $invoice['invoice_number'] ]);
}

// define invoice, which will prevent direct access to the template
define('INVOICE', true);
// Include the template
clearstatcache();
    // set template path
if (file_exists(client_invoice_defines . 'templates/' . $invoice['invoice_template'] . '/template.php')) {
    // set template path
    define('template_path', client_invoice_defines . 'templates/' . $invoice['invoice_template'] . '/');
    // include the template
    require client_invoice_defines . 'templates/' . $invoice['invoice_template'] . '/template.php';
} else if (file_exists(client_invoice_defines . 'templates/default/template.php')) {
    // set template path
    define('template_path', client_invoice_defines . 'templates/default/');
    // include the default template
    require client_invoice_defines . 'templates/default/template.php';
} else {
   exit('No template could be found!');
  } 
?>