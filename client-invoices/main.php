<?php
// production 9/13/24
// refreshed  6/14/25
include 'assets/includes/invoice-user-config.php';
include 'defines.php';
// Namespaces
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
// Connet to the database
try {
    $pdo = new PDO('mysql:host=' . db_host . ';dbname=' . db_name . ';charset=' . db_charset, db_user, db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
    // If there is an error with the connection, stop the script and display the error.
    exit('Failed to connect to database: ' . $exception->getMessage());
}
// Create invoice PDF function
function create_invoice_pdf($invoice, $invoice_items, $client) {
    define('INVOICE', true);
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
    // Include the template
    if (file_exists(base_path . 'templates/' . $invoice['invoice_template'] . '/template-pdf.php')) {
        require base_path . 'templates/' . $invoice['invoice_template'] . '/template-pdf.php';
        // Save the output to a file
        $pdf->Output(base_path . 'pdfs/' . $invoice['invoice_number'] . '.pdf', 'F');
        return true;
    } else if (file_exists(base_path . 'templates/default/template-pdf.php')) {
        require base_path . 'templates/default/template-pdf.php';
        // Save the output to a file
        $pdf->Output(base_path . 'pdfs/' . $invoice['invoice_number'] . '.pdf', 'F');
        return true;
    } 
    return false;
}

// Email functions are in unified email-system.php
// Already loaded by invoice_system_config.php via invoice-user-config.php
?>