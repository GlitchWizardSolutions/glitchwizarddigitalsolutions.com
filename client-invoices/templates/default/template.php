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
                        </tbody>
                    </table>
                </div>
                <?php if ($invoice['payment_status'] == 'Unpaid' && !empty($payment_methods)): ?>
                <div class="payment-methods">
                    <h3>Payment Methods</h3>

                    <div class="methods">
                        <?php if (in_array('Cash', $payment_methods)): ?>
                        <input id="cash" type="radio" name="method" value="cash"<?=$payment_methods[0] == 'Cash' ? ' checked' : ''?>>
                        <label for="cash">
                            <svg width="32" height="32" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M3,6H21V18H3V6M12,9A3,3 0 0,1 15,12A3,3 0 0,1 12,15A3,3 0 0,1 9,12A3,3 0 0,1 12,9M7,8A2,2 0 0,1 5,10V14A2,2 0 0,1 7,16H17A2,2 0 0,1 19,14V10A2,2 0 0,1 17,8H7Z" /></svg>
                            Cash
                        </label>
                        <?php endif; ?>

                        <?php if (in_array('Bank Transfer', $payment_methods)): ?>
                        <input id="banktransfer" type="radio" name="method" value="banktransfer"<?=$payment_methods[0] == 'Bank Transfer' ? ' checked' : ''?>>
                        <label for="banktransfer">
                            <svg width="26" height="26" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M11.5,1L2,6V8H21V6M16,10V17H19V10M2,22H21V19H2M10,10V17H13V10M4,10V17H7V10H4Z" /></svg>
                            Bank Transfer
                        </label>
                        <?php endif; ?>
                        
                        <?php if (in_array('PayPal', $payment_methods) && paypal_enabled): ?>
                        <input id="paypal" type="radio" name="method" value="paypal"<?=$payment_methods[0] == 'PayPal' ? ' checked' : ''?>>
                        <label for="paypal">
                            <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M111.4 295.9c-3.5 19.2-17.4 108.7-21.5 134-.3 1.8-1 2.5-3 2.5H12.3c-7.6 0-13.1-6.6-12.1-13.9L58.8 46.6c1.5-9.6 10.1-16.9 20-16.9 152.3 0 165.1-3.7 204 11.4 60.1 23.3 65.6 79.5 44 140.3-21.5 62.6-72.5 89.5-140.1 90.3-43.4 .7-69.5-7-75.3 24.2zM357.1 152c-1.8-1.3-2.5-1.8-3 1.3-2 11.4-5.1 22.5-8.8 33.6-39.9 113.8-150.5 103.9-204.5 103.9-6.1 0-10.1 3.3-10.9 9.4-22.6 140.4-27.1 169.7-27.1 169.7-1 7.1 3.5 12.9 10.6 12.9h63.5c8.6 0 15.7-6.3 17.4-14.9 .7-5.4-1.1 6.1 14.4-91.3 4.6-22 14.3-19.7 29.3-19.7 71 0 126.4-28.8 142.9-112.3 6.5-34.8 4.6-71.4-23.8-92.6z"/></svg>
                            PayPal
                        </label>
                        <?php endif; ?>

                        <?php if (in_array('Stripe', $payment_methods) && stripe_enabled): ?>
                        <input id="stripe" type="radio" name="method" value="stripe"<?=$payment_methods[0] == 'Stripe' ? ' checked' : ''?>>
                        <label for="stripe">
                            <svg width="28" height="28" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M20 4H4A2 2 0 0 0 2 6V18A2 2 0 0 0 4 20H20A2 2 0 0 0 22 18V6A2 2 0 0 0 20 4M20 11H4V8H20Z" /></svg>
                            Stripe
                        </label>
                        <?php endif; ?>

                        <?php if (in_array('Cryptocurrency', $payment_methods) && coinbase_enabled): ?>
                        <input id="coinbase" type="radio" name="method" value="coinbase"<?=$payment_methods[0] == 'Coinbase' ? ' checked' : ''?>>
                        <label for="coinbase">
                            <svg width="28" height="28" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M17.06 11.57C17.65 10.88 18 10 18 9C18 7.14 16.73 5.57 15 5.13V3H13V5H11V3H9V5H6V7H8V17H6V19H9V21H11V19H13V21H15V19C17.21 19 19 17.21 19 15C19 13.55 18.22 12.27 17.06 11.57M10 7H14C15.1 7 16 7.9 16 9S15.1 11 14 11H10V7M15 17H10V13H15C16.1 13 17 13.9 17 15S16.1 17 15 17Z" /></svg>
                            Cryptocurrency
                        </label>
                        <?php endif; ?>
                    </div>
                    <div class="btns">
                        <button type="submit" class="btn">Pay Invoice</button>
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