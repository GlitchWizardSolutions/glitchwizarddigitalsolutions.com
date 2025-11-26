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

// Connect to the access_resource database
try {
    $access_resource_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name5 . ';charset=' . db_charset, db_user, db_pass);
    $access_resource_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
    exit('Failed to connect to the access resource database!');
}

// Check whether the record ID is specified
if (!isset($_GET['id']) || empty($_GET['id'])) {
    exit('No ID specified!');
}

// Retrieve the access resource record from the database
$stmt = $access_resource_db->prepare('SELECT * FROM access_resource WHERE id = ?');
$stmt->execute([ $_GET['id'] ]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$record) {
    exit('Access resource record not found!');
}

// Determine status based on completeness of information
$resource_complete = false;
$resource_partial = false;
$resource_minimal = false;

$filled_count = 0;
$fields_to_check = ['site_admin_login', 'site_admin_pass', 'hosting_login', 'hosting_pass', 'db_host', 'db_user', 'db_pass', 'db_name'];

foreach ($fields_to_check as $field) {
    if (!empty($record[$field])) {
        $filled_count++;
    }
}

if ($filled_count >= 6) {
    $resource_complete = true;
} elseif ($filled_count >= 3) {
    $resource_partial = true;
} else {
    $resource_minimal = true;
}
?>
<?=template_admin_header('Access Resource', 'resources', 'access')?>

<?=generate_breadcrumbs([
    ['label' => 'Resource System', 'url' => 'index.php'],
    ['label' => 'Access Resources', 'url' => 'access_resources.php'],
    ['label' => 'Quick Reference']
])?>

<div class="content-title mb-3">
    <div class="title">
        <i class="fa-solid fa-key"></i>
        <div class="txt">
            <h2>Access Resource Information</h2>
            <p>Quick reference for <?=htmlspecialchars($record['project_name'] ?? 'Project #' . $record['project_id'], ENT_QUOTES)?></p>
        </div>
    </div>
</div>

<div class="content-title responsive-flex-wrap responsive-pad-bot-3">
    <a href="access_resources.php" class="btn btn-secondary mar-right-2">Back to List</a>
    <a href="access_resource.php?id=<?=$record['id']?>" class="btn btn-primary">Edit</a>
</div>

