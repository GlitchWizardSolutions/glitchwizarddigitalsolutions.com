<?php
/**
 * STEP 2.5: Budget Reconciliation & Balance Preview
 * 
 * This page sits between auto-matching (p2) and final editing (p3).
 * Purpose: Validate bank balance, preview budget impacts, suggest redistributions
 * 
 * Flow:
 * 1. User inputs current bank balance
 * 2. System calculates expected balance from transactions
 * 3. Shows all bucket impacts with color-coded warnings
 * 4. Identifies over-funded buckets and suggests redistributions
 * 5. Detects unallocated "Left to Spend" funds and suggests moving to Savings
 * 6. Validates Minimum Balance stays at $5,000
 * 7. Blocks commit if any bucket goes negative
 */

require_once '../assets/includes/admin_config.php';
require_once 'assets/includes/budget_constants.php';
require_once 'assets/includes/budget_helpers.php';

// Connect to budget database
$budget_pdo = pdo_connect_budget_db();

// Initialize variables
$bank_balance_input = isset($_POST['bank_balance']) ? floatval($_POST['bank_balance']) : 0;
$bank_date = isset($_POST['bank_date']) ? $_POST['bank_date'] : date('Y-m-d');
$show_analysis = isset($_POST['analyze']) || isset($_POST['apply_suggestions']) || isset($_POST['save_manual']);
$redistributions = isset($_POST['redistributions']) ? $_POST['redistributions'] : [];
$apply_suggestions = isset($_POST['apply_suggestions']);

// Get current budget balances
$budget_balances = get_all_budget_balances($budget_pdo);

// Calculate last Hancock balance
$stmt = $budget_pdo->query("SELECT balance FROM hancock ORDER BY id DESC LIMIT 1");
$last_hancock_balance = $stmt ? floatval($stmt->fetchColumn()) : 0;

// Get pending transactions from csv_process
$stmt = $budget_pdo->query("SELECT 
    budget_id, 
    SUM(credits) as total_credits, 
    SUM(debits) as total_debits,
    COUNT(*) as transaction_count
FROM csv_process 
GROUP BY budget_id");
$budget_impacts = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $budget_id = $row['budget_id'];
    $net_change = $row['total_credits'] + $row['total_debits']; // debits are negative
    $budget_impacts[$budget_id] = [
        'credits' => floatval($row['total_credits']),
        'debits' => floatval($row['total_debits']),
        'net_change' => $net_change,
        'count' => $row['transaction_count']
    ];
}

// Calculate expected bank balance from transactions
$total_credits = 0;
$total_debits = 0;
foreach ($budget_impacts as $impact) {
    $total_credits += $impact['credits'];
    $total_debits += $impact['debits'];
}
$expected_balance = $last_hancock_balance + $total_credits + $total_debits;
$bank_difference = $bank_balance_input - $expected_balance;

// Get all bills for last_paid_amount lookups
$stmt = $budget_pdo->query("SELECT id, last_paid_amount, budget_id FROM bills");
$bills_data = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $bills_data[$row['id']] = [
        'last_paid_amount' => floatval($row['last_paid_amount']),
        'budget_id' => $row['budget_id']
    ];
}

// Map budget_id to last_paid_amount (for "full bucket" threshold)
$budget_targets = [];
foreach ($bills_data as $bill) {
    $budget_id = $bill['budget_id'];
    if (!isset($budget_targets[$budget_id]) || $bill['last_paid_amount'] > 0) {
        $budget_targets[$budget_id] = $bill['last_paid_amount'] * 1.10; // 10% buffer
    }
}

// Build comprehensive bucket analysis
$bucket_analysis = [];
$problems = [];
$over_target_buckets = [];
$negative_buckets = [];
$unallocated_left_to_spend = 0;

