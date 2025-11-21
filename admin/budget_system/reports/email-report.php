<?php
session_start();
if(!isset($_SESSION['id'])){
    header('Location: ../../../index.php');
    exit();
}

require_once(__DIR__ . '/../../../../private/config.php');
require_once(__DIR__ . '/../../../lib/email-system.php');
require_once(__DIR__ . '/../../fpdf/fpdf.php');
require_once(__DIR__ . '/lib/generate-mom-hancock-pdf.php');

// Connect to MySQL database
try {
    $budget_pdo = new PDO('mysql:host=' . db_host . ';dbname=' . db_name7 . ';charset=utf8', db_user, db_pass);
    $budget_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
    exit('Failed to connect to database: ' . $exception->getMessage());
}
$conn = $budget_pdo;

// Get date parameters
$date_start = isset($_POST['date_start']) ? $_POST['date_start'] : date("Y-m-1");
$date_end = isset($_POST['date_end']) ? $_POST['date_end'] : date("Y-m-t");
$recipient_email = isset($_POST['recipient_email']) ? $_POST['recipient_email'] : '';
$recipient_name = isset($_POST['recipient_name']) ? $_POST['recipient_name'] : 'Mom';

if(empty($recipient_email)){
    $_SESSION['error'] = 'Please provide a recipient email address.';
    header('Location: mom-hancock-report.php?date_start=' . $date_start . '&date_end=' . $date_end);
    exit();
}

// Generate PDF as string for email attachment
$pdf_content = generate_mom_hancock_pdf($conn, $date_start, $date_end, 'string');

// Save PDF to temporary file
$temp_file = sys_get_temp_dir() . '/mom_hancock_report_' . time() . '.pdf';
file_put_contents($temp_file, $pdf_content);

// Send email with PDF attachment
$subject = 'Monthly Budget Report - ' . date('F Y', strtotime($date_start));
$message = "Hello {$recipient_name},\n\n";
$message .= "Please find attached your monthly budget report for " . date('F Y', strtotime($date_start)) . ".\n\n";
$message .= "Report Date Range: " . date('M d, Y', strtotime($date_start)) . " to " . date('M d, Y', strtotime($date_end)) . "\n\n";
$message .= "Best regards,\n";
$message .= "Glitch Wizard Digital Solutions";

$filename = 'Mom_Hancock_Report_' . date('Y-m', strtotime($date_start)) . '.pdf';

// Use the email system function
$result = send_email_with_attachment(
    $recipient_email,
    $recipient_name,
    $subject,
    $message,
    $temp_file,
    $filename
);

// Clean up temp file
unlink($temp_file);

// Redirect with success or error message
if($result){
    $_SESSION['success'] = 'Report has been emailed to ' . $recipient_email;
} else {
    $_SESSION['error'] = 'Failed to send email. Please check the email system configuration.';
}

header('Location: mom-hancock-report.php?date_start=' . $date_start . '&date_end=' . $date_end);
exit();
