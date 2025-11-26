<?php
require 'assets/includes/admin_config.php';
require_once '../assets/includes/components.php';

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
    // If there is an error with the connection, stop the script and display the error.
    exit('Failed to connect to the logon database!');
}

// Check whether the log ID is specified
if (!isset($_GET['id']) || empty($_GET['id'])) {
    exit('No ID specified!');
}

// Retrieve the log record from the database
$stmt = $logon_db->prepare('SELECT * FROM client_projects_logs WHERE id = ?');
$stmt->execute([ $_GET['id'] ]);
$log_record = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$log_record) {
    exit('Log record not found!');
}

// Get the associated project
$stmt = $logon_db->prepare('SELECT * FROM client_projects WHERE id = ?');
$stmt->execute([ $log_record['client_projects_id'] ]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    exit('Associated project not found!');
}

// Get account information
$stmt = $logon_db->prepare('SELECT * FROM accounts WHERE id = ?');
$stmt->execute([ $project['acc_id'] ]);
$acc_id = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if the user is an admin or the project owner
if ($account['role'] != 'Admin' && $account['id'] != $project['acc_id']) {
    exit('You do not have permission to access this page!');
}

// Get domain information (null-safe)
$domain_id = null;
if (!empty($project['domain_id'])) {
    $stmt = $logon_db->prepare('SELECT * FROM domains WHERE id = ?');
    $stmt->execute([ $project['domain_id'] ]);
    $domain_id = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get project type information (null-safe)
$project_type_id = null;
if (!empty($project['project_type_id'])) {
    $stmt = $logon_db->prepare('SELECT * FROM project_types WHERE id = ?');
    $stmt->execute([ $project['project_type_id'] ]);
    $project_type_id = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Determine log status (active if recent)
$log_date = new DateTime($log_record['date_created']);
$now = new DateTime();
$days_old = (int)$log_date->diff($now)->format('%a');

$log_recent = false;
$log_moderate = false;
$log_old = false;

if ($days_old <= 7) {
    $log_recent = true;
} elseif ($days_old <= 30) {
    $log_moderate = true;
} else {
    $log_old = true;
}
 
?>
<?=template_admin_header('Client Project Log', 'resources', 'logs')?>

<?=generate_breadcrumbs([
    ['label' => 'Resource System', 'url' => 'index.php'],
    ['label' => 'Client Project Logs', 'url' => 'client-project-logs.php'],
    ['label' => 'Quick Reference']
])?>

<div class="content-title mb-3">
    <div class="title">
        <i class="fa-solid fa-comment"></i>
        <div class="txt">
            <h2>Project Log Entry</h2>
            <p>Quick reference for log from <?=date('F j, Y', strtotime($log_record['date_created']))?></p>
        </div>
    </div>
</div>

<div class="content-title responsive-flex-wrap responsive-pad-bot-3">
    <a href="client-project-logs.php" class="btn btn-secondary mar-right-2">Back to List</a>
    <a href="client-project-use.php?id=<?=$project['id']?>" class="btn btn-secondary mar-right-2">View Project</a>
    <a href="client-project-log.php?id=<?=$log_record['id']?>" class="btn btn-primary">Edit</a>
</div>

<div class="content-block">
    
    <!-- Log Status Card -->
    <div class="status-card <?=$log_old ? 'expired' : ($log_moderate ? 'warning' : 'active')?>">
        <?php if ($log_recent): ?>
            <div class="status-icon active-icon">🆕</div>
            <h2>RECENT LOG ENTRY</h2>
            <p class="status-message">Created <?=$days_old?> day<?=$days_old != 1 ? 's' : ''?> ago</p>
        <?php elseif ($log_moderate): ?>
            <div class="status-icon warning-icon">📅</div>
            <h2>LOG ENTRY</h2>
            <p class="status-message">Created <?=$days_old?> days ago</p>
        <?php else: ?>
            <div class="status-icon expired-icon">📜</div>
            <h2>ARCHIVED LOG ENTRY</h2>
            <p class="status-message">Created <?=$days_old?> days ago</p>
        <?php endif; ?>
    </div>

    <!-- Log Content -->
    <div class="info-section">
        <h3><i class="fa-solid fa-comment-dots"></i> Log Details</h3>
        <div style="background: white; padding: 20px; border-radius: 6px;">
            <div style="margin-bottom: 20px;">
                <div style="background: #6c757d; color: white; padding: 8px 12px; border-radius: 4px; margin-bottom: 15px; font-weight: bold;">
                    <?=date('F j, Y \a\t g:i A', strtotime($log_record['date_created']))?>
                </div>
                
                <?php if (!empty($log_record['dev_note'])): ?>
                <div style="background: #F8F4FF; padding: 15px; margin-bottom: 15px; border-radius: 4px; border-left: 4px solid #6b46c1;">
                    <strong style="color: #6b46c1; font-size: 16px; display: block; margin-bottom: 10px;">
                        <i class="fa-solid fa-code"></i> Developer Note:
                    </strong>
                    <div style="color: #333; line-height: 1.6;">
                        <?=nl2br(htmlspecialchars($log_record['dev_note'], ENT_QUOTES))?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($log_record['client_note'])): ?>
                <div style="background: #F5F5F5; padding: 15px; margin-bottom: 15px; border-radius: 4px; border-left: 4px solid #999;">
                    <strong style="color: #666; font-size: 16px; display: block; margin-bottom: 10px;">
                        <i class="fa-solid fa-user"></i> Client Note:
                    </strong>
                    <div style="color: #333; line-height: 1.6;">
                        <?=nl2br(htmlspecialchars($log_record['client_note'], ENT_QUOTES))?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($account['role'] == 'Admin' && !empty($log_record['private_dev_note'])): ?>
                <div style="background: #fff3cd; padding: 15px; border-radius: 4px; border-left: 4px solid #ffc107;">
                    <strong style="color: #856404; font-size: 16px; display: block; margin-bottom: 10px;">
                        <i class="fa-solid fa-lock"></i> Private Developer Note (Admin Only):
                    </strong>
                    <div style="color: #333; line-height: 1.6;">
                        <?=nl2br(htmlspecialchars($log_record['private_dev_note'], ENT_QUOTES))?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Project Context -->
    <div class="info-section">
        <h3><i class="fa-solid fa-diagram-project"></i> Associated Project</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="label">Project Name:</span>
                <span class="value"><?=htmlspecialchars($project['subject'] ?? '', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Project Type:</span>
                <span class="value"><?=htmlspecialchars($project_type_id['name'] ?? 'N/A', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Status:</span>
                <span class="value"><?=htmlspecialchars($project['project_status'] ?? '', ENT_QUOTES)?></span>
            </div>
            <?php if ($domain_id): ?>
            <div class="info-item">
                <span class="label">Domain:</span>
                <span class="value"><?=htmlspecialchars($domain_id['domain'] ?? '', ENT_QUOTES)?></span>
            </div>
            <?php endif; ?>
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

    <!-- Quick Actions -->
    <div class="info-section">
        <h3><i class="fa-solid fa-bolt"></i> Quick Actions</h3>
        <div class="quick-actions">
            <a href="client-project-log.php?id=<?=$log_record['id']?>" class="action-btn">
                <i class="fa-solid fa-pen-to-square"></i>
                <span>Edit Log Entry</span>
            </a>
            <a href="client-project-use.php?id=<?=$project['id']?>" class="action-btn">
                <i class="fa-solid fa-diagram-project"></i>
                <span>View Project</span>
            </a>
            <a href="client-project-logs.php" class="action-btn">
                <i class="fa-solid fa-list"></i>
                <span>All Logs</span>
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
    grid-template-columns: repeat(auto-fit, minmin(250px, 1fr));
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