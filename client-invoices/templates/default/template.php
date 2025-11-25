<?php
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Invoice #<?=$invoice['invoice_number']?></title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,minimum-scale=1">
 
      <?php /*  <link href="<?=template_path?>style.css" rel="stylesheet" type="text/css"> */?>
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
  max-width: 750px;
  margin: 0 auto;
  background-color: #fff;
  padding: 45px;
  box-shadow: 0px 0px 7px 1px rgba(45, 54, 68, 0.05);
}
.invoice .invoice-form .invoice-header .invoice-title {
  font-size: 28px;
  font-weight: 700;
  padding: 0;
  margin: 0;
  color: #4a5361;
}
.invoice .invoice-form .invoice-header .due-date {
  font-size: 14px;
  font-weight: 500;
  color: #9fa4b1;
  margin: 0;
  padding: 15px 0;
}
.invoice .invoice-form .invoice-header .company-logo {
  position: absolute;
  top: 45px;
  right: 45px;
  max-width: 200px;
  max-height: 80px;
  object-fit: contain;
}
.invoice .invoice-form .invoice-details {
  display: flex;
  flex-wrap: wrap;
  padding: 30px 0;
}
.invoice .invoice-form .invoice-details .invoice-from, .invoice .invoice-form .invoice-details .invoice-to, .invoice .invoice-form .invoice-details .invoice-meta {
  padding: 10px 0;
  flex: 1;
}
.invoice .invoice-form .invoice-details .invoice-from h3, .invoice .invoice-form .invoice-details .invoice-to h3, .invoice .invoice-form .invoice-details .invoice-meta h3 {
  font-size: 14px;
  font-weight: 600;
  color: #9fa4b1;
  margin: 0 0 10px 0;
  padding: 0;
}
.invoice .invoice-form .invoice-details .invoice-from p, .invoice .invoice-form .invoice-details .invoice-to p, .invoice .invoice-form .invoice-details .invoice-meta p {
  font-size: 14px;
  font-weight: 500;
  color: #5d6779;
  margin: 0;
  padding: 0;
  line-height: 1.6;
}
.invoice .invoice-form .invoice-details .invoice-from strong, .invoice .invoice-form .invoice-details .invoice-to strong, .invoice .invoice-form .invoice-details .invoice-meta strong {
  color: #1488e7;
  display: block;
  font-weight: 600;
}
.invoice .invoice-form .invoice-details .invoice-from strong.paid, .invoice .invoice-form .invoice-details .invoice-to strong.paid, .invoice .invoice-form .invoice-details .invoice-meta strong.paid {
  color: #2ecc71;
}
.invoice .invoice-form .invoice-details .invoice-from strong.unpaid, .invoice .invoice-form .invoice-details .invoice-from strong.cancelled, .invoice .invoice-form .invoice-details .invoice-to strong.unpaid, .invoice .invoice-form .invoice-details .invoice-to strong.cancelled, .invoice .invoice-form .invoice-details .invoice-meta strong.unpaid, .invoice .invoice-form .invoice-details .invoice-meta strong.cancelled {
  color: #e74c3c;
}
.invoice .invoice-form .invoice-details .invoice-from strong.pending, .invoice .invoice-form .invoice-details .invoice-to strong.pending, .invoice .invoice-form .invoice-details .invoice-meta strong.pending {
  color: #f39c12;
}
.invoice .invoice-form .invoice-details .invoice-from strong, .invoice .invoice-form .invoice-details .invoice-to strong {
  margin-bottom: 5px;
}
.invoice .invoice-form .invoice-details .invoice-meta {
  text-align: right;
}
.invoice .invoice-form .invoice-details .invoice-meta h3 {
  color: #9fa4b1;
  margin: 12px 0 4px 0;
}
.invoice .invoice-form .invoice-details .invoice-meta h3:first-child {
  margin-top: 0;
}
.invoice .invoice-form .invoice-details .invoice-meta strong {
  font-size: 14px;
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

/* Print styles */
@media print {
  .btn, button, .btns {
    display: none !important;
  }
  .invoice {
    padding: 0;
  }
  body {
    background-color: white;
  }
}
.invoice .invoice-form .invoice-items table tbody tr.alt {
  border-bottom: 0;
}
.invoice .invoice-form .invoice-items table tbody tr.alt td {
  padding: 5px 0;
}
.invoice .invoice-form .payment-methods {
  padding: 30px 0;
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
  width: 155px;
  font-size: 14px;
  font-weight: 500;
  color: #9fa4b1;
  padding: 0 15px;
  margin: 0 13px 18px 0;
  cursor: pointer;
}
.invoice .invoice-form .payment-methods .methods label svg {
  margin-bottom: 2px;
  fill: #9fa4b1;
}
.invoice .invoice-form .payment-methods .methods label:hover {
  border: 1px solid #ced4df;
}
.invoice .invoice-form .payment-methods .methods label:nth-child(8) {
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
.invoice .invoice-form .invoice-notes p {
  font-size: 14px;
  font-weight: 500;
  color: #5d6779;
  margin: 0;
  padding: 0;
  line-height: 1.6;
}
.invoice .invoice-form .payment-history {
  margin: 30px 0;
  background: #f8f9fa;
  padding: 20px;
  border-radius: 5px;
  border: 1px solid #e9ecef;
}
.invoice .invoice-form .payment-history h3 {
  font-size: 16px;
  font-weight: 600;
  color: #2d3644;
  margin: 0 0 15px 0;
}
.invoice .invoice-form .payment-history table {
  width: 100%;
  border-collapse: collapse;
}
.invoice .invoice-form .payment-history table th {
  background: #e9ecef;
  color: #495057;
  font-size: 13px;
  font-weight: 600;
  padding: 10px;
  text-align: left;
  border-bottom: 2px solid #dee2e6;
}
.invoice .invoice-form .payment-history table td {
  padding: 12px 10px;
  font-size: 14px;
  color: #5d6779;
  border-bottom: 1px solid #e9ecef;
}
.invoice .invoice-form .payment-history table tr:last-child td {
  border-bottom: none;
}
.invoice .invoice-form .payment-history table td.amount {
  font-weight: 600;
  color: #2ecc71;
  text-align: right;
}
.invoice .invoice-form .payment-history .no-payments {
  text-align: center;
  color: #9fa4b1;
  font-size: 14px;
  padding: 20px;
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
  width: 155px;
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
  .invoice .invoice-form .invoice-details .invoice-to, .invoice .invoice-form .invoice-details .invoice-from, .invoice .invoice-form .invoice-details .invoice-meta {
    margin-right: 20px;
  }
  .invoice .invoice-form .invoice-details .invoice-meta {
    text-align: left;
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
            <?php if (!empty($payment_message)): ?>
            <div style="background:<?=$payment_message_type=='success'?'#d4edda':($payment_message_type=='error'?'#f8d7da':'#d1ecf1')?>;border:1px solid <?=$payment_message_type=='success'?'#c3e6cb':($payment_message_type=='error'?'#f5c6cb':'#bee5eb')?>;color:<?=$payment_message_type=='success'?'#155724':($payment_message_type=='error'?'#721c24':'#0c5460')?>;padding:15px;margin:0 auto 20px;max-width:750px;border-radius:5px;text-align:center;">
                <strong><?=$payment_message_type=='success'?'✓ Payment Successful!':($payment_message_type=='error'?'⚠ Payment Error':'ℹ Information')?></strong><br>
                <span style="font-size:14px;"><?=$payment_message?></span>
            </div>
            <?php endif; ?>
            <?php if (isset($_GET['payment_cancelled']) && $_GET['payment_cancelled'] == 'true'): ?>
            <div style="background:#fff3cd;border:1px solid #ffeaa7;color:#856404;padding:15px;margin:0 auto 20px;max-width:750px;border-radius:5px;text-align:center;">
                <strong>Payment Cancelled</strong><br>
                <span style="font-size:14px;">You cancelled the payment. You can try again anytime.</span>
            </div>
            <?php endif; ?>
            <form action="pay-invoice.php?id=<?=$invoice['invoice_number']?>" method="post" class="invoice-form">
                <div class="invoice-header">
                    <h1 class="invoice-title">INVOICE</h1>
                    <p class="due-date">Due <?=date('F d, Y h:ia', strtotime($invoice['due_date']))?></p>
                    <?php if (company_logo): ?>
                    <img src="<?=company_logo?>" class="company-logo" alt="<?=company_name?>">
                    <?php endif; ?>
                </div>
                <div class="invoice-details">
                    <div class="invoice-from">
                        <h3>From</h3>
                        <?php if (company_name): ?>
                        <strong><?=nl2br(company_name)?></strong>
                        <?php endif; ?>
                        <?php if (company_address): ?>
                        <p><?=str_replace('\n', '<br>', company_address)?></p>
                        <?php endif; ?>
                       
                    </div>
                    <div class="invoice-to">
                        <h3>To</h3>
                        <?php if ($client['first_name'] || $client['last_name']): ?>
                        <strong><?=$client['first_name']?> <?=$client['last_name']?></strong>
                        <?php endif; ?>
                        <?php if ($client_address): ?>
                        <p><?=implode('<br>', $client_address)?></p>
                        <?php endif; ?>
                        <?php if ($client['email']): ?>
                        <p><?=$client['email']?></p>
                        <?php endif; ?>
                        <?php if ($client['phone']): ?>
                        <p><?=$client['phone']?></p>
                        <?php endif; ?>
                    </div>
                    <div class="invoice-meta">
                        <h3>Invoice #</h3>
                        <strong><?=$invoice['invoice_number']?></strong>
                        
                        <h3>Invoice Status</h3>
                        <?php if(isset($_GET['success']) && $_GET['success']=='true' && $invoice['payment_status'] != 'Paid'): ?>
                        <strong class="pending">Processing Payment...<br><small style="font-size: 12px; font-weight: normal;">Thank you! Your payment is being verified.</small></strong>
                        <script>
                        // Auto-refresh after 5 seconds to check if IPN has updated the status
                        setTimeout(function() {
                            window.location.href = 'invoice.php?id=<?=$invoice['invoice_number']?>';
                        }, 5000);
                        </script>
                        <?php else :?>
                        <strong class="<?=strtolower($invoice['payment_status'])?>"><?=$invoice['payment_status']?></strong>
                        <?php if($invoice['payment_status'] == 'Paid'): ?>
                        <br><small style="font-size: 12px; font-weight: normal; color: green;">✓ Payment received</small>
                        <br><button onclick="window.print()" class="btn" style="margin-top: 10px; background: #6c757d; color: white; padding: 8px 16px; font-size: 14px;"><i class="bi bi-printer"></i> Print Receipt</button>
                        <?php endif; ?>
                         <?php endif?>
                        
                        <?php 
                        $invoice_total = $invoice['payment_amount'] + $invoice['tax_total'];
                        $balance_due = isset($invoice['balance_due']) ? $invoice['balance_due'] : ($invoice_total - $invoice['paid_total']);
                        if ($invoice['paid_total'] > 0): 
                        ?>
                        <h3>Total Paid</h3>
                        <strong style="color: #2ecc71;"><?=currency_code?><?=number_format($invoice['paid_total'], 2)?></strong>
                        <?php endif; ?>
                        
                        <?php if ($invoice['payment_status'] == 'Balance' && $balance_due > 0): ?>
                        <h3>Balance Due</h3>
                        <strong style="color: #ff9800; font-size: 18px;"><?=currency_code?><?=number_format($balance_due, 2)?></strong>
                        <?php endif; ?>
                        
                        <h3>Invoice Date</h3>
                        <strong><?=date('F d, Y', strtotime($invoice['created']))?></strong>
                    </div>
                </div>
                <div class="invoice-items">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Total</th>
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
                                <td><?=$item['item_quantity']?></td>
                                <td><?=currency_code?><?=number_format($item['item_price'], 2)?></td>
                                <td><?=currency_code?><?=number_format($item['item_price'] * $item['item_quantity'], 2)?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="alt">
                                <td colspan="3" class="total">Subtotal</td>
                                <td class="num"><?=currency_code?><?=number_format($invoice['payment_amount'], 2)?></td>
                            </tr>
                            <?php if ($invoice['tax_total'] > 0): ?>
                            <tr class="alt">
                                <td colspan="3" class="total">Tax (<?=$invoice['tax']?>)</td>
                                <td class="num"><?=currency_code?><?=number_format($invoice['tax_total'], 2)?></td>
                            </tr>
                            <?php endif; ?>
                            <tr class="alt">
                                <td colspan="3" class="total">Total</td>
                                <td class="num"><?=currency_code?><?=number_format($invoice['payment_amount']+$invoice['tax_total'], 2)?></td>
                            </tr>
                            <?php if ($invoice['paid_total'] > 0): ?>
                            <tr class="alt">
                                <td colspan="3" class="total" style="color:#2ecc71;">Total Paid</td>
                                <td class="num" style="color:#2ecc71;font-weight:600;"><?=currency_code?><?=number_format($invoice['paid_total'], 2)?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ($invoice['payment_status'] == 'Pending' && $balance_due > 0): ?>
                            <tr class="alt">
                                <td colspan="3" class="total" style="color:#f39c12;">Pending Verification</td>
                                <td class="num" style="color:#f39c12;font-weight:600;"><?=currency_code?><?=number_format($balance_due, 2)?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ($invoice['payment_status'] == 'Balance' && $balance_due > 0): ?>
                            <tr class="alt">
                                <td colspan="3" class="total" style="color:#ff9800;font-weight:600;">Balance Due</td>
                                <td class="num" style="color:#ff9800;font-weight:700;font-size:16px;"><?=currency_code?><?=number_format($balance_due, 2)?></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (!empty($payment_history)): ?>
                <div class="payment-history">
                    <h3>💳 Payment History</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Transaction ID</th>
                                <th>Method</th>
                                <th style="text-align:right;">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payment_history as $payment): ?>
                            <tr>
                                <td><?=date('M j, Y g:i A', strtotime($payment['payment_date']))?></td>
                                <td style="font-family:monospace;font-size:12px;"><?=htmlspecialchars($payment['transaction_id'])?></td>
                                <td><?=htmlspecialchars($payment['payment_method'])?></td>
                                <td class="amount"><?=currency_code?><?=number_format($payment['amount_paid'], 2)?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                <?php if ($invoice['payment_status'] == 'Unpaid' || $invoice['payment_status'] == 'Balance'): ?>
                <div class="payment-methods">
                    <h3>Secure Payment</h3>
                    <p style="text-align:center;color:#7f8c8d;font-size:14px;margin:10px 0;">Pay securely with PayPal, Credit Card, Debit Card, or Buy Now Pay Later</p>
                    <div class="btns">
                        <a href="pay-invoice.php?id=<?=$invoice['invoice_number']?>" class="btn" style="background:#0070ba;text-decoration:none;display:inline-block;">💳 Pay Invoice</a>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($invoice['notes']): ?>
                <div class="invoice-notes">
                    <p><?=nl2br(htmlspecialchars($invoice['notes'], ENT_QUOTES))?></p>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </body>
</html>