<?php
require 'assets/includes/admin_config.php';

try {
    echo "=== CATEGORIES TABLE ANALYSIS ===\n\n";

    // Get all categories
    $stmt = $blog_pdo->query('SELECT id, category, slug FROM categories ORDER BY category ASC, id ASC');
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Total categories: " . count($categories) . "\n\n";

    echo "ALL CATEGORIES:\n";
    echo "ID\tCategory\t\tSlug\n";
    echo "--\t--------\t\t----\n";

    foreach ($categories as $cat) {
        echo $cat['id'] . "\t" . $cat['category'] . "\t\t" . $cat['slug'] . "\n";
    }

    echo "\nDUPLICATE ANALYSIS:\n";

    // Check for duplicate names
    $stmt = $blog_pdo->query('
        SELECT category, COUNT(*) as count, GROUP_CONCAT(id ORDER BY id) as ids
        FROM categories
        GROUP BY category
        HAVING COUNT(*) > 1
        ORDER BY count DESC
    ');
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($duplicates)) {
        echo "✅ No duplicate category names found.\n";
    } else {
        echo "❌ Found " . count($duplicates) . " duplicate category names:\n";
        foreach ($duplicates as $dup) {
            echo "  - '" . $dup['category'] . "' appears " . $dup['count'] . " times (IDs: " . $dup['ids'] . ")\n";
        }
    }

    // Check for duplicate slugs
    $stmt = $blog_pdo->query('
        SELECT slug, COUNT(*) as count, GROUP_CONCAT(id ORDER BY id) as ids
        FROM categories
        GROUP BY slug
        HAVING COUNT(*) > 1
        ORDER BY count DESC
    ');
    $slug_duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($slug_duplicates)) {
        echo "✅ No duplicate category slugs found.\n";
    } else {
        echo "❌ Found " . count($slug_duplicates) . " duplicate category slugs:\n";
        foreach ($slug_duplicates as $dup) {
            echo "  - '" . $dup['slug'] . "' appears " . $dup['count'] . " times (IDs: " . $dup['ids'] . ")\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>