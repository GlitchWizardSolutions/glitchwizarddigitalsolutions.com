<?php
// Diagnostic: Check which domains table has data
require 'assets/includes/admin_config.php';

echo "<h2>Checking Domain Tables</h2>";

// Check login_db.domains
try {
    $pdo = new PDO('mysql:host=' . db_host . ';dbname=' . db_name . ';charset=' . db_charset, db_user, db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h3>Table: glitchwizarddigi_login_db.domains</h3>";
    $stmt = $pdo->query("SELECT id, domain, due_date, status, account_id FROM domains ORDER BY due_date DESC");
    $domains = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Domain</th><th>Due Date</th><th>Status</th><th>Account ID</th></tr>";
    foreach ($domains as $d) {
        echo "<tr>";
        echo "<td>{$d['id']}</td>";
        echo "<td>{$d['domain']}</td>";
        echo "<td>{$d['due_date']}</td>";
        echo "<td>{$d['status']}</td>";
        echo "<td>{$d['account_id']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p><strong>Total domains in login_db: " . count($domains) . "</strong></p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red'>Error with login_db.domains: " . $e->getMessage() . "</p>";
}

// Check reminders.domains (if it exists)
try {
    $pdo_rem = new PDO('mysql:host=' . db_host . ';dbname=glitchwizarddigi_reminders;charset=utf8mb4', db_user, db_pass);
    $pdo_rem->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h3>Table: glitchwizarddigi_reminders.domains</h3>";
    $stmt = $pdo_rem->query("SELECT id, domain_name, expiration_date, status FROM domains ORDER BY expiration_date DESC");
    $domains_rem = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Domain Name</th><th>Expiration Date</th><th>Status</th></tr>";
    foreach ($domains_rem as $d) {
        echo "<tr>";
        echo "<td>{$d['id']}</td>";
        echo "<td>{$d['domain_name']}</td>";
        echo "<td>{$d['expiration_date']}</td>";
        echo "<td>{$d['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p><strong>Total domains in reminders: " . count($domains_rem) . "</strong></p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red'>Error with reminders.domains: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>Notification Query Test</h3>";
$date = new DateTime();
$date->add(new DateInterval('P30D'));
$date_end = $date->format('Y-m-d');

$stmt = $pdo->prepare('SELECT * FROM domains WHERE status = "Active" AND due_date <= ?');
$stmt->execute([$date_end]);
$expiring = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Domains expiring in next 30 days (notification query): <strong>" . count($expiring) . "</strong></p>";
foreach ($expiring as $d) {
    echo "<p>- {$d['domain']} (Due: {$d['due_date']}, Status: {$d['status']})</p>";
}
?>
