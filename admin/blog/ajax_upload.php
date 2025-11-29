<?php
//2025-06-24 Production
error_log('Loading Page: /admin/blog/ajax_update ');
require 'assets/includes/admin_config.php';

$uploaddir = '/uploads/documents';

if(isset($_POST['chunk']) && isset($_POST['chunk_last']) && isset($_POST['file_name']) && $_POST['file_name'] != '') {
    // This is chunk upload
    
    $chunk_uploaded = true;
    $message = "";

    // Check Upload Errors
    if(isset($_FILES['file']['error']) && $_FILES['file']['error'] != UPLOAD_ERR_OK) {
        $chunk_uploaded = false;
        $message = "FILES Error code " . $_FILES['file']['error'];
    }

    $uploadfile = getcwd().DIRECTORY_SEPARATOR.$uploaddir.DIRECTORY_SEPARATOR.$_POST['file_name'];
    $uploadfile_tmp = getcwd().DIRECTORY_SEPARATOR.$uploaddir.DIRECTORY_SEPARATOR.$_POST['file_name'].".tmp";
    $file_id = md5($uploadfile);

    $uploaded_file = fopen($_FILES['file']['tmp_name'], 'rb');
    $uploaded_data = fread($uploaded_file, filesize($_FILES['file']['tmp_name']));
    fclose($uploaded_file);

    $new_file = fopen($uploadfile_tmp, "ab");
    $bytes_fwrite = fwrite($new_file, $uploaded_data);
    fclose($new_file);

    if($_FILES['file']['size'] != $bytes_fwrite) {
        $chunk_uploaded = false;
        $message = "Data partially written";
    }

    if($_POST['chunk_last'] == 'true' && $chunk_uploaded) {
        $response['success'] = true;
        $response['type'] = 'file';
        $response['file_id'] = $file_id;

        // Rename file
        if(!file_exists($uploadfile)){
            rename($uploadfile_tmp, $uploadfile);
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
            rename($uploadfile_tmp, $uploadfile);
        }

        // Store File info
        $_SESSION["drop_uploader_".$file_id] = $uploadfile;
        $_SESSION["drop_uploader_".$file_id."_name"] = $_POST['file_name'];
        $_SESSION["drop_uploader_".$file_id."_type"] = mime_content_type($uploadfile);
        $_SESSION["drop_uploader_".$file_id."_size"] = filesize($uploadfile);
        // Demo only
        //unlink($uploadfile);
    } else {
        $response['success'] = true;
        $response['type'] = 'chunk';
        if($message != "") {
            $response['message'] = $message;
        }
    }

    echo json_encode($response);

} else {
    // This is AJAX upload

    if(isset($_FILES['file'])) {
        $name = $_FILES['file']['name'];
        $type = $_FILES['file']['type'];
        $size = $_FILES['file']['size'];
    }

    $error_message = '';

    if(isset($_FILES['file']['error'])) {
        $code = $_FILES['file']['error'];
        switch ($code) { 
            case UPLOAD_ERR_OK: 
                $error_message .= ""; 
                break;
            case UPLOAD_ERR_INI_SIZE: 
                $error_message .= "The uploaded file exceeds the upload_max_filesize directive in php.ini"; 
                break; 
            case UPLOAD_ERR_FORM_SIZE: 
                $error_message .= "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form"; 
                break; 
            case UPLOAD_ERR_PARTIAL: 
                $error_message .= "The uploaded file was only partially uploaded"; 
                break; 
            case UPLOAD_ERR_NO_FILE: 
                $error_message .= "No file was uploaded"; 
                break; 
            case UPLOAD_ERR_NO_TMP_DIR: 
                $error_message .= "Missing a temporary folder"; 
                break; 
            case UPLOAD_ERR_CANT_WRITE: 
                $error_message .= "Failed to write file to disk"; 
                break; 
            case UPLOAD_ERR_EXTENSION: 
                $error_message .= "File upload stopped by extension"; 
                break; 
            default: 
                $error_message .= "Unknown upload error"; 
                break; 
        } 
    } elseif(!isset($_FILES['file'])) {
        $error_message = "File not recieved";
    }

    if(isset($_FILES['file']['name'])) {
        $uploadfile = getcwd().DIRECTORY_SEPARATOR.$uploaddir.DIRECTORY_SEPARATOR.$_FILES['file']['name'];
        $filepath = BASE_URL . blog_files_url . $_FILES['file']['name'];
        if(!file_exists($uploadfile)){
            $file_moved = move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile);
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
            $file_moved = move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile);
        }
        if($file_moved) {
            // ok
            $file_id = md5($uploadfile);
            // Store File id in session. We can delete file by this id from other script
            $_SESSION["drop_uploader_".$file_id] = $uploadfile;
            $filename = $_FILES['file']['name'];
            $filetype=$_FILES['file']['type'];
            $_SESSION["drop_uploader_".$file_id."_name"] = $_FILES['file']['name'];
            $_SESSION["drop_uploader_".$file_id."_type"] = $_FILES['file']['type'];
            $_SESSION["drop_uploader_".$file_id."_size"] = $_FILES['file']['size'];
           $stmt = $pdo_b2b_blog->prepare( "INSERT INTO `documents` (name, path) VALUES (?,?)");
           $stmt->execute([$filename,$filepath]); 
            $success_message = $file_id;
            // Demo only
            //unlink($uploadfile);
        } else {
            $error_message = "Error while uploading file ".$_FILES['file']['name'];
        }
    }

    if($error_message != "") {
    	$response['success'] = false;
    	$response['message'] = $error_message;
    } else {
    	$response['success'] = true;
    	$response['file_id'] = $file_id;
    }

    echo json_encode($response);
}