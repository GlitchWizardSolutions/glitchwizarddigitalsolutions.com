<?php
require_once '../private/blog_config2025.php';

// Create blog database connection
try {
    $blog_pdo = new PDO('mysql:host=' . db_host . ';dbname=' . db_name12 . ';charset=' . db_charset, db_user, db_pass);
    $blog_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

echo "=== BLOG POSTS ===\n\n";
$stmt = $blog_pdo->query("SELECT id, title, slug, content FROM posts ORDER BY id");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']}\n";
    echo "Title: {$row['title']}\n";
    echo "Slug: {$row['slug']}\n";
    echo "Content Length: " . strlen($row['content']) . " chars\n";
    
    // Check for image references
    if (preg_match_all('/kb-[a-z0-9\-]+\.png/', $row['content'], $matches)) {
        echo "Images referenced: " . implode(', ', array_unique($matches[0])) . "\n";
    }
    
    // Check for img tags
    if (preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/', $row['content'], $matches)) {
        echo "IMG src paths:\n";
        foreach ($matches[1] as $src) {
            echo "  - $src\n";
        }
    }
    
    echo "\n" . str_repeat('-', 80) . "\n\n";
}

echo "\n=== BLOG CATEGORIES ===\n\n";
$stmt = $blog_pdo->query("SELECT * FROM categories ORDER BY position");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']}, Name: {$row['name']}, Slug: {$row['slug']}\n";
}

echo "\n=== CONFIGURATION ===\n\n";
echo "BASE_URL: " . BASE_URL . "\n";
echo "blog_site_url: " . blog_site_url . "\n";
echo "blog_uploads_path: " . blog_uploads_path . "\n";
echo "blog_uploads_url: " . blog_uploads_url . "\n";
echo "ENVIRONMENT: " . (defined('ENVIRONMENT') ? ENVIRONMENT : 'not defined') . "\n";
?>
