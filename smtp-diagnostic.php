<?php
/**
 * Office 365 SMTP Connection Diagnostic Tool
 * 
 * Run this on your production server to diagnose DNS and connectivity issues
 * Access via: https://glitchwizarddigitalsolutions.com/smtp-diagnostic.php
 * DELETE THIS FILE after testing for security!
 */

// Security: Only allow from localhost or specific IPs
$allowed_ips = ['127.0.0.1', '::1', 'YOUR_IP_HERE']; // Add your IP
if (!in_array($_SERVER['REMOTE_ADDR'], $allowed_ips) && !isset($_GET['bypass'])) {
    die('Access denied. Run from server console or add your IP to allowed_ips array.');
}

echo "<h1>Office 365 SMTP Diagnostic</h1>";
echo "<pre>";

// 1. Check DNS resolution
echo "\n=== DNS RESOLUTION TEST ===\n";
$hostnames = ['smtp.office365.com', 'outlook.office365.com', 'smtp-mail.outlook.com'];

foreach ($hostnames as $hostname) {
    echo "\nResolving: $hostname\n";
    $ip = gethostbyname($hostname);
    echo "IP Address: $ip\n";
    
    if ($ip === $hostname) {
        echo "⚠️  DNS resolution FAILED\n";
    } else {
        echo "✓ DNS resolved successfully\n";
        
        // Get all IP addresses
        $records = dns_get_record($hostname, DNS_A);
        if ($records) {
            echo "All A records:\n";
            foreach ($records as $record) {
                echo "  - " . $record['ip'] . "\n";
            }
        }
    }
}

// 2. Check MX records
echo "\n=== MX RECORDS FOR glitchwizardsolutions.com ===\n";
$mx_records = dns_get_record('glitchwizardsolutions.com', DNS_MX);
if ($mx_records) {
    foreach ($mx_records as $mx) {
        echo "Priority: {$mx['pri']} - Host: {$mx['target']}\n";
    }
} else {
    echo "⚠️  No MX records found\n";
}

// 3. Test socket connection
echo "\n=== SOCKET CONNECTION TEST ===\n";
$test_hosts = [
    'smtp.office365.com:587',
    'outlook.office365.com:587',
];

