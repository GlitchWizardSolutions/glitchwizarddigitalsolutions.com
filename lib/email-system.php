<?php
/*******************************************************************************
 * UNIFIED EMAIL SYSTEM
 * 
 * Consolidated email handler for all application emails
 * Replaces multiple duplicate email-process.php files
 * 
 * LOCATION: /public_html/lib/email-system.php
 * CREATED: 2025-11-19
 * 
 * FUNCTIONS:
 * - send_email() - General purpose emails (2FA, activation, password reset)
 * - send_ticket_email() - Ticketing system emails
 * - send_client_invoice_email() - Client invoice emails
 * - send_admin_invoice_email() - Admin invoice notifications
 * 
 * USAGE:
 * Include this file once in your config or main includes:
 *   require_once public_path . 'lib/email-system.php';
 * 
 * Then call any email function directly.
 ******************************************************************************/

// Load PHPMailer library ONCE at the top
if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
    require_once __DIR__ . '/phpmailer/Exception.php';
    require_once __DIR__ . '/phpmailer/PHPMailer.php';
    require_once __DIR__ . '/phpmailer/SMTP.php';
}

// Namespaces
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Configure PHPMailer instance with SMTP settings
 * Uses password authentication
 * 
 * @param PHPMailer $mail PHPMailer instance to configure
 * @return void
 */
function configure_smtp_mail($mail) {
    // Only configure if SMTP is enabled
    if (SMTP == true) {
        $mail->isSMTP();
        $mail->Host = smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = smtp_user;
        $mail->Password = smtp_pass;
        $mail->SMTPSecure = smtp_secure == 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = smtp_port;
    }
}

/**
 * General purpose email function
 * Handles: Two-factor authentication, Account activation, Password reset
 * 
 * @param string $email Recipient email address
 * @param string $code Verification/activation code
 * @param string $username Recipient username
 * @param string $type Email type: 'twofactor', 'activation', 'resetpass', 'custom'
 * @return bool Success status
 */
function send_email($email, $code, $username, $type) {
    debug_log('Email System', 'email-system.php', 'Send Email', "=== SEND_EMAIL CALLED ===");
    debug_log('Email System', 'email-system.php', 'Send Email', "Send Mail Parms: email: $email, code: $code, username: $username, type: $type");
    
    $body_template = "";
    $link = "";
    
    // Generic Email Subject
    $subject = 'Action Required';
    
    // Two Factor Authorization
    if ($type == 'twofactor') {
        $subject = 'Your Access Code';
        $body_template = public_path . 'email_template_twofactor.php';
        $link = $code;
    }
    // Account Activation
    elseif ($type == 'activation') {
        $subject = 'Account Activation Required';
        $body_template = public_path . 'activation-email-template.php';
        $link = activation_link . '?email=' . $email . '&code=' . $code;
    }
    // Account Reset Password
    elseif ($type == 'resetpass') {
        $subject = 'Password Reset';
        $body_template = public_path . 'resetpass-email-template.php';
        $link = reset_password_url . '?email=' . $_POST['email'] . '&code=' . $code;
    }
    // Custom
    elseif ($type == 'custom') {
        $subject = 'Welcome to GlitchWizard Solutions!';
    }
    
    // Include the email template as a string
    ob_start();
    include $body_template;
    $email_template = ob_get_clean();
    
    // Create PHPMailer instance
    $mail = new PHPMailer(true);
    
    try {
        // Configure SMTP
        configure_smtp_mail($mail);
        
        // Recipients
        $mail->setFrom(mail_from, no_reply_mail_name);
        $mail->addAddress($email);
        // Only add reply-to if configured (not empty)
        if (!empty(reply_to_email)) {
            $mail->addReplyTo(reply_to_email, reply_to_name);
        }
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $email_template;
        $mail->AltBody = strip_tags($email_template);
        
        // Send mail
        $mail->send();
        debug_log('Email System', 'email-system.php', 'Send Email', "Email sent successfully to $email");
        return true;
    } catch (Exception $e) {
        debug_log('Email System', 'email-system.php', 'Send Email', "EMAIL ERROR: " . $mail->ErrorInfo);
        echo 'error';
        return false;
    }
}

