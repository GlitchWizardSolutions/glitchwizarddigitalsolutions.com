<?php /* 
This has been moved to client-dashboard/assets/includes/ and should be removed 
LOCATION: html/public/client-dashboard/main.php CHANGED?: Updated 9/10/24*/
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
echo 'call to moved page, change in your code.';
echo '<br>';
echo 'call to moved page, change in your code.';
echo '<br>';
echo 'call to moved page, change in your code.';
echo '<br>';
echo 'call to moved page, change in your code.';
echo '<br>';
echo 'call to moved page, change in your code.';
echo '<br>';
echo 'call to moved page, change in your code.';
echo '<br>';
echo 'call to moved page, change in your code.';
// Namespaces
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Connet to the database
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
    	exit('Failed to connect to database!');
    }
    return $pdo;
}

// The below function will check if the user is logged-in and also check the remember me cookie
function check_loggedin($pdo, $redirect_file = public_path . 'index.php') {
	// If you want to update the "last seen" column on every page load, you can uncomment the below code
	if (isset($_SESSION['loggedin'])) {
	    $stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
    	$stmt->execute([ $_SESSION['id'] ]);
    	$account = $stmt->fetch(PDO::FETCH_ASSOC);
    	    $_SESSION['loggedin'] = TRUE;
		    $_SESSION['name'] = $account['username'];
		    $_SESSION['id'] = $account['id'];
            $_SESSION['role'] = $account['role'];
            $_SESSION['access_level'] = $account['access_level'];
            $_SESSION['email'] = $account['email'];
            $_SESSION['full_name'] = $account['full_name'];
            $_SESSION['document_path'] = $account['document_path'];
		$date = date('Y-m-d\TH:i:s');
		$stmt = $pdo->prepare('UPDATE accounts SET last_seen = ? WHERE id = ?');
		$stmt->execute([ $date, $_SESSION['id'] ]);

	}
	// Check for remember me cookie variable and loggedin session variable
    if (isset($_COOKIE['rememberme']) && !empty($_COOKIE['rememberme']) && !isset($_SESSION['loggedin'])) {
    	// If the remember me cookie matches one in the database then we can update the session variables.
    	$stmt = $pdo->prepare('SELECT * FROM accounts WHERE rememberme = ?');
    	$stmt->execute([ $_COOKIE['rememberme'] ]);
    	$account = $stmt->fetch(PDO::FETCH_ASSOC);
		// If account exists...
    	if ($account) {
    		// Found a match, update the session variables and keep the user logged-in
    		session_regenerate_id();
    		$_SESSION['loggedin'] = TRUE;
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
    	} else {
    		// If the user is not remembered redirect to the login page.
    		header('Location: ' . $redirect_file);
    		exit;
    	}
    } else if (!isset($_SESSION['loggedin'])) {
    	// If the user is not logged in redirect to the login page.
    	header('Location: ' . $redirect_file);
    	exit;
    }
}

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
// Convert filesize to a readable format
function convert_filesize($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB']; 
    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow]; 
}
// Convert SVG to PNG
function convert_svg_to_png($source) {
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

// The following function will be used to assign a unique icon color to our users
function color_from_string($string) {
    // The list of hex colors
    $colors = ['#34568B','#FF6F61','#6B5B95','#88B04B','#F7CAC9','#92A8D1','#955251','#B565A7','#009B77','#DD4124','#D65076','#45B8AC','#EFC050','#5B5EA6','#9B2335','#DFCFBE','#BC243C','#C3447A','#363945','#939597','#E0B589','#926AA6','#0072B5','#E9897E','#B55A30','#4B5335','#798EA4','#00758F','#FA7A35','#6B5876','#B89B72','#282D3C','#C48A69','#A2242F','#006B54','#6A2E2A','#6C244C','#755139','#615550','#5A3E36','#264E36','#577284','#6B5B95','#944743','#00A591','#6C4F3D','#BD3D3A','#7F4145','#485167','#5A7247','#D2691E','#F7786B','#91A8D0','#4C6A92','#838487','#AD5D5D','#006E51','#9E4624'];
    // Find color based on the string
    $colorIndex = hexdec(substr(sha1($string), 0, 10)) % count($colors);
    // Return the hex color
    return $colors[$colorIndex];
}

// Send ticket email function
function send_ticket_email($email, $id, $title, $msg, $priority, $category, $private, $status, $type, $name = '', $user_email = '') {
    if (!mail_enabled) return;
    // Ticket generic subject
	$subject = 'Your ticket is #' . $id;
    // Ticket create subject
	$subject = $type == 'create' ? 'Your ticket has been created #' . $id :$subject;
    // Ticket update subject
    $subject = $type == 'update' ? 'Your ticket has been updated #' . $id : $subject;
    // Ticket comment subject
    $subject = $type == 'comment' ? 'Someone has replied to your ticket #' . $id : $subject;
    // Ticket notification
    $subject = $type == 'notification' ? 'A user has submitted a ticket #' . $id : $subject;
    // Comment notification
    $subject = $type == 'notification-comment' ? 'A user has replied on ticket #' . $id : $subject;
    // Ticket URL
    $link = tickets_directory_url . 'ticket-view.php?id=' . $id . '&code=' . md5($id . $email);
    // Include the ticket email template as a string
    ob_start();
    include tickets_directory_url . 'ticket-email-template.php';
    $ticket_email_template = ob_get_clean();
    // Include PHPMailer library
    include public_path . 'lib/phpmailer/Exception.php';
    include public_path . 'lib/phpmailer/PHPMailer.php';
    include public_path . 'lib/phpmailer/SMTP.php';
    // Create an instance; passing `true` enables exceptions
    $mail = new PHPMailer(true);
    try {
        // SMTP Server settings
        if (SMTP) {
            $mail->isSMTP();
            $mail->Host = smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = smtp_user;
            $mail->Password = smtp_pass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = smtp_port;
        }
        // Recipients
        $mail->setFrom(mail_from, mail_name);
        $mail->addAddress($email);
        $mail->addReplyTo(mail_from, mail_name);
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        // Body
        $mail->Body = $ticket_email_template;
        $mail->AltBody = strip_tags($ticket_email_template);
        // Send mail
        $mail->send();
    } catch (Exception $e) {
        // Output error message
        exit('Error: Message could not be sent. Mailer Error: ' . $mail->ErrorInfo);
    }
}

/* Send Activiation Email 2 has been worked to include more than just activation.  It also does two factor authorization code, reset password functionality and resend activation functionality.  This keeps the emails consistant. */
function send_activation_email2($email, $code, $username, $type) {
    $body_template = "";
    $link="";
// Generic Email Subject
	$subject = 'Action Required';
// Two Factor Authorization
	$subject = $type == 'twofactor' ? 'Your Access Code' :$subject;
	$body_template = $type == 'twofactor' ? public_path . 'email_template_twofactor.php' :$body_template;
	$link = $type == 'twofactor' ? $code :$link;
// Account Activation subject
	$subject = $type == 'activation' ? 'Account Activation Required' :$subject;
	$body_template = $type == 'activation' ? public_path . 'activation-email-template.php' :$body_template;
	$link = $type == 'activation' ? activation_link . '?email=' . $email . '&code=' . $code :$link;
// Account Reset Password subject
    $subject = $type == 'resetpass' ? 'Password Reset' : $subject;
    $body_template = $type == 'resetpass' ? public_path . 'resetpass-email-template.php' :$body_template;
    $link = $type == 'resetpass' ? reset_password_url . '?email=' . $_POST['email'] . '&code=' . $code :$link;
    // Account other subject
    $subject = $type == 'custom' ? 'Welcome to GlitchWizard Solutions!' : $subject;
    // Include the ticket email template as a string
    ob_start();
    include $body_template;
    $email_template = ob_get_clean();
    // Include PHPMailer library
 // Include PHPMailer library
    include public_path . 'lib/phpmailer/Exception.php';
    include public_path . 'lib/phpmailer/PHPMailer.php';
    include public_path . 'lib/phpmailer/SMTP.php';
    // Create an instance; passing `true` enables exceptions
    $mail = new PHPMailer(true);
    try {
        // SMTP Server settings
        if (SMTP) {
            $mail->isSMTP();
            $mail->Host = smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = smtp_user;
            $mail->Password = smtp_pass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = smtp_port;
        }
        // Recipients
        $mail->setFrom(mail_from, no_reply_mail_name);
        $mail->addAddress($email);
        $mail->addReplyTo(mail_from, no_reply_mail_name);
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        // Body
        $mail->Body = $email_template;
        $mail->AltBody = strip_tags($email_template);
        // Send mail
        $mail->send();
    } catch (Exception $e) {
        // Output error message
        exit('Error: Message could not be sent. Mailer Error: ' . $mail->ErrorInfo);
    }
}
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
    if (file_exists(base_path_invoices . 'invoice-system-templates/' . $invoice['invoice_template'] . '/template-pdf.php')) {
        require base_path_invoices . 'invoice-system-templates/' . $invoice['invoice_template'] . '/template-pdf.php';
        // Save the output to a file
        $pdf->Output(base_path_invoices . 'invoice-system-pdfs/' . $invoice['invoice_number'] . '.pdf', 'F');
        return true;
    } else if (file_exists(base_path_invoices . 'invoice-system-templates/default/template-pdf.php')) {
        require base_path_invoices . 'invoice-system-templates/default/template-pdf.php';
        // Save the output to a file
        $pdf->Output(base_path_invoices . 'invoice-system-pdfs/' . $invoice['invoice_number'] . '.pdf', 'F');
        return true;
    } 
    return false;
}
// Send notification email function
function send_client_invoice_email($invoice, $client, $subject = '') {
	if (!mail_enabled) return;
 // Include PHPMailer library
    include public_path . 'lib/phpmailer/Exception.php';
    include public_path . 'lib/phpmailer/PHPMailer.php';
    include public_path . 'lib/phpmailer/SMTP.php';
	// Create an instance; passing `true` enables exceptions
	$mail = new PHPMailer(true);
	try {
		// Server settings
		if (SMTP) {
			$mail->isSMTP();
			$mail->Host = smtp_host;
			$mail->SMTPAuth = empty(smtp_user) && empty(smtp_pass) ? false : true;
			$mail->Username = smtp_user;
			$mail->Password = smtp_pass;
			$mail->SMTPSecure = smtp_secure == 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
			$mail->Port = smtp_port;
		}
		// Recipients
		$mail->setFrom(mail_from, mail_name);
		$mail->addAddress($client['email'], rtrim($client['first_name'] . ' ' . $client['last_name'], ' '));
		$mail->addReplyTo(mail_from, mail_name);
		// Content
		$mail->isHTML(true);
        // Set UTF-8 charset
        $mail->CharSet = 'UTF-8';
        // Set email subject
		$mail->Subject = empty($subject) ? 'Invoice #' . $invoice['invoice_number'] . ' from ' . company_name : $subject;
		// Read the template contents and replace the placeholders with the variables
		$email_template = str_replace(
            ['%invoice_number%', '%first_name%', '%amount%', '%due_date%', '%link%'],
            [$invoice['invoice_number'], $client['first_name'], number_format($invoice['payment_amount']+$invoice['tax_total'], 2), $invoice['due_date'], base_url . 'invoice-system-invoice.php?id=' . $invoice['invoice_number']],
            file_get_contents(base_path_invoices . 'invoice-system-templates/client-email-template.html')
        );
        // Check if pdf atatchment is enabled
        if (pdf_attachments && file_exists(base_path_invoices . 'invoice-system-pdfs/' . $invoice['invoice_number'] . '.pdf') && !$subject) {
            // Include the PHPMailer class
            $mail->AddAttachment(base_path_invoices . 'invoice-system-pdfs/' . $invoice['invoice_number'] . '.pdf', $invoice['invoice_number'] . '.pdf');
        }
		// Set email body
		$mail->Body = $email_template;
		$mail->AltBody = strip_tags($email_template);
		// Send mail
		$mail->send();
	} catch (Exception $e) {
		// Output error message
		exit('Error: Message could not be sent. Mailer Error: ' . $mail->ErrorInfo);
	}
}
// Send notification email function
function send_admin_invoice_email($invoice, $client) {
    if (!notifications_enabled || !mail_enabled) return;
     // Include PHPMailer library
    include public_path . 'lib/phpmailer/Exception.php';
    include public_path . 'lib/phpmailer/PHPMailer.php';
    include public_path . 'lib/phpmailer/SMTP.php';
    // Create an instance; passing `true` enables exceptions
    $mail = new PHPMailer(true);
    try {
        // Server settings
        if (SMTP) {
            $mail->isSMTP();
            $mail->Host = smtp_host;
            $mail->SMTPAuth = empty(smtp_user) && empty(smtp_pass) ? false : true;
            $mail->Username = smtp_user;
            $mail->Password = smtp_pass;
            $mail->SMTPSecure = smtp_secure == 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = smtp_port;
        }
        // Recipients
        $mail->setFrom(mail_from, mail_name);
        $mail->addAddress(notification_email);
        $mail->addReplyTo(mail_from, mail_name);
        // Content
        $mail->isHTML(true);
        // Set UTF-8 charset
        $mail->CharSet = 'UTF-8';
        // Set email subject
        if ($invoice['payment_status'] == 'Paid') {
            $mail->Subject = 'Invoice #' . $invoice['invoice_number'] . ' has been paid.';
        } else if ($invoice['payment_status'] == 'Cancelled') {
            $mail->Subject = 'Invoice #' . $invoice['invoice_number'] . ' has been cancelled.';
        } else if ($invoice['payment_status'] == 'Pending') {
            $mail->Subject = 'Invoice #' . $invoice['invoice_number'] . ' is pending payment.';
        } else {
            $mail->Subject = 'Invoice #' . $invoice['invoice_number'] . ' has been updated.';
        }
        // Read the template contents and replace the placeholders with the variables
        $email_template = str_replace(
            ['%invoice_number%', '%client%', '%amount%', '%status%', '%date%'],
            [$invoice['invoice_number'], $client['first_name'] . ' ' . $client['last_name'], number_format($invoice['payment_amount']+$invoice['tax_total'], 2), $invoice['payment_status'], date('Y-m-d H:i:s')],
            file_get_contents(base_path_invoices . 'invoice-system-templates/notification-email-template.html')
        );
 		// Set email body
        $mail->Body = $email_template;
        $mail->AltBody = strip_tags($email_template);
        // Send mail
        $mail->send();
    } catch (Exception $e) {
        // Output error message
        exit('Error: Message could not be sent. Mailer Error: ' . $mail->ErrorInfo);
    }
}   
//document system

// Determine the file icon function
function get_filetype_icon($filetype, $type = null) {
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

// Change directory permissions recursively function
function recursive_chmod($path, $perms) {
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
// Format file function
function get_formatted_file_data($file) {
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
// Get all directories function - they will be populated in the aside element
function get_directories($intial_dir, $level = 0) {
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
// Determine the relative path 
function determine_relative_path($path) {
    $intial_dir = str_replace('\\', '/', INITIAL_DIRECTORY);
    if (substr($path, 0, strlen($intial_dir)) == $intial_dir) {
        $path = ltrim(substr($path, strlen($intial_dir)), '/');
    } 
    return $path;
}
// Determine the full path function
function determine_full_path($path) {
    return rtrim(str_replace('\\', '/', INITIAL_DIRECTORY), '/') . '/' . determine_relative_path($path);
}
// Token verification function - will prevent the user from accessing files and directories they're not supposed to access
function verify_token($file, $token) {
    if (!VERIFY_TOKEN) return true;
    if (hash_hmac('sha256', $file, SECRET_KEY) == $token) {
        return true;
    }
    return false;
}
?>