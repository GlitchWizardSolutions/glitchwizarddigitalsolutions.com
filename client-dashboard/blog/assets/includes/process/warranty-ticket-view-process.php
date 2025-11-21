<?php
//include process_path . 'email-process.php';
if (!isset($_SESSION['ticket_id'])){
     //Redirect to warranty page
    header('Location: warranty-review-responses.php');
    exit;
}
$pdo = pdo_connect_mysql();
$onthego= pdo_connect_onthego_db();
// Retrieve additional account info from the database because we don't have them stored in sessions.
$stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
$stmt->execute([ $_SESSION['id'] ]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);
//

// MySQL query that selects the ticket by the session ticket id
$stmt = $onthego->prepare('SELECT t.*, ty.name AS warranty_type FROM warranty_tickets t LEFT JOIN warranty_types ty ON ty.id = t.warranty_type_id WHERE t.id = ?');
$stmt->execute([ $_SESSION['ticket_id']]);
$ticket_data = $stmt->fetch(PDO::FETCH_ASSOC);

// Retrieve the ticket comments from the database

$stmt = $onthego->prepare('SELECT * FROM warranty_tickets_comments  WHERE  ticket_id = ? ORDER BY created DESC');
$stmt->execute([$_SESSION['ticket_id']]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Retrieve ticket uploads from the database
$stmt = $onthego->prepare('SELECT * FROM warranty_tickets_uploads WHERE ticket_id = ?');
$stmt->execute([ $_SESSION['ticket_id']]);
$ticket_uploads = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Current date in MySQL DATETIME format
$date = date('Y-m-d H:i:s');

// Retrieve all of the warranty_tickets that are due to be reviewed this month.
$stmt = $onthego->prepare('SELECT * FROM warranty_tickets WHERE ticket_status != "closed" AND warranty_expiration_date < date_sub(now(), interval 1 month) ORDER BY reminder_date DESC');
$stmt->execute();
$action_required_ticket  = $stmt->fetchALL(PDO::FETCH_ASSOC);

// output message (errors, etc)
$msg = '';
$radio="";
$status="";
 
// Fetch all the category names from the warranty_categories MySQL table
$categories = $onthego->query('SELECT * FROM warranty_categories')->fetchAll(PDO::FETCH_ASSOC);
// Check if the comment form has been submitted

// Check if POST data exists (user submitted the form)
if (isset($_POST['title'], $_POST['ticket-message']) && (isset($_SESSION['loggedin']))) {
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
       
        // Insert new record into the warranty_tickets table
        $stmt = $onthego->prepare('INSERT INTO warranty_tickets (title, msg, warranty_type_id, ticket_status, owner, reminder_date, purchase_date, warranty_expiration_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([ $_POST['title'], $_POST['ticket-message'], $_POST['warranty_type_id'], $_POST['ticket_status'], $_POST['owner'], $account['reminder_date'], $account['purchase_date'], $account['warranty_expiration_date']]);
        // Retrieve the ticket ID
        $ticket_id = $onthego->lastInsertId();
        // Handle the file uploads
        if (attachments && isset($_FILES['attachments'])) {
            // Iterate the uploaded files
            for ($i = 0; $i < count($_FILES['attachments']['name']); $i++) {
                // Get the file extension (png, jpg, etc)
                $ext = strtolower(pathinfo($_FILES['attachments']['name'][$i], PATHINFO_EXTENSION));
                // The file name will contain a unique code to prevent multiple files with the same name.
            	$upload_path = warranty_uploads_directory . sha1(uniqid() . $ticket_id . $i) .  '.' . $ext;
            	// Check to make sure the file is valid
            	if (!empty($_FILES['attachments']['tmp_name'][$i]) && in_array($ext, explode(',', attachments_allowed))) {
            		if (!file_exists($upload_path) && $_FILES['attachments']['size'][$i] <= max_allowed_upload_file_size) {
            			// If everything checks out, we can move the uploaded file to its final destination...
            			move_uploaded_file($_FILES['attachments']['tmp_name'][$i], $upload_path);
            			// Insert attachment info into the database (ticket_id, filepath)
            			$stmt = $onthego->prepare('INSERT INTO warranty_tickets_uploads (ticket_id, filepath) VALUES (?, ?)');
            	        $stmt->execute([ $ticket_id, $upload_path ]);
            		}
            	}
            }
        }
        // Redirect to the view ticket page, the user should see their created ticket on this page
            set_admin_response_ticket_id($ticket_id);
            exit;
    }
}?>