/**
 * Send ticket system emails
 * Handles: Ticket creation, updates, comments, notifications
 * 
 * @param string $email Recipient email
 * @param int $id Ticket ID
 * @param string $title Ticket title
 * @param string $msg Ticket message
 * @param string $priority Ticket priority
 * @param string $category Ticket category
 * @param int $private Private flag
 * @param string $status Ticket status
 * @param string $type Email type: 'create', 'update', 'comment', 'notification'
 * @param string $name Optional: User name (for notifications)
 * @param string $user_email Optional: User email (for notifications)
 * @return bool Success status
 */
function send_ticket_email($email, $id, $title, $msg, $priority, $category, $private, $status, $type, $name = '', $user_email = '', $acc_id = null) {
    if (!mail_enabled) return false;
    
    // Log to dedicated debug file
    $debug_log = public_path . 'debug-email.log';
    file_put_contents($debug_log, date('Y-m-d H:i:s') . " === SEND_TICKET_EMAIL CALLED ===\n", FILE_APPEND);
    file_put_contents($debug_log, "Email: $email, ID: $id, Type: $type, acc_id: " . ($acc_id ?? 'null') . "\n", FILE_APPEND);
    
    // Determine subject based on type
    $subject = 'Your ticket is #' . $id;
    
    if ($type == 'create') {
        $subject = 'Your ticket has been created #' . $id;
    } elseif ($type == 'update') {
        $subject = 'Your ticket has been updated #' . $id;
    } elseif ($type == 'comment') {
        $subject = 'Someone has replied to your ticket #' . $id;
    } elseif ($type == 'notification') {
        $subject = 'A user has submitted a ticket #' . $id;
    } elseif ($type == 'notification-comment') {
        $subject = 'A user has replied on ticket #' . $id;
    }
    
    // Create secure ticket access token
    // Use ticket ID + secret salt for security
    $ticket_token = hash_hmac('sha256', $id, defined('TICKET_SECRET') ? TICKET_SECRET : 'gwds_ticket_secret_2025');
    
    // Determine link based on account type
    // If acc_id is null, try to look it up from the database
    if ($acc_id === null) {
        try {
            global $pdo;
            $stmt = $pdo->prepare('SELECT acc_id FROM tickets WHERE id = ?');
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $acc_id = $result ? (int)$result['acc_id'] : 0;
        } catch (Exception $e) {
            // Default to guest (0) if lookup fails
            $acc_id = 0;
        }
    }
    
    // Guest tickets (acc_id = 0) use public view, registered users use dashboard
    if ((int)$acc_id === 0) {
        // Public guest ticket view - no login required
        $link = site_menu_base . 'view-ticket.php?t=' . $id . '&token=' . $ticket_token;
        file_put_contents($debug_log, date('Y-m-d H:i:s') . " Guest ticket link: $link\n", FILE_APPEND);
    } else {
        // Dashboard ticket view with auto-login for registered users
        $link = site_menu_base . 'client-dashboard/communication/ticket-view.php?t=' . $id . '&token=' . $ticket_token;
        file_put_contents($debug_log, date('Y-m-d H:i:s') . " Registered user ticket link: $link\n", FILE_APPEND);
    }
    
    // Include the ticket email template
    ob_start();
    include tickets_directory_url . 'ticket-email-template.php';
    $ticket_email_template = ob_get_clean();
    
    // Create PHPMailer instance
    $mail = new PHPMailer(true);
    
    try {
        // Configure SMTP
        configure_smtp_mail($mail);
        
        // Recipients
        $mail->setFrom(mail_from, no_reply_mail_name);
        $mail->addAddress($email);
        // Only add reply-to if configured (not empty)
        if (!empty(reply_to_email)) {
            $mail->addReplyTo(reply_to_email, reply_to_name);
        }
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $ticket_email_template;
        $mail->AltBody = strip_tags($ticket_email_template);
        
        // Send mail
        $mail->send();
        $success_msg = "Ticket email sent successfully to $email";
        file_put_contents($debug_log, date('Y-m-d H:i:s') . " " . $success_msg . "\n", FILE_APPEND);
        return true;
    } catch (Exception $e) {
        $error_msg = "TICKET EMAIL ERROR: " . $mail->ErrorInfo . " | EXCEPTION: " . $e->getMessage();
        file_put_contents($debug_log, date('Y-m-d H:i:s') . " " . $error_msg . "\n", FILE_APPEND);
        error_log($error_msg);
        echo 'Error: Message could not be sent. Mailer Error: ' . $mail->ErrorInfo;
        return false;
    }
}

