<?php
// Knowledge Base - Category View
include_once 'assets/includes/blog-config.php';
include "core.php";

// Use client dashboard page setup
include '../assets/includes/page-setup.php';

$slug = $_GET['name'] ?? '';
if (empty($slug)) {
    header('Location: index.php');
    exit();
}

$stmt = $blog_pdo->prepare("SELECT * FROM categories WHERE slug = ? LIMIT 1");
$stmt->execute([$slug]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    header('Location: index.php');
    exit();
}

$category_id   = $category['id'];
$category_name = $category['category'];
?>

<main id="main" class="main">
    <div class="pagetitle">
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>/index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="index.php">Knowledge Base</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($category_name) ?></li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-folder"></i> <?= htmlspecialchars($category_name) ?></h5>
            </div>
            <div class="card-body">
                <?php
                $postsperpage = 8;
                $pageNum = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
                $offset = ($pageNum - 1) * $postsperpage;

                $stmt = $blog_pdo->prepare("SELECT * FROM posts WHERE category_id = ? AND active = 'Yes' ORDER BY id DESC LIMIT ?, ?");
                $stmt->bindValue(1, $category_id, PDO::PARAM_INT);
                $stmt->bindValue(2, $offset, PDO::PARAM_INT);
                $stmt->bindValue(3, $postsperpage, PDO::PARAM_INT);
                $stmt->execute();
                $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($posts) == 0) {
                    echo '<div class="alert alert-info">There are no published posts in this category.</div>';
                } else {
                    foreach ($posts as $row) {
                        $image_html = "";
                        if (!empty($row['image'])) {
                            $image_html = '<img src="/public_html/' . htmlspecialchars($row['image']) . '" alt="' . htmlspecialchars($row['title']) . '" class="rounded-start" style="width: 100%; height: 100%; object-fit: cover;">';
                        } else {
                            $image_html = '<div style="width: 100%; height: 100%; background: #55595c; display: flex; align-items: center; justify-content: center; color: #eceeef;">No Image</div>';
                        }
                        
                        echo '
                        <div class="card shadow-sm mb-3">
                            <div class="row g-0">
                                <div class="col-md-4" style="height: 200px;">
                                    <a href="post.php?name=' . htmlspecialchars($row['slug']) . '">
                                        ' . $image_html . '
                                    </a>
                                </div>
                                <div class="col-md-8">
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <a href="post.php?name=' . htmlspecialchars($row['slug']) . '" class="text-decoration-none">
                                                ' . htmlspecialchars($row['title']) . '
                                            </a>
                                        </h5>
                                        
                                        <div class="mb-3">
                                            <small class="text-muted">
                                                <i class="bi bi-person"></i> ' . htmlspecialchars(post_author($row['author_id'])) . '
                                                &nbsp;&nbsp;
                                                <i class="bi bi-calendar-event"></i> ' . date($settings['date_format'], strtotime($row['date'])) . ', ' . htmlspecialchars($row['time']) . '
                                            </small>
                                            <small class="text-muted float-end">
                                                <i class="bi bi-chat"></i>
                                                <a href="post.php?name=' . htmlspecialchars($row['slug']) . '#comments">' . post_commentscount($row['id']) . '</a>
                                            </small>
                                        </div>
                                        
                                        <p class="card-text">' . short_text(strip_tags(html_entity_decode($row['content'])), 200) . '</p>
                                    </div>
                                </div>
                            </div>
                        </div>';
                    }
                    
                    // Pagination
                    $count_stmt = $blog_pdo->prepare("SELECT COUNT(id) AS numrows FROM posts WHERE category_id = ? AND active = 'Yes'");
                    $count_stmt->execute([$category_id]);
                    $count_row = $count_stmt->fetch(PDO::FETCH_ASSOC);
                    $numrows = $count_row['numrows'];
                    $maxPage = ceil($numrows / $postsperpage);
                    
                    if ($maxPage > 1) {
                        echo '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
                        
                        // First and Previous
                        if ($pageNum > 1) {
                            echo '<li class="page-item"><a class="page-link" href="?name=' . urlencode($slug) . '&page=1">First</a></li>';
                            echo '<li class="page-item"><a class="page-link" href="?name=' . urlencode($slug) . '&page=' . ($pageNum - 1) . '">Previous</a></li>';
                        }
                        
                        // Page numbers
                        for ($page = 1; $page <= $maxPage; $page++) {
                            if ($page == $pageNum) {
                                echo '<li class="page-item active"><span class="page-link">' . $page . '</span></li>';
                            } else {
                                echo '<li class="page-item"><a class="page-link" href="?name=' . urlencode($slug) . '&page=' . $page . '">' . $page . '</a></li>';
                            }
                        }
                        
                        // Next and Last
                        if ($pageNum < $maxPage) {
                            echo '<li class="page-item"><a class="page-link" href="?name=' . urlencode($slug) . '&page=' . ($pageNum + 1) . '">Next</a></li>';
                            echo '<li class="page-item"><a class="page-link" href="?name=' . urlencode($slug) . '&page=' . $maxPage . '">Last</a></li>';
                        }
                        
                        echo '</ul></nav>';
                    }
                }
                ?>
            </div>
        </div>
    </section>
</main>

<?php include '../assets/includes/footer-close.php'; ?>
