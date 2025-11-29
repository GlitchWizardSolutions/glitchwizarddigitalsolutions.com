<?php
/**
 * User Authentication Process
 *
 * Handles user login with security validation, rate limiting, and session management.
 * Uses proper error handling instead of exit() statements for better maintainability.
 */

include 'assets/includes/process-config.php';

// Initialize response array for structured error handling
$response = ['success' => false, 'message' => '', 'redirect' => ''];

/**
 * Send JSON error response and exit
 */
function sendErrorResponse($message, $httpCode = 400) {
    http_response_code($httpCode);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

/**
 * Send JSON success response and exit
 */
function sendSuccessResponse($message = '', $redirect = '') {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => $message, 'redirect' => $redirect]);
    exit;
}

// Get and validate IP address
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (empty($ip)) {
    log_security_event('auth_attempt_no_ip', ['user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown']);
    sendErrorResponse('Unable to determine IP address', 403);
}

// Check rate limiting first
if (!can_attempt_login($pdo)) {
    log_security_event('rate_limit_exceeded', ['ip' => $ip]);
    sendErrorResponse('Too many failed login attempts. Please try again later.', 429);
}

// Validate CSRF token
if (!validate_csrf_token()) {
    log_security_event('csrf_token_invalid', ['ip' => $ip]);
    sendErrorResponse('Security validation failed. Please refresh the page and try again.', 403);
}

// Validate required input fields
$username = sanitize_username($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (!validate_required($username) || !validate_required($password)) {
    sendErrorResponse('Please provide both username and password.');
}

if (!validate_length($username, 3, 50) || !validate_length($password, 5, 100)) {
    sendErrorResponse('Invalid username or password length.');
}

// Check if account exists and get user data
try {
    $stmt = $pdo->prepare('SELECT * FROM accounts WHERE username = ?');
    $stmt->execute([$username]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$account) {
        // Record failed attempt but don't reveal if username exists
        record_login_attempt($pdo);
        log_security_event('login_username_not_found', ['username' => $username, 'ip' => $ip]);
        sendErrorResponse('Invalid username or password.');
    }

    // Verify password
    if (!password_verify($password, $account['password'])) {
        record_login_attempt($pdo);
        log_security_event('login_password_incorrect', ['username' => $username, 'ip' => $ip]);
        sendErrorResponse('Invalid username or password.');
    }

    // Check account activation status
    if (account_activation && $account['activation_code'] !== 'activated' && $account['activation_code'] !== 'deactivated') {
        sendErrorResponse('Please activate your account to login. Click <a href="resend-activation.php">here</a> to resend the activation email.');
    }

    if ($account['activation_code'] === 'deactivated') {
        log_security_event('login_account_deactivated', ['username' => $username, 'ip' => $ip]);
        sendErrorResponse('Your account has been deactivated.');
    }

    // Check account approval
    if (account_approval && !$account['approved']) {
        sendErrorResponse('Your account has not been approved yet.');
    }

    // Check IP-based two-factor authentication
    if (($ip !== $account['ip']) && !empty($account['ip'])) {
        // Two-factor authentication required - IP address doesn't match saved IP
        $_SESSION['tfa_id'] = $account['id'];
        log_security_event('tfa_required', ['username' => $username, 'ip' => $ip, 'saved_ip' => $account['ip']]);
        sendSuccessResponse('tfa_required', 'twofactor.php');
    }

    // Authentication successful - create session
    session_regenerate_id(true); // More secure session regeneration

    $_SESSION['loggedin'] = true;
    $_SESSION['sec-username'] = $account['username']; // for blog system
    $_SESSION['name'] = $account['username'];
    $_SESSION['id'] = $account['id'];
    $_SESSION['role'] = $account['role'];
    $_SESSION['access_level'] = $account['access_level'];
    $_SESSION['email'] = $account['email'];
    $_SESSION['full_name'] = $account['full_name'];
    $_SESSION['document_path'] = $account['document_path'];

    // Handle remember me functionality
    if (isset($_POST['rememberme']) && $_POST['rememberme'] === '1') {
        $cookiehash = !empty($account['rememberme']) ? $account['rememberme'] : password_hash($account['id'] . $account['username'] . SECRET_KEY, PASSWORD_DEFAULT);

        // Set secure cookie
        $days = 60;
        setcookie('rememberme', $cookiehash, [
            'expires' => time() + (60 * 60 * 24 * $days),
            'path' => '/',
            'secure' => ENVIRONMENT === 'production', // HTTPS only in production
            'httponly' => true,
            'samesite' => 'Strict'
        ]);

        // Update database with remember me hash
        $stmt = $pdo->prepare('UPDATE accounts SET rememberme = ? WHERE id = ?');
        $stmt->execute([$cookiehash, $account['id']]);
    }

    // Update last seen timestamp
    $stmt = $pdo->prepare('UPDATE accounts SET last_seen = ? WHERE id = ?');
    $stmt->execute([date('Y-m-d\TH:i:s'), $account['id']]);

    // Clear failed login attempts on successful login
    $stmt = $pdo->prepare('DELETE FROM login_attempts WHERE ip_address = ?');
    $stmt->execute([$ip]);

    // Log successful login
    log_security_event('login_successful', ['username' => $username, 'ip' => $ip]);

    sendSuccessResponse('Login successful', 'redirect');

} catch (PDOException $e) {
    error_log("Database error during authentication: " . $e->getMessage());
    log_security_event('database_error', ['error' => $e->getMessage(), 'username' => $username, 'ip' => $ip]);
    sendErrorResponse('A system error occurred. Please try again later.', 500);
} catch (Exception $e) {
    error_log("Unexpected error during authentication: " . $e->getMessage());
    log_security_event('unexpected_error', ['error' => $e->getMessage(), 'username' => $username, 'ip' => $ip]);
    sendErrorResponse('An unexpected error occurred. Please try again later.', 500);
}
?>