<?php
require_once __DIR__ . '/vendor/autoload.php'; // Adjust path as needed

use Mpdf\Mpdf;
use Mpdf\Output\Destination;

function createStyledBusinessPDF(
    $logoPath,
    $line1, $line2, $line3, $line4, $line5, $line6, $line7,
    $filename = 'client-summary.pdf',
    $userPassword = 'client123',
    $ownerPassword = 'owner456'
) {
    $mpdf = new Mpdf([
        'format' => 'A4',
        'default_font' => 'Arial'
    ]);

    // Set PDF protection (user must enter password to open)
    $mpdf->SetProtection(['print'], $userPassword, $ownerPassword);

    // Custom stylesheet with accessible contrast and branding
    $stylesheet = "
        body { font-family: Arial; font-size: 12pt; color: #222; }
        .header { color: #4B0082; font-size: 20pt; font-weight: bold; margin-bottom: 10px; }
        .section-title { color: #4B0082; font-size: 14pt; margin-top: 15px; font-weight: bold; }
        .content-box { background-color: #f4f4f8; padding: 10px; border-radius: 6px; margin-bottom: 10px; }
        .footer { font-size: 9pt; color: #555; text-align: center; position: fixed; bottom: 0; }
    ";
    $mpdf->WriteHTML($stylesheet, \Mpdf\HTMLParserMode::HEADER_CSS);

    // HTML Content for PDF
    $html = "
    <div class='header'>Client Summary Report</div>
    <div class='content-box'><strong>1.</strong> {$line1}</div>
    <div class='content-box'><strong>2.</strong> {$line2}</div>
    <div class='content-box'><strong>3.</strong> {$line3}</div>
    <div class='content-box'><strong>4.</strong> {$line4}</div>
    <div class='content-box'><strong>5.</strong> {$line5}</div>
    <div class='content-box'><strong>6.</strong> {$line6}</div>
    <div class='content-box'><strong>7.</strong> {$line7}</div>
    ";

    if (file_exists($logoPath)) {
        $mpdf->Image($logoPath, 150, 10, 40, 0, 'png', '', true, false);
    }

    $mpdf->WriteHTML($html);

    // Footer with page number
    $mpdf->SetHTMLFooter("<div class='footer'>Page {PAGENO}</div>");

    // Output to browser (force download)
    $mpdf->Output($filename, Destination::DOWNLOAD);
}

// Example usage (uncomment to test)
/*
createStyledBusinessPDF(
    'assets/img/my-logo.png',
    'Client: Barbara Moore',
    'Service: Custom Web Design + Hosting',
    'Email: barbara@glitchwizarddigitalsolutions.com',
    'Package: Accessibility First Tier',
    'Start Date: 2025-07-01',
    'Status: Active and In Development',
    'Notes: Client site includes accessibility updates and Termageddon policy sync.',
    'glitchwizard-client-summary.pdf',
    'client-access',
    'internal-only'
);
*/

/*
--- FUN/USEFUL OPTIONS TO TRY ---
- Add QR codes: $mpdf->WriteBarcode('https://example.com', 'C128');
- Add table of contents with $mpdf->TOC();
- Highlight text with: <span style='background-color: #ffff99'>highlight</span>
- Use <ul><li>...</li></ul> for bullet points
- Insert watermark: $mpdf->SetWatermarkText('CONFIDENTIAL'); $mpdf->showWatermarkText = true;
- Use CSS media queries (mPDF supports print rules!)
*/
