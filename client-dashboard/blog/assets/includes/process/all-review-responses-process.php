<?php
/* 01/12/2025 Removed "approved = 1" */
$ticket_view_url= site_menu_base . 'client-dashboard/communication/ticket-view.php';
$pdo = pdo_connect_mysql();
$stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
$stmt->execute([ $_SESSION['id'] ]);
$account = $stmt->fetchAll(PDO::FETCH_ASSOC);
$email=$account['email']??'';
$name=$account['full_name']??'';
// Retrieve ticket admin comment tickets that have not been replied to.
$stmt = $pdo->prepare('SELECT * FROM tickets WHERE ticket_status != "closed" AND last_comment = "Member" ORDER BY created DESC');
$stmt->execute();
$client_waiting_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
// Retrieve ticket client comment tickets that await admin response.
$stmt = $pdo->prepare('SELECT * FROM tickets WHERE ticket_status != "closed" AND last_comment = "Admin" ORDER BY created DESC');
$stmt->execute();
$admin_waiting_tickets = $stmt->fetchALL(PDO::FETCH_ASSOC); 

// Retrieve the open tickets from the database
$stmt = $pdo->prepare('SELECT * FROM tickets WHERE  ticket_status = "open" ORDER BY created DESC');
$stmt->execute();
$open_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Retrieve the resolved tickets from the database
$stmt = $pdo->prepare('SELECT * FROM tickets WHERE  ticket_status = "resolved" ORDER BY created DESC');
$stmt->execute();
$resolved_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Retrieve the closed tickets from the database
$stmt = $pdo->prepare('SELECT * FROM tickets WHERE ticket_status = "closed" ORDER BY created DESC');
$stmt->execute();
$closed_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Update status as admin
if (isset($_GET['status'], $_SESSION['loggedin']) and ($_SESSION['role'] == 'Admin') and in_array($_GET['status'], ['new', 'closed', 'resolved']) and $ticket['ticket_status'] == 'open') {
// Update ticket status in the database
    $stmt = $pdo->prepare('UPDATE tickets SET last_comment = ?, ticket_status = ? WHERE id = ?');
    $stmt->execute([ $_SESSION['role'], $_GET['status'], $action_required_tickets['id'] ]);
  // Send updated ticket email to user
    send_ticket_email($action_required_tickets['email'], $action_required_tickets['id'], $action_required_tickets['title'], $action_required_tickets['msg'], $action_required_tickets['priority'], $action_required_tickets['category'], $action_required_tickets['private'], $_GET['status'], 'update');
    // Redirect to ticket page
    header('Location: all-client-review.php');
    exit;
    
}
 
// Check if the comment form has been submitted
if (isset($_POST['msg']) and !empty($_POST['msg']) and $action_required_tickets['ticket_status'] == 'open') {
    // Insert the new comment into the "tickets_comments" table
    $stmt = $pdo->prepare('INSERT INTO tickets_comments (ticket_id, msg, acc_id, new) VALUES (?, ?, ?, ?)');
    $stmt->execute([ $action_required_tickets['id'], $_POST['msg'], $_SESSION['id'], $_SESSION['role'] ]);
    // Update ticket last_comment in the database
    $stmt = $pdo->prepare('UPDATE tickets SET last_comment = ? WHERE id = ?');
    $stmt->execute([ $account['role'], $action_required_tickets['id'] ]);
 
   // Send updated ticket email to user
    send_ticket_email($action_required_tickets['email'], $action_required_tickets['id'], $action_required_tickets['title'], $action_required_tickets['msg'], $action_required_tickets['priority'], $action_required_tickets['category'], $action_required_tickets['private'], 'open', 'comment');

    //Redirect to ticket page
    header('Location: ticket-view.php');
    exit;
}
// Handle respond to ticket post data
if (isset($_POST['respond_ticket_id2'])) {
			// Update the session variables
			$_SESSION['ticket_id'] = $_POST['respond_ticket_id'];
           //redirect the user to the view-ticket page.
				header('Location: https://glitchwizarddigitalsolutions.com/client-dashboard/communication/ticket-view.php');
				exit;
			}
// output message (errors, etc)
$msg = '';

$private = default_private ? 0 : 1;
// Fetch all the category names from the categories MySQL table
$categories = $pdo->query('SELECT * FROM categories')->fetchAll(PDO::FETCH_ASSOC);
/*
<?php
// MySQL query that selects the ticket by the ID column
$stmt = $pdo->prepare('SELECT t.*, a.full_name AS a_name, a.email AS a_email, c.title AS category FROM tickets t LEFT JOIN categories c ON c.id = t.category_id LEFT JOIN accounts a ON a.id = t.acc_id WHERE t.id = ?');
$stmt->execute([ $ar_ticket['id'] ]);
$ticket_display = $stmt->fetch(PDO::FETCH_ASSOC);
// Retrieve ticket uploads from the database
$stmt = $pdo->prepare('SELECT * FROM tickets_uploads WHERE ticket_id = ?');
$stmt->execute([$ar_ticket['id']]);
$ticket_uploads = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Retrieve the ticket comments from the database
$stmt = $pdo->prepare('SELECT tc.*, a.full_name, a.role FROM tickets_comments tc LEFT JOIN accounts a ON a.id = tc.acc_id WHERE tc.ticket_id = ? ORDER BY tc.created DESC');
$stmt->execute([ $ar_ticket['id'] ]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>*/
?>