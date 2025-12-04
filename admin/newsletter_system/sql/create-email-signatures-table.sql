-- Email Signatures Table
-- Stores email signature templates that can be applied to all outgoing emails
-- Created: December 4, 2025

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default signature template
INSERT INTO email_signatures (template_name, signature_html, signature_text, is_active) VALUES (
    'Default Signature',
    '<hr style="border: 0; border-top: 1px solid #ddd; margin: 20px 0;">
<p style="font-size: 12px; color: #666; line-height: 1.6;">
    <strong>Thank you, again!,<br>Me</strong><br><br>
    <a href="https://glitchwizarddigitalsolutions.com/" style="color: #6610f2; text-decoration: none;">Member Portal</a> | 
    <a href="https://glitchwizardsolutions.com/message-me.php" style="color: #6610f2; text-decoration: none;">Pricing</a>
</p>',
    '-----------------------------------
Thank you, again!,
Me

Member Portal: https://glitchwizarddigitalsolutions.com/
Pricing: https://glitchwizardsolutions.com/message-me.php',
    1
);

-- Insert holiday/special event template example
INSERT INTO email_signatures (template_name, signature_html, signature_text, is_active) VALUES (
    'Holiday Signature',
    '<hr style="border: 0; border-top: 1px solid #ddd; margin: 20px 0;">
<p style="font-size: 12px; color: #666; line-height: 1.6;">
    <strong>Happy Holidays!<br>Thank you, again!,<br>Me</strong><br><br>
    <a href="https://glitchwizarddigitalsolutions.com/" style="color: #6610f2; text-decoration: none;">Member Portal</a> | 
    <a href="https://glitchwizardsolutions.com/message-me.php" style="color: #6610f2; text-decoration: none;">Pricing</a>
</p>',
    '-----------------------------------
Happy Holidays!
Thank you, again!,
Me

Member Portal: https://glitchwizarddigitalsolutions.com/
Pricing: https://glitchwizardsolutions.com/message-me.php',
    0
);
