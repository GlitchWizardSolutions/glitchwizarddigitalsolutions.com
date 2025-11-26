<?php
require 'assets/includes/admin_config.php';
require_once '../assets/includes/components.php';

// Check if the user is logged-in
check_loggedin($pdo, '../../index.php');

// Fetch account details associated with the logged-in user
$stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
$stmt->execute([ $_SESSION['id'] ]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if the user is an admin
if ($account['role'] != 'Admin') {
    exit('You do not have permission to access this page!');
}

// Connect to the Error Handling Database
try {
    $error_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name9 . ';charset=' . db_charset, db_user, db_pass);
    $error_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
    exit('Failed to connect to the error handling database: ' . $exception->getMessage());
}

// Check whether the record ID is specified
if (!isset($_GET['id']) || empty($_GET['id'])) {
    exit('No ID specified!');
}

// Retrieve the error record from the database
$stmt = $error_db->prepare('SELECT * FROM error_handling WHERE id = ?');
$stmt->execute([ $_GET['id'] ]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$record) {
    exit('Error log record not found!');
}

// Determine error severity status
$error_critical = false;
$error_high = false;
$error_warning = false;

$severity = strtolower($record['severity'] ?? '');
if ($severity == 'critical') {
    $error_critical = true;
} elseif ($severity == 'error') {
    $error_high = true;
} else {
    $error_warning = true;
}

// Calculate how old the error is
$error_date = new DateTime($record['timestamp']);
$now = new DateTime();
$days_old = (int)$error_date->diff($now)->format('%a');
?>
<?=template_admin_header('Error Log', 'resources', 'errors')?>

<?=generate_breadcrumbs([
    ['label' => 'Resource System', 'url' => 'index.php'],
    ['label' => 'Error Logs', 'url' => 'error-logs.php'],
    ['label' => 'Quick Reference']
])?>

<div class="content-title mb-3">
    <div class="title">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <div class="txt">
            <h2>Error Log Entry</h2>
            <p>Quick reference for error from <?=date('F j, Y', strtotime($record['timestamp']))?></p>
        </div>
    </div>
</div>

<div class="content-title responsive-flex-wrap responsive-pad-bot-3">
    <a href="error-logs.php" class="btn btn-secondary mar-right-2">Back to List</a>
</div>

