<?php
require 'assets/includes/admin_config.php';

$error_message = '';

if (isset($_POST['add'])) {
    $category = trim($_POST['category']);
	$slug = generateSeoURL($category, 0);
    
    $stmt = $blog_pdo->prepare("SELECT * FROM `categories` WHERE category = ? LIMIT 1");
	$stmt->execute([$category]);
	if ($stmt->fetch(PDO::FETCH_ASSOC)) {
		$error_message = 'Category with this name has already been added.';
    } else {
		$stmt = $blog_pdo->prepare("INSERT INTO categories (category, slug) VALUES (?, ?)");
		$stmt->execute([$category, $slug]);
		header('Location: categories.php');
		exit;
	}
}
?>
<?=template_admin_header('Add Category', 'blog')?>

<?=generate_breadcrumbs([
    ['title' => 'Admin Dashboard', 'url' => '../index.php'],
    ['title' => 'Blog', 'url' => 'blog_dash.php'],
    ['title' => 'Categories', 'url' => 'categories.php'],
    ['title' => 'Add Category', 'url' => '']
])?>

<div class="content-title">
    <div class="title">
       <i class="fa-solid fa-list-ol"></i>
        <div class="txt">
            <h2>Add Category</h2>
            <p>Create a new blog category</p>
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
              <h6 class="card-header">Add Category</h6>         
                  <div class="card-body">
                      <form action="" method="post">
                      <p>
                          <label>Title</label>
                          <input class="form-control" name="category" value="" type="text" required>
                      </p>
					  <div class="form-actions">
                          <input type="submit" name="add" class="btn btn-primary col-12" value="Add" />
                      </div>
                     </form>                           
                  </div>
            </div>
</div>
<?=template_admin_footer()?>