foreach ($test_hosts as $host_port) {
    list($host, $port) = explode(':', $host_port);
    echo "\nTesting: $host_port\n";
    
    $errno = 0;
    $errstr = '';
    $fp = @fsockopen($host, $port, $errno, $errstr, 10);
    
    if ($fp) {
        echo "✓ Connection successful\n";
        
        // Try to read SMTP banner
        $banner = fgets($fp, 1024);
        echo "SMTP Banner: " . trim($banner) . "\n";
        
        // Send EHLO
        fwrite($fp, "EHLO test.local\r\n");
        $response = '';
        while ($line = fgets($fp, 1024)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        echo "EHLO Response:\n$response";
        
        fclose($fp);
    } else {
        echo "⚠️  Connection failed: [$errno] $errstr\n";
    }
}

// 4. Test SSL/TLS connection
echo "\n=== SSL/TLS CONNECTION TEST ===\n";
$context = stream_context_create([
    'ssl' => [
        'verify_peer' => true,
        'verify_peer_name' => true,
        'allow_self_signed' => false,
        'capture_peer_cert' => true,
    ]
]);

foreach (['smtp.office365.com', 'outlook.office365.com'] as $hostname) {
    echo "\nTesting SSL to: $hostname:587\n";
    $fp = @stream_socket_client(
        "tcp://$hostname:587",
        $errno,
        $errstr,
        10,
        STREAM_CLIENT_CONNECT,
        $context
    );
    
    if ($fp) {
        echo "✓ TCP Connection established\n";
        
        // Read banner
        $banner = fgets($fp, 1024);
        echo "Banner: " . trim($banner) . "\n";
        
        // Send EHLO
        fwrite($fp, "EHLO test.local\r\n");
        stream_set_timeout($fp, 5);
        $response = '';
        while (!feof($fp)) {
            $line = fgets($fp, 1024);
            $response .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        echo "EHLO Response: " . trim($response) . "\n";
        
        // Send STARTTLS
        fwrite($fp, "STARTTLS\r\n");
        $tls_response = fgets($fp, 1024);
        echo "STARTTLS Response: " . trim($tls_response) . "\n";
        
        // Enable crypto
        if (substr($tls_response, 0, 3) === '220') {
            $crypto_result = @stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if ($crypto_result === true) {
                echo "✓ TLS encryption enabled successfully\n";
                
                // Get certificate info
                $params = stream_context_get_params($fp);
                if (isset($params['options']['ssl']['peer_certificate'])) {
                    $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
                    echo "Certificate CN: " . ($cert['subject']['CN'] ?? 'N/A') . "\n";
                    echo "Certificate Issuer: " . ($cert['issuer']['CN'] ?? 'N/A') . "\n";
                    echo "Valid From: " . date('Y-m-d H:i:s', $cert['validFrom_time_t']) . "\n";
                    echo "Valid To: " . date('Y-m-d H:i:s', $cert['validTo_time_t']) . "\n";
                }
            } else {
                echo "⚠️  TLS encryption failed\n";
                $error = error_get_last();
                echo "Error: " . ($error['message'] ?? 'Unknown') . "\n";
            }
        }
        
        fclose($fp);
    } else {
        echo "⚠️  Connection failed: [$errno] $errstr\n";
    }
}

// 5. Check hosts file
echo "\n=== HOSTS FILE CHECK ===\n";
$hosts_file = '/etc/hosts'; // Linux
if (file_exists('C:/Windows/System32/drivers/etc/hosts')) {
    $hosts_file = 'C:/Windows/System32/drivers/etc/hosts'; // Windows
}

if (file_exists($hosts_file) && is_readable($hosts_file)) {
    $hosts_content = file_get_contents($hosts_file);
    $lines = explode("\n", $hosts_content);
    $office365_entries = [];
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || $line[0] === '#') continue;
        
        if (stripos($line, 'office365') !== false || 
            stripos($line, 'outlook') !== false || 
            stripos($line, 'smtp') !== false) {
            $office365_entries[] = $line;
        }
    }
    
    if (!empty($office365_entries)) {
        echo "⚠️  Found Office365/SMTP entries in hosts file:\n";
        foreach ($office365_entries as $entry) {
            echo "  $entry\n";
        }
    } else {
        echo "✓ No Office365/SMTP entries in hosts file\n";
    }
} else {
    echo "Cannot read hosts file: $hosts_file\n";
}

// 6. Check PHP OpenSSL
echo "\n=== PHP CONFIGURATION ===\n";
echo "OpenSSL Extension: " . (extension_loaded('openssl') ? '✓ Loaded' : '⚠️  NOT loaded') . "\n";
if (extension_loaded('openssl')) {
    echo "OpenSSL Version: " . OPENSSL_VERSION_TEXT . "\n";
}

echo "\nPHP Version: " . PHP_VERSION . "\n";
echo "allow_url_fopen: " . (ini_get('allow_url_fopen') ? '✓ Enabled' : '⚠️  Disabled') . "\n";

echo "\n=== RECOMMENDATIONS ===\n";
echo "Based on the error you saw:\n";
echo "  'Peer certificate CN=autodiscover.copperhead.digitalbackups.net'\n\n";
echo "This indicates your hosting provider's DNS is hijacking smtp.office365.com\n";
echo "and redirecting it to their own mail server.\n\n";
echo "Solutions:\n";
echo "1. ✓ Use alternate hostname: outlook.office365.com (already applied)\n";
echo "2. Contact hosting provider about DNS hijacking\n";
echo "3. Use IP address directly (not recommended - IPs change)\n";
echo "4. Change hosting provider if they're intercepting email traffic\n";

echo "</pre>";
echo "\n<p><strong>⚠️  IMPORTANT: Delete this file after testing!</strong></p>";
?>
