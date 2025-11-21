<?php
/*******************************************************************************
 * CONTACT FORM HANDLER - Demo Confirmation Email
 * 
 * Sends a professional confirmation email to demonstrate form capabilities
 * Shows submitted data, user info, and technical metadata
 * 
 * LOCATION: /public_html/client-dashboard/forms/contact.php
 * UPDATED: 2025-11-21
 ******************************************************************************/

// Start session and load configuration
session_start();
require '../../../private/config.php';

// Load unified email system
require_once public_path . 'lib/email-system.php';

// Validate POST data
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request method.');
}

// Sanitize and validate input
$name = isset($_POST['name']) ? strip_tags(trim($_POST['name'])) : '';
$email = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL) : '';
$subject = isset($_POST['subject']) ? strip_tags(trim($_POST['subject'])) : '';
$message = isset($_POST['message']) ? strip_tags(trim($_POST['message'])) : '';

// Validation
if (empty($name) || empty($email) || empty($subject) || empty($message)) {
    die('Please fill in all required fields.');
}

if (!$email) {
    die('Please enter a valid email address.');
}

// Gather metadata
$ip_address = $_SERVER['REMOTE_ADDR'];
$user_agent = $_SERVER['HTTP_USER_AGENT'];
$timestamp = date('F j, Y \a\t g:i A T');
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'Direct visit';

// Get logged-in user information
$logged_in_user = isset($_SESSION['name']) ? $_SESSION['name'] : 'Guest';
$logged_in_email = isset($_SESSION['email']) ? $_SESSION['email'] : 'N/A';
$user_id = isset($_SESSION['id']) ? $_SESSION['id'] : 'N/A';

// Detect device type
function detect_device_type($user_agent) {
    if (preg_match('/mobile|android|iphone|ipad|ipod|blackberry|iemobile|opera mini/i', $user_agent)) {
        return 'Mobile Device';
    } elseif (preg_match('/tablet|ipad/i', $user_agent)) {
        return 'Tablet';
    } else {
        return 'Desktop/Laptop';
    }
}

// Detect browser
function detect_browser($user_agent) {
    if (strpos($user_agent, 'Edge') !== false) return 'Microsoft Edge';
    if (strpos($user_agent, 'Chrome') !== false) return 'Google Chrome';
    if (strpos($user_agent, 'Safari') !== false) return 'Safari';
    if (strpos($user_agent, 'Firefox') !== false) return 'Mozilla Firefox';
    if (strpos($user_agent, 'MSIE') !== false || strpos($user_agent, 'Trident') !== false) return 'Internet Explorer';
    return 'Unknown Browser';
}

// Detect operating system
function detect_os($user_agent) {
    if (preg_match('/windows|win32/i', $user_agent)) return 'Windows';
    if (preg_match('/macintosh|mac os x/i', $user_agent)) return 'macOS';
    if (preg_match('/linux/i', $user_agent)) return 'Linux';
    if (preg_match('/android/i', $user_agent)) return 'Android';
    if (preg_match('/iphone|ipad|ipod/i', $user_agent)) return 'iOS';
    return 'Unknown OS';
}

$device_type = detect_device_type($user_agent);
$browser = detect_browser($user_agent);
$operating_system = detect_os($user_agent);