foreach ($budget_balances as $budget_id => $budget) {
    $current_balance = floatval($budget['balance']);
    $impact = isset($budget_impacts[$budget_id]) ? $budget_impacts[$budget_id]['net_change'] : 0;
    $new_balance = $current_balance + $impact;
    $target = isset($budget_targets[$budget_id]) ? $budget_targets[$budget_id] : 0;
    $over_target = ($target > 0 && $new_balance > $target) ? ($new_balance - $target) : 0;
    
    // Determine status
    $status = 'healthy';
    $status_class = 'table-success';
    $status_icon = '✓';
    
    if ($new_balance < 0) {
        $status = 'NEGATIVE';
        $status_class = 'table-danger';
        $status_icon = '⚠';
        $negative_buckets[] = $budget_id;
        $problems[] = [
            'bucket' => $budget['budget'],
            'issue' => 'Balance will be negative: $' . number_format($new_balance, 2),
            'severity' => 'critical'
        ];
    } elseif ($budget_id == BUDGET_REIMBURSEMENTS && $new_balance != MIN_BALANCE_CAP) {
        $status = 'MIN BALANCE ERROR';
        $status_class = 'table-danger';
        $status_icon = '⚠';
        $problems[] = [
            'bucket' => $budget['budget'],
            'issue' => 'Minimum Balance must be $5,000.00, currently $' . number_format($new_balance, 2),
            'severity' => 'critical'
        ];
    } elseif ($over_target > 0) {
        $status = 'Over Target';
        $status_class = 'table-warning';
        $status_icon = '↑';
        $over_target_buckets[$budget_id] = [
            'name' => $budget['budget'],
            'excess' => $over_target,
            'new_balance' => $new_balance,
            'target' => $target
        ];
    } elseif ($target > 0 && $new_balance < $target) {
        $status = 'Below Target';
        $status_class = 'table-info';
        $status_icon = '↓';
    }
    
    // Track "Remaining Balance" (ID 23) for unallocated funds
    if ($budget_id == BUDGET_NONE && $new_balance > 0) {
        $unallocated_left_to_spend = $new_balance;
    }
    
    $bucket_analysis[$budget_id] = [
        'name' => $budget['budget'],
        'current_balance' => $current_balance,
        'impact' => $impact,
        'new_balance' => $new_balance,
        'target' => $target,
        'over_target' => $over_target,
        'status' => $status,
        'status_class' => $status_class,
        'status_icon' => $status_icon,
        'monthly_amount' => floatval($budget['amount']),
        'transaction_count' => isset($budget_impacts[$budget_id]) ? $budget_impacts[$budget_id]['count'] : 0
    ];
}

// Smart redistribution suggestions
$suggestions = [];
if (!empty($negative_buckets)) {
    foreach ($negative_buckets as $neg_id) {
        $needed = abs($bucket_analysis[$neg_id]['new_balance']);
        $bucket_name = $bucket_analysis[$neg_id]['name'];
        
        // Priority 1: Pull from over-target buckets
        foreach ($over_target_buckets as $source_id => $source_data) {
            if ($needed <= 0) break;
            $available = min($source_data['excess'], $needed);
            if ($available > 0) {
                $suggestions[] = [
                    'from_id' => $source_id,
                    'from_name' => $source_data['name'],
                    'to_id' => $neg_id,
                    'to_name' => $bucket_name,
                    'amount' => $available,
                    'reason' => "Move excess from '{$source_data['name']}' (over target by $" . number_format($source_data['excess'], 2) . ")"
                ];
                $needed -= $available;
            }
        }
        
        // Priority 2: Pull from Savings
        if ($needed > 0) {
            $savings_available = $bucket_analysis[BUDGET_SAVINGS]['new_balance'];
            if ($savings_available > 0) {
                $from_savings = min($savings_available, $needed);
                $suggestions[] = [
                    'from_id' => BUDGET_SAVINGS,
                    'from_name' => $bucket_analysis[BUDGET_SAVINGS]['name'],
                    'to_id' => $neg_id,
                    'to_name' => $bucket_name,
                    'amount' => $from_savings,
                    'reason' => "Pull from Savings"
                ];
                $needed -= $from_savings;
            }
        }
        
        // Priority 3: Pull from Difference
        if ($needed > 0) {
            $difference_available = $bucket_analysis[BUDGET_DIFFERENCE]['new_balance'];
            if ($difference_available > 0) {
                $from_difference = min($difference_available, $needed);
                $suggestions[] = [
                    'from_id' => BUDGET_DIFFERENCE,
                    'from_name' => $bucket_analysis[BUDGET_DIFFERENCE]['name'],
                    'to_id' => $neg_id,
                    'to_name' => $bucket_name,
                    'amount' => $from_difference,
                    'reason' => "Pull from Difference (reconciliation buffer)"
                ];
                $needed -= $from_difference;
            }
        }
        
        // Priority 4: Flag as needs manual attention
        if ($needed > 0) {
            $problems[] = [
                'bucket' => $bucket_name,
                'issue' => "Still needs $" . number_format($needed, 2) . " - requires manual override or buffer adjustment",
                'severity' => 'warning'
            ];
        }
    }
}

