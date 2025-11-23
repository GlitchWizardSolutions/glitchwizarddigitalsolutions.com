<?php
/*******************************************************************************
TICKETING SYSTEM DASHBOARD
LOCATION: /public_html/admin/
DESCRIBE: Unified dashboard for Client Tickets and Project Tickets overview
INPUTREQ: 
LOGGEDIN: REQUIRED ADMIN
REQUIRED:
  SYSTEM: DATABASE,LOGIN
   ADMIN: /public_html/admin/
DATABASE: TABLES tickets, project_tickets, tickets_comments, project_tickets_comments
LOG NOTE: Created 2025-01-21 - Unified ticketing dashboard
*******************************************************************************/
require 'assets/includes/admin_config.php';
include_once 'assets/includes/components.php';

$current_date = date('Y-m-d');

// ===== CLIENT TICKETS STATS =====
// New client tickets today
$stmt = $pdo->prepare('SELECT t.*, (SELECT COUNT(tc.id) FROM tickets_comments tc WHERE tc.ticket_id = t.id) AS num_comments, (SELECT GROUP_CONCAT(tu.filepath) FROM tickets_uploads tu WHERE tu.ticket_id = t.id) AS imgs, c.title AS category, a.full_name AS p_full_name, a.email AS a_email FROM tickets t LEFT JOIN categories c ON c.id = t.category_id LEFT JOIN accounts a ON t.acc_id = a.id WHERE cast(t.created as DATE) = cast(now() as DATE) ORDER BY t.priority DESC, t.created DESC');
$stmt->execute();
$new_client_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Client tickets awaiting response
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM tickets WHERE (ticket_status = "open" AND last_comment = "Member")');
$stmt->execute();
$client_awaiting_response = $stmt->fetchColumn();

// Open client tickets total
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM tickets WHERE ticket_status = "open"');
$stmt->execute();
$open_client_tickets = $stmt->fetchColumn();

// Resolved client tickets today
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM tickets WHERE ticket_status = "resolved" AND cast(last_update as DATE) = cast(now() as DATE)');
$stmt->execute();
$resolved_client_today = $stmt->fetchColumn();

// ===== PROJECT TICKETS STATS =====
// New project tickets today
$stmt = $pdo->prepare('SELECT t.*, (SELECT COUNT(tc.id) FROM project_tickets_comments tc WHERE tc.ticket_id = t.id) AS num_comments FROM project_tickets t WHERE cast(t.created as DATE) = cast(now() as DATE) ORDER BY t.priority DESC, t.created DESC');
$stmt->execute();
$new_project_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Project tickets awaiting notes
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM project_tickets WHERE (ticket_status = "open" AND last_comment = "Member")');
$stmt->execute();
$project_awaiting_notes = $stmt->fetchColumn();

// Open project tickets total
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM project_tickets WHERE ticket_status = "open"');
$stmt->execute();
$open_project_tickets = $stmt->fetchColumn();

// Resolved project tickets today
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM project_tickets WHERE ticket_status = "resolved" AND cast(last_update as DATE) = cast(now() as DATE)');
$stmt->execute();
$resolved_projects_today = $stmt->fetchColumn();

// Overdue project reviews
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM project_tickets WHERE reminder_date < CURDATE() AND ticket_status != "closed" AND reminder_date != "9999-12-31"');
$stmt->execute();
$overdue_reviews = $stmt->fetchColumn();

// Projects due today
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM project_tickets WHERE reminder_date = CURDATE() AND ticket_status != "closed" AND reminder_date != "9999-12-31"');
$stmt->execute();
$due_today_reviews = $stmt->fetchColumn();

// ===== LEGAL FILINGS STATS =====
// New legal requirements today
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM gws_legal WHERE cast(created as DATE) = cast(now() as DATE) AND ticket_status = "open"');
$stmt->execute();
$new_legal_tickets = $stmt->fetchColumn();

// Legal comments awaiting response
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM gws_legal WHERE ticket_status = "open" AND last_comment = "Member"');
$stmt->execute();
$legal_comment_awaiting_response = $stmt->fetchColumn();

// Open legal filings total
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM gws_legal WHERE ticket_status = "open"');
$stmt->execute();
$open_legal_tickets = $stmt->fetchColumn();

