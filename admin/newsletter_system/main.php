<?php
session_start();
// Include the configuration file
include_once '../config.php';
// Check if admin is logged in
if (!isset($_SESSION['account_loggedin'])) {
    header('Location: login.php');
    exit;
}
// Connet to the database
try {
    $pdo = new PDO('mysql:host=' . db_host . ';dbname=' . db_name . ';charset=' . db_charset, db_user, db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
    // If there is an error with the connection, stop the script and display the error.
    exit('Failed to connect to database: ' . $exception->getMessage());
}
// Retrieve account based on the session ID
$stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
$stmt->execute([ $_SESSION['account_id'] ]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);
// Ensure account is an admin or redirectc them to the login page
if (!$account || $account['role'] != 'Admin') {
    header('Location: login.php');
    exit;
}
// Roles list
$roles_list = ['Admin'];
// Icons for the table headers
$table_icons = [
    'asc' => '<svg width="10" height="10" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M350 177.5c3.8-8.8 2-19-4.6-26l-136-144C204.9 2.7 198.6 0 192 0s-12.9 2.7-17.4 7.5l-136 144c-6.6 7-8.4 17.2-4.6 26s12.5 14.5 22 14.5h88l0 192c0 17.7-14.3 32-32 32H32c-17.7 0-32 14.3-32 32v32c0 17.7 14.3 32 32 32l80 0c70.7 0 128-57.3 128-128l0-192h88c9.6 0 18.2-5.7 22-14.5z"/></svg>',
    'desc' => '<svg width="10" height="10" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M350 334.5c3.8 8.8 2 19-4.6 26l-136 144c-4.5 4.8-10.8 7.5-17.4 7.5s-12.9-2.7-17.4-7.5l-136-144c-6.6-7-8.4-17.2-4.6-26s12.5-14.5 22-14.5h88l0-192c0-17.7-14.3-32-32-32H32C14.3 96 0 81.7 0 64V32C0 14.3 14.3 0 32 0l80 0c70.7 0 128 57.3 128 128l0 192h88c9.6 0 18.2 5.7 22 14.5z"/></svg>'
];
// Retrieve the total number of campaigns
$campaigns_total = $pdo->query('SELECT COUNT(*) FROM campaigns')->fetchColumn();
// Retrieve the total number of newsletters
$newsletters_total = $pdo->query('SELECT COUNT(*) FROM newsletters')->fetchColumn();
// Retrieve the total number of subscribers
$subscribers_total = $pdo->query('SELECT COUNT(*) FROM subscribers')->fetchColumn();
// Retrieve the total number of accounts
$accounts_total = $pdo->query('SELECT COUNT(*) FROM accounts')->fetchColumn();
// Template admin header
function template_admin_header($title, $selected = 'orders', $selected_child = 'view') {
    global $campaigns_total, $newsletters_total, $subscribers_total, $accounts_total;
    $admin_links = '
        <a href="index.php"' . ($selected == 'dashboard' ? ' class="selected"' : '') . ' title="Dashboard">
            <span class="icon">
                <svg width="15" height="15" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M0 256a256 256 0 1 1 512 0A256 256 0 1 1 0 256zm320 96c0-26.9-16.5-49.9-40-59.3V88c0-13.3-10.7-24-24-24s-24 10.7-24 24V292.7c-23.5 9.5-40 32.5-40 59.3c0 35.3 28.7 64 64 64s64-28.7 64-64zM144 176a32 32 0 1 0 0-64 32 32 0 1 0 0 64zm-16 80a32 32 0 1 0 -64 0 32 32 0 1 0 64 0zm288 32a32 32 0 1 0 0-64 32 32 0 1 0 0 64zM400 144a32 32 0 1 0 -64 0 32 32 0 1 0 64 0z"/></svg>            
            </span>
            <span class="txt">Dashboard</span>
        </a>
        <a href="campaigns.php"' . ($selected == 'campaigns' ? ' class="selected"' : '') . ' title="Campaigns">
            <span class="icon">
                <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19 3H5C3.9 3 3 3.9 3 5V19C3 20.1 3.9 21 5 21H19C20.1 21 21 20.1 21 19V5C21 3.9 20.1 3 19 3M7 7H9V9H7V7M7 11H9V13H7V11M7 15H9V17H7V15M17 17H11V15H17V17M17 13H11V11H17V13M17 9H11V7H17V9Z" /></svg>
            </span>
            <span class="txt">Campaigns</span>
            <span class="note">' . num_format($campaigns_total) . '</span>
        </a>
        <div class="sub">
            <a href="campaigns.php"' . ($selected == 'campaigns' && $selected_child == 'view' ? ' class="selected"' : '') . '><span class="square"></span>View Campaigns</a>
            <a href="campaign.php"' . ($selected == 'campaigns' && $selected_child == 'manage' ? ' class="selected"' : '') . '><span class="square"></span>Create Campaign</a>
            <a href="campaigns_export.php"' . ($selected == 'campaigns' && $selected_child == 'export' ? ' class="selected"' : '') . '><span class="square"></span>Export Campaigns</a>
            <a href="campaigns_import.php"' . ($selected == 'campaigns' && $selected_child == 'import' ? ' class="selected"' : '') . '><span class="square"></span>Import Campaigns</a>
        </div>
        <a href="newsletters.php"' . ($selected == 'newsletters' ? ' class="selected"' : '') . ' title="Newsletters">
            <span class="icon">
                <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M20,8L12,13L4,8V6L12,11L20,6M20,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V6C22,4.89 21.1,4 20,4Z" /></svg>
            </span>
            <span class="txt">Newsletters</span>
            <span class="note">' . num_format($newsletters_total) . '</span>
        </a>
        <div class="sub">
            <a href="newsletters.php"' . ($selected == 'newsletters' && $selected_child == 'view' ? ' class="selected"' : '') . '><span class="square"></span>View Newsletters</a>
            <a href="newsletter.php"' . ($selected == 'newsletters' && $selected_child == 'manage' ? ' class="selected"' : '') . '><span class="square"></span>Create Newsletter</a>
            <a href="newsletters_export.php"' . ($selected == 'newsletters' && $selected_child == 'export' ? ' class="selected"' : '') . '><span class="square"></span>Export Newsletter</a>
            <a href="newsletters_import.php"' . ($selected == 'newsletters' && $selected_child == 'import' ? ' class="selected"' : '') . '><span class="square"></span>Import Newsletter</a>
        </div>
        <a href="subscribers.php"' . ($selected == 'subscribers' ? ' class="selected"' : '') . ' title="Subscribers">
            <span class="icon">
                <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M16 17V19H2V17S2 13 9 13 16 17 16 17M12.5 7.5A3.5 3.5 0 1 0 9 11A3.5 3.5 0 0 0 12.5 7.5M15.94 13A5.32 5.32 0 0 1 18 17V19H22V17S22 13.37 15.94 13M15 4A3.39 3.39 0 0 0 13.07 4.59A5 5 0 0 1 13.07 10.41A3.39 3.39 0 0 0 15 11A3.5 3.5 0 0 0 15 4Z" /></svg>
            </span>
            <span class="txt">Subscribers</span>
            <span class="note">' . num_format($subscribers_total) . '</span>
        </a>
        <div class="sub">
            <a href="subscribers.php"' . ($selected == 'subscribers' && $selected_child == 'view' ? ' class="selected"' : '') . '><span class="square"></span>View Subscribers</a>
            <a href="subscriber.php"' . ($selected == 'subscribers' && $selected_child == 'manage' ? ' class="selected"' : '') . '><span class="square"></span>Create Subscriber</a>
            <a href="groups.php"' . ($selected == 'subscribers' && $selected_child == 'groups' ? ' class="selected"' : '') . '><span class="square"></span>View Groups</a>
            <a href="group.php"' . ($selected == 'subscribers' && $selected_child == 'group' ? ' class="selected"' : '') . '><span class="square"></span>Create Group</a>
            <a href="subscribers_export.php"' . ($selected == 'subscribers' && $selected_child == 'export' ? ' class="selected"' : '') . '><span class="square"></span>Export Subscribers</a>
            <a href="subscribers_import.php"' . ($selected == 'subscribers' && $selected_child == 'import' ? ' class="selected"' : '') . '><span class="square"></span>Import Subscribers</a>
        </div>
        <a href="sendmail.php"' . ($selected == 'sendmail' ? ' class="selected"' : '') . ' title="Send Mail">
            <span class="icon">
                <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M2,21L23,12L2,3V10L17,12L2,14V21Z" /></svg>
            </span>
            <span class="txt">Send Mail</span>
        </a>
        <a href="accounts.php"' . ($selected == 'accounts' ? ' class="selected"' : '') . ' title="Accounts">
            <span class="icon">
                <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12,5.5A3.5,3.5 0 0,1 15.5,9A3.5,3.5 0 0,1 12,12.5A3.5,3.5 0 0,1 8.5,9A3.5,3.5 0 0,1 12,5.5M5,8C5.56,8 6.08,8.15 6.53,8.42C6.38,9.85 6.8,11.27 7.66,12.38C7.16,13.34 6.16,14 5,14A3,3 0 0,1 2,11A3,3 0 0,1 5,8M19,8A3,3 0 0,1 22,11A3,3 0 0,1 19,14C17.84,14 16.84,13.34 16.34,12.38C17.2,11.27 17.62,9.85 17.47,8.42C17.92,8.15 18.44,8 19,8M5.5,18.25C5.5,16.18 8.41,14.5 12,14.5C15.59,14.5 18.5,16.18 18.5,18.25V20H5.5V18.25M0,20V18.5C0,17.11 1.89,15.94 4.45,15.6C3.86,16.28 3.5,17.22 3.5,18.25V20H0M24,20H20.5V18.25C20.5,17.22 20.14,16.28 19.55,15.6C22.11,15.94 24,17.11 24,18.5V20Z" /></svg>
            </span>
            <span class="txt">Accounts</span>
            <span class="note">' . num_format($accounts_total) . '</span>
        </a>
        <div class="sub">
            <a href="accounts.php"' . ($selected == 'accounts' && $selected_child == 'view' ? ' class="selected"' : '') . '><span class="square"></span>View Accounts</a>
            <a href="account.php"' . ($selected == 'accounts' && $selected_child == 'manage' ? ' class="selected"' : '') . '><span class="square"></span>Create Account</a>
            <a href="accounts_export.php"' . ($selected == 'accounts' && $selected_child == 'export' ? ' class="selected"' : '') . '><span class="square"></span>Export Accounts</a>
            <a href="accounts_import.php"' . ($selected == 'accounts' && $selected_child == 'import' ? ' class="selected"' : '') . '><span class="square"></span>Import Accounts</a>
        </div>
        <a href="settings.php"' . ($selected == 'settings' ? ' class="selected"' : '') . ' title="Settings">
            <span class="icon">
                <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12,15.5A3.5,3.5 0 0,1 8.5,12A3.5,3.5 0 0,1 12,8.5A3.5,3.5 0 0,1 15.5,12A3.5,3.5 0 0,1 12,15.5M19.43,12.97C19.47,12.65 19.5,12.33 19.5,12C19.5,11.67 19.47,11.34 19.43,11L21.54,9.37C21.73,9.22 21.78,8.95 21.66,8.73L19.66,5.27C19.54,5.05 19.27,4.96 19.05,5.05L16.56,6.05C16.04,5.66 15.5,5.32 14.87,5.07L14.5,2.42C14.46,2.18 14.25,2 14,2H10C9.75,2 9.54,2.18 9.5,2.42L9.13,5.07C8.5,5.32 7.96,5.66 7.44,6.05L4.95,5.05C4.73,4.96 4.46,5.05 4.34,5.27L2.34,8.73C2.21,8.95 2.27,9.22 2.46,9.37L4.57,11C4.53,11.34 4.5,11.67 4.5,12C4.5,12.33 4.53,12.65 4.57,12.97L2.46,14.63C2.27,14.78 2.21,15.05 2.34,15.27L4.34,18.73C4.46,18.95 4.73,19.03 4.95,18.95L7.44,17.94C7.96,18.34 8.5,18.68 9.13,18.93L9.5,21.58C9.54,21.82 9.75,22 10,22H14C14.25,22 14.46,21.82 14.5,21.58L14.87,18.93C15.5,18.67 16.04,18.34 16.56,17.94L19.05,18.95C19.27,19.03 19.54,18.95 19.66,18.73L21.66,15.27C21.78,15.05 21.73,14.78 21.54,14.63L19.43,12.97Z" /></svg>
            </span>
            <span class="txt">Settings</span>
        </a>
        <div class="sub">
            <a href="settings.php"' . ($selected == 'settings' && $selected_child == 'view' ? ' class="selected"' : '') . '><span class="square"></span>General</a>
            <a href="custom_placeholders.php"' . ($selected == 'settings' && $selected_child == 'custom_placeholders' ? ' class="selected"' : '') . '><span class="square"></span>Custom Placeholders</a>
        </div>
    ';
    // Profile image
    $profile_img = '
    <div class="profile-img">
        <span style="background-color:' . color_from_string($_SESSION['account_name']) . '">' . strtoupper(substr($_SESSION['account_name'], 0, 1)) . '</span>
        <i class="online"></i>
    </div>
    ';
// Indenting the below code may cause an error
echo '<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,minimum-scale=1">
        <title>' . $title . '</title>
        <link href="admin.css" rel="stylesheet" type="text/css">
    </head>
    <body class="admin">
        <aside>
            <h1>
                <span class="icon">A</span>
                <span class="title">Admin</span>
            </h1>
            ' . $admin_links . '
            <div class="footer">
                <a href="https://codeshack.io/package/php/advanced-newsletter-mailing-system/" target="_blank">Advanced Newsletter System</a>
                Version 2.0.0
            </div>
        </aside>
        <main class="responsive-width-100">
            <header>
                <a class="responsive-toggle" href="#" title="Toggle Menu"></a>
                <div class="space-between"></div>
                <div class="dropdown right">
                    ' . $profile_img . '
                    <div class="list">
                        <a href="account.php?id=' . $_SESSION['account_id'] . '">Edit Profile</a>
                        <a href="logout.php" class="red">Logout</a>
                    </div>
                </div>
            </header>';
}
// Template admin footer
function template_admin_footer($footer_code = '') {
        $ajax_updates = ajax_updates;
        $ajax_interval = ajax_interval;
// DO NOT INDENT THE BELOW CODE
echo '  </main>
        <script>
        const ajax_updates = ' . $ajax_updates . ';
        const ajax_interval = ' . $ajax_interval . ';
        </script>
        <script src="admin.js"></script>
        ' . $footer_code . '
    </body>
</html>';
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
// Remove param from URL function
function remove_url_param($url, $param) {
    $url = preg_replace('/(&|\?)'.preg_quote($param).'=[^&]*$/', '', $url);
    $url = preg_replace('/(&|\?)'.preg_quote($param).'=[^&]*&/', '$1', $url);
    return $url;
}
// Num format function
function num_format($num, $decimals  = 0, $decimal_separator = '.', $thousands_separator = ',') {
    return number_format(empty($num) || $num == null || !is_numeric($num) ? 0 : $num, $decimals, $decimal_separator, $thousands_separator);
}
?>