<?php
require('fpdf/fpdf.php');

class PDF extends FPDF {

    // Function to set the document header
    function Header() {

        global $title;
        
        // Sets font to Arial bold 15
        $this->SetFont('Arial', 'B', 15);
      
        // Calculate string length 
        $w = $this->GetStringWidth($title) + 6;
        $this->SetX((210 - $w) / 2);
      
        // Set drawing color
        $this->SetDrawColor(0, 80, 180);
      
        // It defines the grey color for filling
        $this->SetFillColor(105, 105, 105);
      
        // Sets the text color
        $this->SetTextColor(255, 0, 0);
      
        // Set the line width to 1 mm)
        $this->SetLineWidth(1);
      
        // Prints a cell Title
        $this->Cell($w, 9, $title, 1, 1, 'C', 1);
      
        // Line break
        $this->Ln(10);
    }
  
    // Function to set the document footer
    function Footer() {

        // Set Y Position from bottom
        $this->SetY(-20);
      
        // Sets font to Arial italic 10
        $this->SetFont('Arial', 'I', 10);
      
        // Sets the Text color in gray
        $this->SetTextColor(128);
      
        // Prints a cell with Page number
        $this->Cell(0, 10, 'Page ' 
            . $this->PageNo(), 0, 0, 'C');
    }
  
    // Function to set the title for a tutorial
    function tutorialTitle($num, $label) {

        // Sets font to Arial 12
        $this->SetFont('Arial', '', 12);
      
        // Sets to fill Background color with Light grey 
        $this->SetFillColor(211, 211, 211);
      
        // Prints a cell with Title for tutorial
        $this->Cell(0, 6, "Chapter $num : $label", 0, 1, 'L', 1);
      
        // Line break
        $this->Ln(4);
    }
  
    // Function to set the content from a external file 
    function tutorialContent($file) {

        // Read text file
        $f   = fopen($file, 'r');
        $txt = fread($f, filesize($file));
        fclose($f);
      
        // Sets the font to Times 12
        $this->SetFont('Times', '', 12);
      
        // It prints texts with line breaks
        $this->MultiCell(0, 5, $txt);
      
        //Puts a Line break
        $this->Ln();
      
        // Set font in italics
        $this->SetFont('', 'I');
      
        // Prints a cell
        $this->Cell(0, 5, '(end of content)');
    }
    
    function showTutorial($num, $title, $file) {

        // Add a new page
        $this->AddPage();
        $this->tutorialTitle($num, $title);
        $this->tutorialContent($file);
    }
}

// Initiate a PDF object
$pdf   = new PDF();
$title = 'C Programming Language';

// Sets the document title
$pdf->SetTitle($title);

// Sets the document author name
$pdf->SetAuthor('gfg author name');

$pdf->showTutorial(
    1, 
    'C Language Introduction', 
    'text_include.txt'
);

$pdf->showTutorial(
    2, 
    'C Programming Language Standard', 
   'text_include.txt'
);

$pdf->showTutorial(
    3, 
    'Importance of function prototype in C', 
    'text_include.txt'
);

$pdf->Output();
?>