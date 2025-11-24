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
    'paid_total' => 0,
    'domain_id' => null,
    'project_type_id' => null,
    'is_recurring' => 0,
    'recurrence_frequency' => 'none'
];

// Get template names in templates folder, only display folders
$templates = array_filter(glob(base_path . 'templates/*'), 'is_dir');
// Retrieve accounts
$invoice_clients = $pdo->query('SELECT * FROM invoice_clients ORDER BY first_name ASC')->fetchAll();
// Retrieve active domains for dropdown
$domains = $pdo->query('SELECT * FROM domains WHERE status = "Active" ORDER BY domain ASC')->fetchAll();
// Retrieve project types for dropdown
$project_types = $pdo->query('SELECT * FROM project_types ORDER BY name ASC')->fetchAll();
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
        $stmt = $pdo->prepare('UPDATE invoices SET client_id = ?, invoice_number = ?, payment_amount = ?, payment_status = ?, payment_methods = ?, notes = ?, due_date = ?, created = ?, tax = ?, tax_total = ?, invoice_template = ?, recurrence = ?, recurrence_period = ?, recurrence_period_type = ?, paid_total = ?, domain_id = ?, project_type_id = ?, is_recurring = ?, recurrence_frequency = ? WHERE id = ?');
        $stmt->execute([ $_POST['client_id'], $_POST['invoice_number'], $payment_amount, $_POST['payment_status'], $payment_methods, $_POST['notes'], $_POST['due_date'], $_POST['created'], $tax, $tax_total, $_POST['invoice_template'], $_POST['recurrence'], $_POST['recurrence_period'], $_POST['recurrence_period_type'], $_POST['paid_total'], $_POST['domain_id'] ?: null, $_POST['project_type_id'] ?: null, isset($_POST['is_recurring']) ? 1 : 0, $_POST['recurrence_frequency'], $_GET['id'] ]);
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
        $stmt = $pdo->prepare('INSERT INTO invoices (client_id, invoice_number, payment_amount, payment_status, payment_methods, notes, viewed, due_date, created, tax, tax_total, invoice_template, recurrence, recurrence_period, recurrence_period_type, domain_id, project_type_id, is_recurring, recurrence_frequency) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([ $_POST['client_id'], $inv, $payment_amount, $_POST['payment_status'], $payment_methods, $_POST['notes'], 0, $_POST['due_date'], $_POST['created'], $tax, $tax_total, $_POST['invoice_template'], $_POST['recurrence'], $_POST['recurrence_period'], $_POST['recurrence_period_type'], $_POST['domain_id'] ?: null, $_POST['project_type_id'] ?: null, isset($_POST['is_recurring']) ? 1 : 0, $_POST['recurrence_frequency'] ]);
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
    <div class="title">
       <i class="fa-solid fa-file-invoice"></i>
        <div class="txt">
            <h2><?=$page?> Invoice</h2>
            <p>Invoice clients consistently for all work performed</p>
        </div>
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

    <div class="form-actions" style="position: sticky; top: 0; background: white; z-index: 100; padding: 15px 0; border-bottom: 2px solid #e0e0e0; margin-bottom: 20px;">
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

    <!-- Section 1: Client & Basic Info -->
    <div class="content-block" style="margin-bottom: 30px;">
        <div class="form-section">
            <div class="section-title" style="background: #6b46c1; color: white; padding: 12px 20px; border-radius: 6px 6px 0 0; font-size: 16px; display: flex; align-items: center; gap: 10px;">
                <span style="background: white; color: #6b46c1; width: 30px; height: 30px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: bold;">1</span>
                <i class="fa-solid fa-user"></i> Client & Invoice Information
            </div>

            <div class="form responsive-width-100" style="padding: 20px;">

            <label for="client_id"><span class="required">*</span> Client</label>
    
            <select id="client_id" name="client_id" class="client_id" style="margin-bottom:10px" required>
                <option value="">Select Invoice Client</option>
                <?php foreach ($invoice_clients as $invoice_client): ?>
                <option value="<?=$invoice_client['id']?>"<?=$invoice['client_id']==$invoice_client['id']?' selected':''?>><?=$invoice_client['business_name'] ?>&nbsp;[<?=$invoice_client['first_name']?>&nbsp;<?=$invoice_client['last_name']?>]</option>
                <?php endforeach; ?>
            </select>
            <a href="client.php" class="btn btn-primary" style="margin-top: 10px; display: inline-block;">Add New Client</a>
            
            <label for="domain_id"><i class="fa-solid fa-globe"></i> Domain (Optional)</label>
            <select id="domain_id" name="domain_id" style="margin-bottom:10px">
                <option value="">-- Select Domain (Optional) --</option>
                <?php foreach ($domains as $domain): ?>
                <option value="<?=$domain['id']?>" 
                        data-amount="<?=$domain['amount']?>" 
                        data-due-date="<?=$domain['due_date']?>"
                        <?=isset($invoice['domain_id']) && $invoice['domain_id']==$domain['id']?' selected':''?>>
                    <?=$domain['domain']?> - $<?=number_format($domain['amount'], 2)?> (Due: <?=date('m/d/Y', strtotime($domain['due_date']))?>)
                </option>
                <?php endforeach; ?>
            </select>
            <small style="color: #666; display: block; margin-top: 5px;"><i class="fa-solid fa-info-circle"></i> Link this invoice to a domain renewal. Amount will auto-populate.</small>

            <label for="project_type_id"><i class="fa-solid fa-layer-group"></i> Service Category (Optional)</label>
            <select id="project_type_id" name="project_type_id" style="margin-bottom:10px">
                <option value="">-- Select Service Category (Optional) --</option>
                <?php foreach ($project_types as $type): ?>
                <option value="<?=$type['id']?>" <?=isset($invoice['project_type_id']) && $invoice['project_type_id']==$type['id']?' selected':''?>>
                    <?=$type['name']?>
                </option>
                <?php endforeach; ?>
            </select>
            <small style="color: #666; display: block; margin-top: 5px;"><i class="fa-solid fa-info-circle"></i> Categorize this invoice by service type for reporting</small>

            <label for="is_recurring" class="checkbox pad-bot-5" style="margin-top: 15px;">
                <input id="is_recurring" type="checkbox" name="is_recurring" value="1" <?=isset($invoice['is_recurring']) && $invoice['is_recurring'] ? 'checked' : ''?>>
                <i class="fa-solid fa-rotate"></i> Recurring Invoice
            </label>

            <div id="recurrence_options" style="<?=isset($invoice['is_recurring']) && $invoice['is_recurring'] ? '' : 'display:none;'?> margin-left: 25px; padding: 10px; background: #f5f5f5; border-left: 3px solid #6b46c1; margin-bottom: 15px;">
                <label for="recurrence_frequency">Recurrence Frequency</label>
                <select id="recurrence_frequency" name="recurrence_frequency">
                    <option value="none" <?=isset($invoice['recurrence_frequency']) && $invoice['recurrence_frequency']=='none'?' selected':''?>>None</option>
                    <option value="monthly" <?=isset($invoice['recurrence_frequency']) && $invoice['recurrence_frequency']=='monthly'?' selected':''?>>Monthly</option>
                    <option value="quarterly" <?=isset($invoice['recurrence_frequency']) && $invoice['recurrence_frequency']=='quarterly'?' selected':''?>>Quarterly</option>
                    <option value="annually" <?=isset($invoice['recurrence_frequency']) && $invoice['recurrence_frequency']=='annually'?' selected':''?>>Annually</option>
                </select>
                <small style="color: #666; display: block; margin-top: 5px;"><i class="fa-solid fa-info-circle"></i> How often this invoice should be automatically generated</small>
            </div>
            
            <?php if ($page == 'Create'): ?>
            <label for="send_email_checkbox" class="checkbox pad-bot-5" style="margin-top: 15px;">
                <input id="send_email_checkbox" type="checkbox" name="send_email" value="1" checked>
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
                <input type="text" class="search" id="payment_method" placeholder="Add payment method...">
                <div class="list">
                    <span data-value="PayPal">PayPal</span>
                    <span data-value="Cash">Cash</span>
                    <span data-value="Check">Check</span>
                    <span data-value="Bank Transfer">Bank Transfer</span>
                    <span data-value="Credit Card">Credit Card</span>
                </div>
            </div>
            <small style="color: #666; display: block; margin-top: 5px;"><i class="fa-solid fa-info-circle"></i> PayPal is recommended for online payments</small>

            <label for="due_date"><span class="required">*</span> Due Date</label>
            <input id="due_date" type="datetime-local" name="due_date" value="<?=date('Y-m-d\TH:i', strtotime($invoice['due_date']))?>" required>

            <label for="created"><span class="required">*</span> Created Date</label>
            <input id="created" type="datetime-local" name="created" value="<?=date('Y-m-d\TH:i', strtotime($invoice['created']))?>" required>

        </div>
        </div>
    </div>

    <!-- Section 2: Invoice Items (MOST IMPORTANT) -->
    <div class="content-block" style="margin-bottom: 30px; border: 3px solid #ff9800; border-radius: 8px; box-shadow: 0 4px 12px rgba(255, 152, 0, 0.2);">
        <div class="form-section">
            <div class="section-title" style="background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%); color: white; padding: 12px 20px; border-radius: 6px 6px 0 0; font-size: 16px; display: flex; align-items: center; gap: 10px;">
                <span style="background: white; color: #ff9800; width: 30px; height: 30px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: bold;">2</span>
                <i class="fa-solid fa-shopping-cart"></i> Invoice Items
                <span style="background: #fff3e0; color: #e65100; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; margin-left: auto;">⚠️ REQUIRED</span>
            </div>

            <div style="padding: 20px; background: #fff8e1;">
                <p style="margin: 0 0 15px 0; color: #e65100; font-weight: 500;">
                    <i class="fa-solid fa-exclamation-triangle"></i> <strong>Important:</strong> Add at least one item to this invoice. Items define what the client is being charged for.
                </p>

                <div class="table manage-invoice-table">
                    <table style="width: 100%;">
                        <thead>
                            <tr>
                                <td style="width: 25%;">Name</td>
                                <td style="width: 35%;">Description</td>
                                <td style="width: 15%;">Price</td>
                                <td style="width: 10%;">Qty</td>
                                <td style="width: 10%;">Total</td>
                                <td style="width: 5%;"></td>
                            </tr>
                        </thead>
                        <tbody class="invoice-items-tbody">
                            <?php if (empty($invoice_items)): ?>
                            <tr class="no-items-row">
                                <td colspan="6" class="no-invoice-items-msg no-results" style="background: #ffebee; color: #c62828; font-weight: 500; padding: 30px; text-align: center;">
                                    <i class="fa-solid fa-inbox" style="font-size: 48px; opacity: 0.3; display: block; margin-bottom: 10px;"></i>
                                    No items added yet. Click "Add Item" below to get started.
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($invoice_items as $item): ?>
                            <tr class="item-row">
                                <td><input type="hidden" name="item_id[]" value="<?=$item['id']?>"><input name="item_name[]" type="text" placeholder="Item name" value="<?=htmlspecialchars($item['item_name'], ENT_QUOTES)?>" required style="width: 100%;"></td>
                                <td><input name="item_description[]" type="text" placeholder="Description" maxlength="250" value="<?=htmlspecialchars($item['item_description'], ENT_QUOTES)?>" style="width: 100%;"></td>
                                <td><input name="item_price[]" type="number" placeholder="0.00" value="<?=$item['item_price']?>" step="0.01" class="item-price" style="width: 100%;"></td>
                                <td><input name="item_quantity[]" type="number" placeholder="1" value="<?=$item['item_quantity']?>" class="item-quantity" style="width: 100%;"></td>
                                <td class="item-total" style="font-weight: bold; padding: 10px;">$<?=number_format($item['item_price'] * $item['item_quantity'], 2)?></td>
                                <td style="text-align: center;"><svg class="delete-item" width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="cursor: pointer; fill: #d32f2f;"><title>Delete Item</title><path d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z" /></svg></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <div style="margin-top: 15px; display: flex; gap: 10px;">
                        <a href="#" class="add-item btn btn-primary" style="flex: 1;">
                            <i class="fa-solid fa-plus"></i> Add Item
                        </a>
                        <div style="flex: 1; text-align: right; font-size: 18px; font-weight: bold; padding: 10px 15px; background: #e3f2fd; border-radius: 6px; color: #1976d2;">
                            Subtotal: <span class="invoice-subtotal">$0.00</span>
                        </div>
                    </div>
                </div>

                <!-- Future: Service Selector (Coming Soon) -->
                <div style="margin-top: 20px; padding: 15px; background: #e8f5e9; border-left: 4px solid #4caf50; border-radius: 4px;">
                    <p style="margin: 0; color: #2e7d32; font-size: 14px;">
                        <i class="fa-solid fa-lightbulb"></i> <strong>Coming Soon:</strong> Select from your saved services catalog to quickly add items with pre-filled prices and descriptions.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 3: Advanced Settings (Tax, Template, Recurrence) -->
    <div class="content-block" style="margin-bottom: 30px;">
        <div class="form-section">
            <div class="section-title" style="background: #424242; color: white; padding: 12px 20px; border-radius: 6px 6px 0 0; font-size: 16px; display: flex; align-items: center; gap: 10px; cursor: pointer;" onclick="toggleAdvancedSettings()">
                <span style="background: white; color: #424242; width: 30px; height: 30px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: bold;">3</span>
                <i class="fa-solid fa-sliders"></i> Advanced Settings
                <i class="fa-solid fa-chevron-down" id="advanced-toggle-icon" style="margin-left: auto; transition: transform 0.3s;"></i>
            </div>

            <div id="advanced-settings-content" class="form responsive-width-100" style="padding: 20px;">

            <label for="tax">Tax</label>
            <input id="tax" type="text" name="tax" placeholder="% or fixed amount (e.g., 10% or 25.00)" value="<?=$invoice['tax'] == 'fixed' ? $invoice['tax_total'] : $invoice['tax']?>" step="0.01">
            <small style="color: #666; display: block; margin-top: 5px; margin-bottom: 15px;">Enter as percentage (10%) or fixed amount (25.00)</small>

            <label for="invoice_template">Invoice Template</label>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <?php foreach ($templates as $template): 
                    $template_name = basename($template);
                    $template_preview = file_exists($template . '/preview.png') ? $template . '/preview.png' : '';
                ?>
                <label class="template-card" style="cursor: pointer; border: 3px solid #e0e0e0; border-radius: 8px; padding: 10px; transition: all 0.3s; display: block; text-align: center; background: white;">
                    <input type="radio" name="invoice_template" value="<?=$template_name?>" <?=$invoice['invoice_template']==$template_name?' checked':''?> style="margin-bottom: 10px;">
                    <?php if ($template_preview): ?>
                    <img src="<?=str_replace(base_path, '../../client-invoices/', $template_preview)?>" alt="<?=$template_name?>" style="width: 100%; height: 150px; object-fit: cover; border-radius: 4px; margin-bottom: 8px; border: 1px solid #ddd;">
                    <?php else: ?>
                    <div style="width: 100%; height: 150px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 4px; margin-bottom: 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: 48px;">
                        <i class="fa-solid fa-file-invoice"></i>
                    </div>
                    <?php endif; ?>
                    <strong style="display: block; color: #333; text-transform: capitalize;"><?=$template_name?></strong>
                </label>
                <?php endforeach; ?>
            </div>

            <label for="notes">Invoice Notes</label>
            <textarea id="notes" name="notes" placeholder="Thank you for your business!" rows="4"><?=htmlspecialchars($invoice['notes'], ENT_QUOTES)?></textarea>

            <label for="recurrence">Recurring Invoice</label>
            <select id="recurrence" name="recurrence">
                <option value="0"<?=$invoice['recurrence']==0?' selected':''?>>No - One-time invoice</option>
                <option value="1"<?=$invoice['recurrence']==1?' selected':''?>>Yes - Recurring invoice</option>
            </select>

            <div class="recurrence-options"<?=$invoice['recurrence']==0 ? ' style="display:none"' : ''?>>
                <label for="recurrence_period">Recurrence Period</label>
                <input id="recurrence_period" type="number" name="recurrence_period" placeholder="1" min="1" value="<?=$invoice['recurrence_period']?>">

                <label for="recurrence_period_type">Recurrence Frequency</label>
                <select id="recurrence_period_type" name="recurrence_period_type">
                    <option value="day"<?=$invoice['recurrence_period_type']=='day'?' selected':''?>>Day(s)</option>
                    <option value="week"<?=$invoice['recurrence_period_type']=='week'?' selected':''?>>Week(s)</option>
                    <option value="month"<?=$invoice['recurrence_period_type']=='month'?' selected':''?>>Month(s)</option>
                    <option value="year"<?=$invoice['recurrence_period_type']=='year'?' selected':''?>>Year(s)</option>
                </select>
                <small style="color: #666; display: block; margin-top: 5px;">Client will be automatically invoiced every period</small>
            </div>

            <?php if ($page == 'Edit'): ?>
            <label for="paid_total">Amount Paid</label>
            <input id="paid_total" type="number" name="paid_total" placeholder="0.00" value="<?=$invoice['paid_total']?>" step="0.01">
            <?php endif; ?>

            <label for="due_date"><span class="required">*</span> Due Date</label>
            <input id="due_date" type="datetime-local" name="due_date" value="<?=date('Y-m-d\TH:i', strtotime($invoice['due_date']))?>" required>

            <label for="created"><span class="required">*</span> Created Date</label>
            <input id="created" type="datetime-local" name="created" value="<?=date('Y-m-d\TH:i', strtotime($invoice['created']))?>" required>

        </div>
       </div>
    </div>

    <div class="form-actions" style="position: sticky; bottom: 0; background: white; z-index: 100; padding: 15px 0; border-top: 2px solid #e0e0e0; margin-top: 20px;">
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

</form>

<script>
// Auto-preselect PayPal if no payment methods selected
document.addEventListener('DOMContentLoaded', function() {
    const multiselect = document.querySelector('.multiselect[data-name="payment_methods[]"]');
    if (multiselect) {
        const existingItems = multiselect.querySelectorAll('.item');
        
        // If creating new invoice and no payment methods, add PayPal
        if (existingItems.length === 0 && <?=$page == 'Create' ? 'true' : 'false'?>) {
            const paypalOption = multiselect.querySelector('.list span[data-value="PayPal"]');
            if (paypalOption) {
                paypalOption.click();
            }
        }
    }
});

// Toggle advanced settings
function toggleAdvancedSettings() {
    const content = document.getElementById('advanced-settings-content');
    const icon = document.getElementById('advanced-toggle-icon');
    if (content.style.display === 'none') {
        content.style.display = 'block';
        icon.style.transform = 'rotate(180deg)';
    } else {
        content.style.display = 'none';
        icon.style.transform = 'rotate(0deg)';
    }
}

// Calculate item totals and invoice subtotal
function calculateInvoiceTotals() {
    let subtotal = 0;
    const rows = document.querySelectorAll('.item-row');
    if (rows.length > 0) {
        rows.forEach(row => {
            const priceInput = row.querySelector('.item-price');
            const qtyInput = row.querySelector('.item-quantity');
            const totalCell = row.querySelector('.item-total');
            
            if (priceInput && qtyInput && totalCell) {
                const price = parseFloat(priceInput.value) || 0;
                const quantity = parseFloat(qtyInput.value) || 1;
                const total = price * quantity;
                totalCell.textContent = '$' + total.toFixed(2);
                subtotal += total;
            }
        });
    }
    const subtotalElement = document.querySelector('.invoice-subtotal');
    if (subtotalElement) {
        subtotalElement.textContent = '$' + subtotal.toFixed(2);
    }
}

// Update totals on price/quantity change
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('item-price') || e.target.classList.contains('item-quantity')) {
        calculateInvoiceTotals();
    }
});

