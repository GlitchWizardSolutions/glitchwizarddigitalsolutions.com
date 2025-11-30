<?php
/**
 * Blog Image Path Fixer
 * 
 * Fixes all image paths in the database to be relative (without leading /)
 * so templates can prepend BASE_URL for environment-aware paths.
 * 
 * Run from: http://localhost:3000/public_html/fix-blog-paths.php
 * Or CLI: php fix-blog-paths.php
 */

require_once '../private/blog_config2025.php';

// Prevent running in production accidentally
if (ENVIRONMENT !== 'development') {
    die('ERROR: This script can only be run in DEVELOPMENT environment for safety!');
}

// Create blog database connection
try {
    $blog_pdo = new PDO('mysql:host=' . db_host . ';dbname=' . db_name12 . ';charset=' . db_charset, db_user, db_pass);
    $blog_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

echo "==========================================\n";
echo "Blog Image Path Fixer\n";
echo "==========================================\n\n";

echo "Environment: " . ENVIRONMENT . "\n";
echo "BASE_URL: " . BASE_URL . "\n";
echo "Database: " . db_name12 . "\n\n";

// ============================================================================
// STEP 1: Preview Changes
// ============================================================================

echo "STEP 1: Previewing Changes\n";
echo "-------------------------------------------\n";

// Preview featured image changes
$stmt = $blog_pdo->query("
    SELECT 
        id,
        title,
        image AS old_path,
        TRIM(LEADING '/' FROM image) AS new_path
    FROM posts 
    WHERE image LIKE '/client-dashboard/%'
       OR image LIKE '/public_html/%'
");

$featured_count = $stmt->rowCount();
echo "\nFeatured Images to Fix: $featured_count\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  Post #{$row['id']}: {$row['title']}\n";
    echo "    OLD: {$row['old_path']}\n";
    echo "    NEW: {$row['new_path']}\n";
}

// Preview content image changes
$stmt = $blog_pdo->query("
    SELECT id, title, content
    FROM posts
    WHERE content LIKE '%src=\"/client-dashboard/%'
       OR content LIKE '%src=\"/public_html/%'
");

$content_count = $stmt->rowCount();
echo "\nPosts with Embedded Images to Fix: $content_count\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  Post #{$row['id']}: {$row['title']}\n";
    
    // Count how many images need fixing
    $img_count_slash = substr_count($row['content'], 'src="/client-dashboard/');
    $img_count_public = substr_count($row['content'], 'src="/public_html/');
    $total_imgs = $img_count_slash + $img_count_public;
    
    echo "    Images to fix: $total_imgs\n";
}

echo "\n";

// ============================================================================
// STEP 2: Ask for Confirmation
// ============================================================================

if (php_sapi_name() === 'cli') {
    echo "Do you want to apply these changes? (yes/no): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    $confirm = trim(strtolower($line));
    fclose($handle);
} else {
    // Web interface
    if (!isset($_POST['confirm'])) {
        echo "<form method='post'>";
        echo "<input type='hidden' name='confirm' value='yes'>";
        echo "<button type='submit' style='background: #28a745; color: white; padding: 10px 20px; border: none; cursor: pointer; font-size: 16px;'>Apply Fixes</button>";
        echo "</form>";
        echo "<p><strong>Note:</strong> Backup your database before proceeding!</p>";
        exit;
    }
    $confirm = 'yes';
}

if ($confirm !== 'yes') {
    die("Aborted. No changes made.\n");
}

// ============================================================================
// STEP 3: Apply Fixes
// ============================================================================

echo "\nSTEP 2: Applying Fixes\n";
echo "-------------------------------------------\n";

try {
    $blog_pdo->beginTransaction();
    
    // Fix 1: Featured images
    $stmt = $blog_pdo->exec("
        UPDATE posts 
        SET image = TRIM(LEADING '/' FROM image)
        WHERE image LIKE '/client-dashboard/%'
           OR image LIKE '/public_html/%'
    ");
    echo "✓ Fixed $stmt featured image paths\n";
    
    // Fix 2: Embedded images - /client-dashboard/
    $stmt = $blog_pdo->exec("
        UPDATE posts
        SET content = REPLACE(content, 'src=\"/client-dashboard/', 'src=\"client-dashboard/')
        WHERE content LIKE '%src=\"/client-dashboard/%'
    ");
    echo "✓ Fixed src=\"/client-dashboard/ in $stmt posts\n";
    
    // Fix 3: Embedded images - /public_html/client-dashboard/
    $stmt = $blog_pdo->exec("
        UPDATE posts
        SET content = REPLACE(content, 'src=\"/public_html/client-dashboard/', 'src=\"client-dashboard/')
        WHERE content LIKE '%src=\"/public_html/client-dashboard/%'
    ");
    echo "✓ Fixed src=\"/public_html/client-dashboard/ in $stmt posts\n";
    
    // Fix 4: Embedded images - /public_html//client-dashboard/ (double slash)
    $stmt = $blog_pdo->exec("
        UPDATE posts
        SET content = REPLACE(content, 'src=\"/public_html//client-dashboard/', 'src=\"client-dashboard/')
        WHERE content LIKE '%src=\"/public_html//client-dashboard/%'
    ");
    echo "✓ Fixed double slash paths in $stmt posts\n";
    
    // Fix 5: Gallery images
    $tables = $blog_pdo->query("SHOW TABLES LIKE 'gallery'")->fetchAll();
    if (count($tables) > 0) {
        $stmt = $blog_pdo->exec("
            UPDATE gallery
            SET image = TRIM(LEADING '/' FROM image)
            WHERE image LIKE '/client-dashboard/%'
               OR image LIKE '/public_html/%'
        ");
        echo "✓ Fixed $stmt gallery image paths\n";
    } else {
        echo "ℹ Gallery table not found, skipping\n";
    }
    
    $blog_pdo->commit();
    echo "\n✅ All fixes applied successfully!\n\n";
    
} catch (Exception $e) {
    $blog_pdo->rollBack();
    die("ERROR: " . $e->getMessage() . "\n");
}

// ============================================================================
// STEP 4: Verification
// ============================================================================

echo "STEP 3: Verification\n";
echo "-------------------------------------------\n";

// Check featured images
$stmt = $blog_pdo->query("
    SELECT id, title, image 
    FROM posts 
    WHERE image IS NOT NULL AND image != ''
    ORDER BY id
");

echo "\nFeatured Images (all should start without /):\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $status = (strpos($row['image'], '/') === 0) ? '❌ STILL HAS /' : '✓';
    echo "  $status Post #{$row['id']}: {$row['image']}\n";
}

// Check for remaining issues
$stmt = $blog_pdo->query("
    SELECT 
        SUM(CASE WHEN content LIKE '%src=\"/public_html/%' THEN 1 ELSE 0 END) AS has_public_html,
        SUM(CASE WHEN content LIKE '%src=\"/client-dashboard/%' THEN 1 ELSE 0 END) AS has_slash_client,
        SUM(CASE WHEN content LIKE '%src=\"client-dashboard/%' THEN 1 ELSE 0 END) AS has_relative_path,
        COUNT(*) AS total_posts
    FROM posts
");

$stats = $stmt->fetch(PDO::FETCH_ASSOC);
echo "\nEmbedded Image Statistics:\n";
echo "  Posts with src=\"/public_html/: {$stats['has_public_html']} (should be 0)\n";
echo "  Posts with src=\"/client-dashboard/: {$stats['has_slash_client']} (should be 0)\n";
echo "  Posts with src=\"client-dashboard/: {$stats['has_relative_path']} (correct!)\n";
echo "  Total posts: {$stats['total_posts']}\n";

if ($stats['has_public_html'] == 0 && $stats['has_slash_client'] == 0) {
    echo "\n✅ SUCCESS! All paths are now relative.\n";
} else {
    echo "\n⚠️ WARNING: Some paths still need fixing.\n";
}

echo "\n==========================================\n";
echo "NEXT STEPS:\n";
echo "1. Test blog pages in development\n";
echo "2. Verify images display correctly\n";
echo "3. Deploy to production\n";
echo "4. Test images in production\n";
echo "==========================================\n";
?>
