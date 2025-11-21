<?php
include_once 'assets/includes/process-config.php';
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
echo 'Fix your code, in custommail.php';
// Namespaces for the PHPMailer library
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
// Connect to MySQL database function

function pdo_connect_mysql() {
    try {
    	$pdo = new PDO('mysql:host=' . db_host . ';dbname=' . db_name . ';charset=utf8', db_user, db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $exception) {
    	// If there is an error with the connection, stop the script and display the error.
    	exit('Failed to connect to database!');
    }
    return $pdo;
}

// Send ticket email function
function send_ticket_email($email, $id, $title, $msg, $priority, $category, $private, $status, $type = 'create', $name = '', $user_email = '') {
    if (!mail_enabled) return;
    // Welcome
	$subject = 'Welcome to your Portal, ' . $username . '!';
    // Action required
    $subject = $type == 'action' ? 'Action Required, ' . $username . '!' : $subject;
    // Notification of Change
    $subject = $type == 'notify' ? 'Change Notification ' . $username : $subject;
    // 
    $subject = $type == 'remind' ? 'Reminder for ' . $username : $subject;
    // Include the email template as a string
    ob_start();
    include_once 'custom-email-template.php';
    $custom_email_template = ob_get_clean();
    // Include PHPMailer library
    require_once 'lib/phpmailer/Exception.php';
    require_once 'lib/phpmailer/PHPMailer.php';
    require_once 'lib/phpmailer/SMTP.php';
    // Create an instance; passing `true` enables exceptions
    $mail = new PHPMailer(true);
    try {
        // SMTP Server settings
        if (SMTP) {
            $mail->isSMTP();
            $mail->Host = smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = smtp_user;
            $mail->Password = smtp_pass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = smtp_port;
        }
        // Recipients
        $mail->setFrom(mail_from, mail_name);
        $mail->addAddress($email);
        $mail->addReplyTo(mail_from, mail_name);
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        // Body
        $mail->Body = $custom_email_template;
        $mail->AltBody = strip_tags($custom_email_template);
        // Send mail
        $mail->send();
    } catch (Exception $e) {
        // Output error message
        exit('Error: Message could not be sent. Mailer Error: ' . $mail->ErrorInfo);
    }
}
