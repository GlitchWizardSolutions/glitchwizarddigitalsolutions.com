<?php
/**
 * Quick DNS Check - What is my server resolving Office 365 to?
 * 
 * SECURITY: Delete this file after use!
 * Access: https://glitchwizarddigitalsolutions.com/dns-check.php
 */

header('Content-Type: text/plain');

echo "=== OFFICE 365 SMTP DNS RESOLUTION CHECK ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "Server: " . $_SERVER['SERVER_NAME'] . "\n\n";

$hostnames = [
    'smtp.office365.com',
    'outlook.office365.com', 
    'smtp-mail.outlook.com',
];

foreach ($hostnames as $hostname) {
    echo "Checking: $hostname\n";
    echo str_repeat('-', 50) . "\n";
    
    // Simple resolution
    $ip = gethostbyname($hostname);
    echo "Primary IP: $ip\n";
    
    if ($ip === $hostname) {
        echo "❌ FAILED - Could not resolve hostname\n\n";
        continue;
    }
    
    // Check if it's a Microsoft IP range
    // Microsoft uses these ranges for Office 365
    $is_microsoft = (
        strpos($ip, '52.') === 0 ||
        strpos($ip, '40.') === 0 ||
        strpos($ip, '104.') === 0 ||
        strpos($ip, '13.') === 0 ||
        strpos($ip, '20.') === 0
    );
    
    if ($is_microsoft) {
        echo "✅ Looks like Microsoft IP (good!)\n";
    } else {
        echo "⚠️  NOT a typical Microsoft IP range\n";
        echo "⚠️  This might be DNS hijacking!\n";
    }
    
    // Try to get hostname from IP (reverse lookup)
    $reverse = gethostbyaddr($ip);
    if ($reverse !== $ip) {
        echo "Reverse lookup: $reverse\n";
        
        if (stripos($reverse, 'microsoft') !== false || 
            stripos($reverse, 'outlook') !== false ||
            stripos($reverse, 'office365') !== false) {
            echo "✅ Reverse DNS confirms Microsoft\n";
        } elseif (stripos($reverse, 'copperhead') !== false ||
                  stripos($reverse, 'digitalbackups') !== false) {
            echo "❌ HIJACKED - Points to hosting provider!\n";
        } else {
            echo "⚠️  Unexpected reverse DNS\n";
        }
    }
    
    echo "\n";
}

echo "\n=== INTERPRETATION ===\n";
echo "✅ = Resolving correctly to Microsoft\n";
echo "⚠️  = Suspicious, might be hijacked\n";
echo "❌ = Definitely hijacked or not working\n\n";

echo "Expected Microsoft IP ranges:\n";
echo "- 52.x.x.x\n";
echo "- 40.x.x.x\n";
echo "- 104.x.x.x\n";
echo "- 13.x.x.x\n";
echo "- 20.x.x.x\n\n";

echo "If ANY hostname shows copperhead/digitalbackups:\n";
echo "→ Your hosting provider is hijacking Office 365 SMTP\n";
echo "→ The multi-host failover approach should help\n";
echo "→ Consider contacting hosting provider\n\n";

echo "⚠️  DELETE THIS FILE AFTER CHECKING! ⚠️\n";
?>
