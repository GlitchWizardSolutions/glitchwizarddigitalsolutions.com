<?php
// Your MySQL database hostname.
define('db_host','localhost');
// Your MySQL database username.
define('db_user','root');
// Your MySQL database password.
define('db_pass','');
// Your MySQL database name.
define('db_name','phpticket_advanced');
// Your MySQL database charset.
define('db_charset','utf8');
/* Tickets */
// Authentication will require the user to login or register before they can create a ticket.
define('authentication_required',true);
// If enabled, the ticket will require approval.
define('approval_required',false);
// The number of tickets to show per page.
define('num_tickets_per_page',5);
// If enabled, the user will be able to attach files to the ticket.
define('attachments',true);
// The list of attachment types allowed to be uploaded.
define('attachments_allowed','png,jpg,jpeg,gif,webp,bmp,doc,docx,xls,xlsx,ppt,pptx,pdf,zip,rar,txt,csv');
// The directory where the uploaded files will be stored.
define('uploads_directory','uploads/');
// The maximum size of the uploaded image in bytes (5MB default).
define('max_allowed_upload_file_size',5000000);
// The maximum size of the title in characters.
define('max_title_length',200);
// The maximum size of the message in characters.
define('max_msg_length',1000);
// The tickets directory URL (e.g. http://example.com/ticketsystem/).
define('tickets_directory_url','http://example.com/ticketsystem/');
/* Mail */
// Send mail to the users, etc?
define('mail_enabled',false);
// This is the email address that will be used to send emails.
define('mail_from','noreply@example.com');
// The name of your business.
define('mail_name','Your Business Name');
// If enabled, the mail will be sent using SMTP.
define('SMTP',false);
// Your SMTP hostname.
define('smtp_host','smtp.example.com');
// Your SMTP port number.
define('smtp_port',465);
// Your SMTP username.
define('smtp_user','user@example.com');
// Your SMTP Password.
define('smtp_pass','');
?>