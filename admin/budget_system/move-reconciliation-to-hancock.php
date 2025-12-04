<?php
/**
 * Move Reconciliation Transaction to Hancock
 * Removes it from csv_process and adds directly to hancock
 */

require_once 'assets/includes/admin_config.php';
require_once 'assets/includes/budget_constants.php';

$budget_pdo = pdo_connect_budget_db();

echo "<h2>Move Reconciliation Transaction to Hancock</h2>";

// Find the reconciliation transaction in csv_process
$stmt = $budget_pdo->query("
    SELECT * FROM csv_process 
    WHERE description LIKE '%Reconciliation%' 
    OR budget_id = " . BUDGET_DIFFERENCE . " AND ABS(credits + debits) > 5000
    ORDER BY ABS(credits + debits) DESC
    LIMIT 5
");

echo "<h3>Reconciliation Transactions Found in csv_process:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Date</th><th>Description</th><th>Credits</th><th>Debits</th><th>Budget</th><th>Action</th></tr>";

$reconciliation_rows = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $reconciliation_rows[] = $row;
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['date'] . "</td>";
    echo "<td>" . htmlspecialchars($row['description']) . "</td>";
    echo "<td style='color: green;'>$" . number_format($row['credits'], 2) . "</td>";
    echo "<td style='color: red;'>$" . number_format($row['debits'], 2) . "</td>";
    echo "<td>" . htmlspecialchars($row['budget']) . "</td>";
    echo "<td>Will move to hancock</td>";
    echo "</tr>";
}
echo "</table>";

if (empty($reconciliation_rows)) {
    echo "<p style='color: orange;'>No reconciliation transactions found. They may have already been processed.</p>";
} else {
    echo "<p><strong>Action:</strong> These transactions will be:</p>";
    echo "<ol>";
    echo "<li>Deleted from csv_process</li>";
    echo "<li>Added directly to hancock table</li>";
    echo "<li>This removes them from the Step 2.5 workflow</li>";
    echo "</ol>";
    
    if (!isset($_POST['execute'])) {
        echo "<form method='POST'>";
        echo "<button type='submit' name='execute' style='padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer;'>";
        echo "Execute - Move to Hancock";
        echo "</button>";
        echo "</form>";
    } else {
        try {
            $budget_pdo->beginTransaction();
            
            $moved_count = 0;
            foreach ($reconciliation_rows as $row) {
                // Insert into hancock
                $stmt = $budget_pdo->prepare("
                    INSERT INTO hancock (date, description, credits, debits, budget_id, bill_id, flags_id, comment, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $comment = $row['comment'] ?? '';
                if (strlen($comment) > 50) {
                    $comment = substr($comment, 0, 47) . '...';
                }
                
                $notes = 'Reconciliation transaction moved from csv_process to hancock directly';
                
                $stmt->execute([
                    $row['date'],
                    $row['description'],
                    $row['credits'],
                    $row['debits'],
                    $row['budget_id'],
                    $row['bill_id'],
                    $row['flags_id'],
                    $comment,
                    $notes
                ]);
                
                // Delete from csv_process
                $stmt = $budget_pdo->prepare("DELETE FROM csv_process WHERE id = ?");
                $stmt->execute([$row['id']]);
                
                $moved_count++;
            }
            
            $budget_pdo->commit();
            
            echo "<div style='background: #d4edda; padding: 15px; margin: 20px 0; border: 1px solid #c3e6cb;'>";
            echo "<h3 style='color: #155724;'>✓ Success!</h3>";
            echo "<p><strong>Moved:</strong> {$moved_count} reconciliation transaction(s)</p>";
            echo "<p><strong>From:</strong> csv_process (staging)</p>";
            echo "<p><strong>To:</strong> hancock (permanent ledger)</p>";
            echo "<p><strong>Next Steps:</strong></p>";
            echo "<ol>";
            echo "<li>Run <a href='calculate-starting-balances.php'>calculate-starting-balances.php</a> again to recalculate without reconciliation</li>";
            echo "<li>Execute the new starting balances</li>";
            echo "<li>Continue through Step 2.5 with clean CSV data</li>";
            echo "</ol>";
            echo "</div>";
            
        } catch (Exception $e) {
            $budget_pdo->rollBack();
            echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
        }
    }
}

// Show remaining csv_process transactions
echo "<h3>Remaining CSV Transactions (After Move):</h3>";
$stmt = $budget_pdo->query("SELECT COUNT(*) as count, SUM(credits) as credits, SUM(debits) as debits FROM csv_process");
$summary = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<p><strong>Count:</strong> " . $summary['count'] . " transactions</p>";
echo "<p><strong>Credits:</strong> $" . number_format($summary['credits'], 2) . "</p>";
echo "<p><strong>Debits:</strong> $" . number_format($summary['debits'], 2) . "</p>";
echo "<p><strong>Net:</strong> $" . number_format($summary['credits'] + $summary['debits'], 2) . "</p>";
?>
