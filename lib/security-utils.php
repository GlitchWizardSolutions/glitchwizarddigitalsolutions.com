<?php
/**
 * Security Utilities
 * Centralized security functions for CSRF protection and input validation
 *
 * @version 1.0.0
 * @date 2025-11-29
 */

// Prevent direct access
if (!defined('BASE_URL')) {
    die('Configuration not loaded. Include config.php first.');
}

/**
 * CSRF Protection Functions
 */

/**
 * Generate a new CSRF token and store it in session
 * @return string The generated token
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token from POST data
 * @return bool True if token is valid
 */
function validate_csrf_token() {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

/**
 * Get CSRF token for use in forms
 * @return string The current CSRF token
 */
function get_csrf_token() {
    return generate_csrf_token();
}

/**
 * Output hidden CSRF token input field
 */
function csrf_token_field() {
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(get_csrf_token(), ENT_QUOTES) . '">';
}

/**
 * Input Validation Functions
 */

/**
 * Sanitize string input
 * @param string $input The input to sanitize
 * @return string Sanitized string
 */
function sanitize_string($input) {
    return trim(filter_var($input, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
}

/**
 * Validate email address
 * @param string $email Email to validate
 * @return bool True if valid email
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate and sanitize email
 * @param string $email Email to validate and sanitize
 * @return string|false Sanitized email or false if invalid
 */
function sanitize_email($email) {
    $email = trim($email);
    return validate_email($email) ? $email : false;
}

/**
 * Validate phone number (basic validation)
 * @param string $phone Phone number to validate
 * @return bool True if valid format
 */
function validate_phone($phone) {
    // Remove all non-digit characters
    $digits_only = preg_replace('/\D/', '', $phone);
    // Check if it's 10-15 digits (international format)
    return strlen($digits_only) >= 10 && strlen($digits_only) <= 15;
}

/**
 * Sanitize phone number
 * @param string $phone Phone to sanitize
 * @return string Sanitized phone number
 */
function sanitize_phone($phone) {
    return preg_replace('/[^\d\-\+\(\)\s\.]/', '', trim($phone));
}

/**
 * Validate required field
 * @param mixed $value Value to check
 * @return bool True if not empty
 */
function validate_required($value) {
    return !empty(trim($value));
}

/**
 * Validate string length
 * @param string $string String to check
 * @param int $min Minimum length
 * @param int $max Maximum length
 * @return bool True if within range
 */
function validate_length($string, $min = 0, $max = PHP_INT_MAX) {
    $length = strlen(trim($string));
    return $length >= $min && $length <= $max;
}

/**
 * Validate username (alphanumeric, underscore, dash only)
 * @param string $username Username to validate
 * @return bool True if valid
 */
function validate_username($username) {
    return preg_match('/^[a-zA-Z0-9_-]+$/', $username) === 1;
}

/**
 * Sanitize username
 * @param string $username Username to sanitize
 * @return string Sanitized username
 */
function sanitize_username($username) {
    return preg_replace('/[^a-zA-Z0-9_-]/', '', trim($username));
}

/**
 * Validate password strength
 * @param string $password Password to validate
 * @param int $min_length Minimum length (default 8)
 * @return bool True if meets requirements
 */
function validate_password($password, $min_length = 8) {
    // At least one uppercase, one lowercase, one digit, and minimum length
    return strlen($password) >= $min_length &&
           preg_match('/[A-Z]/', $password) &&
           preg_match('/[a-z]/', $password) &&
           preg_match('/[0-9]/', $password);
}

/**
 * Validate URL
 * @param string $url URL to validate
 * @return bool True if valid URL
 */
function validate_url($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Sanitize URL
 * @param string $url URL to sanitize
 * @return string|false Sanitized URL or false if invalid
 */
function sanitize_url($url) {
    $url = trim($url);
    return validate_url($url) ? $url : false;
}

/**
 * Validate numeric value
 * @param mixed $value Value to check
 * @return bool True if numeric
 */
function validate_numeric($value) {
    return is_numeric($value);
}

/**
 * Validate integer
 * @param mixed $value Value to check
 * @param int $min Minimum value (optional)
 * @param int $max Maximum value (optional)
 * @return bool True if valid integer within range
 */
function validate_integer($value, $min = null, $max = null) {
    if (!is_numeric($value) || $value != (int)$value) {
        return false;
    }

    $int_value = (int)$value;

    if ($min !== null && $int_value < $min) {
        return false;
    }

    if ($max !== null && $int_value > $max) {
        return false;
    }

    return true;
}

/**
 * Security Headers Functions
 */

/**
 * Set security headers
 */
function set_security_headers() {
    // Prevent clickjacking
    header('X-Frame-Options: SAMEORIGIN');

    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');

    // Enable XSS protection
    header('X-XSS-Protection: 1; mode=block');

    // Referrer policy
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // Content Security Policy - Environment-aware with optional nonce support
    $useNonces = defined('USE_CSP_NONCES') && USE_CSP_NONCES;

    if ($useNonces) {
        // Include nonce system
        require_once 'csp-nonces.php';
        header(get_csp_header_with_nonce());
    } else {
        // Traditional CSP without reporting
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
            // More permissive CSP for development - allows external resources for easier development
            header("Content-Security-Policy: default-src 'self' 'unsafe-eval'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://www.googletagmanager.com https://static.cloudflareinsights.com https://www.paypal.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://code.jquery.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; img-src 'self' data: https: http:; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; connect-src 'self' https://www.google-analytics.com https://www.paypal.com https://cdn.jsdelivr.net; frame-src 'self' https:;");
        } else {
            // Stricter CSP for production - only allow necessary external resources
            header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://www.googletagmanager.com https://static.cloudflareinsights.com https://www.paypal.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://code.jquery.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; img-src 'self' data: https:; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; connect-src 'self' https://www.google-analytics.com https://www.paypal.com https://cdn.jsdelivr.net;");
        }
    }

    // HSTS (HTTP Strict Transport Security) - only if HTTPS
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

/**
 * Rate Limiting Functions
 */

/**
 * Simple rate limiting by IP
 * @param string $action Action identifier
 * @param int $max_attempts Maximum attempts allowed
 * @param int $time_window Time window in seconds
 * @return bool True if within limits, false if rate limited
 */
function check_rate_limit($action, $max_attempts = 5, $time_window = 300) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = "rate_limit_{$action}_{$ip}";

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 1, 'first_attempt' => time()];
        return true;
    }

    $data = $_SESSION[$key];

    // Reset if time window has passed
    if (time() - $data['first_attempt'] > $time_window) {
        $_SESSION[$key] = ['count' => 1, 'first_attempt' => time()];
        return true;
    }

    // Increment counter
    $data['count']++;

    // Check if exceeded limit
    if ($data['count'] > $max_attempts) {
        return false;
    }

    $_SESSION[$key] = $data;
    return true;
}

/**
 * Log security events
 * @param string $event Event description
 * @param array $data Additional data to log
 */
function log_security_event($event, $data = []) {
    $log_data = [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'event' => $event,
        'data' => $data,
        'session_id' => session_id()
    ];

    // Log to file
    $log_file = dirname(__FILE__) . '/../private/security.log';
    $log_entry = json_encode($log_data) . PHP_EOL;

    @file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}
?>