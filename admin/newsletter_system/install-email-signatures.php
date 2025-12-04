<?php
// Install email signatures table
require_once(__DIR__ . '/../../../private/config.php');

try {
    $pdo = new PDO('mysql:host=' . db_host . ';dbname=' . db_name . ';charset=utf8', db_user, db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Creating email_signatures table...</h2>";
    
    // Execute SQL statements one at a time
    
    // 1. Create table
    echo "<p>Creating table structure...</p>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS email_signatures (
            id INT AUTO_INCREMENT PRIMARY KEY,
            template_name VARCHAR(100) NOT NULL DEFAULT 'Default Signature',
            signature_html TEXT NOT NULL,
            signature_text TEXT NOT NULL,
            include_do_not_reply TINYINT(1) DEFAULT 1,
            do_not_reply_text VARCHAR(500) DEFAULT 'This is an automated message. Please do not reply to this email, as the mailbox is not monitored.',
            is_active TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            updated_by INT,
            FOREIGN KEY (updated_by) REFERENCES accounts(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // 2. Insert default signature
    echo "<p>Inserting default signature template...</p>";
    $default_html = '<hr style="border: 0; border-top: 1px solid #ddd; margin: 20px 0;">
<p style="font-size: 12px; color: #666; line-height: 1.6;">
    <strong>Thank you, again!,<br>Me</strong><br><br>
    <a href="https://glitchwizarddigitalsolutions.com/" style="color: #6610f2; text-decoration: none;">Member Portal</a> | 
    <a href="https://glitchwizardsolutions.com/message-me.php" style="color: #6610f2; text-decoration: none;">Pricing</a>
</p>';
    
    $default_text = '-----------------------------------
Thank you, again!,
Me

Member Portal: https://glitchwizarddigitalsolutions.com/
Pricing: https://glitchwizardsolutions.com/message-me.php';
    
    $stmt = $pdo->prepare("INSERT INTO email_signatures (template_name, signature_html, signature_text, is_active) VALUES (?, ?, ?, ?)");
    $stmt->execute(['Default Signature', $default_html, $default_text, 1]);
    
    // 3. Insert holiday signature example
    echo "<p>Inserting holiday signature template...</p>";
    $holiday_html = '<hr style="border: 0; border-top: 1px solid #ddd; margin: 20px 0;">
<p style="font-size: 12px; color: #666; line-height: 1.6;">
    <strong>Happy Holidays!<br>Thank you, again!,<br>Me</strong><br><br>
    <a href="https://glitchwizarddigitalsolutions.com/" style="color: #6610f2; text-decoration: none;">Member Portal</a> | 
    <a href="https://glitchwizardsolutions.com/message-me.php" style="color: #6610f2; text-decoration: none;">Pricing</a>
</p>';
    
    $holiday_text = '-----------------------------------
Happy Holidays!
Thank you, again!,
Me

Member Portal: https://glitchwizarddigitalsolutions.com/
Pricing: https://glitchwizardsolutions.com/message-me.php';
    
    $stmt->execute(['Holiday Signature', $holiday_html, $holiday_text, 0]);
    
    echo "<h3 style='color: green;'>✓ Email signatures table created successfully!</h3>";
    echo "<p>Default signature template is now active.</p>";
    echo "<p><strong><a href='email-signature.php'>Go to Email Signature Management →</a></strong></p>";
    
} catch (PDOException $e) {
    echo "<h3 style='color: red;'>Error: " . $e->getMessage() . "</h3>";
    echo "<p>If the table already exists, you can safely ignore this error.</p>";
    echo "<p><a href='email-signature.php'>Try accessing Email Signature Management anyway →</a></p>";
}
?>
