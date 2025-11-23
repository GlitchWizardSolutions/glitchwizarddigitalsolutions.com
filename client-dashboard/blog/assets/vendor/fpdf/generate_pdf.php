<?php
/*
This uses fpdf.  There is another version for mpdf called generate-pdf-handler.php
To use this:
include 'generate_pdf.php';

generateCustomPDFToFile(
    'assets/img/my-logo.png',  // logo path
    'John Doe',
    'Web Developer',
    'Email: john@example.com',
    'Project: Blog Platform',
    'Status: Completed',
    'Launch Date: 2025-07-01',
    'Notes: Fully accessible and SEO-optimized.',
    'client-summary.pdf'       // output path
);


*/



function generateCustomPDFToFile($logoPath, $line1, $line2, $line3, $line4, $line5, $line6, $line7, $outputPath = 'output.pdf') {
    require_once('assets/vendor/fpdf/fpdf.php'); // Ensure path is correct
    $pdf = new FPDF();
    $pdf->AddPage();

    // Set logo
    if (file_exists($logoPath)) {
        $pdf->Image($logoPath, 10, 10, 30); // x, y, width
    }

    // Move below the logo
    $pdf->SetY(45);

    // Set font
    $pdf->SetFont('Arial', '', 12);

    // Output lines
    $lines = [$line1, $line2, $line3, $line4, $line5, $line6, $line7];
    foreach ($lines as $line) {
        if (!empty($line)) {
            $pdf->MultiCell(0, 10, $line);
        }
    }

    // Save to file
    $pdf->Output('F', $outputPath); // 'F' = save to file
}//end save to file.

function generateCustomPDFToBrowser($logoPath, $line1, $line2, $line3, $line4, $line5, $line6, $line7) {
    require_once('assets/vendor/fpdf/fpdf.php'); // Ensure path is correct
    $pdf = new FPDF();
    $pdf->AddPage();

    if (file_exists($logoPath)) {
        $pdf->Image($logoPath, 10, 10, 30);
    }

    $pdf->SetY(45);
    $pdf->SetFont('Arial', '', 12);
    $lines = [$line1, $line2, $line3, $line4, $line5, $line6, $line7];
    foreach ($lines as $line) {
        if (!empty($line)) {
            $pdf->MultiCell(0, 10, $line);
        }
    }

    $pdf->Output('I', 'inline-view.pdf'); // 'I' for inline in browser
}
function generateCustomPDFDownload($logoPath, $line1, $line2, $line3, $line4, $line5, $line6, $line7, $filename = 'download.pdf') {
    require_once('assets/vendor/fpdf/fpdf.php'); // Ensure path is correct
    $pdf = new FPDF();
    $pdf->AddPage();

    if (file_exists($logoPath)) {
        $pdf->Image($logoPath, 10, 10, 30);
    }

    $pdf->SetY(45);
    $pdf->SetFont('Arial', '', 12);
    $lines = [$line1, $line2, $line3, $line4, $line5, $line6, $line7];
    foreach ($lines as $line) {
        if (!empty($line)) {
            $pdf->MultiCell(0, 10, $line);
        }
    }

    $pdf->Output('D', $filename); // 'D' forces download
}
//This version has page number, plus a password protection.

?>