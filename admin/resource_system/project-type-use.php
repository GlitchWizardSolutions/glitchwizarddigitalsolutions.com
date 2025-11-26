<?php
require 'assets/includes/admin_config.php';
require_once '../assets/includes/components.php';

// Check if the user is logged-in
check_loggedin($pdo, '../../index.php');

// Fetch account details associated with the logged-in user
$stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
$stmt->execute([ $_SESSION['id'] ]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

// Connect to the login Database
try {
    $login_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name . ';charset=' . db_charset, db_user, db_pass);
    $login_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
    exit('Failed to connect to the login system database!');
}

// Check whether the record ID is specified
if (!isset($_GET['id']) || empty($_GET['id'])) {
    exit('No ID specified!');
}

// Retrieve the project type record from the database
$stmt = $login_db->prepare('SELECT * FROM project_types WHERE id = ?');
$stmt->execute([ $_GET['id'] ]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$record) {
    exit('Project type record not found!');
}

// Get count of projects using this type
$stmt = $login_db->prepare('SELECT COUNT(*) as project_count FROM client_projects WHERE project_type_id = ?');
$stmt->execute([ $record['id'] ]);
$usage = $stmt->fetch(PDO::FETCH_ASSOC);
$project_count = $usage['project_count'] ?? 0;

// Determine status based on usage and value
$type_active = false;
$type_moderate = false;
$type_inactive = false;

if ($project_count > 0) {
    $type_active = true;
} elseif (!empty($record['amount']) && $record['amount'] > 0) {
    $type_moderate = true;
} else {
    $type_inactive = true;
}
?>
<?=template_admin_header('Project Type', 'resources', 'types')?>

<?=generate_breadcrumbs([
    ['label' => 'Resource System', 'url' => 'index.php'],
    ['label' => 'Project Types', 'url' => 'project-types.php'],
    ['label' => 'Quick Reference']
])?>

<div class="content-title mb-3">
    <div class="title">
        <i class="fa-solid fa-list-check"></i>
        <div class="txt">
            <h2>Project Type Information</h2>
            <p>Quick reference for <?=htmlspecialchars($record['name'] ?? '', ENT_QUOTES)?></p>
        </div>
    </div>
</div>

<div class="content-title responsive-flex-wrap responsive-pad-bot-3">
    <a href="project-types.php" class="btn btn-secondary mar-right-2">Back to List</a>
    <a href="project-type.php?id=<?=$record['id']?>" class="btn btn-primary">Edit</a>
</div>

<div class="content-block">
    
    <!-- Project Type Status Card -->
    <div class="status-card <?=$type_inactive ? 'warning' : ($type_moderate ? 'warning' : 'active')?>">
        <?php if ($type_active): ?>
            <div class="status-icon active-icon">✓</div>
            <h2>PROJECT TYPE IN USE</h2>
            <p class="status-message"><?=$project_count?> active project<?=$project_count != 1 ? 's' : ''?> using this type</p>
        <?php elseif ($type_moderate): ?>
            <div class="status-icon warning-icon">⚡</div>
            <h2>PROJECT TYPE AVAILABLE</h2>
            <p class="status-message">Ready for use but not currently assigned</p>
        <?php else: ?>
            <div class="status-icon warning-icon">⏸</div>
            <h2>PROJECT TYPE INACTIVE</h2>
            <p class="status-message">Not currently in use</p>
        <?php endif; ?>
    </div>

    <!-- Project Type Information -->
    <div class="info-section">
        <h3><i class="fa-solid fa-list-check"></i> Type Details</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="label">Project Type Name:</span>
                <span class="value"><?=htmlspecialchars($record['name'] ?? '', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Standard Value:</span>
                <span class="value">$<?=number_format($record['amount'] ?? 0, 2)?></span>
            </div>
            <div class="info-item">
                <span class="label">Estimated Duration:</span>
                <span class="value"><?=htmlspecialchars($record['frequency'] ?? '', ENT_QUOTES)?> days</span>
            </div>
            <div class="info-item">
                <span class="label">Projects Using:</span>
                <span class="value"><?=$project_count?></span>
            </div>
        </div>
    </div>

    <!-- Description -->
    <?php if (!empty($record['description'])): ?>
    <div class="info-section">
        <h3><i class="fa-solid fa-file-lines"></i> Description</h3>
        <div style="background: white; padding: 15px; border-radius: 6px;">
            <?=nl2br(htmlspecialchars($record['description'], ENT_QUOTES))?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Deliverables -->
    <?php if (!empty($record['deliverables'])): ?>
    <div class="info-section">
        <h3><i class="fa-solid fa-box-check"></i> Deliverables</h3>
        <div style="background: white; padding: 15px; border-radius: 6px;">
            <?=nl2br(htmlspecialchars($record['deliverables'], ENT_QUOTES))?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="info-section">
        <h3><i class="fa-solid fa-bolt"></i> Quick Actions</h3>
        <div class="quick-actions">
            <a href="project-type.php?id=<?=$record['id']?>" class="action-btn">
                <i class="fa-solid fa-pen-to-square"></i>
                <span>Edit Project Type</span>
            </a>
            <a href="project-type-view.php?id=<?=$record['id']?>" class="action-btn">
                <i class="fa-solid fa-eye"></i>
                <span>Full Details</span>
            </a>
            <?php if ($project_count > 0): ?>
            <a href="client-projects.php?type=<?=$record['id']?>" class="action-btn">
                <i class="fa-solid fa-diagram-project"></i>
                <span>View Projects</span>
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
