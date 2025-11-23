<?php
// Knowledge Base - Page View
include_once 'assets/includes/blog-config.php';
include "core.php";

// Use client dashboard page setup
include '../assets/includes/page-setup.php';

$slug = $_GET['name'] ?? '';
if (empty($slug)) {
    header('Location: index.php');
    exit();
}

$stmt = $blog_pdo->prepare("SELECT * FROM pages WHERE slug = ? LIMIT 1");
$stmt->execute([$slug]);
$page = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$page) {
    header('Location: index.php');
    exit();
}
?>

<main id="main" class="main">
    <div class="pagetitle">
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>/index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="index.php">Knowledge Base</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($page['title']) ?></li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><?= htmlspecialchars($page['title']) ?></h5>
            </div>
            <div class="card-body">
                <?= html_entity_decode($page['content']) ?>
            </div>
        </div>
    </section>
</main>

<?php include '../assets/includes/footer-close.php'; ?>
