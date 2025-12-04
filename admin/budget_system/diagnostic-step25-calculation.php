<?php
require_once 'assets/includes/admin_config.php';
require_once 'assets/includes/budget_constants.php';

$budget_pdo = pdo_connect_budget_db();

echo "<h2>Step 2.5 Calculation Diagnostic</h2>";

// Get current budget balances
echo "<h3>Current Budget Balances (Production Values):</h3>";
$stmt = $budget_pdo->query("SELECT id, budget, balance FROM budget ORDER BY id");
$current_balances = [];
$current_total = 0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $current_balances[$row['id']] = [
        'name' => $row['budget'],
        'balance' => floatval($row['balance'])
    ];
    $current_total += floatval($row['balance']);
}
echo "<p><strong>Total:</strong> $" . number_format($current_total, 2) . "</p>";

// Get pending transactions from csv_process
echo "<h3>Pending Transactions in csv_process:</h3>";
$stmt = $budget_pdo->query("
    SELECT budget_id, budget, COUNT(*) as count, SUM(credits) as credits, SUM(debits) as debits, SUM(credits+debits) as net
    FROM csv_process
    GROUP BY budget_id, budget
    ORDER BY budget_id
");

$impacts = [];
$total_credits = 0;
$total_debits = 0;

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Budget ID</th><th>Name</th><th>Count</th><th>Credits</th><th>Debits</th><th>Net Impact</th></tr>";

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $budget_id = $row['budget_id'];
    $credits = floatval($row['credits']);
    $debits = floatval($row['debits']);
    $net = floatval($row['net']);
    
    $impacts[$budget_id] = $net;
    $total_credits += $credits;
    $total_debits += $debits;
    
    echo "<tr>";
    echo "<td>{$budget_id}</td>";
    echo "<td>" . htmlspecialchars($row['budget']) . "</td>";
    echo "<td>{$row['count']}</td>";
    echo "<td style='color: green;'>$" . number_format($credits, 2) . "</td>";
    echo "<td style='color: red;'>$" . number_format($debits, 2) . "</td>";
    echo "<td style='font-weight: bold;'>$" . number_format($net, 2) . "</td>";
    echo "</tr>";
}

echo "</table>";
echo "<p><strong>Total Impact:</strong> Credits $" . number_format($total_credits, 2) . " + Debits $" . number_format($total_debits, 2) . " = Net $" . number_format($total_credits + $total_debits, 2) . "</p>";

// Calculate projected balances
echo "<h3>Projected Balances After Processing:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Name</th><th>Current</th><th>Impact</th><th>New Balance</th><th>Status</th></tr>";

$projected_total = 0;
$problems = [];

foreach ($current_balances as $id => $data) {
    $current = $data['balance'];
    $impact = isset($impacts[$id]) ? $impacts[$id] : 0;
    $new_balance = $current + $impact;
    $projected_total += $new_balance;
    
    $status = '✓ OK';
    $color = 'black';
    
    if ($new_balance < 0 && $id != 23) { // Remaining Balance can be negative
        $status = '❌ NEGATIVE';
        $color = 'red';
        $problems[] = $data['name'] . ': $' . number_format($new_balance, 2);
    }
    
    echo "<tr style='color: {$color};'>";
    echo "<td>{$id}</td>";
    echo "<td>" . htmlspecialchars($data['name']) . "</td>";
    echo "<td align='right'>$" . number_format($current, 2) . "</td>";
    echo "<td align='right'>$" . number_format($impact, 2) . "</td>";
    echo "<td align='right' style='font-weight: bold;'>$" . number_format($new_balance, 2) . "</td>";
    echo "<td>{$status}</td>";
    echo "</tr>";
}

echo "<tr style='font-weight: bold; background: #f0f0f0;'>";
echo "<td colspan='2'>TOTALS</td>";
echo "<td align='right'>$" . number_format($current_total, 2) . "</td>";
echo "<td align='right'>$" . number_format($total_credits + $total_debits, 2) . "</td>";
echo "<td align='right'>$" . number_format($projected_total, 2) . "</td>";
echo "<td></td>";
echo "</tr>";
echo "</table>";

echo "<h3>Summary:</h3>";
echo "<p><strong>Starting Total:</strong> $" . number_format($current_total, 2) . "</p>";
echo "<p><strong>Transaction Impact:</strong> $" . number_format($total_credits + $total_debits, 2) . "</p>";
echo "<p><strong>Projected Total:</strong> $" . number_format($projected_total, 2) . "</p>";
echo "<p><strong>Bank Balance:</strong> $11,850.65</p>";
echo "<p><strong>Gap:</strong> $" . number_format($projected_total - 11850.65, 2) . "</p>";

if (!empty($problems)) {
    echo "<h3 style='color: red;'>Problems Found:</h3>";
    echo "<ul style='color: red;'>";
    foreach ($problems as $problem) {
        echo "<li>{$problem}</li>";
    }
    echo "</ul>";
}
?>
