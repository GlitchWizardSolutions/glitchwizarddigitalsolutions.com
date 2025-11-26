<?php
/**
 * Email Tracking Script
 * Tracks email opens and link clicks
 * PUBLIC FILE - No authentication required
 * STANDALONE - No external dependencies
 */

// Detect environment
$is_local = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', '::1']) || 
            strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost:') === 0;

// Database configuration
if ($is_local) {
    $db_host = '127.0.0.1:3307';
    $db_user = 'root';
    $db_pass = 'E714&lockD';
    $db_name = 'glitchwizarddigi_login_db';
    $base_url = 'http://localhost/public_html/';
} else {
    $db_host = 'localhost';
    $db_user = 'glitchwizarddigi_webdev';
    $db_pass = 'HanK00k33125015';
    $db_name = 'glitchwizarddigi_login_db';
    $base_url = 'https://glitchwizarddigitalsolutions.com/';
}

$action = isset($_GET['action']) ? $_GET['action'] : '';
$id = isset($_GET['id']) ? $_GET['id'] : '';

if ($action && $id) {
    try {
        $pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        if ($action == 'open') {
            // Track email open
            // Create tracking table if it doesn't exist
            $pdo->exec('CREATE TABLE IF NOT EXISTS newsletter_tracking (
                id INT AUTO_INCREMENT PRIMARY KEY,
                tracking_code VARCHAR(255) NOT NULL,
                action VARCHAR(50) NOT NULL,
                url TEXT NULL,
                ip_address VARCHAR(45) NULL,
                user_agent TEXT NULL,
                tracked_at DATETIME NOT NULL,
                INDEX idx_tracking_code (tracking_code),
                INDEX idx_action (action),
                INDEX idx_tracked_at (tracked_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
            
            // Insert tracking record
            $stmt = $pdo->prepare('INSERT INTO newsletter_tracking (tracking_code, action, ip_address, user_agent, tracked_at) 
                                   VALUES (?, ?, ?, ?, NOW())');
            $stmt->execute([
                $id,
                'open',
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
            
            // Return 1x1 transparent GIF pixel
            header('Content-Type: image/gif');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            // 1x1 transparent GIF (43 bytes)
            echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
            exit;
            
        } elseif ($action == 'click') {
            // Track link click
            $url = isset($_GET['url']) ? $_GET['url'] : '';
            
            if ($url) {
                // Create tracking table if it doesn't exist
                $pdo->exec('CREATE TABLE IF NOT EXISTS newsletter_tracking (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    tracking_code VARCHAR(255) NOT NULL,
                    action VARCHAR(50) NOT NULL,
                    url TEXT NULL,
                    ip_address VARCHAR(45) NULL,
                    user_agent TEXT NULL,
                    tracked_at DATETIME NOT NULL,
                    INDEX idx_tracking_code (tracking_code),
                    INDEX idx_action (action),
                    INDEX idx_tracked_at (tracked_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
                
                // Insert tracking record
                $stmt = $pdo->prepare('INSERT INTO newsletter_tracking (tracking_code, action, url, ip_address, user_agent, tracked_at) 
                                       VALUES (?, ?, ?, ?, ?, NOW())');
                $stmt->execute([
                    $id,
                    'click',
                    $url,
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    $_SERVER['HTTP_USER_AGENT'] ?? null
                ]);
                
                // Redirect to the actual URL
                header('Location: ' . $url);
                exit;
            }
        }
    } catch (PDOException $e) {
        // Log error silently, don't break user experience
        error_log('Newsletter tracking error: ' . $e->getMessage());
    }
}

// If we get here, something went wrong - redirect to homepage
header('Location: ' . $base_url);
exit;
