<?php
require_once __DIR__ . '/vendor/autoload.php'; // Adjust path as needed

use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function createStyledBusinessPDFToDownload(
    $logoPath, $line1, $line2, $line3, $line4, $line5, $line6, $line7,
    $filename = 'client-summary.pdf',
    $userPassword = 'client123',
    $ownerPassword = 'owner456'
) {
    $mpdf = new Mpdf(['format' => 'A4', 'default_font' => 'Arial']);
    $mpdf->SetProtection(['print'], $userPassword, $ownerPassword);
    $mpdf->WriteHTML(getBusinessPDFStyles(), \Mpdf\HTMLParserMode::HEADER_CSS);
    $mpdf->WriteHTML(getBusinessPDFContent($logoPath, [$line1, $line2, $line3, $line4, $line5, $line6, $line7]));
    $mpdf->SetHTMLFooter("<div class='footer'>Page {PAGENO}</div>");
    $mpdf->Output($filename, Destination::DOWNLOAD);
}

function createStyledBusinessPDFToBrowser(
    $logoPath, $line1, $line2, $line3, $line4, $line5, $line6, $line7
) {
    $mpdf = new Mpdf(['format' => 'A4', 'default_font' => 'Arial']);
    $mpdf->WriteHTML(getBusinessPDFStyles(), \Mpdf\HTMLParserMode::HEADER_CSS);
    $mpdf->WriteHTML(getBusinessPDFContent($logoPath, [$line1, $line2, $line3, $line4, $line5, $line6, $line7]));
    $mpdf->SetHTMLFooter("<div class='footer'>Page {PAGENO}</div>");
    $mpdf->Output('inline.pdf', Destination::INLINE);
}

function exportBusinessDocx(
    $line1, $line2, $line3, $line4, $line5, $line6, $line7,
    $outputPath = 'client-summary.docx'
) {
    $content = "Client Summary Report\n\n" . implode("\n", [$line1, $line2, $line3, $line4, $line5, $line6, $line7]);
    file_put_contents($outputPath, $content);
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header("Content-Disposition: attachment; filename=\"$outputPath\"");
    readfile($outputPath);
    exit;
}

function emailBusinessPDF(
    $logoPath, $line1, $line2, $line3, $line4, $line5, $line6, $line7,
    $emailTo, $subject = 'Your PDF Report', $filename = 'client-summary.pdf'
) {
    $mpdf = new Mpdf(['format' => 'A4', 'default_font' => 'Arial']);
    $mpdf->WriteHTML(getBusinessPDFStyles(), \Mpdf\HTMLParserMode::HEADER_CSS);
    $mpdf->WriteHTML(getBusinessPDFContent($logoPath, [$line1, $line2, $line3, $line4, $line5, $line6, $line7]));
    $mpdf->SetHTMLFooter("<div class='footer'>Page {PAGENO}</div>");
    $pdfData = $mpdf->Output('', Destination::STRING_RETURN);

    $mail = new PHPMailer(true);
    try {
        $mail->setFrom('no-reply@yourdomain.com', 'Your Business');
        $mail->addAddress($emailTo);
        $mail->Subject = $subject;
        $mail->Body = 'Please find your PDF report attached.';
        $mail->addStringAttachment($pdfData, $filename);
        $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
    }
}

function getBusinessPDFStyles() {
    return "
        body { font-family: Arial; font-size: 12pt; color: #222; }
        .header { color: #4B0082; font-size: 20pt; font-weight: bold; margin-bottom: 10px; }
        .section-title { color: #4B0082; font-size: 14pt; margin-top: 15px; font-weight: bold; }
        .content-box { background-color: #f4f4f8; padding: 10px; border-radius: 6px; margin-bottom: 10px; }
        .footer { font-size: 9pt; color: #555; text-align: center; position: fixed; bottom: 0; }
    ";
}

function getBusinessPDFContent($logoPath, $lines) {
    $html = "<div class='header'>Client Summary Report</div>";
    foreach ($lines as $i => $line) {
        if ($line) {
            $html .= "<div class='content-box'><strong>" . ($i+1) . ".</strong> $line</div>";
        }
    }
    if (file_exists($logoPath)) {
        $html .= "<div style='position:absolute; top:10px; right:10px;'><img src='$logoPath' width='100'></div>";
    }
    return $html;
}

/*
--- FUN/USEFUL OPTIONS TO TRY ---
- Add QR codes: $mpdf->WriteBarcode('https://example.com', 'C128');
- Add table of contents with $mpdf->TOC();
- Highlight text with: <span style='background-color: #ffff99'>highlight</span>
- Use <ul><li>...</li></ul> for bullet points
- Insert watermark: $mpdf->SetWatermarkText('CONFIDENTIAL'); $mpdf->showWatermarkText = true;
- Use CSS media queries (mPDF supports print rules!)
*/
