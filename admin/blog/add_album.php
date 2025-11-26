<?php
require 'assets/includes/admin_config.php';

if (isset($_POST['add'])) {
    $title = $_POST['title'];
    
    $add_sql = $blog_pdo->prepare("INSERT INTO albums (title) VALUES (?)");
    $add_sql->execute([$title]);
    echo '<meta http-equiv="refresh" content="0; url=albums.php">';
}
?>
<?=template_admin_header('Add Album', 'blog')?>

<?=generate_breadcrumbs([
    ['title' => 'Admin Dashboard', 'url' => '../index.php'],
    ['title' => 'Blog', 'url' => 'blog_dash.php'],
    ['title' => 'Albums', 'url' => 'albums.php'],
    ['title' => 'Add Album', 'url' => '']
])?>

<div class="content-title mb-3">
    <div class="title">
       <i class="fa-solid fa-images"></i>
        <div class="txt">
            <h2>Add Album</h2>
            <p>Create new photo album</p>
        </div>
    </div>
</div>

<div class="form-professional">
            <div class="card">
              <h6 class="card-header">Add Album</h6>         
                  <div class="card-body">
                      <form action="" method="post">
                      <p>
                          <label>Title</label>
                          <input class="form-control" name="title" value="" type="text" required>
                      </p>
					  <div class="form-actions">
                          <input type="submit" name="add" class="btn btn-primary col-12" value="Add" />
                      </div>
                     </form>                           
                  </div>
            </div>
</div>
<?=template_admin_footer()?>