<?php
require_once 'mpdf-business-pdf-template.php'; // update this to your actual filename

// Extract posted values
$line1 = $_POST['line1'] ?? '';
$line2 = $_POST['line2'] ?? '';
$line3 = $_POST['line3'] ?? '';
$line4 = $_POST['line4'] ?? '';
$line5 = $_POST['line5'] ?? '';
$line6 = $_POST['line6'] ?? '';
$line7 = $_POST['line7'] ?? '';
$emailTo = $_POST['emailTo'] ?? '';
$action = $_POST['action'] ?? '';

// Handle logo upload
$logoPath = 'default-logo.png'; // fallback if no logo uploaded
if (!empty($_FILES['logo']['tmp_name']) && is_uploaded_file($_FILES['logo']['tmp_name'])) {
    $target = 'uploads/' . basename($_FILES['logo']['name']);
    move_uploaded_file($_FILES['logo']['tmp_name'], $target);
    $logoPath = $target;
}

// Choose action
switch ($action) {
    case 'download':
        createStyledBusinessPDFToDownload($logoPath, $line1, $line2, $line3, $line4, $line5, $line6, $line7);
        break;

    case 'browser':
        createStyledBusinessPDFToBrowser($logoPath, $line1, $line2, $line3, $line4, $line5, $line6, $line7);
        break;

    case 'docx':
        exportBusinessDocx($line1, $line2, $line3, $line4, $line5, $line6, $line7);
        break;

    case 'email':
        if (!empty($emailTo)) {
            emailBusinessPDF($logoPath, $line1, $line2, $line3, $line4, $line5, $line6, $line7, $emailTo);
            echo "Email sent to $emailTo.";
        } else {
            echo "Missing email address.";
        }
        break;

    default:
        echo "Invalid action.";
}
?>