// Resolved legal tickets today
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM gws_legal WHERE ticket_status = "resolved" AND cast(last_update as DATE) = cast(now() as DATE)');
$stmt->execute();
$resolved_legal_today = $stmt->fetchColumn();
?>
<?=template_admin_header('Ticketing Dashboard', 'ticketing', 'dashboard')?>

<?=generate_breadcrumbs([
    ['label' => 'Ticketing Dashboard']
])?>

<div class="content-title">
    <div class="title">
        <div class="icon alt">
            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M78.6 5C69.1-2.4 55.6-1.5 47 7L7 47c-8.5 8.5-9.4 22-2.1 31.6l80 104c4.5 5.9 11.6 9.4 19 9.4h54.1l109 109c-14.7 29-10 65.4 14.3 89.6l112 112c12.5 12.5 32.8 12.5 45.3 0l64-64c12.5-12.5 12.5-32.8 0-45.3l-112-112c-24.2-24.2-60.6-29-89.6-14.3l-109-109V104c0-7.5-3.5-14.5-9.4-19L78.6 5zM19.9 396.1C7.2 408.8 0 426.1 0 444.1C0 481.6 30.4 512 67.9 512c18 0 35.3-7.2 48-19.9L233.7 374.3c-7.8-20.9-9-43.6-3.6-65.1l-61.7-61.7L19.9 396.1zM512 144c0-10.5-1.1-20.7-3.2-30.5c-2.4-11.2-16.1-14.1-24.2-6l-63.9 63.9c-3 3-7.1 4.7-11.3 4.7H352c-8.8 0-16-7.2-16-16V102.6c0-4.2 1.7-8.3 4.7-11.3l63.9-63.9c8.1-8.1 5.2-21.8-6-24.2C388.7 1.1 378.5 0 368 0C288.5 0 224 64.5 224 144l0 .8 85.3 85.3c36-9.1 75.8 .5 104 28.7L429 274.5c49-23 83-72.8 83-130.5zM56 432a24 24 0 1 1 48 0 24 24 0 1 1 -48 0z"/></svg>
        </div>
        <div class="txt">
            <h2 class="responsive-width-100">Ticketing System Dashboard</h2>
            <p>Overview of Client Support & Development Projects</p>
        </div>
    </div>
</div>

<!-- CLIENT TICKETS SECTION -->
<div class="content-block">
    <h3 style="padding: 15px; background: #6610f2; color: white; margin: 0;">
        <svg width="16" height="16" style="margin-right: 10px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white"><path d="M21 13.34C20.37 13.12 19.7 13 19 13V5H5V18.26L6 17.6L9 19.6L12 17.6L13.04 18.29C13 18.5 13 18.76 13 19C13 19.65 13.1 20.28 13.3 20.86L12 20L9 22L6 20L3 22V3H21V13.34M17 9V7H7V9H17M15 13V11H7V13H15M18 15V18H15V20H18V23H20V20H23V18H20V15H18Z" /></svg>
        Client Support Tickets
        <a href="ticket_system/tickets.php" class="btn btn-sm" style="float: right; background: white; color: #6610f2;">View All Client Tickets →</a>
    </h3>
</div>

