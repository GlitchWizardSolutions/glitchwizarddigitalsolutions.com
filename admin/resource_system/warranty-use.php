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

// Calculate warranty status
$warranty_active = false;
$warranty_expired = false;
$days_remaining = 0;
if ($record['warranty_expiration_date']) {
    $today = new DateTime();
    $expiration = new DateTime($record['warranty_expiration_date']);
    $interval = $today->diff($expiration);
    $days_remaining = (int)$interval->format('%r%a'); // Signed days
    $warranty_active = $days_remaining > 0;
    $warranty_expired = $days_remaining <= 0;
}
?>
<?=template_admin_header('Warranty Usage', 'resources', 'warranties')?>

<?=generate_breadcrumbs([
    ['label' => 'Resource System', 'url' => 'index.php'],
    ['label' => 'Warranties', 'url' => 'warranties.php'],
    ['label' => 'Quick Reference']
])?>

<div class="content-title mb-3">
    <div class="title">
        <i class="fa-solid fa-file-shield"></i>
        <div class="txt">
            <h2>Warranty Information</h2>
            <p>Quick reference for <?=htmlspecialchars($record['title'], ENT_QUOTES)?></p>
        </div>
    </div>
</div>

<div class="content-title responsive-flex-wrap responsive-pad-bot-3">
    <a href="warranties.php" class="btn btn-secondary mar-right-2">Back to List</a>
    <a href="warranty.php?id=<?=$record['id']?>" class="btn btn-primary">Edit</a>
</div>

