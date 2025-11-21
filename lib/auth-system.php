<?php
/**
 * Unified Authentication System
 * 
 * Centralizes all authentication logic across the entire application.
 * Replaces multiple check_loggedin() implementations with single source of truth.
 * 
 * @version 1.0.0
 * @date 2025-11-20
 */

// Prevent direct access
if (!defined('site_menu_base') && !defined('public_path')) {
    die('Direct access not permitted');
}

/**
 * Get numerical rank for access level comparison
 * Lower number = higher access
 * 
 * @param string $access_level The access level string
 * @return int Rank number (1 = highest, 99 = unknown/lowest)
 */
function get_access_rank($access_level) {
    $ranks = [
        'Admin' => 1,           // Full system access
        'Master' => 2,          // Full dashboard access
        'Services' => 2,        // Full dashboard access
        'Production' => 2,      // Full dashboard access
        'Development' => 2,     // Full dashboard access
        'Onboarding' => 2,      // Full dashboard access
        'Hosting' => 3,         // Partial access: Documents OK, but no communication links/tickets
        'Guest' => 4,           // Limited access: No documents, no communication links/tickets
        'Closed' => 5,          // Account closed
        'Banned' => 6           // Cannot login
    ];
    return $ranks[$access_level] ?? 99; // Unknown access level = lowest access
}

/**
 * Check if user has minimum required access level
 * 
 * @param string $user_level The user's current access level
 * @param string $required_level The minimum required access level
 * @return bool True if user meets or exceeds required level
 */
function has_min_access($user_level, $required_level) {
    return get_access_rank($user_level) <= get_access_rank($required_level);
}

/**
 * Check if user can access Communication menu links
 * 
 * @param string $access_level The user's access level
 * @return bool True if user can click communication links
 */
function can_access_communication($access_level) {
    return in_array($access_level, [
        'Admin', 'Master', 'Services', 'Production', 'Development', 'Onboarding'
    ]);
}

/**
 * Check if user can access Documents menu links
 * 
 * @param string $access_level The user's access level
 * @return bool True if user can click document links
 */
function can_access_documents($access_level) {
    return in_array($access_level, [
        'Admin', 'Master', 'Services', 'Production', 'Development', 'Onboarding', 'Hosting'
    ]);
}

/**
 * Check if user can view ticket notifications
 * 
 * @param string $access_level The user's access level
 * @return bool True if user can see ticket bell
 */
function can_view_tickets($access_level) {
    return in_array($access_level, [
        'Admin', 'Master', 'Services', 'Production', 'Development', 'Onboarding'
    ]);
}

/**
 * Check if user is admin
 * 
 * @param string|null $access_level Optional access level, uses session if not provided
 * @return bool True if user is admin
 */
function is_admin($access_level = null) {
    $level = $access_level ?? ($_SESSION['access_level'] ?? 'Guest');
    return $level === 'Admin';
}

/**
 * Check if user has full dashboard access
 * Includes: Admin, Master, Services, Production, Development, Onboarding
 * 
 * @param string|null $access_level Optional access level, uses session if not provided
 * @return bool True if user has full access
 */
function has_full_access($access_level = null) {
    $level = $access_level ?? ($_SESSION['access_level'] ?? 'Guest');
    return in_array($level, [
        'Admin', 'Master', 'Services', 'Production', 'Development', 'Onboarding'
    ]);
}

/**
 * Check if user account is banned
 * 
 * @param string|null $access_level Optional access level, uses session if not provided
 * @return bool True if user is banned
 */
function is_banned($access_level = null) {
    $level = $access_level ?? ($_SESSION['access_level'] ?? 'Guest');
    return $level === 'Banned';
}

/**
 * Main authentication check
 * Verifies user is logged in, handles remember me cookies, redirects if not authenticated
 * 
 * This is the unified replacement for all check_loggedin() implementations
 * 
 * @param PDO $pdo Database connection
 * @param string|null $redirect_file Where to redirect if not logged in (null = index.php)
 * @return void
 */
function check_loggedin($pdo, $redirect_file = null) {
    // Set default redirect if not specified
    if ($redirect_file === null) {
        if (defined('site_menu_base')) {
            $redirect_file = site_menu_base . 'index.php';
        } elseif (defined('public_path')) {
            $redirect_file = public_path . 'index.php';
        } else {
            $redirect_file = '/index.php';
        }
    }
    
    // Check if user is already logged in
    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === TRUE) {
        // Update last seen timestamp
        $date = date('Y-m-d\TH:i:s');
        $stmt = $pdo->prepare('UPDATE accounts SET last_seen = ? WHERE id = ?');
        $stmt->execute([$date, $_SESSION['id']]);
        
        // Check if user is banned
        if (isset($_SESSION['access_level']) && $_SESSION['access_level'] === 'Banned') {
            session_destroy();
            header('Location: ' . $redirect_file . '?error=banned');
            exit;
        }
        
        return; // User is authenticated
    }
    
    // Check for remember me cookie
    if (isset($_COOKIE['rememberme']) && !empty($_COOKIE['rememberme'])) {
        $stmt = $pdo->prepare('SELECT * FROM accounts WHERE rememberme = ?');
        $stmt->execute([$_COOKIE['rememberme']]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($account) {
            // Check if account is banned
            if ($account['access_level'] === 'Banned') {
                setcookie('rememberme', '', time() - 3600, '/'); // Delete cookie
                header('Location: ' . $redirect_file . '?error=banned');
                exit;
            }
            
            // Auto-login user
            session_regenerate_id();
            $_SESSION['loggedin'] = TRUE;
            $_SESSION['sec-username'] = $account['username'];
            $_SESSION['name'] = $account['username'];
            $_SESSION['id'] = $account['id'];
            $_SESSION['role'] = $account['role'];
            $_SESSION['access_level'] = $account['access_level'];
            $_SESSION['email'] = $account['email'];
            $_SESSION['full_name'] = $account['full_name'];
            $_SESSION['document_path'] = $account['document_path'];
            
            // Update last seen timestamp
            $date = date('Y-m-d\TH:i:s');
            $stmt = $pdo->prepare('UPDATE accounts SET last_seen = ? WHERE id = ?');
            $stmt->execute([$date, $account['id']]);
            
            return; // User is now authenticated
        }
    }
    
    // User is not logged in, redirect to login page
    header('Location: ' . $redirect_file);
    exit;
}

