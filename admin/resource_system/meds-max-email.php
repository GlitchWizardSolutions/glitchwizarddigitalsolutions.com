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
$recipient_name = isset($_POST['recipient_name']) ? $_POST['recipient_name'] : 'Max Moore';

if (empty($recipient_email)) {
    $_SESSION['error_message'] = 'Email address is required';
    header('Location: meds-max.php');
    exit();
}

class PDF extends FPDF {
	function Header() {
		$this->SetFont('Arial','B',16);
		$this->Cell(0,10,'Medication List - Max Moore',0,1,'C');
		$this->SetFont('Arial','',10);
		$this->Cell(0,5,'Generated: ' . date('F d, Y'),0,1,'C');
		$this->Ln(5);
	}
	
	function Footer() {
		$this->SetY(-15);
		$this->SetFont('Arial','I',8);
		$this->Cell(0,10,'Page '.$this->PageNo(),0,0,'C');
	}
	
	function MedicationTable($header, $data) {
		// Column widths
		$w = array(55, 25, 30, 35, 45);
		// Header
		$this->SetFillColor(51,122,183);
		$this->SetTextColor(255);
		$this->SetDrawColor(51,122,183);
		$this->SetLineWidth(.3);
		$this->SetFont('','B');
		for($i=0;$i<count($header);$i++)
			$this->Cell($w[$i],7,$header[$i],1,0,'C',true);
		$this->Ln();
		// Color and font restoration
		$this->SetFillColor(224,235,255);
		$this->SetTextColor(0);
		$this->SetFont('');
		// Data
		$fill = false;
		foreach($data as $row) {
			$this->Cell($w[0],6,$row['name'],'LR',0,'L',$fill);
			$this->Cell($w[1],6,$row['type'],'LR',0,'L',$fill);
			$this->Cell($w[2],6,$row['dosage'],'LR',0,'L',$fill);
			$this->Cell($w[3],6,$row['frequency'],'LR',0,'L',$fill);
			$this->Cell($w[4],6,substr($row['notes'], 0, 30),'LR',0,'L',$fill);
			$this->Ln();
			$fill = !$fill;
		}
		// Closing line
		$this->Cell(array_sum($w),0,'','T');
	}
}

// Fetch medications
$meds_result = $onthego_db->query('SELECT * FROM meds WHERE patient = "Max Moore" and status = "Active" ORDER BY name ASC');
$meds = $meds_result->fetchAll(PDO::FETCH_ASSOC);

// Create PDF
$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial','',10);

// Table header
$header = array('Medication', 'Type', 'Dosage', 'Frequency', 'Notes');

// Generate table
if (count($meds) > 0) {
	$pdf->MedicationTable($header, $meds);
} else {
	$pdf->Cell(0,10,'No active medications found.',0,1);
}

// Save PDF to temporary file
$temp_file = sys_get_temp_dir() . '/medication_list_max_' . time() . '.pdf';
$pdf->Output('F', $temp_file);

// Send email with attachment
$subject = 'Medication List - Max Moore';
$message = "Hello " . htmlspecialchars($recipient_name) . ",\n\n";
$message .= "Please find attached the medication list for Max Moore as of " . date('F d, Y') . ".\n\n";
$message .= "Best regards,\n";
$message .= "Glitch Wizard Digital Solutions";

$result = send_email_with_attachment(
    $recipient_email,
    $recipient_name,
    $subject,
    $message,
    $temp_file,
    'Medication_List_Max_Moore_' . date('Y-m-d') . '.pdf'
);

// Clean up temporary file
if (file_exists($temp_file)) {
    unlink($temp_file);
}

// Redirect with message
if ($result) {
    $_SESSION['success_message'] = 'Email sent successfully to ' . htmlspecialchars($recipient_email);
} else {
    $_SESSION['error_message'] = 'Failed to send email. Please try again.';
}

header('Location: meds-max.php');
exit();
?>
