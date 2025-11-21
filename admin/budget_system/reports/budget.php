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
$date_start = isset($_GET['date_start']) ? $_GET['date_start'] :  date("Y-m-1") ;
$date_end = isset($_GET['date_end']) ? $_GET['date_end'] :  date("Y-m-d") ;
?>
<div class="card card-primary card-outline">
    <div class="card-header">
        <h5 class="card-title" style="color:forestgreen">Every Dollar Has a Job</h5>
    </div>
    <div class="card-body">
        <form id="filter-form">
            <div class="row align-items-end">
                <div class="form-group col-md-3">
                    <label for="date_start">Date Start</label>
                    <input type="date" class="form-control form-control-sm" name="date_start" value="<?php echo date("Y-m-d",strtotime($date_start)) ?>">
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
              
                <h3 class="text-center m-0"><b style="color:forestgreen">Base Budget by Category this Month</b>&nbsp;&nbsp;<img src="https://www.budget.glitchwizardsolutions.com/admin/reports/in.png" alt="cute piggie bank deposit image"></h3>
                <hr style="width:15%">
                <p class="text-center m-0">Date Between <b><?php echo date("M d, Y",strtotime($date_start)) ?> and <?php echo date("M d, Y",strtotime($date_end)) ?></b></p>
                <hr>
            </div>
            <table class="table table-bordered">
                 <colgroup>
                               
                    <col width="20%">                
                    <col width="20%">                
                    <col width="15%">                
                    <col width="45%">                
                </colgroup>
                <thead>
                     <tr class="bg-gray-light">
                        <th class="text-center">Date Entered</th>
                        <th class="text-center">Category</th>
                        <th class="text-center">Amount</th>
                        <th class="text-center">Note</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $i = 1;
                    $total = 0;
                        $qry = $budget_pdo->query("SELECT r.*,c.category,c.balance from `running_balance` r inner join `categories` c on r.category_id = c.id where  r.balance_type = 1 and r.flags_id = 5 and date(r.date_created) between '{$date_start}' and '{$date_end}' order by unix_timestamp(r.date_created) asc");
                        while($row = $qry->fetchAll(PDO::FETCH_ASSOC)):
                            $row['description'] = (stripslashes(html_entity_decode($row['description']??'')));
                            $total += $row['amount'];
                    ?>
                    <tr>
                        <td class="text-center"><?php echo date("M d, Y",strtotime($row['date_created'])) ?></td>
                        <td class="text-center"><?php echo $row['category'] ?></td>
                        <td class="text-right"><?php echo number_format($row['amount'],2) ?>&nbsp;&nbsp;&nbsp;&nbsp;</td>
                        <td><div><?php echo $row['description'] ?></div></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if($qry->num_rows <= 0): ?>
                    <tr>
                        <td class="text-center" colspan="5">No Data...</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td class="text-right px-3" colspan="3"><b>Total</b></td>
                        <td class="text-right"><b><?php echo number_format($total,2) ?></b></td>
                        <td></td>
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
            location.href =  "monthly-report.php&date_start="+$('[name="date_start"]').val()+"&date_end="+$('[name="date_end"]').val()
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