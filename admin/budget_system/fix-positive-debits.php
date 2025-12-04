<?php
require_once 'assets/includes/admin_config.php';
$budget_pdo = pdo_connect_budget_db();

echo "<h2>Fix Positive Debits in Hancock</h2>";

// Find all positive debits
$stmt = $budget_pdo->query("SELECT COUNT(*) as count, SUM(debits) as total FROM hancock WHERE debits > 0");
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<p><strong>Records with positive debits:</strong> {$result['count']}</p>";
echo "<p><strong>Total amount:</strong> $" . number_format($result['total'], 2) . "</p>";

if ($result['count'] > 0) {
    if (!isset($_POST['execute'])) {
        echo "<p>These debits will be converted to negative values (multiplied by -1)</p>";
        echo "<form method='POST'>";
        echo "<button type='submit' name='execute' style='padding: 10px 20px; background: #dc3545; color: white; border: none; cursor: pointer;'>";
        echo "Execute - Fix {$result['count']} Records";
        echo "</button>";
        echo "</form>";
    } else {
        try {
            $budget_pdo->beginTransaction();
            
            // Update all positive debits to negative
            $stmt = $budget_pdo->exec("UPDATE hancock SET debits = debits * -1 WHERE debits > 0");
            
            $budget_pdo->commit();
            
            echo "<div style='background: #d4edda; padding: 15px; margin: 20px 0; border: 1px solid #c3e6cb;'>";
            echo "<h3 style='color: #155724;'>✓ Fixed!</h3>";
            echo "<p><strong>Updated:</strong> {$result['count']} records</p>";
            echo "<p><strong>Action:</strong> All positive debits converted to negative</p>";
            echo "<p><strong>Next:</strong> Check your report again - running balance should now subtract correctly</p>";
            echo "</div>";
            
        } catch (Exception $e) {
            $budget_pdo->rollBack();
            echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
        }
    }
} else {
    echo "<p style='color: green;'>✓ No positive debits found - all records are correct!</p>";
}
?>
