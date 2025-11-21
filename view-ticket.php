<?php
/*******************************************************************************
 * GUEST TICKET VIEW - Public Access
 * 
 * Allows guests (acc_id = 0) to view their tickets via secure token link
 * No login required - token-based authentication only
 * 
 * LOCATION: /public_html/view-ticket.php
 * ACCESS: Public (token required)
 * CREATED: 2025-11-21
 ******************************************************************************/

// Start session for message display
if (!session_id()) {
    session_start();
}

require '../private/config.php';

// Initialize message variable
$msg = '';
$error = '';

// Validate token and ticket ID
if (!isset($_GET['t']) || !isset($_GET['token'])) {
    die('Invalid access. This page requires a secure link from your ticket email.');
}

$ticket_id = (int)$_GET['t'];
$provided_token = $_GET['token'];

// Generate expected token for verification
$expected_token = hash_hmac('sha256', $ticket_id, TICKET_SECRET);

// Verify token matches
if (!hash_equals($expected_token, $provided_token)) {
    die('Invalid or expired ticket link. Please use the link from your most recent ticket email.');
}

// Connect to database
try {
    $pdo = new PDO('mysql:host=' . db_host . ';dbname=' . db_name . ';charset=' . db_charset, db_user, db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Database connection failed. Please try again later.');
}

// Retrieve ticket data
$stmt = $pdo->prepare('SELECT t.*, c.title AS category FROM tickets t LEFT JOIN categories c ON c.id = t.category_id WHERE t.id = ?');
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    die('Ticket not found.');
}

// Security check - ensure this is a guest ticket (acc_id = 0)
// If it's a registered user ticket, redirect them to login
if ($ticket['acc_id'] > 0) {
    header('Location: ' . site_menu_base . 'index.php?msg=Please log in to view your ticket');
    exit;
}

