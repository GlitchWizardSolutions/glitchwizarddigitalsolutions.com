<?php
include_once '../assets/includes/admin_config.php';
// Connect to MySQL database
$budget_pdo = pdo_connect_budget_db();
$conn = $budget_pdo; // Alias for queries

$date_start = isset($_GET['date_start']) ? $_GET['date_start'] : date("Y-m-1");
$date_end = isset($_GET['date_end']) ? $_GET['date_end'] : date("Y-m-t");

// Get savings and budget amounts
$savings = $conn->query("SELECT balance FROM budget WHERE id=25")->fetchColumn();
?>

<?=template_admin_header('Monthly Budget Report', 'budget', 'reports')?>

<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Monthly Budget Report - 2025 Format</h5>
        </div>
        <div class="card-body">
            <form id="filter-form" method="GET" action="">
                <div class="row align-items-end">
                    <div class="form-group col-md-3">
                        <label for="date_start">Date Start</label>
                        <input type="date" class="form-control form-control-sm" name="date_start" value="<?php echo date("Y-m-d", strtotime($date_start)) ?>">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="date_end">Date End</label>
                        <input type="date" class="form-control form-control-sm" name="date_end" value="<?php echo date("Y-m-d", strtotime($date_end)) ?>">
                    </div>
                    <div class="form-group col-md-2">
                        <button type="submit" class="btn btn-primary btn-sm btn-block">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-2">
                        <button type="button" class="btn btn-success btn-sm btn-block" id="printBTN">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                    <div class="col-md-2">
                        <a href="generate-pdf.php?date_start=<?php echo $date_start ?>&date_end=<?php echo $date_end ?>" 
                           class="btn btn-danger btn-sm btn-block" target="_blank">
                            <i class="fas fa-file-pdf"></i> PDF
                        </a>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-info btn-sm btn-block" data-bs-toggle="modal" data-bs-target="#emailModal">
                            <i class="fas fa-envelope"></i> Email
                        </button>
                    </div>
                </div>
            </form>
            
            <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php endif; ?>
            <hr>
            
            <div id="printable">
                <div class="text-center mb-4">
                    <h4 class="mb-0"><?php echo defined('SITE_NAME') ? SITE_NAME : 'Glitch Wizard Digital Solutions' ?></h4>
                    <hr style="width:15%; margin: 10px auto;">
                    <p class="mb-0">Date Between <b><?php echo date("M d, Y", strtotime($date_start)) ?></b> and <b><?php echo date("M d, Y", strtotime($date_end)) ?></b></p>
                    <hr>
                </div>

                <!-- SECTION 1: Future Expenses & Balances -->
                <h3>FUTURE EXPENSES & BALANCES</h3>
                <p>Each transaction will update the reserved balance of their respective expense as they are processed.</p>
                <table class="table table-bordered table-sm">
                    <colgroup>
                        <col width="10%">
                        <col width="10%">                
                        <col width="10%">                
                        <col width="50%">
                        <col width="10%">
                        <col width="10%">
                    </colgroup>
                    <thead>
                        <tr class="bg-light">
                            <th class="text-center">1st of Month</th>
                            <th class="text-center">Amount Reserved</th>
                            <th class="text-center">Reserved Balance</th>
                            <th class="text-center">Future Expense</th>
                            <th class="text-center">From Budget</th>
                            <th class="text-center">Running Balance</th>
                        </tr>
                        <tr>
                            <td class="text-end" colspan="6">Beginning Balance&nbsp; 3,500.00&nbsp;</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $reserved = 0;
                        $balance = 0;
                        $monthly_reserve = 0;
                        $running_balance = 3500;
                        $minimum_balance = 0;
                        
                        $qry = $conn->query("SELECT id, budget, amount, balance FROM budget WHERE monthly_reserve_flag = 1 ORDER BY budget ASC");
                        while($row = $qry->fetch(PDO::FETCH_ASSOC)):
                            $row['budget'] = stripslashes(html_entity_decode($row['budget']));
                            $reserved += $row['amount'];
                            $balance += $row['balance'];
                            $monthly_reserve += $row['amount'];
                            $running_balance -= $row['amount'];
                            if($row['id'] == 24){
                                $minimum_balance = $row['balance'];
                            }
                        ?>
                        <tr>
                            <td class="text-center"><?php echo date("m/1", strtotime($date_start)) ?></td>
                            <td class="text-end"><?php echo number_format($row['amount'], 2) ?>&nbsp;</td>
                            <td class="text-end"><?php echo number_format($row['balance'], 2) ?>&nbsp;</td>
                            <td><?php echo $row['budget'] ?></td>
                            <td class="text-end">-<?php echo number_format($row['amount'], 2) ?>&nbsp;</td>
                            <td class="text-end"><?php echo number_format($running_balance, 2) ?>&nbsp;</td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if($qry->rowCount() <= 0): ?>
                        <tr>
                            <td class="text-center" colspan="6">No Data...</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- SECTION 2: Left to Spend Report -->
                <h3>LEFT TO SPEND REPORT</h3>
                <table class="table table-bordered table-sm">
                    <colgroup>
                        <col width="10%">
                        <col width="35%">
                        <col width="35%">                
                        <col width="10%">
                        <col width="10%">
                    </colgroup>
                    <thead>
                        <tr class="bg-light">
                            <th class="text-center">Bank Date</th>
                            <th class="text-center">Bank Reference</th>
                            <th class="text-center">Description</th>
                            <th class="text-center">To Bank</th>
                            <th class="text-center">From Bank</th>
                            <th class="text-center">Running Balance</th>
                        </tr>
                        <tr>
                            <td class="text-end" colspan="6">Remaining Balance&nbsp;<?php echo number_format($running_balance, 2) ?>&nbsp;</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_amount = 0;
                        $qry = $conn->query("SELECT date, description, credits, debits, comment FROM `hancock` 
                                            WHERE (flags_id = 3 OR flags_id = 10) 
                                            AND DATE(date) BETWEEN '{$date_start}' AND '{$date_end}' 
                                            ORDER BY date ASC");
                        while($row = $qry->fetch(PDO::FETCH_ASSOC)):
                            $row['description'] = stripslashes(html_entity_decode($row['description']));
                            $row['comment'] = stripslashes(html_entity_decode($row['comment']));
                            $credits = $row['credits'];
                            $debits = $row['debits'];
                            $amount = $credits + $debits;
                            $total_amount += $amount;
                            $running_balance += $amount;
                        ?>
                        <tr>
                            <td class="text-center"><?php echo date("m/d", strtotime($row['date'])); ?></td>
                            <td><?php echo $row['description'] ?></td>
                            <td><?php echo $row['comment'] ?></td>
                            <td class="text-end"><?php echo number_format($credits, 2) ?>&nbsp;</td>
                            <td class="text-end"><?php echo number_format($debits, 2) ?>&nbsp;</td>
                            <td class="text-end"><?php echo number_format($running_balance, 2) ?>&nbsp;</td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if($qry->rowCount() <= 0): ?>
                        <tr>
                            <td class="text-center" colspan="6">No Data...</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- SECTION 3: Bills Paid from Reserved Balances -->
                <h3>BILLS PAID FROM RESERVED BALANCES</h3>
                <h6>(Subtracts from Balances, first.)</h6>
                <table class="table table-bordered table-sm">
                    <colgroup>
                        <col width="10%">
                        <col width="15%">
                        <col width="25%">                
                        <col width="10%">
                        <col width="10%">
                        <col width="10%">
                        <col width="10%">
                    </colgroup>
                    <thead>
                        <tr class="bg-light">
                            <th class="text-center">Bank Date</th>
                            <th class="text-center">Bank Reference</th>
                            <th class="text-center">Reserve Bill (Envelope)</th>
                            <th class="text-center">From Reserve Balances</th>
                            <th class="text-center">Balance Remaining</th>
                            <th class="text-center">From Bank</th>
                            <th class="text-center">Running Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $from_bank = 0.00;
                        $amc = 0;
                        $msft = 0;
                        $qry = $conn->query("SELECT h.date, h.budget_id, h.comment, h.description, h.credits, h.debits, b.bill 
                                            FROM hancock h 
                                            INNER JOIN bills b ON h.bill_id = b.id 
                                            WHERE h.flags_id = 9 
                                            AND DATE(h.date) BETWEEN '{$date_start}' AND '{$date_end}' 
                                            ORDER BY h.date ASC");
                        while($row = $qry->fetch(PDO::FETCH_ASSOC)):
                            $row['description'] = stripslashes(html_entity_decode($row['description']));
                            $row['bill'] = stripslashes(html_entity_decode($row['bill']));
                            $credits = $row['credits'];
                            $debits = $row['debits'];
                            $budget_id = $row['budget_id'];
                            
                            // Special handling for duplicate Microsoft and AMC charges
                            if($row['description'] == 'MSFT * E0500U3B' || $row['description'] == 'MSFT * E0500U38'){
                                $msft++;
                                $row['bill'] = $row['bill'] . ' (# ' . $msft . ')';
                            }
                            if($row['description'] == 'AMC 9640 ONLINE'){
                                $amc++;
                                $row['bill'] = $row['bill'] . ' (# ' . $amc . ')';
                            }
                            
                            $new_balance = $conn->query("SELECT balance FROM budget WHERE id=$budget_id")->fetchColumn();
                        ?>
                        <tr>
                            <td class="text-center"><?php echo date("m/d", strtotime($row['date'])); ?></td>
                            <td><?php echo $row['description'] ?></td>
                            <td><?php echo $row['bill'] ?></td>
                            <td class="text-end"><?php echo number_format($debits, 2) ?>&nbsp;</td>
                            <td class="text-end"><?php echo number_format($new_balance, 2) ?>&nbsp;</td>
                            <td class="text-end"><?php echo number_format($from_bank, 2) ?>&nbsp;</td>
                            <td class="text-end"><?php echo number_format($running_balance, 2) ?>&nbsp;</td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if($qry->rowCount() <= 0): ?>
                        <tr>
                            <td class="text-center" colspan="7">No Data...</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background:yellow; color:black">
                            <td colspan="6">End Budget</td>
                            <td class="text-end"><b><?php echo number_format($running_balance, 2) ?></b></td>
                        </tr>
                    </tfoot>
                </table>

                <!-- SECTION 4: Non-Budgeted Savings -->
                <h3>NON-BUDGETED SAVINGS</h3>
                <h6>(Transactions from Savings Envelope)</h6>
                <table class="table table-bordered table-sm">
                    <colgroup>
                        <col width="10%">
                        <col width="20%">
                        <col width="40%">                
                        <col width="10%">
                        <col width="10%">
                        <col width="10%">
                    </colgroup>
                    <thead>
                        <tr class="bg-light">
                            <th class="text-center">Bank Date</th>
                            <th class="text-center">Bank Reference</th>
                            <th class="text-center">Description</th>
                            <th class="text-center">Whom</th>
                            <th class="text-center">IN</th>
                            <th class="text-center">OUT</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $qry = $conn->query("SELECT date, reimbursement, description, credits, debits, comment, budget_id 
                                            FROM `hancock` 
                                            WHERE flags_id = 6 
                                            AND DATE(date) BETWEEN '{$date_start}' AND '{$date_end}' 
                                            ORDER BY date ASC");
                        while($row = $qry->fetch(PDO::FETCH_ASSOC)):
                            $row['description'] = stripslashes(html_entity_decode($row['description']));
                            $row['comment'] = stripslashes(html_entity_decode($row['comment']));
                            $credits = $row['credits'];
                            $debits = $row['debits'];
                        ?>
                        <tr>
                            <td class="text-center"><?php echo date("m/d", strtotime($row['date'])); ?></td>
                            <td><?php echo $row['description'] ?></td>
                            <td><?php echo $row['comment'] ?></td>
                            <td class="text-center"><?php echo !empty($row['reimbursement']) ? $row['reimbursement'] : 'Barbara'; ?></td>
                            <td class="text-end"><?php echo number_format($credits, 2) ?>&nbsp;</td>
                            <td class="text-end"><?php echo number_format($debits, 2) ?>&nbsp;</td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if($qry->rowCount() <= 0): ?>
                        <tr>
                            <td class="text-center" colspan="6">No Data...</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background:yellow; color:black">
                            <td colspan="5">Balance in Savings</td>
                            <td class="text-end"><b><?php echo number_format($savings, 2) ?></b></td>
                        </tr>
                    </tfoot>
                </table>

                <!-- SECTION 5: Non-Budgeted Expenses -->
                <h3>NON-BUDGETED EXPENSES</h3>
                <h6>(To be reimbursed by Mom.)</h6>
                <table class="table table-bordered table-sm">
                    <colgroup>
                        <col width="10%">
                        <col width="20%">
                        <col width="40%">                
                        <col width="10%">
                        <col width="10%">
                        <col width="10%">
                    </colgroup>
                    <thead>
                        <tr class="bg-light">
                            <th class="text-center">Bank Date</th>
                            <th class="text-center">Bank Reference</th>
                            <th class="text-center">Description</th>
                            <th class="text-center">Whom</th>
                            <th class="text-center" colspan="2">To/From<br>Minimum Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $actual_paid_amount = 0;
                        $qry = $conn->query("SELECT date, reimbursement, description, credits, debits, comment, budget_id 
                                            FROM `hancock` 
                                            WHERE flags_id = 4 
                                            AND DATE(date) BETWEEN '{$date_start}' AND '{$date_end}' 
                                            ORDER BY date ASC");
                        while($row = $qry->fetch(PDO::FETCH_ASSOC)):
                            $row['description'] = stripslashes(html_entity_decode($row['description']));
                            $row['comment'] = stripslashes(html_entity_decode($row['comment']));
                            $credits = $row['credits'];
                            $debits = $row['debits'];
                            $actual_paid_amount += $debits;
                        ?>
                        <tr>
                            <td class="text-center"><?php echo date("m/d", strtotime($row['date'])); ?></td>
                            <td><?php echo $row['description'] ?></td>
                            <td><?php echo $row['comment'] ?></td>
                            <td class="text-center"><?php echo $row['reimbursement'] ?></td>
                            <td class="text-end"><?php echo number_format($credits, 2) ?>&nbsp;</td>
                            <td class="text-end"><?php echo number_format($debits, 2) ?>&nbsp;</td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if($qry->rowCount() <= 0): ?>
                        <tr>
                            <td class="text-center" colspan="6">No Data...</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr style="font-weight:bolder; background:pink; color:black">
                            <?php 
                            $min_balance = $conn->query("SELECT balance FROM budget WHERE id=24")->fetchColumn();
                            $reimburse_due = $min_balance - 5000;
                            ?>
                            <td colspan="5">Needs Reimbursement:</td>
                            <td class="text-end"><b><?php echo number_format($reimburse_due, 2) ?></b></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Email Modal -->
<div class="modal fade" id="emailModal" tabindex="-1" aria-labelledby="emailModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="emailModalLabel">Email Budget Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="email-report.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="date_start" value="<?php echo $date_start ?>">
                    <input type="hidden" name="date_end" value="<?php echo $date_end ?>">
                    
                    <div class="form-group">
                        <label for="recipient_name">Recipient Name</label>
                        <input type="text" class="form-control" id="recipient_name" name="recipient_name" value="Mom" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="recipient_email">Recipient Email</label>
                        <input type="email" class="form-control" id="recipient_email" name="recipient_email" placeholder="mom@example.com" required>
                    </div>
                    
                    <div class="alert alert-info">
                        <small>
                            <strong>Report Period:</strong> <?php echo date("M d, Y", strtotime($date_start)) ?> to <?php echo date("M d, Y", strtotime($date_end)) ?><br>
                            The PDF will be attached to the email automatically.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Send Email
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('printBTN').addEventListener('click', function() {
        var printContent = document.getElementById('printable').cloneNode(true);
        var printWindow = window.open('', '_blank', 'width=900,height=600');
        printWindow.document.write('<html><head><title>Budget Report</title>');
        printWindow.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">');
        printWindow.document.write('<style>table td, table th { padding: 5px !important; font-size: 0.9rem; }</style>');
        printWindow.document.write('</head><body>');
        printWindow.document.write(printContent.innerHTML);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        setTimeout(function(){
            printWindow.print();
            setTimeout(function(){
                printWindow.close();
            }, 500);
        }, 500);
    });
});
</script>

<style>
    table td, table th {
        padding: 5px !important;
        font-size: 0.9rem;
    }
    @media print {
        .card {
            border: none !important;
        }
        .card-header, form, .btn {
            display: none !important;
        }
    }
</style>

<?=template_admin_footer()?>




