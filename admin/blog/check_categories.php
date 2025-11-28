<?php
require 'assets/includes/admin_config.php';

echo "Categories in database:\n";
echo "ID | Category | Slug\n";
echo "---|----------|------\n";

$stmt = $blog_pdo->query('SELECT id, category, slug FROM categories ORDER BY category ASC');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['id'] . ' | ' . $row['category'] . ' | ' . $row['slug'] . "\n";
}

// Check for duplicates
echo "\n\nDuplicate category names:\n";
$stmt = $blog_pdo->query('
    SELECT category, COUNT(*) as count
    FROM categories
    GROUP BY category
    HAVING COUNT(*) > 1
    ORDER BY count DESC
');
$duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($duplicates)) {
    echo "No duplicate category names found.\n";
} else {
    foreach ($duplicates as $dup) {
        echo $dup['category'] . ' (appears ' . $dup['count'] . ' times)' . "\n";
    }
}
?>