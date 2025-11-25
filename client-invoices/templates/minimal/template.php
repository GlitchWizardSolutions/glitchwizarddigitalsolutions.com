<?php defined('INVOICE') or exit; ?>
<!DOCTYPE html>
<html>
	<head>
		<title>Invoice #<?=$invoice['invoice_number']?></title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,minimum-scale=1">
     
       <style>* {
  box-sizing: border-box;
  font-family: system-ui, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
  font-size: 16px;
}

body, html {
  margin: 0;
  padding: 0;
  background-color: #f3f4f7;
}

.invoice {
  padding: 60px 15px 15px 15px;
}
.invoice .invoice-form {
  position: relative;
  display: flex;
  flex-flow: column;
  width: 100%;
  max-width: 500px;
  border-radius: 15px;
  margin: 0 auto;
  background-color: #fff;
  padding: 35px;
  box-shadow: 0px 0px 7px 1px rgba(45, 54, 68, 0.05);
}
.invoice .invoice-form .invoice-header .invoice-title {
  display: flex;
  align-items: flex-end;
  font-size: 28px;
  font-weight: 700;
  padding: 0;
  margin: 0;
  color: #4a5361;
}
.invoice .invoice-form .invoice-header .invoice-title strong {
  color: #5d6779;
  display: inline-block;
  font-weight: 600;
  padding: 0 0 5px 10px;
}
.invoice .invoice-form .invoice-header .invoice-title strong.paid {
  color: #2ecc71;
}
.invoice .invoice-form .invoice-header .invoice-title strong.unpaid, .invoice .invoice-form .invoice-header .invoice-title strong.cancelled {
  color: #e74c3c;
}
.invoice .invoice-form .invoice-header .invoice-title strong.pending {
  color: #f39c12;
}
.invoice .invoice-form .invoice-header .due-date, .invoice .invoice-form .invoice-header .invoice-from {
  font-size: 14px;
  font-weight: 500;
  color: #9fa4b1;
  margin: 0;
  padding: 0 0 10px 0;
}
.invoice .invoice-form .invoice-header .due-date {
  padding: 10px 0 25px 0;
  border-bottom: 1px solid #edeff3;
}
.invoice .invoice-form .invoice-header .company-logo {
  position: absolute;
  top: 35px;
  right: 35px;
  max-width: 100px;
  max-height: 60px;
  object-fit: contain;
}
.invoice .invoice-form .invoice-details {
  display: flex;
  flex-flow: column;
  padding: 15px 0;
}
.invoice .invoice-form .invoice-details .invoice-detail {
  display: flex;
  padding: 6px 0;
  align-items: center;
}
.invoice .invoice-form .invoice-details .invoice-detail h3 {
  font-size: 14px;
  font-weight: 600;
  color: #9fa4b1;
  margin: 0;
  padding: 0;
  width: 80px;
}
.invoice .invoice-form .invoice-details .invoice-detail p {
  font-size: 14px;
  font-weight: 500;
  color: #5d6779;
  margin: 0;
  padding: 0;
}
.invoice .invoice-form .invoice-items table {
  width: 100%;
  border-collapse: collapse;
}
.invoice .invoice-form .invoice-items table thead tr {
  border-bottom: 1px solid #f0f0f0;
}
.invoice .invoice-form .invoice-items table thead tr th {
  padding: 10px 0;
  font-size: 14px;
  font-weight: 600;
  color: #9fa4b1;
  text-align: left;
}
.invoice .invoice-form .invoice-items table thead tr th:first-child {
  padding-left: 0;
}
.invoice .invoice-form .invoice-items table thead tr th:last-child {
  padding-right: 0;
  text-align: right;
}
.invoice .invoice-form .invoice-items table tbody tr {
  border-bottom: 1px solid #f0f0f0;
}
.invoice .invoice-form .invoice-items table tbody tr td {
  padding: 10px 0;
  font-size: 14px;
  font-weight: 500;
  color: #5d6779;
  text-align: left;
}
.invoice .invoice-form .invoice-items table tbody tr td:first-child {
  padding-left: 0;
}
.invoice .invoice-form .invoice-items table tbody tr td:last-child {
  padding-right: 0;
  text-align: right;
}
.invoice .invoice-form .invoice-items table tbody tr td.total {
  text-align: right;
  color: #9fa4b1;
}
.invoice .invoice-form .invoice-items table tbody tr td .description {
  font-size: 12px;
  color: #9fa4b1;
  padding: 0;
  margin: 5px 0 0 0;
}
.invoice .invoice-form .invoice-items table tbody tr:nth-last-child(1 of .item) {
  border-bottom: 0;
}
.invoice .invoice-form .invoice-items table tbody tr.alt {
  border-bottom: 0;
}
.invoice .invoice-form .invoice-items table tbody tr.alt td {
  padding: 5px 0;
}
.invoice .invoice-form .payment-methods {
  padding: 30px 0 10px 0;
}
.invoice .invoice-form .payment-methods h3 {
  font-size: 14px;
  font-weight: 600;
  color: #9fa4b1;
  margin: 0 0 20px 0;
  padding: 0;
}
.invoice .invoice-form .payment-methods .methods {
  display: flex;
  flex-flow: wrap;
  width: 100%;
}
.invoice .invoice-form .payment-methods .methods label {
  text-decoration: none;
  display: flex;
  flex-flow: column;
  justify-content: center;
  border: 1px solid #edeff3;
  border-radius: 5px;
  height: 70px;
  width: 135px;
  font-size: 14px;
  font-weight: 500;
  color: #9fa4b1;
  padding: 0 15px;
  margin: 0 12px 18px 0;
  cursor: pointer;
}
.invoice .invoice-form .payment-methods .methods label svg {
  margin-bottom: 2px;
  fill: #9fa4b1;
}
.invoice .invoice-form .payment-methods .methods label:hover {
  border: 1px solid #ced4df;
}
.invoice .invoice-form .payment-methods .methods label:nth-child(6) {
  margin-right: 0;
}
.invoice .invoice-form .payment-methods .methods input {
  position: absolute;
  top: -9999px;
  left: -9999px;
  visibility: hidden;
}
.invoice .invoice-form .payment-methods .methods input:checked + label {
  color: #5d6779;
  border: 2px solid #5d6779;
}
.invoice .invoice-form .payment-methods .methods input:checked + label svg {
  fill: #5d6779;
}
.invoice .invoice-form .btns {
  display: flex;
  justify-content: flex-end;
}
.invoice .invoice-form .btns .btn {
  appearance: none;
  background: #0e75d6;
  box-shadow: 0px 0px 7px 1px rgba(45, 54, 68, 0.12);
  border: 0;
  border-radius: 4px;
  color: #FFFFFF;
  width: 135px;
  padding: 10px 0;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  margin-top: 5px;
}
.invoice .invoice-form .btns .btn:hover {
  background: #0c68be;
}

