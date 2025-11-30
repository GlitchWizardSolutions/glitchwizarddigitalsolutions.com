<?php
// Knowledge Base - View Post
include_once 'assets/includes/blog-config.php';
include "core.php";

/**
 * Process blog content to fix image paths for environment-aware display
 */
function process_blog_content($content) {
    // Replace image src paths that don't start with http/https
    $content = preg_replace_callback(
        '/<img([^>]*)src=["\'](?!https?:\/\/)([^"\']+)["\']([^>]*)>/i',
        function($matches) {
            $before = $matches[1];
            $path = $matches[2];
            $after = $matches[3];
            // Prepend BASE_URL to relative paths
            return '<img' . $before . 'src="' . BASE_URL . $path . '"' . $after . '>';
        },
        $content
    );
    return $content;
}

$slug = $_GET['name'] ?? '';
if (empty($slug)) {
    header('Location: index.php');
    exit;
}

// Get post
$stmt = $blog_pdo->prepare('SELECT * FROM posts WHERE active="Yes" AND slug = ?');
$stmt->execute([$slug]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    header('Location: index.php');
    exit;
}

// Handle comment submission BEFORE any output
$cancomment = 'No';
if (isset($_SESSION['loggedin'])) {
    $cancomment = 'Yes';
} elseif ($settings['comments'] === 'guests') {
    $cancomment = 'Yes';
}

if ($cancomment === 'Yes' && isset($_POST['comment'])) {
    // Get author info from session (logged-in user)
    $author_name = '';
    $author_id = 0;
    
    if (isset($_SESSION['loggedin']) && isset($_SESSION['name']) && isset($_SESSION['id'])) {
        // Portal user
        $author_name = $_SESSION['name'];
        $author_id = $_SESSION['id']; // Get the actual user ID
    } else {
        $author_name = trim($_POST['name'] ?? '');
        $author_id = 0;
    }
    
    $comment_text = trim($_POST['comment']);
    
    // Validate
    if (strlen($author_name) >= 2 && strlen($comment_text) >= 5) {
        // Insert comment with PDO
        $date = date('Y-m-d');
        $time = date('H:i'); // Shortened time format (HH:MM)
        $remoteIp = $_SERVER['REMOTE_ADDR'];
        
        // Comments table: user_id is INT (account ID), username is VARCHAR (username string)
        $stmt = $blog_pdo->prepare("INSERT INTO comments (post_id, comment, approved, user_id, username, date, time, guest, ip) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $row['id'],
            $comment_text,
            'Yes', // Auto-approve for logged-in users
            $author_id, // Integer account ID
            $author_name, // Username string
            $date,
            $time,
            'No', // Not a guest
            $remoteIp
        ]);
        
        $slug = urlencode($row['slug']);
        header("Location: post.php?name=$slug&success=" . urlencode('Your comment has been successfully posted.') . "#comments");
        exit;
    }
}

// Use client dashboard page setup (must be after comment processing)
include '../assets/includes/page-setup.php';

// Update view count
$stmt = $blog_pdo->prepare('UPDATE posts SET views = views + 1 WHERE active="Yes" AND slug = ?');
$stmt->execute([$slug]);

$post_id = $row['id'];
$post_slug = $row['slug'];

// Check if user is logged in and get their info
$logged_in = isset($_SESSION['loggedin']);
$current_username = '';
$current_avatar = BASE_URL . 'assets/img/avatar.png'; // Default avatar