<div id="client-tickets-dashboard" class="dashboard">
    <div class="content-block stat green">
        <div class="data">
            <h3>New Today</h3>
            <p><?=$new_client_tickets ? number_format(count($new_client_tickets)) : 0?></p>
        </div>
        <div class="icon">
            <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21 13.34C20.37 13.12 19.7 13 19 13V5H5V18.26L6 17.6L9 19.6L12 17.6L13.04 18.29C13 18.5 13 18.76 13 19C13 19.65 13.1 20.28 13.3 20.86L12 20L9 22L6 20L3 22V3H21V13.34M17 9V7H7V9H17M15 13V11H7V13H15M18 15V18H15V20H18V23H20V20H23V18H20V15H18Z" /></svg>
        </div>
        <div class="footer">
            <svg width="11" height="11" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M105.1 202.6c7.7-21.8 20.2-42.3 37.8-59.8c62.5-62.5 163.8-62.5 226.3 0L386.3 160H352c-17.7 0-32 14.3-32 32s14.3 32 32 32H463.5c0 0 0 0 0 0h.4c17.7 0 32-14.3 32-32V80c0-17.7-14.3-32-32-32s-32 14.3-32 32v35.2L414.4 97.6c-87.5-87.5-229.3-87.5-316.8 0C73.2 122 55.6 150.7 44.8 181.4c-5.9 16.7 2.9 34.9 19.5 40.8s34.9-2.9 40.8-19.5zM39 289.3c-5 1.5-9.8 4.2-13.7 8.2c-4 4-6.7 8.8-8.1 14c-.3 1.2-.6 2.5-.8 3.8c-.3 1.7-.4 3.4-.4 5.1V432c0 17.7 14.3 32 32 32s32-14.3 32-32V396.9l17.6 17.5 0 0c87.5 87.4 229.3 87.4 316.7 0c24.4-24.4 42.1-53.1 52.9-83.7c5.9-16.7-2.9-34.9-19.5-40.8s-34.9 2.9-40.8 19.5c-7.7 21.8-20.2 42.3-37.8 59.8c-62.5 62.5-163.8 62.5-226.3 0l-.1-.1L125.6 352H160c17.7 0 32-14.3 32-32s-14.3-32-32-32H48.4c-1.6 0-3.2 .1-4.8 .3s-3.1 .5-4.6 1z"/></svg>
            Client tickets submitted today
        </div>
    </div>

    <div class="content-block stat red">
        <div class="data">
            <h3>Awaiting Response</h3>
            <p><?=number_format($client_awaiting_response)?></p>
        </div>
        <div class="icon">
            <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21 13.34C20.37 13.12 19.7 13 19 13V5H5V18.26L6 17.6L9 19.6L12 17.6L13.04 18.29C13 18.5 13 18.76 13 19C13 19.65 13.1 20.28 13.3 20.86L12 20L9 22L6 20L3 22V3H21V13.34M17 9V7H7V9H17M15 13V11H7V13H15M18 15V18H15V20H18V23H20V20H23V18H20V15H18Z" /></svg>
        </div>
        <div class="footer">
            <svg width="11" height="11" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M105.1 202.6c7.7-21.8 20.2-42.3 37.8-59.8c62.5-62.5 163.8-62.5 226.3 0L386.3 160H352c-17.7 0-32 14.3-32 32s14.3 32 32 32H463.5c0 0 0 0 0 0h.4c17.7 0 32-14.3 32-32V80c0-17.7-14.3-32-32-32s-32 14.3-32 32v35.2L414.4 97.6c-87.5-87.5-229.3-87.5-316.8 0C73.2 122 55.6 150.7 44.8 181.4c-5.9 16.7 2.9 34.9 19.5 40.8s34.9-2.9 40.8-19.5zM39 289.3c-5 1.5-9.8 4.2-13.7 8.2c-4 4-6.7 8.8-8.1 14c-.3 1.2-.6 2.5-.8 3.8c-.3 1.7-.4 3.4-.4 5.1V432c0 17.7 14.3 32 32 32s32-14.3 32-32V396.9l17.6 17.5 0 0c87.5 87.4 229.3 87.4 316.7 0c24.4-24.4 42.1-53.1 52.9-83.7c5.9-16.7-2.9-34.9-19.5-40.8s-34.9 2.9-40.8 19.5c-7.7 21.8-20.2 42.3-37.8 59.8c-62.5 62.5-163.8 62.5-226.3 0l-.1-.1L125.6 352H160c17.7 0 32-14.3 32-32s-14.3-32-32-32H48.4c-1.6 0-3.2 .1-4.8 .3s-3.1 .5-4.6 1z"/></svg>
            Requires admin feedback
        </div>
    </div>

    <div class="content-block stat cyan">
        <div class="data">
            <h3>Open Tickets</h3>
            <p><?=number_format($open_client_tickets)?></p>
        </div>
        <div class="icon">
            <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21 13.34C20.37 13.12 19.7 13 19 13V5H5V18.26L6 17.6L9 19.6L12 17.6L13.04 18.29C13 18.5 13 18.76 13 19C13 19.65 13.1 20.28 13.3 20.86L12 20L9 22L6 20L3 22V3H21V13.34M17 9V7H7V9H17M15 13V11H7V13H15M18 15V18H15V20H18V23H20V20H23V18H20V15H18Z" /></svg>
        </div>
        <div class="footer">
            <svg width="11" height="11" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M105.1 202.6c7.7-21.8 20.2-42.3 37.8-59.8c62.5-62.5 163.8-62.5 226.3 0L386.3 160H352c-17.7 0-32 14.3-32 32s14.3 32 32 32H463.5c0 0 0 0 0 0h.4c17.7 0 32-14.3 32-32V80c0-17.7-14.3-32-32-32s-32 14.3-32 32v35.2L414.4 97.6c-87.5-87.5-229.3-87.5-316.8 0C73.2 122 55.6 150.7 44.8 181.4c-5.9 16.7 2.9 34.9 19.5 40.8s34.9-2.9 40.8-19.5zM39 289.3c-5 1.5-9.8 4.2-13.7 8.2c-4 4-6.7 8.8-8.1 14c-.3 1.2-.6 2.5-.8 3.8c-.3 1.7-.4 3.4-.4 5.1V432c0 17.7 14.3 32 32 32s32-14.3 32-32V396.9l17.6 17.5 0 0c87.5 87.4 229.3 87.4 316.7 0c24.4-24.4 42.1-53.1 52.9-83.7c5.9-16.7-2.9-34.9-19.5-40.8s-34.9 2.9-40.8 19.5c-7.7 21.8-20.2 42.3-37.8 59.8c-62.5 62.5-163.8 62.5-226.3 0l-.1-.1L125.6 352H160c17.7 0 32-14.3 32-32s-14.3-32-32-32H48.4c-1.6 0-3.2 .1-4.8 .3s-3.1 .5-4.6 1z"/></svg>
            Review for scheduling
        </div>
    </div>

    <div class="content-block stat">
        <div class="data">
            <h3>Resolved Today</h3>
            <p><?=number_format($resolved_client_today)?></p>
        </div>
        <div class="icon">
            <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21 13.34C20.37 13.12 19.7 13 19 13V5H5V18.26L6 17.6L9 19.6L12 17.6L13.04 18.29C13 18.5 13 18.76 13 19C13 19.65 13.1 20.28 13.3 20.86L12 20L9 22L6 20L3 22V3H21V13.34M17 9V7H7V9H17M15 13V11H7V13H15M18 15V18H15V20H18V23H20V20H23V18H20V15H18Z" /></svg>
        </div>
        <div class="footer">
            <svg width="11" height="11" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M105.1 202.6c7.7-21.8 20.2-42.3 37.8-59.8c62.5-62.5 163.8-62.5 226.3 0L386.3 160H352c-17.7 0-32 14.3-32 32s14.3 32 32 32H463.5c0 0 0 0 0 0h.4c17.7 0 32-14.3 32-32V80c0-17.7-14.3-32-32-32s-32 14.3-32 32v35.2L414.4 97.6c-87.5-87.5-229.3-87.5-316.8 0C73.2 122 55.6 150.7 44.8 181.4c-5.9 16.7 2.9 34.9 19.5 40.8s34.9-2.9 40.8-19.5zM39 289.3c-5 1.5-9.8 4.2-13.7 8.2c-4 4-6.7 8.8-8.1 14c-.3 1.2-.6 2.5-.8 3.8c-.3 1.7-.4 3.4-.4 5.1V432c0 17.7 14.3 32 32 32s32-14.3 32-32V396.9l17.6 17.5 0 0c87.5 87.4 229.3 87.4 316.7 0c24.4-24.4 42.1-53.1 52.9-83.7c5.9-16.7-2.9-34.9-19.5-40.8s-34.9 2.9-40.8 19.5c-7.7 21.8-20.2 42.3-37.8 59.8c-62.5 62.5-163.8 62.5-226.3 0l-.1-.1L125.6 352H160c17.7 0 32-14.3 32-32s-14.3-32-32-32H48.4c-1.6 0-3.2 .1-4.8 .3s-3.1 .5-4.6 1z"/></svg>
            Completed and closed today
        </div>
    </div>
