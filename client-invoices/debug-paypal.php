<?php
// Debug PayPal Configuration
include 'main.php';

echo "<h1>PayPal Configuration Debug</h1>";
echo "<pre>";
echo "ENVIRONMENT: " . (defined('ENVIRONMENT') ? ENVIRONMENT : 'NOT DEFINED') . "\n";
echo "paypal_testmode: " . (defined('paypal_testmode') ? (paypal_testmode ? 'TRUE' : 'FALSE') : 'NOT DEFINED') . "\n";
echo "paypal_client_id: " . (defined('paypal_client_id') ? paypal_client_id : 'NOT DEFINED') . "\n";
echo "paypal_base_url: " . (defined('paypal_base_url') ? paypal_base_url : 'NOT DEFINED') . "\n";
echo "\nExpected LIVE Client ID: ARB9yrLiAVr7RaCR637ubAN40_JiYF3kKDw5egGpZWP0-HcgbrLrt0X3ch2yB7hPTwt8GjlLyvyo7dTf\n";
echo "Expected SANDBOX Client ID: Ae7yzQFFXfQvMt2asv5_rowKWgwcIXhv4ulmdVHkwHEcDFHQcRrAf_2j0rnuLemA-tVwuT53ymJvS0aW\n";
echo "</pre>";
?>
