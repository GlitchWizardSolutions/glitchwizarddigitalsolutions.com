# Knowledge Base Setup Instructions

## Overview
Your blog has been transformed into a comprehensive **Knowledge Base** system with 6 detailed tutorial articles to help your clients learn how to use the portal.

## Files Created

### 1. Image Upload Form
**File:** `admin/blog/upload-kb-images.php`
- Professional upload interface for Knowledge Base screenshots
- Lists all 28 required images with descriptions
- Auto-resizes images to specified dimensions
- Preserves transparency for PNG/GIF
- Shows upload progress (X of 28 uploaded)
- Replace functionality for existing images

### 2. SQL Script with Articles
**File:** `admin/blog/knowledge-base-articles.sql`
- Creates "Getting Started" category
- Inserts 6 complete articles with detailed content
- Sets Article 1 as Featured
- All articles are active and ready to display

## Articles Created

### Article 1: How to Use the Knowledge Base (FEATURED)
**Images needed:** 3
- `kb-overview-menu.png` (800x600) - KB menu location
- `kb-overview-homepage.png` (1200x800) - KB homepage
- `kb-overview-categories.png` (800x600) - Category navigation

**Content:** Introduction to the Knowledge Base, how to find articles, browse categories, and get help.

---

### Article 2: How to Use the Ticketing System
**Images needed:** 5
- `ticket-submit-menu.png` (800x600) - Menu navigation
- `ticket-submit-form.png` (1200x900) - Ticket form
- `ticket-submit-button.png` (600x400) - Submit button
- `ticket-view-list.png` (1200x800) - My tickets list
- `ticket-view-detail.png` (1200x900) - Ticket detail/replies

**Content:** What tickets are for, how to submit, view, and reply. Best practices and response times.

---

### Article 3: How to Use the Documentation System
**Images needed:** 5
- `docs-menu.png` (800x600) - Documents menu
- `docs-upload-button.png` (800x600) - Upload button
- `docs-upload-form.png` (1000x700) - Upload form
- `docs-file-list.png` (1200x800) - File listing
- `docs-download-button.png` (600x400) - Download button

**Content:** File upload/download, file types, size limits, organization tips.

---

### Article 4: How to View and Pay Invoices
**Images needed:** 5
- `invoice-notification.png` (800x600) - Notification bell
- `invoice-list.png` (1200x800) - Invoice listing
- `invoice-detail.png` (1000x1200) - Full invoice view
- `invoice-pay-button.png` (600x400) - Pay button
- `invoice-payment-complete.png` (800x600) - Confirmation

**Content:** Invoice notifications, viewing details, PayPal payment, partial payments, payment security.

---

### Article 5: How to Update Your Profile and Business Information
**Images needed:** 4
- `profile-menu.png` (600x400) - Profile menu access
- `profile-edit-form.png` (1200x1000) - Edit form
- `profile-business-section.png` (1000x800) - Business info
- `profile-save-button.png` (600x400) - Save button

**Content:** Updating personal info, business details, notification preferences, privacy.

---

### Article 6: How to Change Your Username and Password
**Images needed:** 3
- `password-change-form.png` (800x600) - Password form
- `password-requirements.png` (600x400) - Requirements
- `username-change-form.png` (800x600) - Username form

**Content:** Password security, changing credentials, password reset, two-factor auth, best practices.

---

## Setup Steps

### Step 1: Run the SQL Script
1. Access phpMyAdmin or your MySQL interface
2. Select database: `glitchwizarddigi_envato_blog_db`
3. Import the SQL file: `admin/blog/knowledge-base-articles.sql`
4. Verify 6 articles were created in the `posts` table

### Step 2: Create Screenshots (28 total)
Use a screenshot tool with annotation features:
- **Windows:** Snipping Tool + Paint, Snagit (paid), ShareX (free)
- **Mac:** Built-in Screenshot tool, Snagit
- **Online:** Awesome Screenshot (browser extension)

**Screenshot Requirements:**
- Add red arrows pointing to important elements
- Add text annotations like "Click Here"
- Circle or highlight key areas
- Make sure text is readable
- Use the exact dimensions specified for each image

### Step 3: Upload Screenshots
1. Navigate to: `admin/blog/upload-kb-images.php`
2. For each image:
   - Find it in the list
   - Click "Choose File"
   - Select your annotated screenshot
   - The form will auto-submit and resize the image
   - Verify upload success
3. Progress tracker shows "X of 28 uploaded"
4. You can replace images anytime if you need to update them

### Step 4: Verify Knowledge Base
1. Visit: `client-dashboard/blog/index.php`
2. You should see:
   - Featured Article carousel (Article 1 featured)
   - Recent articles grid with all 6 articles
   - Breadcrumb shows "Knowledge Base"
3. Click into each article to verify:
   - Content displays correctly
   - Images appear in the right places
   - Screenshots match the instructions

## Image Storage
- **Upload location:** `client-dashboard/blog/assets/img/kb/`
- **Auto-resize:** Images resize to specified dimensions maintaining aspect ratio
- **Transparency:** PNG/GIF transparency preserved
- **Format support:** JPG, PNG, GIF, WebP

## Screenshot Tips

### Taking Screenshots
1. **Clear the clutter:** Close unnecessary browser tabs and windows
2. **Zoom if needed:** Make text readable
3. **Include context:** Show enough of the page for orientation
4. **Consistent style:** Use same colors for arrows/annotations

### Adding Annotations
- **Arrows:** Use red or bright color, point to specific elements
- **Text:** Add "Click Here", "Enter here", "Look for this"
- **Highlights:** Circle or box important areas
- **Blur if needed:** Hide any sensitive/personal info

### Example Workflow
1. Navigate to the page/feature you're documenting
2. Take clean screenshot
3. Open in annotation tool
4. Add arrows pointing to buttons/links mentioned in article
5. Add text labels for clarity
6. Save with exact filename from the list
7. Upload via the form

## Article Content Features

Each article includes:
- ✅ Clear step-by-step instructions
- ✅ Non-technical language for regular users
- ✅ Image placeholders at appropriate steps
- ✅ Best practices sections
- ✅ Troubleshooting tips
- ✅ Pro tips in info boxes
- ✅ Warning alerts for important notes
- ✅ What IS and IS NOT sections where applicable
- ✅ Security/privacy information
- ✅ Links to related help resources

## Current Status

✅ **Complete:**
- Upload form created
- SQL script with 6 articles created
- "Knowledge Base" breadcrumbs already in place
- Category system ready

⏳ **Your Tasks:**
1. Run SQL script in database
2. Create 28 annotated screenshots
3. Upload screenshots via form
4. Test Knowledge Base functionality

## Access Levels
The Knowledge Base is accessible to all logged-in users with these access levels:
- Admin
- Onboarding
- Guest
- Hosting
- Development
- Production
- Services
- Master
- Closed

(Banned users cannot log in)

## Future Enhancements
Consider adding more articles for:
- How to view project progress/milestones
- How to provide feedback on designs
- How to request website changes
- Understanding your hosting package
- How to access website analytics
- Billing and payment terms explained

## Support
If you encounter issues:
1. Check that SQL script ran successfully
2. Verify images uploaded correctly (check file permissions)
3. Clear browser cache
4. Check PHP error logs
5. Contact developer if problems persist

---

**Need Help?** Submit a ticket in the portal!
