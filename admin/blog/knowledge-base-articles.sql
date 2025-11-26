-- Knowledge Base Articles SQL Script
-- Run this in glitchwizarddigi_envato_blog_db database
-- This will create 6 initial articles with proper categories

-- First, let's make sure we have the Getting Started category
INSERT INTO categories (name, description) VALUES 
('Getting Started', 'Essential guides to help you navigate the client portal')
ON DUPLICATE KEY UPDATE description = 'Essential guides to help you navigate the client portal';

-- Get the category ID (you may need to adjust this based on your existing categories)
SET @getting_started_cat = (SELECT id FROM categories WHERE name = 'Getting Started' LIMIT 1);

-- Article 1: How to Use the Knowledge Base (FEATURED)
INSERT INTO posts (title, slug, content, image, date, category, active, featured, author) VALUES (
'How to Use the Knowledge Base',
'how-to-use-knowledge-base',
'<div class="kb-article">
<h2>Welcome to Your Knowledge Base</h2>
<p>The Knowledge Base is your go-to resource for step-by-step guides on using every feature of the client portal. Think of it as your personal instruction manual, available 24/7.</p>

<h3>Accessing the Knowledge Base</h3>
<p>You can access the Knowledge Base from anywhere in the portal:</p>
<ol>
<li>Look for <strong>"Knowledge Base"</strong> in the main navigation menu</li>
<li>Click to view all available articles</li>
</ol>
<img src="/client-dashboard/blog/assets/img/kb/kb-overview-menu.png" alt="Knowledge Base menu location" class="img-fluid my-3">

<h3>Finding What You Need</h3>
<p>The Knowledge Base homepage shows you:</p>
<ul>
<li><strong>Featured Articles</strong> - The most important guides displayed in a carousel at the top</li>
<li><strong>Recent Articles</strong> - All articles organized by category below</li>
<li><strong>Categories</strong> - Articles grouped by topic (Getting Started, Portal Features, etc.)</li>
</ul>
<img src="/client-dashboard/blog/assets/img/kb/kb-overview-homepage.png" alt="Knowledge Base homepage" class="img-fluid my-3">

<h3>Browsing by Category</h3>
<p>To filter articles by category:</p>
<ol>
<li>Click on any category name</li>
<li>View all articles in that category</li>
<li>Click "All Categories" to return to the full list</li>
</ol>
<img src="/client-dashboard/blog/assets/img/kb/kb-overview-categories.png" alt="Category navigation" class="img-fluid my-3">

<h3>Reading an Article</h3>
<p>When you find an article you need:</p>
<ol>
<li>Click on the article title or image</li>
<li>Read through the step-by-step instructions</li>
<li>Follow along with the screenshots and arrows</li>
<li>Use the "Back" button or navigation menu to return to the list</li>
</ol>

<h3>Tips for Best Results</h3>
<ul>
<li>📱 Articles work on mobile, tablet, and desktop</li>
<li>🔍 Use your browser''s search (Ctrl+F) to find specific words on long articles</li>
<li>🔖 Bookmark articles you use frequently</li>
<li>💬 Leave a comment if you have questions or suggestions</li>
<li>📧 Contact us through the ticketing system if you need personal assistance</li>
</ul>

<div class="alert alert-info mt-4">
<strong>💡 Pro Tip:</strong> Start with the Getting Started category if you''re new to the portal. These articles will help you understand the basics before diving into advanced features.
</div>
</div>',
'/client-dashboard/blog/assets/img/kb/kb-overview-homepage.png',
NOW(),
@getting_started_cat,
'Yes',
'Yes',
'Admin'
);

