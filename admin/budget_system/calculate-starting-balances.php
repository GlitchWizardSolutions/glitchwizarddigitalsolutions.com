<?php
/**
 * Calculate Correct Starting Balances
 * Works backwards from current bank balance and CSV impact
 * to set balances that will be correct AFTER processing
 */

require_once 'assets/includes/admin_config.php';
require_once 'assets/includes/budget_constants.php';

$budget_pdo = pdo_connect_budget_db();

$current_bank = 11850.65; // Your actual bank balance now

echo "<h2>Calculate Starting Balances (Before CSV Processing)</h2>";
echo "<p><strong>Goal:</strong> Set budget balances so that AFTER processing csv_process, they total $" . number_format($current_bank, 2) . "</p>";

// Get CSV impact by bucket
$stmt = $budget_pdo->query("
    SELECT budget_id, budget, SUM(credits + debits) as net_impact
    FROM csv_process
    GROUP BY budget_id, budget
    ORDER BY budget_id
");

$csv_impacts = [];
$total_csv_impact = 0;

echo "<h3>CSV Transaction Impacts:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Budget ID</th><th>Name</th><th>Net Impact</th></tr>";

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $budget_id = $row['budget_id'];
    $net = floatval($row['net_impact']);
    $csv_impacts[$budget_id] = $net;
    $total_csv_impact += $net;
    
    echo "<tr>";
    echo "<td>{$budget_id}</td>";
    echo "<td>" . htmlspecialchars($row['budget']) . "</td>";
    echo "<td align='right' style='color: " . ($net >= 0 ? 'green' : 'red') . "'>$" . number_format($net, 2) . "</td>";
    echo "</tr>";
}

echo "</table>";
echo "<p><strong>Total CSV Impact:</strong> $" . number_format($total_csv_impact, 2) . "</p>";

// Calculate what starting balances should be
echo "<h3>Required Starting Balances:</h3>";
echo "<p><strong>Logic:</strong> Starting Balance = Target Balance MINUS CSV Impact</p>";

// Get your production "after" balances
$production_after_balances = [
    1 => 1281.13, 5 => 320.12, 7 => 150.00, 8 => 2191.99, 9 => 15.00,
    10 => 410.28, 11 => 141.00, 12 => 380.00, 13 => 1.00, 14 => 290.00,
    15 => 26.26, 20 => 183.04, 21 => 49.52, 22 => 40.00, 23 => 959.32,
    24 => 5000.00, 25 => 2.25, 26 => 0.00, 27 => 62.48, 28 => 15.00,
    29 => 15.00, 30 => 20.00, 31 => -32.38, 32 => 5.00, 33 => 214.73,
    34 => 25.77, 35 => 70.00, 36 => 0.00, 37 => 52.42, 38 => 5.00
];

$production_total = array_sum($production_after_balances);

echo "<p><strong>Your Production Balances Total (AFTER):</strong> $" . number_format($production_total, 2) . "</p>";
echo "<p><strong>Bank Balance:</strong> $" . number_format($current_bank, 2) . "</p>";
echo "<p><strong>Difference:</strong> $" . number_format($production_total - $current_bank, 2) . " (production is higher)</p>";

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Name</th><th>Target (After)</th><th>CSV Impact</th><th>Required (Before)</th><th>Verify</th></tr>";

$starting_balances = [];
$total_starting = 0;

$stmt = $budget_pdo->query("SELECT id, budget FROM budget ORDER BY id");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $id = $row['id'];
    $name = $row['budget'];
    $target_after = isset($production_after_balances[$id]) ? $production_after_balances[$id] : 0;
    $csv_impact = isset($csv_impacts[$id]) ? $csv_impacts[$id] : 0;
    
    // Calculate: Before = After - Impact
    $required_before = $target_after - $csv_impact;
    $starting_balances[$id] = $required_before;
    $total_starting += $required_before;
    
    // Verify: Before + Impact should = After
    $verify = $required_before + $csv_impact;
    
    echo "<tr>";
    echo "<td>{$id}</td>";
    echo "<td>" . htmlspecialchars($name) . "</td>";
    echo "<td align='right'>$" . number_format($target_after, 2) . "</td>";
    echo "<td align='right' style='color: " . ($csv_impact >= 0 ? 'green' : 'red') . "'>$" . number_format($csv_impact, 2) . "</td>";
    echo "<td align='right' style='font-weight: bold;'>$" . number_format($required_before, 2) . "</td>";
    echo "<td align='right' style='color: " . (abs($verify - $target_after) < 0.01 ? 'green' : 'red') . "'>$" . number_format($verify, 2) . "</td>";
    echo "</tr>";
}

