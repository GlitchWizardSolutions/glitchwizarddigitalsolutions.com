<?php
/**
 * Database Connection Diagnostic
 * Verifies which database and port is being used
 */

require_once 'assets/includes/admin_config.php';
require_once 'assets/includes/budget_constants.php';
require_once 'assets/includes/budget_helpers.php';

$budget_pdo = pdo_connect_budget_db();

echo "<h2>Database Connection Diagnostic</h2>";

// Show connection info
echo "<h3>Connection Info:</h3>";
echo "<pre>";
echo "db_host: " . db_host . "\n";
echo "db_name7: " . db_name7 . "\n";
echo "db_user: " . db_user . "\n";
echo "</pre>";

// Check what tables exist
echo "<h3>Available Tables:</h3>";
$stmt = $budget_pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "<pre>";
print_r($tables);
echo "</pre>";

// Check if csv_process exists and has data
if (in_array('csv_process', $tables)) {
    echo "<h3>csv_process Table Data:</h3>";
    
    // Row count
    $stmt = $budget_pdo->query("SELECT COUNT(*) FROM csv_process");
    $count = $stmt->fetchColumn();
    echo "<p><strong>Total rows:</strong> $count</p>";
    
    if ($count > 0) {
        // Summary by budget_id
        $stmt = $budget_pdo->query("
            SELECT 
                budget_id, 
                COUNT(*) as count, 
                SUM(credits) as credits, 
                SUM(debits) as debits, 
                SUM(credits+debits) as net 
            FROM csv_process 
            GROUP BY budget_id 
            ORDER BY budget_id
        ");
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Budget ID</th><th>Count</th><th>Credits</th><th>Debits</th><th>Net</th></tr>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['budget_id'] . "</td>";
            echo "<td>" . $row['count'] . "</td>";
            echo "<td>$" . number_format($row['credits'], 2) . "</td>";
            echo "<td>$" . number_format($row['debits'], 2) . "</td>";
            echo "<td>$" . number_format($row['net'], 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "<p style='color: red;'><strong>ERROR:</strong> csv_process table does not exist!</p>";
}

// Check current hancock balance
echo "<h3>Hancock Table Balance:</h3>";
$stmt = $budget_pdo->query("SELECT SUM(credits) + SUM(debits) as total FROM hancock");
$hancock_total = $stmt->fetchColumn();
echo "<p><strong>Total:</strong> $" . number_format($hancock_total, 2) . "</p>";

// Check budget balances
echo "<h3>Budget Table Balances (Key Buckets):</h3>";
$stmt = $budget_pdo->query("
    SELECT id, budget, balance 
    FROM budget 
    WHERE id IN (23, 24, 25, 26, 36, 38) 
    ORDER BY id
");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Name</th><th>Balance</th></tr>";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['budget'] . "</td>";
    echo "<td>$" . number_format($row['balance'], 2) . "</td>";
    echo "</tr>";
}
echo "</table>";
?>
