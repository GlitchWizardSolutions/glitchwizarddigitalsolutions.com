<?php
// 2024-12-09 Production
// 2025-06-15 Refresh VERIFIED
include_once 'assets/includes/admin_config.php';
include_once '../assets/includes/components.php';
include '../../client-invoices/defines.php';

// Handle status updates
if (isset($_GET['mark_paid']) && is_numeric($_GET['mark_paid'])) {
    $stmt = $pdo->prepare('UPDATE invoices SET payment_status = ? WHERE id = ?');
    $stmt->execute(['Paid', $_GET['mark_paid']]);
    header('Location: invoices.php');
    exit;
}

if (isset($_GET['mark_pending']) && is_numeric($_GET['mark_pending'])) {
    $stmt = $pdo->prepare('UPDATE invoices SET payment_status = ? WHERE id = ?');
    $stmt->execute(['Pending', $_GET['mark_pending']]);
    header('Location: invoices.php');
    exit;
}

if (isset($_GET['mark_unpaid']) && is_numeric($_GET['mark_unpaid'])) {
    $stmt = $pdo->prepare('UPDATE invoices SET payment_status = ? WHERE id = ?');
    $stmt->execute(['Unpaid', $_GET['mark_unpaid']]);
    header('Location: invoices.php');
    exit;
}

