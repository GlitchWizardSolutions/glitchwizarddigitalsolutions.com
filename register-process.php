<?php
/**
 * User Registration Process
 *
 * Handles new user registration with comprehensive validation,
 * security checks, and proper error handling.
 */

include 'assets/includes/public-config.php';
include_once includes_path . 'main.php';

/**
 * Validate registration form data
 *
 * @return array|bool Array of validated data or false on failure
 */
function validateRegistrationData() {
    $requiredFields = ['full_name', 'username', 'password', 'cpassword', 'email'];

    $rules = [
        'full_name' => [
            'sanitize' => 'sanitize_string',
            'validate' => [
                function($value) { return validate_length($value, 2, 100); },
                function($value) { return preg_match('/^[a-zA-Z. ]+$/', $value); }
            ]
        ],
        'username' => [
            'sanitize' => 'sanitize_username',
            'validate' => [
                function($value) { return validate_length($value, 3, 50); },
                function($value) { return preg_match('/^[a-zA-Z0-9]+$/', $value); }
            ]
        ],
        'password' => [
            'validate' => [
                function($value) { return validate_length($value, 5, 100); }
            ]
        ],
        'cpassword' => [
            'validate' => [
                function($value) { return $value === ($_POST['password'] ?? ''); }
            ]
        ],
        'email' => [
            'sanitize' => 'sanitize_email',
            'validate' => [
                'validate_email'
            ]
        ]
    ];

    $result = validateFormData($requiredFields, $rules);

    if (isset($result['errors'])) {
        sendJsonError('Validation failed: ' . implode(', ', $result['errors']));
    }

    return $result;
}

/**
 * Check if username or email already exists
 *
 * @param PDO $pdo Database connection
 * @param string $username
 * @param string $email
 * @return bool True if exists
 */
function checkExistingAccount($pdo, $username, $email) {
    try {
        $stmt = $pdo->prepare('SELECT id FROM accounts WHERE username = ? OR email = ?');
        $stmt->execute([$username, $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    } catch (PDOException $e) {
        handleException($e, 'registration_check_existing');
        return true; // Assume exists on error to be safe
    }
}

/**
 * Create new user account
 *
 * @param PDO $pdo Database connection
 * @param array $data Validated form data
 * @return int|bool User ID on success, false on failure
 */
function createUserAccount($pdo, $data) {
    try {
        $password = password_hash($data['password'], PASSWORD_DEFAULT);
        $activation_code = account_activation ? hash('sha256', uniqid() . $data['email'] . SECRET_KEY) : 'activated';
        $role = 'Member';
        $date = date('Y-m-d\TH:i:s');
        $ip = getClientIP();

        $stmt = $pdo->prepare('INSERT INTO accounts (full_name, username, password, email, activation_code, role, access_level, registered, last_seen, approved, ip) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

        if ($stmt->execute([$data['full_name'], $data['username'], $password, $data['email'], $activation_code, $role, 'Guest', $date, $date, 'Approved', $ip])) {
            return $pdo->lastInsertId();
        }

        return false;
    } catch (PDOException $e) {
        handleException($e, 'registration_create_account');
        return false;
    }
}

/**
 * Handle post-registration actions (email sending, auto-login)
 *
 * @param array $data User data
 * @param int $userId New user ID
 */
function handlePostRegistration($data, $userId) {
    if (account_activation) {
        // Send activation email
        try {
            send_email($data['email'], $data['activation_code'] ?? '', $data['username'], 'activation');
            sendJsonSuccess('Registration successful! Please check your email for activation instructions.');
        } catch (Exception $e) {
            log_security_event('activation_email_failed', ['email' => $data['email'], 'error' => $e->getMessage()]);
            sendJsonError('Registration successful, but activation email could not be sent. Please contact support.');
        }
    } else {
        // Auto-login if enabled
        if (auto_login_after_register) {
            session_regenerate_id(true);
            $_SESSION['loggedin'] = true;
            $_SESSION['sec-username'] = $data['username'];
            $_SESSION['name'] = $data['username'];
            $_SESSION['id'] = $userId;
            $_SESSION['role'] = 'Member';
            $_SESSION['access_level'] = 'Guest';
            $_SESSION['email'] = $data['email'];
            $_SESSION['full_name'] = $data['full_name'];

            log_security_event('auto_login_after_registration', ['username' => $data['username']]);
            sendJsonSuccess('Registration and login successful!', ['redirect' => 'index.php']);
        } else {
            sendJsonSuccess('Registration successful! You can now log in.');
        }
    }
}

// Main registration logic
try {
    // Validate CSRF token
    if (!validate_csrf_token()) {
        log_security_event('csrf_token_invalid_registration', ['ip' => getClientIP()]);
        sendJsonError('Security validation failed. Please refresh the page and try again.', 403);
    }

    // Validate form data
    $formData = validateRegistrationData();

    // Check for existing account
    if (checkExistingAccount($pdo, $formData['username'], $formData['email'])) {
        sendJsonError('An account with this username or email already exists.');
    }

    // Create account
    $userId = createUserAccount($pdo, $formData);
    if (!$userId) {
        sendJsonError('Failed to create account. Please try again.');
    }

    // Handle post-registration
    $formData['activation_code'] = account_activation ? hash('sha256', uniqid() . $formData['email'] . SECRET_KEY) : 'activated';
    handlePostRegistration($formData, $userId);

} catch (Exception $e) {
    handleException($e, 'user_registration');
}