<?php
// Production 2024-12-09
// Refresh    2025-06-14 VERIFIED
include_once 'assets/includes/admin_config.php';
// Check if the ID exists
if (!isset($_GET['id'])) {
    exit('Invalid ID!');
}
// Get the date
$date = date('Y-m-d H:i:s');
// Retrieve invoice items
$stmt = $pdo->prepare('SELECT i.*, ii.* FROM invoices i JOIN invoice_items ii ON ii.invoice_number = i.invoice_number WHERE i.id = ?');
$stmt->execute([ $_GET['id'] ]);
$invoice_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Retrieve invoice details
$stmt = $pdo->prepare('SELECT c.id AS c_id, i.*, c.* FROM invoices i LEFT JOIN invoice_items ii ON ii.invoice_number = i.invoice_number LEFT JOIN invoice_clients c ON c.id = i.client_id WHERE i.id = ?');
$stmt->execute([ $_GET['id'] ]);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);
// address
$address = [
    $invoice['address_street'],
    $invoice['address_city'],
    $invoice['address_state'],
    $invoice['address_zip'],
    $invoice['address_country']
];
$address = implode('<br>', array_filter($address));
// Check if the invoice exists
if (!$invoice) {
    exit('Invalid ID!');
}
?>
<?=template_admin_header('Invoice #' . $invoice['invoice_number'], 'invoices', 'invoices')?>
<div class="content-title">
    <div class="title">
       <i class="fa-solid fa-circle-info"></i>
        <div class="txt">
            <h2 class="responsive-width-100">Invoice # &nbsp;<?=$invoice['invoice_number']?><?php if ($invoice['viewed']): ?><svg width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><title>The client has viewed the invoice.</title><path d="M12,9A3,3 0 0,0 9,12A3,3 0 0,0 12,15A3,3 0 0,0 15,12A3,3 0 0,0 12,9M12,17A5,5 0 0,1 7,12A5,5 0 0,1 12,7A5,5 0 0,1 17,12A5,5 0 0,1 12,17M12,4.5C7,4.5 2.73,7.61 1,12C2.73,16.39 7,19.5 12,19.5C17,19.5 21.27,16.39 23,12C21.27,7.61 17,4.5 12,4.5Z" /></svg><?php endif; ?></h2>
            <p><?=' Invoice Status: ' ?> <?=$invoice['payment_status']?></p>
        </div>
    </div>
</div>
<br><br>
<div class="content-title responsive-flex-wrap responsive-pad-bot-3">
    <a href="invoices.php" class="btn alt mar-right-2">Cancel</a>
    <a href="invoices.php?delete=<?=$_GET['id']?>" class="btn btn-danger mar-right-2" onclick="return confirm('Are you sure you want to delete this invoice?')">Delete</a>
    <a href="invoice.php?id=<?=$_GET['id']?>" class="btn btn-success">Edit</a>
</div>