-- Article 2: How to Use the Ticketing System
INSERT INTO posts (title, slug, content, image, date, category, active, featured, author) VALUES (
'How to Use the Ticketing System',
'how-to-use-ticketing-system',
'<div class="kb-article">
<h2>Understanding the Ticketing System</h2>
<p>The ticketing system is your direct line of communication with us for anything related to your website project. Whether you need to report a bug, request a feature, or ask a question - tickets keep everything organized in one place.</p>

<h3>What IS a Ticket?</h3>
<p>A ticket is like a conversation thread about a specific topic or task. Each ticket has:</p>
<ul>
<li>A clear subject line</li>
<li>A detailed description of your request</li>
<li>Back-and-forth messages between you and us</li>
<li>File attachments if needed</li>
<li>A status (Open, In Progress, or Closed)</li>
</ul>

<h3>What Tickets Are For:</h3>
<ul>
<li>✅ Reporting bugs or errors on your site</li>
<li>✅ Requesting new features or changes</li>
<li>✅ Asking technical questions</li>
<li>✅ Providing content or files for your project</li>
<li>✅ Discussing project timeline or milestones</li>
</ul>

<h3>What Tickets Are NOT For:</h3>
<ul>
<li>❌ Casual conversation (use email for that)</li>
<li>❌ Billing questions (use the invoice system or email)</li>
<li>❌ Multiple unrelated topics in one ticket (create separate tickets instead)</li>
</ul>

<h3>How to Submit a Ticket</h3>

<h4>Step 1: Navigate to Submit Ticket</h4>
<ol>
<li>Click <strong>"Communication"</strong> in the main menu</li>
<li>Select <strong>"Submit Ticket"</strong></li>
</ol>
<img src="/client-dashboard/blog/assets/img/kb/ticket-submit-menu.png" alt="Submit ticket menu" class="img-fluid my-3">

<h4>Step 2: Fill Out the Form</h4>
<p>Provide clear, detailed information:</p>
<ul>
<li><strong>Title:</strong> Brief summary (e.g., "Contact form not working" or "Add blog to homepage")</li>
<li><strong>Category:</strong> Choose the best fit (Support, Bug Report, Feature Request, etc.)</li>
<li><strong>Priority:</strong> Low = No rush, Medium = Normal, High = Urgent</li>
<li><strong>Description:</strong> Explain in detail what you need. Include:
    <ul>
    <li>What you''re trying to do</li>
    <li>What''s happening instead</li>
    <li>Any error messages</li>
    <li>Steps to reproduce the issue</li>
    </ul>
</li>
<li><strong>Attachments:</strong> Add screenshots, documents, or files if helpful</li>
</ul>
<img src="/client-dashboard/blog/assets/img/kb/ticket-submit-form.png" alt="Ticket submission form" class="img-fluid my-3">

<h4>Step 3: Submit</h4>
<p>Click the <strong>"Submit Ticket"</strong> button at the bottom of the form.</p>
<img src="/client-dashboard/blog/assets/img/kb/ticket-submit-button.png" alt="Submit button" class="img-fluid my-3">

<h3>Viewing Your Tickets</h3>
<p>To see all your tickets:</p>
<ol>
<li>Go to <strong>Communication > My Tickets</strong></li>
<li>You''ll see a list of all your tickets with their status</li>
<li>Click any ticket to view the full conversation</li>
</ol>
<img src="/client-dashboard/blog/assets/img/kb/ticket-view-list.png" alt="My tickets list" class="img-fluid my-3">

<h3>Replying to a Ticket</h3>
<p>When we respond to your ticket, you''ll receive a notification. To reply:</p>
<ol>
<li>Open the ticket from your My Tickets page</li>
<li>Scroll to the bottom to see all messages</li>
<li>Type your reply in the text box</li>
<li>Click "Send Reply"</li>
</ol>
<img src="/client-dashboard/blog/assets/img/kb/ticket-view-detail.png" alt="Ticket detail view" class="img-fluid my-3">

<h3>Best Practices</h3>
<ul>
<li>🎯 <strong>One topic per ticket</strong> - If you have 3 different requests, create 3 tickets</li>
<li>📸 <strong>Include screenshots</strong> - A picture is worth a thousand words</li>
<li>📝 <strong>Be specific</strong> - "The button doesn''t work" vs "The Submit button on the Contact page shows an error when clicked"</li>
<li>⏱️ <strong>Set realistic priority</strong> - Not everything is "High" priority</li>
<li>💬 <strong>Keep communication in the ticket</strong> - Don''t split the conversation across email and tickets</li>
</ul>

<div class="alert alert-warning mt-4">
<strong>⚠️ Response Time:</strong> We typically respond to tickets within 1-2 business days. High priority tickets are addressed first. If you haven''t heard back in 48 hours, feel free to add a comment to bump the ticket.
</div>
</div>',
'/client-dashboard/blog/assets/img/kb/ticket-submit-form.png',
NOW(),
@getting_started_cat,
'Yes',
'No',
'Admin'
);

