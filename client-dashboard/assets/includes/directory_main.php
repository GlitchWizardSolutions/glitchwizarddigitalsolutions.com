<?php
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
echo '<!--
 4. DIRECTORY MAIN: client-dashboard/assets/includes/directory_functions.php-->';
if (!function_exists('convert_filesize')){
// Convert filesize to a readable format
function convert_filesize($bytes, $precision = 2) {
  echo '<!--
 -- ++ FUNCTION: convert_filesize-->';
    $units = ['B', 'KB', 'MB', 'GB', 'TB']; 
    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow]; 
}
}//function exists

if (!function_exists('convert_svg_to_png')){
// Convert SVG to PNG
function convert_svg_to_png($source) {
echo '<!--
 -- ++ FUNCTION:convert_svg_to_png-->';
    // The ImageMagick PHP extension is required to convert SVG images 
    if (class_exists('Imagick')) {
        $im = new Imagick();
        // Fetch the SVG file
        $svg = file_get_contents($source);
        // Ensure the background is transparent
        $im->setBackgroundColor(new ImagickPixel('transparent'));
        // Read and process the SVG image
        $im->readImageBlob($svg);
        // Set type as PNG
        $im->setImageFormat('png24');
        // Determine the new path
        $new_path = substr_replace($source, 'png', strrpos($source , '.')+1);
        // Write image to file
        $im->writeImage($new_path);
        // Clean up
        $im->clear();
        $im->destroy();
        // Delete the old file
        unlink($source);
        // return the new path
        return $new_path;
    } else {
        exit('The ImageMagick PHP extension is required to convert SVG images to PNG images!');
    }
}
}//function exists

if (!function_exists('create_image_thumbnail')){
// Create image thumbnails for image media files
function create_image_thumbnail($source, $id) {
echo '<!--
 -- ++ FUNCTION:create_image_thumbnail-->';
    $info = getimagesize($source);
	$image_width = $info[0];
	$image_height = $info[1];
	$new_width = $image_width;
	$new_height = $image_height;
    $thumbnail_parts = explode('.', $source);
	$thumbnail_path = 'media/thumbnails/' . $id . '.' . end($thumbnail_parts);
	if ($image_width > auto_generate_image_thumbnail_max_width || $image_height > auto_generate_image_thumbnail_max_height) {
		if ($image_width > $image_height) {
	    	$new_height = floor(($image_height/$image_width)*auto_generate_image_thumbnail_max_width);
  			$new_width  = auto_generate_image_thumbnail_max_width;
		} else {
			$new_width  = floor(($image_width/$image_height)*auto_generate_image_thumbnail_max_height);
			$new_height = auto_generate_image_thumbnail_max_height;
		}
	}
    if ($info['mime'] == 'image/jpeg') {
        $img = imagescale(imagecreatefromjpeg($source), $new_width, $new_height);
        imagejpeg($img, $thumbnail_path);
    } else if ($info['mime'] == 'image/webp') {
        $img = imagescale(imagecreatefromwebp($source), $new_width, $new_height);
        imagewebp($img, $thumbnail_path);
    } else if ($info['mime'] == 'image/png') {
        $img = imagescale(imagecreatefrompng($source), $new_width, $new_height);
        imagepng($img, $thumbnail_path);
    }
    return $thumbnail_path;
}
}//function exists

if (!function_exists('compress_image')){
// Compress image function
function compress_image($source, $quality) {
    echo '<!--
 -- ++ FUNCTION: compress_image-->';
    $info = getimagesize($source);
    if ($info['mime'] == 'image/jpeg') {
        imagejpeg(imagecreatefromjpeg($source), $source, $quality);
    } else if ($info['mime'] == 'image/webp') {
        imagewebp(imagecreatefromwebp($source), $source, $quality);
    } else if ($info['mime'] == 'image/png') {
        $png_quality = 9 - floor($quality/10);
        $png_quality = $png_quality < 0 ? 0 : $png_quality;
        $png_quality = $png_quality > 9 ? 9 : $png_quality;
        imagepng(imagecreatefrompng($source), $source, $png_quality);
    }
}
}//function exists

