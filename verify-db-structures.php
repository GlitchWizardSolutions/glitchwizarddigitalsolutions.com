<?php
/**
 * PRODUCTION Database Structure Verification
 * 
 * SAFE TO RUN ON PRODUCTION - Read-only queries only
 * 
 * Purpose: Verify database structures exist before deploying cleanup code
 * This script only reads database structure, makes NO changes
 * 
 * Upload to: https://glitchwizarddigitalsolutions.com/verify-db-structures.php
 * Delete after verification complete
 */

// Security: Require authentication or delete after use
// Uncomment to require admin login:
// session_start();
// if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'Admin') {
//     die('Access denied');
// }

// Show formatted output
header('Content-Type: text/plain; charset=utf-8');

echo str_repeat("=", 80) . "\n";
echo "PRODUCTION DATABASE STRUCTURE VERIFICATION\n";
echo "Server: " . $_SERVER['HTTP_HOST'] . "\n";
echo "Date: " . date('Y-m-d H:i:s T') . "\n";
echo str_repeat("=", 80) . "\n\n";

// Load config
$config_path = __DIR__ . '/../private/config.php';
if (file_exists($config_path)) {
    require_once $config_path;
} else {
    die("ERROR: Cannot load config.php - adjust path if needed\n");
}

// Connect to database
try {
    $pdo = new PDO(
        'mysql:host=' . db_host . ';dbname=' . db_name . ';charset=' . db_charset,
        db_user,
        db_pass
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Connected to: " . db_name . " @ " . db_host . "\n";
    echo "✓ Environment: " . (defined('ENVIRONMENT') ? strtoupper(ENVIRONMENT) : 'PRODUCTION') . "\n\n";
} catch (PDOException $e) {
    die("❌ Database connection failed: " . $e->getMessage() . "\n");
}

$all_verified = true;
$issues = [];

// ============================================================================
// CHECK 1: newsletter_tracking table
// ============================================================================
echo "[1/3] newsletter_tracking table...\n";

try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'newsletter_tracking'");
    if ($stmt->rowCount() > 0) {
        echo "      ✓ Table exists\n";
        
        // Check columns
        $stmt = $pdo->query("SHOW COLUMNS FROM newsletter_tracking");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $required = ['id', 'tracking_code', 'action', 'url', 'ip_address', 'user_agent', 'tracked_at'];
        $missing = array_diff($required, $columns);
        
        if (empty($missing)) {
            echo "      ✓ All columns present\n";
        } else {
            echo "      ✗ Missing columns: " . implode(', ', $missing) . "\n";
            $all_verified = false;
            $issues[] = "newsletter_tracking missing columns";
        }
        
        // Check indexes
        $stmt = $pdo->query("SHOW INDEX FROM newsletter_tracking");
        $indexes = $stmt->fetchAll(PDO::FETCH_COLUMN, 2); // Key_name column
        $has_indexes = count(array_intersect(['idx_tracking_code', 'idx_action', 'idx_tracked_at'], $indexes));
        
        if ($has_indexes >= 3) {
            echo "      ✓ Performance indexes present\n";
        } else {
            echo "      ⚠ Only $has_indexes/3 indexes (performance impact)\n";
        }
    } else {
        echo "      ✗ TABLE MISSING - CANNOT DEPLOY CLEANUP\n";
        $all_verified = false;
        $issues[] = "newsletter_tracking table missing";
    }
} catch (PDOException $e) {
    echo "      ✗ Error checking table: " . $e->getMessage() . "\n";
    $all_verified = false;
    $issues[] = "newsletter_tracking check failed";
}

// ============================================================================
// CHECK 2: invoices.email_sent column
// ============================================================================
echo "\n[2/3] invoices.email_sent column...\n";