</div>

<!-- PROJECT TICKETS SECTION -->
<div class="content-block" style="margin-top: 30px;">
    <h3 style="padding: 15px; background: #7F50AB; color: white; margin: 0;">
        <svg width="16" height="16" style="margin-right: 10px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="white"><path d="M78.6 5C69.1-2.4 55.6-1.5 47 7L7 47c-8.5 8.5-9.4 22-2.1 31.6l80 104c4.5 5.9 11.6 9.4 19 9.4h54.1l109 109c-14.7 29-10 65.4 14.3 89.6l112 112c12.5 12.5 32.8 12.5 45.3 0l64-64c12.5-12.5 12.5-32.8 0-45.3l-112-112c-24.2-24.2-60.6-29-89.6-14.3l-109-109V104c0-7.5-3.5-14.5-9.4-19L78.6 5zM19.9 396.1C7.2 408.8 0 426.1 0 444.1C0 481.6 30.4 512 67.9 512c18 0 35.3-7.2 48-19.9L233.7 374.3c-7.8-20.9-9-43.6-3.6-65.1l-61.7-61.7L19.9 396.1zM512 144c0-10.5-1.1-20.7-3.2-30.5c-2.4-11.2-16.1-14.1-24.2-6l-63.9 63.9c-3 3-7.1 4.7-11.3 4.7H352c-8.8 0-16-7.2-16-16V102.6c0-4.2 1.7-8.3 4.7-11.3l63.9-63.9c8.1-8.1 5.2-21.8-6-24.2C388.7 1.1 378.5 0 368 0C288.5 0 224 64.5 224 144l0 .8 85.3 85.3c36-9.1 75.8 .5 104 28.7L429 274.5c49-23 83-72.8 83-130.5zM56 432a24 24 0 1 1 48 0 24 24 0 1 1 -48 0z"/></svg>
        Development Projects
        <a href="project_system/tickets.php" class="btn btn-sm" style="float: right; background: white; color: #7F50AB;">View All Projects →</a>
    </h3>
