<?php
// Simple test to check if basic PHP and database work
require 'assets/includes/admin_config.php';

echo '<h1>Basic Test</h1>';

if (isset($blog_pdo)) {
    echo '<p>Database connection: OK</p>';
    try {
        $stmt = $blog_pdo->query('SELECT COUNT(*) as count FROM posts');
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo '<p>Posts table accessible. Current count: ' . $result['count'] . '</p>';
    } catch (Exception $e) {
        echo '<p>Database error: ' . $e->getMessage() . '</p>';
    }
} else {
    echo '<p>Database connection: FAILED</p>';
}

echo '<h2>Test Form</h2>';
if (isset($_POST['test'])) {
    echo '<h3>Form Submitted!</h3>';
    echo '<p>Title: ' . htmlspecialchars($_POST['title'] ?? '') . '</p>';
    echo '<p>Content: ' . htmlspecialchars($_POST['content'] ?? '') . '</p>';
}

echo '<form method="post">';
echo '<input type="text" name="title" placeholder="Title"><br>';
echo '<textarea name="content" placeholder="Content"></textarea><br>';
echo '<input type="submit" name="test" value="Test Submit">';
echo '</form>';
?>