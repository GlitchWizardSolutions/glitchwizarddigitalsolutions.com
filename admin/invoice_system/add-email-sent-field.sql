-- Add email_sent field to invoices table to track whether invoice email was sent
-- Run this SQL in your database to add the field

ALTER TABLE invoices 
ADD COLUMN email_sent TINYINT(1) DEFAULT 0 AFTER viewed;

-- Update existing invoices - assume old invoices were sent if they are not new
UPDATE invoices 
SET email_sent = 1 
WHERE created < DATE_SUB(NOW(), INTERVAL 7 DAY);
