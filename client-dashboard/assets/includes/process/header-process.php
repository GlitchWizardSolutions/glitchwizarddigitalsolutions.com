<?php
$date = new DateTime();
$date->add(new DateInterval('P30D'));
$date_end = $date->format('Y-m-d');

// Accounts that are active in the last 3 days
$active_recently = $pdo->query('SELECT * FROM accounts WHERE  role !="Admin" AND last_seen > date_sub(now(), interval 3 day) ORDER BY last_seen DESC LIMIT 3')->fetchAll(PDO::FETCH_ASSOC);

$ticket_view_url= site_menu_base . 'client-dashboard/communication/ticket-view.php';
$pdo = pdo_connect_mysql();

// Retrieve additional account info from the database because we don't have them stored in sessions.
$stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
$stmt->execute([ $_SESSION['id'] ]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

// Retrieve the total number of open CLIENT tickets
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM tickets WHERE ticket_status != "closed" AND acc_id != ?');
$stmt->execute([ $account['id'] ]);
$admin_todo_total = $stmt->fetchColumn();
 
//Domain Name Registrations that are expiring THIS month.
$stmt = $pdo->prepare('SELECT * FROM domains WHERE due_date < ?  LIMIT 3');
$stmt->execute([$date_end]);
$domain_registrations_due = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
// Counts the number of domain registrations that are currently due.
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM domains WHERE status = "Active" AND due_date <=?');
$stmt->execute([$date_end]);
$domain_due_count = $stmt->fetchColumn();

// Counts the number of accounts who have logged in within the last three days.
$active_count = $pdo->query('SELECT COUNT(*) AS total FROM accounts WHERE role !="Admin" AND last_seen > date_sub(now(), interval 3 day)')->fetchColumn();

// Counts the number of project tickets where the priority is CRITICAL or it is a bug..
$stmt = $pdo->prepare('SELECT COUNT(*) FROM project_tickets WHERE category_id = "8" AND ticket_status != "closed"');
$stmt->execute();
$admin_project_critical_count = $stmt->fetchColumn();

// Counts the number of project tickets where the priority is high.
$stmt = $pdo->prepare('SELECT COUNT(*) FROM project_tickets WHERE ticket_status != "closed" AND ticket_status != "pending" AND priority = "high" ORDER BY created DESC');
$stmt->execute();
$admin_project_high_count = $stmt->fetchColumn();

// Counts the number of tickets where the user has replied.  Sets the notification bell number at the top of the page.
$stmt = $pdo->prepare('SELECT COUNT(*) FROM tickets WHERE ticket_status != "closed" AND last_comment != "Admin" ORDER BY created DESC');
$stmt->execute();
$admin_notification_bell = $stmt->fetchColumn();


// Counts the number of tickets where the admin has replied.  Sets the notification bell number at the top of the page.
$stmt = $pdo->prepare('SELECT COUNT(*) FROM tickets WHERE acc_id = ?  AND ticket_status != "closed" AND last_comment = "Admin" ORDER BY created DESC');
$stmt->execute([ $account['id'] ]);
$notification_bell = $stmt->fetchColumn();

// Retrieve 3 tickets and info with admin comment tickets that have not been replied to.
$stmt = $pdo->prepare('SELECT * FROM tickets WHERE acc_id = ?  AND ticket_status != "closed" AND last_comment = "Admin" ORDER BY created DESC LIMIT 3');
$stmt->execute([$account['id'] ]);
$actionReq = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Invoice Notifications - Get all client_ids for this account
$stmt = $pdo->prepare('SELECT id FROM invoice_clients WHERE acc_id = ?');
$stmt->execute([ $account['id'] ]);
$client_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (!empty($client_ids)) {
    $placeholders = str_repeat('?,', count($client_ids) - 1) . '?';
    
    // Count unread invoice notifications (excluding PARTIAL/PAST DUE to avoid double counting)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM client_notifications 
        WHERE client_id IN ($placeholders) 
        AND is_read = 0
        AND message NOT LIKE 'PARTIAL -%'
        AND message NOT LIKE 'PAST DUE -%'
    ");
    $stmt->execute($client_ids);
    $unread_count = $stmt->fetchColumn();
    
    // Count PARTIAL/PAST DUE notifications (regardless of read status)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM client_notifications 
        WHERE client_id IN ($placeholders) 
        AND (message LIKE 'PARTIAL -%' OR message LIKE 'PAST DUE -%')
    ");
    $stmt->execute($client_ids);
    $urgent_count = $stmt->fetchColumn();
    
    // Total badge count: unread (non-urgent) + urgent (never double counts)
    $invoice_notification_bell = $unread_count + $urgent_count;
    
    // Badge color: red if urgent items exist, warning if only regular unread
    $invoice_badge_urgent = ($urgent_count > 0);

    // Retrieve invoice notifications: unread NEW/PAID, or PARTIAL/PAST DUE (always show these)
    $stmt = $pdo->prepare("
        SELECT cn.*, i.invoice_number 
        FROM client_notifications cn
        JOIN invoices i ON cn.invoice_id = i.id
        WHERE cn.client_id IN ($placeholders) 
        AND (
            cn.is_read = 0 
            OR cn.message LIKE 'PARTIAL -%'
            OR cn.message LIKE 'PAST DUE -%'
        )
        ORDER BY cn.created_at DESC 
        LIMIT 10
    ");
    $stmt->execute($client_ids);
    $invoice_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $invoice_notification_bell = 0;
    $invoice_badge_urgent = false;
    $invoice_notifications = [];
}

// Retrieve 3 tickets and info with member comment tickets that have not been replied to.
$stmt = $pdo->prepare('SELECT * FROM tickets WHERE ticket_status != "closed" AND last_comment != "Admin" ORDER BY created DESC LIMIT 3');
$stmt->execute();
$admin_actionReq = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Retrieve 3 Critical Bug projects
$stmt = $pdo->prepare('SELECT * FROM project_tickets WHERE category_id = "8" AND ticket_status != "closed" ORDER BY reminder_date ASC LIMIT 3');
$stmt->execute();
$admin_project_critical = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Retrieve 3 upcoming projects
$stmt = $pdo->prepare('SELECT * FROM project_tickets WHERE category_id != "8" AND ticket_status != "closed" AND ticket_status != "paused" ORDER BY reminder_date ASC LIMIT 3');
$stmt->execute();
$admin_project_high = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle respond to ticket post data
if (isset($_POST['respond_ticket_id'])) {
			// Update the session variables
			$_SESSION['ticket_id'] = $_POST['respond_ticket_id'];
           //redirect the user to the view-ticket page.
							echo '<script>window.location.href = "' . $ticket_view_url . '";</script>';
				exit;
			}
			
// Handle respond to ticket post data
if (isset($_POST['respond_project_id'])) {
			// Update the session variables
			$_SESSION['ticket_id'] = $_POST['respond_project_id'];
           //redirect the user to the view-ticket page.
				echo '<script>window.location.href = "' . $base_url . '/communication/project-ticket-view.php";</script>';
				exit;
			}		
// Handle respond to ticket post data
if (isset($_POST['respond_project_med'])) {
			// Update the session variables
			$_SESSION['ticket_id'] = $_POST['respond_project_med'];
           //redirect the user to the view-ticket page.
				echo '<script>window.location.href = "' . $base_url . '/communication/project-ticket-view.php";</script>';
				exit;
			}		
// Handle respond to ticket post data
if (isset($_POST['user_document_form'])) {
		
           //redirect the user to the view-ticket page.
				echo '<script>window.location.href = "' . $outside_url . 'client-documents/system-client-data.php";</script>';
				exit;
			}			
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
}//if function exists