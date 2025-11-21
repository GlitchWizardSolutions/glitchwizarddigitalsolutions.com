<?php
// Unified email system already loaded by user-config.php
$pdo = pdo_connect_mysql();
// Retrieve additional account info from the database because we don't have them stored in sessions.
$stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
$stmt->execute([ $_SESSION['id'] ]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

// Retrieve ticket admin comment project_tickets that have not been replied to.
$stmt = $pdo->prepare('SELECT * FROM project_tickets WHERE acc_id = ? AND approved = 1 AND ticket_status != "closed" AND last_comment = "Admin" ORDER BY created DESC LIMIT 3');
$stmt->execute([ $_SESSION['id'] ]);
$action_required_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Retrieve the open project_tickets from the database
$stmt = $pdo->prepare('SELECT * FROM project_tickets WHERE acc_id = ? AND approved = 1 AND ticket_status = "open" ORDER BY created DESC');
$stmt->execute([ $_SESSION['id'] ]);
$open_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Retrieve the resolved project_tickets from the database
$stmt = $pdo->prepare('SELECT * FROM project_tickets WHERE acc_id = ? AND approved = 1 AND ticket_status = "resolved" ORDER BY created DESC');
$stmt->execute([ $_SESSION['id'] ]);
$resolved_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Retrieve the closed project_tickets from the database
$stmt = $pdo->prepare('SELECT * FROM project_tickets WHERE acc_id = ? AND approved = 1 AND ticket_status = "closed" ORDER BY created DESC');
$stmt->execute([ $_SESSION['id'] ]);
$closed_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!function_exists('set_admin_response_ticket_id')){
// The below function will set ticket_id in session to avoid passing in url to ticket-view.
function set_admin_response_ticket_id($ticketID, $redirect_to = site_menu_base . 'client-dashboard/communication/ticket-view.php') { 
        echo '<!--
 -- ++ FUNCTION: set_admin_response_ticket_id-->';
    if (isset($ticketID)) { 
    		session_regenerate_id();
			$_SESSION['ticket_id']=$ticketID;
		    header('Location: ' . $redirect_to);
    	    exit;
    }else{
        
    }
  }//function
}//function exists

// output message (errors, etc)
$msg = '';
$email=$_SESSION['email'];
$name=$_SESSION['full_name'];
$private = default_private ? 0 : 1;
// Fetch all the category names from the categories MySQL table
$categories = $pdo->query('SELECT * FROM categories')->fetchAll(PDO::FETCH_ASSOC);
