<?php
// Knowledge Base - Search
include_once 'assets/includes/blog-config.php';
include "core.php";

// Use client dashboard page setup
include '../assets/includes/page-setup.php';

$search_query = $_GET['q'] ?? '';
?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1><i class="bi bi-search"></i> Search Knowledge Base</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="index.php">Knowledge Base</a></li>
                <li class="breadcrumb-item active">Search</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-8">
                <!-- Search Form -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form action="search.php" method="get">
                            <div class="input-group">
                                <input type="text" name="q" class="form-control" placeholder="Search articles..." value="<?= htmlspecialchars($search_query) ?>" required minlength="2">
                                <button class="btn btn-primary" type="submit">
                                    <i class="bi bi-search"></i> Search
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Search Results -->
                <?php
                if (!empty($search_query)) {
                    if (strlen($search_query) < 2) {
                        echo '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> Enter at least 2 characters to search.</div>';
                    } else {
                        // Get total count
                        $sql = "SELECT COUNT(id) AS numrows FROM posts WHERE active = 'Yes' AND (title LIKE :word OR content LIKE :word)";
                        $stmt = $blog_pdo->prepare($sql);
                        $searchWord = '%' . $search_query . '%';
                        $stmt->execute([':word' => $searchWord]);
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        $numrows = $row['numrows'];
                        
                        if ($numrows == 0) {
                            echo '<div class="alert alert-info"><i class="bi bi-info-circle"></i> No results found for <strong>"' . htmlspecialchars($search_query) . '"</strong></div>';
                        } else {
                            echo '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Found ' . $numrows . ' result(s) for <strong>"' . htmlspecialchars($search_query) . '"</strong></div>';
                            
                            // Pagination
                            $postsperpage = 8;
                            $pageNum = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
                            $rows = ($pageNum - 1) * $postsperpage;
                            
                            // Get posts
                            $sql = "SELECT * FROM posts WHERE active = 'Yes' AND (title LIKE :word OR content LIKE :word) ORDER BY id DESC LIMIT $rows, $postsperpage";
                            $stmt = $blog_pdo->prepare($sql);
                            $stmt->execute([':word' => $searchWord]);
                            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            foreach ($posts as $post) {
                            ?>
                            <div class="card mb-3">
                                <div class="row g-0">
                                    <?php if (!empty($post['image'])): ?>
                                    <div class="col-md-3">
                                        <a href="post.php?name=<?= $post['slug'] ?>">
                                            <img src="<?= BASE_URL . htmlspecialchars($post['image']) ?>" class="img-fluid rounded-start" alt="<?= htmlspecialchars($post['title']) ?>" style="height: 100%; object-fit: cover;">
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="<?= !empty($post['image']) ? 'col-md-9' : 'col-md-12' ?>">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h5 class="card-title mb-0">
                                                    <a href="post.php?name=<?= $post['slug'] ?>"><?= htmlspecialchars($post['title']) ?></a>
                                                </h5>
                                                <a href="category.php?name=<?= post_categoryslug($post['category_id']) ?>">
                                                    <span class="badge bg-secondary"><?= post_category($post['category_id']) ?></span>
                                                </a>
                                            </div>
                                            
                                            <div class="mb-2">
                                                <small class="text-muted">
                                                    <i class="bi bi-person"></i> <?= post_author($post['author_id']) ?>
                                                    <i class="bi bi-calendar ms-2"></i> <?= date($settings['date_format'], strtotime($post['date'])) ?>
                                                    <i class="bi bi-chat ms-2"></i> <a href="post.php?name=<?= $post['slug'] ?>#comments"><?= post_commentscount($post['id']) ?> comments</a>
                                                </small>
                                            </div>
                                            
                                            <p class="card-text"><?= short_text(strip_tags(html_entity_decode($post['content'])), 200) ?></p>
                                            
                                            <a href="post.php?name=<?= $post['slug'] ?>" class="btn btn-sm btn-primary">
                                                Read More <i class="bi bi-arrow-right"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                            }
                            
                            // Pagination
                            $maxPage = ceil($numrows / $postsperpage);
                            if ($maxPage > 1) {
                                echo '<nav aria-label="Search results pagination"><ul class="pagination justify-content-center">';
                                
                                // First and Previous
                                if ($pageNum > 1) {
                                    echo '<li class="page-item"><a class="page-link" href="?q=' . urlencode($search_query) . '&page=1">First</a></li>';
                                    echo '<li class="page-item"><a class="page-link" href="?q=' . urlencode($search_query) . '&page=' . ($pageNum - 1) . '">Previous</a></li>';
                                }
                                
                                // Page numbers
                                for ($page = 1; $page <= $maxPage; $page++) {
                                    if ($page == $pageNum) {
                                        echo '<li class="page-item active"><span class="page-link">' . $page . '</span></li>';
                                    } else {
                                        echo '<li class="page-item"><a class="page-link" href="?q=' . urlencode($search_query) . '&page=' . $page . '">' . $page . '</a></li>';
                                    }
                                }
                                
                                // Next and Last
                                if ($pageNum < $maxPage) {
                                    echo '<li class="page-item"><a class="page-link" href="?q=' . urlencode($search_query) . '&page=' . ($pageNum + 1) . '">Next</a></li>';
                                    echo '<li class="page-item"><a class="page-link" href="?q=' . urlencode($search_query) . '&page=' . $maxPage . '">Last</a></li>';
                                }
                                
                                echo '</ul></nav>';
                            }
                        }
                    }
                } else {
                    echo '<div class="alert alert-info"><i class="bi bi-info-circle"></i> Enter a search term above to find articles.</div>';
                }
                ?>
            </div>
        </div>
            </div>
            <?php sidebar(); ?>
        </div>
    </section>
</main>

<?php include '../assets/includes/footer-close.php'; ?>