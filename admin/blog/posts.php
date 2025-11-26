<?php
//2025-06-24 Production
error_log('Loading Page: /admin/blog/posts ');
require 'assets/includes/admin_config.php';
require_once __DIR__ . '/functions.php';

if (isset($_GET['delete-id'])) {
    $id = (int) $_GET["delete-id"];
    $stmt = $blog_pdo->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->execute([$id]);
    $stmt = $blog_pdo->prepare("DELETE FROM comments WHERE post_id = ?");
    $stmt->execute([$id]);
    header('Location: posts.php');
    exit;
}

if (isset($_GET['edit-id'])) {
    $id = (int) $_GET["edit-id"];
    $stmt = $blog_pdo->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
	
    if (empty($id) || !$row) {
        header('Location: posts.php');
        exit;
    }
	
	if (isset($_POST['submit'])) {
        $title = $_POST['title'];
		$slug = generateSeoURL($title);
        $image = $row['image'];
        $active = $_POST['active'];
		$featured = $_POST['featured'];
        $category_id = (int) $_POST['category_id'];
        $content = htmlspecialchars($_POST['content']);
		
		$date = date($settings['date_format']);
		$time = date('H:i');
        
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
                $string = "0123456789wsderfgtyhjuk";
                $new_string = str_shuffle($string);
                $imageFileType = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
                $location = "../../client-dashboard/blog/uploads/posts/image_$new_string.$imageFileType";
                move_uploaded_file($_FILES["image"]["tmp_name"], $location);
                $image = 'client-dashboard/blog/uploads/posts/image_' . $new_string . '.' . $imageFileType;
            }
        }
        
        $stmt = $blog_pdo->prepare("UPDATE posts SET title = ?, slug = ?, image = ?, active = ?, featured = ?, date = ?, time = ?, category_id = ?, content = ? WHERE id = ?");
        $stmt->execute([$title, $slug, $image, $active, $featured, $date, $time, $category_id, $content, $id]);
        header('Location: posts.php');
        exit;
    }
}
?>
<?=template_admin_header('Blog Posts', 'blog')?>

<?=generate_breadcrumbs([
    ['title' => 'Admin Dashboard', 'url' => '../index.php'],
    ['title' => 'Blog', 'url' => 'blog_dash.php'],
    ['title' => 'Posts', 'url' => '']
])?>

<div class="content-title mb-3">
    <div class="title">
       <i class="fa-solid fa-edit"></i>
        <div class="txt">
            <h2>Blog Posts</h2>
            <p>Manage blog posts</p>
        </div>
    </div>
</div>

<?php if (isset($_GET['edit-id'])): ?>
<div class="form-professional">
	<div class="content-block">
		<h3>Edit Post</h3>         
			<form name="post_form" action="" method="post" enctype="multipart/form-data" style="max-width: 900px;">
				<p>
					<label>Title</label>
					<input name="title" id="title" type="text" value="<?= htmlspecialchars($row['title']) ?>" style="width: 100%;" oninput="countText()" required>
					<i>For best SEO keep title under 50 characters.</i>
					<label for="characters">Characters: </label>
					<span id="characters"><?= strlen($row['title']) ?></span><br>
				</p>
				<p>
					<label>Image</label><br />
<?php if ($row['image'] != ''): ?>
					<img src="../../<?= htmlspecialchars($row['image']) ?>" width="100px" height="100px" style="object-fit: cover; border-radius: 5px;" /><br /><br />
<?php endif; ?>
					<input type="file" name="image" />
				</p>
				<p>
				<label>Active</label><br />
				<select name="active" style="width: 100%;" required>
					<option value="Yes" <?= $row['active'] == "Yes" ? 'selected' : '' ?>>Yes</option>
					<option value="No" <?= $row['active'] == "No" ? 'selected' : '' ?>>No</option>
				</select>
				</p>
				<p>
					<label>Featured</label><br />
					<select name="featured" style="width: 100%;" required>
						<option value="Yes" <?= $row['featured'] == "Yes" ? 'selected' : '' ?>>Yes</option>
						<option value="No" <?= $row['featured'] == "No" ? 'selected' : '' ?>>No</option>
					</select>
				</p>
				<p>
					<label>Category</label><br />
					<select name="category_id" style="width: 100%;" required>
<?php
$stmt = $blog_pdo->query("SELECT * FROM categories");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($categories as $cat) {
	$selected = ($row['category_id'] == $cat['id']) ? 'selected' : '';
	echo '<option value="' . $cat['id'] . '" ' . $selected . '>' . htmlspecialchars($cat['category']) . '</option>';
}
?>
					</select>
				</p>
				<p>
					<label>Content</label>
					<textarea name="content" id="summernote" rows="8" style="width: 100%;" required><?= html_entity_decode($row['content']) ?></textarea>
				</p>

				<input type="submit" class="btn" name="submit" value="Save" />
			</form>
	</div>
</div>
<?php endif; ?>

	<div class="content-block">
		<h3>All Posts</h3>
		<a href="add_post.php" class="btn btn-primary" style="margin-bottom: 1rem;"><i class="fa fa-plus"></i> Add Post</a>
        <div class="table">

			<table id="dt-basic" width="100%">
				<thead>
					<tr>
						<td>Image</td>
						<td>Title</td>
						<td class="responsive-hidden">Author</td>
						<td class="responsive-hidden">Date</td>
						<td>Active</td>
						<td class="responsive-hidden">Category</td>
						<td class="align-center">Action</td>
					</tr>
				</thead>
				<tbody>
