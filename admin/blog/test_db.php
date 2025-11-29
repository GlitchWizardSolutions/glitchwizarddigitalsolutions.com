<?php
require '../../../private/config.php';
try {
    $blog_pdo = new PDO('mysql:host=' . db_host . ';dbname=' . db_name12 . ';charset=' . db_charset, db_user, db_pass);
    $blog_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo 'Blog database connection successful<br>';

    $stmt = $blog_pdo->query('SHOW TABLES');
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo 'Tables in blog database: ' . implode(', ', $tables) . '<br>';

    $stmt = $blog_pdo->query('SELECT COUNT(*) as count FROM posts');
    $count = $stmt->fetchColumn();
    echo 'Number of posts in database: ' . $count . '<br>';

    // Check categories
    $stmt = $blog_pdo->query('SELECT COUNT(*) as count FROM categories');
    $cat_count = $stmt->fetchColumn();
    echo 'Number of categories in database: ' . $cat_count . '<br>';

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . '<br>';
}
?>