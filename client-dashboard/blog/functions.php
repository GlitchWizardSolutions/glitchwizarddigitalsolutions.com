<?php
if (!function_exists('short_text')){
 function short_text($text, $length){
    $maxTextLenght = $length;
    $aspace        = " ";
    if (strlen($text) > $maxTextLenght) {
        $text = substr(trim($text), 0, $maxTextLenght);
        $text = substr($text, 0, strlen($text) - strpos(strrev($text), $aspace));
        $text = $text . "...";
    }
    return $text;
 }
}//function exists
if (!function_exists('emoticons')){
function emoticons($text){
    include 'config.php';
    $icons = array(
        ':)' => '🙂',
        ':-)' => '🙂',
        ':}' => '🙂',
        ':D' => '😀',
        ':d' => '😁',
        ':-D ' => '😂',
        ';D' => '😂',
        ';d' => '😂',
        ';)' => '😉',
        ';-)' => '😉',
        ':P' => '😛',
        ':-P' => '😛',
        ':-p' => '😛',
        ':p' => '😛',
        ':-b' => '😛',
        ':-Þ' => '😛',
        ':(' => '🙁',
        ';(' => '😓',
        ':\'(' => '😓',
        ':o' => '😮',
        ':O' => '😮',
        ':0' => '😮',
        ':-O' => '😮',
        ':|' => '😐',
        ':-|' => '😐',
        ' :/' => ' 😕',
        ':-/' => '😕',
        ':X' => '😷',
        ':x' => '😷',
        ':-X' => '😷',
        ':-x' => '😷',
        '8)' => '😎',
        '8-)' => '😎',
        'B-)' => '😎',
        ':3' => '😊',
        '^^' => '😊',
        '^_^' => '😊',
        '<3' => '😍',
        ':*' => '😘',
        'O:)' => '😇',
        '3:)' => '😈',
        'o.O' => '😵',
        'O_o' => '😵',
        'O_O' => '😵',
        'o_o' => '😵',
        '0_o' => '😵',
        'T_T' => '😵',
        '-_-' => '😑',
        '>:O' => '😆',
        '><' => '😆',
        '>:(' => '😣',
        ':v' => '🙃',
        '(y)' => '👍',
        ':poop:' => '💩',
        ':|]' => '🤖'
    );
    return strtr($text, $icons);
}
}//function exists
if (!function_exists('post_author')){
function post_author($author_id){
    global $blog_pdo;
    
    $author = '-';
    
    $stmt = $blog_pdo->prepare("SELECT username FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$author_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $author = $result['username'];
    }
 
    return $author;
}
}//function exists
if (!function_exists('post_title')){
function post_title($post_id){
    global $blog_pdo;
    
    $title = '-';
    
    $stmt = $blog_pdo->prepare("SELECT title FROM posts WHERE id = ? LIMIT 1");
    $stmt->execute([$post_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $title = $result['title'];
    }
 
    return $title;
}
}//function exists
if (!function_exists('post_category')){
function post_category($category_id){
    global $blog_pdo;
    
    $category = '-';

    $stmt = $blog_pdo->prepare("SELECT category FROM categories WHERE id = ? LIMIT 1");
    $stmt->execute([$category_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $category = $result['category'];
    }
 
    return $category;
}
}//function exists
if (!function_exists('post_slug')){
function post_slug($post_id){
    global $blog_pdo;
    
    $post_slug = '';

    $stmt = $blog_pdo->prepare("SELECT slug FROM posts WHERE id = ? LIMIT 1");
    $stmt->execute([$post_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $post_slug = $result['slug'];
    }
 
    return $post_slug;
}
}//function exists
if (!function_exists('post_categoryslug')){
function post_categoryslug($category_id){
    global $blog_pdo;
    
    $category_slug = '';

    $stmt = $blog_pdo->prepare("SELECT slug FROM categories WHERE id = ? LIMIT 1");
    $stmt->execute([$category_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $category_slug = $result['slug'];
    }
 
    return $category_slug;
}
}//function exists
if (!function_exists('post_commentscount')){
function post_commentscount($post_id, $blog_pdo = null){
    global $blog_pdo;
    
    $comments_count = '0';

    $stmt = $blog_pdo->prepare("SELECT COUNT(id) as count FROM comments WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $comments_count = $result['count'];
 
    return $comments_count;
}
}//function exists
if (!function_exists('head')){
function head(){
    global $blog_pdo, $settings;
    if (!isset($_SESSION['sec-username'])) {
        $logged = 'No';
    } else {
        
        $username = $_SESSION['sec-username'];
        
        $stmt = $blog_pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            $logged = 'No';
        } else {
            $rowusers = $result;
            $logged = 'Yes';
        }
    }
// Global site settings
$site_name = $settings['site_name'] ?? 'My Blog';
$site_description = $settings['description'] ?? '';
$site_keywords = $settings['keywords'] ?? '';
$theme = $settings['theme'] ?? 'default';
$rtl = $settings['rtl'] ?? 'off';    
?>
<!DOCTYPE html>
<html lang="en" dir="<?= $rtl === 'on' ? 'rtl' : 'ltr' ?>">
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
<?php
	$current_page = basename($_SERVER['SCRIPT_NAME']);

    // SEO Titles, Descriptions and Sharing Tags
    if ($current_page == 'contact.php') {
        $pagetitle   = 'Contact';
		$description = 'If you have any questions do not hestitate to send us a message.';
		
    } else if ($current_page == 'comment_form.php') {
        $pagetitle   = 'Demo';
		$description = 'Demo of using the new recaptcha.';
		
    }else if ($current_page == 'gallery.php') {
        $pagetitle   = 'Gallery';
		$description = 'View all images from the Gallery.';
		
    } else if ($current_page == 'blog.php') {
        $pagetitle   = 'Blog';
		$description = 'View all blog posts.';
        
    } else if ($current_page == 'profile.php') {
        $pagetitle   = 'Profile';
		$description = 'Manage your account settings.';
		
    } else if ($current_page == 'my-comments.php') {
        $pagetitle   = 'My Comments';
		$description = 'Manage your comments.';
		
    } else if ($current_page == 'login.php') {
        $pagetitle   = 'Sign In';
		$description = 'Login into your account.';
		
    } else if ($current_page == 'unsubscribe.php') {
        $pagetitle   = 'Unsubscribe';
		$description = 'Unsubscribe from Newsletter.';
		
    } else if ($current_page == 'error404.php') {
        $pagetitle   = 'Error 404';
		$description = 'Page is not found.';
		
    } else if ($current_page == 'search.php') {
		
		if (!isset($_GET['q'])) {
			echo '<meta http-equiv="refresh" content="0; url=blog">';
            exit;
		}
		
		$word        = $_GET['q'];
        $pagetitle   = 'Search';
		$description = 'Search results for ' . $word . '.';
		
    } else if ($current_page == 'post.php') {
        $slug = $_GET['name'];
        
        if (empty($slug)) {
            echo '<meta http-equiv="refresh" content="0; url=blog">';
            exit;
        }
        
        $stmt = $blog_pdo->prepare("SELECT title, slug, image, content FROM posts WHERE slug = ?");
        $stmt->execute([$slug]);
        $rowpt = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$rowpt) {
            echo '<meta http-equiv="refresh" content="0; url=blog">';
            exit;
        }
        
        $pagetitle   = $rowpt['title'];
		$description = short_text(strip_tags(html_entity_decode($rowpt['content'])), 150);
		
		echo '
		<meta property="og:title" content="' . $rowpt['title'] . '" />
		<meta property="og:description" content="' . short_text(strip_tags(html_entity_decode($rowpt['content'])), 150) . '" />
		<meta property="og:image" content="' . $rowpt['image'] . '" />
		<meta property="og:type" content="article"/>
		<meta property="og:url" content="' . $settings['site_url'] . '/post?name=' . $rowpt['slug'] . '" />
		<meta name="twitter:card" content="summary_large_image"></meta>
		<meta name="twitter:title" content="' . $rowpt['title'] . '" />
		<meta name="twitter:description" content="' . short_text(strip_tags(html_entity_decode($rowpt['content'])), 150) . '" />
		<meta name="twitter:image" content="' . $rowpt['image'] . '" />
		<meta name="twitter:url" content="' . $settings['site_url'] . '/post?name=' . $rowpt['slug'] . '" />
		';
		
    } else if ($current_page == 'page.php') {
        $slug = $_GET['name'];
        
        if (empty($slug)) {
            echo '<meta http-equiv="refresh" content="0; url=' . $settings['site_url'] . '">';
            exit;
        }
        
        $stmt = $blog_pdo->prepare("SELECT title, content FROM pages WHERE slug = ?");
        $stmt->execute([$slug]);
        $rowpp = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$rowpp) {
            echo '<meta http-equiv="refresh" content="0; url=' . $settings['site_url'] . '">';
            exit;
        }
        
        $pagetitle   = $rowpp['title'];
		$description = short_text(strip_tags(html_entity_decode($rowpp['content'])), 150);
		
    } else if ($current_page == 'category.php') {
        $slug = $_GET['name'];
        
        if (empty($slug)) {
            echo '<meta http-equiv="refresh" content="0; url=blog">';
            exit;
        }
        
        $stmt = $blog_pdo->prepare("SELECT category FROM categories WHERE slug = ?");
        $stmt->execute([$slug]);
        $rowct = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$rowct) {
            echo '<meta http-equiv="refresh" content="0; url=blog">';
            exit;
        }
        
        $pagetitle   = $rowct['category'];
		$description = 'View all blog posts from ' . $rowct['category'] . ' category.';
    }
    
    if ($current_page == 'index.php') {
        echo '
		<title>' . $settings['sitename'] . '</title>
		<meta name="description" content="' . $settings['description'] . '" />';
    } else {
        echo '
		<title>' . $pagetitle . ' - ' . $settings['sitename'] . '</title>
		<meta name="description" content="' . $description . '" />';
    }
    
?>      <meta name="keywords" content="<?= htmlspecialchars($site_keywords) ?>">
        <meta name="author" content="Barbara Moore" />
		<meta name="generator" content="Blog" />
        <meta name="robots" content="index, follow, all" />
        <link rel="shortcut icon" href="assets/img/favicon.png" type="image/png" />

       <!-- Bootstrap 5 -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
		<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

		<!-- Font Awesome 5 -->
        <link href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" type="text/css" rel="stylesheet"/>            
<?php
if($settings['theme'] != "Bootstrap 5") {
    echo '
        <!-- Bootstrap 5 Theme -->
        <link href="https://bootswatch.com/5/'. strtolower($settings['theme']) .'/bootstrap.min.css" type="text/css" rel="stylesheet"/>
	';
}
?>
		<!-- jQuery -->
		<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
		
		<!-- phpBlog styles, scripts -->
        <link href="assets/css/phpblog.css" rel="stylesheet">
		<script src="assets/js/phpblog.js"></script>
<?php
if ($current_page == 'post.php') {
?>
        <!-- jsSocials -->
        <link type="text/css" rel="stylesheet" href="https://cdn.jsdelivr.net/jquery.jssocials/1.5.0/jssocials.css" />
        <link type="text/css" rel="stylesheet" href="https://cdn.jsdelivr.net/jquery.jssocials/1.5.0/jssocials-theme-classic.css" />
        <script type="text/javascript" src="https://cdn.jsdelivr.net/jquery.jssocials/1.5.0/jssocials.min.js"></script>
<?php
}
?>
	
        <style>
<?php
if($settings['background_image'] != "") {
    echo 'body {
        background: url("' . $settings['background_image'] . '") no-repeat center center fixed;
        -webkit-background-size: cover;
        -moz-background-size: cover;
        -o-background-size: cover;
        background-size: cover;
    }';
}
?>
.form-control {
    border-color:grey;
}
        </style>
        
<?php
echo base64_decode($settings['head_customcode']);
?>
  <!-- Load reCAPTCHA v3 JavaScript API -->
  <script src="https://www.google.com/recaptcha/api.js?render=6LdmAmgrAAAAAIdsJeCLDjkPhYeVZIH6wSGqkxIH"></script>
</head>
<body <?php 
if ($settings['rtl'] == "Yes") {
	echo 'dir="rtl"';
}
?>>

<?php
if ($logged == 'Yes' && ($rowusers['role'] == 'Admin' || $rowusers['role'] == 'Editor')) {
?>
	<div class="nav-scroller bg-dark shadow-sm">
		<nav class="nav" aria-label="Secondary navigation">
<?php
if ($rowusers['role'] == 'Admin') {
?>
			<a class="nav-link text-white" href="../admin/blog/blog_dash.php">ADMIN MENU</a>
<?php
} else {
?>
			<a class="nav-link text-white" href="../admin/blog/blog_dash.php">EDITOR MENU</a>
<?php
}
?>
			<a class="nav-link text-secondary" href="../admin/blog/blog_dash.php">
				<i class="fas fa-columns"></i> Blog Dashboard
			</a>
			<a class="nav-link text-secondary" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
				<i class="fas fa-tasks"></i> Manage
			</a>
				<ul class="dropdown-menu bg-dark">
<?php
if ($rowusers['role'] == 'Admin') {
?>
					<li>
						<a class="dropdown-item text-white" href="../admin/blog/settings.php">
							Site Settings
						</a>
					</li>
					<li>
						<a class="dropdown-item text-white" href="../admin/blog/menu_editor.php">
							Menu
						</a>
					</li>
					<li>
						<a class="dropdown-item text-white" href="../admin/blog/widgets.php">
							Widgets
						</a>
					</li>
					<li>
						<a class="dropdown-item text-white" href="../admin/blog/users.php">
							Users
						</a>
					</li>
					<li>
						<a class="dropdown-item text-white" href="../admin/blog/newsletter.php">
							Newsletter
						</a>
					</li>
<?php
}
?>
					<li>
						<a class="dropdown-item text-white" href="../admin/blog/file.php">
							Files
						</a>
					</li>
					<li>
						<a class="dropdown-item text-white" href="../admin/blog/posts.php">
							Posts
						</a>
					</li>
					<li>
						<a class="dropdown-item text-white" href="../admin/blog/gallery.php">
							Gallery
						</a>
					</li>
<?php
if ($rowusers['role'] == 'Admin') {
?>
					<li>
						<a class="dropdown-item text-white" href="../admin/blog/pages.php">
							Pages
						</a>
					</li>
<?php
}
?>
				</ul>
<?php
if ($rowusers['role'] == 'Admin') {
	$stmt = $blog_pdo->prepare("SELECT id FROM messages WHERE viewed = 'No'");
	$stmt->execute();
	$unread_messages = $stmt->rowCount();
?>
			
			<a class="nav-link text-secondary" href="../admin/blog/messages.php">
				<i class="fas fa-envelope"></i> Messages
				<span class="badge text-bg-light rounded-pill align-text-bottom"><?php
	echo $unread_messages; 
?> </span>
			</a>
			<a class="nav-link text-secondary" href="../admin/blog/comments.php">
				<i class="fas fa-comments"></i> Comments
			</a>
<?php
}
?>
			<a class="nav-link text-secondary" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
				<i class="far fa-plus-square"></i> New
			</a>
				<ul class="dropdown-menu bg-dark">
					<li>
						<a class="dropdown-item text-white" href="../admin/blog/add_post.php">
							Add Post
						</a>
					</li>
					<li>
						<a class="dropdown-item text-white" href="../admin/blog/add_image.php">
							Add Image
						</a>
					</li>
					<li>
						<a class="dropdown-item text-white" href="../admin/blog/upload_file.php">
							Upload File
						</a>
					</li>
<?php
if ($rowusers['role'] == 'Admin') {
?>
					<li>
						<a class="dropdown-item text-white" href="../admin/blog/add_page.php">
							Add Page
						</a>
					</li>
<?php
}
?>
				</ul>
		</nav>
	</div>
<?php
}
?>
	
	<header class="py-3 border-bottom bg-primary">
		<div class="<?php
if ($settings['layout'] == 'Wide') {
	echo 'container-fluid';
} else {
	echo 'container';
}
?> d-flex flex-wrap justify-content-center">
			<a href="<?php echo $settings['site_url']; ?>" class="d-flex align-items-center text-white mb-3 mb-md-0 me-md-auto text-decoration-none">
				<span class="fs-4"><b><i class="far fa-newspaper"></i> <?php
		echo $settings['sitename'];
?></b></span>
			</a>
			
			<form class="col-12 col-lg-auto mb-3 mb-lg-0" action="search" method="GET">
				<div class="input-group">
					<input type="search" class="form-control" placeholder="Search" name="q" value="<?php
if (isset($_GET['q'])) {
	echo $_GET['q'];
}
?>" required />
					<span class="input-group-btn">
						<button class="btn btn-dark" title='search' type="submit"><i class="fa fa-search"></i></button>
					</span>
				</div>
			</form>
		</div>
	</header>
	
	<nav class="navbar nav-underline navbar-expand-lg py-2 bg-light border-bottom">
		<div class="<?php
if ($settings['layout'] == 'Wide') {
	echo 'container-fluid';
} else {
	echo 'container';
}
?>">
			<button class="navbar-toggler mx-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
				<span class="navbar-toggler-icon"></span> Navigation
			</button>
			<div class="collapse navbar-collapse" id="navbarSupportedContent">
				<ul class="navbar-nav me-auto">
<?php
	$stmt = $blog_pdo->query("SELECT * FROM menu");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        if ($row['path'] == 'blog') {
			
            echo '	<li class="nav-item link-body-emphasis dropdown">
						<a href="blog" class="nav-link link-dark dropdown-toggle px-2';
            if ($current_page == 'blog.php' || $current_page == 'category.php') {
                echo ' active';
            }
            echo '" data-bs-toggle="dropdown">
							<i class="fa ' . $row['fa_icon'] . '"></i> ' . $row['page'] . ' 
							<span class="caret"></span>
						</a>
						<ul class="dropdown-menu">
							<li><a class="dropdown-item" href="blog">View all posts</a></li>';
            $stmt2 = $blog_pdo->query("SELECT * FROM categories ORDER BY category ASC");
            while ($row2 = $stmt2->fetch(PDO::FETCH_ASSOC)) {
                echo '		<li><a class="dropdown-item" href="category?name=' . $row2['slug'] . '"><i class="fas fa-chevron-right"></i> ' . $row2['category'] . '</a></li>';
            }
            echo '		</ul>
					</li>';
		
        } else {

			echo '	<li class="nav-item link-body-emphasis">
						<a href="' . $row['path'] . '" class="nav-link link-dark px-2';
            if ($current_page == 'page.php'
				&& (($_GET['name'] ?? '') == ltrim(strstr($row['path'], '='), '='))
			) {
                echo ' active';
			
            } else if ($current_page != 'page.php' && $current_page == $row['path'] . '.php') {
                echo ' active';
            }
            echo '">
							<i class="fa ' . $row['fa_icon'] . '"></i> ' . $row['page'] . '
						</a>
					</li>';
        }
    }
?>
				</ul>
				<ul class="navbar-nav d-flex">
      
<?php
    if ($logged == 'No') {
?>
					<li class="nav-item">
						<a href="login.php" class="btn btn-primary px-2">
							<i class="fas fa-sign-in-alt"></i> Sign In &nbsp;|&nbsp; Register
						</a>
					</li>
<?php
    } else {
?>
					<li class="nav-item dropdown">
						<a href="#" class="nav-link link-dark dropdown-toggle" data-bs-toggle="dropdown">
							<i class="fas fa-user"></i> Profile <span class="caret"></span>
						</a>
						<ul class="dropdown-menu">
							<li>
								<a class="dropdown-item <?php
if ($current_page == 'my-comments.php') {
	echo ' active';
}
?>" href="my-comments">
									<i class="fa fa-comments"></i> My Comments
								</a>
							</li>
							<li>
								<a class="dropdown-item<?php
if ($current_page == 'profile.php') {
	echo ' active';
}
?>" href="profile">
									<i class="fas fa-cog"></i> Settings
								</a>
							</li>
							<li role="separator" class="divider"></li>
							<li>
								<a class="dropdown-item" href="logout">
									<i class="fas fa-sign-out-alt"></i> Logout
								</a>
							</li>
						</ul>
					</li>
<?php
    }
?>
				</ul>
			</div>
		</div>
	</nav>
    
<?php
if ($settings['latestposts_bar'] == 'Enabled') {
?>
    <div class="pt-2 bg-light">
        <div class="<?php
if ($settings['layout'] == 'Wide') {
	echo 'container-fluid';
} else {
	echo 'container';
}
?> d-flex justify-content-center">
            <div class="col-md-2">
                <h5>
                    <span class="badge bg-danger">
                        <i class="fa fa-info-circle"></i> Latest: 
                    </span>
                </h5>
            </div>
            <div class="col-md-10">
                <div class="marquee-wrapper" aria-label="Scrolling announcements">
                    <div class="marquee-content">
                    <?php /*    This is scrolling text. It's accessible and standards-compliant! */?>
                      <?php
                        $stmt = $blog_pdo->query("SELECT * FROM posts WHERE active='Yes' ORDER BY id DESC LIMIT 6");
                        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        if (count($posts) == 0) {
                             echo 'There are no published posts';
                        } else {
                            foreach ($posts as $row) {
                                echo '<a href="post?name=' . $row['slug'] . '">' . $row['title'] . '</a>
                                &nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;';
                            }
                        }
?>  
                        </div>
                </div>
               </div>
        </div>
    </div>
<?php
}
?>
	
    <div class="<?php
if ($settings['layout'] == 'Wide') {
	echo 'container-fluid';
} else {
	echo 'container';
}
?> mt-3">
	
<?php
$stmt = $blog_pdo->query("SELECT * FROM widgets WHERE position = 'header' ORDER BY id ASC");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo '
		<div class="card mb-3">
			<div class="card-header">' . $row['title'] . '</div>
			<div class="card-body">
				' . html_entity_decode($row['content']) . '
			</div>
		</div>
	';
}
?>
	
        <div class="row">
