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
} catch (PDOException $exception) {
	exit('Database query error: ' . $exception->getMessage());
}
?>
<?=template_admin_header('Resource System Dashboard', 'resources', 'dashboard')?>

<div class="content-title">
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
                <i class="fa-solid fa-triangle-exclamation fa-2x" style="opacity: 0.6;"></i>
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
            <a href="error-logs.php" style="display: inline-block; margin-top: 15px; color: #333; text-decoration: none; opacity: 0.8;">View All →</a>
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

    <!-- Quick Actions -->
    <div style="background: #f8f9fa; padding: 25px; border-radius: 10px; border-left: 4px solid #6b46c1;">
        <h3 style="margin-top: 0; color: #333;"><i class="fa-solid fa-bolt"></i> Quick Actions</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <a href="domains.php" class="btn btn-primary" style="text-align: center; padding: 12px;">
                <i class="fa-solid fa-globe"></i> Manage Domains
            </a>
            <a href="dev-projects.php" class="btn btn-primary" style="text-align: center; padding: 12px;">
                <i class="fa-solid fa-code"></i> Dev Projects
            </a>
            <a href="client-projects.php" class="btn btn-primary" style="text-align: center; padding: 12px;">
                <i class="fa-solid fa-diagram-project"></i> Client Projects
            </a>
            <a href="warranties.php" class="btn btn-primary" style="text-align: center; padding: 12px;">
                <i class="fa-solid fa-shield-halved"></i> Warranties
            </a>
            <a href="sass-accounts.php" class="btn btn-primary" style="text-align: center; padding: 12px;">
                <i class="fa-solid fa-cloud"></i> SaaS Accounts
            </a>
            <a href="error-logs.php" class="btn btn-primary" style="text-align: center; padding: 12px;">
                <i class="fa-solid fa-triangle-exclamation"></i> Error Logs
            </a>
        </div>
    </div>
</div>

<?=template_admin_footer()?>