</div>

<div id="project-tickets-dashboard" class="dashboard">
    <div class="content-block stat green">
        <div class="data">
            <h3>New Projects</h3>
            <p><?=$new_project_tickets ? number_format(count($new_project_tickets)) : 0?></p>
        </div>
    </div>

    <div class="content-block stat red">
        <div class="data">
            <h3>New Notes</h3>
            <p><?=number_format($project_awaiting_notes)?></p>
        </div>
    </div>

    <div class="content-block stat cyan">
        <div class="data">
            <h3>Open Projects</h3>
            <p><?=number_format($open_project_tickets)?></p>
        </div>
    </div>

    <div class="content-block stat">
        <div class="data">
            <h3>Done Today</h3>
            <p><?=number_format($resolved_projects_today)?></p>
        </div>
    </div>

    <?php if ($overdue_reviews > 0): ?>
    <div class="content-block stat orange">
        <div class="data">
            <h3>Overdue Reviews</h3>
            <p><?=number_format($overdue_reviews)?></p>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($due_today_reviews > 0): ?>
    <div class="content-block stat yellow">
        <div class="data">
            <h3>Due Today</h3>
            <p><?=number_format($due_today_reviews)?></p>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- LEGAL FILINGS SECTION -->
<div class="content-block" style="margin-top: 30px;">
    <h3 style="padding: 15px; background: #6b46c1; color: white; margin: 0;">
        <svg width="16" height="16" style="margin-right: 10px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white"><path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z" /></svg>
        Legal Filings Overview
        <a href="gws_legal_system/tickets.php" class="btn btn-sm" style="float: right; background: white; color: #6b46c1;">View All Legal Filings →</a>
    </h3>
</div>

<div id="legal-filings-dashboard" class="dashboard">
    <div class="content-block stat green">
        <div class="data">
            <h3>New Requirements</h3>
            <p><?=number_format($new_legal_tickets)?></p>
        </div>
    </div>

    <div class="content-block stat red">
        <div class="data">
            <h3>New Notes</h3>
            <p><?=number_format($legal_comment_awaiting_response)?></p>
        </div>
    </div>

    <div class="content-block stat cyan">
        <div class="data">
            <h3>Open Projects</h3>
            <p><?=number_format($open_legal_tickets)?></p>
        </div>
    </div>

    <div class="content-block stat">
        <div class="data">
            <h3>Today's Progress</h3>
            <p><?=number_format($resolved_legal_today)?></p>
        </div>
    </div>
</div>

<?=template_admin_footer()?>