<?php
}
/**
 * Get avatar, author name, and badge for a comment author.
 *
 * @param PDO $blog_pdo The blog database PDO connection
 * @param PDO $pdo The main database PDO connection
 * @param string $username The username from the comment
 * @param string $user_id The user ID from the comment
 * @param string $account_id The account ID from the comment
 * @param string $guest 'Yes' if the user is a guest, otherwise 'No'
 * @return array Associative array with 'avatar', 'author', and 'badge'
 */
}//function exists
if (!function_exists('get_user_avatar_info')){
function get_user_avatar_info($blog_pdo, $pdo, string $username, string $user_id, string $account_id, string $guest = 'No'): array{
    static $user_cache = []; // In-memory cache for performance

    $key = strtolower($guest);
    if (isset($user_cache[$key])) {
        return $user_cache[$key]; // Return cached result
    }

    // Default values for guest users
    $default_avatar = 'assets/img/avatar.png';
    $result = [
        'avatar' => $default_avatar,
        'author' => $username,
        'badge'  => '<span class="badge bg-secondary">Guest</span>',
    ];

    if (strtolower($guest) === 'yes') {
        $user_cache[$key] = $result;
        return $result;
    }

    // Lookup for registered user
    //select from comments where user-id=0 and account id = value passed in
    //else where account id is with values passed in.
    if($user_id == 0 AND $account_id !=0){
        $account='Yes';
        $user='No';
    }elseif($user_id !=0 AND $account_id ==0){
        $account='No';
        $user='Yes';
    }else{
        $account='No';
        $user='No';
    }//end determining if account, user, or guest.
    
    if($account='Yes' ){//this is a member account.
        $stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ? LIMIT 1'); 
	    $stmt->execute([ $account_id ]);
	    $user_result = $stmt->fetch(PDO::FETCH_ASSOC);
    }elseif($user='Yes'){//this is a user account.
        $stmt = $blog_pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1'); 
	    $stmt->execute([ $user_id ]);
	    $user_result = $stmt->fetch(PDO::FETCH_ASSOC);        
    }//end checking if the comment is from a user or member.
    
    //start populating the output data, using the results above or the default data.
        if ($user_result) {
            $user = $user_result;
            $result['avatar'] = !empty($user['avatar']) ? $user['avatar'] : $default_avatar;
            $result['author'] = htmlspecialchars($user['username']);
            $result['badge']  = '<span class="badge bg-primary">' . htmlspecialchars($user['role']) . '</span>';
        } else {//this is if there isn't any user in the database, in otherwords, a guest. 
            $result['avatar'] = !empty($user['avatar']) ? $user['avatar'] : $default_avatar;
            $result['author'] = htmlspecialchars($username);
            $result['badge']  = '<span class="badge bg-secondary">Guest</span>';
        }//end checking for user results & populating output data.
        $stmt=null;
        $user_cache[$key] = $result; // Cache the result
        return $result;
 }//end of the function itself.
}//function exists

