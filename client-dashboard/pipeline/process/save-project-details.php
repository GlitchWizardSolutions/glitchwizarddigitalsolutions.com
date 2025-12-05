<?php
/*******************************************************************************
 * SAVE PROJECT DETAILS & GENERATE DEPOSIT INVOICE
 * Processes Guest Level project details form and creates 50% deposit invoice
 * Created: 2025-12-04
 ******************************************************************************/

session_start();
require_once '../../../private/config.php';
require_once '../../lib/service-catalog-functions.php';

// Verify user is logged in
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['id'])) {
    header('Location: ../../index.php?error=not_logged_in');
    exit;
}

// Verify this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../guest-project-details.php?error=invalid_request');
    exit;
}

$acc_id = $_SESSION['id'];

try {
    // Get pipeline status
    $stmt = $pdo->prepare('SELECT * FROM client_pipeline_status WHERE acc_id = ?');
    $stmt->execute([$acc_id]);
    $pipeline = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pipeline || empty($pipeline['service_selected'])) {
        header('Location: ../guest-service-selection.php?error=no_service_selected');
        exit;
    }

    // Collect form data
    $form_data = [
        'business_name' => trim($_POST['business_name'] ?? ''),
        'industry' => $_POST['industry'] ?? '',
        'project_goals' => trim($_POST['project_goals'] ?? ''),
        'target_audience' => trim($_POST['target_audience'] ?? ''),
        'timeline_preference' => $_POST['timeline_preference'] ?? '',
        'domain_preference' => trim($_POST['domain_preference'] ?? ''),
        'color_preferences' => trim($_POST['color_preferences'] ?? ''),
        'additional_notes' => trim($_POST['additional_notes'] ?? ''),
        'submitted_at' => date('Y-m-d H:i:s')
    ];

    // Validate required fields
    if (empty($form_data['business_name']) || empty($form_data['industry']) || empty($form_data['project_goals'])) {
        header('Location: ../guest-project-details.php?error=missing_required_fields');
        exit;
    }

    // Save form data to client_pipeline_forms
    $stmt = $pdo->prepare('
        INSERT INTO client_pipeline_forms (acc_id, form_name, form_data, submitted_at)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
            form_data = VALUES(form_data),
            submitted_at = NOW()
    ');
    $stmt->execute([$acc_id, 'project_details', json_encode($form_data)]);

    // =========================================================================
    // GENERATE DEPOSIT INVOICE (50%)
    // =========================================================================

    // Get user account details
    $stmt = $pdo->prepare('SELECT fname, lname, company, email, address, city, state, zip FROM accounts WHERE acc_id = ?');
    $stmt->execute([$acc_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Build service array for invoice based on selections
    $service_slugs = [];
    $quantities = [];

    // Add website package if selected
    if (!empty($pipeline['service_selected'])) {
        $service_slugs[] = $pipeline['service_selected'];
        $quantities[] = 1;
    }

    // Add domain/email if selected
    if ($pipeline['add_domain_email']) {
        $service_slugs[] = 'domain-email-setup';
        $quantities[] = 1;
    }

    // Add Google Business Profile if selected
    if ($pipeline['add_google_business']) {
        $service_slugs[] = 'google-business-profile';
        $quantities[] = 1;
    }

    // Add DIY content creation if selected
    if ($pipeline['add_diy_content_creation']) {
        $service_slugs[] = 'content-creation-diy';
        $quantities[] = 1;
    }

    // Validate that at least one service is selected
    if (empty($service_slugs)) {
        header('Location: ../guest-project-details.php?error=no_services_selected');
        exit;
    }

    // Calculate total and deposit amount
    // Deposit only applies to website packages - standalone services are paid in full
    $hasPackage = !empty($pipeline['service_selected']);
    $total = calculate_total_from_services($pdo, $service_slugs, $quantities);
    
    if ($hasPackage) {
        $deposit_amount = $total * 0.50;
        $invoice_notes = "50% Deposit Invoice for {$form_data['business_name']}. Remaining 50% due upon project completion.";
    } else {
        $deposit_amount = $total; // Standalone services paid in full
        $invoice_notes = "Invoice for selected services for {$form_data['business_name']}.";
    }

    // Get next invoice number
    $stmt = $pdo->query('SELECT MAX(invoice_id) as max_id FROM invoices');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $next_invoice_number = ($result['max_id'] ?? 0) + 1;
    $invoice_number = 'INV-' . str_pad($next_invoice_number, 5, '0', STR_PAD_LEFT);

    // Create invoice
    $stmt = $pdo->prepare('
        INSERT INTO invoices (
            invoice_number, acc_id, invoice_date, due_date, 
            subtotal, tax, total, status, notes, created_at
        ) VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 14 DAY), ?, 0, ?, \'pending\', ?, NOW())
    ');
    $stmt->execute([$invoice_number, $acc_id, $deposit_amount, $deposit_amount, $invoice_notes]);
    $invoice_id = $pdo->lastInsertId();

    // Create invoice items
    $invoice_items = create_invoice_items_from_services($pdo, $service_slugs, $quantities);
    
    $stmt = $pdo->prepare('
        INSERT INTO invoice_items (invoice_id, item_name, item_description, quantity, unit_price, total_price)
        VALUES (?, ?, ?, ?, ?, ?)
    ');

    foreach ($invoice_items as $item) {
        // Check if this is a website package (requires 50% deposit)
        $isPackage = in_array($item['slug'] ?? '', [
            'website-mvp', 'website-foundational', 'website-expanded',
            'website-adhd-friendly', 'google-business-profile' // Google Business might be package
        ]) && strpos($item['slug'] ?? '', 'website-') === 0;
        
        if ($hasPackage && $isPackage) {
            // Website package items - 50% deposit
            $item_price = $item['unit_price'] * 0.50;
            $description = $item['description'] . ' (50% deposit)';
        } else {
            // Standalone services or add-ons - full price
            $item_price = $item['unit_price'];
            $description = $item['description'];
        }
        
        $stmt->execute([
            $invoice_id,
            $item['name'],
            $description,
            $item['quantity'],
            $item_price,
            $item_price * $item['quantity']
        ]);
    }

    // Update pipeline status
    $stmt = $pdo->prepare('
        UPDATE client_pipeline_status 
        SET current_step = \'await_deposit\',
            deposit_invoice_number = ?,
            project_details_date = NOW()
        WHERE acc_id = ?
    ');
    $stmt->execute([$invoice_number, $acc_id]);

    // =========================================================================
    // SEND EMAIL NOTIFICATION
    // =========================================================================

    $to = $user['email'];
    $from = 'noreply@digitalsolutions.com';
    $subject = "Your Deposit Invoice - {$invoice_number}";
    
    $email_body = "
    <html>
    <head><style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .header { background: #4154f1; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; }
        .invoice-box { border: 2px solid #4154f1; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .amount { font-size: 24px; color: #4154f1; font-weight: bold; }
        .button { background: #4154f1; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 20px 0; }
    </style></head>
    <body>
        <div class='header'>
            <h1>Thank You for Your Project Details!</h1>
        </div>
        <div class='content'>
            <p>Hi {$user['fname']},</p>
            
            <p>We're excited to start working on <strong>{$form_data['business_name']}</strong>!</p>
            
            <div class='invoice-box'>
                <h2>Deposit Invoice: {$invoice_number}</h2>
                <p><strong>Amount Due:</strong> <span class='amount'>\$" . number_format($deposit_amount, 2) . "</span></p>
                <p><strong>Due Date:</strong> " . date('F j, Y', strtotime('+14 days')) . "</p>
                <p><strong>Project Total:</strong> \$" . number_format($total, 2) . "</p>
            </div>
            
            <p>This 50% deposit secures your spot in our development queue. The remaining 50% will be invoiced when your website is complete and ready to launch.</p>
            
            <a href='https://digitalsolutions.com/client-dashboard/invoices/view-invoice.php?id={$invoice_id}' class='button'>View & Pay Invoice</a>
            
            <p><strong>What's Next:</strong></p>
            <ul>
                <li>Pay your deposit invoice</li>
                <li>We'll send you onboarding forms to gather content and design preferences</li>
                <li>Our team will begin creating your website</li>
                <li>You'll receive regular updates on progress</li>
            </ul>
            
            <p>Questions? Just reply to this email - we're here to help!</p>
            
            <p>Best regards,<br>
            The GlitchWizard Team</p>
        </div>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: GlitchWizard <{$from}>\r\n";

    mail($to, $subject, $email_body, $headers);

    // Redirect to success page
    header("Location: ../guest-invoice-sent.php?invoice_number={$invoice_number}&amount=" . number_format($deposit_amount, 2));
    exit;

} catch (Exception $e) {
    error_log('Project Details Save Error: ' . $e->getMessage());
    header('Location: ../guest-project-details.php?error=save_failed');
    exit;
}