if (isset($_SESSION['loggedin']) && isset($_SESSION['name'])) {
    // Portal user - session uses 'name' not 'username'
    $current_username = $_SESSION['name'];
    $stmt = $pdo->prepare('SELECT avatar FROM accounts WHERE username = ? LIMIT 1');
    $stmt->execute([$current_username]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user_data && !empty($user_data['avatar'])) {
        // Prepend BASE_URL if path doesn't start with http
        $avatar_path = $user_data['avatar'];
        $current_avatar = (strpos($avatar_path, 'http') === 0) ? $avatar_path : BASE_URL . ltrim($avatar_path, '/');
    }
}
?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1><i class="bi bi-file-text"></i> <?= htmlspecialchars($row['title']) ?></h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="index.php">Knowledge Base</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($row['title']) ?></li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <?php if ($row['image']): ?>
                    <img src="<?= BASE_URL . $row['image'] ?>" class="card-img-top" alt="<?= htmlspecialchars($row['title']) ?>" style="height: 400px; width: 100%; object-fit: contain; background-color: #f8f9fa;">
                    <?php endif; ?>
                    
                    <div class="card-body">
                        <!-- Category -->
                        <div class="mb-2">
                            <a href="category.php?name=<?= post_categoryslug($row['category_id']) ?>">
                                <span class="badge bg-secondary"><?= post_category($row['category_id']) ?></span>
                            </a>
                        </div>
                        
                        <!-- Post Meta -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <small class="text-muted">
                                <i class="bi bi-person"></i> Posted by <strong><?= post_author($row['author_id']) ?></strong>
                                on <strong><?= date($settings['date_format'], strtotime($row['date'])) ?>, <?= $row['time'] ?></strong>
                            </small>
                            <small class="text-muted">
                                <i class="bi bi-eye"></i> <?= $row['views'] ?> views
                            </small>
                            <small class="text-muted">
                                <i class="bi bi-chat"></i> <a href="#comments"><?= post_commentscount($row['id']) ?> comments</a>
                            </small>
                        </div>
                        
                        <hr>
                        
                        <!-- Post Content -->
                        <div class="post-content">
                            <?= process_blog_content(html_entity_decode($row['content'])) ?>
                        </div>
                        
                        <hr>
                        
                        <!-- Comments Section -->
                        <h5 class="mt-4" id="comments">
                            <i class="bi bi-chat"></i> Comments (<?= post_commentscount($row['id']) ?>)
                        </h5>
                        
                        <?php
                        $stmt = $blog_pdo->prepare('SELECT * FROM comments WHERE post_id = ? AND approved="Yes" ORDER BY id DESC');
                        $stmt->execute([$row['id']]);
                        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (count($comments) === 0) {
                            echo '<p class="text-muted">No comments yet. Be the first to comment!</p>';
                        } else {
                            foreach ($comments as $comment) {
                                // Get user info - user_id is now an integer (account ID or 0)
                                // For portal users: user_id is their account ID
                                // For blog users/guests: user_id is 0
                                if ($comment['user_id'] > 0) {
                                    // Portal user - get from accounts table
                                    $stmt = $pdo->prepare('SELECT username, avatar FROM accounts WHERE id = ? LIMIT 1');
                                    $stmt->execute([$comment['user_id']]);
                                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                                    
                                    if ($user) {
                                        $author = $user['username'];
                                        $avatar_path = !empty($user['avatar']) ? $user['avatar'] : 'assets/img/avatar.png';
                                        $avatar = (strpos($avatar_path, 'http') === 0) ? $avatar_path : BASE_URL . ltrim($avatar_path, '/');
                                        $badge = '<span class="badge bg-primary">Member</span>';
                                    } else {
                                        $author = 'Unknown User';
                                        $avatar = BASE_URL . 'assets/img/avatar.png';
                                        $badge = '<span class="badge bg-secondary">Guest</span>';
                                    }
                                } else {
                                    // Guest or blog user
                                    $author = 'Guest';
                                    $avatar = BASE_URL . 'assets/img/avatar.png';
                                    $badge = '<span class="badge bg-secondary">Guest</span>';
                                }
                        ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-2">
                                    <img class="rounded-circle me-3" src="<?= $avatar ?>" alt="<?= $author ?>" width="50" height="50">
                                    <div>
                                        <h6 class="mb-0"><?= $author ?> <?= $badge ?></h6>
                                        <small class="text-muted">
                                            <i class="bi bi-calendar"></i> <?= date($settings['date_format'], strtotime($comment['date'])) ?>
                                        </small>
                                    </div>
                                </div>
                                <p class="mb-0"><?= nl2br(htmlspecialchars($comment['comment'])) ?></p>
                            </div>
                        </div>
                        <?php
                            }
                        }
                        ?>

                        <!-- Leave A Comment -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="bi bi-chat-left-text"></i> Leave A Comment</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                // Display messages
                                if (isset($_GET['error'])) {
                                    echo '<div class="alert alert-danger">' . htmlspecialchars($_GET['error']) . '</div>';
                                }
                                if (isset($_GET['success'])) {
                                    echo '<div class="alert alert-success">' . htmlspecialchars($_GET['success']) . '</div>';
                                }

                                // Comment permissions
                                $guest = 'No'; 
                                $cancomment = 'No';
                                $approved = 'No';
                                
                                if (isset($_SESSION['loggedin'])) {
                                    $guest = 'Yes';
                                    $cancomment = 'Yes';
                                    $approved = 'Yes';
                                } elseif ($settings['comments'] === 'guests') {
                                    $cancomment = 'Yes';
                                    $approved = 'No';
                                }

                                if ($cancomment === 'Yes') {
                                ?>
                                <form id="comment_form" name="comment_form" action="post.php?name=<?= $post_slug ?>" method="post">
                                    <?php if ($logged_in): ?>
                                    <!-- Show logged-in user info -->
                                    <!-- DEBUG: logged_in=<?= $logged_in ? 'true' : 'false' ?>, username=<?= $current_username ?>, avatar=<?= $current_avatar ?> -->
                                    <div class="mb-4">
                                        <div class="d-flex align-items-center p-3 bg-light rounded">
                                            <img src="<?= $current_avatar ?>" class="rounded-circle me-3" width="60" height="60" alt="<?= htmlspecialchars($current_username) ?>" style="object-fit: cover; border: 2px solid #dee2e6;" onerror="console.error('Avatar failed to load:', this.src)">
                                            <div>
                                                <div><strong class="fs-5"><?= htmlspecialchars($current_username) ?></strong></div>
                                                <small class="text-muted">Commenting as this user</small>
                                            </div>
                                        </div>
                                        <input type="hidden" name="name" value="<?= htmlspecialchars($current_username) ?>">
                                    </div>
                                    <?php else: ?>
                                    <!-- Guest user input -->
                                    <div class="mb-4">
                                        <label for="name" class="form-label"><i class="bi bi-person"></i> Author:</label>
                                        <input type="text" name="name" id="name" class="form-control" placeholder="Your Name" minlength="5" required>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="mb-4">
                                        <label for="comment" class="form-label"><i class="bi bi-chat-left-text"></i> Comment:</label>
                                        <textarea name="comment" id="comment" rows="5" class="form-control" maxlength="1000" oninput="countText()" minlength="5" placeholder="Leave a comment..." required></textarea>
                                        <small class="form-text text-muted">Characters left: <span id="characters">1000</span></small>
                                    </div>
                                    
                                    <div class="d-grid gap-2 mb-3">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="bi bi-send"></i> Submit Comment
                                        </button>
                                    </div>
                                </form>
                                <?php
                                } else {
                                    echo '<div class="alert alert-info">Please <strong><a href="../login.php">Sign In</a></strong> to be able to post a comment.</div>';
                                }
                                ?>
                            </div>
                        </div>
                    </div><!-- .col-lg-8 -->
                </div><!-- .row -->
            </section>
        </main>

        <script>
        function countText() {
            let text = document.comment_form.comment.value;
            document.getElementById('characters').innerText = 1000 - text.length;
        }
        </script>

        <?php include '../assets/includes/footer-close.php'; ?>