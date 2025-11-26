 <?php
require 'assets/includes/admin_config.php';
include_once '../assets/includes/components.php';

// Check if the user is logged-in
check_loggedin($pdo, '../../index.php');
// Fetch account details associated with the logged-in user
$stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
$stmt->execute([ $_SESSION['id'] ]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

// Connect to the login Database using the PDO interface
try {
	$logon_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name . ';charset=' . db_charset, db_user, db_pass);
	$logon_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	exit('Failed to connect to the logon database!');
}

// Check if ID parameter is provided
if (!isset($_GET['id'])) {
    exit('No ID specified!');
}

// Retrieve the project record from the database
$stmt = $logon_db->prepare('SELECT * FROM client_projects WHERE id = ?');
$stmt->execute([ $_GET['id'] ]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if record exists
if (!$record) {
    exit('Project not found!');
}

// Check if the user is an admin or project owner
if ($account['role'] != 'Admin' && $account['id'] != $record['acc_id']) {
    exit('You do not have permission to access this page!');
}

// Get related account information
$stmt = $logon_db->prepare('SELECT * FROM accounts WHERE id = ?');
$stmt->execute([ $record['acc_id'] ]);
$acc_id = $stmt->fetch(PDO::FETCH_ASSOC);

// Get domain information
$domain_id = null;
if (!empty($record['domain_id'])) {
    $stmt = $logon_db->prepare('SELECT * FROM domains WHERE id = ?');
    $stmt->execute([ $record['domain_id'] ]);
    $domain_id = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get project type information
$project_type_id = null;
if (!empty($record['project_type_id'])) {
    $stmt = $logon_db->prepare('SELECT * FROM project_types WHERE id = ?');
    $stmt->execute([ $record['project_type_id'] ]);
    $project_type_id = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get project logs
$stmt = $logon_db->prepare('SELECT client_projects_id, client_note, dev_note, private_dev_note, date_created FROM client_projects_logs WHERE client_projects_id = ? ORDER BY date_created DESC LIMIT 5');
$stmt->execute([ $_GET['id'] ]);
$project_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate project status
$project_active = false;
$project_on_hold = false;
$project_completed = false;

$status = strtolower($record['project_status'] ?? '');
if ($status == 'active') {
    $project_active = true;
} elseif ($status == 'on hold' || $status == 'pending') {
    $project_on_hold = true;
} elseif ($status == 'completed') {
    $project_completed = true;
} else {
    $project_active = true; // Default to active
} 
?>
<?=template_admin_header('Client Project', 'resources', 'projects')?>

<?=generate_breadcrumbs([
    ['label' => 'Resource System', 'url' => 'index.php'],
    ['label' => 'Client Projects', 'url' => 'client-projects.php'],
    ['label' => 'Quick Reference']
])?>

<div class="content-title mb-3">
    <div class="title">
        <i class="fa-solid fa-diagram-project"></i>
        <div class="txt">
            <h2>Client Project Information</h2>
            <p>Quick reference for <?=htmlspecialchars($record['subject'] ?? '', ENT_QUOTES)?></p>
        </div>
    </div>
</div>

<div class="content-title responsive-flex-wrap responsive-pad-bot-3">
    <a href="client-projects.php" class="btn btn-secondary mar-right-2">Back to List</a>
    <a href="client-project.php?id=<?=$record['id']?>" class="btn btn-primary">Edit</a>
</div>

<div class="content-block">
    
    <!-- Project Status Card -->
    <div class="status-card <?=$project_completed ? 'completed' : ($project_on_hold ? 'warning' : 'active')?>">
        <?php if ($project_completed): ?>
            <div class="status-icon completed-icon">✓</div>
            <h2>PROJECT COMPLETED</h2>
            <p class="status-message">This project has been completed</p>
        <?php elseif ($project_on_hold): ?>
            <div class="status-icon warning-icon">⏸</div>
            <h2>PROJECT ON HOLD</h2>
            <p class="status-message">This project is currently paused</p>
        <?php else: ?>
            <div class="status-icon active-icon">⚡</div>
            <h2>PROJECT ACTIVE</h2>
            <p class="status-message">This project is in progress</p>
        <?php endif; ?>
    </div>

    <!-- Project Information -->
    <div class="info-section">
        <h3><i class="fa-solid fa-diagram-project"></i> Project Information</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="label">Project Name:</span>
                <span class="value"><?=htmlspecialchars($record['subject'] ?? '', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Project Type:</span>
                <span class="value"><?=htmlspecialchars($project_type_id['name'] ?? 'N/A', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Status:</span>
                <span class="value"><?=htmlspecialchars($record['project_status'] ?? '', ENT_QUOTES)?></span>
            </div>
            <?php if ($domain_id): ?>
            <div class="info-item">
                <span class="label">Domain:</span>
                <span class="value"><?=htmlspecialchars($domain_id['domain'] ?? '', ENT_QUOTES)?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Project Type Details -->
    <?php if ($project_type_id): ?>
    <div class="info-section">
        <h3><i class="fa-solid fa-list-check"></i> Project Type Details</h3>
        <?php if (!empty($project_type_id['description'])): ?>
        <div style="margin-bottom: 15px;">
            <strong style="color: #666;">Description:</strong>
            <p style="margin: 5px 0;"><?=nl2br(htmlspecialchars($project_type_id['description'], ENT_QUOTES))?></p>
        </div>
        <?php endif; ?>
        <?php if (!empty($project_type_id['deliverables'])): ?>
        <div>
            <strong style="color: #666;">Deliverables:</strong>
            <p style="margin: 5px 0;"><?=nl2br(htmlspecialchars($project_type_id['deliverables'], ENT_QUOTES))?></p>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Timeline -->
    <div class="info-section">
        <h3><i class="fa-solid fa-calendar-days"></i> Timeline</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="label">Date Created:</span>
                <span class="value"><?=!empty($record['date_created']) ? date('F j, Y', strtotime($record['date_created'])) : 'N/A'?></span>
            </div>
            <div class="info-item">
                <span class="label">Last Updated:</span>
                <span class="value"><?=!empty($record['date_updated']) ? date('F j, Y g:i A', strtotime($record['date_updated'])) : 'N/A'?></span>
            </div>
        </div>
    </div>

    <!-- Client Information -->
    <?php if ($acc_id): ?>
    <div class="info-section">
        <h3><i class="fa-solid fa-user"></i> Client Information</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="label">Name:</span>
                <span class="value"><?=htmlspecialchars($acc_id['full_name'] ?? '', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Email:</span>
                <span class="value"><?=htmlspecialchars($acc_id['email'] ?? '', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Phone:</span>
                <span class="value"><?=htmlspecialchars($acc_id['phone'] ?? '', ENT_QUOTES)?></span>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Project Logs -->
    <?php if (!empty($project_logs)): ?>
    <div class="info-section">
        <h3><i class="fa-solid fa-comments"></i> Recent Project Logs (Last 5)</h3>
        <div style="background: white; padding: 15px; border-radius: 6px;">
            <?php foreach ($project_logs as $log): ?>
            <div style="border-bottom: 2px solid #dee2e6; padding: 15px 0; margin-bottom: 15px;">
                <div style="background: #6c757d; color: white; padding: 8px 12px; border-radius: 4px; margin-bottom: 10px; font-weight: bold;">
                    <?=date('M d, Y', strtotime($log['date_created'] ?? ''))?>
                </div>
                <?php if (!empty($log['dev_note'])): ?>
                <div style="background: #F8F4FF; padding: 10px; margin-bottom: 8px; border-radius: 4px;">
                    <strong style="color: #6b46c1;">WebDev:</strong>
                    <span><?=nl2br(htmlspecialchars($log['dev_note'], ENT_QUOTES))?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($log['client_note'])): ?>
                <div style="background: #F5F5F5; padding: 10px; border-radius: 4px;">
                    <strong style="color: #666;">Client:</strong>
                    <span><?=nl2br(htmlspecialchars($log['client_note'], ENT_QUOTES))?></span>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <a href="client-project-logs.php" class="btn btn-secondary" style="margin-top: 10px;">View All Logs</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="info-section">
        <h3><i class="fa-solid fa-bolt"></i> Quick Actions</h3>
        <div class="quick-actions">
            <a href="client-project.php?id=<?=$record['id']?>" class="action-btn">
                <i class="fa-solid fa-pen-to-square"></i>
                <span>Edit Project</span>
            </a>
            <a href="client-project-view.php?id=<?=$record['id']?>" class="action-btn">
                <i class="fa-solid fa-eye"></i>
                <span>Full Details</span>
            </a>
            <a href="client-project-log.php?project_id=<?=$record['id']?>" class="action-btn">
                <i class="fa-solid fa-plus"></i>
                <span>Add Log Entry</span>
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
.status-card.completed {
    background-color: #d1ecf1;
    border-color: #17a2b8;
    color: #0c5460;
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