/**
 * Check if IP is blocked due to too many login attempts
 * 
 * @param PDO $pdo Database connection
 * @return bool True if allowed to attempt login, False if blocked
 */
function can_attempt_login($pdo) {
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $now = date('Y-m-d H:i:s');
    
    $stmt = $pdo->prepare('SELECT * FROM login_attempts WHERE ip_address = ?');
    $stmt->execute([$ip_address]);
    $attempts = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($attempts) {
        // Check if attempts expired (1 day)
        $expire = date('Y-m-d H:i:s', strtotime('+1 day', strtotime($attempts['date'])));
        if ($now > $expire) {
            // Expired, delete record
            $stmt = $pdo->prepare('DELETE FROM login_attempts WHERE ip_address = ?');
            $stmt->execute([$ip_address]);
            return true;
        }
        
        // Check if blocked
        return $attempts['attempts_left'] > 0;
    }
    
    return true; // No record = allowed
}

/**
 * Record a failed login attempt
 * 
 * @param PDO $pdo Database connection
 * @return int Number of attempts remaining
 */
function record_login_attempt($pdo) {
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $now = date('Y-m-d H:i:s');
    
    $stmt = $pdo->prepare('SELECT id, attempts_left FROM login_attempts WHERE ip_address = ?');
    $stmt->execute([$ip_address]);
    $attempts = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($attempts) {
        $new_attempts = max(0, $attempts['attempts_left'] - 1);
        $stmt = $pdo->prepare('UPDATE login_attempts SET attempts_left = ?, `date` = ? WHERE id = ?');
        $stmt->execute([$new_attempts, $now, $attempts['id']]);
        return $new_attempts;
    } else {
        // First failed attempt (5 total, so 4 left after this one)
        $stmt = $pdo->prepare('INSERT INTO login_attempts (ip_address, attempts_left, `date`) VALUES (?, ?, ?)');
        $stmt->execute([$ip_address, 4, $now]);
        return 4;
    }
}

/**
 * Reset login attempts for an IP address (call after successful login)
 * 
 * @param PDO $pdo Database connection
 * @param string|null $ip_address IP to reset (null = current IP)
 * @return void
 */
function reset_login_attempts($pdo, $ip_address = null) {
    $ip = $ip_address ?? $_SERVER['REMOTE_ADDR'];
    $stmt = $pdo->prepare('DELETE FROM login_attempts WHERE ip_address = ?');
    $stmt->execute([$ip]);
}

/**
 * Get tooltip message for disabled features based on access level
 * 
 * @param string $access_level The user's access level
 * @param string $feature The feature being checked ('communication', 'documents', 'tickets')
 * @return string Tooltip message or empty string if feature is accessible
 */
function get_access_tooltip($access_level, $feature) {
    switch ($feature) {
        case 'communication':
            if (!can_access_communication($access_level)) {
                return $access_level === 'Hosting' 
                    ? 'Requires Service Subscription' 
                    : 'Not available for Guest accounts';
            }
            break;
            
        case 'documents':
            if (!can_access_documents($access_level)) {
                return 'Not available for Guest accounts';
            }
            break;
            
        case 'tickets':
            if (!can_view_tickets($access_level)) {
                return $access_level === 'Hosting'
                    ? 'Requires Service Subscription'
                    : 'Not available for Guest accounts';
            }
            break;
    }
    
    return ''; // Feature is accessible
}

/**
 * Require minimum access level or redirect
 * 
 * @param PDO $pdo Database connection
 * @param string $required_level Minimum required access level
 * @param string|null $redirect_url Where to redirect if access denied (null = index.php)
 * @return void
 */
function require_access_level($pdo, $required_level, $redirect_url = null) {
    // First ensure user is logged in
    check_loggedin($pdo);
    
    // Check access level
    $user_level = $_SESSION['access_level'] ?? 'Guest';
    
    if (!has_min_access($user_level, $required_level)) {
        // Access denied
        if ($redirect_url === null) {
            if (defined('site_menu_base')) {
                $redirect_url = site_menu_base . 'index.php?error=access_denied';
            } else {
                $redirect_url = '/index.php?error=access_denied';
            }
        }
        
        header('Location: ' . $redirect_url);
        exit;
    }
}

/**
 * Require admin access or redirect
 * 
 * @param PDO $pdo Database connection
 * @param string|null $redirect_url Where to redirect if not admin
 * @return void
 */
function require_admin($pdo, $redirect_url = null) {
    require_access_level($pdo, 'Admin', $redirect_url);
}
