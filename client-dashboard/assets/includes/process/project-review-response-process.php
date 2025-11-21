<?php
$ticket_view_url= site_menu_base . 'client-dashboard/communication/project-ticket-view.php';
$pdo = pdo_connect_mysql();

$stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
$stmt->execute([ $_SESSION['id'] ]);
$account = $stmt->fetchAll(PDO::FETCH_ASSOC);
$email=$account['email']??'';
$name=$account['full_name']??'';
$date_start = isset($_GET['date_start']) ? $_GET['date_start'] :  date("Y-m-1") ;
$date_end = isset($_GET['date_end']) ? $_GET['date_end'] :  date("Y-m-t") ;
// Retrieve project_tickets
$stmt = $pdo->prepare('SELECT * FROM project_tickets WHERE ticket_status != "closed" AND reminder_date >= ? and reminder_date <= ? ORDER BY reminder_date ASC');
$stmt->execute([$date_start, $date_end]);
$action_required_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
// Retrieve the open project_tickets from the database
$stmt = $pdo->prepare('SELECT * FROM project_tickets WHERE   ticket_status = "open" ORDER BY reminder_date ASC');
$stmt->execute();
$open_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Retrieve the paused project_tickets from the database
$stmt = $pdo->prepare('SELECT * FROM project_tickets WHERE   ticket_status = "paused" ORDER BY reminder_date ASC');
$stmt->execute();
$paused_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Retrieve the closed project_tickets from the database
$stmt = $pdo->prepare('SELECT * FROM project_tickets WHERE  ticket_status = "closed" ORDER BY reminder_date ASC');
$stmt->execute();
$closed_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Retrieve the new project_tickets from the database
$stmt = $pdo->prepare('SELECT * FROM project_tickets WHERE  ticket_status = "new" ORDER BY reminder_date ASC');
$stmt->execute();
$new_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Update status as admin
if (isset($_GET['status'], $_SESSION['loggedin']) and ($_SESSION['role'] == 'Admin') and in_array($_GET['status'], ['closed', 'paused']) and $ticket['ticket_status'] == 'open') {
// Update ticket status in the database
    $stmt = $pdo->prepare('UPDATE project_tickets SET last_comment = ?, ticket_status = ? WHERE id = ?');
    $stmt->execute([ $_SESSION['role'], $_GET['status'], $action_required_tickets['id'] ]);
  // Send updated ticket email to user
    send_ticket_email($action_required_tickets['email'], $action_required_tickets['id'], $action_required_tickets['title'], $action_required_tickets['msg'], $action_required_tickets['priority'], $action_required_tickets['category'], $action_required_tickets['private'], $_GET['status'], 'update');
}
// Update status as user
if (isset($_GET['status']) and ($_SESSION['id'] == $action_required_tickets['acc_id']) and in_array($_GET['status'], ['closed', 'open']) and $ticket['ticket_status'] == 'paused') {
    // Update ticket status in the database
    $stmt = $pdo->prepare('UPDATE project_tickets SET last_comment = ?, ticket_status = ? WHERE id = ?');
    $stmt->execute([ $action_required_tickets['role'], $_GET['status'],$action_required_tickets['id']]);
    // Send updated ticket email to user
    send_ticket_email($action_required_tickets['email'], $action_required_tickets['id'], $action_required_tickets['title'], $action_required_tickets['msg'], $action_required_tickets['priority'], $action_required_tickets['category'], $action_required_tickets['private'], $_GET['status'], 'update');
    // Redirect to ticket page
    header('Location: ticket-view.php');
    exit;
}

// Check if the comment form has been submitted
if (isset($_POST['msg']) and !empty($_POST['msg']) and $action_required_tickets['ticket_status'] == 'open') {
    // Insert the new comment into the "project_tickets_comments" table
    $stmt = $pdo->prepare('INSERT INTO project_tickets_comments (ticket_id, msg, acc_id, new) VALUES (?, ?, ?, ?)');
    $stmt->execute([ $action_required_tickets['id'], $_POST['msg'], $_SESSION['id'], $_SESSION['role'] ]);
    // Update ticket last_comment in the database
    $stmt = $pdo->prepare('UPDATE project_tickets SET last_comment = ? WHERE id = ?');
    $stmt->execute([ $account['role'], $action_required_tickets['id'] ]);
    
if ($_SESSION['role'] =="Admin"){ 
   // Send updated ticket email to user
    send_ticket_email($action_required_tickets['email'], $action_required_tickets['id'], $action_required_tickets['title'], $action_required_tickets['msg'], $action_required_tickets['priority'], $action_required_tickets['category'], $action_required_tickets['private'], 'open', 'comment');
}else{
    $admin_email = notify_admin_email;
          send_ticket_email($admin_email, $action_required_tickets['id'], $action_required_tickets['title'], $action_required_tickets['msg'], $action_required_tickets['priority'], $action_required_tickets['category'], $action_required_tickets['private'], 'open', 'notification-comment', $name, $email);
}

    //Redirect to ticket page
    header('Location: ticket-view.php');
    exit;
}
// Handle respond to ticket post data
if (isset($_POST['respond_ticket_id2'])) {
			// Update the session variables
			$_SESSION['ticket_id'] = $_POST['respond_ticket_id'];
           //redirect the user to the view-ticket page.
				header("Location: {$base_url}/communication/ticket-view.php");
				exit;
			}
