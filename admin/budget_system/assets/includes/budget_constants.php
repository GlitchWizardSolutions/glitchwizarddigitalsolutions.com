<?php
/**
 * Budget System Constants
 * Centralizes all hardcoded IDs for bills, budgets, and flags
 */

// ==================== BILL IDs ====================
const BILL_NONE = 25;                      // "None" - Uncategorized/blank description
const BILL_FILL_BUDGET_BUCKETS = 36;       // Monthly allowance deposit ($3,500)
const BILL_SAVINGS = 43;                   // Savings overflow from deposits
const BILL_MIN_BALANCE_DEPOSIT = 44;       // Minimum balance/reimbursement deposits

// ==================== BUDGET IDs ====================
const BUDGET_NONE = 23;                    // "Remaining Balance" - Leftover from $3,500 after allocations
const BUDGET_REIMBURSEMENTS = 24;          // "Minimum Balance" - Hard $5,000 cap, never reduce
const BUDGET_SAVINGS = 25;                 // "Savings" - Overflow and unallocated funds
const BUDGET_DIFFERENCE = 26;              // "Difference" - Bank reconciliation buffer
const BUDGET_FILL_BUCKETS = 36;            // "Allowance" - Monthly deposit marker

// ==================== FLAG IDs ====================
const FLAG_LEFT_TO_SPEND = 3;              // Unbudgeted spending / remaining balance
const FLAG_REIMBURSEMENT = 4;              // Reimbursement from Mom
const FLAG_BUDGET_DEPOSIT = 5;             // Monthly $3,500 allowance deposit
const FLAG_SAVINGS = 6;                    // Savings transactions
const FLAG_RESERVED_BILLS = 9;             // Reserved/budgeted bills

// ==================== SPECIAL AMOUNTS ====================
const MONTHLY_ALLOWANCE = 3500.00;         // Standard monthly budget
const MIN_BALANCE_CAP = 5000.00;           // Max for reimbursements bucket

// ==================== SPECIAL DESCRIPTIONS ====================
const DESC_ALLOWANCE_DEPOSIT = 'OLB XFER FROM 9';  // Mom's monthly deposit
const DESC_MATCH_LENGTH = 15;              // Character length for description matching
