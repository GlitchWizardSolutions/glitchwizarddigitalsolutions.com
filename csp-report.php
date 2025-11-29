<?php
/**
 * CSP Violation Report Endpoint
 *
 * This endpoint receives Content Security Policy violation reports from browsers.
 * Browsers automatically send reports when CSP violations occur.
 *
 * Endpoint: /csp-report.php
 * Method: POST
 * Content-Type: application/csp-report
 */

// Load configuration and database connections
require_once '../private/config.php';

// Include the CSP reporting system
require_once 'lib/csp-reporting.php';

// Handle the CSP report
handle_csp_report();
?>