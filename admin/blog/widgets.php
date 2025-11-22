<?php
//2025-06-24 Production
error_log('Loading Page: /admin/blog/widgets ');
require 'assets/includes/admin_config.php';
if (isset($_GET['delete-id'])) {
    $id     = (int) $_GET["delete-id"];
    // Delete the account
    $stmt = $blog_pdo->prepare('DELETE FROM widgets WHERE id = ?');
    $stmt->execute([$id]);
}

?>
<?=template_admin_header('Blog Widgets', 'blog')?>

<?=generate_breadcrumbs([
    ['title' => 'Admin Dashboard', 'url' => '../index.php'],
    ['title' => 'Blog', 'url' => 'blog_dash.php'],
    ['title' => 'Widgets', 'url' => '']
])?>

<div class="content-title">
    <div class="title">
       <i class="fa-solid fa-archive"></i>
        <div class="txt">
            <h2>Blog Widgets</h2>
            <p>Manage blog widgets</p>
        </div>
    </div>
</div>
<?php
if (isset($_GET['edit-id'])) {
    $id  = (int) $_GET["edit-id"];
    $stmt = $blog_pdo->prepare("SELECT * FROM `widgets` WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_widgets = $blog_pdo->query('SELECT COUNT(*) AS total FROM widgets')->fetchColumn();

    if (empty($id) ||  $total_widgets == 0) {
        echo '<meta http-equiv="refresh" content="0; url=widgets.php">';
       	exit;
    }
	if (isset($_POST['submit'])) {
        $title    = addslashes($_POST['title']);
	    $position = addslashes($_POST['position']);
        $content  = htmlspecialchars($_POST['content']);
 
         $stmt =$blog_pdo->prepare('UPDATE widgets SET title= ?, position= ?, content= ? WHERE id= ?');
         $stmt->execute([
         $title, $position, $content, $id]);

        echo '<meta http-equiv="refresh" content="0;url=widgets.php">';
    }   

?>
            <div class="card mb-3">
              <h6 class="card-header">Edit Widget</h6>         
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
				  <div class="form-group">
                      <label>Position:</label>
                      <select class="form-select" name="position" required>
						<option value="Sidebar" <?php
    if ($row['position'] == "Sidebar") {
        echo 'selected';
    }
?>>Sidebar</option>
                        <option value="Header" <?php
    if ($row['position'] == "Header") {
        echo 'selected';
    }
?>>Header</option>
 
                      </select>
                  </div><br />
				  
                  <input type="submit" class="btn btn-primary col-12" name="submit" value="Save" />
                  </form>
                  </div>
            </div>
</div>
<?php
}
?>

<div class="form-professional">
            <div class="card mb-3">
              <h6 class="card-header">Widgets</h6>         
                  <div class="card-body">
				  

            <table class="table table-border table-hover">
                <thead>
				<tr>
                    <th>Title</th>
					<th>Position</th>
					<th>Actions</th>
                </tr>
				</thead>
<?php
$stmt =$blog_pdo->prepare("SELECT * FROM widgets ORDER BY id DESC");
$stmt->execute();
$widgets_by_id = $stmt->fetchAll(PDO::FETCH_ASSOC);
 foreach($widgets_by_id as $row){ 
    echo '
                <tr>
	                <td>' . $row['title'] . '</td>
					<td>' . $row['position'] . '</td>
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
			  
<script>
$(document).ready(function() {
	$('#summernote').summernote({height: 350});
	
	var noteBar = $('.note-toolbar');
		noteBar.find('[data-toggle]').each(function() {
		$(this).attr('data-bs-toggle', $(this).attr('data-toggle')).removeAttr('data-toggle');
	});
});
</script>
<?php
include "footer.php";
?>