<?php
//limited version of main, in this context.
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);

// Load unified authentication system
require_once public_path . 'lib/auth-system.php';

// Connect to the database
try {
    $pdo = new PDO('mysql:host=' . db_host . ';dbname=' . db_name . ';charset=' . db_charset, db_user, db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
    // If there is an error with the connection, stop the script and display the error.
    exit('Failed to connect to database: ' . $exception->getMessage());
}

// Connect to MySQL database function
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

// Legacy check_loggedin() moved to lib/auth-system.php
// login_attempts() also available in lib/auth-system.php

// Keep legacy login_attempts for compatibility (uses different table structure)
if (!function_exists('login_attempts_legacy')) {
function login_attempts_legacy($pdo, $update = TRUE) {
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
} // End login_attempts_legacy

// The following function will be used to assign a unique icon color to our users
function color_from_string($string) {
        echo '<!--
 -- ++ FUNCTION: color_from_string-->';
    // The list of hex colors
    $colors = ['#34568B','#FF6F61','#6B5B95','#88B04B','#F7CAC9','#92A8D1','#955251','#B565A7','#009B77','#DD4124','#D65076','#45B8AC','#EFC050','#5B5EA6','#9B2335','#DFCFBE','#BC243C','#C3447A','#363945','#939597','#E0B589','#926AA6','#0072B5','#E9897E','#B55A30','#4B5335','#798EA4','#00758F','#FA7A35','#6B5876','#B89B72','#282D3C','#C48A69','#A2242F','#006B54','#6A2E2A','#6C244C','#755139','#615550','#5A3E36','#264E36','#577284','#6B5B95','#944743','#00A591','#6C4F3D','#BD3D3A','#7F4145','#485167','#5A7247','#D2691E','#F7786B','#91A8D0','#4C6A92','#838487','#AD5D5D','#006E51','#9E4624'];
    // Find color based on the string
    $colorIndex = hexdec(substr(sha1($string), 0, 10)) % count($colors);
    // Return the hex color
    return $colors[$colorIndex];
}

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