echo "<tr style='font-weight: bold; background: #f0f0f0;'>";
echo "<td colspan='2'>TOTALS</td>";
echo "<td align='right'>$" . number_format($production_total, 2) . "</td>";
echo "<td align='right'>$" . number_format($total_csv_impact, 2) . "</td>";
echo "<td align='right'>$" . number_format($total_starting, 2) . "</td>";
echo "<td></td>";
echo "</tr>";
echo "</table>";

echo "<h3>Summary:</h3>";
echo "<p><strong>Required Starting Total:</strong> $" . number_format($total_starting, 2) . "</p>";
echo "<p><strong>CSV Will Add/Subtract:</strong> $" . number_format($total_csv_impact, 2) . "</p>";
echo "<p><strong>Final Total (After):</strong> $" . number_format($total_starting + $total_csv_impact, 2) . "</p>";
echo "<p><strong>Target (Bank):</strong> $" . number_format($current_bank, 2) . "</p>";

$gap = ($total_starting + $total_csv_impact) - $current_bank;
echo "<p><strong>Gap:</strong> $" . number_format($gap, 2);
if (abs($gap) < 0.01) {
    echo " <span style='color: green;'>✓ Perfect!</span>";
} else {
    echo " <span style='color: orange;'>⚠ Needs reconciliation adjustment of $" . number_format(-$gap, 2) . "</span>";
    echo "<br><small>This will be added to Difference bucket to make totals match bank exactly.</small>";
}
echo "</p>";

// Execute button
if (!isset($_POST['execute'])) {
    echo "<form method='POST'>";
    echo "<input type='hidden' name='apply_reconciliation' value='" . (-$gap) . "'>";
    echo "<button type='submit' name='execute' style='padding: 10px 20px; margin: 20px 0; background: #28a745; color: white; border: none; cursor: pointer;'>";
    echo "Execute - Set Starting Balances";
    if (abs($gap) >= 0.01) {
        echo " & Apply $" . number_format(abs($gap), 2) . " Reconciliation";
    }
    echo "</button>";
    echo "</form>";
} else {
    try {
        $budget_pdo->beginTransaction();
        
        // Get reconciliation amount if any
        $reconciliation = isset($_POST['apply_reconciliation']) ? floatval($_POST['apply_reconciliation']) : 0;
        
        foreach ($starting_balances as $id => $balance) {
            // Apply reconciliation to Difference bucket
            if ($id == BUDGET_DIFFERENCE && abs($reconciliation) >= 0.01) {
                $balance += $reconciliation;
            }
            
            $stmt = $budget_pdo->prepare("UPDATE budget SET balance = ? WHERE id = ?");
            $stmt->execute([$balance, $id]);
        }
        
        $budget_pdo->commit();
        
        echo "<div style='background: #d4edda; padding: 15px; margin: 20px 0; border: 1px solid #c3e6cb;'>";
        echo "<h3 style='color: #155724;'>✓ Starting Balances Set!</h3>";
        echo "<p>Budget balances have been set so they will total $" . number_format($current_bank, 2) . " AFTER processing csv_process.</p>";
        if (abs($reconciliation) >= 0.01) {
            echo "<p><strong>Reconciliation:</strong> $" . number_format(abs($reconciliation), 2) . " added to Difference bucket</p>";
        }
        echo "<p><strong>Next:</strong> Navigate to Step 2.5 and process through the workflow.</p>";
        echo "</div>";
        
    } catch (Exception $e) {
        $budget_pdo->rollBack();
        echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    }
}
?>
