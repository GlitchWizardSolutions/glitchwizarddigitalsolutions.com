<?php
//2025-06-24 Production
error_log('Loading Page: /admin/blog/upload_documents ');
require 'assets/includes/admin_config.php';
$append='append_name_';
if(isset($_FILES['file'])) {
    $name = $_FILES['file']['name'];
    $type = $_FILES['file']['type'];
    $size = $_FILES['file']['size'];
}

$error_message = '';

if(isset($_FILES['file']['error']) && is_array($_FILES['file']['error'])) {
    foreach ($_FILES['file']['error'] as $code) {
        switch ($code) { 
            case UPLOAD_ERR_OK: 
                $error_message .= ""; 
                break;
            case UPLOAD_ERR_INI_SIZE: 
                $error_message .= "The uploaded file exceeds the upload_max_filesize directive in php.ini<br/>"; 
                break; 
            case UPLOAD_ERR_FORM_SIZE: 
                $error_message .= "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form<br/>"; 
                break; 
            case UPLOAD_ERR_PARTIAL: 
                $error_message .= "The uploaded file was only partially uploaded<br/>"; 
                break; 
            case UPLOAD_ERR_NO_FILE: 
                $error_message .= "No file was uploaded<br/>"; 
                break; 
            case UPLOAD_ERR_NO_TMP_DIR: 
                $error_message .= "Missing a temporary folder<br/>"; 
                break; 
            case UPLOAD_ERR_CANT_WRITE: 
                $error_message .= "Failed to write file to disk<br/>"; 
                break; 
            case UPLOAD_ERR_EXTENSION: 
                $error_message .= "File upload stopped by extension<br/>"; 
                break; 
            default: 
                $error_message .= "Unknown upload error<br/>"; 
                break; 
        } 
    }
} elseif(isset($_FILES['file']['error'])) {
    $code = $_FILES['file']['error'];
    switch ($code) { 
        case UPLOAD_ERR_OK: 
            $error_message .= ""; 
            break;
        case UPLOAD_ERR_INI_SIZE: 
            $error_message .= "The uploaded file exceeds the upload_max_filesize directive in php.ini<br/>"; 
            break; 
        case UPLOAD_ERR_FORM_SIZE: 
            $error_message .= "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form<br/>"; 
            break; 
        case UPLOAD_ERR_PARTIAL: 
            $error_message .= "The uploaded file was only partially uploaded<br/>"; 
            break; 
        case UPLOAD_ERR_NO_FILE: 
            $error_message .= "No file was uploaded<br/>"; 
            break; 
        case UPLOAD_ERR_NO_TMP_DIR: 
            $error_message .= "Missing a temporary folder<br/>"; 
            break; 
        case UPLOAD_ERR_CANT_WRITE: 
            $error_message .= "Failed to write file to disk<br/>"; 
            break; 
        case UPLOAD_ERR_EXTENSION: 
            $error_message .= "File upload stopped by extension<br/>"; 
            break; 
        default: 
            $error_message .= "Unknown upload error<br/>"; 
            break; 
    } 
} elseif(!isset($_FILES['file'])) {
    $error_message = "No files recieved<br/>";
}

$uploaddir = '/uploads/documents/';

