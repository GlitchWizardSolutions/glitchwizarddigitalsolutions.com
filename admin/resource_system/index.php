<?php
ini_set('display_errors', 1); 
ini_set('display_startup_errors', 1); 
error_reporting(E_ALL);

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

// Connect to databases
try {
	$login_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name . ';charset=' . db_charset, db_user, db_pass);
	$login_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	exit('Failed to connect to login database! ' . $exception->getMessage());
}

try {
	$onthego_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name2 . ';charset=' . db_charset, db_user, db_pass);
	$onthego_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	exit('Failed to connect to the on the go database! ' . $exception->getMessage());
}

try {
	$error_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name9 . ';charset=' . db_charset, db_user, db_pass);
	$error_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	exit('Failed to connect to error handling database! ' . $exception->getMessage());
}

// Handle clear all errors action
if (isset($_GET['clear_errors']) && $_GET['clear_errors'] == '1') {
	try {
		$error_db->exec('TRUNCATE TABLE error_handling');
		header('Location: index.php?cleared=1');
		exit;
	} catch (PDOException $exception) {
		exit('Failed to clear error logs: ' . $exception->getMessage());
	}
}

// Handle debug logging toggle
if (isset($_POST['toggle_debug_logging'])) {
	try {
		$new_state = $debug_logging_enabled ? 0 : 1;
		$stmt = $error_db->prepare('UPDATE logging_settings SET debug_logging_enabled = ?, last_updated = NOW(), updated_by = ? WHERE id = 1');
		$stmt->execute([$new_state, $account['username'] ?? 'Unknown']);
		header('Location: index.php?toggled=1');
		exit;
	} catch (PDOException $exception) {
		exit('Failed to toggle debug logging: ' . $exception->getMessage());
	}
}

// Get resource counts with error handling
try {
	// Login DB (db_name)
	$domains_total = $login_db->query('SELECT COUNT(*) FROM domains')->fetchColumn();
	$domains_expiring = $login_db->query('SELECT COUNT(*) FROM domains WHERE due_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY)')->fetchColumn();
	
	$client_projects_total = $login_db->query('SELECT COUNT(*) FROM client_projects')->fetchColumn();
	$client_projects_active = $login_db->query('SELECT COUNT(*) FROM client_projects WHERE project_status = "active"')->fetchColumn();
	
	$project_types_total = $login_db->query('SELECT COUNT(*) FROM project_types')->fetchColumn();

	// OnTheGo DB (db_name2)
	$sass_accounts_total = $onthego_db->query('SELECT COUNT(*) FROM sass_account')->fetchColumn();

	$financial_accounts_total = $onthego_db->query('SELECT COUNT(*) FROM financial_accounts')->fetchColumn();

	$warranties_total = $onthego_db->query('SELECT COUNT(*) FROM warranty_tickets')->fetchColumn();
	$warranties_active = $onthego_db->query('SELECT COUNT(*) FROM warranty_tickets WHERE ticket_status = "active"')->fetchColumn();
	$warranties_expiring = $onthego_db->query('SELECT COUNT(*) FROM warranty_tickets WHERE warranty_expiration_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY) AND ticket_status = "active"')->fetchColumn();

	$caches_total = $onthego_db->query('SELECT COUNT(*) FROM cache')->fetchColumn();
	
	// Error Handling DB (db_name9)
	$error_logs_total = $error_db->query('SELECT COUNT(*) FROM error_handling')->fetchColumn();
	$error_logs_24h = $error_db->query('SELECT COUNT(*) FROM error_handling WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)')->fetchColumn();
	
	// Check for critical errors today
	$critical_errors_today = $error_db->query('SELECT COUNT(*) FROM error_handling WHERE severity = "Critical" AND DATE(timestamp) = CURDATE()')->fetchColumn();
	
	// Get debug logging toggle state
	$debug_logging_enabled = $error_db->query('SELECT debug_logging_enabled FROM logging_settings WHERE id = 1')->fetchColumn();
} catch (PDOException $exception) {
	exit('Database query error: ' . $exception->getMessage());
}
?>
<?=template_admin_header('Resource System Dashboard', 'resources', 'dashboard')?>

<div class="content-title mb-3">
    <div class="title">
        <i class="fa-solid fa-chart-line"></i>
        <div class="txt">
            <h2>Resource System Dashboard</h2>
            <p>Overview of all resource types</p>
        </div>
    </div>
