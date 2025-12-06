<?php
/**
 * =====================================================
 * Error Logging System
 * Centralized error logging for all applications
 * =====================================================
 * 
 * Usage:
 * require_once 'lib/error-logging.php';
 * log_error('Invoice System', 'view_invoice.php', 'Database Query', 'Failed to fetch invoice');
 * 
 * Or with full details:
 * log_error_detailed([
 *     'application' => 'Newsletter System',
 *     'pagename' => 'campaign.php',
 *     'section' => 'Send Email',
 *     'error_type' => 'Exception',
 *     'severity' => 'Error',
 *     'thrown' => $exception->getMessage(),
 *     'inputs' => ['campaign_id' => $_GET['id']],
 *     'outputs' => $exception->getTraceAsString()
 * ]);
 */

/**
 * Note: get_error_db() function is provided by database-system.php
 * It uses DatabasePool to manage the error database connection.
 */

/**
 * Simple error logging function
 * 
 * @param string $application Application/system name
 * @param string $pagename Page filename
 * @param string $section Section/function where error occurred
 * @param string $error_message Error message
 * @param string $severity Severity level (Critical, Error, Warning, Notice, Info)
 * @param array $additional_data Additional data to log (optional)
 * @return bool True on success, false on failure
 */
function log_error($application, $pagename, $section, $error_message, $severity = 'Error', $additional_data = []) {
    $error_db = get_error_db();
    
    if (!$error_db) {
        // Fallback to PHP error log
        error_log("[$application][$pagename][$section] $error_message");
        return false;
    }
    
    try {
        // Get current file path
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller_file = isset($backtrace[0]['file']) ? $backtrace[0]['file'] : '';
        
        // Collect request data
        $inputs = array_merge(
            ['GET' => $_GET, 'POST' => $_POST],
            $additional_data
        );
        
        $stmt = $error_db->prepare('
            INSERT INTO error_handling (
                application, pagename, path, section, error_type, severity,
                thrown, inputs, user_id, session_id, ip_address, user_agent,
                request_method, request_uri, referer, environment, timestamp
            ) VALUES (
                :application, :pagename, :path, :section, :error_type, :severity,
                :thrown, :inputs, :user_id, :session_id, :ip_address, :user_agent,
                :request_method, :request_uri, :referer, :environment, NOW()
            )
        ');
        
        $stmt->execute([
            'application' => $application,
            'pagename' => $pagename,
            'path' => $caller_file,
            'section' => $section,
            'error_type' => 'Custom',
            'severity' => $severity,
            'thrown' => $error_message,
            'inputs' => json_encode($inputs, JSON_PRETTY_PRINT),
            'user_id' => isset($_SESSION['id']) ? $_SESSION['id'] : null,
            'session_id' => session_id(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? null,
            'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
            'referer' => $_SERVER['HTTP_REFERER'] ?? null,
            'environment' => defined('ENVIRONMENT') ? ENVIRONMENT : 'unknown'
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log('Failed to log error to database: ' . $e->getMessage());
        return false;
    }
}

/**
 * Detailed error logging function with full control
 * 
 * @param array $data Associative array of error data
 * @return bool True on success, false on failure
 * 
 * Available keys:
 * - application (required)
 * - pagename (required)
 * - section
 * - error_type (PHP Error, Exception, Database, Validation, Custom)
 * - severity (Critical, Error, Warning, Notice, Info)
 * - error_code
 * - thrown (required)
 * - inputs (array or JSON string)
 * - outputs
 * - noted
 */
function log_error_detailed($data) {
    $error_db = get_error_db();
    
    if (!$error_db) {
        error_log("[{$data['application']}][{$data['pagename']}] {$data['thrown']}");
        return false;
    }
    
    try {
        // Get current file path if not provided
        if (!isset($data['path'])) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $data['path'] = isset($backtrace[0]['file']) ? $backtrace[0]['file'] : '';
        }
        
        // Convert inputs to JSON if it's an array
        if (isset($data['inputs']) && is_array($data['inputs'])) {
            $data['inputs'] = json_encode($data['inputs'], JSON_PRETTY_PRINT);
        } elseif (!isset($data['inputs'])) {
            $data['inputs'] = json_encode(['GET' => $_GET, 'POST' => $_POST], JSON_PRETTY_PRINT);
        }
        
        $stmt = $error_db->prepare('
            INSERT INTO error_handling (
                application, pagename, path, section, error_type, severity, error_code,
                thrown, inputs, outputs, user_id, session_id, ip_address, user_agent,
                request_method, request_uri, referer, noted, environment, timestamp
            ) VALUES (
                :application, :pagename, :path, :section, :error_type, :severity, :error_code,
                :thrown, :inputs, :outputs, :user_id, :session_id, :ip_address, :user_agent,
                :request_method, :request_uri, :referer, :noted, :environment, NOW()
            )
        ');
        
        $stmt->execute([
            'application' => $data['application'] ?? 'Unknown',
            'pagename' => $data['pagename'] ?? 'Unknown',
            'path' => $data['path'],
            'section' => $data['section'] ?? null,
            'error_type' => $data['error_type'] ?? 'Custom',
            'severity' => $data['severity'] ?? 'Error',
            'error_code' => $data['error_code'] ?? null,
            'thrown' => $data['thrown'] ?? 'No error message provided',
            'inputs' => $data['inputs'],
            'outputs' => $data['outputs'] ?? null,
            'user_id' => isset($_SESSION['id']) ? $_SESSION['id'] : null,
            'session_id' => session_id(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? null,
            'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
            'referer' => $_SERVER['HTTP_REFERER'] ?? null,
            'noted' => $data['noted'] ?? null,
            'environment' => defined('ENVIRONMENT') ? ENVIRONMENT : 'unknown'
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log('Failed to log error to database: ' . $e->getMessage());
        return false;
    }
}

/**
 * Log PHP exception
 * 
 * @param Exception|Throwable $exception The exception to log
 * @param string $application Application name
 * @param string $pagename Page name
 * @param string $section Section where exception occurred
 * @return bool True on success, false on failure
 */
function log_exception($exception, $application, $pagename, $section = null) {
    return log_error_detailed([
        'application' => $application,
        'pagename' => $pagename,
        'path' => $exception->getFile(),
        'section' => $section ?? 'Exception Handler',
        'error_type' => 'Exception',
        'severity' => 'Error',
        'error_code' => $exception->getCode(),
        'thrown' => $exception->getMessage(),
        'outputs' => $exception->getTraceAsString(),
        'noted' => get_class($exception) . ' on line ' . $exception->getLine()
    ]);
}

/**
 * Log database error
 * 
 * @param PDOException $exception Database exception
 * @param string $application Application name
 * @param string $pagename Page name
 * @param string $query SQL query that failed (optional)
 * @return bool True on success, false on failure
 */
function log_database_error($exception, $application, $pagename, $query = null) {
    $outputs = $exception->getTraceAsString();
    if ($query) {
        $outputs = "Query: " . $query . "\n\n" . $outputs;
    }
    
    return log_error_detailed([
        'application' => $application,
        'pagename' => $pagename,
        'section' => 'Database Query',
        'error_type' => 'Database',
        'severity' => 'Error',
        'error_code' => $exception->getCode(),
        'thrown' => $exception->getMessage(),
        'outputs' => $outputs
    ]);
}

/**
 * Set global error and exception handlers (optional)
 * Call this at the beginning of your application if you want automatic error logging
 */
function enable_global_error_logging($application_name = 'Unknown Application') {
    // Custom error handler
    set_error_handler(function($errno, $errstr, $errfile, $errline) use ($application_name) {
        $severity_map = [
            E_ERROR => 'Critical',
            E_WARNING => 'Warning',
            E_PARSE => 'Critical',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Critical',
            E_CORE_WARNING => 'Warning',
            E_COMPILE_ERROR => 'Critical',
            E_COMPILE_WARNING => 'Warning',
            E_USER_ERROR => 'Error',
            E_USER_WARNING => 'Warning',
            E_USER_NOTICE => 'Notice',
            E_STRICT => 'Notice',
            E_RECOVERABLE_ERROR => 'Error',
            E_DEPRECATED => 'Notice',
            E_USER_DEPRECATED => 'Notice'
        ];
        
        log_error_detailed([
            'application' => $application_name,
            'pagename' => basename($errfile),
            'path' => $errfile,
            'section' => 'Line ' . $errline,
            'error_type' => 'PHP Error',
            'severity' => $severity_map[$errno] ?? 'Error',
            'error_code' => $errno,
            'thrown' => $errstr
        ]);
        
        return false; // Continue with normal error handling
    });
    
    // Custom exception handler
    set_exception_handler(function($exception) use ($application_name) {
        log_exception($exception, $application_name, basename($exception->getFile()));
    });
}

/**
 * Critical error logging - ALWAYS logs regardless of environment or toggle
 * Used for system failures, database connection errors, critical config issues
 *
 * @param string $application Application/system name
 * @param string $pagename Page filename
 * @param string $section Section/function where error occurred
 * @param string $error_message Error message
 * @param string $severity Severity level (Critical, Error, Warning, Notice, Info)
 * @param array $additional_data Additional data to log (optional)
 * @return bool True on success, false on failure
 */
function critical_log($application, $pagename, $section, $error_message, $severity = 'Critical', $additional_data = []) {
    return log_error($application, $pagename, $section, $error_message, $severity, $additional_data);
}

/**
 * Debug logging - Conditional based on environment and toggle state
 * Development: Always logs
 * Production: Only logs if debug_logging_enabled = TRUE
 *
 * @param string $application Application/system name
 * @param string $pagename Page filename
 * @param string $section Section/function where error occurred
 * @param string $error_message Error message
 * @param string $severity Severity level (Critical, Error, Warning, Notice, Info)
 * @param array $additional_data Additional data to log (optional)
 * @return bool True on success, false on failure
 */
function debug_log($application, $pagename, $section, $error_message, $severity = 'Info', $additional_data = []) {
    // Include environment detection if not already loaded
    if (!defined('ENVIRONMENT')) {
        require_once __DIR__ . '/../../private/config.php';
    }

    // Always log in development
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        return log_error($application, $pagename, $section, $error_message, $severity, $additional_data);
    }

    // In production, check toggle state
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
        $error_db = get_error_db();
        if ($error_db) {
            try {
                $stmt = $error_db->prepare("SELECT debug_logging_enabled FROM logging_settings WHERE id = 1");
                $stmt->execute();
                $enabled = $stmt->fetchColumn();

                if ($enabled) {
                    return log_error($application, $pagename, $section, $error_message, $severity, $additional_data);
                }
            } catch (PDOException $e) {
                // If we can't check the toggle, don't log debug messages
                return false;
            }
        }
    }

    // Default: don't log
    return false;
}
