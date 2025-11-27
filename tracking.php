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
                // Decode URL if it was encoded
                $url = urldecode($url);
                
                // If URL doesn't have a protocol, add https://
                if (!preg_match('/^https?:\/\//i', $url)) {
                    // Check if it's a relative URL starting with /
                    if (strpos($url, '/') === 0) {
                        $url = $base_url . ltrim($url, '/');
                    } else {
                        $url = 'https://' . $url;
                    }
                }
                
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