<div class="content-block-wrapper">
    <div class="content-block invoice-details">
        <div class="block-header">
            <div class="icon">
                <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M3 3V22L6 20L9 22L12 20L15 22L18 20L21 22V3H3M17 7V9H7V7H17M15 11V13H7V11H15Z" /></svg>
            </div>
            Invoice Details
        </div>
        <div class="invoice-detail">
            <h3>Invoice ID</h3>
            <p><?=$invoice['id']?></p>
        </div>
        <div class="invoice-detail">
            <h3>Invoice Number</h3>
            <p><?=$invoice['invoice_number']?></p>
        </div>
        <div class="invoice-detail">
            <h3>Payment Method(s)</h3>
            <p>
                <?php if ($invoice['payment_methods']): ?>
                <?php foreach (explode(',', $invoice['payment_methods']) as $method): ?>
                <span class="grey"><?=$method?></span>
                <?php endforeach; ?>    
                <?php endif; ?>
            </p>
        </div>
        <div class="invoice-detail">
            <h3>Payment Status</h3>
            <p>
                <?php if ($invoice['payment_status'] == 'Paid'): ?>
                <span class="green">Paid</span>
                <?php elseif ($invoice['payment_status'] == 'Cancelled'): ?>
                <span class="red">Cancelled</span>
                <?php elseif ($invoice['payment_status'] == 'Pending'): ?>
                <span class="orange">Pending</span>
                <?php elseif ($invoice['due_date'] < $date): ?>
                <span class="red">Overdue</span>
                <?php else: ?>
                <span class="red">Unpaid</span>
                <?php endif; ?>
            </p>
        </div>
        <?php if ($invoice['paid_with']): ?>
        <div class="invoice-detail">
            <h3>Paid With</h3>
            <p><?=$invoice['paid_with']?></p>
        </div>
        <?php endif; ?>
        <?php if ($invoice['payment_ref']): ?>
        <div class="invoice-detail">
            <h3>Transaction ID</h3>
            <p><?=$invoice['payment_ref']?></p>
        </div>
        <?php endif; ?>       
        <?php if ($invoice['paid_total']): ?>
        <div class="invoice-detail">
            <h3>Total Paid</h3>
            <p style="font-weight:500"><?=currency_code?><?=number_format($invoice['paid_total'], 2)?></p>
        </div>
        <?php endif; ?>
        <div class="invoice-detail">
            <h3>Due Date</h3>
            <p><?=date('F j, Y', strtotime($invoice['due_date']))?></p>
        </div>
        <div class="invoice-detail">
            <h3>Created</h3>
            <p><?=date('F j, Y', strtotime($invoice['created']))?></p>
        </div>
        <?php if (file_exists(client_side_invoice . 'pdfs/' . $invoice['invoice_number'] . '.pdf')): ?>
        <div class="invoice-detail">
            <h3>Attachment</h3>
            <p><a href="<?=client_side_invoice . 'pdfs/' . $invoice['invoice_number']?>.pdf" target="_blank" class="link1"><?=$invoice['invoice_number']?>.pdf</a></p>
        </div>
        <?php endif; ?>
        <div class="invoice-detail">
            <h3>Share Link</h3>
            <p style="max-width:250px"><a href="<?=client_side_invoice ?>invoice.php?id=<?=$invoice['invoice_number']?>" class="link1" target="_blank"><?=client_side_invoice ?>invoice.php?id=<?=$invoice['invoice_number']?></a></p>
        </div>       
    </div>

    <div class="content-block invoice-details">
        <div class="block-header">
            <div class="icon">
                <svg width="15" height="15" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z" /></svg>
            </div>
            Client Details
        </div>
        <?php if ($invoice['email']): ?>
        <div class="invoice-detail">
            <h3>Email</h3>
            <p><a href="client.php?id=<?=$invoice['c_id']?>" target="_blank" class="link1" style="margin:0"><?=htmlspecialchars($invoice['email'], ENT_QUOTES)?></a></p>
        </div>
        <div class="invoice-detail">
            <h3>Name</h3>
            <p><?=htmlspecialchars($invoice['first_name'], ENT_QUOTES)?> <?=htmlspecialchars($invoice['last_name'], ENT_QUOTES)?></p>
        </div>
        <div class="invoice-detail">
            <h3>Address</h3>
            <p style="text-align:right;"><?=$address?></p>
        </div>
        <?php else: ?>
        <p>The invoice is not associated with a client.</p>
        <?php endif; ?>
    </div>
  </div>
<div class="content-block-wrapper">
    <div class="content-block invoice-details">
        <div class="block-header">
            <div class="icon">
                <svg width="15" height="15" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M20.71,7.04C21.1,6.65 21.1,6 20.71,5.63L18.37,3.29C18,2.9 17.35,2.9 16.96,3.29L15.12,5.12L18.87,8.87M3,17.25V21H6.75L17.81,9.93L14.06,6.18L3,17.25Z" /></svg>
            </div>
            Notes
        </div>
        <div class="invoice-detail">
            <p><?=htmlspecialchars($invoice['notes'], ENT_QUOTES)?></p>
        </div>
    </div>
</div>

<div class="content-block">
    <div class="block-header">
        <div class="icon">
            <svg width="15" height="15" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M3,6H21V8H3V6M3,11H21V13H3V11M3,16H21V18H3V16Z" /></svg>
        </div>
        Invoice
    </div>
    <div class="table invoice-table">
        <table>
            <thead>
                <tr>
                    <td>Name</td>
                    <td>Description</td>
                    <td>Qty</td>
                    <td class="responsive-hidden">Price</td>
                    <td style="text-align:right;">Total</td>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($invoice_items)): ?>
                <tr>
                    <td colspan="20" class="no-results">There are no invoice items.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($invoice_items as $item): ?>
                <tr>
                    <td><?=htmlspecialchars($item['item_name'], ENT_QUOTES)?></td>
                    <td class="alt"><?=htmlspecialchars($item['item_description'], ENT_QUOTES)?></td>
                    <td><?=$item['item_quantity']?></td>
                    <td class="responsive-hidden"><?=currency_code?><?=number_format($item['item_price'], 2)?></td>
                    <td style="text-align:right;"><?=currency_code?><?=number_format($item['item_price']*$item['item_quantity'], 2)?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                <tr>
                    <td colspan="5" class="item-list-end"></td>
                </tr>
                <tr>
                    <td colspan="4" class="total">Subtotal</td>
                    <td class="num"><?=currency_code?><?=number_format($invoice['payment_amount'], 2)?></td>
                </tr>
                <?php if ($invoice['tax_total'] > 0): ?>
                <tr>
                    <td colspan="4" class="total">Tax (<?=$invoice['tax']?>)</td>
                    <td class="num"><?=currency_code?><?=number_format($invoice['tax_total'], 2)?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td colspan="4" class="total">Total</td>
                    <td class="num"><?=currency_code?><?=number_format($invoice['payment_amount']+$invoice['tax_total'], 2)?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?=template_admin_footer()?>