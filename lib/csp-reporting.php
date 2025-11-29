<?php
/**
 * CSP Violation Reporting System
 *
 * Handles Content Security Policy violation reports from browsers
 * and provides admin dashboard functionality to monitor violations.
 */

// Database table creation SQL
/*
CREATE TABLE IF NOT EXISTS csp_violations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_uri VARCHAR(500) NOT NULL,
    violated_directive VARCHAR(100) NOT NULL,
    original_policy TEXT NOT NULL,
    blocked_uri VARCHAR(500),
    source_file VARCHAR(500),
    line_number INT,
    column_number INT,
    status_code INT,
    user_agent TEXT,
    ip_address VARCHAR(45),
    referrer VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at),
    INDEX idx_violated_directive (violated_directive),
    INDEX idx_document_uri (document_uri)
);
*/

/**
 * Handle CSP violation reports
 */
function handle_csp_report() {
    // Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Method not allowed');
    }

    // Get the raw POST data
    $json = file_get_contents('raw');
    if (!$json) {
        http_response_code(400);
        exit('No data received');
    }

    // Decode the JSON report
    $report = json_decode($json, true);
    if (!$report || !isset($report['csp-report'])) {
        http_response_code(400);
        exit('Invalid CSP report format');
    }

    $violation = $report['csp-report'];

    // Validate required fields
    if (!isset($violation['document-uri']) || !isset($violation['violated-directive'])) {
        http_response_code(400);
        exit('Missing required CSP report fields');
    }

    // Store the violation
    store_csp_violation($violation);

    // Return success
    http_response_code(200);
    exit('CSP violation logged');
}

/**
 * Store CSP violation in database
 */
function store_csp_violation($violation) {
    try {
        // Get database connection
        $pdo = get_accounts_db();

        // Prepare insert statement
        $stmt = $pdo->prepare("
            INSERT INTO csp_violations (
                document_uri, violated_directive, original_policy, blocked_uri,
                source_file, line_number, column_number, status_code,
                user_agent, ip_address, referrer
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        // Extract data from violation report
        $documentUri = $violation['document-uri'] ?? '';
        $violatedDirective = $violation['violated-directive'] ?? '';
        $originalPolicy = $violation['original-policy'] ?? '';
        $blockedUri = $violation['blocked-uri'] ?? '';
        $sourceFile = $violation['source-file'] ?? '';
        $lineNumber = isset($violation['line-number']) ? (int)$violation['line-number'] : null;
        $columnNumber = isset($violation['column-number']) ? (int)$violation['column-number'] : null;
        $statusCode = isset($violation['status-code']) ? (int)$violation['status-code'] : null;

        // Get additional context
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ipAddress = getClientIP();
        $referrer = $_SERVER['HTTP_REFERER'] ?? '';

        // Execute the insert
        $stmt->execute([
            $documentUri,
            $violatedDirective,
            $originalPolicy,
            $blockedUri,
            $sourceFile,
            $lineNumber,
            $columnNumber,
            $statusCode,
            $userAgent,
            $ipAddress,
            $referrer
        ]);

        // Log the violation for immediate awareness
        log_security_event('csp_violation', [
            'directive' => $violatedDirective,
            'blocked_uri' => $blockedUri,
            'document_uri' => $documentUri,
            'ip' => $ipAddress
        ]);

    } catch (Exception $e) {
        // Log the error but don't expose it to the client
        error_log("Failed to store CSP violation: " . $e->getMessage());
    }
}

/**
 * Get CSP violation statistics
 */
function get_csp_stats($days = 30) {
    try {
        $pdo = get_accounts_db();

        // Get total violations in the last N days
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total_violations
            FROM csp_violations
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->execute([$days]);
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total_violations'];

        // Get violations by directive
        $stmt = $pdo->prepare("
            SELECT violated_directive, COUNT(*) as count
            FROM csp_violations
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY violated_directive
            ORDER BY count DESC
            LIMIT 10
        ");
        $stmt->execute([$days]);
        $byDirective = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get recent violations
        $stmt = $pdo->prepare("
            SELECT * FROM csp_violations
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY created_at DESC
            LIMIT 50
        ");
        $stmt->execute([$days]);
        $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'total_violations' => $total,
            'by_directive' => $byDirective,
            'recent_violations' => $recent,
            'period_days' => $days
        ];

    } catch (Exception $e) {
        error_log("Failed to get CSP stats: " . $e->getMessage());
        return [
            'total_violations' => 0,
            'by_directive' => [],
            'recent_violations' => [],
            'period_days' => $days
        ];
    }
}

/**
 * Clean up old CSP violations (keep last 90 days)
 */
function cleanup_csp_violations() {
    try {
        $pdo = get_accounts_db();
        $stmt = $pdo->prepare("
            DELETE FROM csp_violations
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)
        ");
        $stmt->execute();
        return $stmt->rowCount();
    } catch (Exception $e) {
        error_log("Failed to cleanup CSP violations: " . $e->getMessage());
        return 0;
    }
}