-- Article 3: How to Use the Documentation System
INSERT INTO posts (title, slug, content, image, date, category, active, featured, author) VALUES (
'How to Use the Documentation System',
'how-to-use-documentation-system',
'<div class="kb-article">
<h2>Managing Your Project Documents</h2>
<p>The Documentation System is your secure file storage area where you can upload content, images, PDFs, and other files needed for your website project. Think of it as a shared folder between you and us.</p>

<h3>What You Can Upload</h3>
<p>Common file types include:</p>
<ul>
<li>📄 <strong>Documents:</strong> Word docs, PDFs, text files with your content</li>
<li>🖼️ <strong>Images:</strong> Photos, logos, graphics for your website</li>
<li>📊 <strong>Spreadsheets:</strong> Data, pricing tables, product lists</li>
<li>🎨 <strong>Design Files:</strong> Mockups, wireframes, brand guidelines</li>
<li>📦 <strong>Archives:</strong> ZIP files with multiple files</li>
</ul>

<h3>Accessing Your Documents</h3>
<ol>
<li>Click <strong>"Documents"</strong> in the main navigation menu</li>
<li>You''ll see your document library with all uploaded files</li>
</ol>
<img src="/client-dashboard/blog/assets/img/kb/docs-menu.png" alt="Documents menu" class="img-fluid my-3">

<h3>How to Upload a Document</h3>

<h4>Step 1: Click Upload</h4>
<p>Look for the <strong>"Upload Document"</strong> or <strong>"+"</strong> button at the top of the Documents page.</p>
<img src="/client-dashboard/blog/assets/img/kb/docs-upload-button.png" alt="Upload button" class="img-fluid my-3">

<h4>Step 2: Select Your File</h4>
<ol>
<li>Click "Choose File" or drag and drop your file into the upload area</li>
<li>Add a description (optional but helpful - e.g., "Homepage hero image" or "About Us page content")</li>
<li>Select a category if available (Website Content, Images, Legal, etc.)</li>
</ol>
<img src="/client-dashboard/blog/assets/img/kb/docs-upload-form.png" alt="Upload form" class="img-fluid my-3">

<h4>Step 3: Upload</h4>
<p>Click the <strong>"Upload"</strong> button. You''ll see a progress bar, then a confirmation message.</p>

<h3>Viewing Your Documents</h3>
<p>All uploaded documents appear in a list showing:</p>
<ul>
<li>📎 File name</li>
<li>📅 Upload date</li>
<li>📏 File size</li>
<li>📁 Category</li>
<li>⬇️ Download button</li>
</ul>
<img src="/client-dashboard/blog/assets/img/kb/docs-file-list.png" alt="Document list" class="img-fluid my-3">

<h3>Downloading a Document</h3>
<p>To download any file:</p>
<ol>
<li>Find the document in your list</li>
<li>Click the <strong>Download</strong> or <strong>⬇️</strong> button/icon</li>
<li>The file will download to your computer</li>
</ol>
<img src="/client-dashboard/blog/assets/img/kb/docs-download-button.png" alt="Download button" class="img-fluid my-3">

<h3>Deleting a Document</h3>
<p>If you uploaded the wrong file:</p>
<ol>
<li>Find the document</li>
<li>Click the <strong>Delete</strong> or <strong>🗑️</strong> button</li>
<li>Confirm the deletion</li>
</ol>

<div class="alert alert-warning">
<strong>⚠️ Note:</strong> Deleted files cannot be recovered. Make sure you really want to delete before confirming.
</div>

<h3>File Size Limits</h3>
<p>Maximum file size: <strong>50 MB per file</strong></p>
<p>If you have larger files:</p>
<ul>
<li>Compress images before uploading</li>
<li>Split large documents into smaller parts</li>
<li>Use a ZIP file to combine multiple files</li>
<li>Contact us via ticket for special arrangements</li>
</ul>

<h3>Best Practices</h3>
<ul>
<li>📝 <strong>Use descriptive filenames</strong> - "logo.png" is better than "image1.png"</li>
<li>🗂️ <strong>Organize by category</strong> - Makes finding files easier later</li>
<li>💾 <strong>Keep a backup</strong> - Don''t delete your original files from your computer</li>
<li>🖼️ <strong>Optimize images</strong> - Use tools like TinyPNG to compress images before uploading</li>
<li>📋 <strong>Include context</strong> - Add descriptions so we know how to use each file</li>
</ul>

<div class="alert alert-info mt-4">
<strong>💡 Pro Tip:</strong> If you''re uploading content for a specific page, mention the page name in the file description or create a ticket referencing the uploaded files. This helps us implement your content faster!
</div>
</div>',
'/client-dashboard/blog/assets/img/kb/docs-file-list.png',
NOW(),
@getting_started_cat,
'Yes',
'No',
'Admin'
);