if (!function_exists('correct_image_orientation')){
// Correct image orientation function
function correct_image_orientation($source) {
echo '<!--
 -- ++ FUNCTION: correct_image_orientation-->';
    if (strpos(strtolower($source), '.jpg') == false && strpos(strtolower($source), '.jpeg') == false) return;
    $exif = exif_read_data($source);
    $info = getimagesize($source);
    if ($exif && isset($exif['Orientation'])) {
        if ($exif['Orientation'] && $exif['Orientation'] != 1) {
            if ($info['mime'] == 'image/jpeg') {
                $img = imagecreatefromjpeg($source);
            } else if ($info['mime'] == 'image/webp') {
                $img = imagecreatefromwebp($source);
            } else if ($info['mime'] == 'image/png') {
                $img = imagecreatefrompng($source);
            }
            $deg = 0;
            $deg = $exif['Orientation'] == 3 ? 180 : $deg;
            $deg = $exif['Orientation'] == 6 ? 90 : $deg;
            $deg = $exif['Orientation'] == 8 ? -90 : $deg;
            if ($deg) {
                $img = imagerotate($img, $deg, 0);
                if ($info['mime'] == 'image/jpeg') {
                    imagejpeg($img, $source);
                } else if ($info['mime'] == 'image/webp') {
                    imagewebp($img, $source);
                } else if ($info['mime'] == 'image/png') {
                    imagepng($img, $source);
                }
            }
        }
    }
}
}//function exists

if (!function_exists('resize_image')){
// Resize image function
function resize_image($source, $max_width, $max_height) {
    echo '<!--
 -- ++ FUNCTION: resize_image-->';
    $info = getimagesize($source);
	$image_width = $info[0];
	$image_height = $info[1];
	$new_width = $image_width;
	$new_height = $image_height;
	if ($image_width > $max_width || $image_height > $max_height) {
		if ($image_width > $image_height) {
	    	$new_height = floor(($image_height/$image_width)*$max_width);
  			$new_width  = $max_width;
		} else {
			$new_width  = floor(($image_width/$image_height)*$max_height);
			$new_height = $max_height;
		}
	}
    if ($info['mime'] == 'image/jpeg') {
        $img = imagescale(imagecreatefromjpeg($source), $new_width, $new_height);
        imagejpeg($img, $source);
    } else if ($info['mime'] == 'image/webp') {
        $img = imagescale(imagecreatefromwebp($source), $new_width, $new_height);
        imagewebp($img, $source);
    } else if ($info['mime'] == 'image/png') {
        $img = imagescale(imagecreatefrompng($source), $new_width, $new_height);
        imagepng($img, $source);
    }
}
}//function exists

if (!function_exists('get_filetype_icon')){
//document system

// Determine the file icon function
function get_filetype_icon($filetype, $type = null) {
             echo '<!--
 -- ++ FUNCTION: get_filetype_icon-->';
    if (is_dir($filetype)) {
        return '<i class="fa-solid fa-folder"></i>';
    } else if (preg_match('/image\/*/', $type ? $type : mime_content_type($filetype))) {
        return '<i class="fa-solid fa-file-image"></i>';
    } else if (preg_match('/video\/*/', $type ? $type : mime_content_type($filetype))) {
        return '<i class="fa-solid fa-file-video"></i>';
    } else if (preg_match('/audio\/*/', $type ? $type : mime_content_type($filetype))) {
        return '<i class="fa-solid fa-file-audio"></i>';
    } else if (preg_match('/text\/*/', $type ? $type : mime_content_type($filetype))) {
        return '<i class="fa-solid fa-file-lines"></i>';
    } else if (preg_match('/application\/(zip|x-tar|gzip|x-bzip2)/', $type ? $type : mime_content_type($filetype))) {
        return '<i class="fa-solid fa-file-zipper"></i>';
    }else if (preg_match('/application\/(msword|vnd.openxmlformats-officedocument.wordprocessingml.document)/', $type ? $type : mime_content_type($filetype))) {
        return '<i class="fa-solid fa-file-word"></i>';
    }else if (preg_match('/font\/*/', $type ? $type : mime_content_type($filetype))) {
        return '<i class="fa-solid fa-f"></i>';
    }else if (preg_match('/application\/(pdf)/', $type ? $type : mime_content_type($filetype))) {
        return '<i class="fa-solid fa-file-pdf"></i>';
    }else if (preg_match('/application\/(vnd.ms-powerpoint|vnd.openxmlformats-officedocument.presentationml.presentation)/', $type ? $type : mime_content_type($filetype))) {
        return '<i class="fa-solid fa-file-powerpoint"></i>';
    }else if (preg_match('/application\/(rtf)/', $type ? $type : mime_content_type($filetype))) {
        return '<i class="fa-solid fa-paragraph"></i>';
    }else if (preg_match('/application\/(vnd.ms-excel|vnd.openxmlformats-officedocument.spreadsheetml.sheet)/', $type ? $type : mime_content_type($filetype))) {
        return '<i class="fa-solid fa-file-excel"></i>';
    }
    
    return '<i class="fa-solid fa-file-export"></i>';
}
}//function exists

