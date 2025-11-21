<?php
//2025-06-24 Production
error_log('Loading Page: /admin/blog/add+widget ');
require 'assets/includes/admin_config.php';
if (isset($_POST['add'])) {
    $title    = addslashes($_POST['title']);
    $content  = htmlspecialchars($_POST['content']);
	$position = addslashes($_POST['position']);
    
          // Insert the records
         $stmt = $blog_pdo->prepare('INSERT INTO `widgets` (title, content, position) VALUES (?, ?, ? )');
         $stmt->execute([$title, $content, $position]);
  
    echo '<meta http-equiv="refresh" content="0; url=widgets.php">';
}
?>
<?=template_admin_header('Add Widget',  'blog', 'blog')?>


<div class="content-title">
  <div class="title">
     <div class="icon">
        <i class="fa-solid fa-circle-info"></i>
       
       </div>
        <div class="txt">
            <h2>Blog - Add Widget</h2>
            <p>Add a Widget.</p>
        </div>
    </div>
    <div class="btns">
           <a href="https://glitchwizarddigitalsolutions.com/blog/" class="btn btn-primary" style='background:green'><i class="fa fa-eye"></i>&nbsp;  Go to Blog</a>
    </div>
</div>
<div class="content-header responsive-flex-column pad-top-5">
               <div class="card">
              <h6 class="card-header">Shortcuts</h6>         
                <div class="card-body">
     <center>
                    <a href="blog_dash.php" class="btn btn-sm btn-primary mt-2">Blog Dashboard</a>
					<a href="widgets.php" class="btn btn-sm btn-primary mt-2">View Widgets</a>
                  </center>
</div>
      </div>
            </div>



	<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
	 
	</div>

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