-- Article 4: How to View and Pay Invoices
INSERT INTO posts (title, slug, content, image, date, category, active, featured, author) VALUES (
'How to View and Pay Invoices',
'how-to-view-and-pay-invoices',
'<div class="kb-article">
<h2>Managing Your Invoices</h2>
<p>All invoices for your website project are available in the portal. You can view invoice details, track payment status, and pay securely online using PayPal.</p>

<h3>Invoice Notifications</h3>
<p>When a new invoice is created, you''ll receive:</p>
<ul>
<li>📧 An email notification with invoice details</li>
<li>🔔 A notification in the portal (bell icon in the header)</li>
</ul>
<img src="/client-dashboard/blog/assets/img/kb/invoice-notification.png" alt="Invoice notification" class="img-fluid my-3">

<h3>Viewing Your Invoices</h3>

<h4>Method 1: From Notifications</h4>
<ol>
<li>Click the bell icon 🔔 in the header</li>
<li>Click on the invoice notification</li>
<li>You''ll be taken directly to that invoice</li>
</ol>

<h4>Method 2: From the Menu</h4>
<ol>
<li>Click <strong>"Invoices"</strong> in the main menu (if available)</li>
<li>Or navigate to your account/billing section</li>
<li>You''ll see a list of all your invoices</li>
</ol>
<img src="/client-dashboard/blog/assets/img/kb/invoice-list.png" alt="Invoice list" class="img-fluid my-3">

<h3>Understanding Invoice Status</h3>
<p>Each invoice shows its payment status:</p>
<ul>
<li><span class="badge bg-warning">Unpaid</span> - Full amount is due</li>
<li><span class="badge bg-info">Partial</span> - Partially paid, balance remaining</li>
<li><span class="badge bg-success">Paid</span> - Fully paid</li>
<li><span class="badge bg-danger">Past Due</span> - Payment is overdue</li>
</ul>

<h3>Invoice Details</h3>
<p>When you open an invoice, you''ll see:</p>
<ul>
<li>📋 Invoice number and date</li>
<li>💰 Total amount due</li>
<li>📝 Itemized list of services/products</li>
<li>📅 Due date</li>
<li>💳 Payment button (if unpaid)</li>
<li>🧾 Download PDF option</li>
</ul>
<img src="/client-dashboard/blog/assets/img/kb/invoice-detail.png" alt="Invoice detail" class="img-fluid my-3">

<h3>How to Pay an Invoice</h3>

<h4>Step 1: Open the Invoice</h4>
<p>Click on any unpaid or partial invoice from your invoice list.</p>

<h4>Step 2: Review the Amount</h4>
<p>Double-check the amount due. For partial payments, you can often choose to pay the full balance or a partial amount.</p>

<h4>Step 3: Click Pay with PayPal</h4>
<p>Click the <strong>"Pay with PayPal"</strong> button.</p>
<img src="/client-dashboard/blog/assets/img/kb/invoice-pay-button.png" alt="Pay button" class="img-fluid my-3">

<h4>Step 4: Complete Payment</h4>
<ol>
<li>You''ll be redirected to PayPal (or see a PayPal popup)</li>
<li>Log into your PayPal account, or pay as a guest with a credit/debit card</li>
<li>Review the payment details</li>
<li>Click "Pay Now" in PayPal</li>
<li>You''ll be redirected back to the portal</li>
</ol>

<h4>Step 5: Confirmation</h4>
<p>After successful payment:</p>
<ul>
<li>✅ You''ll see a confirmation message</li>
<li>📧 You''ll receive a payment receipt email</li>
<li>🔔 The invoice notification will update to show "Paid"</li>
<li>📊 The invoice status will change to "Paid" or "Partial" (if paying partial amount)</li>
</ul>
<img src="/client-dashboard/blog/assets/img/kb/invoice-payment-complete.png" alt="Payment confirmation" class="img-fluid my-3">

<h3>Payment Methods</h3>
<p>Through PayPal, you can pay with:</p>
<ul>
<li>💳 Credit or Debit Card</li>
<li>🏦 Bank Account (if linked to PayPal)</li>
<li>💰 PayPal Balance</li>
<li>📱 PayPal Credit (if eligible)</li>
</ul>

<div class="alert alert-info">
<strong>ℹ️ Don''t have a PayPal account?</strong> You can still pay! PayPal allows guest checkout with any credit or debit card. You don''t need to create an account.
</div>

<h3>Partial Payments</h3>
<p>If you need to make a partial payment:</p>
<ol>
<li>Open the invoice</li>
<li>Look for "Pay Partial Amount" or enter a custom amount</li>
<li>Enter the amount you want to pay now</li>
<li>Complete the PayPal payment</li>
<li>The remaining balance will stay on the invoice</li>
</ol>

<h3>Downloading Invoice PDFs</h3>
<p>To save or print an invoice:</p>
<ol>
<li>Open the invoice</li>
<li>Click <strong>"Download PDF"</strong> or <strong>Print</strong></li>
<li>Save the PDF to your computer or print it</li>
</ol>

<h3>Payment Issues?</h3>
<p>If you encounter problems:</p>
<ul>
<li>🔄 Try refreshing the page</li>
<li>💻 Try a different browser</li>
<li>📧 Check your email for payment confirmation (sometimes it processes even if the redirect fails)</li>
<li>🎫 Submit a ticket if the problem persists</li>
</ul>

<div class="alert alert-warning mt-4">
<strong>⚠️ Payment Security:</strong> All payments are processed through PayPal''s secure system. We never see or store your credit card information. Your payment data is protected by PayPal''s industry-leading security.
</div>

<h3>Payment Terms</h3>
<ul>
<li>📅 Invoices are typically due within 7-14 days of issue date</li>
<li>⏰ Late fees may apply to past due balances</li>
<li>✋ Project work may pause if invoices go unpaid</li>
<li>💬 Contact us immediately if you have payment difficulties - we''re happy to work with you!</li>
</ul>
</div>',
'/client-dashboard/blog/assets/img/kb/invoice-detail.png',
NOW(),
@getting_started_cat,
'Yes',
'No',
'Admin'
);

