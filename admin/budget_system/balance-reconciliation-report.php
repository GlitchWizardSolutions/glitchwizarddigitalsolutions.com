<?php
/**
 * Balance Reconciliation Report
 * 
 * Purpose: Verify that budget bucket balances match hancock transaction history
 * 
 * Key Validations:
 * 1. Last hancock.balance should equal current bank balance
 * 2. Sum of all budget.balance should equal last hancock.balance
 * 3. Each budget bucket balance should match sum of its hancock transactions
 * 
 * This report identifies discrepancies before processing new transactions
 */

require_once 'assets/includes/admin_config.php';
require_once 'assets/includes/budget_constants.php';
require_once 'assets/includes/budget_helpers.php';

// Connect to budget database
$budget_pdo = pdo_connect_budget_db();

// Calculate current hancock balance (sum of all transactions)
$stmt = $budget_pdo->query("SELECT 
    SUM(credits) as total_credits,
    SUM(debits) as total_debits,
    COUNT(*) as total_count,
    MAX(date) as last_date,
    MAX(id) as last_id
FROM hancock");
$hancock_totals = $stmt->fetch(PDO::FETCH_ASSOC);

$total_credits = floatval($hancock_totals['total_credits']);
$total_debits = floatval($hancock_totals['total_debits']);
$hancock_balance = $total_credits + $total_debits; // debits are negative
$hancock_count = $hancock_totals['total_count'];
$hancock_date = $hancock_totals['last_date'];

// Get the last transaction description
$stmt = $budget_pdo->prepare("SELECT description FROM hancock WHERE id = ?");
$stmt->execute([$hancock_totals['last_id']]);
$hancock_desc = $stmt->fetchColumn();

// Get all budget balances
$stmt = $budget_pdo->query("SELECT id, budget, balance, amount, monthly_reserve_flag 
                           FROM budget 
                           ORDER BY id ASC");
$budgets = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_budget_balance = 0;
$budget_details = [];

foreach ($budgets as $budget_row) {
    $budget_id = $budget_row['id'];
    $budget_name = $budget_row['budget'];
    $budget_balance = floatval($budget_row['balance']);
    $budget_amount = floatval($budget_row['amount']);
    
    // Calculate what this bucket's balance SHOULD be based on hancock transactions
    $stmt = $budget_pdo->prepare("SELECT 
        SUM(credits) as total_credits,
        SUM(debits) as total_debits,
        COUNT(*) as transaction_count
    FROM hancock 
    WHERE budget_id = ?");
    $stmt->execute([$budget_id]);
    $calc = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $total_credits = floatval($calc['total_credits']);
    $total_debits = floatval($calc['total_debits']);
    $transaction_count = $calc['transaction_count'];
    $calculated_balance = $total_credits + $total_debits; // debits are negative
    
    $discrepancy = $budget_balance - $calculated_balance;
    
    $total_budget_balance += $budget_balance;
    
    $budget_details[] = [
        'id' => $budget_id,
        'name' => $budget_name,
        'current_balance' => $budget_balance,
        'calculated_balance' => $calculated_balance,
        'discrepancy' => $discrepancy,
        'transaction_count' => $transaction_count,
        'monthly_amount' => $budget_amount,
        'is_reserve' => $budget_row['monthly_reserve_flag']
    ];
}

// Calculate overall discrepancy
$overall_discrepancy = $total_budget_balance - $hancock_balance;

// Identify critical buckets
$min_balance_bucket = array_filter($budget_details, fn($b) => $b['id'] == BUDGET_REIMBURSEMENTS);
$min_balance_bucket = reset($min_balance_bucket);

$savings_bucket = array_filter($budget_details, fn($b) => $b['id'] == BUDGET_SAVINGS);
$savings_bucket = reset($savings_bucket);

$difference_bucket = array_filter($budget_details, fn($b) => $b['id'] == BUDGET_DIFFERENCE);
$difference_bucket = reset($difference_bucket);

$remaining_bucket = array_filter($budget_details, fn($b) => $b['id'] == BUDGET_NONE);
$remaining_bucket = reset($remaining_bucket);

// Sort budget details by discrepancy (largest first)
usort($budget_details, function($a, $b) {
    return abs($b['discrepancy']) <=> abs($a['discrepancy']);
});

// Get pending transactions in csv_process and csv_upload
$stmt = $budget_pdo->query("SELECT COUNT(*) FROM csv_process");
$csv_process_count = $stmt->fetchColumn();

$stmt = $budget_pdo->query("SELECT COUNT(*) FROM csv_upload");
$csv_upload_count = $stmt->fetchColumn();

?>

<?=template_admin_header('Balance Reconciliation Report', 'budget', 'reports')?>

<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-balance-scale"></i> Balance Reconciliation Report
            </h5>
        </div>
        <div class="card-body">
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                <strong>Purpose:</strong> This report verifies that your budget bucket balances match your hancock transaction history.
                Run this before processing new transactions to ensure data integrity.
            </div>
            
            <!-- Section 1: Overall Status -->
            <div class="card mb-4">
                <div class="card-header <?php echo abs($overall_discrepancy) < 0.01 ? 'bg-success text-white' : 'bg-warning text-dark' ?>">
                    <h6 class="mb-0">
                        <i class="fas fa-clipboard-check"></i> Overall Balance Status
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <small class="text-muted">Last Hancock Balance</small>
                                    <h4 class="mb-0"><?php echo number_format($hancock_balance, 2) ?></h4>
                                    <small class="text-muted">
                                        <?php echo date('m/d/Y', strtotime($hancock_date)) ?><br>
                                        <?php echo substr($hancock_desc, 0, 20) ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <small class="text-muted">Sum of Budget Balances</small>
                                    <h4 class="mb-0"><?php echo number_format($total_budget_balance, 2) ?></h4>
                                    <small class="text-muted"><?php echo count($budgets) ?> buckets</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card <?php echo abs($overall_discrepancy) < 0.01 ? 'bg-success text-white' : 'bg-danger text-white' ?>">
                                <div class="card-body text-center">
                                    <small>Overall Discrepancy</small>
                                    <h4 class="mb-0"><?php echo number_format($overall_discrepancy, 2) ?></h4>
                                    <?php if (abs($overall_discrepancy) < 0.01): ?>
                                    <small><i class="fas fa-check-circle"></i> Balanced!</small>
                                    <?php else: ?>
                                    <small><i class="fas fa-exclamation-triangle"></i> Out of Balance</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <small class="text-muted">Hancock Transactions</small>
                                    <h4 class="mb-0"><?php echo number_format($hancock_count) ?></h4>
                                    <small class="text-muted">Total records</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (abs($overall_discrepancy) >= 0.01): ?>
                    <div class="alert alert-warning mt-3 mb-0">
                        <strong><i class="fas fa-exclamation-triangle"></i> Warning:</strong>
                        Your budget bucket balances do not match your hancock transaction history.
                        This means either:
                        <ul class="mb-0 mt-2">
                            <li>Budget balances were manually adjusted outside the system</li>
                            <li>Some transactions were not properly recorded in buckets</li>
                            <li>Data corruption or missing transactions</li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Section 2: Critical Buckets -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-star"></i> Critical Buckets Status
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card <?php echo $min_balance_bucket['current_balance'] == MIN_BALANCE_CAP ? 'border-success' : 'border-danger' ?>">
                                <div class="card-body">
                                    <h6 class="card-title">Minimum Balance (ID <?php echo BUDGET_REIMBURSEMENTS ?>)</h6>
                                    <p class="mb-1">
                                        <strong>Current:</strong> $<?php echo number_format($min_balance_bucket['current_balance'], 2) ?>
                                    </p>
                                    <p class="mb-1">
                                        <strong>Required:</strong> $<?php echo number_format(MIN_BALANCE_CAP, 2) ?>
                                    </p>
                                    <?php if ($min_balance_bucket['current_balance'] == MIN_BALANCE_CAP): ?>
                                    <span class="badge bg-success"><i class="fas fa-check"></i> Correct</span>
                                    <?php else: ?>
                                    <span class="badge bg-danger"><i class="fas fa-times"></i> INCORRECT</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-primary">
                                <div class="card-body">
                                    <h6 class="card-title">Savings (ID <?php echo BUDGET_SAVINGS ?>)</h6>
                                    <p class="mb-1">
                                        <strong>Balance:</strong> $<?php echo number_format($savings_bucket['current_balance'], 2) ?>
                                    </p>
                                    <p class="mb-1">
                                        <strong>Calculated:</strong> $<?php echo number_format($savings_bucket['calculated_balance'], 2) ?>
                                    </p>
                                    <p class="mb-0">
                                        <strong>Diff:</strong> 
                                        <span class="<?php echo abs($savings_bucket['discrepancy']) < 0.01 ? 'text-success' : 'text-danger' ?>">
                                            $<?php echo number_format($savings_bucket['discrepancy'], 2) ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-secondary">
                                <div class="card-body">
                                    <h6 class="card-title">Difference (ID <?php echo BUDGET_DIFFERENCE ?>)</h6>
                                    <p class="mb-1">
                                        <strong>Balance:</strong> $<?php echo number_format($difference_bucket['current_balance'], 2) ?>
                                    </p>
                                    <p class="mb-1">
                                        <strong>Purpose:</strong> Reconciliation buffer
                                    </p>
                                    <p class="mb-0">
                                        <small class="text-muted">Holds bank balance differences</small>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-warning">
                                <div class="card-body">
                                    <h6 class="card-title">Remaining Balance (ID <?php echo BUDGET_NONE ?>)</h6>
                                    <p class="mb-1">
                                        <strong>Balance:</strong> $<?php echo number_format($remaining_bucket['current_balance'], 2) ?>
                                    </p>
                                    <p class="mb-1">
                                        <strong>Monthly:</strong> $<?php echo number_format($remaining_bucket['monthly_amount'], 2) ?>
                                    </p>
                                    <p class="mb-0">
                                        <small class="text-muted">Unallocated "Left to Spend"</small>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Section 3: Pending Staging Transactions -->
            <?php if ($csv_process_count > 0 || $csv_upload_count > 0): ?>
            <div class="alert alert-warning">
                <strong><i class="fas fa-exclamation-circle"></i> Warning: Pending Transactions Detected</strong>
                <ul class="mb-0 mt-2">
                    <?php if ($csv_upload_count > 0): ?>
                    <li><strong><?php echo $csv_upload_count ?></strong> transactions in csv_upload table (awaiting Step 2 processing)</li>
                    <?php endif; ?>
                    <?php if ($csv_process_count > 0): ?>
                    <li><strong><?php echo $csv_process_count ?></strong> transactions in csv_process table (awaiting Step 3 commit)</li>
                    <?php endif; ?>
                </ul>
                <p class="mb-0 mt-2">
                    <small>
                        These pending transactions are NOT included in the reconciliation above. 
                        Complete or clear them before running this report for accurate results.
                    </small>
                </p>
            </div>
            <?php endif; ?>
            
            <!-- Section 4: Detailed Budget Bucket Analysis -->
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-list"></i> Detailed Budget Bucket Analysis
                        <small class="float-end">Sorted by Discrepancy (Largest First)</small>
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Budget Bucket</th>
                                    <th class="text-end">Current Balance</th>
                                    <th class="text-end">Calculated Balance<br><small>(from hancock)</small></th>
                                    <th class="text-end">Discrepancy</th>
                                    <th class="text-center">Transactions</th>
                                    <th class="text-end">Monthly Allocation</th>
                                    <th class="text-center">Reserve?</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($budget_details as $bucket): ?>
                                <tr class="<?php 
                                    if (abs($bucket['discrepancy']) >= 100) echo 'table-danger';
                                    elseif (abs($bucket['discrepancy']) >= 10) echo 'table-warning';
                                    elseif (abs($bucket['discrepancy']) >= 0.01) echo 'table-info';
                                    else echo 'table-success';
                                ?>">
                                    <td><?php echo $bucket['id'] ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($bucket['name']) ?></strong>
                                        <?php if ($bucket['id'] == BUDGET_REIMBURSEMENTS): ?>
                                        <span class="badge bg-danger">MIN BALANCE</span>
                                        <?php elseif ($bucket['id'] == BUDGET_SAVINGS): ?>
                                        <span class="badge bg-success">SAVINGS</span>
                                        <?php elseif ($bucket['id'] == 26): ?>
                                        <span class="badge bg-secondary">DIFFERENCE</span>
                                        <?php elseif ($bucket['id'] == BUDGET_NONE): ?>
                                        <span class="badge bg-warning text-dark">REMAINING</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">$<?php echo number_format($bucket['current_balance'], 2) ?></td>
                                    <td class="text-end">$<?php echo number_format($bucket['calculated_balance'], 2) ?></td>
                                    <td class="text-end">
                                        <strong class="<?php echo abs($bucket['discrepancy']) < 0.01 ? 'text-success' : 'text-danger' ?>">
                                            <?php if ($bucket['discrepancy'] > 0) echo '+'; ?>
                                            $<?php echo number_format($bucket['discrepancy'], 2) ?>
                                        </strong>
                                    </td>
                                    <td class="text-center"><?php echo number_format($bucket['transaction_count']) ?></td>
                                    <td class="text-end">
                                        <?php if ($bucket['monthly_amount'] > 0): ?>
                                            $<?php echo number_format($bucket['monthly_amount'], 2) ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($bucket['is_reserve']): ?>
                                        <i class="fas fa-check text-success"></i>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-secondary">
                                <tr>
                                    <th colspan="2">TOTALS</th>
                                    <th class="text-end">$<?php echo number_format($total_budget_balance, 2) ?></th>
                                    <th class="text-end">$<?php echo number_format($hancock_balance, 2) ?></th>
                                    <th class="text-end">
                                        <strong class="<?php echo abs($overall_discrepancy) < 0.01 ? 'text-success' : 'text-danger' ?>">
                                            <?php if ($overall_discrepancy > 0) echo '+'; ?>
                                            $<?php echo number_format($overall_discrepancy, 2) ?>
                                        </strong>
                                    </th>
                                    <th class="text-center"><?php echo number_format($hancock_count) ?></th>
                                    <th colspan="2"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Section 5: Recommendations -->
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-lightbulb"></i> Recommendations
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (abs($overall_discrepancy) < 0.01): ?>
                    <div class="alert alert-success mb-0">
                        <h6><i class="fas fa-check-circle"></i> System is Balanced!</h6>
                        <p class="mb-0">
                            Your budget bucket balances match your hancock transaction history perfectly.
                            You can safely proceed with processing new transactions.
                        </p>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-warning">
                        <h6><i class="fas fa-wrench"></i> Action Required</h6>
                        <p>Your budget balances are out of sync by <strong>$<?php echo number_format(abs($overall_discrepancy), 2) ?></strong>.</p>
                        
                        <p><strong>Options to fix:</strong></p>
                        <ol>
                            <li>
                                <strong>Rebuild Budget Balances (Recommended):</strong><br>
                                Recalculate all budget bucket balances from hancock transactions (source of truth).
                                This will make "Current Balance" match "Calculated Balance" for all buckets.
                                <br><a href="balance-rebuild.php" class="btn btn-warning btn-sm mt-2">
                                    <i class="fas fa-sync"></i> Rebuild Budget Balances
                                </a>
                            </li>
                            <li>
                                <strong>Manual Adjustment:</strong><br>
                                Review each bucket's discrepancy above and manually adjust in the budget table.
                                <br><small class="text-muted">Not recommended - error-prone and time-consuming</small>
                            </li>
                            <li>
                                <strong>Accept Current State:</strong><br>
                                Proceed with current balances and let future transactions correct over time.
                                <br><small class="text-danger">Warning: May perpetuate errors in reports</small>
                            </li>
                        </ol>
                    </div>
                    
                    <div class="alert alert-info mb-0">
                        <strong><i class="fas fa-info-circle"></i> What causes discrepancies?</strong>
                        <ul class="mb-0">
                            <li>Manual balance adjustments outside the system</li>
                            <li>Transactions with incorrect budget_id assignments</li>
                            <li>Deleted transactions not reflected in buckets</li>
                            <li>Data migration or import errors</li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Navigation -->
            <div class="mt-4 text-center">
                <a href="bs_dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <?php if (abs($overall_discrepancy) < 0.01): ?>
                <a href="csv-upload-auto.php" class="btn btn-success">
                    <i class="fas fa-upload"></i> Proceed to Upload CSV
                </a>
                <?php endif; ?>
            </div>
            
        </div>
    </div>
</div>

<style>
.table th, .table td {
    vertical-align: middle;
}
.card {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style>

<?=template_admin_footer()?>
