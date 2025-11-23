<?php
require 'assets/includes/admin_config.php';
include_once '../assets/includes/components.php';
// Check if the user is logged-in
check_loggedin($pdo, '../../index.php');
// Fetch account details associated with the logged-in user
$stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
$stmt->execute([ $_SESSION['id'] ]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);
// Check if the user is an admin...
if ($account['role'] != 'Admin') {
    exit('You do not have permission to access this page!');
}

// Connect to the On the Go Database using the PDO interface
try {
	$onthego_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name2 . ';charset=' . db_charset, db_user, db_pass);
	$onthego_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	// If there is an error with the connection, stop the script and display the error.
	exit('Failed to connect to the on the go database!');
}

// Check if ID parameter is provided
if (!isset($_GET['id'])) {
    exit('No ID specified!');
}

// Retrieve the warranty record from the database
$stmt = $onthego_db->prepare('SELECT wt.*, wtype.name as warranty_type FROM warranty_tickets wt LEFT JOIN warranty_types wtype ON wt.warranty_type_id = wtype.id WHERE wt.id = ?');
$stmt->execute([ $_GET['id'] ]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if record exists
if (!$record) {
    exit('Warranty record not found!');
}
?>
<?=template_admin_header('View Warranty', 'resources', 'warranties')?>

<?=generate_breadcrumbs([
    ['label' => 'Resource System', 'url' => 'index.php'],
    ['label' => 'Warranties', 'url' => 'warranties.php'],
    ['label' => 'View Warranty']
])?>

<div class="content-title">
    <div class="title">
        <i class="fa-solid fa-shield-halved"></i>
        <div class="txt">
            <h2><?=htmlspecialchars($record['title'], ENT_QUOTES)?></h2>
            <p>Complete warranty details and documentation</p>
        </div>
    </div>
</div>

<div class="content-title responsive-flex-wrap responsive-pad-bot-3">
    <a href="warranties.php" class="btn btn-secondary mar-right-2">Return to List</a>
    <a href="warranty.php?id=<?=$record['id']?>" class="btn btn-primary mar-right-2">Edit</a>
    <a href="warranties.php?delete=<?=$record['id']?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this warranty record?')">Delete</a>
</div>

<div class="content-block">
    <div class="form responsive-width-100">
        
        <h3>Warranty Information</h3>
        
        <div class="view-field">
            <label>Title:</label>
            <p><?=htmlspecialchars($record['title'], ENT_QUOTES)?></p>
        </div>
        
        <div class="view-field">
            <label>Message/Description:</label>
            <p><?=nl2br(htmlspecialchars($record['msg'], ENT_QUOTES))?></p>
        </div>
        
        <div class="view-field">
            <label>Warranty Type:</label>
            <p><?=htmlspecialchars($record['warranty_type'] ?? 'N/A', ENT_QUOTES)?></p>
        </div>
        
        <div class="view-field">
            <label>Status:</label>
            <p><span class="badge badge-<?=$record['ticket_status']?>"><?=ucfirst($record['ticket_status'])?></span></p>
        </div>
        
        <div class="view-field">
            <label>Owner:</label>
            <p><?=htmlspecialchars($record['owner'], ENT_QUOTES)?></p>
        </div>
        
        <div class="view-field">
            <label>Purchase Date:</label>
            <p><?=$record['purchase_date'] ? date('F j, Y', strtotime($record['purchase_date'])) : 'N/A'?></p>
        </div>
        
        <div class="view-field">
            <label>Warranty Expiration Date:</label>
            <p><?=$record['warranty_expiration_date'] ? date('F j, Y', strtotime($record['warranty_expiration_date'])) : 'N/A'?></p>
        </div>
        
        <div class="view-field">
            <label>Reminder Date:</label>
            <p><?=$record['reminder_date'] ? date('F j, Y', strtotime($record['reminder_date'])) : 'N/A'?></p>
        </div>

        <?php if ($record['warranty_expiration_date']): ?>
        <?php 
        $today = new DateTime();
        $expiration = new DateTime($record['warranty_expiration_date']);
        $days_remaining = $today->diff($expiration)->days;
        $is_expired = $today > $expiration;
        ?>
        <div class="view-field">
            <label>Warranty Status:</label>
            <?php if ($is_expired): ?>
                <p style="color: #dc3545; font-weight: bold;">⚠️ EXPIRED (<?=$days_remaining?> days ago)</p>
            <?php elseif ($days_remaining <= 30): ?>
                <p style="color: #ffc107; font-weight: bold;">⚠️ Expiring Soon (<?=$days_remaining?> days remaining)</p>
            <?php else: ?>
                <p style="color: #28a745; font-weight: bold;">✓ Active (<?=$days_remaining?> days remaining)</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php
        // Get uploaded attachments
        $stmt = $onthego_db->prepare('SELECT * FROM warranty_tickets_uploads WHERE ticket_id = ?');
        $stmt->execute([ $_GET['id'] ]);
        $uploads = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($uploads)):
        ?>
        <div class="view-field">
            <label>Attachments:</label>
            <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-top: 10px;">
                <?php foreach($uploads as $upload): ?>
                <?php
                // Handle old database paths that include 'warranty-ticket-uploads/' prefix
                $clean_filepath = str_replace('warranty-ticket-uploads/', '', $upload['filepath']);
                $file_path = warranty_resource_uploads_path . $clean_filepath;
                $file_url = warranty_resource_uploads_url . $clean_filepath;
                $is_image = @getimagesize($file_path) !== false;
                ?>
                <div style="text-align: center;">
                    <?php if ($is_image): ?>
                    <a href="<?=$file_url?>" target="_blank">
                        <img src="<?=$file_url?>" width="150" height="150" style="object-fit: cover; border: 2px solid #ddd; border-radius: 6px;" alt="<?=htmlspecialchars($upload['filepath'], ENT_QUOTES)?>">
                    </a>
                    <?php else: ?>
                    <a href="<?=$file_url?>" download style="display: block; padding: 30px; border: 2px solid #ddd; border-radius: 6px; text-decoration: none;">
                        <i class="fas fa-file fa-4x" style="color: #6b46c1;"></i>
                    </a>
                    <?php endif; ?>
                    <p style="font-size: 12px; margin-top: 8px; word-break: break-all; max-width: 150px;"><?=htmlspecialchars($upload['filepath'], ENT_QUOTES)?></p>
                    <a href="<?=$file_url?>" download class="btn btn-sm btn-primary" style="font-size: 11px; padding: 4px 8px;">Download</a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
    </div>
</div>

<style>
.view-field {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e0e0e0;
}
.view-field:last-child {
    border-bottom: none;
}
.view-field label {
    font-weight: bold;
    color: #555;
    display: block;
    margin-bottom: 5px;
}
.view-field p {
    margin: 0;
    color: #333;
    font-size: 16px;
}
.badge {
    padding: 5px 10px;
    border-radius: 4px;
    font-weight: bold;
    display: inline-block;
}
.badge-new {
    background-color: #17a2b8;
    color: white;
}
.badge-active {
    background-color: #28a745;
    color: white;
}
.badge-closed {
    background-color: #6c757d;
    color: white;
}
.badge-service {
    background-color: #ffc107;
    color: #333;
}
</style>

<?=template_admin_footer()?>