<div class="content-block">
    
    <!-- Error Severity Card -->
    <div class="status-card <?=$error_critical ? 'critical' : ($error_high ? 'expired' : 'warning')?>">
        <?php if ($error_critical): ?>
            <div class="status-icon critical-icon">🔴</div>
            <h2>CRITICAL ERROR</h2>
            <p class="status-message">System critical error - immediate attention required</p>
        <?php elseif ($error_high): ?>
            <div class="status-icon expired-icon">⚠️</div>
            <h2>ERROR</h2>
            <p class="status-message">Application error detected</p>
        <?php else: ?>
            <div class="status-icon warning-icon">⚡</div>
            <h2>WARNING</h2>
            <p class="status-message">Warning condition detected</p>
        <?php endif; ?>
        <p class="status-time"><?=$days_old?> day<?=$days_old != 1 ? 's' : ''?> ago</p>
    </div>

    <!-- Error Information -->
    <div class="info-section">
        <h3><i class="fa-solid fa-info-circle"></i> Error Information</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="label">Application:</span>
                <span class="value"><?=htmlspecialchars($record['application'] ?? '', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Severity:</span>
                <span class="value" style="color: <?=$error_critical ? '#8B0000' : ($error_high ? '#DC143C' : '#FFA500')?>; font-weight: bold;">
                    <?=htmlspecialchars($record['severity'] ?? '', ENT_QUOTES)?>
                </span>
            </div>
            <div class="info-item">
                <span class="label">Timestamp:</span>
                <span class="value"><?=date('M d, Y h:i A', strtotime($record['timestamp']))?></span>
            </div>
            <div class="info-item full-width">
                <span class="label">Path:</span>
                <span class="value"><?=htmlspecialchars($record['path'] ?? '', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Page Name:</span>
                <span class="value"><?=htmlspecialchars($record['pagename'] ?? '', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Section:</span>
                <span class="value"><?=htmlspecialchars($record['section'] ?? '', ENT_QUOTES)?></span>
            </div>
            <?php if (!empty($record['noted'])): ?>
            <div class="info-item">
                <span class="label">Notes:</span>
                <span class="value"><?=nl2br(htmlspecialchars($record['noted'], ENT_QUOTES))?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Technical Details -->
    <?php if (!empty($record['error_type']) || !empty($record['error_code'])): ?>
    <div class="info-section">
        <h3><i class="fa-solid fa-code"></i> Technical Details</h3>
        <div class="info-grid">
            <?php if (!empty($record['error_type'])): ?>
            <div class="info-item">
                <span class="label">Error Type:</span>
                <span class="value"><?=htmlspecialchars($record['error_type'], ENT_QUOTES)?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($record['error_code'])): ?>
            <div class="info-item">
                <span class="label">Error Code:</span>
                <span class="value"><?=htmlspecialchars($record['error_code'], ENT_QUOTES)?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Request Information -->
    <?php if (!empty($record['user_id']) || !empty($record['ip_address']) || !empty($record['request_method'])): ?>
    <div class="info-section">
        <h3><i class="fa-solid fa-globe"></i> Request Context</h3>
        <div class="info-grid">
            <?php if (!empty($record['user_id'])): ?>
            <div class="info-item">
                <span class="label">User ID:</span>
                <span class="value"><?=htmlspecialchars($record['user_id'], ENT_QUOTES)?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($record['ip_address'])): ?>
            <div class="info-item">
                <span class="label">IP Address:</span>
                <span class="value"><?=htmlspecialchars($record['ip_address'], ENT_QUOTES)?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($record['request_method'])): ?>
            <div class="info-item">
                <span class="label">HTTP Method:</span>
                <span class="value"><?=htmlspecialchars($record['request_method'], ENT_QUOTES)?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($record['request_uri'])): ?>
            <div class="info-item full-width">
                <span class="label">Request URI:</span>
                <span class="value"><?=htmlspecialchars($record['request_uri'], ENT_QUOTES)?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Error Message -->
    <div class="info-section">
        <h3><i class="fa-solid fa-message-exclamation"></i> Error Message</h3>
        <div style="background: #FFF0F5; padding: 15px; border-radius: 6px; border-left: 4px solid #dc3545;">
            <div style="color: #dc3545; font-weight: bold; font-family: monospace; line-height: 1.6;">
                <?=nl2br(htmlspecialchars($record['thrown'] ?? '', ENT_QUOTES))?>
            </div>
        </div>
    </div>

    <!-- Request Parameters -->
    <?php if (!empty($record['inputs'])): ?>
    <div class="info-section">
        <h3><i class="fa-solid fa-sliders"></i> Request Parameters</h3>
        <div style="background: white; padding: 15px; border-radius: 6px;">
            <pre style="margin: 0; white-space: pre-wrap; font-family: monospace; font-size: 12px; color: #333;"><?=htmlspecialchars($record['inputs'], ENT_QUOTES)?></pre>
        </div>
    </div>
    <?php endif; ?>

    <!-- Stack Trace -->
    <?php if (!empty($record['outputs'])): ?>
    <div class="info-section">
        <h3><i class="fa-solid fa-list-tree"></i> Stack Trace / Additional Output</h3>
        <div style="background: white; padding: 15px; border-radius: 6px; overflow-x: auto;">
            <pre style="margin: 0; white-space: pre-wrap; font-family: monospace; font-size: 11px; color: #555;"><?=htmlspecialchars($record['outputs'], ENT_QUOTES)?></pre>
        </div>
    </div>
    <?php endif; ?>

    <!-- Notes -->
    <?php if (!empty($record['noted'])): ?>
    <div class="info-section">
        <h3><i class="fa-solid fa-note-sticky"></i> Notes</h3>
        <div style="background: #fff3cd; padding: 15px; border-radius: 6px; border-left: 4px solid #ffc107;">
            <?=nl2br(htmlspecialchars($record['noted'], ENT_QUOTES))?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="info-section">
        <h3><i class="fa-solid fa-bolt"></i> Quick Actions</h3>
        <div class="quick-actions">
            <a href="error-log-view.php?id=<?=$record['id']?>" class="action-btn">
                <i class="fa-solid fa-eye"></i>
                <span>Full Details</span>
            </a>
            <a href="error-logs.php?delete=<?=$record['id']?>" onclick="return confirm('Are you sure you want to delete this error log?')" class="action-btn" style="background: #dc3545;">
                <i class="fa-solid fa-trash"></i>
                <span>Delete</span>
            </a>
            <a href="#" onclick="window.print(); return false;" class="action-btn">
                <i class="fa-solid fa-print"></i>
                <span>Print</span>
            </a>
        </div>
    </div>

</div>

<style>
.status-card {
    text-align: center;
    padding: 40px;
    margin-bottom: 30px;
    border-radius: 8px;
    border: 3px solid;
}
.status-card.active {
    background-color: #d4edda;
    border-color: #28a745;
    color: #155724;
}
.status-card.warning {
    background-color: #fff3cd;
    border-color: #ffc107;
    color: #856404;
}
.status-card.expired {
    background-color: #f8d7da;
    border-color: #dc3545;
    color: #721c24;
}
.status-card.critical {
    background-color: #8B0000;
    border-color: #5a0000;
    color: #fff;
}
.status-icon {
    font-size: 60px;
    margin-bottom: 15px;
}
.status-card h2 {
    margin: 10px 0;
    font-size: 28px;
}
.status-message {
    font-size: 18px;
    margin: 10px 0 0 0;
}
.status-time {
    font-size: 14px;
    margin: 5px 0 0 0;
    opacity: 0.8;
}

.info-section {
    background: #f8f9fa;
    padding: 25px;
    margin-bottom: 20px;
    border-radius: 8px;
    border-left: 4px solid #6b46c1;
}
.info-section h3 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #333;
    font-size: 20px;
}
.info-section h3 i {
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
.info-item.full-width {
    grid-column: 1 / -1;
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

@media print {
    .content-title .btns,
    .quick-actions,
    nav,
    .sidebar,
    footer {
        display: none !important;
    }
    .status-card,
    .info-section {
        border: 1px solid #333 !important;
        page-break-inside: avoid;
    }
}
</style>

<script src="assets/js/resource-system-script.js"></script>
<?=template_admin_footer()?>
