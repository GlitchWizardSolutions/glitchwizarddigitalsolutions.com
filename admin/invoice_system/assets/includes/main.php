<?php
/*
ISSUES: 
The quick create invoice does not work in this section.
*/
include client_invoice_defines . 'defines.php';
include client_invoice_defines . 'main.php';
// Check if admin is logged in
if (!isset($_SESSION['loggedin'])) {
    header('Location: "https://glitchwizarddigitalsolutions.com/"');
    exit;
}
// If the user is not admin redirect them back to the home page
$stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
$stmt->execute([ $_SESSION['id'] ]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);
// Ensure account is an admin
if (!$account || $account['role'] != 'Admin') {
    header('Location: "https://glichwizarddigitalsolutions.com"');
    exit;
}
// Get the total number of accounts
$stmt = $pdo->query('SELECT COUNT(*) FROM accounts');
$accounts_total = $stmt->fetchColumn();
// Get the total number of events
$stmt = $pdo->query('SELECT COUNT(*) FROM invoices');
$invoices_total = $stmt->fetchColumn();
// Get the total number of clients
$stmt = $pdo->query('SELECT COUNT(*) FROM invoice_clients');
$clients_total = $stmt->fetchColumn();
// Icons for the table headers
$table_icons = [
    'asc' => '<svg width="10" height="10" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M350 177.5c3.8-8.8 2-19-4.6-26l-136-144C204.9 2.7 198.6 0 192 0s-12.9 2.7-17.4 7.5l-136 144c-6.6 7-8.4 17.2-4.6 26s12.5 14.5 22 14.5h88l0 192c0 17.7-14.3 32-32 32H32c-17.7 0-32 14.3-32 32v32c0 17.7 14.3 32 32 32l80 0c70.7 0 128-57.3 128-128l0-192h88c9.6 0 18.2-5.7 22-14.5z"/></svg>',
    'desc' => '<svg width="10" height="10" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M350 334.5c3.8 8.8 2 19-4.6 26l-136 144c-4.5 4.8-10.8 7.5-17.4 7.5s-12.9-2.7-17.4-7.5l-136-144c-6.6-7-8.4-17.2-4.6-26s12.5-14.5 22-14.5h88l0-192c0-17.7-14.3-32-32-32H32C14.3 96 0 81.7 0 64V32C0 14.3 14.3 0 32 0l80 0c70.7 0 128 57.3 128 128l0 192h88c9.6 0 18.2 5.7 22 14.5z"/></svg>'
];