/* Responsive */
@media screen and (max-width: 768px) {
  .invoice .invoice-form {
    padding: 15px;
  }
  .invoice .invoice-form .invoice-header .company-logo {
    display: none;
  }
  .invoice .invoice-form .invoice-items table tbody tr td .description {
    display: none;
  }
  .invoice .invoice-form .payment-methods .methods label {
    width: 100%;
    margin-bottom: 10px;
  }
  .invoice .invoice-form .payment-methods .btn {
    width: 100%;
  }
}</style>
	</head>
	<body>
        <div class="invoice">
            <?php if (isset($_GET['payment_success']) && $_GET['payment_success'] == 'true'): ?>
            <div style="background:#d4edda;border:1px solid #c3e6cb;color:#155724;padding:15px;margin:0 auto 20px;max-width:500px;border-radius:5px;text-align:center;">
                <strong>✓ Payment Successful!</strong><br>
                <span style="font-size:14px;">Your payment has been processed successfully. Thank you!</span>
            </div>
            <?php endif; ?>
            <?php if (isset($_GET['payment_cancelled']) && $_GET['payment_cancelled'] == 'true'): ?>
            <div style="background:#fff3cd;border:1px solid #ffeaa7;color:#856404;padding:15px;margin:0 auto 20px;max-width:500px;border-radius:5px;text-align:center;">
                <strong>Payment Cancelled</strong><br>
                <span style="font-size:14px;">You cancelled the payment. You can try again anytime.</span>
            </div>
            <?php endif; ?>
            <form action="pay-invoice.php?id=<?=$invoice['invoice_number']?>" method="post" class="invoice-form">
                <div class="invoice-header">
                    <?php if (company_name): ?>
                    <p class="invoice-from">Invoice from <?=nl2br(company_name)?></p>
                    <?php endif; ?>
                    <?php 
                    $invoice_total = $invoice['payment_amount'] + $invoice['tax_total'];
                    $balance_due = isset($invoice['balance_due']) ? $invoice['balance_due'] : ($invoice_total - $invoice['paid_total']);
                    $display_amount = ($invoice['payment_status'] == 'Balance' && $balance_due > 0) ? $balance_due : $invoice_total;
                    ?>
                    <h1 class="invoice-title"><?=currency_code?><?=number_format($display_amount, 2)?><?=$invoice['payment_status'] != 'Unpaid' ? ' <strong class="' . strtolower($invoice['payment_status']) . '">' . $invoice['payment_status'] . '</strong>' : ''?></h1>
                    <?php if ($invoice['payment_status'] == 'Balance' && $balance_due > 0): ?>
                    <p class="due-date" style="color:#ff9800;font-weight:600;">Balance Due (Total: <?=currency_code?><?=number_format($invoice_total, 2)?> - Paid: <?=currency_code?><?=number_format($invoice['paid_total'], 2)?>)</p>
                    <?php endif; ?>
                    <p class="due-date">Due <?=date('F d, Y h:ia', strtotime($invoice['due_date']))?></p>
                    <?php if (company_logo): ?>
                    <img src="<?=company_logo?>" class="company-logo" alt="<?=company_name?>">
                    <?php endif; ?>
                </div>
                <div class="invoice-details">
                    <div class="invoice-detail">
                        <h3>From</h3>
                        <?php if (company_name): ?>
                        <p><?=nl2br(company_name)?></p>
                        <?php endif; ?>
                    </div>
                    <div class="invoice-detail">
                        <h3>To</h3>
                        <?php if ($client['first_name'] || $client['last_name']): ?>
                        <p><?=$client['first_name']?> <?=$client['last_name']?></p>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($invoice['notes'])): ?>
                    <div class="invoice-detail">
                        <h3>Notes</h3>
                        <p><?=nl2br(htmlspecialchars($invoice['notes'], ENT_QUOTES))?></p>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="invoice-items">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoice_items as $item): ?>
                            <tr class="item">
                                <td>
                                    <?=$item['item_name']?>
                                    <?php if ($item['item_description']): ?>
                                    <p class="description"><?=$item['item_description']?></p>
                                    <?php endif; ?>
                                </td>
                                <td><?=$item['item_quantity']?> x <?=currency_code?><?=number_format($item['item_price'], 2)?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="alt">
                                <td colspan="1" class="total">Subtotal</td>
                                <td class="num"><?=currency_code?><?=number_format($invoice['payment_amount'], 2)?></td>
                            </tr>
                            <?php if ($invoice['tax_total'] > 0): ?>
                            <tr class="alt">
                                <td colspan="1" class="total">Tax (<?=$invoice['tax']?>)</td>
                                <td class="num"><?=currency_code?><?=number_format($invoice['tax_total'], 2)?></td>
                            </tr>
                            <?php endif; ?>
                            <tr class="alt">
                                <td colspan="1" class="total">Total</td>
                                <td class="num"><?=currency_code?><?=number_format($invoice['payment_amount']+$invoice['tax_total'], 2)?></td>
                            </tr>
                            <?php if ($invoice['paid_total'] > 0): ?>
                            <tr class="alt">
                                <td colspan="1" class="total" style="color:#2ecc71;">Total Paid</td>
                                <td class="num" style="color:#2ecc71;font-weight:600;"><?=currency_code?><?=number_format($invoice['paid_total'], 2)?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ($invoice['payment_status'] == 'Pending' && $balance_due > 0): ?>
                            <tr class="alt">
                                <td colspan="1" class="total" style="color:#f39c12;">Pending Verification</td>
                                <td class="num" style="color:#f39c12;font-weight:600;"><?=currency_code?><?=number_format($balance_due, 2)?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ($invoice['payment_status'] == 'Balance' && $balance_due > 0): ?>
                            <tr class="alt">
                                <td colspan="1" class="total" style="color:#ff9800;font-weight:600;">Balance Due</td>
                                <td class="num" style="color:#ff9800;font-weight:700;font-size:16px;"><?=currency_code?><?=number_format($balance_due, 2)?></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($invoice['payment_status'] == 'Unpaid' || $invoice['payment_status'] == 'Balance'): ?>
                <div class="payment-methods">
                    <h3>Secure Payment</h3>
                    <p style="text-align:center;color:#7f8c8d;font-size:14px;margin:10px 0;">Pay securely with PayPal, Credit Card, Debit Card, or Buy Now Pay Later</p>
                    <div class="btns">
                        <a href="pay-invoice.php?id=<?=$invoice['invoice_number']?>" class="btn" style="background:#0070ba;text-decoration:none;display:inline-block;">💳 Pay Invoice</a>
                    </div>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </body>
</html>