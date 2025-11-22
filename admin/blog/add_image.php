<?php
require 'assets/includes/admin_config.php';

if (isset($_POST['add'])) {
    $title = trim($_POST['title']);
    $active = $_POST['active'];
	$album_id = (int) $_POST['album_id'];
    $description = $_POST['description'];
    
    $image = '';
    
    if (@$_FILES['avafile']['name'] != '') {
        $target_dir    = "uploads/gallery/";
        $target_file   = $target_dir . basename($_FILES["avafile"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        $uploadOk = 1;
        
        // Check if image file is a actual image or fake image
        $check = getimagesize($_FILES["avafile"]["tmp_name"]);
        if ($check !== false) {
            $uploadOk = 1;
        } else {
            echo '<div class="alert alert-danger">The file is not an image.</div>';
            $uploadOk = 0;
        }
        
        // Check file size
        if ($_FILES["avafile"]["size"] > 10000000) {
            echo '<div class="alert alert-warning">Sorry, your file is too large.</div>';
            $uploadOk = 0;
        }
        
        if ($uploadOk == 1) {
            $string     = "0123456789wsderfgtyhjuk";
            $new_string = str_shuffle($string);
            $location   = "../uploads/gallery/image_$new_string.$imageFileType";
            move_uploaded_file($_FILES["avafile"]["tmp_name"], $location);
            $image = 'uploads/gallery/image_' . $new_string . '.' . $imageFileType . '';
        }
    }
    
    $stmt = $blog_pdo->prepare("INSERT INTO `gallery` (album_id, title, image, description, active) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$album_id, $title, $image, $description, $active]);
    header('Location: gallery.php');
    exit;
}
?>
<?=template_admin_header('Add Image', 'blog')?>

<?=generate_breadcrumbs([
    ['title' => 'Admin Dashboard', 'url' => '../index.php'],
    ['title' => 'Blog', 'url' => 'blog_dash.php'],
    ['title' => 'Gallery', 'url' => 'gallery.php'],
    ['title' => 'Add Image', 'url' => '']
])?>

<div class="content-title">
    <div class="title">
       <i class="fa-solid fa-camera-retro"></i>
        <div class="txt">
            <h2>Add Image</h2>
            <p>Upload a new gallery image</p>
        </div>
    </div>
</div>

<div class="form-professional">
	<div class="card">
        <h6 class="card-header">Add Image</h6>         
            <div class="card-body">
                <form action="" method="post" enctype="multipart/form-data">
					<p>
						<label>Title</label>
						<input class="form-control" name="title" value="" type="text" required>
					</p>
					<p>
						<label>Image</label>
						<input type="file" name="avafile" class="form-control" required />
					</p>
					<p>
						<label>Active</label><br />
						<select name="active" class="form-select" required>
							<option value="Yes" selected>Yes</option>
							<option value="No">No</option>
                        </select>
					</p>
					<p>
						<label>Album</label><br />
						<select name="album_id" class="form-select" required>
<?php
$stmt = $blog_pdo->query("SELECT * FROM `albums`");
while ($rw = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo '
                            <option value="' . $rw['id'] . '">' . htmlspecialchars($rw['title']) . '</option>
									';
}
?>
						</select>
					</p>
					<p>
						<label>Description</label>
						<textarea class="form-control" id="summernote" name="description"></textarea>
					</p>
                                
					<input type="submit" name="add" class="btn btn-primary col-12" value="Add" />
				</form>                            
            </div>
        </div>
</div>

<?=template_admin_footer('
<script>
$(document).ready(function() {
	$("#summernote").summernote({height: 350});
	
	var noteBar = $(".note-toolbar");
		noteBar.find("[data-toggle]").each(function() {
		$(this).attr("data-bs-toggle", $(this).attr("data-toggle")).removeAttr("data-toggle");
	});
});
</script>
')?>