<?php
require 'assets/includes/admin_config.php';

if (isset($_GET['delete-id'])) {
    $id = (int) $_GET["delete-id"];
	
	$stmt = $blog_pdo->prepare("SELECT * FROM `pages` WHERE id = ? LIMIT 1");
	$stmt->execute([$id]);
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	
	if ($row) {
		$slug = $row['slug'];
		
		$stmt = $blog_pdo->prepare("DELETE FROM `menu` WHERE path = ?");
		$stmt->execute(['page?name=' . $slug]);
		
		$stmt = $blog_pdo->prepare("DELETE FROM `pages` WHERE id = ?");
		$stmt->execute([$id]);
		
		header('Location: pages.php');
		exit;
    }
}
?>
<?=template_admin_header('Pages', 'blog')?>

<?=generate_breadcrumbs([
    ['title' => 'Admin Dashboard', 'url' => '../index.php'],
    ['title' => 'Blog', 'url' => 'blog_dash.php'],
    ['title' => 'Pages', 'url' => '']
])?>

<div class="content-title">
    <div class="title">
       <i class="fa-solid fa-file-alt"></i>
        <div class="txt">
            <h2>Pages</h2>
            <p>Manage static pages</p>
        </div>
    </div>
</div>
	  
<?php
if (isset($_GET['edit-id'])) {
    $id = (int) $_GET["edit-id"];
    $stmt = $blog_pdo->prepare("SELECT * FROM `pages` WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
	$slug_old = $row ? $row['slug'] : '';
	
    if (empty($id) || !$row) {
        header('Location: pages.php');
		exit;
    }
	
	if (isset($_POST['submit'])) {
        $title = trim($_POST['title']);
		$slug = generateSeoURL($title, 0);
        $content = $_POST['content'];
        
		$stmt = $blog_pdo->prepare("SELECT * FROM `pages` WHERE title = ? AND id != ? LIMIT 1");
		$stmt->execute([$title, $id]);
		if ($stmt->fetch(PDO::FETCH_ASSOC)) {
		echo '
			<div class="alert alert-warning">
				' . svg_icon_content() . ' Page with this name has already been added.
			</div>';
		} else {
		
			$stmt = $blog_pdo->prepare("UPDATE pages SET title = ?, slug = ?, content = ? WHERE id = ?");
			$stmt->execute([$title, $slug, $content, $id]);
			
			$stmt = $blog_pdo->prepare("UPDATE menu SET page = ?, path = ? WHERE path = ?");
			$stmt->execute([$title, 'page?name=' . $slug, 'page?name=' . $slug_old]);
			
			header('Location: pages.php');
			exit;
		}
    }
?>
<div class="form-professional">
            <div class="card mb-3">
              <h6 class="card-header">Edit Page</h6>         
                  <div class="card-body">
					  <form action="" method="post">
						  <p>
						  	<label>Title</label>
						  	<input name="title" type="text" class="form-control" value="<?php
						      echo $row['title'];
?>" required>
						  </p>
						  <p>
						  	<label>Content</label>
						  	<textarea name="content" id="summernote" required><?php
						      echo html_entity_decode($row['content']);
?></textarea>
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
              <h6 class="card-header">Pages</h6>
                  <div class="card-body">
				  <a href="add_page.php" class="btn btn-primary col-12"><i class="fa fa-edit"></i> Add Page</a><br /><br />

            <table id="dt-basic" class="table table-border table-hover">
                <thead>
				<tr>
                    <th>Title</th>
					<th>Actions</th>
                </tr>
				</thead>
<?php
$stmt = $blog_pdo->query("SELECT * FROM pages ORDER BY id DESC");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  echo '
                <tr>
	                <td>' . htmlspecialchars($row['title']) . '</td>
					<td>
					    <a href="?edit-id=' . $row['id'] . '" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i> Edit</a>
						<a href="?delete-id=' . $row['id'] . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure you want to delete this page?\')"><i class="fa fa-trash"></i> Delete</a>
					</td>
                </tr>
';
}
?>
			</table>
                  </div>
              
            </div>

<?=template_admin_footer('
<script>
$(document).ready(function() {

	$("#dt-basic").dataTable( {
		"responsive": true,
		"language": {
			"paginate": {
			  "previous": "<i class=\"fa fa-angle-left\"></i>",
			  "next": "<i class=\"fa fa-angle-right\"></i>"
			}
		}
	} );
	
	$("#summernote").summernote({height: 350});
	
	var noteBar = $(".note-toolbar");
		noteBar.find("[data-toggle]").each(function() {
		$(this).attr("data-bs-toggle", $(this).attr("data-toggle")).removeAttr("data-toggle");
	});
} );
</script>
')?>