if(isset($_FILES['file']['name']) && is_array($_FILES['file']['name'])) {
    foreach ($_FILES['file']['name'] as $k=>$filename) {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $uploadfile = getcwd().DIRECTORY_SEPARATOR.$uploaddir.DIRECTORY_SEPARATOR.$filename;
        if(!file_exists($uploadfile)){
            $file_moved = move_uploaded_file($_FILES['file']['tmp_name'][$k], $uploadfile);
        } else {
            $uploadfile_name_arr = explode('.', $uploadfile);
            $uploadfile_name_arr_len = count($uploadfile_name_arr);
            $uploadfile_ext = $uploadfile_name_arr[$uploadfile_name_arr_len - 1];
            $uploadfile_without_ext = str_replace('.'.$uploadfile_ext, '', $uploadfile);
            $i = 2;
            do {
                $uploadfile = $uploadfile_without_ext . '_' . $i . '.' . $uploadfile_ext;
                $i++;
            } while (file_exists($uploadfile));
            $file_moved = move_uploaded_file($_FILES['file']['tmp_name'][$k], $uploadfile);
         
        }
        if($file_moved) {
            // ok
        } else {
            $error_message .= "Error while uploading file ".$filename."<br/>";
        }
    }
} elseif(isset($_FILES['file']['name'])) {
    $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
    $uploadfile = getcwd().DIRECTORY_SEPARATOR.$uploaddir.DIRECTORY_SEPARATOR.md5(rand(99999,999999).$_FILES['file']['name']).".".$ext;
    if(move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {
        // ok
    } else {
        $error_message = "Error while uploading file ".$_FILES['file']['name']."<br/>";
    }
}

?>
<?=template_admin_header('Upload Documents', 'blog')?>

<?=generate_breadcrumbs([
    ['title' => 'Admin Dashboard', 'url' => '../index.php'],
    ['title' => 'Blog', 'url' => 'blog_dash.php'],
    ['title' => 'Upload Documents', 'url' => '']
])?>

<div class="content-title">
    <div class="title">
       <i class="fa-solid fa-upload"></i>
        <div class="txt">
            <h2>Upload Documents</h2>
            <p>Upload multiple documents</p>
        </div>
    </div>
</div>

        <div class="container">
            <div class="row">
                <div class="twelve column" style="margin-top: 5%">
                    <?php if($error_message != '') { ?>
                        <?php if(!isset($_REQUEST['file'])) { ?>
                            <h3>File Uploading Error</h3>
                            <?php echo $error_message; ?>
                            <br/>
                        <?php } else { ?>
                            <h3>Files Received & Uploaded</h3>
                            <table class="u-full-width">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Type</th>
                                        <th>Size</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if(is_array($_REQUEST['file'])) {
                                    for ($i=0; $i < count($_REQUEST['file']); $i++) { 
                                    $file_id = $_REQUEST['file'][$i];
                                    ?>
                                    <tr>
                                        <td><?php echo $_SESSION["drop_uploader_".$file_id."_name"]; ?></td>
                                        <td><?php echo $_SESSION["drop_uploader_".$file_id."_type"]; ?></td>
                                        <td><?php echo $_SESSION["drop_uploader_".$file_id."_size"]; ?> Bytes</td>
                                    </tr>
                                    <?php
                                    }
                                    } else {
                                    ?>
                                    <tr>
                                        <td><?php echo $_SESSION["drop_uploader_".$_REQUEST['file']."_name"]; ?></td>
                                        <td><?php echo $_SESSION["drop_uploader_".$_REQUEST['file']."_type"]; ?></td>
                                        <td><?php echo $_SESSION["drop_uploader_".$_REQUEST['file']."_size"]; ?> Bytes</td>
                                    </tr>
                                    <?php 
                                    }
                                    ?>
                                </tbody>
                            </table>
                        <?php } ?>
                    <?php
                    } else { ?>
                        <h3>Files Received</h3>
                        <table class="u-full-width">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Size</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if(is_array($name)) {
                                for ($i=0; $i < count($name); $i++) { ?>
                                <tr>
                                    <td><?php echo $name[$i]; ?></td>
                                    <td><?php echo $type[$i]; ?></td>
                                    <td><?php echo $size[$i]; ?> Bytes</td>
                                </tr>
                                <?php
                                }
                                } else {
                                ?>
                                <tr>
                                    <td><?php echo $name; ?></td>
                                    <td><?php echo $type; ?></td>
                                    <td><?php echo $size; ?> Bytes</td>
                                </tr>
                                <?php 
                                }
                                ?>
                            </tbody>
                        </table>
                    <?php } ?>
                </div>
            </div>
        </div>
            <link rel="stylesheet" href="css/skeleton.css">
    <link rel="stylesheet" href="pe-icon-7-stroke/css/pe-icon-7-stroke.css">
    <link rel="stylesheet" href="css/drop_uploader.css">
<?php include "footer.php";?>