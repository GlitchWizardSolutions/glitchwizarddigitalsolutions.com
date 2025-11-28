<?php
require 'assets/includes/admin_config.php';

if (isset($_GET['delete-id'])) {
    $id = (int) $_GET["delete-id"];
    $stmt = $blog_pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $stmt = $blog_pdo->prepare("DELETE FROM posts WHERE category_id = ?");
    $stmt->execute([$id]);
    header('Location: categories.php');
    exit;
}

if (isset($_GET['edit-id'])) {
    $id = (int) $_GET["edit-id"];
    $stmt = $blog_pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
	
    if (empty($id) || !$row) {
        header('Location: categories.php');
		exit;
    }
    
    if (isset($_POST['submit'])) {
        $category = $_POST['category'];
		$slug = generateSeoURL($category, 0);
		
		$stmt = $blog_pdo->prepare("SELECT * FROM categories WHERE category = ? AND id != ? LIMIT 1");
		$stmt->execute([$category, $id]);
		if ($stmt->rowCount() > 0) {
			$error_msg = 'Category with this name has already been added.';
		} else {
			$stmt = $blog_pdo->prepare("UPDATE categories SET category = ?, slug = ? WHERE id = ?");
			$stmt->execute([$category, $slug, $id]);
			header('Location: categories.php');
			exit;
		}
    }
}
?>
<?=template_admin_header('Blog Categories', 'blog')?>

<?=generate_breadcrumbs([
    ['title' => 'Admin Dashboard', 'url' => '../index.php'],
    ['title' => 'Blog', 'url' => 'blog_dash.php'],
    ['title' => 'Categories', 'url' => '']
])?>

<div class="content-title mb-3">
    <div class="title">
       <i class="fa-solid fa-list-ol"></i>
        <div class="txt">
            <h2>Blog Categories</h2>
            <p>Manage blog categories</p>
        </div>
    </div>
</div>

<?php if (isset($_GET['edit-id'])): ?>
<div class="form-professional">
            <div class="content-block">
              <h3>Edit Category</h3>         
                      <form action="" method="post" style="max-width: 500px;">
						<?php if (isset($error_msg)): ?>
						<div class="msg error">
							<i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_msg) ?>
						</div>
						<?php endif; ?>
						<p>
                          <label>Category</label>
                          <input name="category" type="text" value="<?= htmlspecialchars($row['category']) ?>" style="width: 100%;" required>
						</p>
                        <input type="submit" class="btn btn-primary" name="submit" value="Save" />
                      </form>
              </div>
</div>
<?php endif; ?>

			<div class="content-block">
              <h3>All Categories</h3>
			  <a href="add_category.php" class="btn btn-primary" style="margin-bottom: 1rem;"><i class="fa fa-plus"></i> Add Category</a>
              <div class="table">

            <table width="100%">
                <thead>
				<tr>
                    <td>Category</td>
					<td class="align-center">Action</td>
                </tr>
				</thead>
				<tbody>
<?php
$stmt = $blog_pdo->query("SELECT * FROM categories ORDER BY category ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$categories) {
    echo '<tr><td colspan="2" class="no-results">No categories found</td></tr>';
}

foreach ($categories as $row) {
    echo '
                <tr>
	                <td>' . htmlspecialchars($row['category']) . '</td>
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
                                <a class="red" href="?delete-id=' . $row['id'] . '" onclick="return confirm(\'Are you sure you want to delete this category and all its posts?\');">
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

<?=template_admin_footer()?>