if (!function_exists('sidebar')){
function sidebar() {
	
    global $blog_pdo, $settings;
?>
			<div id="sidebar" class="col-md-4">

				<div class="card">
					<div class="card-header"><i class="fas fa-list"></i> Categories</div>
					<div class="card-body">
						<ul class="list-group">
<?php
    $stmt = $blog_pdo->query("SELECT * FROM categories ORDER BY category ASC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $category_id = $row['id'];
        $stmt_count = $blog_pdo->prepare("SELECT id FROM posts WHERE category_id = ? AND active = 'Yes'");
        $stmt_count->execute([$category_id]);
		$posts_count = $stmt_count->rowCount();
        echo '
							<a href="category?name=' . $row['slug'] . '">
								<li class="list-group-item d-flex justify-content-between align-items-center">
									' . $row['category'] . '
									<span class="badge bg-secondary rounded-pill">' . $posts_count . '</span>
								</li>
							</a>
		';
    }
?>
						</ul>
					</div>
				</div>
				
				<div class="card mt-3">
					<div class="card-header">
						<ul class="nav nav-tabs card-header-tabs nav-justified">
							<li class="nav-item active">
								<a class="nav-link active" href="#popular" data-bs-toggle="tab">
									Popular Posts
								</a>
							</li>
							<li class="nav-item">
								<a class="nav-link" href="#commentss" data-bs-toggle="tab">
									Recent Comments
								</a>
							</li>
						</ul>
					</div>
					<div class="card-body">
						<div class="tab-content">
							<div id="popular" class="tab-pane fade show active">
<?php
    $stmt = $blog_pdo->query("SELECT * FROM posts WHERE active='Yes' ORDER BY views, id DESC LIMIT 4");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($posts) == 0) {
        echo '<div class="alert alert-info">There are no published posts</div>';
    } else {
        foreach ($posts as $row) {
            
            $image = "";
            if($row['image'] != "") {
                $image = '<img class="rounded shadow-1-strong me-1"
							src="' . $row['image'] . '" alt="' . $row['title'] . '" width="70"
							height="70" />';
			} else {
                $image = '<svg class="bd-placeholder-img rounded shadow-1-strong me-1" width="70" height="70" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="No Image" preserveAspectRatio="xMidYMid slice" focusable="false">
                <title>Image</title><rect width="70" height="70" fill="#55595c"/>
                <text x="0%" y="50%" fill="#eceeef" dy=".1em">No Image</text></svg>';
            }
            echo '       
								<div class="mb-2 d-flex flex-start align-items-center bg-light rounded">
									<a href="post?name=' . $row['slug'] . '" class="ms-1">
										' . $image . '
									</a>
									<div class="mt-2 mb-2 ms-1 me-1">
										<h6 class="text-primary mb-1">
											<a href="post?name=' . $row['slug'] . '">' . $row['title'] . '</a>
										</h6>
										<p class="text-muted small mb-0">
											<i class="fas fa-calendar"></i> ' . date($settings['date_format'], strtotime($row['date'])) . ', ' . $row['time'] . '<br />
                                            <i class="fa fa-comments"></i> Comments: 
												<a href="post?name=' . $row['slug'] . '#comments">
													<b>' . post_commentscount($row['id']) . '</b>
												</a>
										</p>
									</div>
								</div>
';
        }
    }
?>
							</div>
							<div id="commentss" class="tab-pane fade">
<?php
    $stmt = $blog_pdo->query("SELECT * FROM comments WHERE approved='Yes' ORDER BY id DESC LIMIT 4");
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($comments) == 0) {
        echo "There are no comments";
    } else {
        foreach ($comments as $row) {
			
			$badge = '';
			$acuthor = $row['user_id'];
            if ($row['guest'] == 'Yes') {
                $acavatar = 'assets/img/avatar.png';
				$badge = ' <span class="badge bg-secondary">Guest</span>';
            } else {
                $stmt_user = $blog_pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
                $stmt_user->execute([$acuthor]);
                $rowch = $stmt_user->fetch(PDO::FETCH_ASSOC);
                
                if ($rowch) {
                    $acavatar = $rowch['avatar'];
                    $acuthor = $rowch['username'];
                }
            }
			
            $stmt_post = $blog_pdo->prepare("SELECT * FROM posts WHERE active='Yes' AND id = ?");
            $stmt_post->execute([$row['post_id']]);
            while ($row2 = $stmt_post->fetch(PDO::FETCH_ASSOC)) {
				echo '
								<div class="mb-2 d-flex flex-start align-items-center bg-light rounded border">
									<a href="post?name=' . $row2['slug'] . '#comments" class="ms-2">
										<img class="rounded-circle shadow-1-strong me-2"
										src="' . $acavatar . '" alt="' . $acuthor . '" 
										width="60" height="60" />
									</a>
									<div class="mt-1 mb-1 ms-1 me-1">
										<h6 class="text-primary mb-1">
											<a href="post?name=' . $row2['slug'] . '#comments">' . $acuthor . '</a>
										</h6>
										<p class="text-muted small mb-0">
											on <a href="post?name=' . $row2['slug'] . '#comments">' . $row2['title'] . '</a><br />
											<i class="fas fa-calendar"></i> ' . date($settings['date_format'], strtotime($row['date'])) . ', ' . $row['time'] . '
										</p>
									</div>
								</div>
';
            }
        }
    }
