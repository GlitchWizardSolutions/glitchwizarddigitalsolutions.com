<?php
// Shared PDF generation function for Mom Hancock Report
// Used by both generate-pdf.php (download) and email-report.php (email attachment)

function generate_mom_hancock_pdf($conn, $date_start, $date_end, $output_mode = 'download') {
    // Get savings and budget amounts
    $savings = $conn->query("SELECT balance FROM budget WHERE id=25")->fetchColumn();
    
    // Pre-fetch all budget balances for Section 3 to avoid querying in loop
    $budget_balances = [];
    $balance_qry = $conn->query("SELECT id, balance FROM budget");
    while($bal = $balance_qry->fetch(PDO::FETCH_ASSOC)) {
        $budget_balances[$bal['id']] = $bal['balance'];
    }
    
    class PDF extends FPDF {
        function Header() {
            $this->SetFont('Arial', 'B', 14);
            $this->Cell(0, 10, 'Glitch Wizard Digital Solutions', 0, 1, 'C');
            $this->SetFont('Arial', '', 10);
            $this->Cell(0, 5, 'Monthly Budget Report', 0, 1, 'C');
            $this->Ln(5);
        }
        
        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial', 'I', 8);
            $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
        }
        
        function SectionTitle($title, $subtitle = '') {
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(0, 8, $title, 0, 1);
            if($subtitle) {
                $this->SetFont('Arial', 'I', 9);
                $this->Cell(0, 5, $subtitle, 0, 1);
            }
            $this->Ln(2);
        }
        
        function TableHeader($headers, $widths) {
            $this->SetFont('Arial', 'B', 9);
            $this->SetFillColor(200, 200, 200);
            foreach($headers as $i => $header) {
                $this->Cell($widths[$i], 7, $header, 1, 0, 'C', true);
            }
            $this->Ln();
        }
        
        function TableRow($data, $widths, $aligns = [], $fill = false) {
            $this->SetFont('Arial', '', 8);
            foreach($data as $i => $value) {
                $align = isset($aligns[$i]) ? $aligns[$i] : 'L';
                $this->Cell($widths[$i], 5, $value, 1, 0, $align, $fill);
            }
            $this->Ln();
        }
    }
    
    $pdf = new PDF('L', 'mm', 'Letter');
    $pdf->AddPage();
    
    // SECTION 1: Future Expenses & Balances
    $pdf->SectionTitle('FUTURE EXPENSES & BALANCES', '(Reserved Monthly - Adds to Balances)');
    
    $headers = ['1st Month', 'Amt Reserved', 'Reserved Bal', 'Future Expense', 'From Budget', 'Running Bal'];
    $widths = [25, 30, 30, 105, 40, 30];
    
    // Beginning Balance row
    $pdf->SetFont('Arial', '', 8);
    foreach($widths as $i => $width) {
        if($i == count($widths) - 2) {
            $pdf->Cell($width, 5, 'Beginning Balance:', 1, 0, 'L');
        } elseif($i == count($widths) - 1) {
            $pdf->Cell($width, 5, '3,500.00', 1, 1, 'R');
        } else {
            $pdf->Cell($width, 5, '', 1, 0, 'L');
        }
    }
    
    $pdf->TableHeader($headers, $widths);
    
    $running_balance = 3500;
    $minimum_balance = 0;
    $qry = $conn->query("SELECT id, budget, amount, balance FROM budget WHERE monthly_reserve_flag = 1 ORDER BY budget ASC");
    while($row = $qry->fetch(PDO::FETCH_ASSOC)){
        $row['budget'] = stripslashes(html_entity_decode($row['budget']));
        $running_balance -= $row['amount'];
        if($row['id'] == 24) $minimum_balance = $row['balance'];
        
        $data = [
            date("m/1", strtotime($date_start)),
            '$' . number_format($row['amount'], 2),
            '$' . number_format($row['balance'], 2),
            substr($row['budget'], 0, 50),
            '-$' . number_format($row['amount'], 2),
            '$' . number_format($running_balance, 2)
        ];
        $aligns = ['C', 'R', 'R', 'L', 'R', 'R'];
        $pdf->TableRow($data, $widths, $aligns);
    }
    $pdf->Ln(5);
    
    // SECTION 2: Left to Spend Report
    $pdf->SectionTitle('LEFT TO SPEND REPORT');
    
    $headers = ['Date', 'Bank Reference', 'Description', 'To Bank', 'From Bank', 'Running Bal'];
    $widths = [25, 65, 80, 30, 30, 30];
    
    // Remaining Balance row
    $pdf->SetFont('Arial', '', 8);
    foreach($widths as $i => $width) {
        if($i == count($widths) - 2) {
            $pdf->Cell($width, 5, 'Remaining Balance:', 1, 0, 'L');
        } elseif($i == count($widths) - 1) {
            $pdf->Cell($width, 5, number_format($running_balance, 2), 1, 1, 'R');
        } else {
            $pdf->Cell($width, 5, '', 1, 0, 'L');
        }
    }
    
    $pdf->TableHeader($headers, $widths);
    
    $qry = $conn->query("SELECT date, description, credits, debits, comment FROM `hancock` 
                        WHERE (flags_id = 3 OR flags_id = 10) 
                        AND DATE(date) BETWEEN '{$date_start}' AND '{$date_end}' 
                        ORDER BY date ASC");
    while($row = $qry->fetch(PDO::FETCH_ASSOC)){
        $row['description'] = stripslashes(html_entity_decode($row['description']));
        $row['comment'] = stripslashes(html_entity_decode($row['comment']));
        $running_balance += $row['credits'] + $row['debits'];
        
        $data = [
            date("m/d", strtotime($row['date'])),
            substr($row['description'], 0, 25),
            substr($row['comment'], 0, 40),
            '$' . number_format($row['credits'], 2),
            '$' . number_format($row['debits'], 2),
            '$' . number_format($running_balance, 2)
        ];
        $aligns = ['C', 'L', 'L', 'R', 'R', 'R'];
        $pdf->TableRow($data, $widths, $aligns);
    }
    $pdf->Ln(5);
    
    // SECTION 3: Bills Paid from Reserved Balances
    $pdf->SectionTitle('BILLS PAID FROM RESERVED BALANCES', '(Subtracts from Balances, first.)');
    
    $headers = ['Date', 'Bank Ref', 'Reserve Bill', 'From Reserve', 'Bal Remain', 'From Bank', 'Running'];
    $widths = [25, 50, 70, 35, 30, 20, 30];
    $pdf->TableHeader($headers, $widths);
    
    $amc = 0;
    $msft = 0;
    $qry = $conn->query("SELECT h.date, h.budget_id, h.comment, h.description, h.credits, h.debits, b.bill 
                        FROM hancock h 
                        INNER JOIN bills b ON h.bill_id = b.id 
                        WHERE h.flags_id = 9 
                        AND DATE(h.date) BETWEEN '{$date_start}' AND '{$date_end}' 
                        ORDER BY h.date ASC");
    while($row = $qry->fetch(PDO::FETCH_ASSOC)){
        $row['description'] = stripslashes(html_entity_decode($row['description']));
        $row['bill'] = stripslashes(html_entity_decode($row['bill']));
        
        if($row['description'] == 'MSFT * E0500U3B' || $row['description'] == 'MSFT * E0500U38'){
            $msft++;
            $row['bill'] = $row['bill'] . ' (#' . $msft . ')';
        }
        if($row['description'] == 'AMC 9640 ONLINE'){
            $amc++;
            $row['bill'] = $row['bill'] . ' (#' . $amc . ')';
        }
        
        // Use pre-fetched balance instead of querying in loop
        $new_balance = isset($budget_balances[$row['budget_id']]) ? $budget_balances[$row['budget_id']] : 0;
        
        $data = [
            date("m/d", strtotime($row['date'])),
            substr($row['description'], 0, 18),
            substr($row['bill'], 0, 25),
            '$' . number_format($row['debits'], 2),
            '$' . number_format($new_balance, 2),
            '$0.00',
            '$' . number_format($running_balance, 2)
        ];
        $aligns = ['C', 'L', 'L', 'R', 'R', 'R', 'R'];
        $pdf->TableRow($data, $widths, $aligns);
    }
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetFillColor(255, 255, 0);
    $pdf->Cell(array_sum($widths), 6, 'End Budget: $' . number_format($running_balance, 2), 1, 1, 'R', true);
    $pdf->Ln(5);
    
    // Add new page for remaining sections
    $pdf->AddPage('L');
    
    // SECTION 4: Non-Budgeted Savings
    $pdf->SectionTitle('NON-BUDGETED SAVINGS', '(Transactions from Savings Envelope)');
    
    $headers = ['Date', 'Bank Reference', 'Description', 'Whom', 'IN', 'OUT'];
    $widths = [25, 60, 105, 30, 20, 20];
    $pdf->TableHeader($headers, $widths);
    
    $qry = $conn->query("SELECT date, reimbursement, description, credits, debits, comment 
                        FROM `hancock` 
                        WHERE flags_id = 6 
                        AND DATE(date) BETWEEN '{$date_start}' AND '{$date_end}' 
                        ORDER BY date ASC");
    while($row = $qry->fetch(PDO::FETCH_ASSOC)){
        $row['description'] = stripslashes(html_entity_decode($row['description']));
        $row['comment'] = stripslashes(html_entity_decode($row['comment']));
        
        $data = [
            date("m/d", strtotime($row['date'])),
            substr($row['description'], 0, 25),
            substr($row['comment'], 0, 50),
            !empty($row['reimbursement']) ? $row['reimbursement'] : 'Barbara',
            '$' . number_format($row['credits'], 2),
            '$' . number_format($row['debits'], 2)
        ];
        $aligns = ['C', 'L', 'L', 'C', 'R', 'R'];
        $pdf->TableRow($data, $widths, $aligns);
    }
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetFillColor(255, 255, 0);
    $pdf->Cell(array_sum($widths), 6, 'Balance in Savings: $' . number_format($savings, 2), 1, 1, 'R', true);
    $pdf->Ln(5);
    
    // SECTION 5: Non-Budgeted Expenses
    $pdf->SectionTitle('NON-BUDGETED EXPENSES', '(To be reimbursed by Mom.)');
    
    $headers = ['Date', 'Bank Reference', 'Description', 'Whom', 'IN', 'OUT'];
    $widths = [25, 60, 105, 30, 20, 20];
    $pdf->TableHeader($headers, $widths);
    
    $actual_paid_amount = 0;
    $qry = $conn->query("SELECT date, reimbursement, description, credits, debits, comment 
                        FROM `hancock` 
                        WHERE flags_id = 4 
                        AND DATE(date) BETWEEN '{$date_start}' AND '{$date_end}' 
                        ORDER BY date ASC");
    while($row = $qry->fetch(PDO::FETCH_ASSOC)){
        $row['description'] = stripslashes(html_entity_decode($row['description']));
        $row['comment'] = stripslashes(html_entity_decode($row['comment']));
        $actual_paid_amount += $row['debits'];
        
        $data = [
            date("m/d", strtotime($row['date'])),
            substr($row['description'], 0, 25),
            substr($row['comment'], 0, 50),
            !empty($row['reimbursement']) ? $row['reimbursement'] : 'Barbara',
            '$' . number_format($row['credits'], 2),
            '$' . number_format($row['debits'], 2)
        ];
        $aligns = ['C', 'L', 'L', 'C', 'R', 'R'];
        $pdf->TableRow($data, $widths, $aligns);
    }
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetFillColor(255, 192, 203);
    $pdf->Cell(array_sum($widths), 6, 'Needs Reimbursement: $' . number_format($actual_paid_amount, 2), 1, 1, 'R', true);
    
    // Output based on mode
    if($output_mode == 'download') {
        $pdf->Output('D', 'Mom_Hancock_Report_' . date('Y-m-d') . '.pdf');
    } else {
        return $pdf->Output('S'); // Return as string for email attachment
    }
}
