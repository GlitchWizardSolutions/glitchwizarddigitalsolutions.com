<?php
require_once '../assets/includes/admin_config.php';
$budget_pdo = pdo_connect_budget_db();

echo "<h2>Hancock Recent Transactions Diagnostic</h2>";
echo "<p>Checking sign of credits and debits in hancock table</p>";

$stmt = $budget_pdo->query("
    SELECT id, date, description, credits, debits, (credits + debits) as net
    FROM hancock 
    ORDER BY id DESC 
    LIMIT 20
");

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Date</th><th>Description</th><th>Credits</th><th>Debits</th><th>Net</th><th>Issue?</th></tr>";

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $issue = '';
    if ($row['credits'] < 0) {
        $issue .= '❌ Credits should be positive or 0! ';
    }
    if ($row['debits'] > 0) {
        $issue .= '❌ Debits should be negative or 0! ';
    }
    if ($issue == '') {
        $issue = '✓ OK';
    }
    
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['date']}</td>";
    echo "<td>" . htmlspecialchars(substr($row['description'], 0, 40)) . "</td>";
    echo "<td style='color: " . ($row['credits'] >= 0 ? 'green' : 'red') . "'>$" . number_format($row['credits'], 2) . "</td>";
    echo "<td style='color: " . ($row['debits'] <= 0 ? 'red' : 'green') . "'>$" . number_format($row['debits'], 2) . "</td>";
    echo "<td>$" . number_format($row['net'], 2) . "</td>";
    echo "<td>{$issue}</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h3>Rules:</h3>";
echo "<ul>";
echo "<li>Credits should be ≥ 0 (money IN)</li>";
echo "<li>Debits should be ≤ 0 (money OUT, stored as negative)</li>";
echo "<li>Net = Credits + Debits</li>";
echo "</ul>";
?>
