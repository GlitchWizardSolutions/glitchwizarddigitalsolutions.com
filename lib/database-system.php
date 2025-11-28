<?php
/**
 * Unified Database Connection System
 * 
 * Centralizes all database connections across the entire application.
 * Provides consistent error handling and connection pooling.
 * 
 * @version 1.0.0
 * @date 2025-11-20
 */

// Prevent direct access
if (!defined('db_host')) {
    die('Configuration not loaded. Include config.php first.');
}

/**
 * Database connection pool to prevent duplicate connections
 */
class DatabasePool {
    private static $connections = [];
    
    /**
     * Get or create a database connection
     * 
     * @param string $key Unique identifier for this connection
     * @param string $dbname Database name
     * @param string $user Database user (optional, uses db_user if not provided)
     * @param string $pass Database password (optional, uses db_pass if not provided)
     * @return PDO Database connection
     * @throws Exception If connection fails
     */
    public static function getConnection($key, $dbname, $user = null, $pass = null) {
        if (!isset(self::$connections[$key])) {
            $user = $user ?? db_user;
            $pass = $pass ?? db_pass;
            
            try {
                $pdo = new PDO(
                    'mysql:host=' . db_host . ';dbname=' . $dbname . ';charset=' . db_charset,
                    $user,
                    $pass
                );
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                // Ensure the connection uses the configured charset at the client level
                try {
                    $pdo->exec("SET NAMES '" . db_charset . "'");
                } catch (Exception $e) {
                    // If SET NAMES fails, log and continue; some environments don't allow this command
                    critical_log('Database System', 'database-system.php', 'Connection Setup', 'Warning: SET NAMES failed: ' . $e->getMessage());
                }
                self::$connections[$key] = $pdo;
            } catch (PDOException $exception) {
                critical_log('Database System', 'database-system.php', 'Connection Setup', "Failed to connect to database '$dbname': " . $exception->getMessage());
                throw new Exception("Database connection failed. Please try again later.");
            }
        }
        
        // Connection must exist at this point (either pre-existing or just created)
        return self::$connections[$key];
    }
    
    /**
     * Close a specific connection
     */
    public static function closeConnection($key) {
        if (isset(self::$connections[$key])) {
            self::$connections[$key] = null;
            unset(self::$connections[$key]);
        }
    }
    
    /**
     * Close all connections
     */
    public static function closeAll() {
        foreach (self::$connections as $key => $conn) {
            self::$connections[$key] = null;
        }
        self::$connections = [];
    }
}

/**
 * Get main login/accounts database connection
 * Database: glitchwizarddigi_login_db
 * Contains: accounts, login_attempts, etc.
 * 
 * @return PDO Database connection
 */
function get_accounts_db() {
    return DatabasePool::getConnection('accounts', db_name);
}

/**
 * Get OnTheGo resources database connection
 * Database: glitchwizarddigi_onthego
 * Contains: medications, resources, etc.
 * 
 * @return PDO Database connection
 */
function get_onthego_db() {
    if (!defined('db_name2')) {
        throw new Exception('OnTheGo database not configured');
    }
    return DatabasePool::getConnection('onthego', db_name2);
}

/**
 * Get budget database connection
 * Database: glitchwizarddigi_budget_2025
 * Contains: bills, transactions, etc.
 * 
 * @return PDO Database connection
 */
function get_budget_db() {
    if (!defined('db_name7')) {
        throw new Exception('Budget database not configured');
    }
    return DatabasePool::getConnection('budget', db_name7);
}

/**
 * Get error handling database connection
 * Database: glitchwi_error_handling_db
 * Contains: error logs
 * 
 * @return PDO Database connection
 */
function get_error_db() {
    if (!defined('db_name9')) {
        throw new Exception('Error handling database not configured');
    }
    $user = defined('db_user9') ? db_user9 : db_user;
    return DatabasePool::getConnection('errors', db_name9, $user);
}

/**
 * Get blog database connection
 * Database: glitchwizarddigi_envato_blog_db
 * Contains: blog posts, comments, etc.
 * 
 * @return PDO Database connection
 */
function get_blog_db() {
    if (!defined('db_name12')) {
        throw new Exception('Blog database not configured');
    }
    return DatabasePool::getConnection('blog', db_name12);
}

