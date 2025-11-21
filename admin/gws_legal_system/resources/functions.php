<?php
// Initialize the session
session_start();
// Include config file
include_once '../../../private/config.php';
// Namespaces for the PHPMailer library
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
// Connect to MySQL using PDO function
function pdo_connect_mysql() {
    try {
        // Connect to the MySQL database using PDO...
    	$pdo = new PDO('mysql:host=' . db_host . ';dbname=' . db_name . ';charset=' . db_charset, db_user, db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $exception) {
    	// Could not connect to the MySQL database, if this error occurs make sure you check your db settings are correct!
    	exit('Failed to connect to database!');
    }
    return $pdo;
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
function send_ticket_email($email, $id, $title, $msg, $priority, $category, $private, $status, $type = 'create', $name = '', $user_email = '') {
    if (!mail_enabled) return;
    // Ticket create subject
	$subject = 'Your ticket has been created #' . $id;
    // Ticket update subject
    $subject = $type == 'update' ? 'Your ticket has been updated #' . $id : $subject;
    // Ticket comment subject
    $subject = $type == 'comment' ? 'Someone has replied to your ticket #' . $id : $subject;
    // Ticket notification
    $subject = $type == 'notification' ? 'A user has submitted a ticket #' . $id : $subject;
    // Ticket URL
    $link = tickets_directory_url . 'view.php?id=' . $id . '&code=' . md5($id . $email);
    // Include the ticket email template as a string
    ob_start();
    include_once 'ticket-email-template.php';
    $ticket_email_template = ob_get_clean();
    // Include PHPMailer library
    require_once 'lib/phpmailer/Exception.php';
    require_once 'lib/phpmailer/PHPMailer.php';
    require_once 'lib/phpmailer/SMTP.php';
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
// Template header, feel free to customize this
function template_header($title) {
$login_link = isset($_SESSION['loggedin']) ? '<a href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a>' : '<a href="login.php"><i class="fas fa-lock"></i>Login</a>';
$admin_link = isset($_SESSION['loggedin']) && $_SESSION['account_role'] == 'Admin' ? '<a href="admin/index.php" target="_blank"><i class="fas fa-cog"></i>Admin</a>' : '';
$my_tickets_link = isset($_SESSION['loggedin']) ? '<a href="my-tickets.php"><i class="fas fa-user"></i>My Tickets</a>' : '';
echo <<<EOT
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
        <meta name="viewport" content="width=device-width,minimum-scale=1">
		<title>$title</title>
		<link href="style.css" rel="stylesheet" type="text/css">
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" integrity="sha512-xh6O/CkQoPOWDdYTDqeRdPCVd1SpvCA9XXcUnZS2FmJNp1coAFzvtCN9BmamE+4aHK8yyUHUSCcJHgXloTyT2A==" crossorigin="anonymous" referrerpolicy="no-referrer">
	</head>
	<body>
        <header class="header">

            <div class="wrapper">

                <h1><a href="index.php">Ticketing System</a></h1>

                <input type="checkbox" id="menu">
                <label for="menu">
                    <i class="fa-solid fa-bars"></i>
                </label>
                
                <nav class="menu">
                    <a href="create.php"><i class="fas fa-plus"></i>Create Ticket</a>
                    $my_tickets_link
                    <a href="tickets.php"><i class="fa-solid fa-list"></i>Browse</a>
                    $admin_link
                    $login_link
                </nav>

            </div>

        </header>
EOT;
}
// Template footer
function template_footer() {
echo <<<EOT
    <script>
    document.querySelectorAll('.content .toolbar .format-btn').forEach(element => element.onclick = () => {
        let textarea = document.querySelector('.content textarea');
        let text = '<strong></strong>';
        text = element.classList.contains('fa-italic') ? '<i></i>' : text;
        text = element.classList.contains('fa-underline') ? '<u></u>' : text;
        textarea.setRangeText(text, textarea.selectionStart, textarea.selectionEnd, 'select');
    });
    </script>
    </body>
</html>
EOT;
}
// Template admin header
function template_admin_header($title, $selected = 'orders', $selected_child = 'view') {
    $admin_links = '
        <a href="index.php"' . ($selected == 'dashboard' ? ' class="selected"' : '') . '><i class="fas fa-tachometer-alt"></i>Dashboard</a>
        <a href="tickets.php"' . ($selected == 'tickets' ? ' class="selected"' : '') . '><i class="fa-solid fa-ticket"></i>Tickets</a>
        <div class="sub">
            <a href="tickets.php"' . ($selected == 'tickets' && $selected_child == 'view' ? ' class="selected"' : '') . '><span>&#9724;</span>View Tickets</a>
            <a href="ticket.php"' . ($selected == 'tickets' && $selected_child == 'manage' ? ' class="selected"' : '') . '><span>&#9724;</span>Create Ticket</a>
            <a href="tickets_export.php"' . ($selected == 'tickets' && $selected_child == 'export' ? ' class="selected"' : '') . '><span>&#9724;</span>Export</a>
            <a href="tickets_import.php"' . ($selected == 'tickets' && $selected_child == 'import' ? ' class="selected"' : '') . '><span>&#9724;</span>Import</a>
        </div>
        <a href="comments.php"' . ($selected == 'comments' ? ' class="selected"' : '') . '><i class="fas fa-comments"></i>Comments</a>
        <div class="sub">
            <a href="comments.php"' . ($selected == 'comments' && $selected_child == 'view' ? ' class="selected"' : '') . '><span>&#9724;</span>View Comments</a>
            <a href="comment.php"' . ($selected == 'comments' && $selected_child == 'manage' ? ' class="selected"' : '') . '><span>&#9724;</span>Create Comment</a>
        </div>
        <a href="categories.php"' . ($selected == 'categories' ? ' class="selected"' : '') . '><i class="fas fa-list"></i>Categories</a>
        <div class="sub">
            <a href="categories.php"' . ($selected == 'categories' && $selected_child == 'view' ? ' class="selected"' : '') . '><span>&#9724;</span>View Categories</a>
            <a href="category.php"' . ($selected == 'categories' && $selected_child == 'manage' ? ' class="selected"' : '') . '><span>&#9724;</span>Create Category</a>
        </div>
        <a href="email-templates.php"' . ($selected == 'emailtemplates' ? ' class="selected"' : '') . '><i class="fa-solid fa-envelope"></i>Email Templates</a>
        <a href="accounts.php"' . ($selected == 'accounts' ? ' class="selected"' : '') . '><i class="fas fa-users"></i>Accounts</a>
        <div class="sub">
            <a href="accounts.php"' . ($selected == 'accounts' && $selected_child == 'view' ? ' class="selected"' : '') . '><span>&#9724;</span>View Accounts</a>
            <a href="account.php"' . ($selected == 'accounts' && $selected_child == 'manage' ? ' class="selected"' : '') . '><span>&#9724;</span>Create Account</a>
        </div>
        <a href="settings.php"' . ($selected == 'settings' ? ' class="selected"' : '') . '><i class="fas fa-tools"></i>Settings</a>
    ';
// DO NOT INDENT THE BELOW CODE
echo <<<EOT
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width,minimum-scale=1">
		<title>$title</title>
		<link href="admin.css" rel="stylesheet" type="text/css">
	</head>
	<body class="admin">
        <aside class="responsive-width-100 responsive-hidden">
            <h1>Admin</h1>
            $admin_links
            <div class="footer">
                <a href="https://codeshack.io/package/php/advanced-ticketing-system/" target="_blank">Advanced Ticketing System</a>
                Version 2.0.0
            </div>
        </aside>
        <main class="responsive-width-100">
            <header>
                <a class="responsive-toggle" href="#">
                    <i class="fas fa-bars"></i>
                </a>
                <div class="space-between"></div>
                <div class="dropdown right">
                    <i class="fas fa-user-circle"></i>
                    <div class="list">
                        <a href="account.php?id={$_SESSION['account_id']}">Edit Profile</a>
                        <a href="logout.php">Logout</a>
                    </div>
                </div>
            </header>
EOT;
}
// Template admin footer
function template_admin_footer($js_script = '') {
        $js_script = $js_script ? '<script>' . $js_script . '</script>' : '';
// DO NOT INDENT THE BELOW CODE
echo <<<EOT
        </main>
        <script src="admin.js"></script>
        {$js_script}
    </body>
</html>
EOT;
}
?>