/**
 * Send client invoice emails
 * 
 * @param array $invoice Invoice data
 * @param array $client Client data
 * @param string $subject Optional custom subject
 * @return bool Success status
 */
function send_client_invoice_email($invoice, $client, $subject = '') {
    if (!mail_enabled) return false;
    
    // Create PHPMailer instance
    $mail = new PHPMailer(true);
    
    try {
        // Configure SMTP
        configure_smtp_mail($mail);
        
        // Recipients
        $mail->setFrom(mail_from, mail_name);
        $mail->addAddress($client['email'], rtrim($client['first_name'] . ' ' . $client['last_name'], ' '));
        // Only add reply-to if configured (not empty)
        if (!empty(reply_to_email)) {
            $mail->addReplyTo(reply_to_email, reply_to_name);
        }
        
        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = empty($subject) ? 'Invoice #' . $invoice['invoice_number'] . ' from ' . company_name : $subject;
        
        // Determine message type and amount based on payment status
        $invoice_total = $invoice['payment_amount'] + $invoice['tax_total'];
        $balance_due = isset($invoice['balance_due']) ? $invoice['balance_due'] : ($invoice_total - $invoice['paid_total']);
        
        if ($invoice['payment_status'] == 'Balance' && $balance_due > 0) {
            $message_type = 'a balance due of';
            $display_amount = number_format($balance_due, 2);
        } else {
            $message_type = 'an invoice of';
            $display_amount = number_format($invoice_total, 2);
        }
        
        // Read template and replace placeholders
        $email_template = str_replace(
            ['%invoice_number%', '%first_name%', '%amount%', '%due_date%', '%link%', '%message_type%'],
            [
                $invoice['invoice_number'], 
                $client['first_name'], 
                $display_amount, 
                date('m/d/y', strtotime($invoice['due_date'])), 
                BASE_URL . 'client-invoices/invoice.php?id=' . $invoice['invoice_number'],
                $message_type
            ],
            file_get_contents(public_path . 'client-invoices/templates/client-email-template.html')
        );
        
        // Check if PDF attachment is enabled
        if (defined('pdf_attachments') && pdf_attachments && file_exists(public_path . '/client-invoices/pdfs/' . $invoice['invoice_number'] . '.pdf') && !$subject) {
            $mail->AddAttachment(public_path . '/client-invoices/pdfs/' . $invoice['invoice_number'] . '.pdf', $invoice['invoice_number'] . '.pdf');
        }
        
        $mail->Body = $email_template;
        $mail->AltBody = strip_tags($email_template);
        
        // Send mail
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("INVOICE EMAIL ERROR: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Send client receipt email for paid invoices
 * 
 * @param array $invoice Invoice data
 * @param array $client Client data
 * @param string $subject Optional custom subject
 * @return bool Success status
 */
function send_client_receipt_email($invoice, $client, $subject = '') {
    if (!mail_enabled) return false;
    
    // Create PHPMailer instance
    $mail = new PHPMailer(true);
    
    try {
        // Configure SMTP
        configure_smtp_mail($mail);
        
        // Recipients
        $mail->setFrom(mail_from, mail_name);
        $mail->addAddress($client['email'], rtrim($client['first_name'] . ' ' . $client['last_name'], ' '));
        // Only add reply-to if configured (not empty)
        if (!empty(reply_to_email)) {
            $mail->addReplyTo(reply_to_email, reply_to_name);
        }
        
        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = empty($subject) ? 'Receipt for Invoice #' . $invoice['invoice_number'] . ' - ' . company_name : $subject;
        
        // Read receipt template and replace placeholders
        $email_template = str_replace(
            ['%invoice_number%', '%first_name%', '%amount%', '%due_date%', '%link%'],
            [
                $invoice['invoice_number'], 
                $client['first_name'], 
                number_format($invoice['payment_amount'] + $invoice['tax_total'], 2), 
                date('m/d/y', strtotime($invoice['due_date'])), 
                BASE_URL . 'client-invoices/invoice.php?id=' . $invoice['invoice_number']
            ],
            file_get_contents(public_path . 'client-invoices/templates/client-receipt-template.html')
        );
        
        // Check if PDF attachment is enabled
        if (defined('pdf_attachments') && pdf_attachments && file_exists(public_path . '/client-invoices/pdfs/' . $invoice['invoice_number'] . '.pdf')) {
            $mail->AddAttachment(public_path . '/client-invoices/pdfs/' . $invoice['invoice_number'] . '.pdf', $invoice['invoice_number'] . '.pdf');
        }
        
        $mail->Body = $email_template;
        $mail->AltBody = strip_tags($email_template);
        
        // Send mail
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("RECEIPT EMAIL ERROR: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Send admin invoice notification emails
 * 
 * @param array $invoice Invoice data
 * @param array $client Client data
 * @return bool Success status
 */
function send_admin_invoice_email($invoice, $client) {
    if (!defined('notifications_enabled') || !notifications_enabled || !mail_enabled) return false;
    
    // Create PHPMailer instance
    $mail = new PHPMailer(true);
    
    try {
        // Configure SMTP
        configure_smtp_mail($mail);
        
        // Recipients
        $mail->setFrom(mail_from, mail_name);
        $mail->addAddress(notification_email);
        // Only add reply-to if configured (not empty)
        if (!empty(reply_to_email)) {
            $mail->addReplyTo(reply_to_email, reply_to_name);
        }
        
        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        
        // Set subject based on payment status
        if ($invoice['payment_status'] == 'Paid') {
            $mail->Subject = 'Invoice #' . $invoice['invoice_number'] . ' has been paid.';
        } elseif ($invoice['payment_status'] == 'Cancelled') {
            $mail->Subject = 'Invoice #' . $invoice['invoice_number'] . ' has been cancelled.';
        } elseif ($invoice['payment_status'] == 'Pending') {
            $mail->Subject = 'Invoice #' . $invoice['invoice_number'] . ' is pending payment.';
        } else {
            $mail->Subject = 'Invoice #' . $invoice['invoice_number'] . ' has been updated.';
        }
        
        // Read template and replace placeholders
        $email_template = str_replace(
            ['%invoice_number%', '%client%', '%amount%', '%status%', '%date%'],
            [
                $invoice['invoice_number'], 
                $client['first_name'] . ' ' . $client['last_name'], 
                number_format($invoice['payment_amount'] + $invoice['tax_total'], 2), 
                $invoice['payment_status'], 
                date('Y-m-d H:i:s')
            ],
            file_get_contents(client_side_invoice . 'templates/notification-email-template.html')
        );
        
        $mail->Body = $email_template;
        $mail->AltBody = strip_tags($email_template);
        
        // Send mail
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("ADMIN EMAIL ERROR: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Send email with file attachment
 * 
 * @param string $to_email Recipient email address
 * @param string $to_name Recipient name
 * @param string $subject Email subject
 * @param string $message Email body (plain text)
 * @param string $attachment_path Full path to attachment file
 * @param string $attachment_name Display name for attachment
 * @return bool True on success, false on failure
 */
function send_email_with_attachment($to_email, $to_name, $subject, $message, $attachment_path, $attachment_name) {
    // Create PHPMailer instance
    $mail = new PHPMailer(true);
    
    try {
        // Configure SMTP
        configure_smtp_mail($mail);
        
        // Recipients
        $mail->setFrom(mail_from, mail_name);
        $mail->addAddress($to_email, $to_name);
        // Only add reply-to if configured (not empty)
        if (!empty(reply_to_email)) {
            $mail->addReplyTo(reply_to_email, reply_to_name);
        }
        
        // Content
        $mail->isHTML(false);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body = $message;
        
        // Attach file if provided
        if (!empty($attachment_path) && file_exists($attachment_path)) {
            $mail->addAttachment($attachment_path, $attachment_name);
        }
        
        // Send mail
        $mail->send();
        
        // Debug log
        $debug_msg = date('Y-m-d H:i:s') . " - Email with attachment sent to: {$to_email} | Subject: {$subject} | File: {$attachment_name}\n";
        file_put_contents(__DIR__ . '/email-debug.log', $debug_msg, FILE_APPEND);
        
        return true;
    } catch (Exception $e) {
        error_log("EMAIL WITH ATTACHMENT ERROR: " . $mail->ErrorInfo);
        $debug_msg = date('Y-m-d H:i:s') . " - EMAIL FAILED to {$to_email}: " . $mail->ErrorInfo . "\n";
        file_put_contents(__DIR__ . '/email-debug.log', $debug_msg, FILE_APPEND);
        return false;
    }
}
?>
