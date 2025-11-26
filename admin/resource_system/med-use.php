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

// Connect to the onthego database
try {
    $onthego_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name2 . ';charset=' . db_charset, db_user, db_pass);
    $onthego_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
    exit('Failed to connect to the on the go database!');
}

// Check whether the record ID is specified
if (!isset($_GET['id']) || empty($_GET['id'])) {
    exit('No ID specified!');
}

// Retrieve the medication record from the database
$stmt = $onthego_db->prepare('SELECT * FROM meds WHERE id = ?');
$stmt->execute([ $_GET['id'] ]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$record) {
    exit('Medication record not found!');
}

// Determine medication status
$med_active = false;
$med_inactive = false;
$med_discontinued = false;

$status = strtolower($record['status'] ?? '');
if ($status == 'active' || $status == 'in use' || $status == 'current') {
    $med_active = true;
} elseif ($status == 'inactive' || $status == 'not in use') {
    $med_inactive = true;
} else {
    $med_discontinued = true;
}
?>
<?=template_admin_header('Medication', 'resources', 'meds')?>

<?=generate_breadcrumbs([
    ['label' => 'Resource System', 'url' => 'index.php'],
    ['label' => 'Medications', 'url' => 'meds.php'],
    ['label' => 'Quick Reference']
])?>

<div class="content-title mb-3">
    <div class="title">
        <i class="fa-solid fa-pills"></i>
        <div class="txt">
            <h2>Medication Information</h2>
            <p>Quick reference for <?=htmlspecialchars($record['name'] ?? '', ENT_QUOTES)?></p>
        </div>
    </div>
</div>

<div class="content-title responsive-flex-wrap responsive-pad-bot-3">
    <a href="meds.php" class="btn btn-secondary mar-right-2">Back to List</a>
    <a href="med.php?id=<?=$record['id']?>" class="btn btn-primary">Edit</a>
</div>

<div class="content-block">
    
    <!-- Medication Status Card -->
    <div class="status-card <?=$med_discontinued ? 'expired' : ($med_inactive ? 'warning' : 'active')?>">
        <?php if ($med_active): ?>
            <div class="status-icon active-icon">💊</div>
            <h2>MEDICATION ACTIVE</h2>
            <p class="status-message">This medication is currently in use</p>
        <?php elseif ($med_inactive): ?>
            <div class="status-icon warning-icon">⏸</div>
            <h2>MEDICATION INACTIVE</h2>
            <p class="status-message">This medication is not currently being taken</p>
        <?php else: ?>
            <div class="status-icon expired-icon">✕</div>
            <h2>MEDICATION DISCONTINUED</h2>
            <p class="status-message">This medication has been discontinued</p>
        <?php endif; ?>
    </div>

    <!-- Medication Information -->
    <div class="info-section">
        <h3><i class="fa-solid fa-pills"></i> Medication Details</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="label">Medication Name:</span>
                <span class="value"><?=htmlspecialchars($record['name'] ?? '', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Status:</span>
                <span class="value"><?=htmlspecialchars($record['status'] ?? '', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Type:</span>
                <span class="value"><?=htmlspecialchars($record['type'] ?? '', ENT_QUOTES)?></span>
            </div>
        </div>
    </div>

    <!-- Usage Information -->
    <div class="info-section">
        <h3><i class="fa-solid fa-prescription-bottle-medical"></i> Usage Information</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="label">Patient:</span>
                <span class="value"><?=htmlspecialchars($record['patient'] ?? '', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Dosage:</span>
                <span class="value"><?=htmlspecialchars($record['dosage'] ?? '', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Frequency:</span>
                <span class="value"><?=htmlspecialchars($record['frequency'] ?? '', ENT_QUOTES)?></span>
            </div>
        </div>
    </div>

    <!-- Notes -->
    <?php if (!empty($record['notes'])): ?>
    <div class="info-section">
        <h3><i class="fa-solid fa-note-sticky"></i> Notes</h3>
        <div style="background: white; padding: 15px; border-radius: 6px;">
            <?=nl2br(htmlspecialchars($record['notes'], ENT_QUOTES))?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="info-section">
        <h3><i class="fa-solid fa-bolt"></i> Quick Actions</h3>
        <div class="quick-actions">
            <a href="med.php?id=<?=$record['id']?>" class="action-btn">
                <i class="fa-solid fa-pen-to-square"></i>
                <span>Edit Medication</span>
            </a>
            <a href="med-view.php?id=<?=$record['id']?>" class="action-btn">
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
