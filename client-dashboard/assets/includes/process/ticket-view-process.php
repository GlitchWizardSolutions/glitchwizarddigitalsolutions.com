<?php
// Unified email system already loaded by user-config.php
// Token handling is now done in ticket-view.php before this file is included

// Check if ticket_id is set in session
if (!isset($_SESSION['ticket_id'])){
     //Redirect to ticket page
    header('Location: review-responses.php');
    exit;
}
$pdo = pdo_connect_mysql();

// Retrieve additional account info from the database because we don't have them stored in sessions.
$stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
$stmt->execute([ $_SESSION['id'] ]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

// MySQL query that selects the ticket by the session ticket id
$stmt = $pdo->prepare('SELECT t.*, a.full_name AS a_name, a.email AS a_email, c.title AS category FROM tickets t LEFT JOIN categories c ON c.id = t.category_id LEFT JOIN accounts a ON a.id = t.acc_id WHERE t.id = ?');
$stmt->execute([ $_SESSION['ticket_id']]);
$ticket_data = $stmt->fetch(PDO::FETCH_ASSOC);

// Retrieve the ticket comments from the database
$stmt = $pdo->prepare('SELECT tc.*, a.full_name, a.role FROM tickets_comments tc LEFT JOIN accounts a ON a.id = tc.acc_id WHERE tc.ticket_id = ? ORDER BY tc.created DESC');
$stmt->execute([$_SESSION['ticket_id']]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Retrieve ticket uploads from the database
$stmt = $pdo->prepare('SELECT * FROM tickets_uploads WHERE ticket_id = ?');
$stmt->execute([ $_SESSION['ticket_id']]);
$ticket_uploads = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Retrieve ticket admin comment tickets that have not been replied to.
$stmt = $pdo->prepare('SELECT * FROM tickets WHERE acc_id = ? AND approved = 1 AND ticket_status != "closed" AND last_comment = "Admin" ORDER BY last_update DESC');
$stmt->execute([ $_SESSION['id'] ]);
$action_required_tickets = $stmt->fetch(PDO::FETCH_ASSOC);
// output message (errors, etc)
$msg = '';
$radio="";
$status="";
$private = default_private ? 0 : 1;
// Fetch all the category names from the categories MySQL table
$categories = $pdo->query('SELECT * FROM categories')->fetchAll(PDO::FETCH_ASSOC);
// Check if the comment form has been submitted
/*
// Check if POST data exists (user submitted the form)
if (isset($_POST['title'], $_POST['ticket-message'], $_POST['priority'], $_POST['category']) && (isset($_SESSION['loggedin']))) {
    // Validation checks...
    if (empty($_POST['title']) || empty($_POST['ticket-message']) || empty($_POST['priority'])) {
        $msg = 'Please complete the form!';
    } else if (strlen($_POST['title']) > max_title_length) {
        $msg = 'Title must be less than ' . max_title_length . ' characters long!';
    } else if (strlen($_POST['ticket-message']) > max_msg_length) {
        $msg = 'Message must be less than ' . max_msg_length . ' characters long!';
    } else {
        // Get the account ID if the user is logged in
        $account_id = isset($_SESSION['loggedin']) ? $_SESSION['id'] : 0;
        $approved = approval_required ? 0 : 1; 
        // Insert new record into the tickets table
        $stmt = $pdo->prepare('INSERT INTO tickets (title, email, msg, priority, category_id, private, acc_id, created, approved, full_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([ $_POST['title'], $account['email'], $_POST['ticket-message'], $_POST['priority'], $_POST['category'], $private, $account_id, date('Y-m-d H:i:s'), $approved, $account['full_name']]);
        // Retrieve the ticket ID
        $ticket_id = $pdo->lastInsertId();
        // Handle the file uploads
        if (attachments && isset($_FILES['attachments'])) {
            // Iterate the uploaded files
            for ($i = 0; $i < count($_FILES['attachments']['name']); $i++) {
                // Get the file extension (png, jpg, etc)
                $ext = strtolower(pathinfo($_FILES['attachments']['name'][$i], PATHINFO_EXTENSION));
                // Get the original filename without extension
                $original_name = pathinfo($_FILES['attachments']['name'][$i], PATHINFO_FILENAME);

                // Generate a unique filename that preserves the original name
                $counter = 0;
                $filename = $original_name;
                $upload_path = uploads_directory . $filename . '.' . $ext;

                // Check if file exists and add numbering if needed
                while (file_exists($upload_path)) {
                    $counter++;
                    $filename = $original_name . ' (' . $counter . ')';
                    $upload_path = uploads_directory . $filename . '.' . $ext;
                }

            	// Check to make sure the file is valid
            	if (!empty($_FILES['attachments']['tmp_name'][$i]) && in_array($ext, explode(',', attachments_allowed))) {
            		if ($_FILES['attachments']['size'][$i] <= max_allowed_upload_file_size) {
            			// If everything checks out, we can move the uploaded file to its final destination...
            			move_uploaded_file($_FILES['attachments']['tmp_name'][$i], $upload_path);
            			// Insert attachment info into the database (ticket_id, filepath)
            			$stmt = $pdo->prepare('INSERT INTO tickets_uploads (ticket_id, filepath) VALUES (?, ?)');
            	        $stmt->execute([ $ticket_id, $upload_path ]);
            		}
            	}
            }
        }
        // Get the category name
        $category_name = 'none';
        foreach ($categories as $c) {
            $category_name = $c['id'] == $_POST['category'] ? $c['title'] : $category_name;
        }
        
        // Send the ticket email to the user
           send_ticket_email($account['email'], $ticket_id, $_POST['title'], $_POST['ticket-message'], $_POST['priority'], $category_name, $private, 'open', 'create');
        // Send the ticket email notification to Admin
            $admin_email = notify_admin_email;
            send_ticket_email($admin_email, $ticket_id, $_POST['title'], $_POST['ticket-message'], $_POST['priority'], $category_name, $private, 'open', 'notification', $account['full_name'], $account['email']);
        // Redirect to the view ticket page, the user should see their created ticket on this page
            set_admin_response_ticket_id($ticket_id);
            exit;
    } 
} */ ?>