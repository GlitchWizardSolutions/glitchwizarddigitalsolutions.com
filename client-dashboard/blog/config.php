<?php
// Load centralized configuration for secure credentials
if (file_exists(__DIR__ . '/../../../private/config.php')) {
    require_once __DIR__ . '/../../../private/config.php';
}

/*******************************************************************************
 * ENVIRONMENT AUTO-DETECTION
 * Matches invoice_system_config.php environment detection
 ******************************************************************************/
if (!defined('ENVIRONMENT')) {
    $is_local = in_array($_SERVER['HTTP_HOST'] ?? '', [
        'localhost',
        '127.0.0.1',
        'localhost:3000',
        'localhost:8080',
        '::1'
    ]) || strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost:') === 0;
    
    define('ENVIRONMENT', $is_local ? 'development' : 'production');
}

// Database configuration based on environment
if (ENVIRONMENT === 'development') {
    // Local development
    $host     = "127.0.0.1";
    $port     = "3307";
    $user     = "root";
    $password = defined('db_pass') ? db_pass : ""; // From private/config.php
    $database = "glitchwizarddigi_envato_blog_db";
} else {
    // Production
    $host     = "localhost";
    $port     = "3306";
    $user     = "glitchwizarddigi_webdev";
    $password = defined('db_pass') ? db_pass : ""; // From private/config.php
    $database = "glitchwizarddigi_envato_blog_db";
}

// Create PDO connection for blog system
try {
    $blog_pdo = new PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4", $user, $password);
    $blog_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $blog_pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please check your configuration.");
}

// Settings
include "config_settings.php";
include "functions.php";