<div class="content-block">
    
    <!-- Resource Status Card -->
    <div class="status-card <?=$resource_minimal ? 'warning' : ($resource_partial ? 'warning' : 'active')?>">
        <?php if ($resource_complete): ?>
            <div class="status-icon active-icon">✓</div>
            <h2>RESOURCE COMPLETE</h2>
            <p class="status-message">All essential access credentials documented</p>
        <?php elseif ($resource_partial): ?>
            <div class="status-icon warning-icon">⚡</div>
            <h2>RESOURCE PARTIAL</h2>
            <p class="status-message">Some access credentials available</p>
        <?php else: ?>
            <div class="status-icon warning-icon">⚠</div>
            <h2>RESOURCE MINIMAL</h2>
            <p class="status-message">Limited access credentials documented</p>
        <?php endif; ?>
    </div>

    <!-- Project Information -->
    <div class="info-section">
        <h3><i class="fa-solid fa-diagram-project"></i> Project Information</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="label">Project ID:</span>
                <span class="value"><?=htmlspecialchars($record['project_id'] ?? '', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Project Name:</span>
                <span class="value"><?=htmlspecialchars($record['project_name'] ?? '', ENT_QUOTES)?></span>
            </div>
        </div>
    </div>

    <!-- Site Access Credentials -->
    <div class="info-section">
        <h3><i class="fa-solid fa-user-shield"></i> Site Access Credentials</h3>
        <div class="info-grid">
            <?php if (!empty($record['site_admin_login']) || !empty($record['site_admin_pass'])): ?>
            <div class="info-item">
                <span class="label">Admin Login:</span>
                <span class="value"><?=htmlspecialchars($record['site_admin_login'] ?? 'N/A', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Admin Password:</span>
                <span class="value"><?=htmlspecialchars($record['site_admin_pass'] ?? 'N/A', ENT_QUOTES)?></span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($record['site_webmaster_login']) || !empty($record['site_webmaster_pass'])): ?>
            <div class="info-item">
                <span class="label">Webmaster Login:</span>
                <span class="value"><?=htmlspecialchars($record['site_webmaster_login'] ?? 'N/A', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Webmaster Password:</span>
                <span class="value"><?=htmlspecialchars($record['site_webmaster_pass'] ?? 'N/A', ENT_QUOTES)?></span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($record['site_user_login']) || !empty($record['site_user_pass'])): ?>
            <div class="info-item">
                <span class="label">User Login:</span>
                <span class="value"><?=htmlspecialchars($record['site_user_login'] ?? 'N/A', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">User Password:</span>
                <span class="value"><?=htmlspecialchars($record['site_user_pass'] ?? 'N/A', ENT_QUOTES)?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Hosting Information -->
    <div class="info-section">
        <h3><i class="fa-solid fa-server"></i> Hosting Information</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="label">Hosting Provider:</span>
                <span class="value"><?=htmlspecialchars($record['hosting_name'] ?? 'N/A', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Hosting Login:</span>
                <span class="value"><?=htmlspecialchars($record['hosting_login'] ?? 'N/A', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Hosting Password:</span>
                <span class="value"><?=htmlspecialchars($record['hosting_pass'] ?? 'N/A', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Hosting URL:</span>
                <span class="value"><?=htmlspecialchars($record['hosting_url'] ?? 'N/A', ENT_QUOTES)?></span>
            </div>
        </div>
    </div>

    <!-- Database Information -->
    <div class="info-section">
        <h3><i class="fa-solid fa-database"></i> Database Credentials</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="label">Database Host:</span>
                <span class="value"><?=htmlspecialchars($record['db_host'] ?? 'N/A', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Database User:</span>
                <span class="value"><?=htmlspecialchars($record['db_user'] ?? 'N/A', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Database Password:</span>
                <span class="value"><?=htmlspecialchars($record['db_pass'] ?? 'N/A', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Database Name:</span>
                <span class="value"><?=htmlspecialchars($record['db_name'] ?? 'N/A', ENT_QUOTES)?></span>
            </div>
        </div>
    </div>

    <!-- Additional Databases -->
    <?php if (!empty($record['db_name2']) || !empty($record['db_name3']) || !empty($record['db_name4']) || !empty($record['db_name5'])): ?>
    <div class="info-section">
        <h3><i class="fa-solid fa-database"></i> Additional Databases</h3>
        <div class="info-grid">
            <?php if (!empty($record['db_name2'])): ?>
            <div class="info-item">
                <span class="label">Database 2:</span>
                <span class="value"><?=htmlspecialchars($record['db_name2'], ENT_QUOTES)?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($record['db_name3'])): ?>
            <div class="info-item">
                <span class="label">Database 3:</span>
                <span class="value"><?=htmlspecialchars($record['db_name3'], ENT_QUOTES)?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($record['db_name4'])): ?>
            <div class="info-item">
                <span class="label">Database 4:</span>
                <span class="value"><?=htmlspecialchars($record['db_name4'], ENT_QUOTES)?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($record['db_name5'])): ?>
            <div class="info-item">
                <span class="label">Database 5:</span>
                <span class="value"><?=htmlspecialchars($record['db_name5'], ENT_QUOTES)?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="info-section">
        <h3><i class="fa-solid fa-bolt"></i> Quick Actions</h3>
        <div class="quick-actions">
            <a href="access_resource.php?id=<?=$record['id']?>" class="action-btn">
                <i class="fa-solid fa-pen-to-square"></i>
                <span>Edit Resource</span>
            </a>
            <a href="access_resource_view.php?id=<?=$record['id']?>" class="action-btn">
                <i class="fa-solid fa-eye"></i>
                <span>Full Details</span>
            </a>
            <?php if (!empty($record['hosting_url'])): ?>
            <a href="<?=htmlspecialchars($record['hosting_url'], ENT_QUOTES)?>" target="_blank" class="action-btn">
                <i class="fa-solid fa-external-link"></i>
                <span>Open Hosting Panel</span>
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
