<?php
/**
 * Budget System Helper Functions
 * Reduces code duplication and improves efficiency
 */

require_once 'budget_constants.php';

/**
 * Get all budget balances and names in one query
 * Returns associative array: [budget_id => ['balance' => float, 'budget' => string]]
 */
function get_all_budget_balances($pdo) {
    $stmt = $pdo->prepare('SELECT id, balance, budget FROM budget');
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $balances = [];
    foreach ($results as $row) {
        $balances[$row['id']] = [
            'balance' => floatval($row['balance']),
            'budget' => $row['budget']
        ];
    }
    return $balances;
}

/**
 * Get bill details by ID
 */
function get_bill_by_id($pdo, $bill_id) {
    $stmt = $pdo->prepare('SELECT * FROM bills WHERE id = ?');
    $stmt->execute([$bill_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Calculate next due date based on frequency
 */
function calculate_next_due_date($date, $frequency, $fallback_date = null) {
    if ($frequency != 0) {
        return date('Y-m-d', strtotime($date . ' + ' . $frequency . ' days'));
    }
    return $fallback_date ?? $date;
}

/**
 * Insert a single row into csv_process table
 * Reduces duplicate INSERT code
 */
function insert_csv_process_row($pdo, $data) {
    $stmt = $pdo->prepare('INSERT INTO csv_process 
         ( 
         date,
         check_number, 
         transaction_type, 
         description, 
         debits, 
         credits,
         edited,
         reconciled, 
         comment, 
         bill_id,
         bill,
         budget_id,
         budget,
         amount,
         flags_id,
         reimbursement,
         notes,
         last_paid_amount,
         last_paid_date,
         next_due_amount,
         next_due_date,
         hancock,
         frequency,
         bills_table_updated,
         budget_updated,
         prior_balance,
         updated_balance,
         reference,
         hancock_table_match,
         rollback_next_due_date, 
         rollback_last_paid_date,  
         rollback_next_due_amount,   
         rollback_last_paid_amount   
         ) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    
    $stmt->execute([
        $data['date'],
        $data['check_number'], 
        $data['transaction_type'], 
        $data['description'], 
        $data['debits'], 
        $data['credits'],
        $data['edited'] ?? 'No',
        $data['reconciled'],    
        $data['comment'],
        $data['bill_id'],
        $data['bill'],
        $data['budget_id'],
        $data['budget_name'],
        $data['amount'],
        $data['flags_id'],
        $data['reimbursement'] ?? '',
        $data['notes'],
        $data['last_paid_amount'],                
        $data['last_paid_date'], 
        $data['next_due_amount'],
        $data['next_due_date'],
        $data['hancock'],
        $data['frequency'] ?? 0,
        $data['bills_table_updated'] ?? 'N',
        $data['budget_updated'] ?? 'No',
        $data['prior_balance'],
        $data['updated_balance'], 
        $data['reference'],
        $data['hancock_table_match'] ?? 'No',
        $data['rollback_next_due_date'],
        $data['rollback_last_paid_date'],
        $data['rollback_next_due_amount'],
        $data['rollback_last_paid_amount']
    ]);
}

/**
 * Process allowance deposit transaction (OLB XFER FROM 9)
 * Returns array of transactions to insert (1-3 rows depending on amount)
 */
function process_allowance_deposit($pdo, $row, $description, $date, $month, $year, $check_number, $transaction_type, $hancock, $reference, $budget_balances) {
    $transactions = [];
    $credits = $row['credits'];
    $debits = $row['debits'];
    
    // Exactly $3,500 - Standard monthly allowance
    if ($credits == MONTHLY_ALLOWANCE) {
        $bill = get_bill_by_id($pdo, BILL_FILL_BUDGET_BUCKETS);
        
        $transactions[] = [
            'date' => $date,
            'check_number' => $check_number,
            'transaction_type' => $transaction_type,
            'description' => $description,
            'debits' => $debits,
            'credits' => $credits,
            'edited' => 'No',
            'reconciled' => 'No',
            'comment' => $month . '/' . $year . ' Budget Deposit',
            'bill_id' => BILL_FILL_BUDGET_BUCKETS,
            'bill' => $bill['bill'],
            'budget_id' => BUDGET_FILL_BUCKETS,
            'budget_name' => 'Fill Budget Buckets',
            'amount' => 0,
            'flags_id' => FLAG_BUDGET_DEPOSIT,
            'reimbursement' => '',
            'notes' => 'Budget buckets have been loaded.',
            'last_paid_amount' => $credits,
            'last_paid_date' => $date,
            'next_due_amount' => $credits,
            'next_due_date' => calculate_next_due_date($date, $bill['frequency'], $bill['next_due_date']),
            'hancock' => $hancock,
            'frequency' => $bill['frequency'],
            'bills_table_updated' => 'No',
            'budget_updated' => 'No',
            'prior_balance' => 0,
            'updated_balance' => 0,
            'reference' => $reference,
            'hancock_table_match' => 'No',
            'rollback_next_due_date' => $bill['next_due_date'],
            'rollback_last_paid_date' => $bill['last_paid_date'],
            'rollback_next_due_amount' => $bill['next_due_amount'],
            'rollback_last_paid_amount' => $bill['last_paid_amount']
        ];
    }
    // More than $3,500 - Split between budget, min balance, and possibly savings
    elseif ($credits > MONTHLY_ALLOWANCE) {
        $bill_budget = get_bill_by_id($pdo, BILL_FILL_BUDGET_BUCKETS);
        $bill_min_balance = get_bill_by_id($pdo, BILL_MIN_BALANCE_DEPOSIT);
        
        $split_amount = $credits - MONTHLY_ALLOWANCE;
        $split_prior_balance = $budget_balances[BUDGET_REIMBURSEMENTS]['balance'];
        $split_updated_balance = $split_prior_balance + $split_amount;
        
        // Check if minimum balance bucket would exceed cap
        $remainder_amount = 0;
        if ($split_updated_balance > MIN_BALANCE_CAP) {
            $remainder_amount = $split_updated_balance - MIN_BALANCE_CAP;
            $split_updated_balance = MIN_BALANCE_CAP;
            $split_amount = $split_amount - $remainder_amount;
        }
        
        // Main deposit: $3,500
        $transactions[] = [
            'date' => $date,
            'check_number' => $check_number,
            'transaction_type' => $transaction_type,
            'description' => $description,
            'debits' => $debits,
            'credits' => MONTHLY_ALLOWANCE,
            'edited' => 'No',
            'reconciled' => 'No',
            'comment' => $month . '/' . $year . ' Budget Deposit',
            'bill_id' => BILL_FILL_BUDGET_BUCKETS,
            'bill' => $bill_budget['bill'],
            'budget_id' => BUDGET_FILL_BUCKETS,
            'budget_name' => 'Fill Budget Buckets',
            'amount' => 0,
            'flags_id' => FLAG_BUDGET_DEPOSIT,
            'reimbursement' => '',
            'notes' => 'Budget Deposit',
            'last_paid_amount' => MONTHLY_ALLOWANCE,
            'last_paid_date' => $date,
            'next_due_amount' => MONTHLY_ALLOWANCE,
            'next_due_date' => calculate_next_due_date($date, $bill_budget['frequency'], $bill_budget['next_due_date']),
            'hancock' => $hancock,
            'frequency' => $bill_budget['frequency'],
            'bills_table_updated' => 'No',
            'budget_updated' => 'No',
            'prior_balance' => 0,
            'updated_balance' => 0,
            'reference' => $reference,
            'hancock_table_match' => 'No',
            'rollback_next_due_date' => $bill_budget['next_due_date'],
            'rollback_last_paid_date' => $bill_budget['last_paid_date'],
            'rollback_next_due_amount' => $bill_budget['next_due_amount'],
            'rollback_last_paid_amount' => $bill_budget['last_paid_amount']
        ];
        
        // Split deposit: Excess to minimum balance
        $transactions[] = [
            'date' => $date,
            'check_number' => $check_number,
            'transaction_type' => $transaction_type,
            'description' => $description,
            'debits' => $debits,
            'credits' => $split_amount,
            'edited' => 'No',
            'reconciled' => 'No',
            'comment' => $month . '/' . $year . ' Min. Balance Deposit',
            'bill_id' => BILL_MIN_BALANCE_DEPOSIT,
            'bill' => $bill_min_balance['bill'],
            'budget_id' => BUDGET_REIMBURSEMENTS,
            'budget_name' => 'Reimbursements',
            'amount' => $split_amount,
            'flags_id' => FLAG_REIMBURSEMENT,
            'reimbursement' => 'Mom',
            'notes' => 'Minimum Balance Deposit',
            'last_paid_amount' => $split_amount,
            'last_paid_date' => $date,
            'next_due_amount' => 0,
            'next_due_date' => calculate_next_due_date($date, $bill_min_balance['frequency'], $bill_min_balance['next_due_date']),
            'hancock' => $hancock,
            'frequency' => $bill_min_balance['frequency'],
            'bills_table_updated' => 'No',
            'budget_updated' => 'No',
            'prior_balance' => $split_prior_balance,
            'updated_balance' => $split_updated_balance,
            'reference' => $reference,
            'hancock_table_match' => 'No',
            'rollback_next_due_date' => $bill_min_balance['next_due_date'],
            'rollback_last_paid_date' => $bill_min_balance['last_paid_date'],
            'rollback_next_due_amount' => $bill_min_balance['next_due_amount'],
            'rollback_last_paid_amount' => $bill_min_balance['last_paid_amount']
        ];
        
        // Remainder: Overflow to savings if min balance capped
        if ($remainder_amount > 0) {
            $bill_savings = get_bill_by_id($pdo, BILL_SAVINGS);
            $remainder_prior_balance = $budget_balances[BUDGET_SAVINGS]['balance'];
            $remainder_updated_balance = $remainder_prior_balance + $remainder_amount;
            
            $transactions[] = [
                'date' => $date,
                'check_number' => $check_number,
                'transaction_type' => $transaction_type,
                'description' => $description,
                'debits' => $debits,
                'credits' => $remainder_amount,
                'edited' => 'No',
                'reconciled' => 'No',
                'comment' => $month . '/' . $year . ' Min. Balance Deposit',
                'bill_id' => BILL_SAVINGS,
                'bill' => $bill_savings['bill'],
                'budget_id' => BUDGET_SAVINGS,
                'budget_name' => 'Savings',
                'amount' => $remainder_amount,
                'flags_id' => FLAG_SAVINGS,
                'reimbursement' => 'Mom',
                'notes' => 'Remainder of Deposit',
                'last_paid_amount' => $remainder_amount,
                'last_paid_date' => $date,
                'next_due_amount' => 0,
                'next_due_date' => calculate_next_due_date($date, $bill_savings['frequency'], $bill_savings['next_due_date']),
                'hancock' => $hancock,
                'frequency' => $bill_savings['frequency'],
                'bills_table_updated' => 'No',
                'budget_updated' => 'No',
                'prior_balance' => $remainder_prior_balance,
                'updated_balance' => $remainder_updated_balance,
                'reference' => $reference,
                'hancock_table_match' => 'No',
                'rollback_next_due_date' => $bill_savings['next_due_date'],
                'rollback_last_paid_date' => $bill_savings['last_paid_date'],
                'rollback_next_due_amount' => $bill_savings['next_due_amount'],
                'rollback_last_paid_amount' => $bill_savings['last_paid_amount']
            ];
        }
    }
    // Less than $3,500 - Goes to reimbursements bucket
    else {
        $bill_min_balance = get_bill_by_id($pdo, BILL_MIN_BALANCE_DEPOSIT);
        $prior_balance = $budget_balances[BUDGET_REIMBURSEMENTS]['balance'];
        $updated_balance = $prior_balance + $credits;
        
        $transactions[] = [
            'date' => $date,
            'check_number' => $check_number,
            'transaction_type' => $transaction_type,
            'description' => $description,
            'debits' => $debits,
            'credits' => $credits,
            'edited' => 'No',
            'reconciled' => 'No',
            'comment' => $month . '/' . $year . ' Budget Deposit',
            'bill_id' => BILL_MIN_BALANCE_DEPOSIT,
            'bill' => $bill_min_balance['bill'],
            'budget_id' => BUDGET_REIMBURSEMENTS,
            'budget_name' => 'Reimbursements',
            'amount' => $credits,
            'flags_id' => FLAG_REIMBURSEMENT,
            'reimbursement' => 'Mom',
            'notes' => 'Minimum Balance Deposit',
            'last_paid_amount' => $credits,
            'last_paid_date' => $date,
            'next_due_amount' => $credits,
            'next_due_date' => calculate_next_due_date($date, $bill_min_balance['frequency'], $bill_min_balance['next_due_date']),
            'hancock' => $hancock,
            'frequency' => $bill_min_balance['frequency'],
            'bills_table_updated' => 'N',
            'budget_updated' => 'M',
            'prior_balance' => $prior_balance,
            'updated_balance' => $updated_balance,
            'reference' => $reference,
            'hancock_table_match' => 'N',
            'rollback_next_due_date' => $bill_min_balance['next_due_date'],
            'rollback_last_paid_date' => $bill_min_balance['last_paid_date'],
            'rollback_next_due_amount' => $bill_min_balance['next_due_amount'],
            'rollback_last_paid_amount' => $bill_min_balance['last_paid_amount']
        ];
    }
    
    return $transactions;
}