-- Article 5: How to Update Your Profile & Business Information
INSERT INTO posts (title, slug, content, image, date, category, active, featured, author) VALUES (
'How to Update Your Profile and Business Information',
'how-to-update-profile',
'<div class="kb-article">
<h2>Managing Your Account Information</h2>
<p>Keeping your profile and business information up-to-date ensures we can contact you properly and helps us personalize your website with accurate details.</p>

<h3>Why Update Your Profile?</h3>
<ul>
<li>📧 Ensure you receive important notifications</li>
<li>📱 Update contact methods</li>
<li>🏢 Keep business information current</li>
<li>🌐 Update details that appear on your website</li>
<li>👤 Personalize your portal experience</li>
</ul>

<h3>Accessing Your Profile</h3>
<p>To edit your profile:</p>
<ol>
<li>Click your <strong>name or avatar</strong> in the top-right corner</li>
<li>Select <strong>"Edit Profile"</strong> or <strong>"Account Settings"</strong></li>
</ol>
<img src="/client-dashboard/blog/assets/img/kb/profile-menu.png" alt="Profile menu" class="img-fluid my-3">

<h3>Personal Information Section</h3>
<p>Update your personal details:</p>
<ul>
<li><strong>Name:</strong> Your full name as you''d like it displayed</li>
<li><strong>Email:</strong> Primary contact email (you''ll receive notifications here)</li>
<li><strong>Phone:</strong> Contact phone number</li>
<li><strong>Profile Photo:</strong> Optional avatar/photo</li>
</ul>
<img src="/client-dashboard/blog/assets/img/kb/profile-edit-form.png" alt="Profile editing form" class="img-fluid my-3">

<div class="alert alert-warning">
<strong>⚠️ Email Changes:</strong> If you change your email address, you may need to verify the new email before it becomes active. Check your inbox for a verification link.
</div>

<h3>Business Information Section</h3>
<p>These details may appear on your website or invoices:</p>
<ul>
<li><strong>Business Name:</strong> Your company or organization name</li>
<li><strong>Business Address:</strong> Physical or mailing address</li>
<li><strong>Business Phone:</strong> Main business contact number</li>
<li><strong>Business Email:</strong> Public-facing business email</li>
<li><strong>Website:</strong> Your current website URL (if applicable)</li>
<li><strong>Industry/Category:</strong> Type of business</li>
<li><strong>Tax ID/EIN:</strong> Optional, for invoice purposes</li>
</ul>
<img src="/client-dashboard/blog/assets/img/kb/profile-business-section.png" alt="Business information" class="img-fluid my-3">

<h3>Notification Preferences</h3>
<p>Control what emails you receive:</p>
<ul>
<li>✅ Invoice notifications</li>
<li>✅ Ticket updates</li>
<li>✅ System announcements</li>
<li>✅ Newsletter (optional)</li>
</ul>

<h3>Saving Your Changes</h3>
<ol>
<li>Make all desired changes to your profile</li>
<li>Scroll to the bottom of the page</li>
<li>Click <strong>"Save Changes"</strong> or <strong>"Update Profile"</strong></li>
<li>You''ll see a confirmation message</li>
</ol>
<img src="/client-dashboard/blog/assets/img/kb/profile-save-button.png" alt="Save button" class="img-fluid my-3">

<h3>Required vs Optional Fields</h3>
<p>Fields marked with an asterisk (*) are required. You must fill these out to save your profile. Optional fields can be left blank if not applicable.</p>

<h3>Profile Photo Tips</h3>
<p>If uploading a profile photo:</p>
<ul>
<li>📏 Use a square image (400x400px or larger)</li>
<li>😊 Professional headshot or company logo works best</li>
<li>📦 Maximum file size: 2MB</li>
<li>🎨 Accepted formats: JPG, PNG, GIF</li>
</ul>

<h3>Business Profile for Website</h3>
<p>Information entered in the Business Information section may automatically populate:</p>
<ul>
<li>Contact pages</li>
<li>Footer information</li>
<li>About pages</li>
<li>Invoice headers</li>
</ul>

<div class="alert alert-info">
<strong>💡 Pro Tip:</strong> Keep your business hours, address, and contact methods current. This ensures visitors to your website get accurate information!
</div>

<h3>Privacy & Security</h3>
<ul>
<li>🔒 Your personal information is private and secure</li>
<li>👁️ Only you and authorized admins can see your full profile</li>
<li>🌐 Only information you mark as "public" appears on your website</li>
<li>📧 We never sell or share your information</li>
</ul>

<h3>Multiple Profiles</h3>
<p>If you have multiple businesses or websites:</p>
<ul>
<li>Each website project may have its own business profile</li>
<li>Switch between profiles using the selector (if available)</li>
<li>Update each profile independently</li>
</ul>

<h3>Troubleshooting</h3>
<p>If you can''t save your changes:</p>
<ol>
<li>Check that all required fields (*) are filled in</li>
<li>Ensure email address is in valid format (name@domain.com)</li>
<li>Check that phone number doesn''t include special characters</li>
<li>Try refreshing the page and entering changes again</li>
<li>Submit a ticket if problems persist</li>
</ol>
</div>',
'/client-dashboard/blog/assets/img/kb/profile-edit-form.png',
NOW(),
@getting_started_cat,
'Yes',
'No',
'Admin'
);