</div>

<div class="content-block">
    <div class="dashboard-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px;">
        
        <!-- Domains Card -->
        <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0; font-size: 18px;">Domains</h3>
                <i class="fa-solid fa-globe fa-2x" style="opacity: 0.8;"></i>
            </div>
            <div style="font-size: 36px; font-weight: bold; margin-bottom: 10px;"><?=$domains_total?></div>
            <?php if ($domains_expiring > 0): ?>
            <div style="background: rgba(255,255,255,0.2); padding: 8px 12px; border-radius: 5px; font-size: 14px;">
                ⚠️ <?=$domains_expiring?> expiring in 30 days
            </div>
            <?php endif; ?>
            <a href="domains.php" style="display: inline-block; margin-top: 15px; color: white; text-decoration: none; opacity: 0.9;">View All →</a>
        </div>

        <!-- Client Projects Card -->
        <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0; font-size: 18px;">Client Projects</h3>
                <i class="fa-solid fa-diagram-project fa-2x" style="opacity: 0.8;"></i>
            </div>
            <div style="font-size: 36px; font-weight: bold; margin-bottom: 10px;"><?=$client_projects_total?></div>
            <div style="background: rgba(255,255,255,0.2); padding: 8px 12px; border-radius: 5px; font-size: 14px;">
                <?=$client_projects_active?> active
            </div>
            <a href="client-projects.php" style="display: inline-block; margin-top: 15px; color: white; text-decoration: none; opacity: 0.9;">View All →</a>
        </div>

        <!-- SaaS Accounts Card -->
        <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0; font-size: 18px;">SaaS Accounts</h3>
                <i class="fa-solid fa-cloud fa-2x" style="opacity: 0.8;"></i>
            </div>
            <div style="font-size: 36px; font-weight: bold; margin-bottom: 10px;"><?=$sass_accounts_total?></div>
            <div style="background: rgba(255,255,255,0.2); padding: 8px 12px; border-radius: 5px; font-size: 14px;">
                Active subscriptions
            </div>
            <a href="sass-accounts.php" style="display: inline-block; margin-top: 15px; color: white; text-decoration: none; opacity: 0.9;">View All →</a>
        </div>

        <!-- Financial Institutions Card -->
        <div class="stat-card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0; font-size: 18px;">Financial</h3>
                <i class="fa-solid fa-building-columns fa-2x" style="opacity: 0.8;"></i>
            </div>
            <div style="font-size: 36px; font-weight: bold; margin-bottom: 10px;"><?=$financial_accounts_total?></div>
            <div style="background: rgba(255,255,255,0.2); padding: 8px 12px; border-radius: 5px; font-size: 14px;">
                Financial institutions
            </div>
            <a href="financial-institutions.php" style="display: inline-block; margin-top: 15px; color: white; text-decoration: none; opacity: 0.9;">View All →</a>
        </div>

        <!-- Warranties Card -->
        <div class="stat-card" style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); color: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0; font-size: 18px;">Warranties</h3>
                <i class="fa-solid fa-shield-halved fa-2x" style="opacity: 0.8;"></i>
            </div>
            <div style="font-size: 36px; font-weight: bold; margin-bottom: 10px;"><?=$warranties_total?></div>
            <div style="background: rgba(255,255,255,0.2); padding: 8px 12px; border-radius: 5px; font-size: 14px; margin-bottom: 5px;">
                <?=$warranties_active?> active
            </div>
            <?php if ($warranties_expiring > 0): ?>
            <div style="background: rgba(255,193,7,0.3); padding: 8px 12px; border-radius: 5px; font-size: 14px;">
                ⚠️ <?=$warranties_expiring?> expiring soon
            </div>
            <?php endif; ?>
            <a href="warranties.php" style="display: inline-block; margin-top: 15px; color: white; text-decoration: none; opacity: 0.9;">View All →</a>
        </div>

        <!-- Error Logs Card -->
        <div class="stat-card" style="background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%); color: #333; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0; font-size: 18px;">Error Logs</h3>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <!-- Custom Check Engine Icon -->
                    <?php if ($critical_errors_today > 0): ?>
                        <img src="assets/img/icons/redCheckEngine.svg" alt="Critical Errors" style="width: 40px; height: 40px;">
                    <?php elseif ($error_logs_24h > 0): ?>
                        <img src="assets/img/icons/goldCheckEngine.svg" alt="Errors Detected" style="width: 40px; height: 40px;">
                    <?php else: ?>
                        <img src="assets/img/icons/greyCheckEngine.svg" alt="No Errors" style="width: 40px; height: 40px;">
                    <?php endif; ?>
                    <i class="fa-solid fa-triangle-exclamation fa-2x" style="opacity: 0.6;"></i>
                </div>
            </div>

            <!-- Check Engine Icon Options -->
            <div style="margin-bottom: 15px; padding: 10px; background: rgba(255,255,255,0.1); border-radius: 8px;">
                <div style="font-size: 12px; margin-bottom: 8px; color: #666;">Choose Check Engine Icon:</div>
                <div style="display: flex; gap: 15px; justify-content: center;">
                    <!-- Option 1: Classic Dashboard Light -->
                    <div style="text-align: center;">
                        <div style="font-size: 10px; margin-bottom: 3px; color: #666;">Classic</div>
                        <div style="width: 32px; height: 32px; display: inline-block;">
                            <?php if ($critical_errors_today > 0): ?>
                                <svg viewBox="0 0 32 32" style="width: 100%; height: 100%;">
                                    <rect x="2" y="2" width="28" height="28" rx="4" ry="4" fill="#dc3545" stroke="#b02a37" stroke-width="1"/>
                                    <text x="16" y="20" text-anchor="middle" font-family="Arial, sans-serif" font-size="8" font-weight="bold" fill="white">ENGINE</text>
                                </svg>
                            <?php elseif ($error_logs_24h > 0): ?>
                                <svg viewBox="0 0 32 32" style="width: 100%; height: 100%;">
                                    <rect x="2" y="2" width="28" height="28" rx="4" ry="4" fill="#ffc107" stroke="#d39e00" stroke-width="1"/>
                                    <text x="16" y="20" text-anchor="middle" font-family="Arial, sans-serif" font-size="8" font-weight="bold" fill="black">ENGINE</text>
                                </svg>
                            <?php else: ?>
                                <svg viewBox="0 0 32 32" style="width: 100%; height: 100%;">
                                    <rect x="2" y="2" width="28" height="28" rx="4" ry="4" fill="#6c757d" stroke="#495057" stroke-width="1"/>
                                    <text x="16" y="20" text-anchor="middle" font-family="Arial, sans-serif" font-size="8" font-weight="bold" fill="white">ENGINE</text>
                                </svg>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Option 2: Modern Dashboard Light -->
                    <div style="text-align: center;">
                        <div style="font-size: 10px; margin-bottom: 3px; color: #666;">Modern</div>
                        <div style="width: 32px; height: 32px; display: inline-block;">
                            <?php if ($critical_errors_today > 0): ?>
                                <svg viewBox="0 0 32 32" style="width: 100%; height: 100%;">
                                    <rect x="1" y="1" width="30" height="30" rx="15" ry="15" fill="#dc3545" stroke="#b02a37" stroke-width="2"/>
                                    <circle cx="16" cy="16" r="12" fill="none" stroke="white" stroke-width="1"/>
                                    <text x="16" y="12" text-anchor="middle" font-family="Arial, sans-serif" font-size="6" font-weight="bold" fill="white">CHECK</text>
                                    <text x="16" y="20" text-anchor="middle" font-family="Arial, sans-serif" font-size="6" font-weight="bold" fill="white">ENGINE</text>
                                </svg>
                            <?php elseif ($error_logs_24h > 0): ?>
                                <svg viewBox="0 0 32 32" style="width: 100%; height: 100%;">
                                    <rect x="1" y="1" width="30" height="30" rx="15" ry="15" fill="#ffc107" stroke="#d39e00" stroke-width="2"/>
                                    <circle cx="16" cy="16" r="12" fill="none" stroke="black" stroke-width="1"/>
                                    <text x="16" y="12" text-anchor="middle" font-family="Arial, sans-serif" font-size="6" font-weight="bold" fill="black">CHECK</text>
                                    <text x="16" y="20" text-anchor="middle" font-family="Arial, sans-serif" font-size="6" font-weight="bold" fill="black">ENGINE</text>
                                </svg>
                            <?php else: ?>
                                <svg viewBox="0 0 32 32" style="width: 100%; height: 100%;">
                                    <rect x="1" y="1" width="30" height="30" rx="15" ry="15" fill="#6c757d" stroke="#495057" stroke-width="2"/>
                                    <circle cx="16" cy="16" r="12" fill="none" stroke="white" stroke-width="1"/>
                                    <text x="16" y="12" text-anchor="middle" font-family="Arial, sans-serif" font-size="6" font-weight="bold" fill="white">CHECK</text>
                                    <text x="16" y="20" text-anchor="middle" font-family="Arial, sans-serif" font-size="6" font-weight="bold" fill="white">ENGINE</text>
                                </svg>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Option 3: Simple Warning Light -->
                    <div style="text-align: center;">
                        <div style="font-size: 10px; margin-bottom: 3px; color: #666;">Warning</div>
                        <div style="width: 32px; height: 32px; display: inline-block;">
                            <?php if ($critical_errors_today > 0): ?>
                                <svg viewBox="0 0 32 32" style="width: 100%; height: 100%;">
                                    <rect x="4" y="4" width="24" height="24" rx="2" ry="2" fill="#dc3545" stroke="#b02a37" stroke-width="1"/>
                                    <text x="16" y="16" text-anchor="middle" dominant-baseline="middle" font-family="Arial, sans-serif" font-size="10" font-weight="bold" fill="white">!</text>
                                    <text x="16" y="26" text-anchor="middle" font-family="Arial, sans-serif" font-size="5" font-weight="bold" fill="white">ENGINE</text>
                                </svg>
                            <?php elseif ($error_logs_24h > 0): ?>
                                <svg viewBox="0 0 32 32" style="width: 100%; height: 100%;">
                                    <rect x="4" y="4" width="24" height="24" rx="2" ry="2" fill="#ffc107" stroke="#d39e00" stroke-width="1"/>
                                    <text x="16" y="16" text-anchor="middle" dominant-baseline="middle" font-family="Arial, sans-serif" font-size="10" font-weight="bold" fill="black">!</text>
                                    <text x="16" y="26" text-anchor="middle" font-family="Arial, sans-serif" font-size="5" font-weight="bold" fill="black">ENGINE</text>
                                </svg>
                            <?php else: ?>
                                <svg viewBox="0 0 32 32" style="width: 100%; height: 100%;">
                                    <rect x="4" y="4" width="24" height="24" rx="2" ry="2" fill="#6c757d" stroke="#495057" stroke-width="1"/>
                                    <text x="16" y="16" text-anchor="middle" dominant-baseline="middle" font-family="Arial, sans-serif" font-size="10" font-weight="bold" fill="white">!</text>
                                    <text x="16" y="26" text-anchor="middle" font-family="Arial, sans-serif" font-size="5" font-weight="bold" fill="white">ENGINE</text>
                                </svg>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Option 4: Engine Block (Original) -->
                    <div style="text-align: center;">
                        <div style="font-size: 10px; margin-bottom: 3px; color: #666;">Engine</div>
                        <div style="width: 32px; height: 32px; display: inline-block;">
                            <?php if ($critical_errors_today > 0): ?>
                                <svg viewBox="0 0 24 24" style="width: 100%; height: 100%; fill: #dc3545;">
                                    <path d="M8 2C6.89 2 6 2.89 6 4V6H4C2.89 6 2 6.89 2 8V10C2 11.11 2.89 12 4 12H6V14H4C2.89 14 2 14.89 2 16V18C2 19.11 2.89 20 4 20H6V22H8V20H10V22H12V20H14V22H16V20H18C19.11 20 20 19.11 20 18V16C20 14.89 19.11 14 18 14H16V12H18C19.11 12 20 11.11 20 10V8C20 6.89 19.11 6 18 6H16V4C16 2.89 15.11 2 14 2H8Z"/>
                                </svg>
                            <?php elseif ($error_logs_24h > 0): ?>
                                <svg viewBox="0 0 24 24" style="width: 100%; height: 100%; fill: #ffc107;">
                                    <path d="M8 2C6.89 2 6 2.89 6 4V6H4C2.89 6 2 6.89 2 8V10C2 11.11 2.89 12 4 12H6V14H4C2.89 14 2 14.89 2 16V18C2 19.11 2.89 20 4 20H6V22H8V20H10V22H12V20H14V22H16V20H18C19.11 20 20 19.11 20 18V16C20 14.89 19.11 14 18 14H16V12H18C19.11 12 20 11.11 20 10V8C20 6.89 19.11 6 18 6H16V4C16 2.89 15.11 2 14 2H8Z"/>
                                </svg>
                            <?php else: ?>
                                <svg viewBox="0 0 24 24" style="width: 100%; height: 100%; fill: #6c757d;">
                                    <path d="M8 2C6.89 2 6 2.89 6 4V6H4C2.89 6 2 6.89 2 8V10C2 11.11 2.89 12 4 12H6V14H4C2.89 14 2 14.89 2 16V18C2 19.11 2.89 20 4 20H6V22H8V20H10V22H12V20H14V22H16V20H18C19.11 20 20 19.11 20 18V16C20 14.89 19.11 14 18 14H16V12H18C19.11 12 20 11.11 20 10V8C20 6.89 19.11 6 18 6H16V4C16 2.89 15.11 2 14 2H8Z"/>
                                </svg>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div style="font-size: 36px; font-weight: bold; margin-bottom: 10px;"><?=$error_logs_total?></div>
            <?php if ($error_logs_24h > 0): ?>
            <div style="background: rgba(220,53,69,0.2); padding: 8px 12px; border-radius: 5px; font-size: 14px;">
                <?=$error_logs_24h?> in last 24 hours
            </div>
            <?php else: ?>
            <div style="background: rgba(0,0,0,0.1); padding: 8px 12px; border-radius: 5px; font-size: 14px;">
                No errors in 24h ✓
            </div>
            <?php endif; ?>
            <div style="margin-top: 15px; display: flex; gap: 10px; align-items: center; justify-content: space-between;">
                <div style="display: flex; gap: 10px; align-items: center;">
                    <a href="error-logs.php" style="color: #333; text-decoration: none; opacity: 0.8;">View All →</a>
                    <?php if ($error_logs_total > 0): ?>
                    <a href="?clear_errors=1" onclick="return confirm('Are you sure you want to delete ALL error logs? This cannot be undone.')" style="font-size: 12px; padding: 5px 10px; background: rgba(220,53,69,0.8); color: white; border-radius: 4px; text-decoration: none;">Clear All</a>
                    <?php endif; ?>
                </div>
                <form method="post" style="margin: 0;">
                    <button type="submit" name="toggle_debug_logging" style="font-size: 12px; padding: 5px 10px; background: <?=$debug_logging_enabled ? 'rgba(220,53,69,0.8)' : 'rgba(40,167,69,0.8)'?>; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        <?=$debug_logging_enabled ? 'Disable' : 'Enable'?> Debug Logging
                    </button>
                </form>
            </div>
        </div>

        <!-- Project Types Card -->
        <div class="stat-card" style="background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); color: #333; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0; font-size: 18px;">Project Types</h3>
                <i class="fa-solid fa-layer-group fa-2x" style="opacity: 0.6;"></i>
            </div>
            <div style="font-size: 36px; font-weight: bold; margin-bottom: 10px;"><?=$project_types_total?></div>
            <div style="background: rgba(0,0,0,0.1); padding: 8px 12px; border-radius: 5px; font-size: 14px;">
                Categories defined
            </div>
            <a href="project-types.php" style="display: inline-block; margin-top: 15px; color: #333; text-decoration: none; opacity: 0.8;">View All →</a>
        </div>

        <!-- Caches Card -->
        <div class="stat-card" style="background: linear-gradient(135deg, #e0c3fc 0%, #8ec5fc 100%); color: #333; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0; font-size: 18px;">Cache Records</h3>
                <i class="fa-solid fa-database fa-2x" style="opacity: 0.6;"></i>
            </div>
            <div style="font-size: 36px; font-weight: bold; margin-bottom: 10px;"><?=$caches_total?></div>
            <div style="background: rgba(0,0,0,0.1); padding: 8px 12px; border-radius: 5px; font-size: 14px;">
                Cached items
            </div>
            <a href="caches.php" style="display: inline-block; margin-top: 15px; color: #333; text-decoration: none; opacity: 0.8;">View All →</a>
        </div>

    </div>
</div>

<?=template_admin_footer()?>