// Retrieve ticket comments
$stmt = $pdo->prepare('SELECT tc.*, a.full_name, a.role FROM tickets_comments tc LEFT JOIN accounts a ON a.id = tc.acc_id WHERE tc.ticket_id = ? ORDER BY tc.created DESC');
$stmt->execute([$ticket_id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle new comment submission
if (isset($_POST['new-comment']) && !empty($_POST['new-comment'])) {
    if (strlen($_POST['new-comment']) > 5000) {
        $error = 'Comment must be less than 5000 characters.';
    } else {
        // Insert guest comment
        $stmt = $pdo->prepare('INSERT INTO tickets_comments (ticket_id, msg, acc_id, new) VALUES (?, ?, 0, ?)');
        $stmt->execute([$ticket_id, $_POST['new-comment'], 'Guest']);
        
        // Update ticket status
        $stmt = $pdo->prepare('UPDATE tickets SET last_comment = ?, last_update = ? WHERE id = ?');
        $stmt->execute(['Guest', date('Y-m-d\TH:i:s'), $ticket_id]);
        
        // Reload page to show new comment
        header('Location: view-ticket.php?t=' . $ticket_id . '&token=' . $provided_token . '&msg=Comment added successfully');
        exit;
    }
}

// Get success message if present
if (isset($_GET['msg'])) {
    $msg = htmlspecialchars($_GET['msg']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Ticket #<?= $ticket_id ?> - GlitchWizard Solutions</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f6f8;
            color: #333;
            line-height: 1.6;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #012970 0%, #1a4d9e 100%);
            color: white;
            padding: 30px;
        }
        .header h1 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        .content {
            padding: 30px;
        }
        .alert {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 4px;
            border-left: 4px solid;
        }
        .alert-success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        .alert-error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        .alert-warning {
            background: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }
        .ticket-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 30px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #495057;
        }
        .info-value {
            color: #212529;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-open { background: #cfe2ff; color: #084298; }
        .status-resolved { background: #d1e7dd; color: #0f5132; }
        .status-closed { background: #e2e3e5; color: #41464b; }
        .priority-low { color: #0d6efd; }
        .priority-medium { color: #ffc107; }
        .priority-high { color: #dc3545; }
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #012970;
            margin: 30px 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }
        .ticket-message {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 4px;
            border-left: 4px solid #012970;
            margin-bottom: 30px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .comment {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
        }
        .comment-author {
            font-weight: 600;
            color: #012970;
        }
        .comment-role {
            display: inline-block;
            padding: 2px 8px;
            background: #e9ecef;
            border-radius: 10px;
            font-size: 11px;
            margin-left: 8px;
        }
        .comment-role.admin {
            background: #012970;
            color: white;
        }
        .comment-date {
            font-size: 13px;
            color: #6c757d;
        }
        .comment-body {
            color: #495057;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .comment-form {
            margin-top: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #495057;
        }
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-family: inherit;
            font-size: 14px;
            resize: vertical;
            min-height: 120px;
        }
        textarea:focus {
            outline: none;
            border-color: #012970;
            box-shadow: 0 0 0 3px rgba(1, 41, 112, 0.1);
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #012970;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #1a4d9e;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
            font-size: 13px;
        }
        @media (max-width: 600px) {
            body {
                padding: 10px;
            }
            .content {
                padding: 20px;
            }
            .info-row {
                flex-direction: column;
            }
            .info-value {
                margin-top: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Ticket #<?= $ticket_id ?></h1>
            <p>GlitchWizard Solutions - Support Ticket</p>
        </div>

        <div class="content">
            <?php if ($msg): ?>
            <div class="alert alert-success"><?= $msg ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>

            <?php if ($ticket['last_comment'] == 'Admin' && $ticket['ticket_status'] != 'closed'): ?>
            <div class="alert alert-warning">
                <strong>⚠️ New Reply from Support!</strong><br>
                Our team has responded to your ticket. Please review the comment below.
            </div>
            <?php endif; ?>

            <div class="ticket-info">
                <div class="info-row">
                    <span class="info-label">Title:</span>
                    <span class="info-value"><?= htmlspecialchars($ticket['title']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value">
                        <span class="status-badge status-<?= $ticket['ticket_status'] ?>">
                            <?= ucfirst($ticket['ticket_status']) ?>
                        </span>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Priority:</span>
                    <span class="info-value priority-<?= $ticket['priority'] ?>">
                        <?= ucfirst($ticket['priority']) ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Category:</span>
                    <span class="info-value"><?= htmlspecialchars($ticket['category']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Created:</span>
                    <span class="info-value"><?= date('F j, Y g:i A', strtotime($ticket['created'])) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Name:</span>
                    <span class="info-value"><?= htmlspecialchars($ticket['full_name']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?= htmlspecialchars($ticket['email']) ?></span>
                </div>
            </div>

            <div class="section-title">Original Message</div>
            <div class="ticket-message"><?= htmlspecialchars($ticket['msg']) ?></div>

            <?php if (count($comments) > 0): ?>
            <div class="section-title">Comments & Replies (<?= count($comments) ?>)</div>
            <?php foreach ($comments as $comment): ?>
            <div class="comment">
                <div class="comment-header">
                    <div>
                        <span class="comment-author">
                            <?= $comment['full_name'] ? htmlspecialchars($comment['full_name']) : ($comment['new'] == 'Admin' ? 'Support Team' : 'Guest') ?>
                        </span>
                        <span class="comment-role <?= $comment['new'] == 'Admin' ? 'admin' : '' ?>">
                            <?= $comment['new'] == 'Admin' ? 'Admin' : 'Guest' ?>
                        </span>
                    </div>
                    <span class="comment-date"><?= date('M j, Y g:i A', strtotime($comment['created'])) ?></span>
                </div>
                <div class="comment-body"><?= nl2br(htmlspecialchars($comment['msg'])) ?></div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>

            <?php if ($ticket['ticket_status'] != 'closed'): ?>
            <div class="comment-form">
                <div class="section-title">Add a Comment</div>
                <form method="post">
                    <div class="form-group">
                        <label class="form-label" for="new-comment">Your Message:</label>
                        <textarea id="new-comment" name="new-comment" placeholder="Type your comment or additional information here..." required></textarea>
                    </div>
                    <button type="submit" class="btn">Submit Comment</button>
                </form>
            </div>
            <?php else: ?>
            <div class="alert alert-success">
                This ticket has been closed. If you need further assistance, please submit a new ticket.
            </div>
            <?php endif; ?>
        </div>

        <div class="footer">
            <p><strong>GlitchWizard Solutions, LLC</strong></p>
            <p>This ticket can only be accessed via the secure link sent to your email.</p>
            <p>For security, do not share this link with others.</p>
        </div>
    </div>
</body>
</html>