?>
                            </div>
                        </div>
                    </div>
                </div>
				
				<div class="p-4 mt-3 bg-body-tertiary rounded text-dark">
					<h6><i class="fas fa-envelope-open-text"></i> Subscribe</h6><hr />
					
					<p class="mb-3">Get the latest news and exclusive offers</p>
					
					<form action="" method="POST">
						<div class="input-group">
							<input type="email" class="form-control" placeholder="E-Mail Address" name="email" required />
							<span class="input-group-btn">
								<button class="btn btn-primary" type="submit" name="subscribe">Subscribe</button>
							</span>
						</div>
					</form>
<?php
    if (isset($_POST['subscribe'])) {
        $email = $_POST['email'];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo '<div class="alert alert-danger">The entered E-Mail Address is invalid</div>';
        } else {
            $stmt = $blog_pdo->prepare("SELECT * FROM newsletter WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $validator = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($validator) {
                echo '<div class="alert alert-warning">This E-Mail Address is already subscribed.</div>';
            } else {
                $stmt_insert = $blog_pdo->prepare("INSERT INTO newsletter (email) VALUES (?)");
                $stmt_insert->execute([$email]);
                echo '<div class="alert alert-success">You have successfully subscribed to our newsletter.</div>';
            }
        }
    }
?>
				</div>

