# Centralized Email Template Manager - Specification

**Created:** November 22, 2025  
**Status:** Planning Phase  
**Priority:** High  
**Related System:** `/public_html/lib/email-system.php` (Unified Email System)

---

## 1. OVERVIEW

### Purpose
Create a centralized WYSIWYG email template management system that allows non-technical users to create, edit, preview, and test email templates used across all application systems (Invoice, Ticket, Legal, Newsletter).

### Current Pain Points
- ❌ Multiple separate email template editors scattered across subdirectories
- ❌ Plain HTML textarea editing requires HTML knowledge
- ❌ No preview capability before sending actual emails
- ❌ No image upload - requires manual URL entry
- ❌ No testing functionality
- ❌ Duplicate templates across systems
- ❌ No version control or history

### Goals
- ✅ Single centralized location for ALL email templates
- ✅ WYSIWYG TinyMCE editor (no HTML knowledge required)
- ✅ Image upload functionality built into editor
- ✅ Live preview with sample data
- ✅ Send test emails to yourself
- ✅ Template library and reusability
- ✅ Placeholder management system
- ✅ Version history tracking
- ✅ User-friendly interface matching invoice system redesign

---

## 2. DIRECTORY STRUCTURE

### New System Location
Since this works with the unified email system at `/public_html/lib/email-system.php`, create new directory:

```
/public_html/admin/email_system/
    email_templates.php          # Main template manager (list view)
    email_template.php           # Create/Edit template (WYSIWYG editor)
    preview_template.php         # Preview with sample data
    send_test_email.php          # Send test email handler
    upload_image.php             # Image upload handler for TinyMCE
    assets/
        includes/
            admin_config.php     # Config for email system admin
        css/
            email-templates.css  # Custom styles
        js/
            email-templates.js   # Custom JavaScript
    uploads/
        email-images/            # Uploaded images storage
            .htaccess            # Allow image access
```