// output message (errors, etc)
$msg = '';

$private = default_private ? 0 : 1;
// Fetch all the category names from the project_categories MySQL table
$categories = $pdo->query('SELECT * FROM project_categories')->fetchAll(PDO::FETCH_ASSOC);
/*
<?php
// MySQL query that selects the ticket by the ID column
$stmt = $pdo->prepare('SELECT t.*, a.full_name AS a_name, a.email AS a_email, c.title AS category FROM project_tickets t LEFT JOIN project_categories c ON c.id = t.category_id LEFT JOIN accounts a ON a.id = t.acc_id WHERE t.id = ?');
$stmt->execute([ $ar_ticket['id'] ]);
$ticket_display = $stmt->fetch(PDO::FETCH_ASSOC);
// Retrieve ticket uploads from the database
$stmt = $pdo->prepare('SELECT * FROM project_tickets_uploads WHERE ticket_id = ?');
$stmt->execute([$ar_ticket['id']]);
$ticket_uploads = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Retrieve the ticket comments from the database
$stmt = $pdo->prepare('SELECT tc.*, a.full_name, a.role FROM project_tickets_comments tc LEFT JOIN accounts a ON a.id = tc.acc_id WHERE tc.ticket_id = ? ORDER BY tc.created DESC');
$stmt->execute([ $ar_ticket['id'] ]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>*/

function time2str($ts)
{
    // Handle non-numeric timestamps
    if (!ctype_digit($ts)) {
        $ts = strtotime($ts);
    }

    $diff = time() - $ts;

    // Handle current time
    if ($diff == 0) {
        return 'now';
    }

    // Handle past timestamps
    if ($diff > 0) {
        $day_diff = floor($diff / 86400);
        if ($day_diff == 0) {
            // Handle minutes and hours ago
            if ($diff < 60) {
                return 'just now';
            } elseif ($diff < 120) {
                return '1 minute ago';
            } elseif ($diff < 3600) {
                return floor($diff / 60) . ' minutes ago';
            } elseif ($diff < 7200) {
                return '1 hour ago';
            } elseif ($diff < 86400) {
                return floor($diff / 3600) . ' hours ago';
            }
        } elseif ($day_diff == 1) {
            return 'Yesterday';
        } elseif ($day_diff < 7) {
            return $day_diff . ' days ago';
        } elseif ($day_diff < 31) {
            return ceil($day_diff / 7) . ' weeks ago';
        } elseif ($day_diff < 60) {
            return 'last month';
        } else {
            return date('F Y', $ts);
        }
    }

    // Handle future timestamps
    else {
        $diff = abs($diff);
        $day_diff = floor($diff / 86400);
        if ($day_diff == 0) {
            // Handle minutes and hours from now
            if ($diff < 120) {
                return 'in a minute';
            } elseif ($diff < 3600) {
                return 'in ' . floor($diff / 60) . ' minutes';
            } elseif ($diff < 7200) {
                return 'in an hour';
            } elseif ($diff < 86400) {
                return 'in ' . floor($diff / 3600) . ' hours';
            }
        } elseif ($day_diff == 1) {
            return 'Tomorrow';
        } elseif ($day_diff < 4) {
            return date('l', $ts);
        } elseif ($day_diff < 7 + (7 - date('w'))) {
            return 'next week';
        } elseif (ceil($day_diff / 7) < 4) {
            return 'in ' . ceil($day_diff / 7) . ' weeks';
        } elseif (date('n', $ts) == date('n') + 1) {
            return 'next month';
        } else {
            return date('F Y', $ts);
        }
    }
}
?>