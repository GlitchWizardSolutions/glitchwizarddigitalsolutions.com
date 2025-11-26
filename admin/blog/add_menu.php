<?php
require 'assets/includes/admin_config.php';

if (isset($_POST['add'])) {
    $page    = $_POST['page'];
    $path    = $_POST['path'];
    $fa_icon = $_POST['fa_icon'];
    
	$add_sql = $blog_pdo->prepare("INSERT INTO menu (page, path, fa_icon) VALUES (?, ?, ?)");
    $add_sql->execute([$page, $path, $fa_icon]);

    header('Location: menu_editor.php');
    exit;
}
?>
<?=template_admin_header('Add Menu', 'blog')?>

<?=generate_breadcrumbs([
    ['title' => 'Admin Dashboard', 'url' => '../index.php'],
    ['title' => 'Blog', 'url' => 'blog_dash.php'],
    ['title' => 'Menu Editor', 'url' => 'menu_editor.php'],
    ['title' => 'Add Menu', 'url' => '']
])?>

<div class="content-title mb-3">
    <div class="title">
       <i class="fa-solid fa-bars"></i>
        <div class="txt">
            <h2>Add Menu</h2>
            <p>Create new menu item</p>
        </div>
    </div>
</div>

<div class="form-professional">
            <div class="card">
              <h6 class="card-header">Add Menu</h6>         
                  <div class="card-body">
                        <form action="" method="post">
							<p>
								<label>Title</label>
								<input class="form-control" name="page" value="" type="text" required>
							</p>
							<p>
								<label>Path (Link)</label>
								<input class="form-control" name="path" value="" type="text" required>
							</p>
                            <p>
								<label>Font Awesome 5 Icon</label>
								<input class="form-control" name="fa_icon" value="" type="text">
							</p>
							<div class="form-actions">
                                <input type="submit" name="add" class="btn btn-primary col-12" value="Add" />
                            </div>
						</form>                       
                  </div>
            </div>
</div>
<?=template_admin_footer()?>