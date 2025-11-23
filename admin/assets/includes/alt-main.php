<?php
/*
LOCATION: html_public/ADMIN/access/includes/main.php
LAUNCHED: 2022-11-05
EDIT:     2024-12-09 Preparation for 2025
EDIT:     2025-01-04 Bug Identified: Closing toggle aside.
EDIT:     2025-02-11 Bug Identified: elipses dropdown not opening.
*/
include includes_path . 'main.php';

// Check if the user is logged-in
check_loggedin($pdo, '../index.php');
// Fetch account details associated with the logged-in user
$stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
$stmt->execute([ $_SESSION['id'] ]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if the user is an admin...
if (!$account || $account['role'] != 'Admin') {
    header('Location: ../logout.php');
    exit;
}
// Add/remove roles from the list
$roles_list = ['Admin', 'Member', 'Manager'];
$access_list = ['Admin','Guest', 'Onboarding','Development','Production','Hosting','Services','Master', 'Closed', 'Banned'];

// Icons for the table headers
$table_icons = [
    'asc' => '<svg width="10" height="10" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M350 177.5c3.8-8.8 2-19-4.6-26l-136-144C204.9 2.7 198.6 0 192 0s-12.9 2.7-17.4 7.5l-136 144c-6.6 7-8.4 17.2-4.6 26s12.5 14.5 22 14.5h88l0 192c0 17.7-14.3 32-32 32H32c-17.7 0-32 14.3-32 32v32c0 17.7 14.3 32 32 32l80 0c70.7 0 128-57.3 128-128l0-192h88c9.6 0 18.2-5.7 22-14.5z"/></svg>',
    'desc' => '<svg width="10" height="10" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M350 334.5c3.8 8.8 2 19-4.6 26l-136 144c-4.5 4.8-10.8 7.5-17.4 7.5s-12.9-2.7-17.4-7.5l-136-144c-6.6-7-8.4-17.2-4.6-26s12.5-14.5 22-14.5h88l0-192c0-17.7-14.3-32-32-32H32C14.3 96 0 81.7 0 64V32C0 14.3 14.3 0 32 0l80 0c70.7 0 128 57.3 128 128l0 192h88c9.6 0 18.2 5.7 22 14.5z"/></svg>'
];
//For applications in the admin directory only
if (!function_exists('template_admin_header')){
// Template admin header
function template_admin_header($title, $selected = 'dashboard', $selected_child = 'view') {
    global $pdo; global $pdo_budget;  global $base_url; global  $outside_url;
    $base_url='https://glitchwizarddigitalsolutions.com/admin';
    $outside_url= 'https://glitchwizarddigitalsolutions.com';
    // Retrieve the counts
    // Retrieve the total number of campaigns
    $campaigns_total = $pdo->query('SELECT COUNT(*) FROM campaigns')->fetchColumn();
    // Retrieve the total number of newsletters
    $newsletters_total = $pdo->query('SELECT COUNT(*) FROM newsletters')->fetchColumn();
    // Retrieve the total number of subscribers
    $subscribers_total = $pdo->query('SELECT COUNT(*) FROM subscribers')->fetchColumn();
    $new_tickets  = $pdo->query('SELECT COUNT(*) AS total FROM tickets WHERE ticket_status = "open" AND cast(created as DATE) = cast(now() as DATE)')->fetchColumn();  
    $accounts_total = $pdo->query('SELECT COUNT(*) AS total FROM accounts')->fetchColumn();
    $blog_posts_total = $pdo->query('SELECT COUNT(*) AS total FROM blog_posts')->fetchColumn();
    $invoices_total = $pdo->query('SELECT COUNT(*) AS total FROM invoices')->fetchColumn();
    $clients_total  = $pdo->query('SELECT COUNT(*) AS total FROM invoice_clients')->fetchColumn();
    $unread_messages = $pdo->query('SELECT COUNT(*) AS total FROM messages WHERE status = "Unread"')->fetchColumn();
    $read_messages = $pdo->query('SELECT COUNT(*) AS total FROM messages WHERE status = "Read"')->fetchColumn();
    $open_tickets  = $pdo->query('SELECT COUNT(*) AS total FROM tickets WHERE ticket_status = "open"')->fetchColumn();
    $project_open_tickets  = $pdo->query('SELECT COUNT(*) AS total FROM project_tickets WHERE ticket_status = "open"')->fetchColumn();
    $legal_open_tickets  = $pdo->query('SELECT COUNT(*) AS total FROM gws_legal WHERE ticket_status = "open"')->fetchColumn();
    $new_comments = $pdo->query('SELECT COUNT(*) AS total FROM tickets WHERE last_comment = "Member" AND ticket_status != "closed"')->fetchColumn();
    $project_new_comments = $pdo->query('SELECT COUNT(*) AS total FROM project_tickets WHERE last_comment = "Member" AND ticket_status = "open"')->fetchColumn();
    $awaiting_response = $pdo->query('SELECT COUNT(*) AS total FROM tickets WHERE last_comment = "Admin"')->fetchColumn();
    $project_awaiting_response = $pdo->query('SELECT COUNT(*) AS total FROM project_tickets WHERE last_comment = "Admin"')->fetchColumn();
    // Admin HTML links
    $admin_links = '
    <!-- Dashboard Home-->
   <a href= "' . $base_url . '/index.php"' . ($selected == 'dashboard' ? ' class="selected"' : '') . ' title="Dashboard">
            <span class="icon"><svg width="15" height="15" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M0 256a256 256 0 1 1 512 0A256 256 0 1 1 0 256zm320 96c0-26.9-16.5-49.9-40-59.3V88c0-13.3-10.7-24-24-24s-24 10.7-24 24V292.7c-23.5 9.5-40 32.5-40 59.3c0 35.3 28.7 64 64 64s64-28.7 64-64zM144 176a32 32 0 1 0 0-64 32 32 0 1 0 0 64zm-16 80a32 32 0 1 0 -64 0 32 32 0 1 0 64 0zm288 32a32 32 0 1 0 0-64 32 32 0 1 0 0 64zM400 144a32 32 0 1 0 -64 0 32 32 0 1 0 64 0z"/></svg></span>
            <span class="txt">Dashboard</span></a>
 <!--Return to Home-->        
   <a href= "' . $outside_url . '/client-dashboard/index.php"' . ($selected == 'home' ? '      class="selected"' : '') . ' title="Return to Home">
            <span class="icon"><svg width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M17 2H2V17H4V4H17V2M21 22L18.5 20.32L16 22L13.5 20.32L11 22L8.5 20.32L6 22V6H21V22M10 10V12H17V10H10M15 14H10V16H15V14Z" /></svg></span>
            <span class="txt"> Return to Home</span>
            </a>           
<!--Resource Systems-->
   <a href= "' . $base_url . '/resource_system/index.php"' . ($selected == 'resources' ? '      class="selected"' : '') . ' title="Resource System">
            <span class="icon"><svg width="15" height="15" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M17 2H2V17H4V4H17V2M21 22L18.5 20.32L16 22L13.5 20.32L11 22L8.5 20.32L6 22V6H21V22M10 10V12H17V10H10M15 14H10V16H15V14Z" /></svg></span>
            <span class="txt">Resource System</span>
            </a>
            <div class="sub">
                <a href= "' . $base_url . '/resource_system/index.php"'                  . ($selected == 'resources' && $selected_child == 'dashboard' ? ' class="selected"' : '') . '><span class="square" style="background:#6610f2"></span>Dashboard</a>
                <a href= "' . $base_url . '/resource_system/domains.php"'                . ($selected == 'resources' && $selected_child == 'domains' ? ' class="selected"' : '') . '><span class="square" style="background:#6610f2"></span>Domains</a>
                <a href= "' . $base_url . '/resource_system/client-projects.php"'        . ($selected == 'resources' && $selected_child == 'projects' ? ' class="selected"' : '') . '><span class="square" style="background:#6610f2"></span>Client Projects</a>
                <a href= "' . $base_url . '/resource_system/sass-accounts.php"'          . ($selected == 'resources' && $selected_child == 'sass' ? ' class="selected"' : '') . '><span class="square" style="background:#6610f2"></span>SaaS Accounts</a>
                <a href= "' . $base_url . '/resource_system/financial-institutions.php"' . ($selected == 'resources' && $selected_child == 'cards' ? ' class="selected"' : '') . '><span class="square" style="background:#6610f2"></span>Financial Institutions</a>
                <a href= "' . $base_url . '/resource_system/warranties.php"'             . ($selected == 'resources' && $selected_child == 'warranties' ? ' class="selected"' : '') . '><span class="square" style="background:#6610f2"></span>Warranties</a>
                <a href= "' . $base_url . '/resource_system/error-logs.php"'             . ($selected == 'resources' && $selected_child == 'errors' ? ' class="selected"' : '') . '><span class="square" style="background:#6610f2"></span>Error Logs</a>
                <a href= "' . $base_url . '/resource_system/project-types.php"'          . ($selected == 'resources' && $selected_child == 'types' ? ' class="selected"' : '') . '><span class="square" style="background:#6610f2"></span>Project Types</a>
                <a href= "' . $base_url . '/resource_system/caches.php"'                 . ($selected == 'resources' && $selected_child == 'cache' ? ' class="selected"' : '') . '><span class="square" style="background:#6610f2"></span>Caches</a>
            </div>
    <!--Client Ticket System-->
   <a href= "' . $base_url . '/ticket_system/tickets.php"' . ($selected == 'tickets' ? ' class="selected"' : '') . ' title="Tickets">
            <span class="icon"><svg width="15" height="15" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M78.6 5C69.1-2.4 55.6-1.5 47 7L7 47c-8.5 8.5-9.4 22-2.1 31.6l80 104c4.5 5.9 11.6 9.4 19 9.4h54.1l109 109c-14.7 29-10 65.4 14.3 89.6l112 112c12.5 12.5 32.8 12.5 45.3 0l64-64c12.5-12.5 12.5-32.8 0-45.3l-112-112c-24.2-24.2-60.6-29-89.6-14.3l-109-109V104c0-7.5-3.5-14.5-9.4-19L78.6 5zM19.9 396.1C7.2 408.8 0 426.1 0 444.1C0 481.6 30.4 512 67.9 512c18 0 35.3-7.2 48-19.9L233.7 374.3c-7.8-20.9-9-43.6-3.6-65.1l-61.7-61.7L19.9 396.1zM512 144c0-10.5-1.1-20.7-3.2-30.5c-2.4-11.2-16.1-14.1-24.2-6l-63.9 63.9c-3 3-7.1 4.7-11.3 4.7H352c-8.8 0-16-7.2-16-16V102.6c0-4.2 1.7-8.3 4.7-11.3l63.9-63.9c8.1-8.1 5.2-21.8-6-24.2C388.7 1.1 378.5 0 368 0C288.5 0 224 64.5 224 144l0 .8 85.3 85.3c36-9.1 75.8 .5 104 28.7L429 274.5c49-23 83-72.8 83-130.5zM56 432a24 24 0 1 1 48 0 24 24 0 1 1 -48 0z"/></svg></span>
            <span class="txt">Client Tickets </span><span class="note" style="color:yellow"> ' . $new_comments . ' &nbsp; Unread </span>
            </a>
            <div class="sub">
                <a href= "' . $base_url . '/ticket_system/tickets.php"' . ($selected == 'tickets' && $selected_child == 'manage' ? ' class="selected"' : '') . '><span class="square" style="background:#6610f2"></span>Tickets &nbsp;<span style="color:yellow"> [' . $open_tickets . '] </span></a>
                <a href= "' . $base_url . '/ticket_system/comments.php"' . ($selected == 'tickets' && $selected_child == 'comments' ? ' class="selected"' : '') . '><span class="square" style="background:#6610f2"></span>Comments&nbsp;<span style="color:yellow"> [' . $new_comments . '] </span> </a>
                <a href= "' . $base_url . '/ticket_system/categories.php"' . ($selected == 'tickets' && $selected_child == 'catagories' ? ' class="selected"' : '') . '><span class="square" style="background:#6610f2"></span>Catagories</a>
            </div>
            
   <!--Project Ticket System-->
   <a href= "' . $base_url . '/project_system/tickets.php"' . ($selected == 'projects' ? ' class="selected"' : '') . ' title="Project System">
            <span class="icon"><svg width="15" height="15" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M78.6 5C69.1-2.4 55.6-1.5 47 7L7 47c-8.5 8.5-9.4 22-2.1 31.6l80 104c4.5 5.9 11.6 9.4 19 9.4h54.1l109 109c-14.7 29-10 65.4 14.3 89.6l112 112c12.5 12.5 32.8 12.5 45.3 0l64-64c12.5-12.5 12.5-32.8 0-45.3l-112-112c-24.2-24.2-60.6-29-89.6-14.3l-109-109V104c0-7.5-3.5-14.5-9.4-19L78.6 5zM19.9 396.1C7.2 408.8 0 426.1 0 444.1C0 481.6 30.4 512 67.9 512c18 0 35.3-7.2 48-19.9L233.7 374.3c-7.8-20.9-9-43.6-3.6-65.1l-61.7-61.7L19.9 396.1zM512 144c0-10.5-1.1-20.7-3.2-30.5c-2.4-11.2-16.1-14.1-24.2-6l-63.9 63.9c-3 3-7.1 4.7-11.3 4.7H352c-8.8 0-16-7.2-16-16V102.6c0-4.2 1.7-8.3 4.7-11.3l63.9-63.9c8.1-8.1 5.2-21.8-6-24.2C388.7 1.1 378.5 0 368 0C288.5 0 224 64.5 224 144l0 .8 85.3 85.3c36-9.1 75.8 .5 104 28.7L429 274.5c49-23 83-72.8 83-130.5zM56 432a24 24 0 1 1 48 0 24 24 0 1 1 -48 0z"/></svg></span>
            <span class="txt">Project Tickets&nbsp;<span class="note" style="color:yellow">' . $project_open_tickets . ' &nbsp; Open</span>
            </a>
            <div class="sub">
                <a href= "' . $base_url . '/project_system/tickets.php"' . ($selected == 'projects' && $selected_child == 'manage' ? ' class="selected"' : '') . '><span class="square" style="background:#6610f2"></span>Project Tickets</a>
                <a href= "' . $base_url . '/project_system/comments.php"' . ($selected == 'projects' && $selected_child == 'comments' ? ' class="selected"' : '') . '><span class="square" style="background:#6610f2"></span>Comments<span class="note">' . '&nbsp; ' . $project_new_comments . '/' . $project_awaiting_response . '</span></a>
                <a href= "' . $base_url . '/project_system/categories.php"' . ($selected == 'projects' && $selected_child == 'catagories' ? ' class="selected"' : '') . '><span class="square" style="background:#6610f2"></span>Catagories</a>
            </div>
    <!--Blog-->
   <a href= "' . $base_url . '/cms/index.php"' . ($selected == 'blog' ? ' class="selected"' : '') . ' title="Blog">
            <span class="icon"><svg width="15" height="15" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M144 0a80 80 0 1 1 0 160A80 80 0 1 1 144 0zM512 0a80 80 0 1 1 0 160A80 80 0 1 1 512 0zM0 298.7C0 239.8 47.8 192 106.7 192h42.7c15.9 0 31 3.5 44.6 9.7c-1.3 7.2-1.9 14.7-1.9 22.3c0 38.2 16.8 72.5 43.3 96c-.2 0-.4 0-.7 0H21.3C9.6 320 0 310.4 0 298.7zM405.3 320c-.2 0-.4 0-.7 0c26.6-23.5 43.3-57.8 43.3-96c0-7.6-.7-15-1.9-22.3c13.6-6.3 28.7-9.7 44.6-9.7h42.7C592.2 192 640 239.8 640 298.7c0 11.8-9.6 21.3-21.3 21.3H405.3zM224 224a96 96 0 1 1 192 0 96 96 0 1 1 -192 0zM128 485.3C128 411.7 187.7 352 261.3 352H378.7C452.3 352 512 411.7 512 485.3c0 14.7-11.9 26.7-26.7 26.7H154.7c-14.7 0-26.7-11.9-26.7-26.7z"/></svg></span>
            <span class="txt">Blog</span>
            <span class="note">' . ($blog_posts_total ? number_format($blog_posts_total) : 0) . '</span>
            </a>

   <!--Accounts-->
   <a href= "' . $base_url . '/client_accounts/account_dash.php"' . ($selected == 'accounts' ? ' class="selected"' : '') . ' title="Accounts">
            <span class="icon"><svg width="15" height="15" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M144 0a80 80 0 1 1 0 160A80 80 0 1 1 144 0zM512 0a80 80 0 1 1 0 160A80 80 0 1 1 512 0zM0 298.7C0 239.8 47.8 192 106.7 192h42.7c15.9 0 31 3.5 44.6 9.7c-1.3 7.2-1.9 14.7-1.9 22.3c0 38.2 16.8 72.5 43.3 96c-.2 0-.4 0-.7 0H21.3C9.6 320 0 310.4 0 298.7zM405.3 320c-.2 0-.4 0-.7 0c26.6-23.5 43.3-57.8 43.3-96c0-7.6-.7-15-1.9-22.3c13.6-6.3 28.7-9.7 44.6-9.7h42.7C592.2 192 640 239.8 640 298.7c0 11.8-9.6 21.3-21.3 21.3H405.3zM224 224a96 96 0 1 1 192 0 96 96 0 1 1 -192 0zM128 485.3C128 411.7 187.7 352 261.3 352H378.7C452.3 352 512 411.7 512 485.3c0 14.7-11.9 26.7-26.7 26.7H154.7c-14.7 0-26.7-11.9-26.7-26.7z"/></svg></span>
            <span class="txt">Accounts</span>
            <span class="note">' . ($accounts_total ? number_format($accounts_total) : 0) . '</span>
            </a>
            <div class="sub">
                <a href= "' . $base_url . '/client_accounts/accounts.php"' . ($selected == 'accounts' && $selected_child == 'view' ? ' class="selected"' : '') . '><span class="square" style="background:#6610f2"></span>Member Accounts</a>
               
                <a href= "' . $base_url . '/client_accounts/accounts_export.php"' . ($selected == 'accounts' && $selected_child == 'export' ? ' class="selected"' : '') . '><span class="square" style="background:#6610f2"></span>Export Accounts</a>
                <a href= "' . $base_url . '/client_accounts/accounts_import.php"' . ($selected == 'accounts' && $selected_child == 'import' ? ' class="selected"' : '') . '><span class="square" style="background:#6610f2"></span>Import Accounts</a>
                <a href= "' . $base_url . '/client_accounts/roles.php"' . ($selected == 'accounts' && $selected_child == 'roles' ? ' class="selected"' : '') . '><span class="square" style="background:#6610f2"></span>Access Levels</a>
            </div>
   <!--Invoice System-->     
   <a href= "' . $base_url . '/invoice_system/invoices.php"' . ($selected == 'invoices' ? ' class="selected"' : '') . ' title="Invoice System">
           <span class="icon"><svg width="17" height="17" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M22,6V4L14,9L6,4V6L14,11L22,6M22,2A2,2 0 0,1 24,4V16A2,2 0 0,1 22,18H6C4.89,18 4,17.1 4,16V4C4,2.89 4.89,2 6,2H22M2,6V20H20V22H2A2,2 0 0,1 0,20V6H2Z" /></svg></span>
            <span class="txt">Invoice System&nbsp;<span style="color:yellow"> * </span></span>
            <span class="note">' . ($invoices_total ? number_format($invoices_total) : 0) . '</span>
            </a>
             <div class="sub">
                <a href= "' . $base_url . '/invoice_system/invoices.php"' . ($selected == 'invoices' && $selected_child == 'invoices' ? ' class="selected"' : '') . '><span class="square" style="background:#6610f2"></span>Manage Invoices</a>
                 <a href= "' . $base_url . '/invoice_system/clients.php"' . ($selected == 'invoices' && $selected_child == 'clients' ? ' class="selected"' : '') . '><span class="square" style="background:#6610f2"></span>Manage Clients</a>
            </div>
   <!--Invoice Email Templates-->        
   <a href= "' . $base_url . '/invoice_system/email_templates.php"' . ($selected == 'email_templates' ? ' class="selected"' : '') . ' title="Email Templates">
            <span class="icon"><svg width="17" height="17" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M22,6V4L14,9L6,4V6L14,11L22,6M22,2A2,2 0 0,1 24,4V16A2,2 0 0,1 22,18H6C4.89,18 4,17.1 4,16V4C4,2.89 4.89,2 6,2H22M2,6V20H20V22H2A2,2 0 0,1 0,20V6H2Z" /></svg></span>
            <span class="txt">Email Templates&nbsp;<span style="color:red"> * </span></span>
            </a>

            
            
<!--Accounting System-->
           <a href= "' . $base_url . '/resource_system/accountings.php"' . ($selected == 'finances' ? ' class="selected"' : '') . ' title="Accounting System"> 
              <i class="fa-solid fa-money-bill-alt"></i>
               <span class="txt">Accounting System&nbsp;<span style="color:red"> * </span></span>
               </a>
            <div class="sub">
                <a href= "' . $base_url . '/resource_system/accounting-view.php"' . ($selected == 'finances' && $selected_child == 'manage' ? ' class="selected"' : '') . '><span class="square" style="background:#6610f2"></span>Expenses</a>
                <a href= "' . $base_url . '/resource_system/accountings.php"' . ($selected == 'finances' && $selected_child == 'report' ? ' class="selected"' : '') . '><span class="square" style="background:#6610f2"></span>Management</a>
                <a href= "' . $base_url . '/resource_system/accountings.php"' . ($selected == 'finances' && $selected_child == 'list' ? ' class="selected"' : '') . '><span class="square" style="background:#6610f2"></span>Reports</a>
            </div>
            

<!--Feedback-->        
   <a href= "' . $base_url . '/poll_system/poll_dashboard.php"' . ($selected == 'feedback' ? ' class="selected"' : '') . ' title="Feedback"> 
               <span class="icon"><svg width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M17 2H2V17H4V4H17V2M21 22L18.5 20.32L16 22L13.5 20.32L11 22L8.5 20.32L6 22V6H21V22M10 10V12H17V10H10M15 14H10V16H15V14Z" /></svg></span>
               <span class="txt">Feedback&nbsp;<span style="color:yellow"> * </span></span>
               </a>
            <div class="sub">
              <a href= "' . $base_url . '/poll_system/polls.php"' . ($selected == 'feedback' && $selected_child == 'polls' ? ' class="selected"' : '') . '><span class="square" style="background:#6610f2"></span>Polls</a>
            </div>    
<!--Contract Approvals-->        
   <a href= "' . $base_url . '/contract_system/messages.php"' . ($selected == 'messages' ? ' class="selected"' : '') . ' title="Contract Approvals">
            <span class="icon"><svg width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M17 2H2V17H4V4H17V2M21 22L18.5 20.32L16 22L13.5 20.32L11 22L8.5 20.32L6 22V6H21V22M10 10V12H17V10H10M15 14H10V16H15V14Z" /></svg></span>
            <span class="txt">Contract System&nbsp;<span style="color:yellow"> * </span></span> <span class="note">' . $unread_messages . '</span>
            </a>
<!--Newsletter System-->
        <a href= "' . $base_url . '/newsletter_system/campaigns.php"' . ($selected == 'campaigns' ? ' class="selected"' : '') . ' title="Campaigns">
            <span class="icon">
                <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19 3H5C3.9 3 3 3.9 3 5V19C3 20.1 3.9 21 5 21H19C20.1 21 21 20.1 21 19V5C21 3.9 20.1 3 19 3M7 7H9V9H7V7M7 11H9V13H7V11M7 15H9V17H7V15M17 17H11V15H17V17M17 13H11V11H17V13M17 9H11V7H17V9Z" /></svg>
            </span>
            <span class="txt">Campaigns</span>
            <span class="note">' . number_format($campaigns_total) . '</span>
        </a>
        <div class="sub">
            <a href= "' . $base_url . '/newsletter_system/campaigns.php"' . ($selected == 'campaigns' && $selected_child == 'view' ? ' class="selected"' : '') . '><span class="square"></span>View Campaigns</a>
            <a href= "' . $base_url . '/newsletter_system/campaign.php"' . ($selected == 'campaigns' && $selected_child == 'manage' ? ' class="selected"' : '') . '><span class="square"></span>Create Campaign</a>
            <a href= "' . $base_url . '/newsletter_system/campaigns_export.php"' . ($selected == 'campaigns' && $selected_child == 'export' ? ' class="selected"' : '') . '><span class="square"></span>Export Campaigns</a>
            <a href= "' . $base_url . '/newsletter_system/campaigns_import.php"' . ($selected == 'campaigns' && $selected_child == 'import' ? ' class="selected"' : '') . '><span class="square"></span>Import Campaigns</a>
        </div>
       <a href= "' . $base_url . '/newsletter_system/newsletters.php"' . ($selected == 'newsletters' ? ' class="selected"' : '') . ' title="Newsletters">
            <span class="icon">
                <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M20,8L12,13L4,8V6L12,11L20,6M20,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V6C22,4.89 21.1,4 20,4Z" /></svg>
            </span>
            <span class="txt">Newsletters</span>
            <span class="note">' . number_format($newsletters_total) . '</span>
        </a>
        <div class="sub">
            <a href= "' . $base_url . '/newsletter_system/newsletters.php"' . ($selected == 'newsletters' && $selected_child == 'view' ? ' class="selected"' : '') . '><span class="square"></span>View Newsletters</a>
            <a href= "' . $base_url . '/newsletter_system/newsletter.php"' . ($selected == 'newsletters' && $selected_child == 'manage' ? ' class="selected"' : '') . '><span class="square"></span>Create Newsletter</a>
            <a href= "' . $base_url . '/newsletter_system/newsletters_export.php"' . ($selected == 'newsletters' && $selected_child == 'export' ? ' class="selected"' : '') . '><span class="square"></span>Export Newsletter</a>
            <a href= "' . $base_url . '/newsletter_system/newsletters_import.php"' . ($selected == 'newsletters' && $selected_child == 'import' ? ' class="selected"' : '') . '><span class="square"></span>Import Newsletter</a>
        </div>
        <a href= "' . $base_url . '/newsletter_system/subscribers.php"' . ($selected == 'subscribers' ? ' class="selected"' : '') . ' title="Subscribers">
            <span class="icon">
                <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M16 17V19H2V17S2 13 9 13 16 17 16 17M12.5 7.5A3.5 3.5 0 1 0 9 11A3.5 3.5 0 0 0 12.5 7.5M15.94 13A5.32 5.32 0 0 1 18 17V19H22V17S22 13.37 15.94 13M15 4A3.39 3.39 0 0 0 13.07 4.59A5 5 0 0 1 13.07 10.41A3.39 3.39 0 0 0 15 11A3.5 3.5 0 0 0 15 4Z" /></svg>
            </span>
            <span class="txt">Subscribers</span>
            <span class="note">' . number_format($subscribers_total) . '</span>
        </a>
        <div class="sub">
            <a href= "' . $base_url . '/newsletter_system/subscribers.php"' . ($selected == 'subscribers' && $selected_child == 'view' ? ' class="selected"' : '') . '><span class="square"></span>View Subscribers</a>
            <a href= "' . $base_url . '/newsletter_system/subscriber.php"' . ($selected == 'subscribers' && $selected_child == 'manage' ? ' class="selected"' : '') . '><span class="square"></span>Create Subscriber</a>
            <a href= "' . $base_url . '/newsletter_system/groups.php"' . ($selected == 'subscribers' && $selected_child == 'groups' ? ' class="selected"' : '') . '><span class="square"></span>View Groups</a>
            <a href= "' . $base_url . '/newsletter_system/group.php"' . ($selected == 'subscribers' && $selected_child == 'group' ? ' class="selected"' : '') . '><span class="square"></span>Create Group</a>
            <a href= "' . $base_url . '/newsletter_system/subscribers_export.php"' . ($selected == 'subscribers' && $selected_child == 'export' ? ' class="selected"' : '') . '><span class="square"></span>Export Subscribers</a>
            <a href= "' . $base_url . '/newsletter_system/subscribers_import.php"' . ($selected == 'subscribers' && $selected_child == 'import' ? ' class="selected"' : '') . '><span class="square"></span>Import Subscribers</a>
        </div>
        <a href= "' . $base_url . '/newsletter_system/sendmail.php"' . ($selected == 'sendmail' ? ' class="selected"' : '') . ' title="Send Mail">
            <span class="icon">
                <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M2,21L23,12L2,3V10L17,12L2,14V21Z" /></svg>
            </span>
            <span class="txt">Send Mail</span>
        </a>
 
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
<!--Email Templates-->
   <a href= "' . $base_url . '/email_system/email-templates.php"' . ($selected == 'emailtemplates' ? ' class="selected"' : '') . ' title="Email Templates">
            <span class="icon"><svg width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M17 2H2V17H4V4H17V2M21 22L18.5 20.32L16 22L13.5 20.32L11 22L8.5 20.32L6 22V6H21V22M10 10V12H17V10H10M15 14H10V16H15V14Z" /></svg></span>
            <span class="txt">Email Templates</span>&nbsp;<span style="color:red"> * </span>
            </a>
            <div class="sub">
                <a href= "' . $base_url . '/email_system/email-templates.php"' . ($selected == 'emailtemplates' && $selected_child == 'system' ? ' class="selected"' : '') . '><span class="square" style="background:#6610f2"></span>Ticket Email</a>
                <a href= "' . $base_url . '/email_system/emailtemplate.php"' . ($selected == 'emailtemplates' && $selected_child == 'other' ? ' class="selected"' : '') . '><span class="square" style="background:#6610f2"></span>Account Emails</a>
            </div>
<!--Client Content-->
 
   <a href= "' . $base_url . '/gallery_system/collections.php"' . ($selected == 'allmedia' ? ' class="selected"' : '') . ' title="Client Content">
             <span class="icon"><svg width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M17 2H2V17H4V4H17V2M21 22L18.5 20.32L16 22L13.5 20.32L11 22L8.5 20.32L6 22V6H21V22M10 10V12H17V10H10M15 14H10V16H15V14Z" /></svg></span>
             <span class="txt">Client Content</span>&nbsp;<span style="color:yellow"> * </span>
             </a>
            <div class="sub">
                <a href= "' . $base_url . '/gallery_system/collections.php"' . ($selected == 'allmedia' && $selected_child == 'manage' ? ' class="selected"' : '') . '><span class="square" style="background:#6610f2"></span>Manage Content</a>
                <a href= "' . $base_url . '/gallery_system/allmedia.php"' . ($selected == 'allmedia' && $selected_child == 'view' ? ' class="selected"' : '') . '><span class="square" style="background:#6610f2"></span>View Media</a>
                <a href= "' . $base_url . '/gallery_system/likes.php"' . ($selected == 'allmedia' && $selected_child == 'likes' ? ' class="selected"' : '') . '><span class="square" style="background:#6610f2"></span>View Likes</a>
            </div>
  <!--Budget-->
           <a href= "' . $base_url . '/budget_system/bs_dashboard.php"' . ($selected == 'budget' ? ' class="selected"' : '') . ' title="Budget System">
            <span class="icon">
                <svg width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M17 2H2V17H4V4H17V2M21 22L18.5 20.32L16 22L13.5 20.32L11 22L8.5 20.32L6 22V6H21V22M10 10V12H17V10H10M15 14H10V16H15V14Z" /></svg>
            </span>
            <span class="txt">Budget System</span>
        </a>
        <div class="sub">
           <a href= "' . $base_url . '/budget_system/instructions-p1.php"' . ($selected == 'budget' && $selected_child == 'process' ? ' class="selected"' : '') . '><span class="square" style="background:#6610f2"></span><strong>Process Transactions</strong></a>
           <a href= "' . $base_url . '/budget_system/hancock-browse.php"' . ($selected == 'budget' && $selected_child == 'hancock' ? ' class="selected"' : '') . '><span class="square" style="background:#6610f2"></span><strong>Hancock Table</strong></a>
           <a href= "' . $base_url . '/budget_system/bills-browse.php"' . ($selected == 'budget' && $selected_child == 'bills' ? ' class="selected"' : '') . '><span class="square" style="background:#6610f2"></span><strong>Bills Table</strong></a>
           <a href= "' . $base_url . '/budget_system/notes-browse.php"' . ($selected == 'budget' && $selected_child == 'notes' ? ' class="selected"' : '') . '><span class="square" style="background:#6610f2"></span>Notes Table</a>
           <a href= "' . $base_url . '/budget_system/update-results-browse.php"' . ($selected == 'budget' && $selected_child == 'updateR' ? ' class="selected"' : '') . '><span class="square" style="background:#6610f2"></span><strong>Update Results Table</strong></a>
           <a href= "' . $base_url . '/budget_system/flags-browse.php"' . ($selected == 'budget' && $selected_child == 'flags' ? ' class="selected"' : '') . '><span class="square" style="background:#6610f2"></span><strong>Flags Table</strong></a>
           <a href= "' . $base_url . '/budget_system/bills-dash.php"' . ($selected == 'budget' && $selected_child == 'flags' ? ' class="selected"' : '') . '><span class="square" style="background:#6610f2"></span><strong>Bill Schedule</strong></a>
         </div>
            
            
   <!--Reporting-->
           <a href= "' . $base_url . '/budget_system/mom_report/"' . ($selected == 'budget' ? ' class="selected"' : '') . ' title="Reporting">
            <span class="icon">
                <svg width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M17 2H2V17H4V4H17V2M21 22L18.5 20.32L16 22L13.5 20.32L11 22L8.5 20.32L6 22V6H21V22M10 10V12H17V10H10M15 14H10V16H15V14Z" /></svg>
            </span>
            <span class="txt">Reporting System</span>
        </a>      
              <!--GWS Legal Requirements System-->
   <a href= "' . $base_url . '/gws_legal_system/tickets.php"' . ($selected == 'legal' ? ' class="selected"' : '') . ' title="GWS Legal Requirements">
            <span class="icon"><svg width="15" height="15" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M78.6 5C69.1-2.4 55.6-1.5 47 7L7 47c-8.5 8.5-9.4 22-2.1 31.6l80 104c4.5 5.9 11.6 9.4 19 9.4h54.1l109 109c-14.7 29-10 65.4 14.3 89.6l112 112c12.5 12.5 32.8 12.5 45.3 0l64-64c12.5-12.5 12.5-32.8 0-45.3l-112-112c-24.2-24.2-60.6-29-89.6-14.3l-109-109V104c0-7.5-3.5-14.5-9.4-19L78.6 5zM19.9 396.1C7.2 408.8 0 426.1 0 444.1C0 481.6 30.4 512 67.9 512c18 0 35.3-7.2 48-19.9L233.7 374.3c-7.8-20.9-9-43.6-3.6-65.1l-61.7-61.7L19.9 396.1zM512 144c0-10.5-1.1-20.7-3.2-30.5c-2.4-11.2-16.1-14.1-24.2-6l-63.9 63.9c-3 3-7.1 4.7-11.3 4.7H352c-8.8 0-16-7.2-16-16V102.6c0-4.2 1.7-8.3 4.7-11.3l63.9-63.9c8.1-8.1 5.2-21.8-6-24.2C388.7 1.1 378.5 0 368 0C288.5 0 224 64.5 224 144l0 .8 85.3 85.3c36-9.1 75.8 .5 104 28.7L429 274.5c49-23 83-72.8 83-130.5zM56 432a24 24 0 1 1 48 0 24 24 0 1 1 -48 0z"/></svg></span>
            <span class="txt">Legal Filings</span>
            </a>
            <div class="sub">
                <a href= "' . $base_url . '/gws_legal_system/tickets.php"' . ($selected == 'legal' && $selected_child == 'legal' ? ' class="selected"' : '') . '><span class="square" style="background:#6610f2"></span>Legal Requirements</a>
                <a href= "' . $base_url . '/gws_legal_system/comments.php"' . ($selected == 'legal' && $selected_child == 'comments' ? ' class="selected"' : '') . '><span class="square" style="background:#6610f2"></span>Notes<span class="note"></span></a>
                <a href= "' . $base_url . '/gws_legal_system/categories.php"' . ($selected == 'legal' && $selected_child == 'catagories' ? ' class="selected"' : '') . '><span class="square" style="background:#6610f2"></span>Catagories</a>
            </div>
<!--Admin Settings-->        
    <a href= "' . $base_url . '/settings.php"' . ($selected == 'settings' ? ' class="selected"' : '') . ' title="Settings">
            <span class="icon"><svg width="15" height="15" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M78.6 5C69.1-2.4 55.6-1.5 47 7L7 47c-8.5 8.5-9.4 22-2.1 31.6l80 104c4.5 5.9 11.6 9.4 19 9.4h54.1l109 109c-14.7 29-10 65.4 14.3 89.6l112 112c12.5 12.5 32.8 12.5 45.3 0l64-64c12.5-12.5 12.5-32.8 0-45.3l-112-112c-24.2-24.2-60.6-29-89.6-14.3l-109-109V104c0-7.5-3.5-14.5-9.4-19L78.6 5zM19.9 396.1C7.2 408.8 0 426.1 0 444.1C0 481.6 30.4 512 67.9 512c18 0 35.3-7.2 48-19.9L233.7 374.3c-7.8-20.9-9-43.6-3.6-65.1l-61.7-61.7L19.9 396.1zM512 144c0-10.5-1.1-20.7-3.2-30.5c-2.4-11.2-16.1-14.1-24.2-6l-63.9 63.9c-3 3-7.1 4.7-11.3 4.7H352c-8.8 0-16-7.2-16-16V102.6c0-4.2 1.7-8.3 4.7-11.3l63.9-63.9c8.1-8.1 5.2-21.8-6-24.2C388.7 1.1 378.5 0 368 0C288.5 0 224 64.5 224 144l0 .8 85.3 85.3c36-9.1 75.8 .5 104 28.7L429 274.5c49-23 83-72.8 83-130.5zM56 432a24 24 0 1 1 48 0 24 24 0 1 1 -48 0z"/></svg></span>
            <span class="txt" style="color:red"><span class="txt" style="color:yellow"> Caution: </span> Config Settings</span>
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
        <link rel="icon" type="image/png" <a href= "' . $outside_url . '/assets/imgs/purple-logo-sm.png">
        <link rel="stylesheet" type="text/css" href= "' . $base_url. '/assets/css/admin.css?key=time();">
        <link rel="stylesheet" type="text/css" href= "' . $base_url. '/assets/css/css_handler/invoice-system-admin.css?key=time();">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js">
    </script>
    </head>
    <body class="admin">
        <aside>
            <h1   style="background:#6610f2">
           
                <span class="title">Administration</span>
            </h1>
            ' . $admin_links . '
            <div class="footer">
                Admin Dashboard by <a href= "' . $outside_url . '/" target="_blank">GlitchWizard Solutions, LLC</a>
                Version 3.0.1
            </div>
        </aside>
        <main class="responsive-width-100">
            <header>
                <a class="responsive-toggle" href="#" title="Toggle Menu"></a>
                <a class="shortcut-link quick-create-invoice" href="#"><svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z" /></svg>Quick Create Invoice</a>
                <div class="space-between"></div>
                <div class="dropdown right">
                    ' . $profile_img . '
                    <div class="list">
                        <a  href= "' . $base_url . '/client_accounts/account.php?id=' . $_SESSION['id'] . '">Edit Profile</a>
                        <a  href= "' . $outside_url . '/logout.php">Logout</a>
                    </div>
                </div>
            </header>';
}
}//function exists
// Template admin footer
if (!function_exists('template_admin_footer')){
function template_admin_footer($footer_code = '') {
    global $outside_url; 
         $ajax_updates = ajax_updates;
         $ajax_interval = ajax_interval;
// DO NOT INDENT THE BELOW CODE
echo '  </main>
        <script>
        const countries = ' . json_encode(get_countries()) . ';
        const ajax_updates = ' . $ajax_updates . ';
        const ajax_interval = ' . $ajax_interval . ';
        </script>
        <script src="' . $outside_url . '/admin/assets/js/admin.js"></script>  
        <!-- Removed missing file: mdb.min.js -->
        <!-- <script src="' . $outside_url . '/admin/cms/assets/js/mdb.min.js"></script> -->  
        <!--<script src= "' . $outside_url . '/assets/js/js_handler/invoice-system-admin.js"></script>-->
        ' . $footer_code . '
        
    </body>
</html>';
}
}//function exists

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
// Add invoice transactions items to the database
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