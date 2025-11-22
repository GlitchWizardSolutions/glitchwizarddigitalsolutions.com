<?php
require 'assets/includes/admin_config.php';

if (isset($_GET['delete-id'])) {
    $id    = (int) $_GET["delete-id"];
    $query = $blog_pdo->prepare("DELETE FROM `albums` WHERE id=?");
    $query->execute([$id]);
    $query = $blog_pdo->prepare("DELETE FROM `galery` WHERE album_id=?");
    $query->execute([$id]);
}
?>
<?=template_admin_header('Albums', 'blog')?>

<?=generate_breadcrumbs([
    ['title' => 'Admin Dashboard', 'url' => '../index.php'],
    ['title' => 'Blog', 'url' => 'blog_dash.php'],
    ['title' => 'Albums', 'url' => '']
])?>

<div class="content-title">
    <div class="title">
       <i class="fa-solid fa-images"></i>
        <div class="txt">
            <h2>Albums</h2>
            <p>Manage photo albums</p>
        </div>
    </div>
</div>
	  
<?php
if (isset($_GET['edit-id'])) {
    $id  = (int) $_GET["edit-id"];
    $sql = $blog_pdo->prepare("SELECT * FROM `albums` WHERE id = ?");
    $sql->execute([$id]);
    $row = $sql->fetch(PDO::FETCH_ASSOC);
    if (empty($id)) {
        echo '<meta http-equiv="refresh" content="0; url=albums.php">';
		exit;
    }
    if (!$row) {
        echo '<meta http-equiv="refresh" content="0; url=albums.php">';
		exit;
    }
    
    if (isset($_POST['submit'])) {
        $title    = $_POST['title'];
        $edit_sql = $blog_pdo->prepare("UPDATE albums SET title=? WHERE id=?");
        $edit_sql->execute([$title, $id]);
        echo '<meta http-equiv="refresh" content="0; url=albums.php">';
    }
?>
<div class="form-professional">
            <div class="card mb-3">
              <h6 class="card-header">Edit Album</h6>         
                  <div class="card-body">
                      <form action="" method="post">
						<p>
                          <label>Title</label>
                          <input class="form-control" class="form-control" name="title" type="text" value="<?php
    echo $row['title'];
?>" required>
						</p>
                        <input type="submit" class="btn btn-primary col-12" name="submit" value="Save" /><br />
                      </form>
                  </div>
            </div>
</div>
<?php
}
?>

            <div class="card">
              <h6 class="card-header">Albums</h6>         
                  <div class="card-body">
				  <a href="add_album.php" class="btn btn-primary col-12"><i class="fa fa-edit"></i> Add Album</a><br /><br />

            <table class="table table-border table-hover">
                <thead>
				<tr>
                    <th>Title</th>
					<th>Actions</th>
                </tr>
				</thead>
<?php
$sql    = "SELECT * FROM albums ORDER BY title ASC";
$result = $blog_pdo->query($sql);
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo '
                <tr>
	                <td>' . $row['title'] . '</td>
					<td>
					    <a href="?edit-id=' . $row['id'] . '" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i> Edit</a>
						<a href="?delete-id=' . $row['id'] . '" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i> Delete</a>
					</td>
                </tr>
';
}
?>
            </table>

                  </div>
              </div>
<?=template_admin_footer()?>