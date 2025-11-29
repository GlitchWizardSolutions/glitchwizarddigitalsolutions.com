<?php
//error_log('Loading Page includes path main -  the one in client dashboard...');

// Load unified authentication system
require_once public_path . 'lib/auth-system.php';

try {
    $pdo = new PDO('mysql:host=' . db_host . ';dbname=' . db_name . ';charset=' . db_charset, db_user, db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
    // If there is an error with the connection, stop the script and display the error.
    error_log("Dashboard main.php - Failed to connect to login database: " . $exception->getMessage());
    error_log("Connection details - Host: " . db_host . " | DB: " . db_name . " | User: " . db_user);
    exit('Failed to connect to database: ' . $exception->getMessage());
}
// Connect to the budget database
try {
    $budget_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name7 . ';charset=' . db_charset, db_user, db_pass);
    $budget_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
   
} catch (PDOException $exception) {
    // If there is an error with the connection, stop the script and display the error.
    exit('Failed to connect to database: ' . $exception->getMessage());
}
/*Connect to the envato blog database*/
try {
    $blog_pdo = new PDO('mysql:host=' . db_host . ';dbname=' . db_name12 . ';charset=' . db_charset, db_user, db_pass);
    $blog_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
   
} catch (PDOException $exception) {
    // If there is an error with the connection, stop the script and display the error.
    error_log('failed to connect to blog pdo database from client dashboard main.');
    exit('Failed to connect to the blog pdo database: ' . $exception->getMessage());
}
if (!function_exists('pdo_connect_mysql')){
function pdo_connect_mysql() {
    try {
    	$pdo = new PDO('mysql:host=' . db_host . ';dbname=' . db_name . ';charset=utf8', db_user, db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $exception) {
    	// If there is an error with the connection, stop the script and display the error.
    	exit('Failed to connect to database!' . $exception->getMessage());
    }
    return $pdo;
 }
}//end function exists

// URL Configuration - Auto-detects development vs production
// Uses BASE_URL constant from config.php (environment detection)
$base_url = BASE_URL . 'client-dashboard';  // Dashboard base URL
$outside_url = BASE_URL;                    // Main site base URL

// Make variables global for use throughout dashboard
global $base_url, $outside_url;

if (!function_exists('pdo_connect_onthego_db')){
// Connect to MySQL database function
function pdo_connect_onthego_db() {
    try {
    	$pdo = new PDO('mysql:host=' . db_host . ';dbname=' . db_name2 . ';charset=utf8', db_user, db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $exception) {
    	// If there is an error with the connection, stop the script and display the error.
    	exit('Failed to connect to database!' . $exception->getMessage());
    }
    return $pdo;
 }
}//function exists
if (!function_exists('pdo_connect_budget_db')){
 // Connect to MySQL database function
function pdo_connect_budget_db() {
    try {
     $budget_pdo = new PDO('mysql:host=' . db_host . ';dbname=' . db_name7 . ';charset=utf8', db_user, db_pass);
     $budget_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $exception) {
    	// If there is an error with the connection, stop the script and display the error.
    	exit('Failed to connect to database!' . $exception->getMessage());
    }
    return $budget_pdo;
 }
}//end function exists
if (!function_exists('pdo_connect_blog_db')){
  // Connect to MySQL database function
function pdo_connect_blog_db() {
    try {
     $blog_pdo = new PDO('mysql:host=' . db_host . ';dbname=' . db_name12 . ';charset=utf8', db_user, db_pass);
     $blog_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $exception) {
    	// If there is an error with the connection, stop the script and display the error.
    	exit('Failed to connect to Failed to connect to the blog pdo with function database!' . $exception->getMessage());
    }
    return $blog_pdo;
 }
}//end function exists
if (!function_exists('check_loggedin')){
// The below function will check if the user is logged-in and also check the remember me cookie
function check_loggedin($pdo, $redirect_file = site_menu_base .'index.php') { 
	if (isset($_SESSION['loggedin'])) { 
	    $_SESSION['sec-username'] = $_SESSION['name'];
		$date = date('Y-m-d\TH:i:s');
		$stmt = $pdo->prepare('UPDATE accounts SET last_seen = ? WHERE id = ?');
		$stmt->execute([ $date, $_SESSION['id'] ]);
	} 
	// Check for remember me cookie variable and loggedin session variable 
    if (isset($_COOKIE['rememberme']) && !empty($_COOKIE['rememberme']) && !isset($_SESSION['loggedin'])) { //remembered cookie
    	// If the remember me cookie matches one in the database then we can update the session variables.
    	$stmt = $pdo->prepare('SELECT * FROM accounts WHERE rememberme = ?');
    	$stmt->execute([ $_COOKIE['rememberme'] ]);
    	$account = $stmt->fetch(PDO::FETCH_ASSOC);
		// If account exists...
    	if ($account) { //account matched
    		// Found a match, update the session variables and keep the user logged-in
    		session_regenerate_id();
    		$_SESSION['loggedin'] = TRUE;
    		$_SESSION['sec-username'] = $account['username'];
    		$_SESSION['name'] = $account['username'];
    		$_SESSION['id'] = $account['id'];
			$_SESSION['role'] = $account['role'];
			$_SESSION['access_level'] = $account['access_level'];
			$_SESSION['email'] = $account['email'];
			$_SESSION['full_name'] = $account['full_name'];
			$_SESSION['document_path'] = $account['document_path'];
			// Update last seen date
			$date = date('Y-m-d\TH:i:s');
			$stmt = $pdo->prepare('UPDATE accounts SET last_seen = ? WHERE id = ?');
			$stmt->execute([ $date, $account['id'] ]);
    	} else { //exit account matched not remembered
    		// If the user is not remembered redirect to the login page.
    		header('Location: ' . $redirect_file);
    		exit;
    	}//remembered
    } else if (!isset($_SESSION['loggedin'])) {
    	// If the user is not logged in redirect to the login page.
    	header('Location: ' . $redirect_file);
    	exit;
    }//logged
  }//function
}////end function exists
if (!function_exists('login_attempts')){
function login_attempts($pdo, $update = TRUE) {
	$ip = $_SERVER['REMOTE_ADDR'];
	$now = date('Y-m-d H:i:s');
	if ($update) {
		$stmt = $pdo->prepare('INSERT INTO login_attempts (ip_address, `date`) VALUES (?,?) ON DUPLICATE KEY UPDATE attempts_left = attempts_left - 1, `date` = VALUES(`date`)');
		$stmt->execute([ $ip, $now ]);
	}
	$stmt = $pdo->prepare('SELECT * FROM login_attempts WHERE ip_address = ?');
	$stmt->execute([ $ip ]);
	$login_attempts = $stmt->fetch(PDO::FETCH_ASSOC);
	if ($login_attempts) {
		// The user can try to login after 1 day... change the "+1 day" if you want to increase/decrease this date.
		$expire = date('Y-m-d H:i:s', strtotime('+1 day', strtotime($login_attempts['date'])));
		if ($now > $expire) {
			$stmt = $pdo->prepare('DELETE FROM login_attempts WHERE ip_address = ?');
			$stmt->execute([ $ip ]);
			$login_attempts = array();
		}
	}
	return $login_attempts;
}
}//end function exists
//The following function contains an error in the call online 150
/*if (!function_exists('check_bloggedin')){
// The below function will check if the user is logged-in and also check the remember me cookie
function check_bloggedin($blog_pdo, $pdo, $redirect_file =  $settings['blog_site_url'] .'blog.php') { 
	if (isset($_SESSION['loggedin'])){ //member is logged in, so update their last seen date.
		$date = date('Y-m-d\TH:i:s');
		$stmt = $pdo->prepare('UPDATE accounts SET last_seen = ? WHERE id = ?');
		$stmt->execute([ $date, $_SESSION['id'] ]);
    }elseif(isset($_COOKIE['rememberme']) && !empty($_COOKIE['rememberme']) && !isset($_SESSION['loggedin'])) { //remembered cookie
        //determine if someone is in accounts table
	    $stmt = $pdo->prepare('SELECT * FROM accounts WHERE rememberme = ?');
	    $stmt->execute([ $_COOKIE['rememberme'] ]);
	    $auto_login_account = $stmt->fetch(PDO::FETCH_ASSOC);
	    
	if($auto_login_account) {
		// Authenticate the account member
		session_regenerate_id();
		$_SESSION['loggedin'] = TRUE;
		$_SESSION['sec-username'] = $auto_login_account['username'];
		$_SESSION['name'] = $auto_login_account['username'];
		$_SESSION['id'] = $auto_login_account['id'];
        $_SESSION['role'] = $auto_login_account['role'];
        $_SESSION['access_level'] = $auto_login_account['access_level'];
        $_SESSION['email'] = $auto_login_account['email'];
        $_SESSION['full_name'] = $auto_login_account['full_name'];
        $_SESSION['document_path'] = $auto_login_account['document_path'];
		// Update last seen date
		$date = date('Y-m-d\TH:i:s');
		$stmt = $pdo->prepare('UPDATE accounts SET last_seen = ? WHERE id = ?');
		$stmt->execute([ $date, $auto_login_account['id'] ]);
		// Redirect to blog home page
        header('Location: /blog/');
		exit;
	} else { //exit account matched not remembered
    		// If the user is not remembered redirect to the login page.
    		header('Location: ' . $redirect_file);
    		exit;
    	}//remembered
    } else if (!isset($_SESSION['loggedin'])) {
    	// If the user is not logged in redirect to the login page.
    	header('Location: ' . $redirect_file);
    	exit;
    }//logged
  }//function
}////end function exists*/

if (!function_exists('color_from_string')){
// The following function will be used to assign a unique icon color to our users
function color_from_string($string) {
    // The list of hex colors
    $colors = ['#34568B','#FF6F61','#6B5B95','#88B04B','#F7CAC9','#92A8D1','#955251','#B565A7','#009B77','#DD4124','#D65076','#45B8AC','#EFC050','#5B5EA6','#9B2335','#DFCFBE','#BC243C','#C3447A','#363945','#939597','#E0B589','#926AA6','#0072B5','#E9897E','#B55A30','#4B5335','#798EA4','#00758F','#FA7A35','#6B5876','#B89B72','#282D3C','#C48A69','#A2242F','#006B54','#6A2E2A','#6C244C','#755139','#615550','#5A3E36','#264E36','#577284','#6B5B95','#944743','#00A591','#6C4F3D','#BD3D3A','#7F4145','#485167','#5A7247','#D2691E','#F7786B','#91A8D0','#4C6A92','#838487','#AD5D5D','#006E51','#9E4624'];
    // Find color based on the string
    $colorIndex = hexdec(substr(sha1($string), 0, 10)) % count($colors);
    // Return the hex color
    return $colors[$colorIndex];
}
}////end function exists
if (!function_exists('convert_filesize')){
// Convert filesize to a readable format
function convert_filesize($bytes, $precision = 2) {
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
    // The ImageMagick PHP extension is required to convert SVG images 
    if (class_exists('Imagick')) {
        $im = new Imagick();
        // Fetch the SVG file
        $svg = file_get_contents($source);
        // Ensure the background is transparent
        // $im->setBackgroundColor(new ImagickPixel('transparent')); // Requires Imagick extension
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
}//end function exists
if (!function_exists('correct_image_orientation')){
// Correct image orientation function
function correct_image_orientation($source) {
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
}//end function exists
if (!function_exists('resize_image')){
// Resize image function
function resize_image($source, $max_width, $max_height) {
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
}//end function exists
if (!function_exists('create_invoice_pdf')){
// Create invoice PDF function
function create_invoice_pdf($invoice, $invoice_items, $client) {
    define('INVOICE', true);
    // Client address
    $client_address = [
        $client['address_street'],
        $client['address_city'],
        $client['address_state'],
        $client['address_zip'],
        $client['address_country']
    ];
    // remove any empty values
    $client_address = array_filter($client_address);
    // Get payment methods
    $payment_methods = explode(', ', $invoice['payment_methods']);
    // Include the template
    if (file_exists(client_side_invoice . 'templates/' . $invoice['invoice_template'] . '/template-pdf.php')) {
        require client_side_invoice . 'templates/' . $invoice['invoice_template'] . '/template-pdf.php';
        // Save the output to a file
        $pdf->Output(client_side_invoice . 'pdfs/' . $invoice['invoice_number'] . '.pdf', 'F');
        return true;
    } else if (file_exists(client_side_invoice . 'templates/default/template-pdf.php')) {
        require client_side_invoice . 'templates/default/template-pdf.php';
        // Save the output to a file
        $pdf->Output(client_side_invoice . 'pdfs/' . $invoice['invoice_number'] . '.pdf', 'F');
        return true;
    } 
    return false;
}
}///end function exists
if (!function_exists('time_elapsed_string')){
// Convert date to elapsed string function
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    $w = floor($diff->d / 7);
    $diff->d -= $w * 7;
    $string = ['y' => 'year','m' => 'month','w' => 'week','d' => 'day','h' => 'hour','i' => 'minute','s' => 'second'];
    foreach ($string as $k => &$v) {
        if ($k == 'w' && $w) {
            $v = $w . ' week' . ($w > 1 ? 's' : '');
        } else if (isset($diff->$k) && $diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }
    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
}//end function exists
if (!function_exists('time_difference_string')){
// Convert date to elapsed string function
function time_difference_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    if ($now > $ago){
        $elapsed=" ago";
        
    }else{
        $elapsed=" until";
       
    }
    $w = floor($diff->d / 7);
    $diff->d -= $w * 7;
    $string = ['y' => 'year','m' => 'month','w' => 'week','d' => 'day','h' => 'hour','i' => 'minute','s' => 'second'];
    foreach ($string as $k => &$v) {
        if ($k == 'w' && $w) {
            $v = $w . ' week' . ($w > 1 ? 's' : '');
        } else if (isset($diff->$k) && $diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }
    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) .  $elapsed : 'just now';
}
}//end function exists
?>