<?php
    $stmt = $blog_pdo->query("SELECT * FROM widgets WHERE position = 'sidebar' ORDER BY id ASC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo '	
				<div class="card mt-3">
					  <div class="card-header">' . $row['title'] . '</div>
					  <div class="card-body">
						' . html_entity_decode($row['content']) . '
					  </div>
				</div>
';
    }
?>
			</div>
		
<?php
}//end function sidebar...
}//function exists
if (!function_exists('footer')){
function footer(){
    global $settings, $phpblog_version;
	echo '<footer class="footer border-top px-4 py-3 mt-3" style="background:#BFA4EF;">
		<div class="row">
			<div class="col-md-4 mb-3">
    
			        <h3 style="color:#593196"><i class="fas fa-gavel"></i> Policy Suite</h3>
			         <div style="margin-top:20px">
			    	<p class="btn btn-primary" style="background:white"><a href="https://glitchwizardsolutions.com/privacy.php" target="_blank"><i class="fas fa-file-contract"></i> Privacy Policy</a></p><br>
			    	<p class="btn btn-primary" style="background:white"><a href="https://glitchwizardsolutions.com/terms.php" target="_blank"><i class="fas fa-file-contract"></i>Terms of Service</a></p><br>
			    	<p class="btn btn-primary" style="background:white"><a href="https://glitchwizardsolutions.com/accessibility.php" target="_blank"><i class="fas fa-wheelchair"></i> Accessibility Policy</a></p><br>
			    	</div>
		 
			</div>
			<div class="col-md-5 mb-3" style="color:#593196">
				<h3><i class="fa fa-info-circle"></i> About</h3>

    <div style="margin-top:20px; font-size:1.2em;">' 
	 . $settings["description"] .  
	'</div>
			</div>
			<div class="col-md-3 mb-3">
		 
					<div class="col-12">';
					
 
    if ($settings["facebook"] != "") {
 
					echo '<a href="' . $settings["facebook"] . '" target="_blank" class="btn btn-primary btn-sm">
							<strong><i class="fab fa-facebook-square"></i>&nbsp; Facebook</strong></a>&nbsp';
 
    }
    if ($settings["instagram"] != "") {
 
				echo '<a href="' . $settings["instagram"] . '" target="_blank" class="btn btn-warning btn-sm">
							<strong><i class="fab fa-instagram"></i>&nbsp; Instagram</strong></a>';
 
    }
    if ($settings["twitter"] != "") {
 
		echo '<a href="' . $settings["twitter"] . '" target="_blank" class="btn btn-info btn-sm">
							<strong><i class="fab fa-twitter-square"></i>&nbsp; Twitter</strong></a>';
 
    }
    if ($settings["youtube"] != "") {
 
	echo '<a href="' . $settings["youtube"] . '" target="_blank" class="btn btn-danger btn-sm">
							<strong><i class="fab fa-youtube-square"></i>&nbsp; YouTube</strong></a>';
 
    }
	if ($settings["linkedin"] != "") {
 
	echo '<a href="' . $settings["linkedin"] . '" target="_blank" class="btn btn-primary btn-sm">
							<strong><i class="fab fa-linkedin"></i>&nbsp; LinkedIn</strong></a>';
 
    };?>
        <div style="margin-top:20px;">
	            <p class="btn btn-primary" style="background:white"><a href="rss" target="_blank"><i class="fas fa-rss-square"></i> RSS Feed</a></p><br>
				<p class="btn btn-primary" style="background:white"><a href="sitemap" target="_blank"><i class="fas fa-sitemap"></i> XML Sitemap</a></p><br>
				<p class="btn btn-primary" style="background:white"> 
				<!-- Contact Our Webmaster Link -->
                    <a id="contact-webmaster"><i class="fas fa-envelope"></i> Contact Our Webmaster</a>

                    <script>
                        // Get the "name" parameter from URL if it exists
                        const urlParams = new URLSearchParams(window.location.search);
                        const name = urlParams.get('name');

                        // Create the email fields
                        const email = 'WebMaster@glitchwizardsolutions.com';
                        const displayName = 'WebMaster';
                        let subject = 'GlitchWizard Solutions Blog ';
                        if (name) {
                        subject += ` - Page: ${document.title}, Name: ${name}`;
                        }

                        const body = encodeURIComponent(
                        "We're glad to help! Please be very specific, so we can better assist you. Thank you!"
                        );

                        const mailtoLink = `mailto:${email}?subject=${encodeURIComponent(subject)}&body=${body}`;

                        // Update the link
                        const contactLink = document.getElementById('contact-webmaster');
                        contactLink.setAttribute('href', mailtoLink);
                        // The following line overwrites the text in between the opening and closing a tags.  It is commented out here, as I want to do something else.
                       //contactLink.textContent = `Contact Our ${displayName}`;
                        contactLink.innerHTML = `<i class="fas fa-envelope"></i> Contact Our ${displayName}`;
                        </script>

			 
				</a></p><br>
	   </div>
					</div>
					
					<div class="scroll-btn"><div class="scroll-btn-arrow"></div></div>
			</div>
		</div>
	
		<hr>

<div class="container d-flex justify-content-between">
    <div>
      <!-- Content for the left side -->
      
	  <p class="d-block" style="color:#593196"><strong>&copy; 2024-<?=date("Y")?> <?=$settings["sitename"]?></strong></p> 
    </div>
    <div>
      <!-- Content for the right side (e.g., Copyright) -->
        <a href="https://glitchwizardsolutions.com" target="_blank"><i>Blog System <?=$phpblog_version; ?> Powered by <b>GlitchWizard Solutions, LLC </b></i></a>
  
    </div>
   </div>
  </footer>    
</body>
</html>
<?php
  }
}//function exists
?>