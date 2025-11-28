<?php
// Knowledge Base - Homepage
include_once 'assets/includes/blog-config.php';
include "core.php";

// Use client dashboard page setup
include '../assets/includes/page-setup.php';
?>

<main id="main" class="main">
    <div class="pagetitle">
        
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>/index.php">&nbsp;Home</a></li>
                <li class="breadcrumb-item active">Knowledge Base</li>
                <li class="breadcrumb-item active">Recent Articles</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <?php
        // Featured posts carousel
        $stmt = $blog_pdo->query("SELECT * FROM posts WHERE active='Yes' AND featured='Yes' ORDER BY id DESC");
        $featured = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($featured) > 0) {
        ?>
        <div id="featuredCarousel" class="carousel slide mb-4" data-bs-ride="carousel">
            <div class="carousel-indicators">
                <?php foreach ($featured as $i => $post): ?>
                <button type="button" data-bs-target="#featuredCarousel" data-bs-slide-to="<?= $i ?>" 
                    <?= $i === 0 ? 'class="active" aria-current="true"' : '' ?> 
                    aria-label="<?= htmlspecialchars($post['title']) ?>"></button>
                <?php endforeach; ?>
            </div>
            <div class="carousel-inner rounded">
                <?php foreach ($featured as $i => $post): ?>
                <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                    <?php if ($post['image']): ?>
                    <a href="post.php?name=<?= $post['slug'] ?>">
                        <img src="/public_html/<?= $post['image'] ?>" alt="<?= htmlspecialchars($post['title']) ?>" 
                             class="d-block w-100" style="height: 400px; object-fit: contain; background-color: #f8f9fa;">
                    </a>
                    <?php else: ?>
                    <div style="height: 400px; background: #555; display: flex; align-items: center; justify-content: center;">
                        <span class="text-light">No Image</span>
                    </div>
                    <?php endif; ?>
                    <div class="carousel-caption d-md-block" style="background: rgba(0, 0, 0, 0.6); padding: 15px; border-radius: 8px; left: 50%; right: auto; transform: translateX(-50%); bottom: 20px; max-width: 80%;">
                        <h5>
                            <a href="post.php?name=<?= $post['slug'] ?>" class="text-light text-decoration-none">
                                <?= htmlspecialchars($post['title']) ?>
                            </a>
                        </h5>
                        <p class="text-light mb-0">
                            <i class="bi bi-calendar-event"></i> <?= date($settings['date_format'], strtotime($post['date'])) ?>
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#featuredCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#featuredCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
        <?php } ?>

        <!-- Recent Posts -->
        <div class="row">
           
            <?php
            $stmt = $blog_pdo->query("SELECT * FROM posts WHERE active='Yes' ORDER BY id DESC LIMIT 8");
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($posts) === 0) {
                echo '<div class="col-12"><div class="alert alert-info">No published posts yet.</div></div>';
            } else {
                foreach ($posts as $post):
            ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <?php if ($post['image']): ?>
                    <a href="post.php?name=<?= $post['slug'] ?>">
                        <img src="/public_html/<?= $post['image'] ?>" alt="<?= htmlspecialchars($post['title']) ?>" 
                             class="card-img-top" style="height: 200px; object-fit: contain; background-color: #f8f9fa;">
                    </a>
                    <?php else: ?>
                    <div style="height: 200px; background: #55595c; display: flex; align-items: center; justify-content: center;">
                        <span class="text-light">No Image</span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="card-body d-flex flex-column">
                        <h6 class="card-title">
                            <a href="post.php?name=<?= $post['slug'] ?>" class="text-decoration-none">
                                <?= htmlspecialchars($post['title']) ?>
                            </a>
                        </h6>
                        
                        <div class="mb-2">
                            <small class="text-muted">
                                <i class="bi bi-calendar"></i> <?= date($settings['date_format'], strtotime($post['date'])) ?>
                            </small>
                            <small class="text-muted ms-2">
                                <i class="bi bi-chat"></i> <?= post_commentscount($post['id']) ?>
                            </small>
                        </div>
                        
                        <div class="mb-2">
                            <a href="category.php?name=<?= post_categoryslug($post['category_id']) ?>">
                                <span class="badge bg-secondary"><?= post_category($post['category_id']) ?></span>
                            </a>
                        </div>
                        
                        <p class="card-text flex-grow-1">
                            <?= short_text(strip_tags(html_entity_decode($post['content'])), 100) ?>
                        </p>
                        
                        <a href="post.php?name=<?= $post['slug'] ?>" class="btn btn-sm btn-primary mt-auto">
                            Read more <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php 
                endforeach;
            }
            ?>
        </div>

        <div class="row mt-3">
            <div class="col-12">
                <a href="blog.php" class="btn btn-primary">
                    <i class="bi bi-arrow-right-circle"></i> View All Articles
                </a>
            </div>
        </div>
    </section>
</main>

<?php include '../assets/includes/footer-close.php'; ?>