<?php
/**
 * Reset Budget Balances to Production Values
 * ONLY updates balance column - leaves amount column untouched
 * Does NOT touch hancock table (master transaction ledger)
 */

require_once 'assets/includes/admin_config.php';

$budget_pdo = pdo_connect_budget_db();

// Production balance values from report.txt
// These are realistic positive amounts totaling ~$11,850.65
$production_balances = [
    1 => 1281.13,    // Homeowner Insurance
    5 => 320.12,     // YouMail App
    7 => 150.00,     // Property Taxes - Florida
    8 => 2191.99,    // Auto Insurance
    9 => 15.00,      // Amazon Prime
    10 => 410.28,    // Vehicle Registrations
    11 => 141.00,    // Instacart Subscription
    12 => 380.00,    // TurboTax
    13 => 1.00,      // MessageEZ App
    14 => 290.00,    // Verizon Cellular
    15 => 26.26,     // CHEWY-Animal Supplies
    20 => 183.04,    // Brilliant Pad
    21 => 49.52,     // Starlink WiFi
    22 => 40.00,     // Microsoft 365
    23 => 959.32,    // Remaining Balance
    24 => 5000.00,   // Minimum Balance
    25 => 2.25,      // Savings
    26 => 0.00,      // Difference
    27 => 62.48,     // Home Pest Control
    28 => 15.00,     // Security Camera Sub
    29 => 15.00,     // Credit Card/Bank/ATM Fees
    30 => 20.00,     // ChatGPT4/AI
    31 => -32.38,    // Talquin Electric (negative - needs funding)
    32 => 5.00,      // Waste Pro
    33 => 214.73,    // Optician & Prescription Eyeglasses
    34 => 25.77,     // Dollar Shave Club
    35 => 70.00,     // Lucky's Barber Shop
    36 => 0.00,      // Allowance
    37 => 52.42,     // Dog Nail Trims / Vaccinations
    38 => 5.00       // Earth Breeze
];

echo "<h2>Budget Balance Reset to Production Values</h2>";
echo "<p><strong>Preview Mode</strong> - Review changes before executing</p>";
echo "<p style='color: blue;'><strong>NOTE:</strong> This will ONLY update the balance column. The amount column and hancock table will not be touched.</p>";

try {
    // Get current values
    $stmt = $budget_pdo->query("SELECT id, budget, amount, balance FROM budget ORDER BY id");
    $current = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr>
            <th>ID</th>
            <th>Name</th>
            <th>Amount<br><small>(unchanged)</small></th>
            <th>Current Balance</th>
            <th>New Balance</th>
            <th>Change</th>
          </tr>";
    
    $total_current_amount = 0;
    $total_current_balance = 0;
    $total_new_balance = 0;
    
    foreach ($current as $row) {
        $id = $row['id'];
        $amount = floatval($row['amount']); // Keep existing amount
        $current_balance = floatval($row['balance']);
        $new_balance = isset($production_balances[$id]) ? $production_balances[$id] : $current_balance;
        
        $balance_change = $new_balance - $current_balance;
        
        $total_current_amount += $amount;
        $total_current_balance += $current_balance;
        $total_new_balance += $new_balance;
        
        echo "<tr>";
        echo "<td>{$id}</td>";
        echo "<td>" . htmlspecialchars($row['budget']) . "</td>";
        echo "<td align='right'>$" . number_format($amount, 2) . "</td>";
        echo "<td align='right'>$" . number_format($current_balance, 2) . "</td>";
        echo "<td align='right'>$" . number_format($new_balance, 2) . "</td>";
        echo "<td align='right' style='color: " . ($balance_change >= 0 ? 'green' : 'red') . "'>";
        echo ($balance_change >= 0 ? '+' : '') . "$" . number_format($balance_change, 2);
        echo "</td>";
        echo "</tr>";
    }
    
    echo "<tr style='font-weight: bold; background: #f0f0f0;'>";
    echo "<td colspan='2'>TOTALS</td>";
    echo "<td align='right'>$" . number_format($total_current_amount, 2) . "</td>";
    echo "<td align='right'>$" . number_format($total_current_balance, 2) . "</td>";
    echo "<td align='right'>$" . number_format($total_new_balance, 2) . "</td>";
    echo "<td></td>";
    echo "</tr>";
    echo "</table>";
    
    echo "<h3>Validation:</h3>";
    echo "<p><strong>Amount Column Total:</strong> $" . number_format($total_current_amount, 2);
    if (abs($total_current_amount - 3500) < 1) {
        echo " <span style='color: green;'>✓ Equals $3,500 (unchanged)</span>";
    } else {
        echo " <span style='color: orange;'>⚠ Currently $" . number_format($total_current_amount - 3500, 2) . " off from $3,500</span>";
    }
    echo "</p>";
    
    echo "<p><strong>Current Balance Total:</strong> $" . number_format($total_current_balance, 2) . "</p>";
    
    echo "<p><strong>New Balance Total:</strong> $" . number_format($total_new_balance, 2);
    $bank_balance = 11850.65;
    $difference = $total_new_balance - $bank_balance;
    echo " <span style='color: " . (abs($difference) < 50 ? 'green' : 'orange') . "'>";
    echo "(Bank: $" . number_format($bank_balance, 2) . ", Difference: ";
    echo ($difference >= 0 ? '+' : '') . "$" . number_format($difference, 2) . ")";
    echo "</span></p>";
    
    echo "<form method='POST'>";
    echo "<button type='submit' name='execute_reset' class='btn btn-danger' style='padding: 10px 20px; margin: 20px 0;'>";
    echo "Execute Reset NOW - Update Balance Column Only";
    echo "</button>";
    echo "</form>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// Execute if confirmed
if (isset($_POST['execute_reset'])) {
    try {
        $budget_pdo->beginTransaction();
        
        $updated_count = 0;
        foreach ($production_balances as $id => $balance) {
            // ONLY update balance column - leave amount alone
            $stmt = $budget_pdo->prepare("UPDATE budget SET balance = ? WHERE id = ?");
            $stmt->execute([$balance, $id]);
            $updated_count++;
        }
        
        $budget_pdo->commit();
        
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
        echo "<h3 style='color: #155724; margin-top: 0;'>✓ Reset Complete!</h3>";
        echo "<p><strong>Updated:</strong> $updated_count budget balance records</p>";
        echo "<p><strong>What was changed:</strong> ONLY the balance column</p>";
        echo "<p><strong>What was NOT touched:</strong> amount column, hancock table</p>";
        echo "<p><strong>Next Steps:</strong></p>";
        echo "<ol>";
        echo "<li>Delete csv_process and csv_upload staging tables</li>";
        echo "<li>Upload your new CSV through Step 1</li>";
        echo "<li>Process through Step 2 (auto-match)</li>";
        echo "<li>Reconcile at Step 2.5 (approve bank reconciliation)</li>";
        echo "<li>Commit at Step 3</li>";
        echo "</ol>";
        echo "</div>";
        
    } catch (Exception $e) {
        $budget_pdo->rollBack();
        echo "<p style='color: red;'><strong>Error during execution:</strong> " . $e->getMessage() . "</p>";
    }
}
?>
