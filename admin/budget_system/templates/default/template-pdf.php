<?php
defined('INVOICE') or exit;

require base_path . 'lib/fpdf/fpdf.php';

// Colors
$text_color = [93, 103, 121];
$text_color_light = [159, 164, 177];
$text_color_alt = [20, 129, 231];
$content_border_color = [237, 239, 243];
$header_color = [74, 83, 97];

// convert currency code to HTML entity
$currency_code = html_entity_decode(currency_code, ENT_HTML5, 'UTF-8');

$pdf = new FPDF();
$pdf->AddPage();

// Header
$pdf->SetFont('Arial', 'B', 24);
$pdf->SetTextColor($header_color[0], $header_color[1], $header_color[2]);
$pdf->Cell(0, 10, 'INVOICE', 0, 1, 'L');
$pdf->Ln(2);

$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor($text_color_light[0], $text_color_light[1], $text_color_light[2]);
$pdf->Cell(0, 10, 'Due ' . date('F d, Y h:ia', strtotime($invoice['due_date'])), 0, 1, 'L');
$pdf->Ln(10);

// Company Logo
if (company_logo) {
    $image_width = 30;
    $image_height = 30;
    $page_width = $pdf->GetPageWidth();
    $x = $page_width - $image_width - 5;
    $pdf->Image(company_logo, $x, 5, $image_width, $image_height);
}

// Company, Client Details and Invoice Meta
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor($text_color_light[0], $text_color_light[1], $text_color_light[2]);
$pdf->Cell(70, 5, 'From', 0, 0, 'L');
$pdf->Cell(60, 5, 'To', 0, 0, 'L');
$pdf->SetTextColor($text_color_light[0], $text_color_light[1], $text_color_light[2]);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(60, 5, 'Invoice #', 0, 1, 'R');

$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor($text_color_alt[0], $text_color_alt[1], $text_color_alt[2]);
$pdf->Cell(70, 10, company_name, 0, 0, 'L');
$pdf->Cell(60, 10, $client['first_name'] . ' ' . $client['last_name'], 0, 0, 'L');
$pdf->Cell(60, 10, $invoice['invoice_number'], 0, 1, 'R');

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor($text_color[0], $text_color[1], $text_color[2]);
$pdf->MultiCell(70, 5, str_replace('\n', "\n", company_address) . "\n" . company_email . "\n" . company_phone, 0, 'L');
$x = $pdf->GetX();
$y = $pdf->GetY();
$pdf->SetXY(80, 58);
$pdf->MultiCell(60, 5, implode("\n", $client_address) . "\n" . $client['email'] . "\n" . $client['phone'], 0, 'L');
$pdf->SetXY($x + 130, 58);

$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor($text_color_light[0], $text_color_light[1], $text_color_light[2]);
$pdf->Cell(0, 5, 'Invoice Date', 0, 1, 'R');
$pdf->SetTextColor($text_color_alt[0], $text_color_alt[1], $text_color_alt[2]);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, date('F d, Y', strtotime($invoice['created'])), 0, 1, 'R');

$pdf->Ln(45);

// Invoice Items Table
// Table header
$pdf->SetDrawColor($content_border_color[0], $content_border_color[1], $content_border_color[2]);
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor($text_color_light[0], $text_color_light[1], $text_color_light[2]);
$pdf->Cell(70, 10, 'Name', 'B', 0, 'L');
$pdf->Cell(30, 10, 'Quantity', 'B', 0, 'L');
$pdf->Cell(40, 10, 'Price', 'B', 0, 'L');
$pdf->Cell(50, 10, 'Total', 'B', 1, 'R');
// Table body
$pdf->SetFont('Arial', '', 12);
$pdf->SetTextColor($text_color[0], $text_color[1], $text_color[2]);
foreach ($invoice_items as $item) {
    $pdf->Cell(70, 10, $item['item_name'], 0, 0, 'L');
    $pdf->Cell(30, 10, $item['item_quantity'], 0, 0, 'L');
    $pdf->Cell(40, 10, $currency_code . number_format($item['item_price'], 2), 0, 0, 'L');
    $pdf->Cell(50, 10, $currency_code . number_format($item['item_price'] * $item['item_quantity'], 2), 0, 1, 'R');
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

// Notes
if ($invoice['notes']) {
    $pdf->Ln(10);
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor($text_color[0], $text_color[1], $text_color[2]);
    $pdf->MultiCell(0, 10, $invoice['notes'], 0, 'L');
}

?>