if (!function_exists('recursive_chmod')){
// Change directory permissions recursively function
function recursive_chmod($path, $perms) {
   echo '<!--
 -- ++ FUNCTION: recursive_chmod-->';
    if (is_dir($path)) {
        $dir = new DirectoryIterator($path);
        foreach ($dir as $item) {
            if (!chmod($item->getPathname(), $perms)) {
                return false;
            }
            if ($item->isDir() && !$item->isDot()) {
                recursive_chmod($item->getPathname(), $perms);
            }
        }
    } else {
        if (!chmod($path, $perms)) {
            return false;
        }
    }
    return true;
}
}//function exists

if (!function_exists('get_formatted_file_data')){
// Format file function
function get_formatted_file_data($file) {
   echo '<!--
 -- ++ FUNCTION: get_formatted_file_data-->';
    if (file_exists($file)) {
        $editable_extensions = explode(',', EDITABLE_EXTENSIONS);
        $type = mime_content_type($file);
        $media = '';
        $media = preg_match('/image\/*/', $type) ? 'image' : $media;
        $media = preg_match('/audio\/*/', $type) ? 'audio' : $media;
        $media = preg_match('/video\/*/', $type) ? 'video' : $media;
        return [
            'name' => determine_relative_path($file),
            'encodedname' => urlencode(determine_relative_path($file)),
            'basename' => basename($file),
            'icon' => get_filetype_icon($file, $type),
            'size' => is_dir($file) ? 'Folder' : convert_filesize(filesize($file)),
            'modified' => str_replace(date('F j, Y'), 'Today,', date('F j, Y H:ia', filemtime($file))),
            'type' => $type,
            'perms' => substr(sprintf('%o', fileperms($file)), -4),
            'owner' => function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($file))['name'] . ':' . posix_getgrgid(filegroup($file))['name'] : fileowner($file) . ':' . filegroup($file),
            'editable' => in_array(strtolower(substr($file, strrpos($file, '.'))), $editable_extensions),
            'token' => hash_hmac('sha256', determine_relative_path($file), SECRET_KEY),
            'media' => $media
        ];
    }
    return false;
}
}//function exists

if (!function_exists('get_directories')){
// Get all directories function - they will be populated in the aside element
function get_directories($intial_dir, $level = 0) {
       echo '<!--
 -- ++ FUNCTION: get_directories-->';
    $intial_dir = str_replace('\\', '/', $intial_dir);
    $directories = [];
    foreach (scandir($intial_dir) as $file) {
        if ($file == '.' || $file == '..') continue;
        $dir = $intial_dir . '/' . $file;
        if (is_dir($dir)) {
            $directories[] = [
                'level' => $level,
                'name' => $file,
                'path' => urlencode(rtrim(determine_relative_path($dir), '/') . '/'),
                'token' => hash_hmac('sha256', rtrim(determine_relative_path($dir), '/') . '/', SECRET_KEY),
                'children' => get_directories($dir, $level+1)
            ];
        }
    }
    return $directories;
}
}//function exists

if (!function_exists('determine_relative_path')){
// Determine the relative path 
function determine_relative_path($path) {
    $intial_dir = str_replace('\\', '/', INITIAL_DIRECTORY);
    if (substr($path, 0, strlen($intial_dir)) == $intial_dir) {
        $path = ltrim(substr($path, strlen($intial_dir)), '/');
    } 
    return $path;
}
}//function exists

if (!function_exists('determine_full_path')){
// Determine the full path function
function determine_full_path($path) {
     echo '<!--
 -- ++ FUNCTION: determine_full_path-->';
    return rtrim(str_replace('\\', '/', INITIAL_DIRECTORY), '/') . '/' . determine_relative_path($path);
}
}//function exists

if (!function_exists(' verify_token')){
// Token verification function - will prevent the user from accessing files and directories they're not supposed to access
function verify_token($file, $token) {
         echo '<!--
 -- ++ FUNCTION: verify_token-->';
    if (!VERIFY_TOKEN) return true;
    if (hash_hmac('sha256', $file, SECRET_KEY) == $token) {
        return true;
    }
    return false;
}
}//function exists
?>