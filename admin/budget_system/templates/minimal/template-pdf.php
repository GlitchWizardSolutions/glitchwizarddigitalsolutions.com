<?php
defined('INVOICE') or exit;

require base_path . 'lib/fpdf/fpdf.php';

// Colors
$text_color = [93, 103, 121];
$text_color_light = [159, 164, 177];
$content_border_color = [237, 239, 243];
$header_color = [74, 83, 97];

// convert currency code to HTML entity
$currency_code = html_entity_decode(currency_code, ENT_HTML5, 'UTF-8');

$pdf = new FPDF();
$pdf->AddPage();

// Header
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor($text_color_light[0], $text_color_light[1], $text_color_light[2]);
$pdf->Cell(0, 10, 'Invoice from ' . nl2br(company_name), 0, 1, 'L');

$pdf->SetFont('Arial', 'B', 24);
$pdf->SetTextColor($header_color[0], $header_color[1], $header_color[2]);
$pdf->Cell(0, 10, $currency_code . number_format($invoice['payment_amount'] + $invoice['tax_total'], 2), 0, 1, 'L');
$pdf->Ln(2);

$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor($text_color_light[0], $text_color_light[1], $text_color_light[2]);
$pdf->Cell(0, 10, 'Due ' . date('F d, Y h:ia', strtotime($invoice['due_date'])), 0, 1, 'L');
$pdf->Ln(5);

// Company Logo
if (company_logo) {
    $image_width = 80;
    $image_height = 25;
    $page_width = $pdf->GetPageWidth();
    $x = $page_width - $image_width - 5;
    $pdf->Image(company_logo, $x, 10, $image_width, $image_height);
}

// Add horizontal line
$pdf->SetDrawColor($content_border_color[0], $content_border_color[1], $content_border_color[2]);
$pdf->Cell(190, 0, '', 'T', 1, 'L');
$pdf->Ln(10);

// From details
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor($text_color_light[0], $text_color_light[1], $text_color_light[2]);
$pdf->Cell(30, 5, 'From', 0, 0, 'L');
$pdf->SetTextColor($text_color[0], $text_color[1], $text_color[2]);
$pdf->Cell(60, 5, company_name, 0, 0, 'L');
$pdf->Ln(10);

// To details
if ($client['first_name'] || $client['last_name']) {
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor($text_color_light[0], $text_color_light[1], $text_color_light[2]);
    $pdf->Cell(30, 5, 'To', 0, 0, 'L');
    $pdf->SetTextColor($text_color[0], $text_color[1], $text_color[2]);
    $pdf->Cell(60, 5, $client['first_name'] . ' ' . $client['last_name'], 0, 0, 'L');
    $pdf->Ln(10);
}

// Notes
if ($invoice['notes']) {
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor($text_color_light[0], $text_color_light[1], $text_color_light[2]);
    $pdf->Cell(30, 5, 'Notes', 0, 0, 'L');
    $pdf->SetTextColor($text_color[0], $text_color[1], $text_color[2]);
    $pdf->Cell(60, 5, $invoice['notes'], 0, 0, 'L');
    $pdf->Ln(15);
} else {
    $pdf->Ln(5);
}

// Invoice Items Table
// Table header
$pdf->SetDrawColor($content_border_color[0], $content_border_color[1], $content_border_color[2]);
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor($text_color_light[0], $text_color_light[1], $text_color_light[2]);
$pdf->Cell(140, 10, 'Name', 'B', 0, 'L');
$pdf->Cell(50, 10, 'Price', 'B', 1, 'R');
// Table body
$pdf->SetFont('Arial', '', 12);
$pdf->SetTextColor($text_color[0], $text_color[1], $text_color[2]);
foreach ($invoice_items as $item) {
    $pdf->Cell(140, 10, $item['item_name'], 0, 0, 'L');
    $pdf->Cell(50, 10, $item['item_quantity'] . ' x ' . $currency_code . number_format($item['item_price'], 2), 0, 1, 'R');
    // Add item description if available
    if (!empty($item['item_description'])) {
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->SetTextColor($text_color_light[0], $text_color_light[1], $text_color_light[2]);
        $pdf->Cell(190, 10, $item['item_description'], 0, 1, 'L');
        $pdf->SetFont('Arial', '', 12);
        $pdf->SetTextColor($text_color[0], $text_color[1], $text_color[2]);
    }
    // Add horizontal line between items
    $pdf->Cell(190, 0, '', 'T', 1, 'L');
}

$pdf->Ln(4);

// Subtotal
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor($text_color_light[0], $text_color_light[1], $text_color_light[2]);
$pdf->Cell(140, 8, 'Subtotal', 0, 0, 'R');
$pdf->SetTextColor($text_color[0], $text_color[1], $text_color[2]);
$pdf->Cell(50, 8, $currency_code . number_format($invoice['payment_amount'], 2), 0, 1, 'R');

// Tax if applicable
if ($invoice['tax_total'] > 0) {
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor($text_color_light[0], $text_color_light[1], $text_color_light[2]);
    $pdf->Cell(140, 8, 'Tax (' . $invoice['tax'] . ')', 0, 0, 'R');
    $pdf->SetTextColor($text_color[0], $text_color[1], $text_color[2]);
    $pdf->Cell(50, 8, $currency_code . number_format($invoice['tax_total'], 2), 0, 1, 'R');
}

// Total
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor($text_color_light[0], $text_color_light[1], $text_color_light[2]);
$pdf->Cell(140, 8, 'Total', 0, 0, 'R');
$pdf->SetTextColor($text_color[0], $text_color[1], $text_color[2]);
$pdf->Cell(50, 8, $currency_code . number_format($invoice['payment_amount'] + $invoice['tax_total'], 2), 0, 1, 'R');

?>