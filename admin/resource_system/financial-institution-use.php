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

// Retrieve the financial card record from the database
$stmt = $onthego_db->prepare('SELECT * FROM financial_cards WHERE id = ?');
$stmt->execute([ $_GET['id'] ]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if record exists
if (!$record) {
    exit('Financial card not found!');
}

// Calculate card status based on expiration
$card_active = false;
$card_expiring = false;
$card_expired = false;
$days_remaining = 0;

if (!empty($record['expires']) && $record['expires'] != '00/00' && $record['expires'] != '12/9999') {
    try {
        // Parse expiration date (MM/YY or MM/YYYY format)
        $exp_parts = explode('/', $record['expires']);
        if (count($exp_parts) == 2) {
            $exp_month = (int)$exp_parts[0];
            $exp_year = (int)$exp_parts[1];
            // Convert 2-digit year to 4-digit if needed
            if ($exp_year < 100) {
                $exp_year += 2000;
            }
            // Set expiration to last day of the month
            $expiration = new DateTime($exp_year . '-' . $exp_month . '-' . date('t', mktime(0, 0, 0, $exp_month, 1, $exp_year)));
            $today = new DateTime();
            $interval = $today->diff($expiration);
            $days_remaining = (int)$interval->format('%r%a');
            
            if ($days_remaining > 30) {
                $card_active = true;
            } elseif ($days_remaining > 0 && $days_remaining <= 30) {
                $card_expiring = true;
            } else {
                $card_expired = true;
            }
        }
    } catch (Exception $e) {
        // If date parsing fails, assume active
        $card_active = true;
    }
} else {
    // No expiration or indefinite expiration
    $card_active = true;
}
?>
<?=template_admin_header('Financial Card Usage', 'resources', 'cards')?>

<?=generate_breadcrumbs([
    ['label' => 'Resource System', 'url' => 'index.php'],
    ['label' => 'Financial Institutions', 'url' => 'financial-institutions.php'],
    ['label' => 'Quick Reference']
])?>

<div class="content-title mb-3">
    <div class="title">
        <i class="fa-solid fa-credit-card"></i>
        <div class="txt">
            <h2>Financial Card Information</h2>
            <p>Quick reference for <?=htmlspecialchars($record['description'] ?? '', ENT_QUOTES)?></p>
        </div>
    </div>
</div>

<div class="content-title responsive-flex-wrap responsive-pad-bot-3">
    <a href="financial-institutions.php" class="btn btn-secondary mar-right-2">Back to List</a>
    <a href="financial-institution.php?id=<?=$record['id']?>" class="btn btn-primary">Edit</a>
</div>

<div class="content-block">
    
    <!-- Card Status Card -->
    <div class="status-card <?=$card_expired ? 'expired' : ($card_expiring ? 'warning' : 'active')?>">
        <?php if ($card_expired): ?>
            <div class="status-icon expired-icon">⚠️</div>
            <h2>CARD EXPIRED</h2>
            <p class="status-message">This card expired <?=abs($days_remaining)?> days ago</p>
        <?php elseif ($card_expiring): ?>
            <div class="status-icon warning-icon">⏰</div>
            <h2>CARD EXPIRING SOON</h2>
            <p class="status-message"><?=$days_remaining?> days remaining</p>
        <?php else: ?>
            <div class="status-icon active-icon">✓</div>
            <h2>CARD ACTIVE</h2>
            <p class="status-message">Card is valid</p>
        <?php endif; ?>
    </div>

    <!-- Card Information -->
    <div class="info-section">
        <h3><i class="fa-solid fa-credit-card"></i> Card Information</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="label">Description:</span>
                <span class="value"><?=htmlspecialchars($record['description'] ?? '', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Cardholder Name:</span>
                <span class="value"><?=htmlspecialchars($record['name'] ?? '', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Card Type:</span>
                <span class="value"><?=htmlspecialchars($record['card_type'] ?? '', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Brand:</span>
                <span class="value"><?=htmlspecialchars($record['brand'] ?? '', ENT_QUOTES)?></span>
            </div>
        </div>
    </div>

    <!-- Card Details -->
    <div class="info-section">
        <h3><i class="fa-solid fa-lock"></i> Card Details</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="label">Card Number:</span>
                <span class="value"><?=htmlspecialchars($record['card_number'] ?? '', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Expiration Date:</span>
                <span class="value <?=$card_expired ? 'text-danger' : ''?>"><?=htmlspecialchars($record['expires'] ?? '', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Security Code:</span>
                <span class="value"><?=htmlspecialchars($record['code'] ?? '', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">ZIP Code:</span>
                <span class="value"><?=htmlspecialchars($record['zip_code'] ?? '', ENT_QUOTES)?></span>
            </div>
        </div>
    </div>

    <!-- Banking Information -->
    <div class="info-section">
        <h3><i class="fa-solid fa-building-columns"></i> Banking Information</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="label">Bank/Institution:</span>
                <span class="value"><?=htmlspecialchars($record['bank'] ?? '', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Account Number:</span>
                <span class="value"><?=htmlspecialchars($record['account_number'] ?? 'N/A', ENT_QUOTES)?></span>
            </div>
            <div class="info-item">
                <span class="label">Routing Number:</span>
                <span class="value"><?=htmlspecialchars($record['routing_number'] ?? 'N/A', ENT_QUOTES)?></span>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="info-section">
        <h3><i class="fa-solid fa-bolt"></i> Quick Actions</h3>
        <div class="quick-actions">
            <a href="financial-institution.php?id=<?=$record['id']?>" class="action-btn">
                <i class="fa-solid fa-pen-to-square"></i>
                <span>Edit Card</span>
            </a>
            <a href="financial-institution-view.php?id=<?=$record['id']?>" class="action-btn">
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