// Prepare professional confirmation email
$email_subject = "Form Submission Confirmation - " . $subject;
$email_body = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            line-height: 1.6; 
            color: #333; 
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .email-container { 
            max-width: 650px; 
            margin: 20px auto; 
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header { 
            background: linear-gradient(135deg, #012970 0%, #1a4d9e 100%); 
            color: white; 
            padding: 30px 20px; 
            text-align: center; 
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        .content { 
            padding: 40px 30px; 
        }
        .intro {
            background: #f8f9fa;
            padding: 20px;
            border-left: 4px solid #012970;
            margin-bottom: 30px;
            border-radius: 4px;
        }
        .intro p {
            margin: 0;
            line-height: 1.8;
        }
        .section-title {
            color: #012970;
            font-size: 18px;
            font-weight: 600;
            margin: 30px 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            font-weight: 600;
            color: #495057;
            padding: 12px 15px 12px 0;
            width: 40%;
            vertical-align: top;
        }
        .info-value {
            display: table-cell;
            color: #212529;
            padding: 12px 0;
            word-break: break-word;
        }
        .info-block {
            margin-bottom: 20px;
        }
        .info-block .info-label {
            display: block;
            font-weight: 600;
            color: #495057;
            padding-bottom: 8px;
        }
        .info-block .info-value {
            display: block;
            color: #212529;
            padding: 12px 15px;
            background: #f8f9fa;
            border-radius: 4px;
            word-break: break-word;
        }
        .message-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 4px;
            margin: 15px 0;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .metadata {
            background: #fff8e1;
            padding: 20px;
            border-radius: 4px;
            margin-top: 20px;
        }
        .metadata .info-label {
            color: #856404;
        }
        .footer { 
            text-align: center; 
            padding: 30px 20px; 
            background: #f8f9fa;
            color: #6c757d; 
            font-size: 13px; 
        }
        .footer p {
            margin: 5px 0;
        }
        .footer a {
            color: #012970;
            text-decoration: none;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            background: #012970;
            color: white;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class='email-container'>
        <div class='header'>
            <h1>Form Submission Confirmation</h1>
            <p>GlitchWizard Solutions, LLC</p>
        </div>
        
        <div class='content'>
            <div class='intro'>
                <p><strong>Thank you for submitting the sample contact form at GlitchWizard Solutions!</strong></p>
                <p style='margin-top: 10px;'>This confirmation email demonstrates how form submissions can be professionally formatted and customized for your specific business needs. Below is a summary of your submission along with technical details that can be captured.</p>
            </div>
            
            <div class='section-title'>Your Submission Details</div>
            <div class='info-block'>
                <div class='info-label'>Name:</div>
                <div class='info-value'>" . htmlspecialchars($name) . "</div>
            </div>
            <div class='info-block'>
                <div class='info-label'>Email:</div>
                <div class='info-value'>" . htmlspecialchars($email) . "</div>
            </div>
            <div class='info-block'>
                <div class='info-label'>Subject:</div>
                <div class='info-value'>" . htmlspecialchars($subject) . "</div>
            </div>
            <div class='info-block'>
                <div class='info-label'>Submitted:</div>
                <div class='info-value'>" . $timestamp . "</div>
            </div>
            
            <div class='section-title'>Your Message</div>
            <div class='message-box'>" . htmlspecialchars($message) . "</div>
            
            <div class='section-title'>Account Information <span class='badge'>Logged In</span></div>
            <div class='info-grid'>
                <div class='info-row'>
                    <div class='info-label'>Username:</div>
                    <div class='info-value'>" . htmlspecialchars($logged_in_user) . "</div>
                </div>
            </div>
            <div class='info-block'>
                <div class='info-label'>Account Email:</div>
                <div class='info-value'>" . htmlspecialchars($logged_in_email) . "</div>
            </div>
            <div class='info-grid'>
                <div class='info-row'>
                    <div class='info-label'>User ID:</div>
                    <div class='info-value'>" . htmlspecialchars($user_id) . "</div>
                </div>
            </div>
            
            <div class='metadata'>
                <div class='section-title' style='margin-top: 0; border-color: #ffc107;'>Technical Metadata <span class='badge' style='background: #856404;'>Auto-Captured</span></div>
                <div class='info-grid'>
                    <div class='info-row'>
                        <div class='info-label'>IP Address:</div>
                        <div class='info-value'>" . htmlspecialchars($ip_address) . "</div>
                    </div>
                </div>
                <div class='info-block'>
                    <div class='info-label'>Device Type:</div>
                    <div class='info-value'>" . htmlspecialchars($device_type) . "</div>
                </div>
                <div class='info-grid'>
                    <div class='info-row'>
                        <div class='info-label'>Browser:</div>
                        <div class='info-value'>" . htmlspecialchars($browser) . "</div>
                    </div>
                    <div class='info-row'>
                        <div class='info-label'>Operating System:</div>
                        <div class='info-value'>" . htmlspecialchars($operating_system) . "</div>
                    </div>
                </div>
                <div class='info-block'>
                    <div class='info-label'>Referrer:</div>
                    <div class='info-value' style='font-size: 12px;'>" . htmlspecialchars($referer) . "</div>
                </div>
            </div>
            
            <div style='margin-top: 30px; padding: 20px; background: #e7f3ff; border-left: 4px solid #0066cc; border-radius: 4px;'>
                <p style='margin: 0; color: #004085;'><strong>What This Demonstrates:</strong></p>
                <p style='margin: 10px 0 0 0; color: #004085; font-size: 14px;'>
                    This sample form showcases how custom contact forms can capture visitor information, 
                    provide professional confirmations, and gather valuable analytics data - all while 
                    maintaining a polished, branded experience for your customers.
                </p>
            </div>
        </div>
        
        <div class='footer'>
            <p><strong>GlitchWizard Solutions, LLC</strong></p>
            <p>Professional Web Development & Digital Solutions</p>
            <p style='margin-top: 15px;'>
                <a href='https://glitchwizarddigitalsolutions.com'>glitchwizarddigitalsolutions.com</a>
            </p>
            <p style='margin-top: 15px; font-size: 11px; color: #999;'>
                This is an automated confirmation email from a demonstration contact form.<br>
                Emails like this can be fully customized with your branding, colors, and content.
            </p>
        </div>
    </div>
</body>
</html>
";

// Plain text version
$email_body_text = "FORM SUBMISSION CONFIRMATION\n";
$email_body_text .= "GlitchWizard Solutions, LLC\n";
$email_body_text .= str_repeat("=", 60) . "\n\n";
$email_body_text .= "Thank you for submitting the sample contact form!\n\n";
$email_body_text .= "YOUR SUBMISSION DETAILS:\n";
$email_body_text .= str_repeat("-", 60) . "\n";
$email_body_text .= "Name: " . $name . "\n";
$email_body_text .= "Email: " . $email . "\n";
$email_body_text .= "Subject: " . $subject . "\n";
$email_body_text .= "Submitted: " . $timestamp . "\n\n";
$email_body_text .= "YOUR MESSAGE:\n";
$email_body_text .= str_repeat("-", 60) . "\n";
$email_body_text .= $message . "\n\n";
$email_body_text .= "ACCOUNT INFORMATION:\n";
$email_body_text .= str_repeat("-", 60) . "\n";
$email_body_text .= "Username: " . $logged_in_user . "\n";
$email_body_text .= "Account Email: " . $logged_in_email . "\n";
$email_body_text .= "User ID: " . $user_id . "\n\n";
$email_body_text .= "TECHNICAL METADATA:\n";
$email_body_text .= str_repeat("-", 60) . "\n";
$email_body_text .= "IP Address: " . $ip_address . "\n";
$email_body_text .= "Device Type: " . $device_type . "\n";
$email_body_text .= "Browser: " . $browser . "\n";
$email_body_text .= "Operating System: " . $operating_system . "\n";
$email_body_text .= "Referrer: " . $referer . "\n\n";
$email_body_text .= str_repeat("=", 60) . "\n";
$email_body_text .= "This is a demonstration of custom form capabilities.\n";
$email_body_text .= "GlitchWizard Solutions - Professional Web Development\n";
$email_body_text .= "https://glitchwizarddigitalsolutions.com\n";

// Send email using PHPMailer directly
try {
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    // Configure SMTP
    configure_smtp_mail($mail);
    
    // Recipients
    $mail->setFrom('noreply@glitchwizarddigitalsolutions.com', 'GlitchWizard Solutions');
    $mail->addAddress($email, $name);
    $mail->addReplyTo('webmaster@glitchwizardsolutions.com', 'Webmaster');
    
    // Content
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Subject = $email_subject;
    $mail->Body = $email_body;
    $mail->AltBody = $email_body_text;
    
    // Send mail
    $mail->send();
    echo 'OK';
} catch (Exception $e) {
    die('An error occurred: ' . $mail->ErrorInfo);
}
?>