<div class="content-block">
    
    <!-- Warranty Status Card -->
    <div class="warranty-status-card <?=$warranty_expired ? 'expired' : 'active'?>">
        <?php if ($warranty_expired): ?>
            <div class="status-icon expired-icon">⚠️</div>
            <h2>WARRANTY EXPIRED</h2>
            <p class="status-message">This warranty expired <?=abs($days_remaining)?> days ago</p>
        <?php elseif ($days_remaining <= 30): ?>
            <div class="status-icon warning-icon">⏰</div>
            <h2>WARRANTY EXPIRING SOON</h2>
            <p class="status-message"><?=$days_remaining?> days remaining</p>
        <?php else: ?>
            <div class="status-icon active-icon">✓</div>
            <h2>WARRANTY ACTIVE</h2>
            <p class="status-message"><?=$days_remaining?> days remaining</p>
        <?php endif; ?>
    </div>

    <!-- Product Information -->
    <div class="warranty-info-section">
        <h3><i class="fa-solid fa-box"></i> Product Information</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="label">Product:</span>
                <span class="value"><?=htmlspecialchars($record['title'], ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Type:</span>
                <span class="value"><?=htmlspecialchars($record['warranty_type'] ?? 'N/A', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Owner:</span>
                <span class="value"><?=htmlspecialchars($record['owner'], ENT_QUOTES)?></span>
            </div>
        </div>
    </div>

    <!-- Warranty Dates -->
    <div class="warranty-info-section">
        <h3><i class="fa-solid fa-calendar-days"></i> Important Dates</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="label">Purchase Date:</span>
                <span class="value"><?=$record['purchase_date'] ? date('F j, Y', strtotime($record['purchase_date'])) : 'N/A'?></span>
            </div>
            <div class="info-item">
                <span class="label">Expiration Date:</span>
                <span class="value <?=$warranty_expired ? 'text-danger' : ''?>"><?=$record['warranty_expiration_date'] ? date('F j, Y', strtotime($record['warranty_expiration_date'])) : 'N/A'?></span>
            </div>
            <?php if ($record['reminder_date']): ?>
            <div class="info-item">
                <span class="label">Reminder Set:</span>
                <span class="value"><?=date('F j, Y', strtotime($record['reminder_date']))?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Warranty Terms -->
    <?php if (!empty($record['msg'])): ?>
    <div class="warranty-info-section">
        <h3><i class="fa-solid fa-file-lines"></i> Warranty Terms & Notes</h3>
        <div class="warranty-terms">
            <?=nl2br(htmlspecialchars($record['msg'], ENT_QUOTES))?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Attachments -->
    <?php
    // Get uploaded attachments
    $stmt = $onthego_db->prepare('SELECT * FROM warranty_tickets_uploads WHERE ticket_id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $uploads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($uploads)):
    ?>
    <div class="warranty-info-section">
        <h3><i class="fa-solid fa-paperclip"></i> Attachments</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px;">
            <?php foreach($uploads as $upload): ?>
            <?php
            // Handle old database paths that include 'warranty-ticket-uploads/' prefix
            $clean_filepath = str_replace('warranty-ticket-uploads/', '', $upload['filepath']);
            $file_path = warranty_resource_uploads_path . $clean_filepath;
            $file_url = warranty_resource_uploads_url . $clean_filepath;
            $is_image = @getimagesize($file_path) !== false;
            ?>
            <div class="attachment-card">
                <?php if ($is_image): ?>
                <a href="<?=$file_url?>" target="_blank" class="attachment-image">
                    <img src="<?=$file_url?>" alt="<?=htmlspecialchars($upload['filepath'], ENT_QUOTES)?>">
                </a>
                <?php else: ?>
                <a href="<?=$file_url?>" download class="attachment-file">
                    <i class="fas fa-file fa-4x"></i>
                </a>
                <?php endif; ?>
                <p class="attachment-name"><?=htmlspecialchars($upload['filepath'], ENT_QUOTES)?></p>
                <a href="<?=$file_url?>" download class="btn btn-sm" style="background: #6b46c1; color: white; padding: 6px 12px; font-size: 12px;">Download</a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="warranty-info-section">
        <h3><i class="fa-solid fa-bolt"></i> Quick Actions</h3>
        <div class="quick-actions">
            <a href="warranty.php?id=<?=$record['id']?>" class="action-btn">
                <i class="fa-solid fa-pen-to-square"></i>
                <span>Edit Warranty</span>
            </a>
            <a href="warranty-view.php?id=<?=$record['id']?>" class="action-btn">
                <i class="fa-solid fa-eye"></i>
                <span>Full Details</span>
            </a>
            <a href="#" onclick="window.print(); return false;" class="action-btn">
                <i class="fa-solid fa-print"></i>
                <span>Print</span>
            </a>
        </div>
    </div>

</div>

<style>
.warranty-status-card {
    text-align: center;
    padding: 40px;
    margin-bottom: 30px;
    border-radius: 8px;
    border: 3px solid;
}
.warranty-status-card.active {
    background-color: #d4edda;
    border-color: #28a745;
    color: #155724;
}
.warranty-status-card.expired {
    background-color: #f8d7da;
    border-color: #dc3545;
    color: #721c24;
}
.status-icon {
    font-size: 60px;
    margin-bottom: 15px;
}
.warranty-status-card h2 {
    margin: 10px 0;
    font-size: 28px;
}
.status-message {
    font-size: 18px;
    margin: 10px 0 0 0;
}

.warranty-info-section {
    background: #f8f9fa;
    padding: 25px;
    margin-bottom: 20px;
    border-radius: 8px;
    border-left: 4px solid #6b46c1;
}
.warranty-info-section h3 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #333;
    font-size: 20px;
}
.warranty-info-section h3 i {
    margin-right: 10px;
    color: #6b46c1;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}
.info-item {
    display: flex;
    flex-direction: column;
    padding: 15px;
    background: white;
    border-radius: 6px;
    border: 1px solid #dee2e6;
}
.info-item .label {
    font-weight: bold;
    color: #666;
    font-size: 14px;
    margin-bottom: 5px;
}
.info-item .value {
    color: #333;
    font-size: 16px;
}
.text-danger {
    color: #dc3545 !important;
    font-weight: bold;
}

.warranty-terms {
    background: white;
    padding: 20px;
    border-radius: 6px;
    border: 1px solid #dee2e6;
    line-height: 1.6;
}

.quick-actions {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}
.action-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px 25px;
    background: #6b46c1;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    transition: background 0.3s;
    font-weight: 500;
}
.action-btn:hover {
    background: #5a3ca6;
}
.action-btn i {
    font-size: 18px;
}

.attachment-card {
    background: white;
    padding: 15px;
    border-radius: 6px;
    border: 1px solid #dee2e6;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
}
.attachment-image {
    width: 100%;
    display: block;
}
.attachment-image img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 4px;
}
.attachment-file {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 200px;
    color: #6b46c1;
    text-decoration: none;
}
.attachment-name {
    font-size: 12px;
    word-break: break-all;
    text-align: center;
    margin: 0;
}

@media print {
    .content-title .btns,
    .quick-actions,
    nav,
    .sidebar,
    footer {
        display: none !important;
    }
    .warranty-status-card,
    .warranty-info-section {
        border: 1px solid #333 !important;
        page-break-inside: avoid;
    }
}
</style>

<?=template_admin_footer()?>