// Get current date
$current_date = date('Y-m-d H:i:s');
// Retrieve the GET request parameters (if specified)
$pagination_page = isset($_GET['pagination_page']) ? $_GET['pagination_page'] : 1;
$search = isset($_GET['search_query']) ? $_GET['search_query'] : '';
// Filters parameters
$datestart = isset($_GET['datestart']) ? $_GET['datestart'] : '';
$dateend = isset($_GET['dateend']) ? $_GET['dateend'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$payment_method = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';
$payment_method_str = '%' . $payment_method . '%';
$client_id = isset($_GET['client_id']) ? $_GET['client_id'] : '';
// Order by column
$order = isset($_GET['order']) && $_GET['order'] == 'DESC' ? 'DESC' : 'ASC';
// Add/remove columns to the whitelist array
$order_by_whitelist = ['id','client_id','invoice_number','payment_amount','payment_status','payment_methods','due_date','created','viewed','first_name','last_name','total_items'];
$order_by = isset($_GET['order_by']) && in_array($_GET['order_by'], $order_by_whitelist) ? $_GET['order_by'] : 'id';
// Number of results per pagination pagination_page
$results_per_pagination_page = 20;
// Declare query param variables
$param1 = ($pagination_page - 1) * $results_per_pagination_page;
$param2 = $results_per_pagination_page;
$param3 = '%' . $search . '%';
// SQL where clause
$where = '';
$where .= $search ? 'WHERE (i.invoice_number LIKE :search OR CONCAT(c.first_name, " ", c.last_name) LIKE :search) ' : '';
// Add filters
// Date start filter
if ($datestart) {
    $where .= ($where ? 'AND ' : 'WHERE ') . 'i.due_date >= :datestart ';
}
// Date end filter
if ($dateend) {
    $where .= ($where ? 'AND ' : 'WHERE ') . 'i.due_date <= :dateend ';
}
// Status filter
if ($status) {
    if ($status == 'paid') {
        $where .= ($where ? 'AND ' : 'WHERE ') . 'i.payment_status = "Paid" ';
    } elseif ($status == 'unpaid') {
        $where .= ($where ? 'AND ' : 'WHERE ') . 'i.payment_status = "Unpaid" ';
    } elseif ($status == 'pending') {
        $where .= ($where ? 'AND ' : 'WHERE ') . 'i.payment_status = "Pending" ';
    } elseif ($status == 'overdue') {
        $where .= ($where ? 'AND ' : 'WHERE ') . 'i.due_date < :current_date AND i.payment_status = "Unpaid" ';
    } elseif ($status == 'cancelled') {
        $where .= ($where ? 'AND ' : 'WHERE ') . 'i.payment_status = "Cancelled" ';
    }
}
// Payment method filter
if ($payment_method) {
    $where .= ($where ? 'AND ' : 'WHERE ') . 'i.payment_methods LIKE :payment_method ';
}
// Client ID filter
if ($client_id) {
    $where .= ($where ? 'AND ' : 'WHERE ') . 'i.client_id = :client_id ';
}
// Retrieve the total number of invoices
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM invoices i LEFT JOIN invoice_clients c ON c.id = i.client_id ' . $where);
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
if ($datestart) $stmt->bindParam('datestart', $datestart, PDO::PARAM_STR);
if ($dateend) $stmt->bindParam('dateend', $dateend, PDO::PARAM_STR);
if ($status && $status == 'overdue') $stmt->bindParam('current_date', $current_date, PDO::PARAM_STR);
if ($payment_method) $stmt->bindParam('payment_method', $payment_method_str, PDO::PARAM_STR);
if ($client_id) $stmt->bindParam('client_id', $client_id, PDO::PARAM_INT);
$stmt->execute();
$total_invoices = $stmt->fetchColumn();
// Prepare invoices query
$stmt = $pdo->prepare('SELECT i.*, c.first_name, c.last_name, c.email, (SELECT COUNT(*) FROM invoice_items ii WHERE ii.invoice_number = i.invoice_number) AS total_items FROM invoices i LEFT JOIN invoice_clients c ON c.id = i.client_id ' . $where . ' ORDER BY ' . $order_by . ' ' . $order . ' LIMIT :start_results,:num_results');
$stmt->bindParam('start_results', $param1, PDO::PARAM_INT);
$stmt->bindParam('num_results', $param2, PDO::PARAM_INT);
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
if ($datestart) $stmt->bindParam('datestart', $datestart, PDO::PARAM_STR);
if ($dateend) $stmt->bindParam('dateend', $dateend, PDO::PARAM_STR);
if ($status && $status == 'overdue') $stmt->bindParam('current_date', $current_date, PDO::PARAM_STR);
if ($payment_method) $stmt->bindParam('payment_method', $payment_method_str, PDO::PARAM_STR);
if ($client_id) $stmt->bindParam('client_id', $client_id, PDO::PARAM_INT);
$stmt->execute();
// Retrieve query results
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Delete invoice
if (isset($_GET['delete'])) {
    // Get invoice
    $stmt = $pdo->prepare('SELECT * FROM invoices WHERE id = ?');
    $stmt->execute([ $_GET['delete'] ]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
    // Delete the invoice
    $stmt = $pdo->prepare('DELETE i, ii FROM invoices i LEFT JOIN invoice_items ii ON ii.invoice_number = i.invoice_number WHERE i.id = ?');
    $stmt->execute([ $_GET['delete'] ]);
    // Check if PDF exists
    if (file_exists('../../client-invoices/pdfs/' . $invoice['invoice_number'] . '.pdf')) {
        unlink('../../client-invoices/pdfs/' . $invoice['invoice_number'] . '.pdf');
    }
    header('Location: invoices.php?success_msg=3');
    exit;
}
// Send reminder
if (isset($_GET['reminder'])) {
    // Get invoice
    $stmt = $pdo->prepare('SELECT * FROM invoices WHERE id = ?');
    $stmt->execute([ $_GET['reminder'] ]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
    // Get client details
    $stmt = $pdo->prepare('SELECT * FROM invoice_clients WHERE id = ?');
    $stmt->execute([ $invoice['client_id'] ]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    // Send email
    send_client_invoice_email($invoice, $client, 'Payment Reminder');
    header('Location: invoices.php?success_msg=5');
    exit;
}
// Handle success messages
if (isset($_GET['success_msg'])) {
    if ($_GET['success_msg'] == 1) {
        $success_msg = 'Invoice created successfully!';
    }
    if ($_GET['success_msg'] == 2) {
        $success_msg = 'Invoice updated successfully!';
    }
    if ($_GET['success_msg'] == 3) {
        $success_msg = 'Invoice deleted successfully!';
    }
    if ($_GET['success_msg'] == 4) {
        $success_msg = 'Invoice(s) imported successfully! ' . $_GET['imported'] . ' invoice(s) were imported.';
    }
    if ($_GET['success_msg'] == 5) {
        $success_msg = 'Payment reminder sent successfully!';
    }
    if ($_GET['success_msg'] == 6) {
        $success_msg = 'Invoice updated and email sent successfully!';
    }
    if ($_GET['success_msg'] == 7) {
        $success_msg = 'Invoice updated and receipt sent successfully!';
    }
}
// Create URL
$url = 'invoices.php?search_query=' . $search . '&datestart=' . $datestart . '&dateend=' . $dateend . '&status=' . $status . '&payment_method=' . $payment_method . '&client_id=' . $client_id;
?>
<?=template_admin_header('Manage Invoices', 'invoices', 'invoices')?>

<?=generate_breadcrumbs([
    ['label' => 'Invoices']
])?>

<div class="content-title">
    <div class="title">
       <i class="fa-solid fa-file-invoice"></i>
        <div class="txt">
            <h2>Manage Invoices</h2>
            <p>View, edit, and create invoices</p>
        </div>
    </div>
</div>

<?php if (isset($success_msg)): ?>
<div class="msg success">
    <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM369 209L241 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L335 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/></svg>
    <p><?=$success_msg?></p>
    <svg class="close" width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg>
</div>
<?php endif; ?>

<div class="content-title responsive-flex-wrap responsive-pad-bot-3">
    <a href="invoice.php" class="btn btn-primary">+ Invoice</a>&nbsp;&nbsp;
    <a href="invoices_import.php" class="btn btn-primary">Import</a>&nbsp;&nbsp;
    <a href="invoices_export.php" class="btn btn-primary">Export</a>
</div>
    <br>
    </div>
<div class="content-header responsive-flex-column pad-top-5">

    <form action="" method="get">
        <input type="hidden" name="page" value="invoices">
        <div class="filters">
            <a href="#">
                <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M0 416c0 17.7 14.3 32 32 32l54.7 0c12.3 28.3 40.5 48 73.3 48s61-19.7 73.3-48L480 448c17.7 0 32-14.3 32-32s-14.3-32-32-32l-246.7 0c-12.3-28.3-40.5-48-73.3-48s-61 19.7-73.3 48L32 384c-17.7 0-32 14.3-32 32zm128 0a32 32 0 1 1 64 0 32 32 0 1 1 -64 0zM320 256a32 32 0 1 1 64 0 32 32 0 1 1 -64 0zm32-80c-32.8 0-61 19.7-73.3 48L32 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l246.7 0c12.3 28.3 40.5 48 73.3 48s61-19.7 73.3-48l54.7 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-54.7 0c-12.3-28.3-40.5-48-73.3-48zM192 128a32 32 0 1 1 0-64 32 32 0 1 1 0 64zm73.3-64C253 35.7 224.8 16 192 16s-61 19.7-73.3 48L32 64C14.3 64 0 78.3 0 96s14.3 32 32 32l86.7 0c12.3 28.3 40.5 48 73.3 48s61-19.7 73.3-48L480 128c17.7 0 32-14.3 32-32s-14.3-32-32-32L265.3 64z"/></svg>
                Filters
            </a>
            <div class="list">
                <label for="datestart">Due Date Start</label>
                <input type="datetime-local" name="datestart" id="datestart" value="<?=htmlspecialchars($datestart, ENT_QUOTES)?>">
                <label for="dateend">Due Date End</label>
                <input type="datetime-local" name="dateend" id="dateend" value="<?=htmlspecialchars($dateend, ENT_QUOTES)?>">
                <label for="status">Status</label>
                <select name="status" id="status">
                    <option value=""<?=$status==''?' selected':''?>>All</option>
                    <option value="paid"<?=$status=='paid'?' selected':''?>>Paid</option>
                    <option value="unpaid"<?=$status=='unpaid'?' selected':''?>>Unpaid</option>
                    <option value="pending"<?=$status=='pending'?' selected':''?>>Pending</option>
                    <option value="overdue"<?=$status=='overdue'?' selected':''?>>Overdue</option>
                    <option value="cancelled"<?=$status=='cancelled'?' selected':''?>>Cancelled</option>
                </select>
                <label for="payment_method">Payment Method</label>
                <select name="payment_method" id="payment_method">
                    <option value=""<?=$payment_method==''?' selected':''?>>All</option>
                    <option value="Cash"<?=$payment_method=='Cash'?' selected':''?>>Cash</option>
                    <option value="Bank Transfer"<?=$payment_method=='Bank Transfer'?' selected':''?>>Bank Transfer</option>
                    <option value="PayPal"<?=$payment_method=='PayPal'?' selected':''?>>PayPal</option>
                    <option value="Stripe"<?=$payment_method=='Stripe'?' selected':''?>>Stripe</option>
                </select>
                <button type="submit">Apply</button>
            </div>
        </div>
        <div class="search">
            <label for="search_query">
                <input id="search_query" type="text" name="search_query" placeholder="Search invoice..." value="<?=htmlspecialchars($search, ENT_QUOTES)?>" class="responsive-width-100">
                <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"/></svg>
            </label>
        </div>
    </form>
</div>

<div class="filter-list">
    <?php if ($datestart != ''): ?>
    <div class="filter">
        <a href="<?=remove_url_param($url, 'datestart')?>"><svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free --><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg></a>
        Date Start : <?=htmlspecialchars($datestart, ENT_QUOTES)?>
    </div>
    <?php endif; ?>
    <?php if ($dateend != ''): ?>
    <div class="filter">
        <a href="<?=remove_url_param($url, 'dateend')?>"><svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free --><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg></a>
        Date End : <?=htmlspecialchars($dateend, ENT_QUOTES)?>
    </div>
    <?php endif; ?>
    <?php if ($status != ''): ?>
    <div class="filter">
        <a href="<?=remove_url_param($url, 'status')?>"><svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free --><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg></a>
        Status : <?=htmlspecialchars($status, ENT_QUOTES)?>
    </div>
    <?php endif; ?>
    <?php if ($payment_method != ''): ?>
    <div class="filter">
        <a href="<?=remove_url_param($url, 'payment_method')?>"><svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free --><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg></a>
        Payment Method : <?=htmlspecialchars($payment_method, ENT_QUOTES)?>
    </div>
    <?php endif; ?>
    <?php if ($client_id != ''): ?>
    <div class="filter">
        <a href="<?=remove_url_param($url, 'client_id')?>"><svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free --><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg></a>
        Client ID : <?=htmlspecialchars($client_id, ENT_QUOTES)?>
    </div>
    <?php endif; ?>
    <?php if ($search != ''): ?>
    <div class="filter">
        <a href="<?=remove_url_param($url, 'search_query')?>"><svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free --><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg></a>
        Search : <?=htmlspecialchars($search, ENT_QUOTES)?>
    </div>
    <?php endif; ?>   
</div>

<div class="content-block">
    <div class="table">
        <table>
            <thead>
                <tr>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=id'?>">#<?=$order_by=='id' ? $table_icons[strtolower($order)] : ''?></td>
                    <td colspan="2"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=first_name'?>">Client<?=$order_by=='first_name' ? $table_icons[strtolower($order)] : ''?></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=invoice_number'?>">Invoice #<?=$order_by=='invoice_number' ? $table_icons[strtolower($order)] : ''?></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=total_items'?>">Items<?=$order_by=='total_items' ? $table_icons[strtolower($order)] : ''?></td>
               <?php /*     <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=payment_methods'?>">Method(s)<?=$order_by=='payment_methods' ? $table_icons[strtolower($order)] : ''?></td> */ ?>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=payment_amount'?>">Amount<?=$order_by=='payment_amount' ? $table_icons[strtolower($order)] : ''?></td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=payment_status'?>">Status<?=$order_by=='payment_status' ? $table_icons[strtolower($order)] : ''?></td>
                    <td>Seen</td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=due_date'?>">Due Date<?=$order_by=='due_date' ? $table_icons[strtolower($order)] : ''?></td>
                    <td class="align-center">Actions</td>
                </tr>
            </thead>
            <tbody>
                <?php if (!$invoices): ?>
                <tr>
                    <td colspan="20" class="no-results">There are no invoices.</td>
                </tr>
                <?php endif; ?>
                <?php foreach ($invoices as $invoice): ?>
                <tr>
                    <td><?=$invoice['id']?></td>
                    <td class="img">
                        <div class="profile-img">
                            <span style="background-color:<?=color_from_string($invoice['first_name'])?>"><?=strtoupper(substr($invoice['first_name'], 0, 1))?></span>
                        </div>
                    </td>
                    <td><?=htmlspecialchars($invoice['first_name'] . ' ' . $invoice['last_name'], ENT_QUOTES)?></td>
                    <td class="alt responsive-hidden"><?=htmlspecialchars($invoice['invoice_number'], ENT_QUOTES)?></td>
                    <td class="alt responsive-hidden"><span class="grey small"><?=number_format($invoice['total_items'])?></span></td>
                  <?php /*  <td class="alt responsive-hidden">
                        <?php if ($invoice['payment_methods']): ?>
                        <?php foreach (explode(',', $invoice['payment_methods']) as $method): ?>
                        <span class="grey"><?=$method?></span>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </td> */ ?>
                    <td class="responsive-hidden"><?=currency_code . number_format($invoice['payment_amount']+$invoice['tax_total'], 2)?></td>
                    <td>
                        <div class="invoice-detail">
                            <?php if ($invoice['payment_status'] == 'Paid'): ?>
                            <span class="green">Paid in Full</span>
                            <?php elseif ($invoice['payment_status'] == 'Cancelled'): ?>
                            <span class="grey">Cancelled</span>
                            <?php elseif ($invoice['payment_status'] == 'Pending'): ?>
                            <span class="orange">Pending</span>
                            <?php elseif ($invoice['due_date'] < $current_date): ?>
                            <span class="red">OVERDUE</span>
                            <?php else: ?>
                            <span class="blue">Unpaid</span>
                            <?php endif; ?>
                            </td>
                            <td>
                            <?php if ($invoice['viewed']): ?>
                            <div class="viewed">
                                <svg width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><title>The client has viewed the invoice.</title><path d="M12,9A3,3 0 0,0 9,12A3,3 0 0,0 12,15A3,3 0 0,0 15,12A3,3 0 0,0 12,9M12,17A5,5 0 0,1 7,12A5,5 0 0,1 12,7A5,5 0 0,1 17,12A5,5 0 0,1 12,17M12,4.5C7,4.5 2.73,7.61 1,12C2.73,16.39 7,19.5 12,19.5C17,19.5 21.27,16.39 23,12C21.27,7.61 17,4.5 12,4.5Z" /></svg>
                            </div>
                            <?php endif; ?>
                            </td>
                        </div>
                    </td>
                   <?php if ($invoice['payment_status'] == 'Paid'): ?>
                    <td class="alt responsive-hidden" style="text-align:center; color:green">Paid</td>
                    <?php else:?>
                        <?php if($current_date > $invoice['due_date']): ?>
                          <td class="alt responsive-hidden" style="text-align:center"><span style="color:red"> <?=time_difference_string($invoice['due_date'])?></span></td>
                          <?php elseif($current_date < $invoice['due_date']): ?>
                           <td class="alt responsive-hidden" style="text-align:center"><span style="color:blue"><?=time_difference_string($invoice['due_date'])?></span></td>
                           <?php else: ?>  
                           <td class="alt responsive-hidden" style="text-align:center"><span style="color:red"><?=time_difference_string($invoice['due_date'])?></span></td>
 <?php endif?>
                    <?php endif?>
                   <?php /* <td class="alt responsive-hidden"><?=time_elapsed_string($invoice['due_date'])?></td>*/ ?>
                    <td class="actions">
                        <div class="table-dropdown">
                            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M8 256a56 56 0 1 1 112 0A56 56 0 1 1 8 256zm160 0a56 56 0 1 1 112 0 56 56 0 1 1 -112 0zm216-56a56 56 0 1 1 0 112 56 56 0 1 1 0-112z"/></svg>
                            <div class="table-dropdown-items">
                                <a href="view_invoice.php?id=<?=$invoice['id']?>">
                                    <span class="icon">
                                        <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M288 32c-80.8 0-145.5 36.8-192.6 80.6C48.6 156 17.3 208 2.5 243.7c-3.3 7.9-3.3 16.7 0 24.6C17.3 304 48.6 356 95.4 399.4C142.5 443.2 207.2 480 288 480s145.5-36.8 192.6-80.6c46.8-43.5 78.1-95.4 93-131.1c3.3-7.9 3.3-16.7 0-24.6c-14.9-35.7-46.2-87.7-93-131.1C433.5 68.8 368.8 32 288 32zM144 256a144 144 0 1 1 288 0 144 144 0 1 1 -288 0zm144-64c0 35.3-28.7 64-64 64c-7.1 0-13.9-1.2-20.3-3.3c-5.5-1.8-11.9 1.6-11.7 7.4c.3 6.9 1.3 13.8 3.2 20.7c13.7 51.2 66.4 81.6 117.6 67.9s81.6-66.4 67.9-117.6c-11.1-41.5-47.8-69.4-88.6-71.1c-5.8-.2-9.2 6.1-7.4 11.7c2.1 6.4 3.3 13.2 3.3 20.3z"/></svg>
                                    </span>
                                    View
                                </a>
                                <a href="invoice.php?id=<?=$invoice['id']?>">
                                    <span class="icon">
                                        <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M471.6 21.7c-21.9-21.9-57.3-21.9-79.2 0L362.3 51.7l97.9 97.9 30.1-30.1c21.9-21.9 21.9-57.3 0-79.2L471.6 21.7zm-299.2 220c-6.1 6.1-10.8 13.6-13.5 21.9l-29.6 88.8c-2.9 8.6-.6 18.1 5.8 24.6s15.9 8.7 24.6 5.8l88.8-29.6c8.2-2.7 15.7-7.4 21.9-13.5L437.7 172.3 339.7 74.3 172.4 241.7zM96 64C43 64 0 107 0 160V416c0 53 43 96 96 96H352c53 0 96-43 96-96V320c0-17.7-14.3-32-32-32s-32 14.3-32 32v96c0 17.7-14.3 32-32 32H96c-17.7 0-32-14.3-32-32V160c0-17.7 14.3-32 32-32h96c17.7 0 32-14.3 32-32s-14.3-32-32-32H96z"/></svg>
                                    </span>
                                    Edit
                                </a>
                                <a class="red" href="invoices.php?delete=<?=$invoice['id']?>" onclick="return confirm('Are you sure you want to delete this invoice?')">
                                    <span class="icon">
                                        <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z"/></svg>
                                    </span>    
                                    Delete
                                </a>
                                <?php if ($invoice['payment_status'] == 'Unpaid' && mail_enabled): ?>
                                <a href="invoices.php?reminder=<?=$invoice['id']?>">
                                    <span class="icon">
                                        <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M20,8L12,13L4,8V6L12,11L20,6M20,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V6C22,4.89 21.1,4 20,4Z" /></svg>
                                    </span>
                                    Send Reminder
                                </a>     
                                <?php endif; ?>
                                <?php if ($invoice['payment_status'] != 'Paid'): ?>
                                <a href="invoices.php?mark_paid=<?=$invoice['id']?>" class="green" onclick="return confirm('Mark this invoice as Paid?')">
                                    <span class="icon">
                                        <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21,7L9,19L3.5,13.5L4.91,12.09L9,16.17L19.59,5.59L21,7Z" /></svg>
                                    </span>
                                    Mark as Paid
                                </a>
                                <?php endif; ?>
                                <?php if ($invoice['payment_status'] != 'Pending'): ?>
                                <a href="invoices.php?mark_pending=<?=$invoice['id']?>" class="orange">
                                    <span class="icon">
                                        <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22C6.47,22 2,17.5 2,12A10,10 0 0,1 12,2M12.5,7V12.25L17,14.92L16.25,16.15L11,13V7H12.5Z" /></svg>
                                    </span>
                                    Mark as Pending
                                </a>
                                <?php endif; ?>                          
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="pagination">
    <?php if ($pagination_page > 1): ?>
    <a href="<?=$url?>&pagination_page=<?=$pagination_page-1?>&order=<?=$order?>&order_by=<?=$order_by?>">Prev</a>
    <?php endif; ?>
    <span>Page <?=$pagination_page?> of <?=ceil($total_invoices / $results_per_pagination_page) == 0 ? 1 : ceil($total_invoices / $results_per_pagination_page)?></span>
    <?php if ($pagination_page * $results_per_pagination_page < $total_invoices): ?>
    <a href="<?=$url?>&pagination_page=<?=$pagination_page+1?>&order=<?=$order?>&order_by=<?=$order_by?>">Next</a>
    <?php endif; ?>
</div>

<?=template_admin_footer()?>