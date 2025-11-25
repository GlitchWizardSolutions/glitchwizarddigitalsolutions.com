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
    'project_type_id' => null
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
        // Use existing invoice number from the fetched invoice record
        $invoice_number = $invoice['invoice_number'];
        // Update the invoice
        error_log("Update invoices: " . $_POST['client_id'] . "," . $invoice_number);
        $stmt = $pdo->prepare('UPDATE invoices SET client_id = ?, invoice_number = ?, payment_amount = ?, payment_status = ?, payment_methods = ?, notes = ?, due_date = ?, created = ?, tax = ?, tax_total = ?, invoice_template = ?, recurrence = ?, recurrence_period = ?, recurrence_period_type = ?, domain_id = ?, project_type_id = ? WHERE id = ?');
        $stmt->execute([ $_POST['client_id'], $invoice_number, $payment_amount, $_POST['payment_status'], $payment_methods, $_POST['notes'], $_POST['due_date'], $_POST['created'], $tax, $tax_total, $_POST['invoice_template'], $_POST['recurrence'], $_POST['recurrence_period'], $_POST['recurrence_period_type'], $_POST['domain_id'] ?: null, $_POST['project_type_id'] ?: null, $_GET['id'] ]);
        // add items
        addItems($pdo, $invoice_number);
        // Create PDF
        error_log("Create PDF: " . $invoice_number);
        $stmt = $pdo->prepare('SELECT * FROM invoices WHERE invoice_number = ?');
        $stmt->execute([ $invoice_number ]);
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
        $inv= substr($business_name['business_name'],0,6);
        $inv= str_replace(' ', '', $inv); 
        $inv= date('ymdH:i') . $inv; 
        // Insert the invoice
        $stmt = $pdo->prepare('INSERT INTO invoices (client_id, invoice_number, payment_amount, payment_status, payment_methods, notes, viewed, due_date, created, tax, tax_total, invoice_template, recurrence, recurrence_period, recurrence_period_type, domain_id, project_type_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([ $_POST['client_id'], $inv, $payment_amount, $_POST['payment_status'], $payment_methods, $_POST['notes'], 0, $_POST['due_date'], $_POST['created'], $tax, $tax_total, $_POST['invoice_template'], $_POST['recurrence'], $_POST['recurrence_period'], $_POST['recurrence_period_type'], $_POST['domain_id'] ?: null, $_POST['project_type_id'] ?: null ]);
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
        
        // Create notification for unpaid invoices only
        if ($_POST['payment_status'] != 'Paid') {
            $invoice_total = $payment_amount + $tax_total;
            $notification_message = "New invoice #{$inv} created - Amount due: $" . number_format($invoice_total, 2);
            
            $stmt = $pdo->prepare('INSERT INTO client_notifications (client_id, invoice_id, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())');
            $stmt->execute([$_POST['client_id'], $invoice['id'], $notification_message]);
        }
        
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

<div class="content-title responsive-flex-wrap responsive-pad-bot-3 mb-3">
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

<style>
.form-professional {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
}

.form-professional .form {
    max-width: 100% !important;
    width: 100% !important;
}

.form-professional label {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 8px;
    display: block;
    font-size: 14px;
}

.form-professional label i {
    color: #6b46c1;
    margin-right: 6px;
}

.form-professional input[type="text"],
.form-professional input[type="number"],
.form-professional input[type="datetime-local"],
.form-professional input[type="email"],
.form-professional select,
.form-professional textarea,
.form-professional .multiselect {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #6b46c1;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
    background: #ffffff;
    color: #2c3e50;
    margin-bottom: 20px;
    box-sizing: border-box;
}

.form-professional input[type="text"]:focus,
.form-professional input[type="number"]:focus,
.form-professional input[type="datetime-local"]:focus,
.form-professional input[type="email"]:focus,
.form-professional select:focus,
.form-professional textarea:focus {
    outline: none;
    border-color: #8e44ad;
    box-shadow: 0 0 0 3px rgba(107, 70, 193, 0.15);
    background: #ffffff;
}

.form-professional select {
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b46c1' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 16px center;
    padding-right: 40px;
}

.form-professional textarea {
    resize: vertical;
    min-height: 100px;
    font-family: inherit;
    line-height: 1.6;
}

.form-professional small {
    display: block;
    margin-top: -15px;
    margin-bottom: 15px;
    color: #7f8c8d;
    font-size: 13px;
    padding-left: 4px;
}

.form-professional small i {
    color: #3498db;
    margin-right: 4px;
}

.form-professional .required {
    color: #e74c3c;
    font-weight: bold;
    margin-right: 4px;
}

.form-professional .btn {
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.form-professional .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.section-title {
    background: linear-gradient(135deg, #6b46c1 0%, #8e44ad 100%);
    box-shadow: 0 2px 8px rgba(107, 70, 193, 0.2);
}

.content-block {
    border: 2px solid #6b46c1;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(107, 70, 193, 0.1);
}

.form-professional .checkbox {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.form-professional .checkbox:hover {
    background: #e9ecef;
}

.form-professional .checkbox input[type="checkbox"] {
    width: 20px;
    height: 20px;
    margin: 0;
    cursor: pointer;
}

.recurrence-options {
    background: #f8f4ff;
    border-left: 4px solid #6b46c1;
    padding: 20px;
    margin: 20px 0;
    border-radius: 0 8px 8px 0;
}

/* Grid Layout System */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 0;
}

.form-row-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.form-row .form-group,
.form-row-3 .form-group {
    margin-bottom: 0;
}

.form-group {
    margin-bottom: 20px;
    box-sizing: border-box;
    width: 100%;
}

.form-group label {
    margin-bottom: 8px;
}

.form-group input,
.form-group select,
.form-group textarea {
    margin-bottom: 0;
    width: 100%;
    box-sizing: border-box;
}

.form-group small {
    margin-top: 5px;
    margin-bottom: 0;
}

.form-row .btn {
    align-self: end;
    height: fit-content;
    margin-bottom: 0;
}

@media (max-width: 768px) {
    .form-row,
    .form-row-3 {
        grid-template-columns: 1fr;
    }
    
    /* Stack templates vertically on mobile */
    #invoice_template_grid {
        grid-template-columns: 1fr !important;
    }
}
</style>

<form action="" method="post" class="form-professional">

    <div class="form-actions" style="top: 0; z-index: 100; padding: 15px 0;  margin-bottom: 20px;">
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
            <a href="client.php" class="btn btn-primary">Add New Client</a>
            <!-- Client and Add New Client Button on same line -->
            <div class="form-row mt-3">
                
                <div class="form-group">
                    <label for="client_id"><span class="required">*</span> Client</label>
                    <select id="client_id" name="client_id" class="client_id" required>
                        <option value="">Select Invoice Client</option>
                        <?php foreach ($invoice_clients as $invoice_client): ?>
                        <option value="<?=$invoice_client['id']?>"<?=$invoice['client_id']==$invoice_client['id']?' selected':''?>><?=$invoice_client['business_name'] ?>&nbsp;[<?=$invoice_client['first_name']?>&nbsp;<?=$invoice_client['last_name']?>]</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
            </div>
            
            <!-- Domain and Service Category on same line -->
            <div class="form-row">
                <div class="form-group">
                    <label for="domain_id"><i class="fa-solid fa-globe"></i> Domain</label>
                    <select id="domain_id" name="domain_id">
                        <option value="">-- Select Domain --</option>
                        <?php foreach ($domains as $domain): ?>
                        <option value="<?=$domain['id']?>" <?=isset($invoice['domain_id']) && $invoice['domain_id']==$domain['id']?' selected':''?>>
                            <?=$domain['domain']?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                     
                </div>
                
                <div class="form-group">
                    <label for="project_type_id"><i class="fa-solid fa-layer-group"></i> Service Category</label>
                    <select id="project_type_id" name="project_type_id">
                        <option value="">-- Select Service Category --</option>
                        <?php foreach ($project_types as $type): ?>
                        <option value="<?=$type['id']?>" <?=isset($invoice['project_type_id']) && $invoice['project_type_id']==$type['id']?' selected':''?>>
                            <?=$type['name']?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    
                </div>
            </div>
            
            <!-- Invoice Number and Payment Status on same line -->
            <div class="form-row">
                <div class="form-group">
                    <label for ="invoice_number"><span class="required">*</span> Invoice Number</label>
                    <input id="invoice_number" type="text" name="invoice_number" placeholder="Automatically Generated" value="<?=htmlspecialchars($invoice['invoice_number']?? '', ENT_QUOTES)?>" disabled style="background-color: #f5f5f5; cursor: not-allowed;">
                </div>
                
                <div class="form-group">
                    <label for="payment_status">Payment Status</label>
                    <select id="payment_status" name="payment_status">
                        <option value="Unpaid"<?=$invoice['payment_status']=='Unpaid'?' selected':''?>>Unpaid</option>
                        <option value="Balance"<?=$invoice['payment_status']=='Balance'?' selected':''?>>Balance</option>
                        <option value="Pending"<?=$invoice['payment_status']=='Pending'?' selected':''?>>Pending</option>
                        <option value="Paid"<?=$invoice['payment_status']=='Paid'?' selected':''?>>Paid</option>
                        <option value="Gift"<?=$invoice['payment_status']=='Gift'?' selected':''?>>Gift</option>
                        <option value="Favor"<?=$invoice['payment_status']=='Favor'?' selected':''?>>Favor</option>
                        <option value="Cancelled"<?=$invoice['payment_status']=='Cancelled'?' selected':''?>>Cancelled</option>
                    </select>
                </div>
            </div>
            
            <?php if ($page == 'Edit' && isset($invoice['id'])): ?>
            <?php 
            // Calculate payment totals
            $invoice_total = $invoice['payment_amount'] + $invoice['tax_total'];
            $paid_total = $invoice['paid_total'];
            $balance_due = $invoice_total - $paid_total;
            ?>
            <!-- Payment Summary Box (only show when editing existing invoice) -->
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <h3 style="margin: 0 0 15px 0; font-size: 16px; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-money-bill-wave"></i> Payment Summary
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                    <div>
                        <div style="font-size: 12px; opacity: 0.9;">Invoice Total</div>
                        <div style="font-size: 20px; font-weight: bold;"><?=currency_code . number_format($invoice_total, 2)?></div>
                    </div>
                    <div>
                        <div style="font-size: 12px; opacity: 0.9;">Total Paid</div>
                        <div style="font-size: 20px; font-weight: bold;"><?=currency_code . number_format($paid_total, 2)?></div>
                    </div>
                    <div>
                        <div style="font-size: 12px; opacity: 0.9;">Balance Due</div>
                        <div style="font-size: 20px; font-weight: bold;"><?=currency_code . number_format($balance_due, 2)?></div>
                    </div>
                </div>
                <div style="margin-top: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
                    <?php if ($balance_due > 0): ?>
                    <a href="record_payment.php?invoice_id=<?=$invoice['id']?>" class="btn" style="background: white; color: #667eea; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-plus-circle"></i> Record Payment
                    </a>
                    <?php endif; ?>
                    <a href="payment_history.php?invoice_id=<?=$invoice['id']?>" class="btn" style="background: rgba(255,255,255,0.2); color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-history"></i> Payment History
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Paid Total (manual override - only for special cases) -->
            <div class="form-group" style="<?=$page == 'Edit' ? 'display:none;' : ''?>">
                <label for="paid_total">Paid Total (Manual Override)</label>
                <input type="number" id="paid_total" name="paid_total" step="0.01" value="<?=$invoice['paid_total']?>" placeholder="0.00">
                <small style="color: #6c757d; font-size: 12px;">⚠️ Use "Record Payment" button above instead of manual entry.</small>
            </div>

            <!-- Payment Methods (full width) -->
            <div class="form-group">
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
                </div>
            </div>
             
        </div>

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
                    <i class="fa-solid fa-exclamation-triangle"></i> <strong>Important:</strong> Add at least one item to this invoice.
                </p>

                <div class="table manage-invoice-table" style="padding: 15px; background: white; border-radius: 8px;">
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
                            <i class="fa-solid fa-plus"></i>&nbsp; Add Item
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
            <small><i class="fa-solid fa-info-circle"></i> Enter as percentage (10%) or fixed amount (25.00)</small>

            <label for="invoice_template">Invoice Template</label>
            <div id="invoice_template_grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px;">
                <?php foreach ($templates as $template): 
                    $template_name = basename($template);
                    $template_preview = file_exists($template . '/preview.png') ? $template . '/preview.png' : '';
                ?>
                <label class="template-card" style="cursor: pointer; border: 3px solid #6b46c1; border-radius: 8px; padding: 10px; transition: all 0.3s; display: block; text-align: center; background: white;">
                    <input type="radio" name="invoice_template" value="<?=$template_name?>" <?=$invoice['invoice_template']==$template_name?' checked':''?> style="margin-bottom: 10px;">
                    <?php if ($template_preview): ?>
                    <img src="<?=str_replace(base_path, '../../client-invoices/', $template_preview)?>" alt="<?=$template_name?>" style="width: 100%; height: 150px; object-fit: cover; border-radius: 4px; margin-bottom: 8px; border: 1px solid #ddd;">
                    <?php else: ?>
                    <div style="width: 100%; height: 150px; background: linear-gradient(135deg, #6b46c1 0%, #8e44ad 100%); border-radius: 4px; margin-bottom: 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: 48px;">
                        <i class="fa-solid fa-file-invoice"></i>
                    </div>
                    <?php endif; ?>
                    <strong style="display: block; color: #333; text-transform: capitalize;"><?=$template_name?></strong>
                </label>
                <?php endforeach; ?>
            </div>

            <!-- Invoice Notes (full width) -->
            <div class="form-group">
                <label for="notes">Invoice Notes</label>
                <textarea id="notes" name="notes" placeholder="Thank you for your business!" rows="4"><?=htmlspecialchars($invoice['notes'], ENT_QUOTES)?></textarea>
            </div>

            <!-- Send Email and Recurring Invoice on same row -->
            <div class="form-row">
                <?php if ($page == 'Create'): ?>
                <div class="form-group">
                    <label for="send_email_checkbox" class="checkbox">
                        <input id="send_email_checkbox" type="checkbox" name="send_email" value="1" checked>
                        <span>Send email to client</span>
                    </label>
                </div>
                <?php else: ?>
                <div></div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="recurrence">Recurring Invoice</label>
                    <select id="recurrence" name="recurrence">
                        <option value="0"<?=$invoice['recurrence']==0?' selected':''?>>No - One-time invoice</option>
                        <option value="1"<?=$invoice['recurrence']==1?' selected':''?>>Yes - Recurring invoice</option>
                    </select>
                </div>
            </div>

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
                <small><i class="fa-solid fa-info-circle"></i> Client will be automatically invoiced every period</small>
            </div>

            <!-- Created and Due Date on same row at the bottom -->
            <div class="form-row">
                <div class="form-group">
                    <label for="created"><span class="required">*</span> Created Date</label>
                    <input id="created" type="datetime-local" name="created" value="<?=date('Y-m-d\TH:i', strtotime($invoice['created']))?>" required>
                </div>
                
                <div class="form-group">
                    <label for="due_date"><span class="required">*</span> Due Date</label>
                    <input id="due_date" type="datetime-local" name="due_date" value="<?=date('Y-m-d\TH:i', strtotime($invoice['due_date']))?>" required>
                </div>
            </div>

        </div>
       </div>
    </div>

    <div class="form-actions" style="bottom: 0; z-index: 100; padding: 15px 0; margin-top: 20px;">
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
// Scroll to top and focus client dropdown on page load
document.addEventListener('DOMContentLoaded', function() {
    // Scroll to top of page
    window.scrollTo(0, 0);
    
    // Blur any auto-focused elements
    if (document.activeElement) {
        document.activeElement.blur();
    }
    
    // Focus on client dropdown instead of payment methods
    const clientDropdown = document.getElementById('client_id');
    if (clientDropdown) {
        setTimeout(function() {
            // Close any open multiselect dropdowns
            document.querySelectorAll('.multiselect .list').forEach(list => {
                list.style.display = 'none';
            });
            // Focus client dropdown
            clientDropdown.focus();
        }, 150);
    }
    
    // Auto-preselect PayPal if no payment methods selected
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
</script>
<script>
// Also call the external function if it exists
if (typeof initManageInvoiceItems === 'function') {
    initManageInvoiceItems();
}
</script>

<?=template_admin_footer()?>