// Calculate on page load
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(calculateInvoiceTotals, 500);
});

// Highlight selected template card
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.template-card').forEach(card => {
        const radio = card.querySelector('input[type="radio"]');
        if (radio && radio.checked) {
            card.style.borderColor = '#6b46c1';
            card.style.background = '#f3e5f5';
        }
        card.addEventListener('click', function() {
            document.querySelectorAll('.template-card').forEach(c => {
                c.style.borderColor = '#e0e0e0';
                c.style.background = 'white';
            });
            this.style.borderColor = '#6b46c1';
            this.style.background = '#f3e5f5';
        });
    });
});

// Recurrence toggle
const recurrenceSelect = document.getElementById('recurrence');
if (recurrenceSelect) {
    recurrenceSelect.addEventListener('change', function() {
        const options = document.querySelector('.recurrence-options');
        if (options) {
            if (this.value == '1') {
                options.style.display = 'block';
            } else {
                options.style.display = 'none';
            }
        }
    });
}

// Add Item functionality
document.addEventListener('DOMContentLoaded', function() {
    const addItemBtn = document.querySelector('.add-item');
    if (addItemBtn) {
        addItemBtn.addEventListener('click', function(event) {
            event.preventDefault();
            const tbody = document.querySelector('.invoice-items-tbody');
            if (tbody) {
                // Remove "no items" row if it exists
                const noItemsRow = tbody.querySelector('.no-items-row');
                if (noItemsRow) {
                    noItemsRow.remove();
                }
                
                // Add new item row
                const newRow = document.createElement('tr');
                newRow.className = 'item-row';
                newRow.innerHTML = `
                    <td><input type="hidden" name="item_id[]" value="0"><input name="item_name[]" type="text" placeholder="Item name" required style="width: 100%;"></td>
                    <td><input name="item_description[]" type="text" placeholder="Description" maxlength="250" style="width: 100%;"></td>
                    <td><input name="item_price[]" type="number" placeholder="0.00" step="0.01" value="0" class="item-price" style="width: 100%;"></td>
                    <td><input name="item_quantity[]" type="number" placeholder="1" value="1" class="item-quantity" style="width: 100%;"></td>
                    <td class="item-total" style="font-weight: bold; padding: 10px;">$0.00</td>
                    <td style="text-align: center;"><svg class="delete-item" width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="cursor: pointer; fill: #d32f2f;"><title>Delete Item</title><path d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z" /></svg></td>
                `;
                tbody.appendChild(newRow);
                
                // Attach delete handler to new row
                const deleteBtn = newRow.querySelector('.delete-item');
                if (deleteBtn) {
                    deleteBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        newRow.remove();
                        calculateInvoiceTotals();
                    });
                }
                
                // Recalculate totals
                calculateInvoiceTotals();
                
                // Focus on the new item's name field
                const nameInput = newRow.querySelector('input[name="item_name[]"]');
                if (nameInput) {
                    nameInput.focus();
                }
            }
        });
    }
    
    // Attach delete handlers to existing items
    document.querySelectorAll('.delete-item').forEach(function(element) {
        element.addEventListener('click', function(event) {
            event.preventDefault();
            element.closest('tr').remove();
            calculateInvoiceTotals();
        });
    });
    
    // Form validation before submission
    const invoiceForm = document.querySelector('form');
    if (invoiceForm) {
        invoiceForm.addEventListener('submit', function(event) {
            const clientSelect = document.getElementById('client_id');
            if (!clientSelect.value || clientSelect.value === '' || clientSelect.value === '0') {
                event.preventDefault();
                alert('Please select a client before submitting the invoice.');
                clientSelect.focus();
                clientSelect.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }
            
            // Check if at least one item exists
            const itemRows = document.querySelectorAll('.invoice-items-tbody .item-row');
            if (itemRows.length === 0) {
                event.preventDefault();
                alert('Please add at least one invoice item before submitting.');
                document.querySelector('.invoice-items-section').scrollIntoView({ behavior: 'smooth', block: 'start' });
                return false;
            }
        });
    }
});

