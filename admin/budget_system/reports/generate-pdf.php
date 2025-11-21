<?php
session_start();
if(!isset($_SESSION['id'])){
    header('Location: ../../../index.php');
    exit();
}

require_once(__DIR__ . '/../../../../private/config.php');
require_once(__DIR__ . '/../../fpdf/fpdf.php');
require_once(__DIR__ . '/lib/generate-mom-hancock-pdf.php');

// Connect to MySQL database
try {
    $budget_pdo = new PDO('mysql:host=' . db_host . ';dbname=' . db_name7 . ';charset=utf8', db_user, db_pass);
    $budget_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
    exit('Failed to connect to database: ' . $exception->getMessage());
}
$conn = $budget_pdo;

$date_start = isset($_GET['date_start']) ? $_GET['date_start'] : date("Y-m-1");
$date_end = isset($_GET['date_end']) ? $_GET['date_end'] : date("Y-m-t");

// Generate and download PDF using shared function
generate_mom_hancock_pdf($conn, $date_start, $date_end, 'download');
