<?php
// Namespaces
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
// Send confirmation email
function send_confirmation_email($email, $id) {
    $content = '<div style="background-color:#eeeff1;font-family:-apple-system, BlinkMacSystemFont, \'segoe ui\', roboto, oxygen, ubuntu, cantarell, \'fira sans\', \'droid sans\', \'helvetica neue\', Arial, sans-serif;font-size:16px;padding:10px;">
    <div style="padding:60px;background-color:#fff;text-align:center;font-size:16px;max-width:600px;width:100%;margin:60px auto;">
        <h1 style="font-size:18px;color:#474a50;padding-bottom:10px;font-weight:500;">Confirmation Required</h1>
        <p style="font-size:16px;">
            Please click the following link to confirm your subscription:<br>
            <a href="' . website_url . 'confirm.php?id=' . $id . '" style="text-decoration:none;color:#e91c1c;font-size:16px;line-height:34px;">' . website_url . 'confirm.php?id=' . $id . '</a>
        </p>
    </div>
</div>';
    return admin_sendmail(mail_from, mail_from_name, $email, 'Subscription Confirmation Required', $content);
}
// Send mail function
function admin_sendmail($from, $name, $to, $subject, $content, $attachments = []) {
    // Use Microsoft Graph API for newsletter emails
    try {
        // Update content with placeholders
        $content = replace_placeholders($content);
        
        // Update name placeholder
        $recipient_name = htmlspecialchars(explode('@', $to)[0], ENT_QUOTES);
        $content = str_replace('%name%', $recipient_name, $content);
        
        // Wrap content in base template
        $html_body = base_template($content, $subject);
        
        // Send via Graph API with attachments
        $result = send_email_via_graph_with_attachments(
            $to,             // Recipient email
            $recipient_name, // Recipient name
            $subject,        // Email subject
            $html_body,      // HTML body with template
            $attachments,    // File attachments array
            mail_from,       // From email
            mail_from_name,  // From name
            'webmaster@glitchwizardsolutions.com', // Reply-to for newsletters
            'GlitchWizard Digital Solutions'       // Reply-to name
        );
        
        if ($result) {
            return 'success';
        } else {
            return 'Graph API failed to send email. Check error logs.';
        }
        
    } catch (Exception $e) {
        return 'Newsletter send error: ' . $e->getMessage();
    }
}
// Base template function
function base_template($content, $title = 'Newsletter') {
    return '<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width,minimum-scale=1">
		<title>' . $title . '</title>
	</head>
    <body style="padding:0;margin:0;">
    ' . $content . '
    </body>
</html>';
}
// Replace placeholders function
function replace_placeholders($content) {
    // Basic time/placeholders
    $content = str_replace(
        ['%year%', '%month%', '%day%', '%date%', '%time%', '%website_url%', '%company_name%'], 
        [date('Y'), date('m'), date('d'), date('Y-m-d'), date('H:i:s'), website_url, defined('company_name') ? company_name : ''], 
        $content
    );

    // Replace any custom placeholders defined in the admin UI (table: custom_placeholders)
    // Use the global $pdo connection if available
    global $pdo;
    if (isset($pdo)) {
        try {
            $placeholders = $pdo->query('SELECT placeholder_text, placeholder_value FROM custom_placeholders')->fetchAll(PDO::FETCH_ASSOC);
            if ($placeholders) {
                foreach ($placeholders as $ph) {
                    if (!empty($ph['placeholder_text'])) {
                        $content = str_replace($ph['placeholder_text'], $ph['placeholder_value'], $content);
                    }
                }
            }
        } catch (Exception $e) {
            // If the table doesn't exist or query fails, silently continue (non-fatal)
            error_log('replace_placeholders: could not load custom_placeholders - ' . $e->getMessage());
        }
    }

    return $content;
}
?>