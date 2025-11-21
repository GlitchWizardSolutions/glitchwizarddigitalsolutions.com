<?php
/**
 * Configuration Only - No HTML Output
 * 
 * This file loads configuration and database connections WITHOUT outputting any HTML.
 * Use this for pages that need to:
 * - Send HTTP headers (redirects, cookies, etc.)
 * - Send emails
 * - Modify session data
 * 
 * Created: 2025-11-18
 * Purpose: Fix "headers already sent" issues in email system
 */

// Start session
session_start();

// Load configuration constants
require_once '../private/config.php';

// Load unified email system
require_once public_path . 'lib/email-system.php';

// Connect to primary database (login/accounts)
try {
    $pdo = new PDO('mysql:host=' . db_host . ';dbname=' . db_name . ';charset=' . db_charset, db_user, db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
    exit('Failed to connect to database: ' . $exception->getMessage());
}

// Connect to budget database
try {
    $budget_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name7 . ';charset=' . db_charset, db_user, db_pass);
    $budget_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
    exit('Failed to connect to budget database: ' . $exception->getMessage());
}

// Connect to blog database
try {
    $blog_pdo = new PDO('mysql:host=' . db_host . ';dbname=' . db_name12 . ';charset=' . db_charset, db_user, db_pass);
    $blog_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
    exit('Failed to connect to blog database: ' . $exception->getMessage());
}

// DO NOT include public-page-setup.php here - that outputs HTML!
// Pages using this file must manually include public-page-setup.php AFTER all logic/headers/email
?>
