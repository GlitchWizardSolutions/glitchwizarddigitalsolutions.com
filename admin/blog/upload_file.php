<?php
//2025-06-24 Production
error_log('Loading Page: /admin/blog/upload_files ');
require 'assets/includes/admin_config.php';
?> 

<?=template_admin_header('Upload File', 'blog')?>

<?=generate_breadcrumbs([
    ['title' => 'Admin Dashboard', 'url' => '../index.php'],
    ['title' => 'Blog', 'url' => 'blog_dash.php'],
    ['title' => 'Files', 'url' => 'files.php'],
    ['title' => 'Upload File', 'url' => '']
])?>

<div class="content-title mb-3">
    <div class="title">
       <i class="fa-solid fa-upload"></i>
        <div class="txt">
            <h2>Upload File</h2>
            <p>Upload files to server</p>
        </div>
    </div>
</div>

<div class="form-professional">    
            <div class="card">
              <h6 class="card-header">Upload File</h6>
                  <div class="card-body">
                    <form action="" method="post" enctype="multipart/form-data">
						<p>
							<label><b>File</b></label>
							<input type="file" name="file" class="form-control" required />
						</p>
						<div class="form-actions">
                            <input type="submit" name="upload" class="btn btn-primary col-12" value="Upload" />
                        </div>
                    </form>
<?php
if (isset($_POST['upload'])) {
    $file     = $_FILES['file'];
    $tmp_name = $_FILES['file']['tmp_name'];
    $name     = $_FILES['file']['name'];
    $date = date($settings['date_format']);
    $time = date('H:i');
    
    @$format = end(explode(".", $name));
    if ($format != "png" && $format != "gif" && $format != "jpeg" && $format != "jpg" 
		&& $format != "JPG" && $format != "PNG" && $format != "bmp" && $format != "GIF" 
		&& $format != "doc" && $format != "docx" && $format != "pdf" && $format != "txt" 
		&& $format != "rar" && $format != "html" && $format != "zip" && $format != "odt"
		&& $format != "rtf" && $format != "csv" && $format != "ods" && $format != "xls"
		&& $format != "xlsx" && $format != "odp" && $format != "ppt" && $format != "pptx"
		&& $format != "mp3" && $format != "flac" && $format != "wav" && $format != "wma"
		&& $format != "aac" && $format != "m4a" && $format != "html" && $format != "htm"
		&& $format != "mov" && $format != "avi" && $format != "mkv" && $format != "mp4"
		&& $format != "wmv" && $format != "webm" && $format != "mkv" && $format != "ts"
		&& $format != "webp" && $format != "svg") {
        echo '<br /><div class="alert alert-info">The uploaded file is with unallowed extension.<br />';
    } else {
        $string     = "0123456789wsderfgtyhjuk";
        $new_string = str_shuffle($string);
        $file_location   = blog_files_path . "file_$new_string.$format";
        $web_path = blog_files_url . "file_$new_string.$format";
        move_uploaded_file($tmp_name, $file_location);
        // Insert the records
         $stmt = $blog_pdo->prepare('INSERT INTO files (filename, date, time, path) VALUES (?, ?, ?, ?)');
         $stmt->execute([$name, $date, $time, $web_path]);
		echo '<meta http-equiv="refresh" content="0; url=files.php">';
    }
}
?>                          
                  </div>
              </div>
            </div>
</div>
<?php
include "footer.php";
?>