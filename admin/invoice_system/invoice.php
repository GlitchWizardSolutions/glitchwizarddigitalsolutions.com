<?php
// 2024-12-09 Production.
// 2025-06-15 Reworked.
include_once 'assets/includes/admin_config.php';
include_once '../assets/includes/components.php';
$date = new DateTime();
$duedate = $date->modify('+7 days');
// Default invoice values
$invoice = [
    'client_id' => '',
    'invoice_number' => 'Automatically Generated',
   // 'invoice_number' => invoice_prefix . substr(str_shuffle(str_repeat('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ', 8)), 0, 8),
    'payment_amount' => '',
    'payment_status' => '',
    'payment_methods' => '',
    'notes' => 'Thank you for your order!',
    'viewed' => 0,
    'due_date' => $duedate->format('Y-m-d\TH:i'),
    'created' => date('Y-m-d\TH:i'),
    'tax' => '0.00',
    'tax_total' => '0.00',
    'invoice_template' => 'default',
    'recurrence' => 0,
    'recurrence_period' => 1,
    'recurrence_period_type' => 'year',
    'payment_ref' => '',
    'paid_with' => '',
    'paid_total' => 0
];

// Get template names in templates folder, only display folders
$templates = array_filter(glob(base_path . 'templates/*'), 'is_dir');
// Retrieve accounts
$invoice_clients = $pdo->query('SELECT * FROM invoice_clients ORDER BY first_name ASC')->fetchAll();
// Invoice items
$invoice_items = [];
// Calculate payment amount
$payment_amount = 0;
if (isset($_POST['item_id']) && is_array($_POST['item_id']) && count($_POST['item_id']) > 0) {
    foreach ($_POST['item_id'] as $i => $item_id) {
        $payment_amount += $_POST['item_price'][$i] * $_POST['item_quantity'][$i];
    }
}
// Calculate tax
$tax_total = 0;
$tax = 'fixed';
if (isset($_POST['tax'])) {
    if (strpos($_POST['tax'], '%') !== false) {
        $tax_total = $payment_amount * (floatval(str_replace('%', '', $_POST['tax'])) / 100);
        $tax = $_POST['tax'];
    } else {
        $tax_total = floatval($_POST['tax']);
    }
}
// Check if the ID param exists
if (isset($_GET['id'])) {
    // Retrieve the invoice from the database
    $stmt = $pdo->prepare('SELECT * FROM invoices WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
    // Get items
    $stmt = $pdo->prepare('SELECT * FROM invoice_items WHERE invoice_number = ?');
    $stmt->execute([ $invoice['invoice_number'] ]);
    $invoice_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // ID param exists, edit an existing invoice
    $page = 'Edit';
    if (isset($_POST['submit']) || isset($_POST['send_email']) || isset($_POST['send_receipt'])) {
        // Get the payment methods as a comma separated string
        $payment_methods = isset($_POST['payment_methods']) ? implode(', ', $_POST['payment_methods']) : '';
        // Update the invoice
        error_log("Update invoices: " . $_POST['client_id'] . "," . $_POST['invoice_number']);
        $stmt = $pdo->prepare('UPDATE invoices SET client_id = ?, invoice_number = ?, payment_amount = ?, payment_status = ?, payment_methods = ?, notes = ?, due_date = ?, created = ?, tax = ?, tax_total = ?, invoice_template = ?, recurrence = ?, recurrence_period = ?, recurrence_period_type = ?, paid_total = ? WHERE id = ?');
        $stmt->execute([ $_POST['client_id'], $_POST['invoice_number'], $payment_amount, $_POST['payment_status'], $payment_methods, $_POST['notes'], $_POST['due_date'], $_POST['created'], $tax, $tax_total, $_POST['invoice_template'], $_POST['recurrence'], $_POST['recurrence_period'], $_POST['recurrence_period_type'], $_POST['paid_total'], $_GET['id'] ]);
        // add items
        addItems($pdo, $_POST['invoice_number']);
        // Create PDF
        error_log("Create PDF: " .$_POST['invoice_number'] . "," . $_POST['invoice_number']);
        $stmt = $pdo->prepare('SELECT * FROM invoices WHERE invoice_number = ?');
        $stmt->execute([ $_POST['invoice_number'] ]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        // Get invoice items
        $stmt = $pdo->prepare('SELECT * FROM invoice_items WHERE invoice_number = ?');
        $stmt->execute([ $invoice['invoice_number'] ]);
        $invoice_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Get client details
        $stmt = $pdo->prepare('SELECT * FROM invoice_clients WHERE id = ?');
        $stmt->execute([ $invoice['client_id'] ]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        // Generate pdf
        create_invoice_pdf($invoice, $invoice_items, $client);
        
        // If send_receipt button was clicked, send receipt email
        if (isset($_POST['send_receipt'])) {
            send_client_receipt_email($invoice, $client);
            header('Location: invoices.php?success_msg=7');
        } elseif (isset($_POST['send_email'])) {
            // If send_email button was clicked, send the invoice email
            send_client_invoice_email($invoice, $client);
            header('Location: invoices.php?success_msg=6');
        } else {
            // Just save without email
            header('Location: invoices.php?success_msg=2');
        }
        exit;
    }
    if (isset($_POST['delete'])) {
        // Redirect and delete invoice
        header('Location: invoices.php?delete=' . $_GET['id']);
        exit;
    }
} else {
    // Create a new invoice
    $page = 'Create';
    if (isset($_POST['submit'])) {
        // Get the payment methods as a comma separated string
        $payment_methods = isset($_POST['payment_methods']) ? implode(', ', $_POST['payment_methods']) : ''; 
        //Generate the invoice number
        $stmt = $pdo->prepare('SELECT business_name FROM invoice_clients WHERE id = ?');
        $stmt->execute([ $_POST['client_id'] ]);
        $business_name = $stmt->fetch(PDO::FETCH_ASSOC);
        $inv= substr($business_name['business_name'],0,7);
        $inv= str_replace(' ', '', $inv); 
        $inv= date('ymdh:ia') . $inv; 
        // Insert the invoice
        $stmt = $pdo->prepare('INSERT INTO invoices (client_id, invoice_number, payment_amount, payment_status, payment_methods, notes, viewed, due_date, created, tax, tax_total, invoice_template, recurrence, recurrence_period, recurrence_period_type) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([ $_POST['client_id'], $inv, $payment_amount, $_POST['payment_status'], $payment_methods, $_POST['notes'], 0, $_POST['due_date'], $_POST['created'], $tax, $tax_total, $_POST['invoice_template'], $_POST['recurrence'], $_POST['recurrence_period'], $_POST['recurrence_period_type'] ]);
        // add items
        addItems($pdo, $inv);
        // Create PDF
        $stmt = $pdo->prepare('SELECT * FROM invoices WHERE invoice_number = ?');
        $stmt->execute([ $inv ]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        // Get invoice items
        $stmt = $pdo->prepare('SELECT * FROM invoice_items WHERE invoice_number = ?');
        $stmt->execute([ $inv ]);
        $invoice_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Get client details
        $stmt = $pdo->prepare('SELECT * FROM invoice_clients WHERE id = ?');
        $stmt->execute([ $invoice['client_id'] ]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        // Generate pdf
        create_invoice_pdf($invoice, $invoice_items, $client);
        // Send email
        if (isset($_POST['send_email'])) {
            send_client_invoice_email($invoice, $client);
        }
        // Redirect to the invoices page
        header('Location: invoices.php?success_msg=1');
        exit;
    }
}
?>
<?=template_admin_header($page . ' Invoice', 'invoices', 'invoices')?>

<?=generate_breadcrumbs([
    ['label' => 'Invoices', 'url' => 'invoices.php'],
    ['label' => $page . ' Invoice']
])?>

<div class="content-title">
    <div class="icon alt"><?=svg_icon_invoice()?></div>
    <div class="txt">
        <h2><?=$page?> Invoice</h2>
        <p class="subtitle">Invoice clients consistently for all work performed</p>
    </div>
</div>

<div class="content-title responsive-flex-wrap responsive-pad-bot-3">
    <a href="invoices.php" class="btn btn-primary">
        View Invoices
    </a>&nbsp;&nbsp;
    <a href="invoices_import.php" class="btn btn-primary">
        Import
    </a>&nbsp;&nbsp;
    <a href="invoices_export.php" class="btn btn-primary">
        Export
    </a>
</div>

<?php if (isset($success_msg)): ?>
<div class="msg success">
    <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM369 209L241 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L335 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/></svg>
    <p><?=$success_msg?></p>
    <svg class="close" width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg>
</div>
<?php endif; ?>

<form action="" method="post" class="form-professional">

    <div class="form-actions">
        <a href="invoices.php" class="btn btn-secondary">Cancel</a>
        <?php if ($page == 'Edit'): ?>
        <input type="submit" name="delete" value="Delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this invoice?')">
        <?php endif; ?>
        <input type="submit" name="submit" value="Save Invoice" class="btn btn-success">
        <?php if ($page == 'Edit'): ?>
            <?php if (isset($invoice['payment_status']) && $invoice['payment_status'] == 'Paid'): ?>
        <input type="submit" name="send_receipt" value="Save & Send Receipt" class="btn btn-success" onclick="return confirm('Send payment receipt to client?')">
            <?php else: ?>
        <input type="submit" name="send_email" value="Save & Send Email" class="btn btn-success" onclick="return confirm('Send invoice email to client?')">
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="tabs">
        <a href="#" class="active">Details</a>
        <a href="#">Items</a>
    </div>

    <div class="content-block tab-content active">
        <div class="form-section">
            <div class="section-title">Invoice Details</div>

            <div class="form responsive-width-100">

            <label for="client_id">Client</label>
    
            <select id="client_id" name="client_id" class="client_id" style="margin-bottom:10px">
 required>
                <option value="0">Select Invoice Client</option>
                <?php foreach ($invoice_clients as $invoice_client): ?>
                <option value="<?=$invoice_client['id']?>"<?=$invoice['client_id']==$invoice_client['id']?' selected':''?>><?=$invoice_client['business_name'] ?>&nbsp;[<?=$invoice_client['first_name']?>&nbsp;<?=$invoice_client['last_name']?>]</option>

                <?php endforeach; ?>
            </select>
          <?php /*  <a href="#" class="add-client"><svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z" /></svg>Add New Client</a>  
                This modal doesn't allow for a client with the same email as another, nor does it collect the acc_id, errors also, saying the name and email aren't populating.*/ ?>
<a href="client.php" class="btn btn-primary">Add New Client</a>  <br>
            <?php if ($page == 'Create'): ?>
            <label for="send_email" class="checkbox pad-bot-5">
                <input id="send_email" type="checkbox" name="send_email" value="1" checked>
                Send email to client
            </label>
            <?php endif; ?>
  
             <label for ="invoice_number"><span class="required">*</span> Invoice Number</label>
            
            <input id="invoice_number" type="text" name="invoice_number" placeholder="Invoice Number" value="<?=htmlspecialchars($invoice['invoice_number']?? '', ENT_QUOTES)?>" required>
           
            <label for="payment_status">Payment Status</label>
            <select id="payment_status" name="payment_status">
                <option value="Unpaid"<?=$invoice['payment_status']=='Unpaid'?' selected':''?>>Unpaid</option>
                <option value="Paid"<?=$invoice['payment_status']=='Paid'?' selected':''?>>Paid</option>
                <option value="Pending"<?=$invoice['payment_status']=='Pending'?' selected':''?>>Pending</option>
                <option value="Cancelled"<?=$invoice['payment_status']=='Cancelled'?' selected':''?>>Cancelled</option>
            </select>

            <label for="payment_methods">Payment Methods</label>
            <div class="multiselect" data-name="payment_methods[]">
                <?php foreach (array_filter(explode(', ', $invoice['payment_methods'])) as $m): ?>
                <span class="item" data-value="<?=$m?>">
                    <i class="remove">&times;</i><?=$m?>
                    <input type="hidden" name="payment_methods[]" value="<?=$m?>">
                </span>
                <?php endforeach; ?>
                <input type="text" class="search" id="payment_method" placeholder="Payment Methods">
                <div class="list">
                    <span data-value="Cash">Cash</span>
                    <span data-value="PayPal">PayPal</span>
                 
                </div>
            </div>

            <label for="tax">Tax</label>
            <input id="tax" type="text" name="tax" placeholder="% or fixed amount" value="<?=$invoice['tax'] == 'fixed' ? $invoice['tax_total'] : $invoice['tax']?>" step=".01">

            <label for="invoice_template">Template</label>
            <select id="invoice_template" name="invoice_template">
                <?php foreach ($templates as $template): ?>
                <option value="<?=basename($template)?>"<?=$invoice['invoice_template']==basename($template)?' selected':''?>><?=basename($template)?></option>
                <?php endforeach; ?>
            </select>

            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" placeholder="Notes"><?=htmlspecialchars($invoice['notes'], ENT_QUOTES)?></textarea>

            <label for="recurrence">Recurrence</label>
            <select id="recurrence" name="recurrence">
                <option value="0"<?=$invoice['recurrence']==0?' selected':''?>>No</option>
                <option value="1"<?=$invoice['recurrence']==1?' selected':''?>>Yes</option>
            </select>

            <div class="recurrence-options"<?=$invoice['recurrence']==0 ? ' style="display:none"' : ''?>>
                <label for="recurrence_period">Recurrence Period</label>
                <input id="recurrence_period" type="number" name="recurrence_period" placeholder="Recurrence Period" min="1" value="<?=$invoice['recurrence_period']?>">

                <label for="recurrence_period_type">Recurrence Period Type</label>
                <select id="recurrence_period_type" name="recurrence_period_type">
                    <option value="day"<?=$invoice['recurrence_period_type']=='day'?' selected':''?>>Day</option>
                    <option value="week"<?=$invoice['recurrence_period_type']=='week'?' selected':''?>>Week</option>
                    <option value="month"<?=$invoice['recurrence_period_type']=='month'?' selected':''?>>Month</option>
                    <option value="year"<?=$invoice['recurrence_period_type']=='year'?' selected':''?>>Year</option>
                </select>
            </div>

            <?php if ($page == 'Edit'): ?>
            <label for="paid_total">Paid Total</label>
            <input id="paid_total" type="number" name="paid_total" placeholder="Paid Total" value="<?=$invoice['paid_total']?>" step=".01">
            <?php endif; ?>

            <label for="due_date"><span class="required">*</span> Due Date</label>
            <input id="due_date" type="datetime-local" name="due_date" value="<?=date('Y-m-d\TH:i', strtotime($invoice['due_date']))?>" required>

            <label for="created"><span class="required">*</span> Created</label>
            <input id="created" type="datetime-local" name="created" value="<?=date('Y-m-d\TH:i', strtotime($invoice['created']))?>" required>

        </div>
       <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
 
        <a href="invoices.php" class="btn mar-right-2">Cancel</a>
        <a href="invoices.php" class="btn mar-right-2">Cancel</a>
        <?php if ($page == 'Edit'): ?>
        <input type="submit" name="delete" value="Delete" class="btn btn-danger mar-right-2" onclick="return confirm('Are you sure you want to delete this invoice?')">
        <?php endif; ?>
        <input type="submit" name="submit" value="Save Invoice" class="btn btn-success mar-right-2">
        <?php if ($page == 'Edit'): ?>
            <?php if (isset($invoice['payment_status']) && $invoice['payment_status'] == 'Paid'): ?>
        <input type="submit" name="send_receipt" value="Save & Send Receipt" class="btn" style="background:#28a745; color:white;" onclick="return confirm('Send payment receipt to client?')">
            <?php else: ?>
        <input type="submit" name="send_email" value="Save & Send Email" class="btn" style="background:#0066cc; color:white;" onclick="return confirm('Send invoice email to client?')">
            <?php endif; ?>
        <?php endif; ?>
   </div>
    </div>

    <div class="content-block tab-content">
        <div class="table manage-invoice-table">
            <table>
                <thead>
                    <tr>
                        <td>Name</td>
                        <td>Description</td>
                        <td>Price</td>
                        <td>Quantity</td>
                        <td></td>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($invoice_items)): ?>
                    <tr>
                        <td colspan="20" class="no-invoice-items-msg no-results">There are no invoice items.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($invoice_items as $item): ?>
                    <tr>
                        <td><input type="hidden" name="item_id[]" value="<?=$item['id']?>"><input name="item_name[]" type="text" placeholder="Name" value="<?=htmlspecialchars($item['item_name'], ENT_QUOTES)?>" required></td>
                        <td><input name="item_description[]" type="text" placeholder="Description"  maxlength="250" value="<?=htmlspecialchars($item['item_description'], ENT_QUOTES)?>"></td>
                        <td><input name="item_price[]" type="number" placeholder="Price" value="<?=$item['item_price']?>" step=".01"></td>
                        <td><input name="item_quantity[]" type="number" placeholder="Quantity" value="<?=$item['item_quantity']?>"></td>
                        <td><svg class="delete-item" width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><title>Delete Item</title><path d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z" /></svg></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <a href="#" class="add-item"><svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z" /></svg>Add Item</a>
        </div>
       </div>
 
 
 </div>
</form>
  
    
<?=template_admin_footer('<script>initManageInvoiceItems()</script>')?>