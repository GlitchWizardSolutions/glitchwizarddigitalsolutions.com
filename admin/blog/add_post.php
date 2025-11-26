<?php
require 'assets/includes/admin_config.php';

if (isset($_POST['add'])) {
    $title = trim($_POST['title']);
	$slug = generateSeoURL($title);
    $active = $_POST['active'];
	$featured = $_POST['featured'];
    $category_id = (int) $_POST['category_id'];
    $content = $_POST['content'];
    $date = date($settings['date_format']);
    $time = date('H:i');
    
	$author = $uname;
	$stmt = $blog_pdo->prepare("SELECT id FROM `users` WHERE username = ? LIMIT 1");
	$stmt->execute([$author]);
    $auth = $stmt->fetch(PDO::FETCH_ASSOC);
    $author_id = $auth ? $auth['id'] : 0;

    $image = '';
    
    if (@$_FILES['image']['name'] != '') {
        $target_dir    = "../../client-dashboard/blog/uploads/posts/";
        $target_file   = $target_dir . basename($_FILES["image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        $uploadOk = 1;
        
        // Check if image file is a actual image or fake image
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if ($check !== false) {
            $uploadOk = 1;
        } else {
            echo '<div class="alert alert-danger">The file is not an image.</div>';
            $uploadOk = 0;
        }
        
        // Check file size
        if ($_FILES["image"]["size"] > 10000000) {
            echo '<div class="alert alert-warning">Sorry, your file is too large.</div>';
            $uploadOk = 0;
        }
        
        if ($uploadOk == 1) {
            $string     = "0123456789wsderfgtyhjuk";
            $new_string = str_shuffle($string);
            $location   = "../../client-dashboard/blog/uploads/posts/image_$new_string.$imageFileType";
            move_uploaded_file($_FILES["image"]["tmp_name"], $location);
            $image = 'client-dashboard/blog/uploads/posts/image_' . $new_string . '.' . $imageFileType . '';
	   }
    }
    
    $stmt = $blog_pdo->prepare("INSERT INTO `posts` (category_id, title, slug, author_id, image, content, date, time, active, featured) 
								   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
	$stmt->execute([$category_id, $title, $slug, $author_id, $image, $content, $date, $time, $active, $featured]);
    
    $post_id = $blog_pdo->lastInsertId();
    $from     = $settings['email'];
    $sitename = $settings['sitename'];
	
    $stmt = $blog_pdo->query("SELECT * FROM `newsletter`");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $to = $row['email'];
        $subject = $title;
        $message = '
<html>
<body>
  <b><h1>' . $settings['sitename'] . '</h1><b/>
  <h2>New post: <b><a href="' . $settings['site_url'] . '/post.php?id=' . $post_id . '" title="Read more">' . $title . '</a></b></h2><br />

  ' . html_entity_decode($content) . '
  
  <hr />
  <i>If you do not want to receive more notifications, you can <a href="' . $settings['site_url'] . '/unsubscribe?email=' . $to . '">Unsubscribe</a></i>
</body>
</html>
';
        
        $headers = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";

        $headers .= 'From: ' . $from . '';
        
        @mail($to, $subject, $message, $headers);
    }
    
    header('Location: posts.php');
    exit;
}
?>
<?=template_admin_header('Add Post', 'blog')?>

<?=generate_breadcrumbs([
    ['title' => 'Admin Dashboard', 'url' => '../index.php'],
    ['title' => 'Blog', 'url' => 'blog_dash.php'],
    ['title' => 'Posts', 'url' => 'posts.php'],
    ['title' => 'Add Post', 'url' => '']
])?>

<div class="content-title mb-3">
    <div class="title">
       <i class="fa-solid fa-edit"></i>
        <div class="txt">
            <h2>Add Post</h2>
            <p>Create a new blog post</p>
        </div>
    </div>
</div>

<div class="form-professional">
    <div class="card">
        <h6 class="card-header">Add Post</h6>         
            <div class="card-body">
                <form name="post_form" action="" method="post" enctype="multipart/form-data">
					<p>
						<label>Title</label>
						<input class="form-control" name="title" id="title" value="" type="text" oninput="countText()" required>
						<i>For best SEO keep title under 50 characters.</i>
						<label for="characters">Characters: </label>
						<span id="characters">0</span><br>
					</p>
					<p>
						<label>Image</label>
						<input type="file" name="image" class="form-control" />
					</p>
					<p>
						<label>Active</label><br />
						<select name="active" class="form-select" required>
							<option value="Yes" selected>Yes</option>
							<option value="No">No</option>
                        </select>
					</p>
					<p>
						<label>Featured</label><br />
						<select name="featured" class="form-select" required>
							<option value="Yes">Yes</option>
							<option value="No" selected>No</option>
                        </select>
					</p>
					<p>
						<label>Category</label><br />
						<select name="category_id" class="form-select" required>
<?php
$stmt = $blog_pdo->query("SELECT * FROM `categories`");
while ($rw = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo '
                            <option value="' . $rw['id'] . '">' . htmlspecialchars($rw['category']) . '</option>
									';
}
?>
						</select>
					</p>
					<p>
						<label>Content</label>
						<textarea class="form-control" id="summernote" rows="8" name="content" required></textarea>
					</p>
								
					<input type="submit" name="add" class="btn btn-primary col-12" value="Add" />
				</form>                      
            </div>
    </div>
</div>

<?=template_admin_footer('
<script>
$(document).ready(function() {
	$("#summernote").summernote({height: 350});
	
	var noteBar = $(".note-toolbar");
		noteBar.find("[data-toggle]").each(function() {
		$(this).attr("data-bs-toggle", $(this).attr("data-toggle")).removeAttr("data-toggle");
	});
});
</script>
')?>