<?php
require 'assets/includes/admin_config.php';

echo "Constants:<br>";
echo "blog_files_url: " . blog_files_url . "<br>";
echo "blog_files_path: " . blog_files_path . "<br><br>";

echo "Database check:<br>";
$stmt = $blog_pdo->query('SELECT id, filename, path FROM files ORDER BY id DESC LIMIT 1');
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    echo "ID: " . $row['id'] . "<br>";
    echo "Filename: " . htmlspecialchars($row['filename']) . "<br>";
    echo "Path in DB: " . htmlspecialchars($row['path']) . "<br><br>";
    
    // Test the conversion logic from files.php
    $file_path = $row['path'];
    echo "Original path: " . htmlspecialchars($file_path) . "<br>";
    
    if (strpos($file_path, '../../') === 0) {
        $file_path = str_replace('../../blog/', blog_uploads_url, $file_path);
        echo "Converted old relative path to: " . htmlspecialchars($file_path) . "<br>";
    }
    
    $server_path = str_replace(blog_files_url, blog_files_path, $file_path);
    echo "Server path: " . htmlspecialchars($server_path) . "<br>";
    echo "File exists: " . (file_exists($server_path) ? 'YES' : 'NO') . "<br>";
    echo "Final web URL that will be used: " . htmlspecialchars($file_path) . "<br>";
}
?>