// Template admin header
function template_admin_header($title, $selected = 'dashboard', $selected_child = 'view') {
    global $accounts_total, $invoices_total, $clients_total, $pdo;
    
    // Get count of unsent invoices for admin notification bell
    // Check if email_sent column exists first
    $unsent_invoices_count = 0;
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM invoices LIKE 'email_sent'");
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->query("
                SELECT COUNT(*) as count
                FROM invoices 
                WHERE email_sent = 0
                AND payment_status != 'Paid'
            ");
            $unsent_invoices_count = $stmt->fetchColumn();
        }
    } catch (Exception $e) {
        // Column doesn't exist yet, skip notification
        $unsent_invoices_count = 0;
    }
    
    // Admin links
    $admin_links = '
        <a href="../../index.php"' . ($selected == 'dashboard' ? ' class="selected"' : '') . ' title="Dashboard">
            <span class="icon">
                <svg width="15" height="15" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M0 256a256 256 0 1 1 512 0A256 256 0 1 1 0 256zm320 96c0-26.9-16.5-49.9-40-59.3V88c0-13.3-10.7-24-24-24s-24 10.7-24 24V292.7c-23.5 9.5-40 32.5-40 59.3c0 35.3 28.7 64 64 64s64-28.7 64-64zM144 176a32 32 0 1 0 0-64 32 32 0 1 0 0 64zm-16 80a32 32 0 1 0 -64 0 32 32 0 1 0 64 0zm288 32a32 32 0 1 0 0-64 32 32 0 1 0 0 64zM400 144a32 32 0 1 0 -64 0 32 32 0 1 0 64 0z"/></svg>            
            </span>
            <span class="txt">Dashboard</span>
        </a>
           <!--Accounts are not in this directory-->
           <a href="../../account_dash.php"' . ($selected == 'accounts' ? ' class="selected"' : '') . ' title="Accounts">
            <span class="icon">
                <svg width="15" height="15" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M144 0a80 80 0 1 1 0 160A80 80 0 1 1 144 0zM512 0a80 80 0 1 1 0 160A80 80 0 1 1 512 0zM0 298.7C0 239.8 47.8 192 106.7 192h42.7c15.9 0 31 3.5 44.6 9.7c-1.3 7.2-1.9 14.7-1.9 22.3c0 38.2 16.8 72.5 43.3 96c-.2 0-.4 0-.7 0H21.3C9.6 320 0 310.4 0 298.7zM405.3 320c-.2 0-.4 0-.7 0c26.6-23.5 43.3-57.8 43.3-96c0-7.6-.7-15-1.9-22.3c13.6-6.3 28.7-9.7 44.6-9.7h42.7C592.2 192 640 239.8 640 298.7c0 11.8-9.6 21.3-21.3 21.3H405.3zM224 224a96 96 0 1 1 192 0 96 96 0 1 1 -192 0zM128 485.3C128 411.7 187.7 352 261.3 352H378.7C452.3 352 512 411.7 512 485.3c0 14.7-11.9 26.7-26.7 26.7H154.7c-14.7 0-26.7-11.9-26.7-26.7z"/></svg>
            </span>
            <span class="txt">Accounts</span>
            <span class="note">' . ($accounts_total ? number_format($accounts_total) : 0) . '</span>
        </a>
        <!--Invoices are in this directory-->
        <a href="invoices_dash.php"' . ($selected == 'invoices' ? ' class="selected"' : '') . ' title="Invoices">
            <span class="icon">
                <svg width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M17 2H2V17H4V4H17V2M21 22L18.5 20.32L16 22L13.5 20.32L11 22L8.5 20.32L6 22V6H21V22M10 10V12H17V10H10M15 14H10V16H15V14Z" /></svg>
            </span>
            <span class="txt">Invoices</span>
            <span class="note">' . ($invoices_total ? number_format($invoices_total) : 0) . '</span>
        </a>
        <div class="sub">
            <a href="invoices.php"' . ($selected == 'invoices' && $selected_child == 'view' ? ' class="selected"' : '') . '><span class="square"></span>View Invoices</a>
            <a href="invoice.php"' . ($selected == 'invoices' && $selected_child == 'manage' ? ' class="selected"' : '') . '><span class="square"></span>Create Invoice</a>
            <a href="invoices_export.php"' . ($selected == 'invoices' && $selected_child == 'export' ? ' class="selected"' : '') . '><span class="square"></span>Export Invoices</a>
            <a href="invoices_import.php"' . ($selected == 'invoices' && $selected_child == 'import' ? ' class="selected"' : '') . '><span class="square"></span>Import Invoices</a>
            <a href="invoice_templates.php"' . ($selected == 'invoices' && $selected_child == 'templates' ? ' class="selected"' : '') . '><span class="square"></span>Templates</a>
        </div>
        <!--Clients are in this directory, they are who the invoices go to-->
        <a href="clients.php"' . ($selected == 'clients' ? ' class="selected"' : '') . ' title="Clients">
            <span class="icon">
                <svg width="15" height="15" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M96 128a128 128 0 1 0 256 0A128 128 0 1 0 96 128zm94.5 200.2l18.6 31L175.8 483.1l-36-146.9c-2-8.1-9.8-13.4-17.9-11.3C51.9 342.4 0 405.8 0 481.3c0 17 13.8 30.7 30.7 30.7H162.5c0 0 0 0 .1 0H168 280h5.5c0 0 0 0 .1 0H417.3c17 0 30.7-13.8 30.7-30.7c0-75.5-51.9-138.9-121.9-156.4c-8.1-2-15.9 3.3-17.9 11.3l-36 146.9L238.9 359.2l18.6-31c6.4-10.7-1.3-24.2-13.7-24.2H224 204.3c-12.4 0-20.1 13.6-13.7 24.2z"/></svg>
            </span>
            <span class="txt">Clients</span>
            <span class="note">' . ($clients_total ? number_format($clients_total) : 0) . '</span>
        </a>
        <div class="sub">
            <a href="clients.php"' . ($selected == 'clients' && $selected_child == 'view' ? ' class="selected"' : '') . '><span class="square"></span>View Clients</a>
            <a href="client.php"' . ($selected == 'clients' && $selected_child == 'manage' ? ' class="selected"' : '') . '><span class="square"></span>Create Client</a>
            <a href="clients_export.php"' . ($selected == 'clients' && $selected_child == 'export' ? ' class="selected"' : '') . '><span class="square"></span>Export Clients</a>
            <a href="clients_import.php"' . ($selected == 'clients' && $selected_child == 'import' ? ' class="selected"' : '') . '><span class="square"></span>Import Clients</a>
        </div>
        <!--Invoice Email Templates are in this directory-->
        <a href="email_templates.php"' . ($selected == 'email_templates' ? ' class="selected"' : '') . ' title="Email Templates">
            <span class="icon">
                <svg width="17" height="17" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M22,6V4L14,9L6,4V6L14,11L22,6M22,2A2,2 0 0,1 24,4V16A2,2 0 0,1 22,18H6C4.89,18 4,17.1 4,16V4C4,2.89 4.89,2 6,2H22M2,6V20H20V22H2A2,2 0 0,1 0,20V6H2Z" /></svg>
            </span>
            <span class="txt">Invoice Email Templates</span>
        </a>
       
        <a href="settings.php"' . ($selected == 'settings' ? ' class="selected"' : '') . ' title="Settings">
            <span class="icon">
                <svg width="15" height="15" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M78.6 5C69.1-2.4 55.6-1.5 47 7L7 47c-8.5 8.5-9.4 22-2.1 31.6l80 104c4.5 5.9 11.6 9.4 19 9.4h54.1l109 109c-14.7 29-10 65.4 14.3 89.6l112 112c12.5 12.5 32.8 12.5 45.3 0l64-64c12.5-12.5 12.5-32.8 0-45.3l-112-112c-24.2-24.2-60.6-29-89.6-14.3l-109-109V104c0-7.5-3.5-14.5-9.4-19L78.6 5zM19.9 396.1C7.2 408.8 0 426.1 0 444.1C0 481.6 30.4 512 67.9 512c18 0 35.3-7.2 48-19.9L233.7 374.3c-7.8-20.9-9-43.6-3.6-65.1l-61.7-61.7L19.9 396.1zM512 144c0-10.5-1.1-20.7-3.2-30.5c-2.4-11.2-16.1-14.1-24.2-6l-63.9 63.9c-3 3-7.1 4.7-11.3 4.7H352c-8.8 0-16-7.2-16-16V102.6c0-4.2 1.7-8.3 4.7-11.3l63.9-63.9c8.1-8.1 5.2-21.8-6-24.2C388.7 1.1 378.5 0 368 0C288.5 0 224 64.5 224 144l0 .8 85.3 85.3c36-9.1 75.8 .5 104 28.7L429 274.5c49-23 83-72.8 83-130.5zM56 432a24 24 0 1 1 48 0 24 24 0 1 1 -48 0z"/></svg>
            </span>
            <span class="txt">Invoice Settings</span>
        </a> 
    ';
    // Profile image
    $profile_img = '
    <div class="profile-img">
        <span style="background-color:' . color_from_string($_SESSION['name']) . '">' . strtoupper(substr($_SESSION['name'], 0, 1)) . '</span>
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
        <link rel="icon" type="image/png" href="https://glitchwizarddigitalsolutions.com/assets/imgs/purple-logo-sm.png">
        <link href="admin.css?v=20251125" rel="stylesheet" type="text/css">
    </head>
    <body class="admin">
        <aside>
            <h1>
              <!--  <span class="icon">A</span>-->
               <span class="title">Enjoy the Day!</span>
            </h1>
            ' . $admin_links . '
            <div class="footer">
                <a href="https://glitchwizardsolutions.com/" target="_blank">Invoice Management by GlitchWizard Solutions, LLC</a>
                Version 1.0.0
            </div>
        </aside>
        <main class="responsive-width-100">
            <header>
                <a class="responsive-toggle" href="#" title="Toggle Menu"></a>
                <a class="shortcut-link quick-create-invoice" href="#"><svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z" /></svg>Quick Create Invoice</a>
                <div class="space-between"></div>
                
                <!-- Unsent Invoices Notification Bell -->
                <div class="dropdown right" style="margin-right: 15px;">
                    <a href="invoices.php?filter=unsent" title="Unsent invoice emails" style="position: relative; display: inline-block; padding: 10px 15px; text-decoration: none; color: #333;">
                        <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="fill: ' . ($unsent_invoices_count > 0 ? 'rgb(120, 13, 227)' : '#999') . ';">
                            <path d="M48 64C21.5 64 0 85.5 0 112c0 15.1 7.1 29.3 19.2 38.4L236.8 313.6c11.4 8.5 27 8.5 38.4 0L492.8 150.4c12.1-9.1 19.2-23.3 19.2-38.4c0-26.5-21.5-48-48-48H48zM0 176V384c0 35.3 28.7 64 64 64H448c35.3 0 64-28.7 64-64V176L294.4 339.2c-22.8 17.1-54 17.1-76.8 0L0 176z"/>
                        </svg>
                        ' . ($unsent_invoices_count > 0 ? '<span style="position: absolute; top: 5px; right: 8px; background: #dc3545; color: white; border-radius: 50%; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: bold;">' . $unsent_invoices_count . '</span>' : '') . '
                    </a>
                </div>
                
                <div class="dropdown right">
                    ' . $profile_img . '
                    <div class="list">
                       
                        <a href="logout.php">Logout</a>
                    </div>
                </div>
            </header>';
}
// Template admin footer
function template_admin_footer($footer_code = '') {
// DO NOT INDENT THE BELOW CODE
echo '  </main>
        <script>
        const countries = ' . json_encode(get_countries()) . ';
        </script>
        <script src="admin.js"></script>
        ' . $footer_code . '
    </body>
</html>';
}
// Remove param from URL function
function remove_url_param($url, $param) {
    $url = preg_replace('/(&|\?)'.preg_quote($param).'=[^&]*$/', '', $url);
    $url = preg_replace('/(&|\?)'.preg_quote($param).'=[^&]*&/', '$1', $url);
    return $url;
}
// Get country list
function get_countries() {
    return ["Afghanistan", "Albania", "Algeria", "American Samoa", "Andorra", "Angola", "Anguilla", "Antarctica", "Antigua and Barbuda", "Argentina", "Armenia", "Aruba", "Australia", "Austria", "Azerbaijan", "Bahamas", "Bahrain", "Bangladesh", "Barbados", "Belarus", "Belgium", "Belize", "Benin", "Bermuda", "Bhutan", "Bolivia", "Bosnia and Herzegowina", "Botswana", "Bouvet Island", "Brazil", "British Indian Ocean Territory", "Brunei Darussalam", "Bulgaria", "Burkina Faso", "Burundi", "Cambodia", "Cameroon", "Canada", "Cape Verde", "Cayman Islands", "Central African Republic", "Chad", "Chile", "China", "Christmas Island", "Cocos (Keeling) Islands", "Colombia", "Comoros", "Congo", "Congo, the Democratic Republic of the", "Cook Islands", "Costa Rica", "Cote d'Ivoire", "Croatia (Hrvatska)", "Cuba", "Cyprus", "Czech Republic", "Denmark", "Djibouti", "Dominica", "Dominican Republic", "East Timor", "Ecuador", "Egypt", "El Salvador", "Equatorial Guinea", "Eritrea", "Estonia", "Ethiopia", "Falkland Islands (Malvinas)", "Faroe Islands", "Fiji", "Finland", "France", "France Metropolitan", "French Guiana", "French Polynesia", "French Southern Territories", "Gabon", "Gambia", "Georgia", "Germany", "Ghana", "Gibraltar", "Greece", "Greenland", "Grenada", "Guadeloupe", "Guam", "Guatemala", "Guinea", "Guinea-Bissau", "Guyana", "Haiti", "Heard and Mc Donald Islands", "Holy See (Vatican City State)", "Honduras", "Hong Kong", "Hungary", "Iceland", "India", "Indonesia", "Iran (Islamic Republic of)", "Iraq", "Ireland", "Israel", "Italy", "Jamaica", "Japan", "Jordan", "Kazakhstan", "Kenya", "Kiribati", "Korea, Democratic People's Republic of", "Korea, Republic of", "Kuwait", "Kyrgyzstan", "Lao, People's Democratic Republic", "Latvia", "Lebanon", "Lesotho", "Liberia", "Libyan Arab Jamahiriya", "Liechtenstein", "Lithuania", "Luxembourg", "Macau", "Macedonia, The Former Yugoslav Republic of", "Madagascar", "Malawi", "Malaysia", "Maldives", "Mali", "Malta", "Marshall Islands", "Martinique", "Mauritania", "Mauritius", "Mayotte", "Mexico", "Micronesia, Federated States of", "Moldova, Republic of", "Monaco", "Mongolia", "Montserrat", "Morocco", "Mozambique", "Myanmar", "Namibia", "Nauru", "Nepal", "Netherlands", "Netherlands Antilles", "New Caledonia", "New Zealand", "Nicaragua", "Niger", "Nigeria", "Niue", "Norfolk Island", "Northern Mariana Islands", "Norway", "Oman", "Pakistan", "Palau", "Panama", "Papua New Guinea", "Paraguay", "Peru", "Philippines", "Pitcairn", "Poland", "Portugal", "Puerto Rico", "Qatar", "Reunion", "Romania", "Russian Federation", "Rwanda", "Saint Kitts and Nevis", "Saint Lucia", "Saint Vincent and the Grenadines", "Samoa", "San Marino", "Sao Tome and Principe", "Saudi Arabia", "Senegal", "Seychelles", "Sierra Leone", "Singapore", "Slovakia (Slovak Republic)", "Slovenia", "Solomon Islands", "Somalia", "South Africa", "South Georgia and the South Sandwich Islands", "Spain", "Sri Lanka", "St. Helena", "St. Pierre and Miquelon", "Sudan", "Suriname", "Svalbard and Jan Mayen Islands", "Swaziland", "Sweden", "Switzerland", "Syrian Arab Republic", "Taiwan, Province of China", "Tajikistan", "Tanzania, United Republic of", "Thailand", "Togo", "Tokelau", "Tonga", "Trinidad and Tobago", "Tunisia", "Turkey", "Turkmenistan", "Turks and Caicos Islands", "Tuvalu", "Uganda", "Ukraine", "United Arab Emirates", "United Kingdom", "United States", "United States Minor Outlying Islands", "Uruguay", "Uzbekistan", "Vanuatu", "Venezuela", "Vietnam", "Virgin Islands (British)", "Virgin Islands (U.S.)", "Wallis and Futuna Islands", "Western Sahara", "Yemen", "Yugoslavia", "Zambia", "Zimbabwe"];
}
// Copy directory function
function copy_directory($source, $destination) {
    if (is_dir($source)) {
        @mkdir($destination);
        $directory = dir($source);
        while (false !== ($readdirectory = $directory->read())) {
            if ($readdirectory == '.' || $readdirectory == '..') {
                continue;
            }
            $PathDir = $source . '/' . $readdirectory;
            if (is_dir($PathDir)) {
                copy_directory($PathDir, $destination . '/' . $readdirectory);
                continue;
            }
            copy($PathDir, $destination . '/' . $readdirectory);
        }
        $directory->close();
    } else {
        copy($source, $destination);
    }
}
// Add transactions items to the database
function addItems($pdo, $invoice_number) {
    if (isset($_POST['item_id']) && is_array($_POST['item_id']) && count($_POST['item_id']) > 0) {
        // Iterate items
        $delete_list = [];
        for ($i = 0; $i < count($_POST['item_id']); $i++) {
            // If the item doesnt exist in the database
            if (!intval($_POST['item_id'][$i])) {
                // Insert new item
                $stmt = $pdo->prepare('INSERT INTO invoice_items (invoice_number, item_name, item_description, item_price, item_quantity) VALUES (?,?,?,?,?)');
                $stmt->execute([ $invoice_number, $_POST['item_name'][$i], $_POST['item_description'][$i], $_POST['item_price'][$i], $_POST['item_quantity'][$i] ]);
                $delete_list[] = $pdo->lastInsertId();
            } else {
                // Update existing item
                $stmt = $pdo->prepare('UPDATE invoice_items SET invoice_number = ?, item_name = ?, item_description = ?, item_price = ?, item_quantity = ? WHERE id = ?');
                $stmt->execute([ $invoice_number, $_POST['item_name'][$i], $_POST['item_description'][$i], $_POST['item_price'][$i], $_POST['item_quantity'][$i], $_POST['item_id'][$i] ]);
                $delete_list[] = $_POST['item_id'][$i];          
            }
        }
        // Delete item
        $in  = str_repeat('?,', count($delete_list) - 1) . '?';
        $stmt = $pdo->prepare('DELETE FROM invoice_items WHERE invoice_number = ? AND id NOT IN (' . $in . ')');
        $stmt->execute(array_merge([ $invoice_number ], $delete_list));
    } else {
        // No item exists, delete all
        $stmt = $pdo->prepare('DELETE FROM invoice_items WHERE invoice_number = ?');
        $stmt->execute([ $invoice_number ]);       
    }
}
?>