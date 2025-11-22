<?php
require 'assets/includes/admin_config.php';

$error_message = '';
$success_message = '';

if (isset($_POST['add'])) {
    $title   = trim($_POST['title']);
	$slug    = generateSeoURL($title, 0);
    $content = $_POST['content'];
    
	$stmt = $blog_pdo->prepare("SELECT * FROM `pages` WHERE title = ? LIMIT 1");
	$stmt->execute([$title]);
	if ($stmt->fetch(PDO::FETCH_ASSOC)) {
		$error_message = 'Page with this name has already been added.';
    } else {
		$stmt = $blog_pdo->prepare("INSERT INTO pages (title, slug, content) VALUES (?, ?, ?)");
		$stmt->execute([$title, $slug, $content]);
		
		$page_id = $blog_pdo->lastInsertId();
		$stmt = $blog_pdo->prepare("INSERT INTO menu (page, path, fa_icon) VALUES (?, ?, ?)");
		$stmt->execute([$title, 'page?name=' . $slug, 'fa-columns']);
		
		header('Location: pages.php');
		exit;
	}
}
?>
<?=template_admin_header('Add Page', 'blog')?>

<?=generate_breadcrumbs([
    ['title' => 'Admin Dashboard', 'url' => '../index.php'],
    ['title' => 'Blog', 'url' => 'blog_dash.php'],
    ['title' => 'Pages', 'url' => 'pages.php'],
    ['title' => 'Add Page', 'url' => '']
])?>

<div class="content-title">
    <div class="title">
       <i class="fa-solid fa-file-alt"></i>
        <div class="txt">
            <h2>Add Page</h2>
            <p>Create a new page</p>
        </div>
    </div>
</div>

<?php if ($error_message): ?>
<div class="alert alert-warning">
	<?=svg_icon_content()?> <?=htmlspecialchars($error_message)?>
</div>
<?php endif; ?>
	
<div class="form-professional">
            <div class="card">
              <h6 class="card-header">Add Page</h6>         
                  <div class="card-body">
                      <form action="" method="post">
						<p>
							<label>Title</label>
							<input class="form-control" name="title" value="" type="text" required>
						</p>
						<p>
							<label>Content</label>
							<textarea class="form-control" id="summernote" name="content" required></textarea>
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