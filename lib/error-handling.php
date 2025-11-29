<?php
/**
 * Common Error Handling Utilities
 *
 * Provides standardized error handling functions across the application.
 * Replaces inconsistent use of die(), exit(), and echo statements.
 */

/**
 * Send a JSON error response and exit
 *
 * @param string $message Error message to display
 * @param int $httpCode HTTP status code (default: 400)
 * @param array $additionalData Additional data to include in response
 */
function sendJsonError($message, $httpCode = 400, $additionalData = []) {
    http_response_code($httpCode);
    header('Content-Type: application/json');

    $response = array_merge([
        'success' => false,
        'message' => $message,
        'timestamp' => date('c')
    ], $additionalData);

    echo json_encode($response);
    exit;
}

/**
 * Send a JSON success response and exit
 *
 * @param string $message Success message
 * @param array $data Additional data to include
 * @param int $httpCode HTTP status code (default: 200)
 */
function sendJsonSuccess($message = '', $data = [], $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json');

    $response = array_merge([
        'success' => true,
        'message' => $message,
        'timestamp' => date('c')
    ], $data);

    echo json_encode($response);
    exit;
}

/**
 * Send an HTML error response and exit
 *
 * @param string $message Error message
 * @param int $httpCode HTTP status code
 * @param string $title Page title
 */
function sendHtmlError($message, $httpCode = 400, $title = 'Error') {
    http_response_code($httpCode);
    header('Content-Type: text/html');

    echo "<!DOCTYPE html>
<html>
<head>
    <title>$title</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .error { color: #d9534f; background: #f2dede; border: 1px solid #ebccd1; padding: 15px; border-radius: 4px; }
        .back-link { margin-top: 20px; }
    </style>
</head>
<body>
    <div class='error'>
        <h2>$title</h2>
        <p>$message</p>
    </div>
    <div class='back-link'>
        <a href='javascript:history.back()'>← Go Back</a>
    </div>
</body>
</html>";
    exit;
}

/**
 * Handle exceptions with proper logging and user-friendly responses
 *
 * @param Exception $e The exception to handle
 * @param string $context Context where the error occurred
 * @param bool $showDetails Whether to show detailed error info (only in development)
 */
function handleException(Exception $e, $context = 'application', $showDetails = false) {
    // Log the error
    error_log("Exception in $context: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    // Log security event if security utils are available
    if (function_exists('log_security_event')) {
        log_security_event('exception_occurred', [
            'context' => $context,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
    }

    // Show appropriate response based on environment
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development' && $showDetails) {
        sendJsonError(
            "Development Error: " . $e->getMessage(),
            500,
            [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]
        );
    } else {
        sendJsonError('An unexpected error occurred. Please try again later.', 500);
    }
}

/**
 * Validate and sanitize common form inputs
 *
 * @param array $requiredFields Required field names
 * @param array $rules Validation rules for each field
 * @return array|bool Array of sanitized data or false on validation failure
 */
function validateFormData($requiredFields = [], $rules = []) {
    $errors = [];
    $sanitized = [];

    // Check required fields
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || !validate_required($_POST[$field])) {
            $errors[] = "Field '$field' is required.";
        }
    }

    if (!empty($errors)) {
        return ['errors' => $errors];
    }

    // Apply validation rules
    foreach ($rules as $field => $fieldRules) {
        $value = $_POST[$field] ?? '';

        if (isset($fieldRules['sanitize'])) {
            $sanitizeFunc = $fieldRules['sanitize'];
            $value = $sanitizeFunc($value);
        }

        if (isset($fieldRules['validate'])) {
            foreach ($fieldRules['validate'] as $validator) {
                if (!$validator($value)) {
                    $errors[] = "Invalid value for field '$field'.";
                    break;
                }
            }
        }

        $sanitized[$field] = $value;
    }

    return empty($errors) ? $sanitized : ['errors' => $errors];
}

/**
 * Check if the current request is an AJAX request
 *
 * @return bool
 */
function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Get the client's IP address safely
 *
 * @return string
 */
function getClientIP() {
    $ipHeaders = [
        'HTTP_CF_CONNECTING_IP', // Cloudflare
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];

    foreach ($ipHeaders as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = $_SERVER[$header];

            // Handle comma-separated IPs (like X-Forwarded-For)
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }

            // Validate IP address
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }

    return 'unknown';
}