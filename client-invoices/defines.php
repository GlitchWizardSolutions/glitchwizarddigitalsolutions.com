<?php
// Load centralized configuration for secure credentials
if (file_exists(__DIR__ . '/../../private/config.php')) {
    require_once __DIR__ . '/../../private/config.php';
}

/* Company */
// Your company name.
if(!defined('company_name')) define('company_name','GlitchWizard Solutions');
// Your company email address.
if(!defined('company_email')) define('company_email','webmaster@glitchwizardsolutions.com');
// Your company phone number.
if(!defined('company_phone')) define('company_phone','1-850-294-4226');
// Your company address.
if(!defined('company_address')) define('company_address','127 Northwood Road
\nCrawfordville, FL
\n32327
\nUnited States');
// Your company logo.
if(!defined('company_logo'))define('company_logo','purple-logo.png');
/* PayPal */
// Accept payments with PayPal?
if(!defined('paypal_enabled')) define('paypal_enabled',true);
// Your business email account, which is where you'll receive the payments.
if(!defined('paypal_email')) define('paypal_email','clarityconnect@glitchwizardsolutions.com');
if(!defined('pdf_attachments'))define('pdf_attachments',true);
/* Stripe */
// Accept payments with Stripe?
if(!defined('stripe_enabled')) define('stripe_enabled',false);
/* Coinbase */
// Accept payments with Coinbase?
if(!defined('coinbase_enabled')) define('coinbase_enabled',false);
/* Mail */
// If enabled, the website will send an email to the client when a new invoice is created.
if(!defined('mail_enabled')) define('mail_enabled',true);
// If enabled, you will receive email notifications when a new payment is received.
if(!defined('notifications_enabled')) define('notifications_enabled',true);
// The email address to send notification emails to.
if(!defined('notification_email')) define('notification_email','accounting@glitchwizardsolutions.com');
// Send mail from which address ON THIS SERVER?
if(!defined('mail_from')) define('mail_from','no_reply@GlitchwizardDigitalSolutions.com');
if(!defined('mail_name')) define('mail_name','GlitchWizard Solutions');
if(!defined('SMTP')) define('SMTP',true);
if(!defined('smtp_host')) define('smtp_host','mail.glitchwizarddigitalsolutions.com');
if(!defined('smtp_port')) define('smtp_port',465);
if(!defined('smtp_user')) define('smtp_user','no_reply@glitchwizarddigitalsolutions.com');
// SMTP password loaded from private/config.php
// The SMTP Secure connection type (ssl, tls).
if(!defined('smtp_secure'))define('smtp_secure','ssl');
// Reply-To address (where recipients should reply)
if(!defined('reply_to_email')) define('reply_to_email','barbara@glitchwizardsolutions.com');
if(!defined('reply_to_name')) define('reply_to_name','Barbara Moore');
/* Chron */
if(!defined('cron_mails_per_request')) define('cron_mails_per_request',1);
// Cron sleep per request in seconds
if(!defined('cron_sleep_per_request')) define('cron_sleep_per_request',5);
// The cron secret is used to prevent unauthorized access to the cron.php file. It should be a random string.
if(!defined('cron_secret')) define('cron_secret','MyInvoiceSecret2025');
/*Do Not Edit*/
if(!defined('base_path'))define("base_path", __DIR__ . '/');
?>