/**
 * Get demo database connection (if enabled)
 * Database: glitchwizarddigi_demo_db
 * 
 * @return PDO Database connection
 */
function get_demo_db() {
    if (!defined('db_name3')) {
        throw new Exception('Demo database not configured');
    }
    return DatabasePool::getConnection('demo', db_name3);
}

/**
 * Get accounting database connection (if enabled)
 * Database: glitchwizarddigi_accounting
 * 
 * @return PDO Database connection
 */
function get_accounting_db() {
    if (!defined('db_name4')) {
        throw new Exception('Accounting database not configured');
    }
    return DatabasePool::getConnection('accounting', db_name4);
}

/**
 * Get invoice system database connection (if enabled)
 * Database: glitchwizarddigi_invoice_system
 * 
 * @return PDO Database connection
 */
function get_invoice_db() {
    if (!defined('db_name5')) {
        throw new Exception('Invoice database not configured');
    }
    return DatabasePool::getConnection('invoice', db_name5);
}

/**
 * Get developer database connection (if enabled)
 * Database: glitchwizarddigi_developer
 * 
 * @return PDO Database connection
 */
function get_developer_db() {
    if (!defined('db_name6')) {
        throw new Exception('Developer database not configured');
    }
    return DatabasePool::getConnection('developer', db_name6);
}

/**
 * Get reminder system database connection (if enabled)
 * Database: glitchwizarddigi_reminder_system
 * 
 * @return PDO Database connection
 */
function get_reminder_db() {
    if (!defined('db_name8')) {
        throw new Exception('Reminder database not configured');
    }
    return DatabasePool::getConnection('reminder', db_name8);
}

/**
 * LEGACY COMPATIBILITY FUNCTIONS
 * These maintain backward compatibility with existing code
 */

/**
 * Legacy: Connect to MySQL database (main accounts database)
 * @deprecated Use get_accounts_db() instead
 */
function pdo_connect_mysql() {
    return get_accounts_db();
}

/**
 * Legacy: Connect to budget database
 * @deprecated Use get_budget_db() instead
 */
function pdo_connect_budget_db($host = null, $dbname = null, $user = null, $password = null) {
    // If custom parameters provided, create new connection
    if ($host !== null && $dbname !== null) {
        $user = $user ?? db_user;
        $password = $password ?? db_pass;
        
        try {
            $pdo = new PDO(
                'mysql:host=' . $host . ';dbname=' . $dbname . ';charset=utf8',
                $user,
                $password
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $exception) {
            critical_log('Database System', 'database-system.php', 'Budget Database Connection', "Failed to connect to budget database: " . $exception->getMessage());
            exit('Failed to connect to database!' . $exception->getMessage());
        }
    }
    
    // Use pooled connection
    return get_budget_db();
}

/**
 * Legacy: Connect to blog database
 * @deprecated Use get_blog_db() instead
 */
function pdo_connect_blog_db() {
    return get_blog_db();
}

/**
 * Get database name for a specific database type
 * Useful for queries that need to reference database name
 * 
 * @param string $type Database type (accounts, onthego, budget, etc.)
 * @return string Database name
 */
function get_db_name($type) {
    $map = [
        'accounts' => db_name,
        'login' => db_name,
        'main' => db_name,
        'onthego' => defined('db_name2') ? db_name2 : null,
        'demo' => defined('db_name3') ? db_name3 : null,
        'accounting' => defined('db_name4') ? db_name4 : null,
        'invoice' => defined('db_name5') ? db_name5 : null,
        'developer' => defined('db_name6') ? db_name6 : null,
        'budget' => defined('db_name7') ? db_name7 : null,
        'reminder' => defined('db_name8') ? db_name8 : null,
        'error' => defined('db_name9') ? db_name9 : null,
        'blog' => defined('db_name12') ? db_name12 : null,
    ];
    
    if (!isset($map[$type]) || $map[$type] === null) {
        throw new Exception("Unknown or unconfigured database type: $type");
    }
    
    return $map[$type];
}

/**
 * Check if a database is configured
 * 
 * @param string $type Database type
 * @return bool True if database is configured
 */
function is_db_configured($type) {
    try {
        get_db_name($type);
        return true;
    } catch (Exception $e) {
        return false;
    }
}
