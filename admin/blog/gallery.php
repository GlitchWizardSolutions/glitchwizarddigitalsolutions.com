<?php
require 'assets/includes/admin_config.php';

if (isset($_GET['delete-id'])) {
    $id = (int) $_GET["delete-id"];
    $stmt = $blog_pdo->prepare("DELETE FROM `gallery` WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: gallery.php');
    exit;
}
?>
<?=template_admin_header('Gallery', 'blog')?>

<?=generate_breadcrumbs([
    ['title' => 'Admin Dashboard', 'url' => '../index.php'],
    ['title' => 'Blog', 'url' => 'blog_dash.php'],
    ['title' => 'Gallery', 'url' => '']
])?>

<div class="content-title mb-3">
    <div class="title">
       <i class="fa-solid fa-images"></i>
        <div class="txt">
            <h2>Gallery</h2>
            <p>Manage gallery images</p>
        </div>
    </div>
</div>
	  
<?php
if (isset($_GET['edit-id'])) {
    $id = (int) $_GET["edit-id"];
    $stmt = $blog_pdo->prepare("SELECT * FROM `gallery` WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (empty($id) || !$row) {
        header('Location: gallery.php');
		exit;
    }
	
	if (isset($_POST['edit'])) {
        $title = trim($_POST['title']);
        $image = $row['image'];
        $active = $_POST['active'];
		$album_id = (int) $_POST['album_id'];
        $description = $_POST['description'];
        
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
        
        $stmt = $blog_pdo->prepare("UPDATE gallery SET album_id = ?, title = ?, image = ?, active = ?, description = ? WHERE id = ?");
        $stmt->execute([$album_id, $title, $image, $active, $description, $id]);
        header('Location: gallery.php');
        exit;
    }
?>

<div class="form-professional">
	  <div class="card mb-3">
		  <h6 class="card-header">Edit Image</h6>         
              <div class="card-body">
				  <form action="" method="post" enctype="multipart/form-data">
					  <p>
						  <label>Title</label>
						  <input class="form-control" class="form-control" name="title" type="text" value="<?php
    echo $row['title'];
?>" required>
					  </p>
					  <p>
						  <label>Image</label><br />
						  <img src="../<?php
    echo $row['image'];
?>" width="50px" height="50px" /><br />
						  <input type="file" name="avafile" class="form-control" />
					  </p>
					  <p>
						  <label>Active</label><br />
						  <select name="active" class="form-select">
							  <option value="Yes" <?php
    if ($row['active'] == "Yes") {
        echo 'selected';
    }
?>>Yes</option>
							  <option value="No" <?php
    if ($row['active'] == "No") {
        echo 'selected';
    }
?>>No</option>
                          </select>
					  </p>
					  <p>
						  <label>Album</label><br />
						  <select name="album_id" class="form-select" required>
<?php
    $stmt = $blog_pdo->query("SELECT * FROM `albums`");
    while ($rw = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$selected = "";
		if ($row['album_id'] == $rw['id']) {
			$selected = "selected";
		}
        echo '<option value="' . $rw['id'] . '" ' . $selected . '>' . htmlspecialchars($rw['title']) . '</option>';
    }
?>
						  </select>
						</p>
					  <p>
						  <label>Description</label>
						  <textarea class="form-control" id="summernote" name="description"><?php
    echo $row['description'];
?></textarea>
					  </p>

					  <input type="submit" class="btn btn-primary col-12" name="edit" value="Save" /><br />

				  </form>
			  </div>
	  </div>
<?php
}
?>

            <div class="card">
              <h6 class="card-header">Gallery</h6>         
                  <div class="card-body">
				  <a href="add_image.php" class="btn btn-primary col-12"><i class="fa fa-edit"></i> Add Image</a><br /><br />

            <table class="table table-border table-hover" id="dt-basic" width="100%">
                <thead>
				<tr>
                    <th>Image</th>
                    <th>Title</th>
					<th>Active</th>
					<th>Album</th>
					<th>Actions</th>
                </tr>
				</thead>
<?php
$stmt = $blog_pdo->query("SELECT * FROM gallery ORDER BY id DESC");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
	$album_id = $row['album_id'];
    $stmt2 = $blog_pdo->prepare("SELECT * FROM `albums` WHERE id = ?");
    $stmt2->execute([$album_id]);
    $cat = $stmt2->fetch(PDO::FETCH_ASSOC);
	
    echo '
                <tr>
	                <td><center><img src="../' . htmlspecialchars($row['image']) . '" width="100px" height="75px" style="object-fit: cover;" /></center></td>
	                <td>' . htmlspecialchars($row['title']) . '</td>
					<td>' . htmlspecialchars($row['active']) . '</td>
					<td>' . ($cat ? htmlspecialchars($cat['title']) : 'N/A') . '</td>
					<td>
					    <a href="?edit-id=' . $row['id'] . '" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i> Edit</a>
						<a href="?delete-id=' . $row['id'] . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure you want to delete this image?\')"><i class="fa fa-trash"></i> Delete</a>
					</td>
                </tr>
';
}
echo '</table>';

?>
                  </div>
            </div>

<?=template_admin_footer('
<script>
$(document).ready(function() {
	
	$("#dt-basic").dataTable( {
		"responsive": true,
		"order": [[ 1, "asc" ]],
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