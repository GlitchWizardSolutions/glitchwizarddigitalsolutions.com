<?php
require 'assets/includes/admin_config.php';
// Output JSON
header('Content-Type: application/json; charset=utf-8');
// Digital Downloads Endpoint
if (isset($_GET['action']) && $_GET['action'] == 'add_client') {
    // Validation
    if (empty($_POST['first_name']) || empty($_POST['email'])) {
        echo '{"status":"error","message":"The first name and email fields are required!"}';
        exit;
    }
    // Validate email
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        echo '{"status":"error","message":"Invalid email address!"}';
        exit;
    }
    // Check if the email already exists
    $stmt = $pdo->prepare('SELECT * FROM invoice_client WHERE email = ?');
    $stmt->execute([ $_POST['email'] ]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($client) {
        echo '{"status":"error","message":"Client already exists with that email!"}';
        exit;
    }
    // Add a new client
    $created = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare('INSERT INTO invoice_client (first_name, last_name, email, phone, address_street, address_city, address_state, address_zip, address_country, created) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([ $_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['phone'], $_POST['address_street'], $_POST['address_city'], $_POST['address_state'], $_POST['address_zip'], $_POST['address_country'], $created ]);
    $id = $pdo->lastInsertId();
    echo '{"status":"success","message":"Client added!","client_id":' . $id . '}';
}
// Create invoice endpoint
if (isset($_GET['action']) && $_GET['action'] == 'create_invoice') {
    // Calculate payment amount
    $payment_amount = 0;
    if (isset($_POST['item_id']) && is_array($_POST['item_id']) && count($_POST['item_id']) > 0) {
        foreach ($_POST['item_id'] as $i => $item_id) {
            $payment_amount += $_POST['item_price'][$i] * $_POST['item_quantity'][$i];
        }
    }
    // Calculate tax
    $tax_total = 0;
    $tax = 'fixed';
    if (isset($_POST['tax'])) {
        if (strpos($_POST['tax'], '%') !== false) {
            $tax_total = $payment_amount * (floatval(str_replace('%', '', $_POST['tax'])) / 100);
            $tax = $_POST['tax'];
        } else {
            $tax_total = floatval($_POST['tax']);
        }
    }
    // Get the payment methods as a comma separated string
    $payment_methods = isset($_POST['payment_methods']) ? implode(', ', $_POST['payment_methods']) : ''; 
    // Insert the invoice
    $stmt = $pdo->prepare('INSERT INTO invoices (client_id, invoice_number, payment_amount, payment_status, payment_methods, notes, viewed, due_date, created, tax, tax_total, invoice_template, recurrence, recurrence_period, recurrence_period_type) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([ $_POST['client_id'], $_POST['invoice_number'], $payment_amount, $_POST['payment_status'], $payment_methods, $_POST['notes'], 0, $_POST['due_date'], $_POST['created'], $tax, $tax_total, $_POST['template'], $_POST['recurrence'], $_POST['recurrence_period'], $_POST['recurrence_period_type'] ]);
    $invoice_id = $pdo->lastInsertId();
    // add items
    addItems($pdo, $_POST['invoice_number']);
    // Create PDF
    $stmt = $pdo->prepare('SELECT * FROM invoices WHERE invoice_number = ?');
    $stmt->execute([ $_POST['invoice_number'] ]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
    // Get invoice items
    $stmt = $pdo->prepare('SELECT * FROM invoice_items WHERE invoice_number = ?');
    $stmt->execute([ $invoice['invoice_number'] ]);
    $invoice_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Get client details
    $stmt = $pdo->prepare('SELECT * FROM clients WHERE id = ?');
    $stmt->execute([ $invoice['client_id'] ]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    // Generate pdf
    create_invoice_pdf($invoice, $invoice_items, $client);
    // Send email
    if (isset($_POST['send_email'])) {
        send_client_invoice_email($invoice, $client);
    }
    // Output response
    echo '{"status":"success","message":"Invoice created!","invoice_id":"' . $invoice_id . '"}';
}
?>