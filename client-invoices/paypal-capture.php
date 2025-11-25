<?php
// PayPal Payment Capture Handler
// This file receives payment confirmation from PayPal Smart Payment Buttons
// and records the payment in the database

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_log("PayPal Capture: Request received at " . date('Y-m-d H:i:s'));

include 'main.php';

// Connect to the database
try {
    $pdo = new PDO('mysql:host=' . db_host . ';dbname=' . db_name . ';charset=' . db_charset, db_user, db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get JSON input
$input = file_get_contents('php://input');
error_log("PayPal Capture: Raw input: " . $input);
$data = json_decode($input, true);
error_log("PayPal Capture: Decoded data: " . print_r($data, true));

// Validate input
if (!$data || !isset($data['orderID']) || !isset($data['invoice_number'])) {
    error_log("PayPal Capture: Invalid request data - " . print_r($data, true));
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

$order_id = $data['orderID'];
$invoice_number = $data['invoice_number'];
$details = $data['details'];

// Verify the payment with PayPal API
try {
    // Get PayPal access token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, paypal_base_url . '/v1/oauth2/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
    curl_setopt($ch, CURLOPT_USERPWD, paypal_client_id . ':' . paypal_secret);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Accept-Language: en_US'
    ]);
    
    $token_response = curl_exec($ch);
    $token_data = json_decode($token_response, true);
    
    if (!isset($token_data['access_token'])) {
        throw new Exception('Failed to get PayPal access token');
    }
    
    $access_token = $token_data['access_token'];
    
    // Verify the order details with PayPal
    curl_setopt($ch, CURLOPT_URL, paypal_base_url . '/v2/checkout/orders/' . $order_id);
    curl_setopt($ch, CURLOPT_POST, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token
    ]);
    
    $order_response = curl_exec($ch);
    $order_data = json_decode($order_response, true);
    curl_close($ch);
    
    // Verify payment was completed
    if (!isset($order_data['status']) || $order_data['status'] !== 'COMPLETED') {
        throw new Exception('Payment not completed');
    }
    
    // Get payment amount and details
    $capture = $order_data['purchase_units'][0]['payments']['captures'][0];
    $payment_amount = floatval($capture['amount']['value']);
    $transaction_id = $capture['id'];
    $payer_email = $order_data['payer']['email_address'] ?? '';
    $payer_name = ($order_data['payer']['name']['given_name'] ?? '') . ' ' . ($order_data['payer']['name']['surname'] ?? '');
    
    // Get the invoice
    $stmt = $pdo->prepare('SELECT * FROM invoices WHERE invoice_number = ?');
    $stmt->execute([$invoice_number]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$invoice) {
        throw new Exception('Invoice not found');
    }
    
    // Calculate invoice totals
    $invoice_total = $invoice['payment_amount'] + $invoice['tax_total'];
    $balance_due = $invoice_total - $invoice['paid_total'];
    
    // Verify payment amount matches what was expected
    $expected_amount = ($invoice['payment_status'] == 'Balance' && $balance_due > 0) ? $balance_due : $invoice_total;
    
    if (abs($payment_amount - $expected_amount) > 0.01) {
        // Amount mismatch - still record but flag it
        error_log("PayPal payment amount mismatch: Expected $expected_amount, got $payment_amount for invoice $invoice_number");
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Record payment in payment_history table
    $stmt = $pdo->prepare('
        INSERT INTO payment_history (
            invoice_id, 
            payment_date, 
            amount, 
            payment_method, 
            reference_number, 
            notes,
            recorded_by
        ) VALUES (?, NOW(), ?, ?, ?, ?, NULL)
    ');
    
    $notes = "PayPal Order ID: $order_id\nPayer: $payer_name ($payer_email)";
    
    $stmt->execute([
        $invoice['id'],
        $payment_amount,
        'PayPal',
        $transaction_id,
        $notes
    ]);
    
    // Update invoice paid_total
    $new_paid_total = $invoice['paid_total'] + $payment_amount;
    $stmt = $pdo->prepare('UPDATE invoices SET paid_total = ? WHERE id = ?');
    $stmt->execute([$new_paid_total, $invoice['id']]);
    
    // Determine new payment status
    $remaining_balance = $invoice_total - $new_paid_total;
    
    if ($remaining_balance <= 0.01) {
        // Fully paid
        $new_status = 'Paid';
    } else {
        // Partial payment
        $new_status = 'Balance';
    }
    
    // Update invoice status
    $stmt = $pdo->prepare('UPDATE invoices SET payment_status = ? WHERE id = ?');
    $stmt->execute([$new_status, $invoice['id']]);
    
    // Create client notification
    $stmt = $pdo->prepare('
        INSERT INTO client_notifications (client_id, invoice_id, message, date_created) 
        VALUES (?, ?, ?, NOW())
    ');
    
    $message = $new_status == 'Paid' 
        ? "Payment of $" . number_format($payment_amount, 2) . " received for Invoice #{$invoice['invoice_number']}. Invoice is now fully paid."
        : "Payment of $" . number_format($payment_amount, 2) . " received for Invoice #{$invoice['invoice_number']}. Remaining balance: $" . number_format($remaining_balance, 2);
    
    $stmt->execute([$invoice['client_id'], $invoice['id'], $message]);
    
    // Commit transaction
    $pdo->commit();
    
    // Send success response
    echo json_encode([
        'success' => true,
        'message' => 'Payment recorded successfully',
        'payment_amount' => $payment_amount,
        'new_status' => $new_status,
        'remaining_balance' => $remaining_balance
    ]);
    
} catch (Exception $e) {
    // Rollback transaction if active
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log error
    error_log('PayPal Capture Error: ' . $e->getMessage());
    
    // Send error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
