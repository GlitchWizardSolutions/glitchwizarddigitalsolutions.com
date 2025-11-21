<?php
$ticket_view_url= site_menu_base . 'client-dashboard/barb-resources/warranty-ticket-view.php';
// output message (errors, etc)
$msg = '';
$pdo = pdo_connect_mysql();
$onthego= pdo_connect_onthego_db();

$stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
$stmt->execute([ $_SESSION['id'] ]);
$account = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Retrieve all of the warranty_tickets that are due to expire this month.
$stmt = $onthego->prepare('SELECT * FROM warranty_tickets WHERE ticket_status != "closed" AND warranty_expiration_date < date_sub(now(), interval 1 month) ORDER BY warranty_expiration_date DESC');
$stmt->execute();
$expiring = $stmt->fetchALL(PDO::FETCH_ASSOC);


// Retrieve the active warranty_tickets from the database
$stmt = $onthego->prepare('SELECT * FROM warranty_tickets WHERE ticket_status = "new"');
$stmt->execute();
$action_required_ticket = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Retrieve the active warranty_tickets from the database
$stmt = $onthego->prepare('SELECT * FROM warranty_tickets WHERE ticket_status = "active" ORDER BY warranty_expiration_date DESC');
$stmt->execute();
$active_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Retrieve the service warranty_tickets from the database
$stmt = $onthego->prepare('SELECT * FROM warranty_tickets WHERE ticket_status = "service" ORDER BY warranty_expiration_date DESC');
$stmt->execute();
$service_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Retrieve the closed warranty_tickets from the database
$stmt = $onthego->prepare('SELECT * FROM warranty_tickets WHERE ticket_status = "closed" ORDER BY warranty_expiration_date DESC');
$stmt->execute();
$closed_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);



// Check if the comment form has been submitted
if (isset($_POST['message']) and !empty($_POST['message'])) {
    // Insert the new comment into the "warranty_tickets_comments" table
    $stmt = $onthego->prepare('INSERT INTO warranty_tickets_comments (ticket_id, message) VALUES (?, ?)');
    $stmt->execute([ $action_required_tickets['id'], $_POST['message'] ]);
    // Update ticket status in the database
    $stmt = $onthego->prepare('UPDATE warranty_tickets SET  ticket_status = ? WHERE id = ?');
    $stmt->execute([$_GET['status'], $action_required_tickets['id'] ]);
}

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