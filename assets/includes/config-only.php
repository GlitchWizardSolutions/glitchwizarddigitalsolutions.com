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

// Increase memory limit for config loading
ini_set('memory_limit', '1024M');

// Start session
session_start();

// Load configuration constants (also loads database-system.php, email-system.php, error-logging.php)
require_once '../private/config.php';

// Get database connections using unified system (prevents duplicate connections)
$pdo = get_accounts_db();      // Main login/accounts database
$budget_db = get_budget_db();  // Budget database
$blog_pdo = get_blog_db();     // Blog database

// DO NOT include public-page-setup.php here - that outputs HTML!
// Pages using this file must manually include public-page-setup.php AFTER all logic/headers/email
?>
