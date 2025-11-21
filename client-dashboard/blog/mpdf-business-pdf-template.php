<?php
require_once __DIR__ . '/vendor/autoload.php';

use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// SMTP CONFIGURATION (Update with your SMTP server details)
const SMTP_HOST = 'smtp.yourdomain.com';
const SMTP_PORT = 587;
const SMTP_USER = 'you@yourdomain.com';
const SMTP_PASS = 'yourpassword';
const SMTP_FROM_NAME = 'Your Business';
const SMTP_FROM_EMAIL = 'no-reply@yourdomain.com';

define('PDF_PREVIEW_PATH', 'uploads/previews/');

function logDocumentActivity(PDO $pdo, $clientName, $documentTitle, $fileType, $filePath) {
    $stmt = $pdo->prepare("INSERT INTO audit_log 
        (client_name, document_title, file_type, file_path, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $clientName,
        $documentTitle,
        $fileType,
        $filePath,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
}

function exportAuditLogToCSV(PDO $pdo, $outputPath) {
    $stmt = $pdo->query("SELECT * FROM audit_log ORDER BY created_at DESC");
    $fp = fopen($outputPath, 'w');
    fputcsv($fp, ['ID', 'Client Name', 'Title', 'Type', 'Path', 'Date', 'IP', 'Agent']);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($fp, [
            $row['id'],
            $row['client_name'],
            $row['document_title'],
            $row['file_type'],
            $row['file_path'],
            $row['created_at'],
            $row['ip_address'],
            $row['user_agent']
        ]);
    }
    fclose($fp);
    return $outputPath;
}

function exportAuditLogToXLSX(PDO $pdo, $outputPath) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray([
        ['ID', 'Client Name', 'Title', 'Type', 'Path', 'Date', 'IP', 'Agent']
    ], NULL, 'A1');

    $stmt = $pdo->query("SELECT * FROM audit_log ORDER BY created_at DESC");
    $rowIndex = 2;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $sheet->fromArray([
            $row['id'],
            $row['client_name'],
            $row['document_title'],
            $row['file_type'],
            $row['file_path'],
            $row['created_at'],
            $row['ip_address'],
            $row['user_agent']
        ], NULL, 'A' . $rowIndex);
        $rowIndex++;
    }

    $writer = new Xlsx($spreadsheet);
    $writer->save($outputPath);
    return $outputPath;
}

// (All existing PDF/DOCX generator functions remain unchanged below)
