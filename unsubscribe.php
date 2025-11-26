<?php
/**
 * Newsletter Unsubscribe Page
 * Allows subscribers to unsubscribe from newsletters
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

$id = isset($_GET['id']) ? $_GET['id'] : '';
$message = '';
$unsubscribed = false;
$found_subscriber = null;

if ($id) {
    try {
        $pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Find subscriber by matching the hash
        $stmt = $pdo->query('SELECT * FROM subscribers');
        $subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($subscribers as $subscriber) {
            // Match the hash (same method used in sendmail.php)
            if (sha1($subscriber['id'] . $subscriber['email']) === $id) {
                $found_subscriber = $subscriber;
                break;
            }
        }
        
        if ($found_subscriber) {
            if (isset($_POST['confirm_unsubscribe'])) {
                // Update subscriber status to unsubscribed
                $stmt = $pdo->prepare('UPDATE subscribers SET status = "Unsubscribed" WHERE id = ?');
                $stmt->execute([$found_subscriber['id']]);
                
                $message = 'You have been successfully unsubscribed from our newsletter.';
                $unsubscribed = true;
            }
        } else {
            $message = 'Invalid unsubscribe link. Please contact us if you continue to receive emails.';
        }
    } catch (PDOException $e) {
        $message = 'An error occurred. Please try again later.';
        error_log('Unsubscribe error: ' . $e->getMessage());
    }
} else {
    $message = 'Invalid unsubscribe link.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribe - Newsletter</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 100%;
            padding: 40px;
            text-align: center;
        }
        .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        .icon.success {
            color: #28a745;
        }
        .icon.warning {
            color: #ffc107;
        }
        .icon.error {
            color: #dc3545;
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
            font-size: 28px;
        }
        p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 30px;
            font-size: 16px;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            border: none;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #5568d3;
        }
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        form {
            margin-top: 20px;
        }
        .email-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 600;
            color: #495057;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($unsubscribed): ?>
            <div class="icon success">✓</div>
            <h1>Unsubscribed Successfully</h1>
            <p><?=htmlspecialchars($message, ENT_QUOTES)?></p>
            <p>You will no longer receive newsletters from us.</p>
            <a href="<?=$base_url?>" class="btn">Return to Homepage</a>
            
        <?php elseif ($found_subscriber && !$unsubscribed): ?>
            <div class="icon warning">⚠</div>
            <h1>Unsubscribe from Newsletter</h1>
            <div class="email-info">
                <?=htmlspecialchars($found_subscriber['email'], ENT_QUOTES)?>
            </div>
            <p>Are you sure you want to unsubscribe from our newsletter?</p>
            <p>You will no longer receive updates and promotional emails from us.</p>
            <form method="post">
                <button type="submit" name="confirm_unsubscribe" class="btn btn-danger">Yes, Unsubscribe Me</button>
                <br><br>
                <a href="<?=$base_url?>" class="btn">No, Keep Me Subscribed</a>
            </form>
            
        <?php else: ?>
            <div class="icon error">✗</div>
            <h1>Unable to Unsubscribe</h1>
            <p><?=htmlspecialchars($message, ENT_QUOTES)?></p>
            <a href="<?=$base_url?>" class="btn">Return to Homepage</a>
        <?php endif; ?>
    </div>
</body>
</html>