### Database Table
```sql
CREATE TABLE IF NOT EXISTS email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(255) NOT NULL,
    template_slug VARCHAR(255) NOT NULL UNIQUE,
    template_type ENUM('invoice', 'ticket', 'legal', 'newsletter', 'system', 'custom') NOT NULL,
    subject_line VARCHAR(500) NOT NULL,
    html_content LONGTEXT NOT NULL,
    placeholders_available TEXT,
    is_active TINYINT(1) DEFAULT 1,
    is_default TINYINT(1) DEFAULT 0,
    version INT DEFAULT 1,
    created_by INT,
    created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_modified DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (template_type),
    INDEX idx_slug (template_slug),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_template_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL,
    version_number INT NOT NULL,
    subject_line VARCHAR(500) NOT NULL,
    html_content LONGTEXT NOT NULL,
    saved_by INT,
    saved_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES email_templates(id) ON DELETE CASCADE,
    INDEX idx_template (template_id),
    INDEX idx_version (version_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 3. PLACEHOLDER SYSTEM

### Standard Placeholders by Type

**Invoice Templates:**
```
%invoice_number%       - Invoice number (e.g., INV-2025-001)
%first_name%           - Client first name
%last_name%            - Client last name
%business_name%        - Client business name
%amount%               - Invoice amount formatted
%subtotal%             - Subtotal before tax
%tax_total%            - Tax amount
%due_date%             - Due date formatted
%invoice_link%         - Link to view invoice
%payment_status%       - Current payment status
%payment_methods%      - Available payment methods
%company_name%         - Your company name
%company_email%        - Your company email
%company_phone%        - Your company phone
```

**Ticket Templates:**
```
%ticket_id%            - Ticket ID number
%first_name%           - User first name
%last_name%            - User last name
%email%                - User email
%title%                - Ticket title
%message%              - Ticket message/description
%status%               - Ticket status
%priority%             - Ticket priority
%category%             - Ticket category
%ticket_link%          - Link to view ticket
%company_name%         - Your company name
%support_email%        - Support email address
```

**Legal System Templates:**
```
(Same as Ticket Templates - uses same structure)
```

**Newsletter Templates:**
```
%name%                 - Subscriber name
%email%                - Subscriber email
%open_tracking_code%   - Email open tracking pixel
%click_link%           - Tracked click link
%unsubscribe_link%     - Unsubscribe URL
%website_url%          - Your website URL
%date%                 - Current date
%time%                 - Current time
%year%                 - Current year
%month%                - Current month
%day%                  - Current day
```

**System Templates (2FA, Password Reset, etc.):**
```
%name%                 - User name
%email%                - User email
%verification_code%    - 2FA code
%reset_link%           - Password reset link
%activation_link%      - Account activation link
%expiry_time%          - Link expiration time
%company_name%         - Your company name
```

---

## 4. TINYMCE CONFIGURATION

### Required Features

**Image Upload Functionality:**
```javascript
tinymce.init({
    selector: '#html_content',
    plugins: 'image table lists media link code fullscreen preview',
    toolbar: 'undo redo | insert_placeholders | blocks | formatselect | bold italic underline forecolor backcolor | alignleft aligncenter alignright alignjustify | outdent indent | numlist bullist | table image link | preview code fullscreen',
    menubar: 'edit view insert format tools table',
    
    // IMAGE UPLOAD CONFIGURATION
    images_upload_url: 'upload_image.php',  // Upload handler
    automatic_uploads: true,                 // Auto-upload on paste/drop
    images_reuse_filename: true,
    file_picker_types: 'image',
    
    // File picker for image uploads
    file_picker_callback: function(callback, value, meta) {
        if (meta.filetype === 'image') {
            var input = document.createElement('input');
            input.setAttribute('type', 'file');
            input.setAttribute('accept', 'image/*');
            
            input.onchange = function() {
                var file = this.files[0];
                var reader = new FileReader();
                
                reader.onload = function() {
                    var formData = new FormData();
                    formData.append('file', file);
                    
                    fetch('upload_image.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.location) {
                            callback(result.location, {
                                alt: file.name,
                                title: file.name
                            });
                        }
                    });
                };
                
                reader.readAsDataURL(file);
            };
            
            input.click();
        }
    },
    
    // Other settings
    valid_elements: '*[*]',
    extended_valid_elements: '*[*]',
    valid_children: '+body[style]',
    content_css: false,
    height: 600,
    branding: false,
    promotion: false,
    license_key: 'gpl',
    
    // Custom placeholder insertion button
    setup: function(editor) {
        // Add placeholder button (defined per template type)
        editor.ui.registry.addMenuButton('insert_placeholders', {
            icon: 'code-sample',
            tooltip: 'Insert Placeholder',
            fetch: function(callback) {
                // Dynamic based on template_type
                callback(getPlaceholdersForType());
            }
        });
    }
});
```

### Image Upload Handler (upload_image.php)
```php
<?php
// Security checks
if (!isset($_FILES['file'])) {
    die(json_encode(['error' => 'No file uploaded']));
}

// Validate image
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$file_type = $_FILES['file']['type'];

if (!in_array($file_type, $allowed_types)) {
    die(json_encode(['error' => 'Invalid file type']));
}

// Size limit: 5MB
if ($_FILES['file']['size'] > 5242880) {
    die(json_encode(['error' => 'File too large. Max 5MB']));
}

// Generate unique filename
$upload_dir = __DIR__ . '/uploads/email-images/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$extension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
$filename = uniqid('email-img-', true) . '.' . $extension;
$filepath = $upload_dir . $filename;

