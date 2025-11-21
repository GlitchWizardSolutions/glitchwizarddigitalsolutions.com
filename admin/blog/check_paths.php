<?php
// Diagnostic script to check current image paths in database
require 'assets/includes/admin_config.php';

echo "<h3>Current Image Paths in Database:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Title</th><th>Image Path</th><th>File Exists?</th></tr>";

$stmt = $blog_pdo->prepare("SELECT id, title, image FROM posts WHERE image != '' ORDER BY id DESC LIMIT 10");
$stmt->execute();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Check both with and without /public_html/ prefix
    $imagePath1 = $_SERVER['DOCUMENT_ROOT'] . $row['image'];
    $imagePath2 = $_SERVER['DOCUMENT_ROOT'] . '/public_html' . $row['image'];
    
    $exists1 = file_exists($imagePath1) ? '✅' : '❌';
    $exists2 = file_exists($imagePath2) ? '✅' : '❌';
    
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['title']) . "</td>";
    echo "<td>" . htmlspecialchars($row['image']) . "</td>";
    echo "<td>";
    echo $exists1 . " " . $imagePath1 . "<br>";
    echo $exists2 . " (with /public_html/) " . $imagePath2;
    echo "</td>";
    echo "</tr>";
}

echo "</table>";
echo "<br><br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'];
echo "<br>Looking for file: " . $_SERVER['DOCUMENT_ROOT'] . "/client-dashboard/blog/uploads/posts/image_y792kdhew84suf6510jt3gr.png";
echo "<br>File exists? " . (file_exists($_SERVER['DOCUMENT_ROOT'] . "/client-dashboard/blog/uploads/posts/image_y792kdhew84suf6510jt3gr.png") ? 'YES' : 'NO');
?>
