<?php
include_once 'assets/includes/admin_config.php';

// Connect to MySQL database
$budget_pdo = pdo_connect_budget_db($db_host, $db_name7, $db_user, $db_pass);
?>
<style>
    table td,table th{
        padding: 3px !important;
    }
</style>
<?php 
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
$budget=0;
$balance=0;
$update='';
$budget_in=0;
$other_in=0;
$budget_out=0;
$expense_out=0;
$total=0;
$total_in=0;
$total_out=0;
$total_reserve_out=0;
$date_start = isset($_GET['date_start']) ? $_GET['date_start'] :  date("Y-m-1") ;
$date_end = isset($_GET['date_end']) ? $_GET['date_end'] :  date("Y-m-d") ;

$records = $budget_pdo->query("SELECT r.*,c.category,c.balance from running_balance r inner join categories c on r.category_id = c.id where date(r.date_created) BETWEEN '" . $date_start . "' AND  '" . $date_end . "'")->fetchAll(PDO::FETCH_ASSOC); 
?>
<?=template_admin_header('Report', 'budget', 'manage-reports')?>
<div class='card card-primary card-outline'>
    <div class='card-header'>
        <h5 class='card-title' style='color:forestgreen'>Budgeting System</h5>
    </div>
    <div class='card-body'>
        <form id='filter-form'>
            <div class='row align-items-end'>
                <div class='form-group col-md-3'>
                    <label for='date_start'>Date Start</label>
                    <input type='date' class='form-control form-control-sm' name='date_start' value="<?php echo date('Y-m-d',strtotime($date_start)) ?>">
                </div>
                <div class="form-group col-md-3">
                    <label for="date_start">Date End</label>
                    <input type="date" class="form-control form-control-sm" name="date_end" value="<?php echo date("Y-m-d",strtotime($date_end)) ?>">
                </div>
                <div class="form-group col-md-1">
                    <button class="btn btn-flat btn-block btn-primary btn-sm"><i class="fa fa-filter"></i></button>
                </div>
                <div class="form-group col-md-1">
                    <button class="btn btn-flat btn-block btn-success btn-sm" type="button" id="printBTN"><i class="fa fa-print"></i> </button>
                </div>
            </div>
        </form>
        <hr>
        <div id="printable">
            <div>
              
                <h3 class="text-center m-0"><b style="color:forestgreen">Current Reserve Balance by Category</b>&nbsp;&nbsp;<img src="https://www.budget.glitchwizardsolutions.com/admin/reports/in.png" alt="cute piggie bank deposit image"></h3>
                <hr style="width:15%">
                <p class="text-center m-0">Date Between <b><?php echo date("M d, Y",strtotime($date_start)) ?> and <?php echo date("M d, Y",strtotime($date_end)) ?></b></p>
                <hr>
            </div>
            <table class="table table-bordered">
                 <colgroup>
                    <col width="20%">           
                    <col width="20%">                
                    <col width="20%">                
                    <col width="20%">                
                    <col width="20%">                
                </colgroup>
                <thead>
                     <tr class="bg-gray-light">
                       
                        <th class="text-center">Category</th>
                        <th class="text-center">Budget IN</th>
                        <th class="text-center">Other IN</th>
                        <th class="text-center">Expense OUT</th>
                        <th class="text-center">Current Balance</th>
                    </tr>
                </thead>
             <tbody>
                <?php if (empty($records)): ?>
                <tr>
                    <td colspan="20" style="text-align:center;">There are no records.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($records as $record): ?>
                <?php
                //Name of Category to display
                    $category_id = $record['category_id'];
                //Only this month's budgeted amount deposited
                    if($record['flags_id'] == 5   && $record['balance_type'] == 1){
                        $budget_in += $record['amount'];
                //Any reimbursements or other deposited amounts, not including the budgeted amount.
                    }else if($record['flags_id'] != 5  && $record['balance_type'] == 1){
                        $other_in += $record['amount'];
                //All expenses paid from this category totaled here.
                    }else if($record['balance_type'] == 2){
                        $expense_out-= $record['amount']; 
                    }
                    $budget_stmt = $budget_pdo->prepare("SELECT SUM(amount) as total FROM running_balance where balance_type = 1 and category_id = ?");
                    $budget_stmt->execute([$category_id]);
                    $budget = $budget_stmt->fetch(PDO::FETCH_COLUMN);
                    if ($budget === NULL) {$budget = 0;}   $total_in +=$budget;
                        $expense_stmt = $budget_pdo->prepare("SELECT SUM(amount) as total FROM running_balance where balance_type = 2 and category_id = ?");
                        $expense_stmt->execute([$category_id]);
                        $expense = $expense_stmt->fetch(PDO::FETCH_COLUMN);
                        if ($expense === NULL) {$expense = 0;} $total_out +=$expense;
                    $balance = $budget - $expense;
                        if ($balance === NULL) {$balance = 0;} $total_reserve = $total_in -$total_out;
                    $update_stmt = $budget_pdo->prepare("UPDATE categories set `balance` = ? where id = ?");
                    $update_stmt->execute([$balance, $category_id]); 
                    $total=$total_in + $total_out;
                ?>
                <tr>
                    <td><?=htmlspecialchars($record['category'], ENT_QUOTES)?></td>
                    <td><?=$budget_in?></td>
                    <td><?=$other_in?></td>
                    <td><?=$expense_out?></td>
                    <td><?=$balance?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </table>
                <tbody>
                    <?php 
                    $num_rows=0;
                    $i = 1;
                    $total = 0;
                    $total_out = 0;
                    $total_in = 0;
                    $category_out=0;
                    $category_in=0;
                        $qry = $budget_pdo->query("SELECT r.*,c.category,c.balance from `running_balance` r inner join `categories` c on r.category_id = c.id where  date(r.date_created) between '{$date_start}' and '{$date_end}' order by unix_timestamp(r.date_created) asc");
                         foreach ($qry as $row): 
                           $num_rows +=$num_rows;
                           $category_id = $row['category_id'];
                           $total=$total_in + $total_out; ?>
                    <tr>
                        <td class="text-left"><?php echo $row['category'] ?></td>
                        <td class="text-right"><?php echo number_format($budget,2) ?>&nbsp;&nbsp;&nbsp;&nbsp;</td>
                        <td class="text-right"><?php echo number_format($expense,2) ?>&nbsp;&nbsp;&nbsp;&nbsp;</td>
                        <td class="text-right"><?php echo number_format($balance,2) ?>&nbsp;&nbsp;&nbsp;&nbsp;</td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if($num_rows <= 0): ?>
                    <tr>
                        <td class="text-center" colspan="5">No Data...</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td class="text-right px-3" colspan="1"><b>Totals</b></td>
                        <td class="text-right mr-auto"><b><?php echo number_format($total_in,2) ?></b>&nbsp;&nbsp;&nbsp;&nbsp;</td>
                        <td class="text-right"><b><?php echo number_format($total,2) ?></b>&nbsp;&nbsp;&nbsp;&nbsp;</td>
                        <td class="text-right"><b><?php echo number_format($total_reserve_out,2) ?></b>&nbsp;&nbsp;&nbsp;&nbsp;</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
<script>
    $(function(){
        $('#filter-form').submit(function(e){
            e.preventDefault()
            location.href = "monthly-report.php&date_start="+$('[name="date_start"]').val()+"&date_end="+$('[name="date_end"]').val()
        })

        $('#printBTN').click(function(){
            var rep = $('#printable').clone();
            var ns = $('head').clone();
            start_loader()
            rep.prepend(ns)
            var nw = window.document.open('','_blank','width=900,height=600')
                nw.document.write(rep.html())
                nw.document.close()
                setTimeout(function(){
                    nw.print()
                    setTimeout(function(){
                        nw.close()
                        end_loader()
                    },500)
                },500)
        })
    })
</script>