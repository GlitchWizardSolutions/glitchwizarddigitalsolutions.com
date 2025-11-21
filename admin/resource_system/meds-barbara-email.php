<?php
session_start();
if(!isset($_SESSION['id'])){
    header('Location: ../../index.php');
    exit();
}

require_once(__DIR__ . '/../../../private/config.php');
require_once(__DIR__ . '/../../lib/email-system.php');
require_once(__DIR__ . '/../fpdf/fpdf.php');

// Connect to database
try {
	$onthego_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name2 . ';charset=' . db_charset, db_user, db_pass);
	$onthego_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	exit('Failed to connect to the on the go database!');
}

// Get email parameters
$recipient_email = isset($_POST['recipient_email']) ? $_POST['recipient_email'] : '';
$recipient_name = isset($_POST['recipient_name']) ? $_POST['recipient_name'] : 'Barbara Moore';

if(empty($recipient_email)){
    $_SESSION['error'] = 'Please provide a recipient email address.';
    header('Location: meds-barbara.php');
    exit();
}

// Get medications
$meds = $onthego_db->query('SELECT * FROM meds WHERE patient = "Barbara Moore" and status = "Active" ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);

class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'Medication List', 0, 1, 'C');
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 6, 'Barbara Moore', 0, 1, 'C');
        $this->SetFont('Arial', 'I', 9);
        $this->Cell(0, 5, 'Generated: ' . date('F d, Y'), 0, 1, 'C');
        $this->Ln(5);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }
    
    function MedicationTable($header, $data) {
        // Colors and font for header
        $this->SetFillColor(41, 128, 185);
        $this->SetTextColor(255);
        $this->SetFont('Arial', 'B', 10);
        
        // Column widths
        $w = array(55, 25, 30, 35, 45);
        
        // Header
        $headers = array('Medication', 'Type', 'Dosage', 'Frequency', 'Notes');
        for($i=0; $i<count($headers); $i++) {
            $this->Cell($w[$i], 7, $headers[$i], 1, 0, 'C', true);
        }
        $this->Ln();
        
        // Color and font for data
        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 9);
        
        // Data
        $fill = false;
        foreach($data as $row) {
            $this->Cell($w[0], 6, substr($row['name'], 0, 35), 1, 0, 'L', $fill);
            $this->Cell($w[1], 6, substr($row['type'], 0, 15), 1, 0, 'L', $fill);
            $this->Cell($w[2], 6, substr($row['dosage'], 0, 18), 1, 0, 'L', $fill);
            $this->Cell($w[3], 6, substr($row['frequency'], 0, 20), 1, 0, 'L', $fill);
            $this->Cell($w[4], 6, substr($row['notes'], 0, 28), 1, 0, 'L', $fill);
            $this->Ln();
            $fill = !$fill;
        }
    }
}

// Create PDF
$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 10);

if(empty($meds)) {
    $pdf->Cell(0, 10, 'No active medications found.', 0, 1, 'C');
} else {
    $pdf->MedicationTable(array('Medication', 'Type', 'Dosage', 'Frequency', 'Notes'), $meds);
}

// Generate PDF as string
$pdf_content = $pdf->Output('S');

// Save PDF to temporary file
$temp_file = sys_get_temp_dir() . '/barbara_medications_' . time() . '.pdf';
file_put_contents($temp_file, $pdf_content);

// Send email with PDF attachment
$subject = 'Medication List - Barbara Moore - ' . date('F Y');
$message = "Hello {$recipient_name},\n\n";
$message .= "Please find attached the current medication list for Barbara Moore.\n\n";
$message .= "Generated: " . date('F d, Y g:i A') . "\n\n";
$message .= "Best regards,\n";
$message .= "Glitch Wizard Digital Solutions";

$filename = 'Barbara_Moore_Medications_' . date('Y-m-d') . '.pdf';

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
    $_SESSION['success'] = 'Medication list has been emailed to ' . $recipient_email;
} else {
    $_SESSION['error'] = 'Failed to send email. Please check the email system configuration.';
}

header('Location: meds-barbara.php');
exit();
