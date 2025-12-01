<?php
/**
 * Balance Rebuild & Cleanup Script
 * 
 * ONE-TIME USE ONLY
 * 
 * This script fixes data integrity issues and rebuilds budget balances:
 * 1. Fixes bill_id 36 "Budget Load" to have correct budget_id = 36
 * 2. Moves misassigned allowance deposits from budget_id 38 → 36
 * 3. Recalculates all budget balances from corrected hancock transactions
 * 4. Sets Minimum Balance (ID 24) to $5,000 (hard override)
 * 
 * Run this ONCE, then use normal workflow going forward.
 */

require_once 'assets/includes/admin_config.php';
require_once 'assets/includes/budget_constants.php';

// Connect to budget database
$budget_pdo = pdo_connect_budget_db();

$preview_mode = !isset($_POST['confirm_execute']);
$errors = [];
$stats = [
    'bills_updated' => 0,
    'hancock_updated' => 0,
    'budgets_updated' => 0
];

// Get current state before changes
$current_state = [];

// Check bill_id 36 current state
$stmt = $budget_pdo->query("SELECT id, bill, budget_id FROM bills WHERE id = 36");
$bill_36 = $stmt->fetch(PDO::FETCH_ASSOC);
$current_state['bill_36'] = $bill_36;

// Check misassigned allowance deposits
$stmt = $budget_pdo->query("SELECT COUNT(*) as count, SUM(credits) as total 
    FROM hancock 
    WHERE description = 'OLB XFER FROM 9' 
    AND credits = 3500.00 
    AND budget_id = 38");
$misassigned = $stmt->fetch(PDO::FETCH_ASSOC);
$current_state['misassigned'] = $misassigned;

// Get current budget balances
$stmt = $budget_pdo->query("SELECT id, budget, balance FROM budget ORDER BY id");
$current_balances = $stmt->fetchAll(PDO::FETCH_ASSOC);
$current_state['budget_balances'] = $current_balances;

// Calculate what balances SHOULD be after fixes
$projected_balances = [];

if (!$preview_mode && isset($_POST['confirm_execute'])) {
    try {
        $budget_pdo->beginTransaction();
        
        // STEP 1: Fix bills table - bill_id 36 should have budget_id = 36
        $stmt = $budget_pdo->prepare("UPDATE bills SET budget_id = ? WHERE id = ?");
        $stmt->execute([36, 36]);
        $stats['bills_updated'] = $stmt->rowCount();
        
        // STEP 2: Fix hancock table - move allowance deposits from budget_id 38 → 36
        $stmt = $budget_pdo->prepare("UPDATE hancock 
            SET budget_id = ? 
            WHERE description = 'OLB XFER FROM 9' 
            AND credits = 3500.00 
            AND budget_id = 38");
        $stmt->execute([36]);
        $stats['hancock_updated'] = $stmt->rowCount();
        
        // STEP 3: Recalculate all budget balances from hancock transactions
        $stmt = $budget_pdo->query("SELECT id FROM budget");
        $budget_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($budget_ids as $budget_id) {
            // Calculate correct balance from hancock transactions
            $stmt = $budget_pdo->prepare("SELECT 
                COALESCE(SUM(credits), 0) + COALESCE(SUM(debits), 0) as calculated_balance
                FROM hancock 
                WHERE budget_id = ?");
            $stmt->execute([$budget_id]);
            $calc = $stmt->fetch(PDO::FETCH_ASSOC);
            $calculated_balance = floatval($calc['calculated_balance']);
            
            // Special case: Minimum Balance (ID 24) always stays at $5,000
            if ($budget_id == BUDGET_REIMBURSEMENTS) {
                $calculated_balance = MIN_BALANCE_CAP;
            }
            
            // Update budget balance
            $stmt = $budget_pdo->prepare("UPDATE budget SET balance = ? WHERE id = ?");
            $stmt->execute([$calculated_balance, $budget_id]);
            $stats['budgets_updated']++;
            
            $projected_balances[$budget_id] = $calculated_balance;
        }
        
        $budget_pdo->commit();
        $success = true;
        
    } catch (Exception $e) {
        $budget_pdo->rollBack();
        $errors[] = 'Database error: ' . $e->getMessage();
        $success = false;
    }
} else {
    // PREVIEW MODE - Calculate what balances WOULD be
    $stmt = $budget_pdo->query("SELECT id FROM budget");
    $budget_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($budget_ids as $budget_id) {
        // Simulate the fix: calculate balance as if allowance deposits were in budget_id 36
        $stmt = $budget_pdo->prepare("SELECT 
            COALESCE(SUM(credits), 0) + COALESCE(SUM(debits), 0) as calculated_balance
            FROM hancock 
            WHERE budget_id = ?");
        $stmt->execute([$budget_id]);
        $calc = $stmt->fetch(PDO::FETCH_ASSOC);
        $calculated_balance = floatval($calc['calculated_balance']);
        
        // Add the $28,000 to budget_id 36 (simulating the move)
        if ($budget_id == 36) {
            $calculated_balance += 28000.00;
        }
        // Subtract the $28,000 from budget_id 38 (simulating the move)
        if ($budget_id == 38) {
            $calculated_balance -= 28000.00;
        }
        
        // Special case: Minimum Balance always $5,000
        if ($budget_id == BUDGET_REIMBURSEMENTS) {
            $calculated_balance = MIN_BALANCE_CAP;
        }
        
        $projected_balances[$budget_id] = $calculated_balance;
    }
}

// Get updated balances after execution (if not preview)
$new_balances = [];
if (!$preview_mode) {
    $stmt = $budget_pdo->query("SELECT id, budget, balance FROM budget ORDER BY id");
    $new_balances = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Calculate new total hancock balance
$stmt = $budget_pdo->query("SELECT 
    COALESCE(SUM(credits), 0) + COALESCE(SUM(debits), 0) as total
    FROM hancock");
$hancock_total = floatval($stmt->fetchColumn());

// Calculate new total budget balances
$total_budget_balance = 0;
if (!$preview_mode && !empty($new_balances)) {
    foreach ($new_balances as $b) {
        $total_budget_balance += floatval($b['balance']);
    }
} else {
    foreach ($projected_balances as $bal) {
        $total_budget_balance += $bal;
    }
}

$new_discrepancy = $total_budget_balance - $hancock_total;

?>

<?=template_admin_header('Balance Rebuild & Cleanup', 'budget', 'admin')?>

<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-header <?php echo $preview_mode ? 'bg-warning text-dark' : ($success ? 'bg-success text-white' : 'bg-danger text-white') ?>">
            <h5 class="mb-0">
                <i class="fas fa-tools"></i> 
                <?php if ($preview_mode): ?>
                    PREVIEW MODE - Balance Rebuild & Cleanup
                <?php elseif ($success): ?>
                    ✓ Cleanup Complete!
                <?php else: ?>
                    ✗ Cleanup Failed
                <?php endif; ?>
            </h5>
        </div>
        <div class="card-body">
            
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <h6><i class="fas fa-exclamation-triangle"></i> Errors Occurred:</h6>
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <?php if ($preview_mode): ?>
            <div class="alert alert-warning">
                <h6><i class="fas fa-eye"></i> Preview Mode</h6>
                <p class="mb-0">
                    This shows what WILL happen when you execute the cleanup. 
                    No changes have been made yet. Review carefully before proceeding.
                </p>
            </div>
            <?php elseif ($success): ?>
            <div class="alert alert-success">
                <h6><i class="fas fa-check-circle"></i> Success!</h6>
                <p class="mb-0">
                    Database cleanup completed successfully. Your budget balances are now in sync with hancock transactions.
                </p>
            </div>
            <?php endif; ?>
            
            <!-- Section 1: Issues Found & Fixed -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-bug"></i> Issues Found & 
                        <?php echo $preview_mode ? 'To Be Fixed' : 'Fixed' ?>
                    </h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>Issue</th>
                                <th>Current State</th>
                                <th><?php echo $preview_mode ? 'Will Be' : 'Fixed To' ?></th>
                                <th>Impact</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="<?php echo $preview_mode ? 'table-warning' : 'table-success' ?>">
                                <td>
                                    <strong>Bill ID 36 Configuration</strong><br>
                                    <small>Budget Load / Allowance</small>
                                </td>
                                <td>
                                    budget_id = <strong><?php echo $bill_36['budget_id'] ?></strong><br>
                                    <small class="text-danger">Wrong! Should be 36</small>
                                </td>
                                <td>
                                    budget_id = <strong>36</strong><br>
                                    <small class="text-success">Correct</small>
                                </td>
                                <td>
                                    Prevents future misassignments
                                </td>
                            </tr>
                            <tr class="<?php echo $preview_mode ? 'table-warning' : 'table-success' ?>">
                                <td>
                                    <strong>Misassigned Allowance Deposits</strong><br>
                                    <small>OLB XFER FROM 9 ($3,500)</small>
                                </td>
                                <td>
                                    <strong><?php echo $misassigned['count'] ?> transactions</strong><br>
                                    Total: $<?php echo number_format($misassigned['total'], 2) ?><br>
                                    <small class="text-danger">In budget_id 38 (Earth Breeze)</small>
                                </td>
                                <td>
                                    <strong><?php echo $misassigned['count'] ?> transactions</strong><br>
                                    Total: $<?php echo number_format($misassigned['total'], 2) ?><br>
                                    <small class="text-success">Moved to budget_id 36 (Allowance)</small>
                                </td>
                                <td>
                                    <?php if (!$preview_mode): ?>
                                    <?php echo $stats['hancock_updated'] ?> records updated
                                    <?php else: ?>
                                    Will update <?php echo $misassigned['count'] ?> records
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Section 2: Overall Balance Status -->
            <div class="card mb-4">
                <div class="card-header <?php echo abs($new_discrepancy) < 0.01 ? 'bg-success text-white' : 'bg-warning text-dark' ?>">
                    <h6 class="mb-0">
                        <i class="fas fa-balance-scale"></i> 
                        <?php echo $preview_mode ? 'Projected' : 'New' ?> Balance Status
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <small class="text-muted">Hancock Total Balance</small>
                                    <h4 class="mb-0">$<?php echo number_format($hancock_total, 2) ?></h4>
                                    <small class="text-muted">Sum of all transactions</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <small class="text-muted"><?php echo $preview_mode ? 'Projected' : 'New' ?> Budget Total</small>
                                    <h4 class="mb-0">$<?php echo number_format($total_budget_balance, 2) ?></h4>
                                    <small class="text-muted">Sum of all bucket balances</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card <?php echo abs($new_discrepancy) < 0.01 ? 'bg-success text-white' : 'bg-danger text-white' ?>">
                                <div class="card-body text-center">
                                    <small><?php echo $preview_mode ? 'Projected' : 'New' ?> Discrepancy</small>
                                    <h4 class="mb-0">$<?php echo number_format($new_discrepancy, 2) ?></h4>
                                    <?php if (abs($new_discrepancy) < 0.01): ?>
                                    <small><i class="fas fa-check-circle"></i> Balanced!</small>
                                    <?php else: ?>
                                    <small><i class="fas fa-exclamation-triangle"></i> Still Out of Balance</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Section 3: Budget Balance Changes -->
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-exchange-alt"></i> 
                        Budget Balance Changes (Current → <?php echo $preview_mode ? 'Projected' : 'New' ?>)
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Budget Name</th>
                                    <th class="text-end">Current Balance</th>
                                    <th class="text-end"><?php echo $preview_mode ? 'Projected' : 'New' ?> Balance</th>
                                    <th class="text-end">Change</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $balances_to_show = $preview_mode ? $current_balances : $new_balances;
                                foreach ($balances_to_show as $budget): 
                                    $budget_id = $budget['id'];
                                    $current_bal = floatval($budget['balance']);
                                    $new_bal = $projected_balances[$budget_id] ?? $current_bal;
                                    if (!$preview_mode && !empty($new_balances)) {
                                        $new_bal = floatval($budget['balance']);
                                    }
                                    $change = $new_bal - $current_bal;
                                    $changed = abs($change) >= 0.01;
                                ?>
                                <tr class="<?php echo $changed ? 'table-warning' : '' ?>">
                                    <td><?php echo $budget_id ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($budget['budget']) ?></strong>
                                        <?php if ($budget_id == 38): ?>
                                        <span class="badge bg-danger">FIXED</span>
                                        <?php elseif ($budget_id == 36): ?>
                                        <span class="badge bg-success">FIXED</span>
                                        <?php elseif ($budget_id == BUDGET_REIMBURSEMENTS): ?>
                                        <span class="badge bg-info">MIN BALANCE</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">$<?php echo number_format($current_bal, 2) ?></td>
                                    <td class="text-end">
                                        <strong>$<?php echo number_format($new_bal, 2) ?></strong>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($changed): ?>
                                        <span class="<?php echo $change > 0 ? 'text-success' : 'text-danger' ?>">
                                            <?php echo $change > 0 ? '+' : '' ?>$<?php echo number_format($change, 2) ?>
                                        </span>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!$changed): ?>
                                        <span class="text-muted">No change</span>
                                        <?php elseif ($budget_id == 38): ?>
                                        <span class="badge bg-success">$28k removed</span>
                                        <?php elseif ($budget_id == 36): ?>
                                        <span class="badge bg-success">$28k added</span>
                                        <?php else: ?>
                                        <span class="badge bg-info">Recalculated</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Section 4: Execution Stats -->
            <?php if (!$preview_mode): ?>
            <div class="card mb-4">
                <div class="card-header bg-dark text-white">
                    <h6 class="mb-0"><i class="fas fa-chart-bar"></i> Execution Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h3><?php echo $stats['bills_updated'] ?></h3>
                                    <small>Bills Records Updated</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h3><?php echo $stats['hancock_updated'] ?></h3>
                                    <small>Hancock Transactions Fixed</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h3><?php echo $stats['budgets_updated'] ?></h3>
                                    <small>Budget Balances Recalculated</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Section 5: Actions -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-cog"></i> Actions</h6>
                </div>
                <div class="card-body">
                    <?php if ($preview_mode): ?>
                    <div class="alert alert-warning">
                        <h6><i class="fas fa-exclamation-triangle"></i> WARNING</h6>
                        <p>This will modify your database:</p>
                        <ul>
                            <li>Update 1 record in <code>bills</code> table</li>
                            <li>Update <?php echo $misassigned['count'] ?> records in <code>hancock</code> table</li>
                            <li>Recalculate all balances in <code>budget</code> table</li>
                        </ul>
                        <p class="mb-0">
                            <strong>Recommendation:</strong> Backup your database before proceeding.
                        </p>
                    </div>
                    
                    <form method="POST" onsubmit="return confirm('Are you sure you want to execute the cleanup? This will modify your database.');">
                        <div class="d-flex gap-2">
                            <a href="balance-reconciliation-report.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Report
                            </a>
                            <button type="submit" name="confirm_execute" value="1" class="btn btn-danger">
                                <i class="fas fa-bolt"></i> Execute Cleanup NOW
                            </button>
                        </div>
                    </form>
                    
                    <?php else: ?>
                    
                    <div class="alert alert-success mb-3">
                        <h6><i class="fas fa-check-circle"></i> Cleanup Complete!</h6>
                        <p class="mb-0">
                            Your budget system is now in sync. You can proceed with normal operations.
                        </p>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <a href="balance-reconciliation-report.php" class="btn btn-primary">
                            <i class="fas fa-chart-line"></i> View Updated Report
                        </a>
                        <a href="csv-upload-auto.php" class="btn btn-success">
                            <i class="fas fa-upload"></i> Upload CSV & Continue
                        </a>
                        <a href="bs_dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </div>
                    
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
    </div>
</div>

<style>
.d-flex.gap-2 {
    gap: 0.5rem;
}
</style>

<?=template_admin_footer()?>
