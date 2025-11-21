<?php
require(__DIR__ . '/../../../private/config.php');
require(__DIR__ . '/../fpdf/fpdf.php');

// Connect to database
try {
	$onthego_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name2 . ';charset=' . db_charset, db_user, db_pass);
	$onthego_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	exit('Failed to connect to the on the go database!');
}

class PDF extends FPDF {
	function Header() {
		$this->SetFont('Arial','B',16);
		$this->Cell(0,10,'Medication List - Dio Moore',0,1,'C');
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
$meds_result = $onthego_db->query('SELECT * FROM meds WHERE patient = "Dio Moore" and status = "Active" ORDER BY name ASC');
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

// Output PDF
$pdf->Output('D', 'Medication_List_Dio_Moore_' . date('Y-m-d') . '.pdf');
?>
