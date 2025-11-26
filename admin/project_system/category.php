<?php
require 'assets/includes/admin_config.php';
// Default category values
$category = [
    'title' => ''
];
if (isset($_GET['id'])) {
    // Retrieve the category from the database
    $stmt = $pdo->prepare('SELECT * FROM project_categories WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    // ID param exists, edit an existing category
    $page = 'Edit';
    if (isset($_POST['submit'])) {
        // Update the category
        $stmt = $pdo->prepare('UPDATE project_categories SET title = ? WHERE id = ?');
        $stmt->execute([ $_POST['title'], $_GET['id'] ]);
        header('Location: categories.php?success_msg=2');
        exit;
    }
    if (isset($_POST['delete'])) {
        // Delete the category
        $stmt = $pdo->prepare('DELETE FROM project_categories WHERE id = ?');
        $stmt->execute([ $_GET['id'] ]);
        header('Location: categories.php?success_msg=3');
        exit;
    }
} else {
    // Create a new category
    $page = 'Create';
    if (isset($_POST['submit'])) {
        $stmt = $pdo->prepare('INSERT INTO project_categories (title) VALUES (?)');
        $stmt->execute([ $_POST['title'] ]);
        header('Location: categories.php?success_msg=1');
        exit;
    }
}
?>
<?=template_admin_header($page . ' Project Category', 'ticketing', 'project')?>
<div class="content-title mb-3">
    <div class="title">
        <div class="icon alt">
            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M10,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V8C22,6.89 21.1,6 20,6H12L10,4Z" /></svg>
        </div>
        <div class="txt">
            <h2 class="responsive-width-100"><?=$page?> Project Category</h2>
            <p>Manage project ticket categories</p>
        </div>
    </div>
</div>

<form action="" method="post">
    <div class="form-professional">
        
        <div class="form-section">
            <h3 class="section-title">Category Information</h3>
            
            <div class="form-group">
                <label for="title">Category Title <span class="required">*</span></label>
                <input id="title" type="text" name="title" placeholder="Enter category name" value="<?=htmlspecialchars($category['title'], ENT_QUOTES)?>" required>
            </div>
        </div>

        <div class="form-actions">
            <a href="categories.php" class="btn btn-secondary">Cancel</a>
            <?php if ($page == 'Edit'): ?>
            <input type="submit" name="delete" value="Delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this category?')">
            <?php endif; ?>
            <input type="submit" name="submit" value="<?=$page == 'Edit' ? 'Update' : 'Create'?> Category" class="btn btn-success">
        </div>

    </div>
</form>

<?=template_admin_footer()?>