// Domain selection auto-populate
document.addEventListener('DOMContentLoaded', function() {
    const domainSelect = document.getElementById('domain_id');
    if (domainSelect) {
        domainSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                const amount = selectedOption.getAttribute('data-amount');
                const dueDate = selectedOption.getAttribute('data-due-date');
                
                // Auto-populate first item if no items exist
                const firstItemPrice = document.querySelector('input[name="item_price[]"]');
                const firstItemName = document.querySelector('input[name="item_name[]"]');
                
                if (firstItemPrice && !firstItemPrice.value && amount) {
                    firstItemPrice.value = amount;
                }
                
                if (firstItemName && !firstItemName.value) {
                    const domainName = selectedOption.textContent.split(' - ')[0];
                    firstItemName.value = 'Domain Renewal: ' + domainName;
                }
                
                // Update due date if it matches the default 7-day value
                const dueDateInput = document.getElementById('due_date');
                if (dueDateInput && dueDate) {
                    dueDateInput.value = dueDate.replace(' ', 'T').substring(0, 16);
                }
                
                // Check the recurring checkbox and set to annually
                const recurringCheckbox = document.getElementById('is_recurring');
                const recurrenceFrequency = document.getElementById('recurrence_frequency');
                if (recurringCheckbox && !recurringCheckbox.checked) {
                    recurringCheckbox.checked = true;
                    recurringCheckbox.dispatchEvent(new Event('change'));
                }
                if (recurrenceFrequency) {
                    recurrenceFrequency.value = 'annually';
                }
                
                if (typeof calculateInvoiceTotals === 'function') {
                    calculateInvoiceTotals();
                }
            }
        });
    }
    
    // Recurring checkbox toggle
    const recurringCheckbox = document.getElementById('is_recurring');
    const recurrenceOptions = document.getElementById('recurrence_options');
    if (recurringCheckbox && recurrenceOptions) {
        recurringCheckbox.addEventListener('change', function() {
            if (this.checked) {
                recurrenceOptions.style.display = 'block';
                const freqSelect = document.getElementById('recurrence_frequency');
                if (freqSelect && freqSelect.value === 'none') {
                    freqSelect.value = 'annually';
                }
            } else {
                recurrenceOptions.style.display = 'none';
            }
        });
    }
});
</script>
<script>
// Also call the external function if it exists
if (typeof initManageInvoiceItems === 'function') {
    initManageInvoiceItems();
}
</script>

<?=template_admin_footer()?>