// Unallocated funds suggestion
if ($unallocated_left_to_spend > 0) {
    $suggestions[] = [
        'from_id' => BUDGET_NONE,
        'from_name' => 'Remaining Balance (Unallocated)',
        'to_id' => BUDGET_SAVINGS,
        'to_name' => $bucket_analysis[BUDGET_SAVINGS]['name'],
        'amount' => $unallocated_left_to_spend,
        'reason' => "Move unallocated 'Left to Spend' funds to Savings"
    ];
}

// Apply suggestions if requested
if ($apply_suggestions && !empty($suggestions)) {
    try {
        $budget_pdo->beginTransaction();
        
        foreach ($suggestions as $suggestion) {
            $from_id = $suggestion['from_id'];
            $to_id = $suggestion['to_id'];
            $amount = $suggestion['amount'];
            
            // Update csv_process transactions to redistribute
            // Move amount from source bucket to destination bucket
            $stmt = $budget_pdo->prepare("UPDATE csv_process 
                SET budget_id = :to_id 
                WHERE budget_id = :from_id 
                AND ABS(debits) <= :amount 
                ORDER BY ABS(debits) DESC 
                LIMIT 1");
            $stmt->execute([
                'from_id' => $from_id,
                'to_id' => $to_id,
                'amount' => $amount
            ]);
        }
        
        $budget_pdo->commit();
        $_SESSION['success'] = "Applied " . count($suggestions) . " redistribution suggestions successfully.";
        header("Location: instructions-p2-5-reconcile.php");
        exit;
    } catch (Exception $e) {
        $budget_pdo->rollBack();
        $_SESSION['error'] = "Failed to apply suggestions: " . $e->getMessage();
    }
}

// Sort bucket analysis: problems first, then alphabetical
uasort($bucket_analysis, function($a, $b) {
    $priority = ['table-danger' => 1, 'table-warning' => 2, 'table-info' => 3, 'table-success' => 4];
    $a_priority = $priority[$a['status_class']] ?? 5;
    $b_priority = $priority[$b['status_class']] ?? 5;
    if ($a_priority != $b_priority) return $a_priority - $b_priority;
    return strcmp($a['name'], $b['name']);
});

?>

<?=template_admin_header('Step 2.5: Reconciliation & Preview', 'budget', 'csv-process')?>

<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">
                <i class="fas fa-balance-scale"></i> Step 2.5: Budget Reconciliation & Impact Preview
            </h5>
        </div>
        <div class="card-body">
            
            <!-- Navigation Breadcrumb -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="csv-upload-auto.php">Step 1: Upload CSV</a></li>
                    <li class="breadcrumb-item"><a href="instructions-p2.php">Step 2: Auto-Match</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Step 2.5: Reconcile</li>
                    <li class="breadcrumb-item"><a href="instructions-p3.php">Step 3: Edit & Commit</a></li>
                </ol>
            </nav>
            
            <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <!-- Section A: Bank Balance Reconciliation -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-university"></i> Bank Balance Reconciliation</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="bank_balance" class="form-label">Current Bank Balance</label>
                                <input type="number" 
                                       step="0.01" 
                                       class="form-control" 
                                       name="bank_balance" 
                                       id="bank_balance" 
                                       value="<?php echo $bank_balance_input ?>" 
                                       required>
                            </div>
                            <div class="col-md-3">
                                <label for="bank_date" class="form-label">As of Date</label>
                                <input type="date" 
                                       class="form-control" 
                                       name="bank_date" 
                                       id="bank_date" 
                                       value="<?php echo $bank_date ?>" 
                                       required>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" name="analyze" class="btn btn-primary w-100">
                                    <i class="fas fa-calculator"></i> Analyze
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <?php if ($show_analysis): ?>
                    <hr>
                    <div class="row mt-3">
                        <div class="col-md-3">
                            <strong>Last Hancock Balance:</strong><br>
                            $<?php echo number_format($last_hancock_balance, 2) ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Pending Credits:</strong><br>
                            <span class="text-success">+$<?php echo number_format($total_credits, 2) ?></span>
                        </div>
                        <div class="col-md-3">
                            <strong>Pending Debits:</strong><br>
                            <span class="text-danger">$<?php echo number_format($total_debits, 2) ?></span>
                        </div>
                        <div class="col-md-3">
                            <strong>Expected Balance:</strong><br>
                            $<?php echo number_format($expected_balance, 2) ?>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <div class="alert <?php echo abs($bank_difference) < 0.01 ? 'alert-success' : 'alert-warning' ?> mb-0">
                                <strong>Bank Difference:</strong> 
                                $<?php echo number_format($bank_difference, 2) ?>
                                <?php if (abs($bank_difference) < 0.01): ?>
                                    <i class="fas fa-check-circle"></i> Reconciled!
                                <?php else: ?>
                                    <br><small>This amount will be added to "Difference" bucket (ID <?php echo BUDGET_DIFFERENCE ?>)</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($show_analysis): ?>
            
            <!-- Section B: Critical Warnings -->
            <?php if (!empty($problems)): ?>
            <div class="card mb-4">
                <div class="card-header bg-danger text-white">
                    <h6 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Critical Issues Detected</h6>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <?php foreach ($problems as $problem): ?>
                        <li class="<?php echo $problem['severity'] == 'critical' ? 'text-danger fw-bold' : 'text-warning' ?>">
                            <strong><?php echo htmlspecialchars($problem['bucket']) ?>:</strong> 
                            <?php echo htmlspecialchars($problem['issue']) ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Section C: Smart Redistribution Suggestions -->
            <?php if (!empty($suggestions)): ?>
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="fas fa-lightbulb"></i> Smart Redistribution Suggestions</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="bank_balance" value="<?php echo $bank_balance_input ?>">
                        <input type="hidden" name="bank_date" value="<?php echo $bank_date ?>">
                        <input type="hidden" name="analyze" value="1">
                        
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>From Bucket</th>
                                    <th>To Bucket</th>
                                    <th class="text-end">Amount</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($suggestions as $idx => $suggestion): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($suggestion['from_name']) ?></td>
                                    <td><?php echo htmlspecialchars($suggestion['to_name']) ?></td>
                                    <td class="text-end">$<?php echo number_format($suggestion['amount'], 2) ?></td>
                                    <td><small><?php echo htmlspecialchars($suggestion['reason']) ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <button type="submit" name="apply_suggestions" class="btn btn-success">
                            <i class="fas fa-magic"></i> Apply All Suggestions
                        </button>
                        <small class="text-muted ms-2">This will update csv_process transactions automatically</small>
                    </form>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Section D: Budget Impact Preview Table -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-chart-line"></i> Budget Impact Preview (All Buckets)</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Status</th>
                                    <th>Budget Bucket</th>
                                    <th class="text-end">Current Balance</th>
                                    <th class="text-end">Change</th>
                                    <th class="text-end">New Balance</th>
                                    <th class="text-end">Target (110%)</th>
                                    <th class="text-end">Over Target</th>
                                    <th class="text-center">Transactions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bucket_analysis as $bucket_id => $data): ?>
                                <tr class="<?php echo $data['status_class'] ?>">
                                    <td class="text-center">
                                        <span title="<?php echo $data['status'] ?>"><?php echo $data['status_icon'] ?></span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($data['name']) ?></strong>
                                        <?php if ($data['monthly_amount'] > 0): ?>
                                        <br><small class="text-muted">Monthly: $<?php echo number_format($data['monthly_amount'], 2) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">$<?php echo number_format($data['current_balance'], 2) ?></td>
                                    <td class="text-end <?php echo $data['impact'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                        <?php echo $data['impact'] >= 0 ? '+' : '' ?>$<?php echo number_format($data['impact'], 2) ?>
                                    </td>
                                    <td class="text-end fw-bold">$<?php echo number_format($data['new_balance'], 2) ?></td>
                                    <td class="text-end">
                                        <?php echo $data['target'] > 0 ? '$' . number_format($data['target'], 2) : '-' ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($data['over_target'] > 0): ?>
                                        <span class="text-warning">$<?php echo number_format($data['over_target'], 2) ?></span>
                                        <?php else: ?>
                                        -
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo $data['transaction_count'] ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        <h6>Legend:</h6>
                        <ul class="list-unstyled">
                            <li><span class="badge bg-danger">⚠</span> CRITICAL: Negative balance or Minimum Balance error</li>
                            <li><span class="badge bg-warning text-dark">↑</span> Over Target: Bucket exceeds 110% of last_paid_amount</li>
                            <li><span class="badge bg-info">↓</span> Below Target: Bucket under 110% threshold</li>
                            <li><span class="badge bg-success">✓</span> Healthy: Balance within normal range</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Section E: Manual Redistribution Interface -->
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0"><i class="fas fa-hand-pointer"></i> Manual Redistribution (Advanced)</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        <small>
                            <i class="fas fa-info-circle"></i> 
                            Use this to manually move funds between buckets. WARNING: Moving from buckets below target or from Minimum Balance below $5,000 will trigger validation errors.
                        </small>
                    </p>
                    <form method="POST" action="">
                        <input type="hidden" name="bank_balance" value="<?php echo $bank_balance_input ?>">
                        <input type="hidden" name="bank_date" value="<?php echo $bank_date ?>">
                        <input type="hidden" name="analyze" value="1">
                        
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">From Bucket</label>
                                <select class="form-select" name="manual_from">
                                    <option value="">-- Select --</option>
                                    <?php foreach ($bucket_analysis as $id => $data): ?>
                                    <option value="<?php echo $id ?>">
                                        <?php echo htmlspecialchars($data['name']) ?> 
                                        ($<?php echo number_format($data['new_balance'], 2) ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">To Bucket</label>
                                <select class="form-select" name="manual_to">
                                    <option value="">-- Select --</option>
                                    <?php foreach ($bucket_analysis as $id => $data): ?>
                                    <option value="<?php echo $id ?>">
                                        <?php echo htmlspecialchars($data['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Amount</label>
                                <input type="number" step="0.01" class="form-control" name="manual_amount" placeholder="0.00">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" name="save_manual" class="btn btn-secondary w-100">
                                    <i class="fas fa-save"></i> Apply
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Section F: Final Validation & Continue -->
            <div class="card">
                <div class="card-header <?php echo empty($problems) && abs($bank_difference) < 0.01 ? 'bg-success text-white' : 'bg-warning text-dark' ?>">
                    <h6 class="mb-0"><i class="fas fa-check-circle"></i> Final Validation</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($problems) && abs($bank_difference) < 0.01): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <strong>All validations passed!</strong>
                        <ul class="mb-0 mt-2">
                            <li>Bank balance reconciled (difference: $<?php echo number_format($bank_difference, 2) ?>)</li>
                            <li>Minimum Balance = $<?php echo number_format(MIN_BALANCE_CAP, 2) ?> ✓</li>
                            <li>No negative buckets detected ✓</li>
                            <li>Ready to proceed to Step 3 (Edit & Commit)</li>
                        </ul>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="instructions-p2.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Step 2
                        </a>
                        <a href="instructions-p3.php" class="btn btn-success">
                            Continue to Step 3 (Edit & Commit) <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> <strong>Validation warnings detected:</strong>
                        <ul class="mb-0 mt-2">
                            <?php if (abs($bank_difference) >= 0.01): ?>
                            <li>Bank balance difference: $<?php echo number_format($bank_difference, 2) ?> (will adjust Difference bucket)</li>
                            <?php endif; ?>
                            <?php if (!empty($problems)): ?>
                            <li><?php echo count($problems) ?> bucket issue(s) need attention (see above)</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="instructions-p2.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Step 2
                        </a>
                        <?php if (!empty($negative_buckets)): ?>
                        <button class="btn btn-danger" disabled>
                            <i class="fas fa-ban"></i> Cannot Continue (Negative Buckets)
                        </button>
                        <?php else: ?>
                        <a href="instructions-p3.php" class="btn btn-warning" 
                           onclick="return confirm('Warning: You have unresolved issues. Continue anyway?')">
                            Continue to Step 3 (Edit & Commit) <i class="fas fa-arrow-right"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php endif; // End show_analysis ?>
            
        </div>
    </div>
</div>

<?=template_admin_footer()?>
