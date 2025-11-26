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
	exit('Failed to connect to the on the go database!');
}

// Check if ID parameter is provided
if (!isset($_GET['id'])) {
    exit('No ID specified!');
}

// Retrieve the SaaS account record from the database
$stmt = $onthego_db->prepare('SELECT * FROM sass_account WHERE id = ?');
$stmt->execute([ $_GET['id'] ]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if record exists
if (!$record) {
    exit('SaaS Account not found!');
}

// Calculate account status (simplified - can be enhanced based on your business logic)
$account_active = true; // Default to active
$account_expiring = false;
$account_inactive = false;

// You can add custom logic here based on usage, expiration dates, etc.
if (!empty($record['usage']) && strtolower($record['usage']) == 'inactive') {
    $account_inactive = true;
    $account_active = false;
}
?>
<?=template_admin_header('SaaS Account Usage', 'resources', 'sass')?>

<?=generate_breadcrumbs([
    ['label' => 'Resource System', 'url' => 'index.php'],
    ['label' => 'SaaS Accounts', 'url' => 'sass-accounts.php'],
    ['label' => 'Quick Reference']
])?>

<div class="content-title mb-3">
    <div class="title">
        <i class="fa-solid fa-cloud"></i>
        <div class="txt">
            <h2>SaaS Account Information</h2>
            <p>Quick reference for <?=htmlspecialchars($record['resource'] ?? '', ENT_QUOTES)?></p>
        </div>
    </div>
</div>

<div class="content-title responsive-flex-wrap responsive-pad-bot-3">
    <a href="sass-accounts.php" class="btn btn-secondary mar-right-2">Back to List</a>
    <a href="sass-account.php?id=<?=$record['id']?>" class="btn btn-primary">Edit</a>
</div>

<div class="content-block">
    
    <!-- Account Status Card -->
    <div class="status-card <?=$account_inactive ? 'expired' : ($account_expiring ? 'warning' : 'active')?>">
        <?php if ($account_inactive): ?>
            <div class="status-icon expired-icon">⚠️</div>
            <h2>ACCOUNT INACTIVE</h2>
            <p class="status-message">This account is currently inactive</p>
        <?php elseif ($account_expiring): ?>
            <div class="status-icon warning-icon">⏰</div>
            <h2>ACCOUNT EXPIRING SOON</h2>
            <p class="status-message">Subscription renewal needed</p>
        <?php else: ?>
            <div class="status-icon active-icon">✓</div>
            <h2>ACCOUNT ACTIVE</h2>
            <p class="status-message">Service is operational</p>
        <?php endif; ?>
    </div>

    <!-- Account Information -->
    <div class="info-section">
        <h3><i class="fa-solid fa-cloud"></i> Account Information</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="label">Resource:</span>
                <span class="value"><?=htmlspecialchars($record['resource'] ?? '', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Type:</span>
                <span class="value"><?=htmlspecialchars($record['type'] ?? '', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Name:</span>
                <span class="value"><?=htmlspecialchars($record['name'] ?? '', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">URL:</span>
                <span class="value">
                    <?php if (!empty($record['url'])): ?>
                        <a href="https://<?=htmlspecialchars($record['url'], ENT_QUOTES)?>" target="_blank" style="color: #6b46c1;">
                            <?=htmlspecialchars($record['url'], ENT_QUOTES)?>
                        </a>
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Credentials -->
    <div class="info-section">
        <h3><i class="fa-solid fa-key"></i> Login Credentials</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="label">User ID:</span>
                <span class="value"><?=htmlspecialchars($record['userid'] ?? '', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Password:</span>
                <span class="value"><?=htmlspecialchars($record['password'] ?? '', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Email:</span>
                <span class="value"><?=htmlspecialchars($record['email'] ?? '', ENT_QUOTES)?></span>
            </div>
        </div>
    </div>

    <!-- Financial Details -->
    <div class="info-section">
        <h3><i class="fa-solid fa-dollar-sign"></i> Financial Details</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="label">Investment:</span>
                <span class="value">$<?=htmlspecialchars($record['investment'] ?? '0.00', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Usage:</span>
                <span class="value"><?=htmlspecialchars($record['usage'] ?? 'N/A', ENT_QUOTES)?></span>
            </div>
        </div>
    </div>

    <!-- Additional Details -->
    <?php if (!empty($record['details'])): ?>
    <div class="info-section">
        <h3><i class="fa-solid fa-file-lines"></i> Additional Details</h3>
        <div class="notes-content">
            <?=nl2br(htmlspecialchars($record['details'], ENT_QUOTES))?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="info-section">
        <h3><i class="fa-solid fa-bolt"></i> Quick Actions</h3>
        <div class="quick-actions">
            <a href="sass-account.php?id=<?=$record['id']?>" class="action-btn">
                <i class="fa-solid fa-pen-to-square"></i>
                <span>Edit Account</span>
            </a>
            <a href="sass-account-view.php?id=<?=$record['id']?>" class="action-btn">
                <i class="fa-solid fa-eye"></i>
                <span>Full Details</span>
            </a>
            <?php if (!empty($record['url'])): ?>
            <a href="https://<?=htmlspecialchars($record['url'], ENT_QUOTES)?>" target="_blank" class="action-btn">
                <i class="fa-solid fa-external-link"></i>
                <span>Open Service</span>
            </a>
            <?php endif; ?>
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

.notes-content {
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