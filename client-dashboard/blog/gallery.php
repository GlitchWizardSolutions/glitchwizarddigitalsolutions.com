<?php
// Knowledge Base - Gallery
include 'assets/includes/blog-config.php';
include "core.php";

// Use client dashboard page setup
include '../assets/includes/page-setup.php';
?>

<main id="main" class="main">
    <div class="pagetitle">
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>/index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="index.php">Knowledge Base</a></li>
                <li class="breadcrumb-item active">Gallery</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-images"></i> Gallery</h5>
            </div>
            <div class="card-body">
                <nav>
                    <div class="nav nav-pills nav-fill" id="nav-tab" role="tablist">
                        <button class="nav-link active" id="nav-all-tab" data-bs-toggle="tab" data-bs-target="#nav-all" type="button" role="tab" aria-controls="nav-all" aria-selected="true">
                            <i class="bi bi-grid-3x3"></i> All
                        </button>
                        <?php
                        $stmt = $blog_pdo->query("SELECT * FROM albums ORDER BY id DESC");
                        while ($album = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo '<button class="nav-link" id="nav-' . $album['id'] . '-tab" data-bs-toggle="tab" data-bs-target="#nav-' . $album['id'] . '" type="button" role="tab" aria-controls="nav-' . $album['id'] . '" aria-selected="false">
                                    <i class="bi bi-folder"></i> ' . htmlspecialchars($album['title']) . '
                                </button>';
                        }
                        ?>
                    </div>
                </nav>
                
                <div class="tab-content mt-3" id="nav-tabContent">
                    <!-- All Images Tab -->
                    <div class="tab-pane fade show active" id="nav-all" role="tabpanel" aria-labelledby="nav-all-tab">
                        <div class="row">
                            <?php
                            $stmt = $blog_pdo->query("SELECT * FROM gallery WHERE active='Yes' ORDER BY id DESC");
                            $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            if (count($images) == 0) {
                                echo '<div class="col-12"><div class="alert alert-info">There are no images in the gallery.</div></div>';
                            } else {
                                foreach ($images as $image) {
                                    echo '
                                    <div class="col-md-4 mb-3">
                                        <div class="card shadow-sm" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#img' . $image['id'] . '">
                                            <img src="' . BASE_URL . htmlspecialchars($image['image']) . '" alt="' . htmlspecialchars($image['title']) . '" class="card-img-top" style="height: 180px; object-fit: cover;">
                                            <div class="card-body">
                                                <h6 class="card-title">' . htmlspecialchars($image['title']) . '</h6>
                                                <button type="button" class="btn btn-sm btn-outline-primary w-100">
                                                    <i class="bi bi-info-circle"></i> View Details
                                                </button>
                                            </div>
                                        </div>
                                    </div>';
                                }
                            }
                            ?>
                        </div>
                    </div>
                    
                    <!-- Album Tabs -->
                    <?php
                    $albums_stmt = $blog_pdo->query("SELECT * FROM albums ORDER BY id DESC");
                    while ($album = $albums_stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo '<div class="tab-pane fade" id="nav-' . $album['id'] . '" role="tabpanel" aria-labelledby="nav-' . $album['id'] . '-tab">
                                <div class="row">';
                        
                        $images_stmt = $blog_pdo->prepare("SELECT * FROM gallery WHERE active='Yes' AND album_id = ? ORDER BY id DESC");
                        $images_stmt->execute([$album['id']]);
                        $album_images = $images_stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (count($album_images) == 0) {
                            echo '<div class="col-12"><div class="alert alert-info">There are no images in this album.</div></div>';
                        } else {
                            foreach ($album_images as $image) {
                                echo '
                                <div class="col-md-4 mb-3">
                                    <div class="card shadow-sm" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#img' . $image['id'] . '">
                                        <img src="' . BASE_URL . htmlspecialchars($image['image']) . '" alt="' . htmlspecialchars($image['title']) . '" class="card-img-top" style="height: 180px; object-fit: cover;">
                                        <div class="card-body">
                                            <h6 class="card-title">' . htmlspecialchars($image['title']) . '</h6>
                                            <button type="button" class="btn btn-sm btn-outline-primary w-100">
                                                <i class="bi bi-info-circle"></i> View Details
                                            </button>
                                        </div>
                                    </div>
                                </div>';
                            }
                        }
                        
                        echo '</div></div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- Image Modals -->
<?php
$all_images_stmt = $blog_pdo->query("SELECT * FROM gallery WHERE active='Yes' ORDER BY id DESC");
while ($image = $all_images_stmt->fetch(PDO::FETCH_ASSOC)) {
    echo '
    <div class="modal fade" id="img' . $image['id'] . '" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">' . htmlspecialchars($image['title']) . '</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <img src="' . BASE_URL . htmlspecialchars($image['image']) . '" class="img-fluid mb-3" alt="' . htmlspecialchars($image['title']) . '">
                    <div>' . html_entity_decode($image['description']) . '</div>
                </div>
            </div>
        </div>
    </div>';
}
?>

<?php include '../assets/includes/footer-close.php'; ?>
