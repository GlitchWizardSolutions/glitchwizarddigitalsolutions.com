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

// Connect to database
try {
	$login_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name . ';charset=' . db_charset, db_user, db_pass); 
	$login_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	exit('Failed to connect to the login system database!');
}

// Check if ID parameter is provided
if (!isset($_GET['id'])) {
    exit('No ID specified!');
}

// Retrieve the domain record from the database
$stmt = $login_db->prepare('SELECT d.*, i.business_name, a.full_name FROM domains d LEFT JOIN invoice_clients i ON d.invoice_client_id = i.id LEFT JOIN accounts a ON d.account_id = a.id WHERE d.id = ?');
$stmt->execute([ $_GET['id'] ]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if record exists
if (!$record) {
    exit('Domain record not found!');
}

// Calculate domain status
$domain_current = false;
$domain_expiring = false;
$domain_expired = false;
$days_remaining = 0;

if ($record['due_date']) {
    $today = new DateTime();
    $due_date = new DateTime($record['due_date']);
    $interval = $today->diff($due_date);
    $days_remaining = (int)$interval->format('%r%a'); // Signed days
    
    if ($days_remaining > 30) {
        $domain_current = true;
    } elseif ($days_remaining > 0 && $days_remaining <= 30) {
        $domain_expiring = true;
    } else {
        $domain_expired = true;
    }
}

// Get related projects
$stmt = $login_db->prepare('SELECT * FROM client_projects WHERE domain_id = ?');
$stmt->execute([ $_GET['id'] ]);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?=template_admin_header('Domain Usage', 'resources', 'domains')?>

<?=generate_breadcrumbs([
    ['label' => 'Resource System', 'url' => 'index.php'],
    ['label' => 'Domains', 'url' => 'domains.php'],
    ['label' => 'Quick Reference']
])?>

<div class="content-title mb-3">
    <div class="title">
        <i class="fa-solid fa-globe"></i>
        <div class="txt">
            <h2>Domain Information</h2>
            <p>Quick reference for <?=htmlspecialchars($record['domain'] ?? '', ENT_QUOTES)?></p>
        </div>
    </div>
</div>

<div class="content-title responsive-flex-wrap responsive-pad-bot-3">
    <a href="domains.php" class="btn btn-secondary mar-right-2">Back to List</a>
    <a href="domain.php?id=<?=$record['id']?>" class="btn btn-primary">Edit</a>
</div>

<div class="content-block">
    
    <!-- Domain Status Card -->
    <div class="status-card <?=$domain_expired ? 'expired' : ($domain_expiring ? 'warning' : 'active')?>">
        <?php if ($domain_expired): ?>
            <div class="status-icon expired-icon">⚠️</div>
            <h2>DOMAIN EXPIRED</h2>
            <p class="status-message">This domain expired <?=abs($days_remaining)?> days ago</p>
        <?php elseif ($domain_expiring): ?>
            <div class="status-icon warning-icon">⏰</div>
            <h2>DOMAIN EXPIRING SOON</h2>
            <p class="status-message"><?=$days_remaining?> days remaining</p>
        <?php else: ?>
            <div class="status-icon active-icon">✓</div>
            <h2>DOMAIN CURRENT</h2>
            <p class="status-message"><?=$days_remaining?> days remaining</p>
        <?php endif; ?>
    </div>

    <!-- Domain Information -->
    <div class="info-section">
        <h3><i class="fa-solid fa-globe"></i> Domain Information</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="label">Domain Name:</span>
                <span class="value"><?=htmlspecialchars($record['domain'] ?? '', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Status:</span>
                <span class="value"><?=htmlspecialchars($record['status'] ?? '', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Business:</span>
                <span class="value"><?=htmlspecialchars($record['business_name'] ?? '', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Member Name:</span>
                <span class="value"><?=htmlspecialchars($record['full_name'] ?? '', ENT_QUOTES)?></span>
            </div>
        </div>
    </div>

    <!-- Important Dates -->
    <div class="info-section">
        <h3><i class="fa-solid fa-calendar-days"></i> Important Dates</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="label">Renewal Due Date:</span>
                <span class="value <?=$domain_expired ? 'text-danger' : ''?>"><?=!empty($record['due_date']) ? date('F j, Y', strtotime($record['due_date'])) : 'N/A'?></span>
            </div>
            <div class="info-item">
                <span class="label">Amount Due:</span>
                <span class="value">$<?=htmlspecialchars($record['amount'] ?? '0.00', ENT_QUOTES)?></span>
            </div>
        </div>
    </div>

    <!-- Renewal Details -->
    <div class="info-section">
        <h3><i class="fa-solid fa-server"></i> Renewal Details</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="label">Renewal Host:</span>
                <span class="value"><?=htmlspecialchars($record['host_url'] ?? '', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Host Login:</span>
                <span class="value"><?=htmlspecialchars($record['host_login'] ?? '', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Host Password:</span>
                <span class="value"><?=htmlspecialchars($record['host_password'] ?? '', ENT_QUOTES)?></span>
            </div>
        </div>
    </div>

    <!-- Instructions/Notes -->
    <?php if (!empty($record['notes'])): ?>
    <div class="info-section">
        <h3><i class="fa-solid fa-file-lines"></i> Instructions & Notes</h3>
        <div class="notes-content">
            <?=nl2br(htmlspecialchars($record['notes'], ENT_QUOTES))?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Related Projects -->
    <?php if (!empty($projects)): ?>
    <div class="info-section">
        <h3><i class="fa-solid fa-diagram-project"></i> Related Projects</h3>
        <div style="background: white; padding: 15px; border-radius: 6px;">
            <table style="width: 100%;">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Last Updated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($projects as $project): ?>
                    <?php
                        $stmt = $login_db->prepare('SELECT * FROM project_types WHERE id = ?');
                        $stmt->execute([ $project['project_type_id'] ]);
                        $type = $stmt->fetch(PDO::FETCH_ASSOC);
                    ?>
                    <tr>
                        <td><?=htmlspecialchars($project['subject'], ENT_QUOTES)?></td>
                        <td><?=htmlspecialchars($type['name'] ?? 'N/A', ENT_QUOTES)?></td>
                        <td><?=htmlspecialchars($project['project_status'], ENT_QUOTES)?></td>
                        <td><?=date('m/d/Y', strtotime($project['date_updated']))?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="info-section">
        <h3><i class="fa-solid fa-bolt"></i> Quick Actions</h3>
        <div class="quick-actions">
            <a href="domain.php?id=<?=$record['id']?>" class="action-btn">
                <i class="fa-solid fa-pen-to-square"></i>
                <span>Edit Domain</span>
            </a>
            <a href="domain-view.php?id=<?=$record['id']?>" class="action-btn">
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
.text-danger {
    color: #dc3545 !important;
    font-weight: bold;
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