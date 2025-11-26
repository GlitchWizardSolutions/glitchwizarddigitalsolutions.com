<?php
require 'assets/includes/admin_config.php';

if (isset($_GET['up-id'])) {
    $id = (int) $_GET["up-id"];
	
    $querype = $blog_pdo->prepare("SELECT id FROM `menu` WHERE id<? ORDER BY id DESC LIMIT 1");
    $querype->execute([$id]);
	$rowpe   = $querype->fetch(PDO::FETCH_ASSOC);
	$prev_id = $rowpe['id'];
	
	$queryce = $blog_pdo->prepare("SELECT id FROM `menu` WHERE id=? LIMIT 1");
    $queryce->execute([$id]);
	$rowce   = $queryce->fetch(PDO::FETCH_ASSOC);
	$curr_id = $rowce['id'];
	
	$update_sql = $blog_pdo->prepare("UPDATE menu SET id='9999999' WHERE id=?");
    $update_sql->execute([$prev_id]);
	$update_sql = $blog_pdo->prepare("UPDATE menu SET id=? WHERE id=?");
    $update_sql->execute([$prev_id, $curr_id]);
	$update_sql = $blog_pdo->prepare("UPDATE menu SET id=? WHERE id='9999999'");
    $update_sql->execute([$curr_id]);
}

if (isset($_GET['down-id'])) {
    $id = (int) $_GET["down-id"];
	
    $queryne = $blog_pdo->prepare("SELECT id FROM `menu` WHERE id>? ORDER BY id ASC LIMIT 1");
    $queryne->execute([$id]);
	$rowne   = $queryne->fetch(PDO::FETCH_ASSOC);
	$next_id = $rowne['id'];
	
	$queryce = $blog_pdo->prepare("SELECT id FROM `menu` WHERE id=? LIMIT 1");
    $queryce->execute([$id]);
	$rowce   = $queryce->fetch(PDO::FETCH_ASSOC);
	$curr_id = $rowce['id'];
	
	$update_sql = $blog_pdo->prepare("UPDATE menu SET id='9999998' WHERE id=?");
    $update_sql->execute([$next_id]);
	$update_sql = $blog_pdo->prepare("UPDATE menu SET id=? WHERE id=?");
    $update_sql->execute([$next_id, $curr_id]);
	$update_sql = $blog_pdo->prepare("UPDATE menu SET id=? WHERE id='9999998'");
    $update_sql->execute([$curr_id]);
}

if (isset($_GET['delete-id'])) {
    $id    = (int) $_GET["delete-id"];
    $query = $blog_pdo->prepare("DELETE FROM `menu` WHERE id=?");
    $query->execute([$id]);
}
?>
<?=template_admin_header('Menu Editor', 'blog')?>

<?=generate_breadcrumbs([
    ['title' => 'Admin Dashboard', 'url' => '../index.php'],
    ['title' => 'Blog', 'url' => 'blog_dash.php'],
    ['title' => 'Menu Editor', 'url' => '']
])?>

<div class="content-title mb-3">
    <div class="title">
       <i class="fa-solid fa-bars"></i>
        <div class="txt">
            <h2>Menu Editor</h2>
            <p>Manage blog menu items</p>
        </div>
    </div>
</div>
	  
<?php
if (isset($_GET['edit-id'])) {
    $id  = (int) $_GET["edit-id"];
    $sql = $blog_pdo->prepare("SELECT * FROM `menu` WHERE id = ?");
    $sql->execute([$id]);
    $row = $sql->fetch(PDO::FETCH_ASSOC);
    if (empty($id)) {
        echo '<meta http-equiv="refresh" content="0; url=menu_editor.php">';
		exit;
    }
    if (!$row) {
        echo '<meta http-equiv="refresh" content="0; url=menu_editor.php">';
		exit;
    }
	
	if (isset($_POST['submit'])) {
        $page    = $_POST['page'];
        $path    = $_POST['path'];
        $fa_icon = $_POST['fa_icon'];
        
		$update_sql = $blog_pdo->prepare("UPDATE menu SET page=?, path=?, fa_icon=? WHERE id=?");
        $update_sql->execute([$page, $path, $fa_icon, $id]);
        echo '<meta http-equiv="refresh" content="0;url=menu_editor.php">';
    }
?>
<div class="form-professional">
            <div class="card mb-3">
              <h6 class="card-header">Edit Menu</h6>         
                  <div class="card-body">
                  <form action="" method="post">
                  <p>
                  	<label>Page</label>
                  	<input name="page" class="form-control" type="text" value="<?php
echo $row['page'];
?>" required>
                  </p>
                  <p>
                  	<label>Path (Link)</label>
                  	<input name="path" class="form-control" type="text" value="<?php
echo $row['path'];
?>" required>
                  </p>
                  <p>
                  	<label>Font Awesome 5 Icon</label>
                  	<input name="fa_icon" class="form-control" type="text" value="<?php
echo $row['fa_icon'];
?>">
                  </p>
                  <input type="submit" class="btn btn-success col-12" name="submit" value="Save" />
                  </form>
                  </div>
            </div>
</div>
<?php
}
?>

            <div class="card">
              <h6 class="card-header">Menu Editor</h6>         
                  <div class="card-body">
				  <a href="add_menu.php" class="btn btn-primary col-12"><i class="fa fa-edit"></i> Add Menu</a><br /><br />

            <table class="table table-border table-hover">
                <thead>
				<tr>
                    <th>Order</th>
                    <th>Page</th>
					<th>Path</th>
					<th>Actions</th>
                </tr>
				</thead>
<?php
$query = $blog_pdo->query("SELECT * FROM menu ORDER BY id ASC");

$queryli  = $blog_pdo->query("SELECT * FROM menu ORDER BY id DESC LIMIT 1");
$rowli    = $queryli->fetch(PDO::FETCH_ASSOC);
$last_id  = $rowli['id'];

$first = true;
while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
	
        echo '
                <tr>
	                <td>' . $row['id'] . '</td>
	                <td><i class="fa ' . $row['fa_icon'] . '"></i> ' . $row['page'] . '</td>
					<td>' . $row['path'] . '</td>
					<td>
';
if ($first == false) {
	echo '
						<a href="?up-id=' . $row['id'] . '" title="Move Up" class="btn btn-secondary btn-sm"><i class="fa fa-arrow-up"></i></a>
	';
}
if ($row['id'] != $last_id) {
	echo '
						<a href="?down-id=' . $row['id'] . '" title="Move Down" class="btn btn-secondary btn-sm"><i class="fa fa-arrow-down"></i></a>
	';
}
echo '
					    <a href="?edit-id=' . $row['id'] . '" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i> Edit</a>
						<a href="?delete-id=' . $row['id'] . '" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i> Delete</a>
					</td>
                </tr>
';
$first = false;
    }
?>
            </table>
            </div>
        </div>
<?php
include "footer.php";
?>