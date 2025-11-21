<?php
// One-time script to fix image paths in the database
// Run this once, then delete this file

require 'assets/includes/admin_config.php';

// Fix paths that start with '/client-dashboard/' to '/public_html/client-dashboard/'
$stmt1 = $blog_pdo->prepare("UPDATE posts SET image = CONCAT('/public_html', image) WHERE image LIKE '/client-dashboard/blog/uploads/posts/%'");
$result1 = $stmt1->execute();
$count1 = $stmt1->rowCount();

echo "Fixed $count1 post image paths to include /public_html/ prefix<br>";
echo "<br><strong>You can now delete this file (fix_image_paths.php)</strong>";
?>