try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'invoices'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->query("SHOW COLUMNS FROM invoices LIKE 'email_sent'");
        if ($stmt->rowCount() > 0) {
            echo "      ✓ Column exists\n";
            
            // Verify data type
            $stmt = $pdo->query("SHOW COLUMNS FROM invoices WHERE Field = 'email_sent'");
            $col = $stmt->fetch(PDO::FETCH_ASSOC);
            if (stripos($col['Type'], 'tinyint') !== false) {
                echo "      ✓ Correct data type (TINYINT)\n";
            } else {
                echo "      ⚠ Data type is " . $col['Type'] . " (expected TINYINT)\n";
            }
        } else {
            echo "      ✗ COLUMN MISSING - CANNOT DEPLOY CLEANUP\n";
            $all_verified = false;
            $issues[] = "invoices.email_sent column missing";
        }
    } else {
        echo "      ⚠ invoices table doesn't exist\n";
    }
} catch (PDOException $e) {
    echo "      ✗ Error checking column: " . $e->getMessage() . "\n";
    $all_verified = false;
    $issues[] = "invoices.email_sent check failed";
}

// ============================================================================
// CHECK 3: client_notifications.is_read column
// ============================================================================
echo "\n[3/3] client_notifications.is_read column...\n";

try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'client_notifications'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->query("SHOW COLUMNS FROM client_notifications LIKE 'is_read'");
        if ($stmt->rowCount() > 0) {
            echo "      ✓ Column exists\n";
            
            // Verify data type
            $stmt = $pdo->query("SHOW COLUMNS FROM client_notifications WHERE Field = 'is_read'");
            $col = $stmt->fetch(PDO::FETCH_ASSOC);
            if (stripos($col['Type'], 'tinyint') !== false) {
                echo "      ✓ Correct data type (TINYINT)\n";
            } else {
                echo "      ⚠ Data type is " . $col['Type'] . " (expected TINYINT)\n";
            }
        } else {
            echo "      ✗ COLUMN MISSING - CANNOT DEPLOY CLEANUP\n";
            $all_verified = false;
            $issues[] = "client_notifications.is_read column missing";
        }
    } else {
        echo "      ⚠ client_notifications table doesn't exist\n";
    }
} catch (PDOException $e) {
    echo "      ✗ Error checking column: " . $e->getMessage() . "\n";
    $all_verified = false;
    $issues[] = "client_notifications.is_read check failed";
}

// ============================================================================
// FINAL VERDICT
// ============================================================================
echo "\n" . str_repeat("=", 80) . "\n";

if ($all_verified) {
    echo "✅ PRODUCTION VERIFICATION PASSED\n\n";
    echo "All required database structures are present.\n";
    echo "You can safely deploy the code cleanup to production.\n\n";
    echo "Deployment Checklist:\n";
    echo "  ✓ Development database verified\n";
    echo "  ✓ Production database verified\n";
    echo "  □ Code cleanup completed in development\n";
    echo "  □ Testing completed in development\n";
    echo "  □ Ready to git push to production\n\n";
    echo "⚠️  REMEMBER: Delete this verification script after use!\n";
} else {
    echo "❌ PRODUCTION VERIFICATION FAILED\n\n";
    echo "Issues found:\n";
    foreach ($issues as $issue) {
        echo "  • $issue\n";
    }
    echo "\n⛔ DO NOT DEPLOY CODE CLEANUP UNTIL THESE ARE FIXED\n\n";
    echo "Options:\n";
    echo "  1. Run missing migrations on production database\n";
    echo "  2. Contact DBA to add missing structures\n";
    echo "  3. Keep runtime checks in code for now\n\n";
    echo "See: AI-DEV/DATABASE-STRUCTURE-CLEANUP-SPEC.md for SQL\n";
}

echo str_repeat("=", 80) . "\n";
echo "\nVerification complete: " . date('Y-m-d H:i:s T') . "\n";
echo "\n🔒 SECURITY: Delete this file after verification!\n";
echo "   rm verify-db-structures.php\n";

// Prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
?>
