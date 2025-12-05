<?php
/**
 * SMTP Connection Debug - Find out WHY copperhead is being contacted
 * DELETE THIS FILE AFTER USE!
 */

header('Content-Type: text/plain');

echo "=== SMTP CONNECTION DEBUGGING ===\n\n";

// Load config
require_once __DIR__ . '/../../private/config.php';

echo "Environment: " . ENVIRONMENT . "\n";
echo "smtp_host constant: " . smtp_host . "\n";
echo "smtp_port constant: " . smtp_port . "\n";
echo "smtp_user constant: " . smtp_user . "\n\n";

// Test what smtp_host resolves to
echo "=== DNS RESOLUTION TEST ===\n";
$ip = gethostbyname(smtp_host);
echo "gethostbyname('" . smtp_host . "') = $ip\n\n";

// Test socket connection to see what server responds
echo "=== SOCKET CONNECTION TEST ===\n";
$fp = @fsockopen(smtp_host, smtp_port, $errno, $errstr, 10);
if ($fp) {
    echo "✓ Connected to " . smtp_host . ":" . smtp_port . "\n";
    $banner = fgets($fp, 1024);
    echo "Server banner: " . trim($banner) . "\n";
    
    // Send EHLO
    fwrite($fp, "EHLO test.local\r\n");
    $response = '';
    while ($line = fgets($fp, 1024)) {
        $response .= $line;
        if (substr($line, 3, 1) === ' ') break;
    }
    echo "EHLO response:\n$response\n";
    
    fclose($fp);
} else {
    echo "✗ Failed to connect: [$errno] $errstr\n";
}

// Test with PHPMailer
echo "\n=== PHPMAILER TEST ===\n";
require_once __DIR__ . '/../lib/phpmailer/Exception.php';
require_once __DIR__ . '/../lib/phpmailer/PHPMailer.php';
require_once __DIR__ . '/../lib/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = smtp_host;
$mail->SMTPAuth = true;
$mail->Username = smtp_user;
$mail->Password = smtp_pass;
$mail->SMTPSecure = smtp_secure == 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
$mail->Port = smtp_port;
$mail->SMTPDebug = 3; // Verbose debug
$mail->Debugoutput = function($str, $level) {
    echo $str . "\n";
};

$mail->setFrom(smtp_user, 'Test');
$mail->addAddress(smtp_user);
$mail->Subject = 'SMTP Test';
$mail->Body = 'Testing SMTP connection';

try {
    echo "Attempting to send via PHPMailer...\n\n";
    $mail->send();
    echo "\n✓ Email sent successfully!\n";
} catch (Exception $e) {
    echo "\n✗ Email failed: " . $mail->ErrorInfo . "\n";
}

echo "\n⚠️  DELETE THIS FILE AFTER TESTING! ⚠️\n";
?>