// Move file
if (move_uploaded_file($_FILES['file']['tmp_name'], $filepath)) {
    // Return URL for TinyMCE
    $url = '../email_system/uploads/email-images/' . $filename;
    echo json_encode(['location' => $url]);
} else {
    echo json_encode(['error' => 'Upload failed']);
}
```

---

## 5. USER INTERFACE DESIGN

### Page 1: Template List (email_templates.php)

**Layout:**
```
┌─────────────────────────────────────────────────────────┐
│ [Icon] Email Templates                                  │
│ Manage all email templates across systems               │
│                                                          │
│ [+ New Template]  [Import]  [Settings]                  │
├─────────────────────────────────────────────────────────┤
│ Filter: [All ▼] [Invoice] [Ticket] [Legal] [Newsletter] │
│ Search: [________________]                               │
├─────────────────────────────────────────────────────────┤
│ Template Name          Type      Status    Modified     │
│ ─────────────────────────────────────────────────────── │
│ Invoice - New         Invoice    🟢 Active  Nov 20      │
│ Invoice - Reminder    Invoice    🟢 Active  Nov 15      │
│ Ticket - Created      Ticket     🟢 Active  Nov 10      │
│ Ticket - Reply        Ticket     🟢 Active  Nov 10      │
│                                                          │
│                                  [Edit] [Preview] [Test] │
└─────────────────────────────────────────────────────────┘
```

**Features:**
- Filter by template type
- Search by name
- Status indicators (Active/Inactive)
- Quick actions: Edit, Preview, Test, Delete
- Default template badges

### Page 2: Create/Edit Template (email_template.php)

**Layout:**
```
┌─────────────────────────────────────────────────────────┐
│ Create Email Template                    [Save] [Cancel]│
├─────────────────────────────────────────────────────────┤
│                                                          │
│ Template Name: [_____________________]                  │
│                                                          │
│ Template Type: [Invoice ▼]                              │
│                                                          │
│ Subject Line:  [Invoice #%invoice_number% from...   ]   │
│                                                          │
│ Available Placeholders:                                 │
│ %invoice_number% %first_name% %amount% [See All...]     │
│                                                          │
│ ┌──────────────────────────────────────────────────┐   │
│ │ [TinyMCE WYSIWYG Editor Area]                    │   │
│ │                                                   │   │
│ │ [Toolbar: Format | Bold Italic | Image | ...]    │   │
│ │                                                   │   │
│ │ Dear %first_name%,                               │   │
│ │                                                   │   │
│ │ Your invoice #%invoice_number% is ready...       │   │
│ │                                                   │   │
│ │ [Insert Image]                                   │   │
│ │                                                   │   │
│ └──────────────────────────────────────────────────┘   │
│                                                          │
│ [Save Template]  [Preview]  [Send Test Email]           │
└─────────────────────────────────────────────────────────┘
```

**Sections:**
1. **Template Info** - Name, type, subject
2. **Placeholder Reference** - Clickable to insert
3. **WYSIWYG Editor** - Full TinyMCE with image upload
4. **Actions** - Save, Preview, Test

### Page 3: Preview (preview_template.php)

**Modal/Popup Layout:**
```
┌─────────────────────────────────────────────────────────┐
│ Preview: Invoice - New                          [Close] │
├─────────────────────────────────────────────────────────┤
│ From: support@company.com                               │
│ To: sample@client.com                                   │
│ Subject: Invoice #INV-2025-001 from Company Name        │
├─────────────────────────────────────────────────────────┤
│                                                          │
│ [Rendered HTML Email Preview with Sample Data]          │
│                                                          │
│ Dear John,                                              │
│                                                          │
│ Your invoice #INV-2025-001 is ready for $1,250.00      │
│                                                          │
│ [View Invoice Button]                                   │
│                                                          │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

**Features:**
- Shows email headers (From, To, Subject)
- Renders HTML with sample placeholder data
- Full email formatting preview
- Button to send test email

### Page 4: Test Email Dialog

**Modal:**
```
┌──────────────────────────────────┐
│ Send Test Email                  │
├──────────────────────────────────┤
│ Send To: [admin@company.com  ]   │
│                                   │
│ Use Sample Data: [Yes ▼]         │
│                                   │
│ Note: Placeholders will be        │
│ replaced with sample values.      │
│                                   │
│ [Cancel]  [Send Test Email]       │
└──────────────────────────────────┘
```

---

## 6. INTEGRATION WITH EXISTING SYSTEMS

### Update Unified Email System Functions

**Modify `/public_html/lib/email-system.php`:**

```php
/**
 * Get email template from database
 * @param string $template_slug Template identifier
 * @param array $data Placeholder replacement data
 * @return array ['subject' => string, 'html' => string]
 */
function get_email_template($template_slug, $data = []) {
    global $pdo;
    
    // Get template from database
    $stmt = $pdo->prepare('SELECT subject_line, html_content, placeholders_available 
                           FROM email_templates 
                           WHERE template_slug = ? AND is_active = 1 
                           LIMIT 1');
    $stmt->execute([$template_slug]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        // Fallback to legacy file-based templates
        return get_legacy_email_template($template_slug, $data);
    }
    
    // Replace placeholders
    $placeholders = json_decode($template['placeholders_available'], true);
    $search = array_keys($data);
    $replace = array_values($data);
    
    $subject = str_replace($search, $replace, $template['subject_line']);
    $html = str_replace($search, $replace, $template['html_content']);
    
    return [
        'subject' => $subject,
        'html' => $html
    ];
}

/**
 * Updated send_client_invoice_email to use database templates
 */
function send_client_invoice_email($invoice, $client, $subject = '') {
    if (!mail_enabled) return false;
    
    $mail = new PHPMailer(true);
    
    try {
        configure_smtp_mail($mail);
        
        $mail->setFrom(mail_from, mail_name);
        $mail->addAddress($client['email'], trim($client['first_name'] . ' ' . $client['last_name']));
        
        if (!empty(reply_to_email)) {
            $mail->addReplyTo(reply_to_email, reply_to_name);
        }
        
        // Get template from database
        $template_data = [
            '%invoice_number%' => $invoice['invoice_number'],
            '%first_name%' => $client['first_name'],
            '%last_name%' => $client['last_name'],
            '%business_name%' => $client['business_name'],
            '%amount%' => number_format($invoice['payment_amount'] + $invoice['tax_total'], 2),
            '%subtotal%' => number_format($invoice['payment_amount'], 2),
            '%tax_total%' => number_format($invoice['tax_total'], 2),
            '%due_date%' => date('F j, Y', strtotime($invoice['due_date'])),
            '%invoice_link%' => BASE_URL . 'client-invoices/invoice.php?id=' . $invoice['invoice_number'],
            '%payment_status%' => $invoice['payment_status'],
            '%company_name%' => company_name,
            '%company_email%' => mail_from,
            '%company_phone%' => company_phone ?? ''
        ];
        
        $email_content = get_email_template('invoice-new', $template_data);
        
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = !empty($subject) ? $subject : $email_content['subject'];
        $mail->Body = $email_content['html'];
        $mail->AltBody = strip_tags($email_content['html']);
        
        // PDF attachment
        if (defined('pdf_attachments') && pdf_attachments && 
            file_exists(public_path . '/client-invoices/pdfs/' . $invoice['invoice_number'] . '.pdf')) {
            $mail->AddAttachment(public_path . '/client-invoices/pdfs/' . $invoice['invoice_number'] . '.pdf');
        }
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("INVOICE EMAIL ERROR: " . $mail->ErrorInfo);
        return false;
    }
}
```

### Template Slug Convention
```
invoice-new              # New invoice sent to client
invoice-reminder         # Payment reminder
invoice-receipt          # Payment receipt
invoice-admin-notify     # Admin notification of new invoice

ticket-created           # New ticket confirmation
ticket-reply             # Ticket reply notification
ticket-status-change     # Status changed
ticket-admin-notify      # Admin notification

legal-created            # Legal ticket created
legal-reply              # Legal reply
legal-status-change      # Legal status change

system-2fa               # Two-factor authentication
system-password-reset    # Password reset
system-activation        # Account activation
system-welcome           # Welcome email

newsletter-campaign      # Newsletter campaign
newsletter-confirmation  # Subscription confirmation
```

---

## 7. MIGRATION PLAN

### Phase 1: Setup Infrastructure
1. Create `/admin/email_system/` directory structure
2. Create database tables (`email_templates`, `email_template_versions`)
3. Create upload directory with proper permissions
4. Create `.htaccess` for image access

### Phase 2: Build Core Pages
1. `email_templates.php` - List view with filtering
2. `email_template.php` - Create/Edit with TinyMCE
3. `upload_image.php` - Image upload handler
4. `preview_template.php` - Preview functionality
5. `send_test_email.php` - Test email sender

### Phase 3: Migrate Existing Templates
Import existing templates into database:
```php
// Migration script: migrate_templates.php

// Invoice templates
$templates = [
    [
        'name' => 'Invoice - New',
        'slug' => 'invoice-new',
        'type' => 'invoice',
        'subject' => 'Invoice #%invoice_number% from ' . company_name,
        'file' => 'client-invoices/templates/client-email-template.html',
        'placeholders' => ['%invoice_number%', '%first_name%', '%amount%', '%due_date%', '%invoice_link%']
    ],
    [
        'name' => 'Invoice - Notification',
        'slug' => 'invoice-admin-notify',
        'type' => 'invoice',
        'subject' => 'New Invoice Created - #%invoice_number%',
        'file' => 'client-invoices/templates/notification-email-template.html',
        'placeholders' => ['%invoice_number%', '%client_name%', '%amount%']
    ],
    // ... ticket, legal, etc.
];

foreach ($templates as $tpl) {
    $html = file_get_contents(public_path . $tpl['file']);
    $stmt = $pdo->prepare('INSERT INTO email_templates 
        (template_name, template_slug, template_type, subject_line, html_content, placeholders_available, is_default) 
        VALUES (?, ?, ?, ?, ?, ?, 1)');
    $stmt->execute([
        $tpl['name'],
        $tpl['slug'],
        $tpl['type'],
        $tpl['subject'],
        $html,
        json_encode($tpl['placeholders'])
    ]);
}
```

### Phase 4: Update Email Functions
1. Add `get_email_template()` function to email-system.php
2. Update `send_client_invoice_email()` to use database templates
3. Update `send_ticket_email()` to use database templates
4. Add fallback to legacy file templates if not in database

### Phase 5: Admin Navigation
Add to admin menu in `admin/assets/includes/components.php`:
```php
// In navigation menu
[
    'label' => 'Email Templates',
    'url' => 'email_system/email_templates.php',
    'icon' => 'fa-envelope',
    'badge' => ''
]
```

---

## 8. SECURITY CONSIDERATIONS

### Image Upload Security
```php
// In upload_image.php
1. Validate file type (whitelist: jpg, png, gif, webp)
2. Validate file size (max 5MB)
3. Sanitize filename (use uniqid)
4. Store outside public_html if possible
5. Generate unique filenames (prevent overwrite)
6. Check for malicious content (getimagesize)
7. Set proper file permissions (0644)
```

### Template Editing Security
```php
1. Require admin authentication
2. Log all template changes
3. Version control (save old versions)
4. XSS protection on preview
5. SQL injection protection (prepared statements)
6. CSRF tokens on forms
```

### Email Sending Security
```php
1. Rate limiting on test emails
2. Validate email addresses
3. Prevent email injection
4. Sanitize placeholder data
5. Require confirmation for mass sends
```

---

## 9. FEATURES ROADMAP

### Phase 1 (MVP) - Week 1
- ✅ Basic CRUD for templates
- ✅ TinyMCE editor with image upload
- ✅ Template list with filtering
- ✅ Preview functionality
- ✅ Placeholder system

### Phase 2 - Week 2
- ✅ Send test emails
- ✅ Version history
- ✅ Template import/export
- ✅ Migration from legacy templates
- ✅ Integration with email-system.php

### Phase 3 - Week 3
- ⏳ Template categories/tags
- ⏳ Template duplication
- ⏳ Advanced placeholder conditions
- ⏳ A/B testing support
- ⏳ Email analytics

### Phase 4 - Future
- ⏳ Drag-drop email builder
- ⏳ Template marketplace
- ⏳ Multi-language templates
- ⏳ Scheduled template changes
- ⏳ AI-assisted writing

---

## 10. TESTING CHECKLIST

### Functional Testing
- [ ] Create new template
- [ ] Edit existing template
- [ ] Delete template
- [ ] Upload images via TinyMCE
- [ ] Insert placeholders
- [ ] Preview with sample data
- [ ] Send test email
- [ ] Filter templates by type
- [ ] Search templates
- [ ] Version history works
- [ ] Template activation/deactivation

### Integration Testing
- [ ] Invoice emails use database template
- [ ] Ticket emails use database template
- [ ] Legal emails use database template
- [ ] Fallback to legacy templates works
- [ ] All placeholders replaced correctly
- [ ] Images display in emails
- [ ] PDF attachments work
- [ ] Subject line variables work

### Security Testing
- [ ] Image upload validates file type
- [ ] File size limits enforced
- [ ] Malicious file upload blocked
- [ ] SQL injection prevented
- [ ] XSS attacks prevented
- [ ] CSRF protection active
- [ ] Authentication required
- [ ] Permissions checked

### Performance Testing
- [ ] Page load < 2 seconds
- [ ] Image upload < 5 seconds
- [ ] Preview renders quickly
- [ ] List handles 100+ templates
- [ ] Search is responsive
- [ ] Database queries optimized

---

## 11. DOCUMENTATION REQUIREMENTS

### User Documentation
- [ ] How to create email template
- [ ] How to use placeholders
- [ ] How to upload images
- [ ] How to preview and test
- [ ] Best practices guide
- [ ] Troubleshooting guide

### Developer Documentation
- [ ] Database schema
- [ ] API functions reference
- [ ] Integration guide
- [ ] Template slug conventions
- [ ] Placeholder naming standards
- [ ] Migration procedures

---

## 12. SUCCESS METRICS

### User Experience
- Users can create templates without HTML knowledge
- Image upload works seamlessly
- Preview shows accurate rendering
- Test emails arrive correctly

### System Efficiency
- All systems use centralized templates
- No duplicate template code
- Easy maintenance and updates
- Version control prevents mistakes

### Technical Quality
- Code follows existing patterns
- Security best practices met
- Performance benchmarks achieved
- Documentation complete

---

## IMPLEMENTATION NOTES

**Priority Items:**
1. Image upload functionality (USER BLOCKER)
2. WYSIWYG editor setup
3. Database structure
4. Basic CRUD operations
5. Preview functionality

**Dependencies:**
- Existing: `/public_html/lib/email-system.php`
- Existing: TinyMCE CDN (already used in newsletters)
- Existing: Admin authentication system
- Existing: Database connection in config.php

**Estimated Timeline:**
- Setup & Infrastructure: 2-3 hours
- Core Pages Development: 6-8 hours
- TinyMCE Integration: 3-4 hours
- Migration Scripts: 2-3 hours
- Testing & Bug Fixes: 4-6 hours
- **Total: 17-24 hours**

---

## NEXT STEPS

1. ✅ Review and approve specification
2. ⏳ Create database tables
3. ⏳ Build directory structure
4. ⏳ Implement image upload handler
5. ⏳ Create email_template.php with TinyMCE
6. ⏳ Create email_templates.php list view
7. ⏳ Implement preview functionality
8. ⏳ Add test email feature
9. ⏳ Migrate existing templates
10. ⏳ Update email-system.php integration
11. ⏳ Testing and QA
12. ⏳ Documentation
13. ⏳ Deploy to production

---

**End of Specification**