-- Article 6: How to Change Your Username and Password
INSERT INTO posts (title, slug, content, image, date, category, active, featured, author) VALUES (
'How to Change Your Username and Password',
'how-to-change-username-password',
'<div class="kb-article">
<h2>Account Security: Username & Password</h2>
<p>Your username and password protect your account. It''s important to keep them secure and update them periodically.</p>

<h3>Password Security Best Practices</h3>
<p>Before we get into the how-to, here''s what makes a strong password:</p>
<ul>
<li>✅ At least 12 characters long</li>
<li>✅ Mix of uppercase and lowercase letters</li>
<li>✅ Include numbers and special characters (!@#$%)</li>
<li>✅ NOT a dictionary word or common phrase</li>
<li>✅ Unique to this account (don''t reuse passwords)</li>
<li>✅ No personal info (birthdays, names, addresses)</li>
</ul>

<div class="alert alert-danger">
<strong>❌ Weak Passwords to Avoid:</strong>
<ul class="mb-0">
<li>password123</li>
<li>qwerty</li>
<li>yourname2024</li>
<li>12345678</li>
<li>iloveyou</li>
</ul>
</div>

<h3>Changing Your Password</h3>

<h4>Step 1: Access Security Settings</h4>
<p>Navigate to your account settings:</p>
<ol>
<li>Click your <strong>name/avatar</strong> in the top-right</li>
<li>Select <strong>"Account Settings"</strong> or <strong>"Security"</strong></li>
<li>Look for the <strong>"Change Password"</strong> section</li>
</ol>

<h4>Step 2: Enter Current Password</h4>
<p>For security, you must enter your current password first.</p>

<h4>Step 3: Enter New Password</h4>
<p>Type your new password. Watch for the password strength indicator:</p>
<ul>
<li><span class="text-danger">Weak</span> - Too short or simple</li>
<li><span class="text-warning">Fair</span> - Better but could be stronger</li>
<li><span class="text-success">Strong</span> - Good password!</li>
</ul>
<img src="/client-dashboard/blog/assets/img/kb/password-change-form.png" alt="Password change form" class="img-fluid my-3">

<h4>Step 4: Confirm New Password</h4>
<p>Re-type your new password exactly as you typed it above. This prevents typos.</p>
<img src="/client-dashboard/blog/assets/img/kb/password-requirements.png" alt="Password requirements" class="img-fluid my-3">

<h4>Step 5: Save Changes</h4>
<ol>
<li>Click <strong>"Update Password"</strong> or <strong>"Save"</strong></li>
<li>You''ll see a confirmation message</li>
<li>You may be logged out and asked to log back in with your new password</li>
</ol>

<h3>Changing Your Username</h3>

<div class="alert alert-warning">
<strong>⚠️ Important:</strong> Your username is what you use to log in. If you change it, make sure to remember or write down the new username!
</div>

<h4>Step 1: Navigate to Username Settings</h4>
<p>In your Account Settings or Profile page:</p>
<ol>
<li>Find the <strong>"Change Username"</strong> section</li>
<li>Or look for an "Edit" button next to your current username</li>
</ol>
<img src="/client-dashboard/blog/assets/img/kb/username-change-form.png" alt="Username change" class="img-fluid my-3">

<h4>Step 2: Enter New Username</h4>
<p>Choose a new username. Requirements typically include:</p>
<ul>
<li>3-20 characters</li>
<li>Letters, numbers, and underscores only</li>
<li>Must be unique (not already taken)</li>
<li>Cannot contain spaces or special characters</li>
</ul>

<h4>Step 3: Verify Availability</h4>
<p>The system will check if your desired username is available. If it''s taken, try adding numbers or underscores.</p>

<h4>Step 4: Confirm and Save</h4>
<ol>
<li>Confirm you want to change your username</li>
<li>Click <strong>"Update Username"</strong></li>
<li>Save the change</li>
</ol>

<h3>Forgot Your Current Password?</h3>
<p>If you can''t remember your password to change it:</p>
<ol>
<li>Log out of the portal</li>
<li>On the login page, click <strong>"Forgot Password?"</strong></li>
<li>Enter your email address</li>
<li>Check your email for a password reset link</li>
<li>Click the link and create a new password</li>
</ol>

<h3>Password Reset Email Not Arriving?</h3>
<p>If you don''t receive the password reset email:</p>
<ul>
<li>📧 Check your spam/junk folder</li>
<li>⏰ Wait 5-10 minutes (sometimes delayed)</li>
<li>✉️ Make sure you entered the correct email address</li>
<li>🔄 Try requesting the reset again</li>
<li>🎫 Submit a ticket if still no email after 30 minutes</li>
</ul>

<h3>Two-Factor Authentication (2FA)</h3>
<p>If your account has 2FA enabled:</p>
<ul>
<li>You''ll need your authentication code when logging in</li>
<li>Changing your password doesn''t affect 2FA</li>
<li>To disable/reconfigure 2FA, go to Security Settings</li>
<li>Keep backup codes in a safe place</li>
</ul>

<h3>After Changing Credentials</h3>
<p>Once you''ve changed your username or password:</p>
<ul>
<li>📝 Write down your new credentials securely</li>
<li>💾 Update any password managers</li>
<li>📱 Update saved passwords in your browser</li>
<li>🖥️ You may need to log in again on other devices</li>
</ul>

<h3>Using a Password Manager</h3>
<p>We highly recommend using a password manager like:</p>
<ul>
<li>1Password</li>
<li>LastPass</li>
<li>Bitwarden (free)</li>
<li>Dashlane</li>
</ul>

<p>Benefits:</p>
<ul>
<li>🔐 Generate strong random passwords</li>
<li>💾 Securely store all your passwords</li>
<li>🔄 Auto-fill login forms</li>
<li>✅ Different password for every site</li>
</ul>

<div class="alert alert-info mt-4">
<strong>💡 Pro Tip:</strong> Change your password every 3-6 months, or immediately if you suspect your account has been compromised. Never share your password with anyone - we will never ask for it!
</div>

<h3>Account Security Checklist</h3>
<ul class="list-unstyled">
<li>☑️ Use a strong, unique password</li>
<li>☑️ Enable two-factor authentication if available</li>
<li>☑️ Keep your email address current</li>
<li>☑️ Don''t share your login credentials</li>
<li>☑️ Log out when using shared computers</li>
<li>☑️ Update password if you suspect compromise</li>
<li>☑️ Use different passwords for different sites</li>
</ul>

<h3>Suspicious Activity?</h3>
<p>If you notice anything unusual:</p>
<ul>
<li>🚨 Change your password immediately</li>
<li>📧 Check your account email settings</li>
<li>🎫 Submit an urgent ticket to notify us</li>
<li>🔍 Review recent account activity</li>
</ul>
</div>',
'/client-dashboard/blog/assets/img/kb/password-change-form.png',
NOW(),
@getting_started_cat,
'Yes',
'No',
'Admin'
);

-- Success message
SELECT 'Knowledge Base articles created successfully!' as Status;
SELECT 
    id,
    title,
    slug,
    featured,
    active,
    date
FROM posts 
WHERE category = @getting_started_cat
ORDER BY id DESC;
