<?php
//2025-06-24 Production
error_log('Loading Page: /admin/blog/add+widget ');
require 'assets/includes/admin_config.php';
if (isset($_POST['add'])) {
    $title    = trim($_POST['title']);
    $content  = $_POST['content'];
	$position = $_POST['position'];
    
          // Insert the records
         $stmt = $blog_pdo->prepare('INSERT INTO `widgets` (title, content, position) VALUES (?, ?, ? )');
         $stmt->execute([$title, $content, $position]);
  
    header('Location: widgets.php');
    exit;
}
?>
<?=template_admin_header('Add Widget', 'blog')?>

<?=generate_breadcrumbs([
    ['title' => 'Admin Dashboard', 'url' => '../index.php'],
    ['title' => 'Blog', 'url' => 'blog_dash.php'],
    ['title' => 'Widgets', 'url' => 'widgets.php'],
    ['title' => 'Add Widget', 'url' => '']
])?>

<div class="content-title mb-3">
    <div class="title">
       <i class="fa-solid fa-archive"></i>
        <div class="txt">
            <h2>Add Widget</h2>
            <p>Create new blog widget</p>
        </div>
    </div>
</div>

<div class="form-professional">
	<div class="card">
        <h6 class="card-header">Add Widget</h6>         
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
					<div class="form-group">
						<label>Position:</label>
						<select class="form-select" name="position" required>
							<option value="Sidebar" selected>Sidebar</option>
							<option value="Header">Header</option>
							<option value="Footer">Footer</option>
						</select>
					</div><br />
					
					<input type="submit" name="add" class="btn btn-primary col-12" value="Add" />
				</form>                          
			</div>
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