<?php
$stmt = $blog_pdo->query("SELECT * FROM posts ORDER BY id DESC");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$posts) {
    echo '<tr><td colspan="7" class="no-results">No posts found</td></tr>';
}

foreach ($posts as $row) {
    $stmt2 = $blog_pdo->prepare("SELECT category FROM categories WHERE id = ?");
    $stmt2->execute([$row['category_id']]);
    $cat = $stmt2->fetch(PDO::FETCH_ASSOC);
    $category_name = $cat ? htmlspecialchars($cat['category']) : 'Uncategorized';
	
	$featured = '';
	if($row['featured'] == "Yes") {
		$featured = ' <span class="badge green">Featured</span>';
	}
	
	$active_badge = $row['active'] == "Yes" ? '<span class="badge green">Yes</span>' : '<span class="badge red">No</span>';
	
	// Get author name
	$author_name = '-';
	if (!empty($row['author_id'])) {
	    if (function_exists('post_author')) {
	        $author_name = htmlspecialchars(post_author($row['author_id']));
	    } else {
	        // Fallback if function not loaded
	        $stmt3 = $blog_pdo->prepare("SELECT username FROM users WHERE id = ? LIMIT 1");
	        $stmt3->execute([$row['author_id']]);
	        $author = $stmt3->fetch(PDO::FETCH_ASSOC);
	        if ($author) {
	            $author_name = htmlspecialchars($author['username']);
	        }
	    }
	}
	
    echo '
					<tr>
						<td class="img">';
    if ($row['image'] != '') {
        // Normalize image path - remove leading slash and ensure proper path
        $image_path = ltrim($row['image'], '/');
        // If path doesn't include client-dashboard/blog, prepend it
        if (strpos($image_path, 'client-dashboard/blog/') !== 0 && strpos($image_path, 'uploads/') === 0) {
            $image_path = 'client-dashboard/blog/' . $image_path;
        }
        echo '<img src="' . BASE_URL . htmlspecialchars($image_path) . '" width="45px" height="45px" style="object-fit: cover; border-radius: 5px;" />';
    }
    echo '</td>
						<td>' . htmlspecialchars($row['title']) . $featured . '</td>
						<td class="responsive-hidden">' . $author_name . '</td>
						<td class="responsive-hidden" data-sort="' . strtotime($row['date']) . '">' . date($settings['date_format'], strtotime($row['date'])) . '</td>
						<td>' . $active_badge . '</td>
						<td class="responsive-hidden">' . $category_name . '</td>
						<td class="actions">
						    <div class="table-dropdown">
                                <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M8 256a56 56 0 1 1 112 0A56 56 0 1 1 8 256zm160 0a56 56 0 1 1 112 0 56 56 0 1 1 -112 0zm216-56a56 56 0 1 1 0 112 56 56 0 1 1 0-112z"/></svg>
                                <div class="table-dropdown-items">
                                    <a href="?edit-id=' . $row['id'] . '">
                                        <span class="icon">
                                            <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M471.6 21.7c-21.9-21.9-57.3-21.9-79.2 0L362.3 51.7l97.9 97.9 30.1-30.1c21.9-21.9 21.9-57.3 0-79.2L471.6 21.7zm-299.2 220c-6.1 6.1-10.8 13.6-13.5 21.9l-29.6 88.8c-2.9 8.6-.6 18.1 5.8 24.6s15.9 8.7 24.6 5.8l88.8-29.6c8.2-2.7 15.7-7.4 21.9-13.5L437.7 172.3 339.7 74.3 172.4 241.7zM96 64C43 64 0 107 0 160V416c0 53 43 96 96 96H352c53 0 96-43 96-96V320c0-17.7-14.3-32-32-32s-32 14.3-32 32v96c0 17.7-14.3 32-32 32H96c-17.7 0-32-14.3-32-32V160c0-17.7 14.3-32 32-32h96c17.7 0 32-14.3 32-32s-14.3-32-32-32H96z"/></svg>
                                        </span>
                                        Edit
                                    </a>
                                    <a class="red" href="?delete-id=' . $row['id'] . '" onclick="return confirm(\'Are you sure you want to delete this post and all its comments?\');">
                                        <span class="icon">
                                            <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z"/></svg>
                                        </span>    
                                        Delete
                                    </a>
                                </div>
                            </div>
						</td>
					</tr>
	';
}
?>
				</tbody>
			</table>
        </div>
	</div>

<?=template_admin_footer('
<script>
$(document).ready(function() {
	if (typeof $.fn.dataTable !== "undefined") {
		$("#dt-basic").dataTable({
			"responsive": true,
			"order": [[ 3, "desc" ]],
			"language": {
				"paginate": {
				  "previous": "<i class=\"fa fa-angle-left\"></i>",
				  "next": "<i class=\"fa fa-angle-right\"></i>"
				}
			}
		});
	}
	
	if (typeof $.fn.summernote !== "undefined") {
		$("#summernote").summernote({height: 350});
		
		var noteBar = $(".note-toolbar");
		noteBar.find("[data-toggle]").each(function() {
			$(this).attr("data-bs-toggle", $(this).attr("data-toggle")).removeAttr("data-toggle");
		});
	}
	
	window.countText = function() {
		var text = document.getElementById("title").value;
		document.getElementById("characters").textContent = text.length;
	}
});
</script>
')?>