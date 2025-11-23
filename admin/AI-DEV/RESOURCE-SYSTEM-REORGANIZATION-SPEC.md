# Resource System Reorganization Specification

**Created:** 2025-11-23  
**Status:** Planning  
**Priority:** High

## Overview
Consolidate all resource management files into a single `/admin/resource_system/` directory with proper organization, consistent naming, and integrated navigation menu with dashboard.

---

## Current Issues

### 1. **Scattered Files**
- Warranty files in `barb-resources/` subdirectory
- Mix of plural/singular naming conventions
- Inconsistent path references (hardcoded vs. constants)

### 2. **Inconsistent Naming**
- Some use plural for list pages (domains.php, warranties.php)
- Others use different patterns
- Form pages sometimes match, sometimes don't

### 3. **Navigation**
- No unified dashboard for resource system
- No submenu structure in admin template
- Hard to access different resource types

---

## File Inventory & Analysis

### Current Resource Types

#### **1. Domains** ✅ Already in resource_system/
- `domains.php` (list - plural) ✅
- `domain.php` (create/edit - singular) ✅
- `domain-view.php` (view details) ✅
- `domain-use.php` (usage tracker) ✅
- `domain-impt.php` (import) ✅
- `domain-expt.php` (export) ✅

#### **2. Dev Projects** ✅ Already in resource_system/
- `dev-projects.php` (list - plural) ✅
- `dev-project.php` (create/edit - singular) ✅
- `dev-project-view.php` ✅
- `dev-project-use.php` ✅
- `dev-project-impt.php` ✅
- `dev-project-expt.php` ✅

#### **3. Client Projects** ✅ Already in resource_system/
- `client-projects.php` (list - plural) ✅
- `client-project.php` (create/edit - singular) ✅
- `client-project-view.php` ✅
- `client-project-use.php` ✅
- `client-project-logs.php` (logs list) ✅
- `client-project-log.php` (create/edit log) ✅
- `client-project-log-view.php` ✅
- `client-project-log-use.php` ✅

#### **4. SaaS Accounts** ✅ Already in resource_system/
- `sass-accounts.php` (list - plural) ✅
- `sass-account.php` (create/edit - singular) ✅
- `sass-account-view.php` ✅
- `sass-account-use.php` ✅
- `sass-account-impt.php` ✅
- `sass-account-expt.php` ✅

#### **5. Financial Institutions** ✅ Already in resource_system/
- `financial-institutions.php` (list - plural) ✅
- `financial-institution.php` (create/edit - singular) ✅
- `financial-institution-view.php` ✅
- `financial-institution-use.php` ✅
- `financial-institution-impt.php` ✅
- `financial-institution-expt.php` ✅

#### **6. Access Resources** ✅ Already in resource_system/
- `access_resources.php` (list - plural) ✅
- `access_resource.php` (create/edit - singular) ✅
- `access_resource_view.php` ✅
- `access_resource_impt.php` ✅
- `access_resource_expt.php` ✅

#### **7. Error Logs** ✅ Already in resource_system/
- `error-logs.php` (list - plural) ✅
- `error-log-view.php` (view only - no create) ✅

#### **8. Project Types** ✅ Already in resource_system/
- `project-types.php` (list - plural) ✅
- `project-type.php` (create/edit - singular) ✅
- `project-type-view.php` ✅

#### **9. Caches** ✅ Already in resource_system/
- `caches.php` (list - plural) ✅
- `cache.php` (create/edit - singular) ✅
- `cache-view.php` ✅

#### **10. Warranties** ⚠️ INCOMPLETE - NEEDS COMPLETION

**Currently in resource_system/** (Partially implemented, has errors)
- `warranties.php` (list - plural) ✅ - Links to warranty-use.php and warranty-view.php that don't exist
- `warranty.php` (create/edit - singular) ⚠️ - HAS SYNTAX ERRORS (missing $ signs, extra comma in UPDATE query)

**Currently in barb-resources/** 🔴 TEMPLATE ONLY - DELETE AFTER REVIEW
- `warranty-submit-ticket.php` - OLD TEMPLATE (uses different config system, client-dashboard paths)
- `warranty-ticket-view.php` - OLD TEMPLATE (uses different config system, has comments feature)
- `warranty-review-responses.php` - OLD TEMPLATE (review list with tabs)
- `warranty-ticket-email-template.php` - Email template (may be useful)
- `warranty-ticket-uploads/` - Upload directory (files may need preserving)

**REQUIRED ACTIONS:**
1. **Fix warranty.php** - Correct syntax errors in UPDATE query
2. **Create warranty-view.php** - View details page (use barb-resources/warranty-ticket-view.php as reference for field layout)
3. **Create warranty-use.php** - Simple display page for when warranty needs to be used
4. **Review barb-resources files** - Check if any features are worth porting (file uploads, comments, email)
5. **Delete barb-resources/** - After completing above, remove entire subdirectory

**Database Table:** `warranty_tickets`
**Fields:** title, msg, warranty_type_id, ticket_status, owner, reminder_date, purchase_date, warranty_expiration_date

**Related Tables:**
- `warranty_types` - Product/warranty categories
- `warranty_tickets_uploads` - File attachments (feature may not be in current warranty.php)
- `warranty_tickets_comments` - Comments feature (feature may not be in current warranty.php)

#### **11. Medications** ✅ Already in resource_system/ (Personal tracking)
- `meds.php` (list) ✅
- `med.php` (create/edit) ✅
- `med-view.php` ✅
- `meds-barbara.php`, `meds-dio.php`, `meds-joseph.php`, `meds-max.php` (filtered views) ✅
- Various PDF/email export files ✅

#### **12. Accounting** ✅ Already in resource_system/
- `accountings.php` (list - plural) ✅
- `accounting.php` (create/edit - singular) ✅
- `accounting-view.php` ✅

#### **13. Accounts** ✅ Already in resource_system/
- `accounts.php` (list) ✅
Note: This might be duplicate of client_accounts - needs investigation

---

## Standardized File Naming Pattern

For each resource type, use this pattern:

### **List Page (Main)**
`{resource-type}s.php` or `{resource-type}-list.php`
- Examples: `domains.php`, `warranties.php`, `sass-accounts.php`

### **Create/Edit Form**
`{resource-type}.php`
- Examples: `domain.php`, `warranty.php`, `sass-account.php`

### **View Details**
`{resource-type}-view.php`
- Examples: `domain-view.php`, `warranty-view.php`

### **Usage/Activity Tracker**
`{resource-type}-use.php`
- Examples: `domain-use.php`, `warranty-use.php`

### **Import/Export**
- `{resource-type}-impt.php`
- `{resource-type}-expt.php`

### **Related Sub-resources**
- `{resource-type}-{sub-resource}s.php` (list)
- `{resource-type}-{sub-resource}.php` (form)
- Examples: `client-project-logs.php`, `client-project-log.php`

---

## Migration Tasks

### Phase 1: Complete Warranty System (NOT Move Files)

#### Fix warranty.php Syntax Errors
```php
// Line 45 - Fix UPDATE query (extra comma before WHERE, missing $ signs)
FROM: $stmt = $onthego_db->prepare('UPDATE warranty_tickets SET title = ?, msg = ?, warranty_type_id = ?, ticket_status = ?, owner = ?, reminder_date = ?, purchase_date = ?, warranty_expiration_date = ?,   WHERE id = ?');
      $stmt->execute([ $_POST['title'],$_POST['msg'],$_POST['warranty_type_id'], _POST['ticket_status'], _POST['owner'],_POST['reminder_date'],_POST['purchase_date'],_POST['warranty_expiration_date'], $_GET['id'] ]);

TO:   $stmt = $onthego_db->prepare('UPDATE warranty_tickets SET title = ?, msg = ?, warranty_type_id = ?, ticket_status = ?, owner = ?, reminder_date = ?, purchase_date = ?, warranty_expiration_date = ? WHERE id = ?');
      $stmt->execute([ $_POST['title'], $_POST['msg'], $_POST['warranty_type_id'], $_POST['ticket_status'], $_POST['owner'], $_POST['reminder_date'], $_POST['purchase_date'], $_POST['warranty_expiration_date'], $_GET['id'] ]);
```

#### Create warranty-view.php
- Use barb-resources/warranty-ticket-view.php as REFERENCE ONLY for layout
- Adapt to resource_system structure (use admin_config.php, components.php)
- Display warranty details in read-only format
- Show: title, msg, warranty type, status, owner, dates, etc.
- Add breadcrumbs, modern styling, proper buttons
- Button: Back to List (btn-secondary), Edit (btn-primary)

#### Create warranty-use.php
- Simple page to display warranty information when needed
- Similar to other -use.php files in resource system
- Show warranty details formatted for practical use (print-friendly)
- Display: title, product, purchase date, expiration date, warranty terms
- Buttons: Back to List (btn-secondary), Edit (btn-primary), Print option

#### Review barb-resources Features
Check if worth porting to warranty.php:
- File upload functionality (warranty_tickets_uploads table exists)
- Comments system (warranty_tickets_comments table exists)
- Email template functionality
- Multiple status options with radio buttons
- Reminder date feature

**Decision:** Keep warranty system SIMPLE for now (basic CRUD only). 
Can add file uploads and comments later if needed.

#### Delete barb-resources Subdirectory
After warranty-view.php and warranty-use.php are created and tested:
```bash
# Back up first (optional)
# Then delete entire subdirectory
rm -rf public_html/admin/resource_system/barb-resources/
```

### Phase 2: Update Path References

#### Replace Hardcoded Paths with Constants

**In config.php, ensure these are defined:**
```php
// Resource System Paths
if(!defined('resource_system_path')) define('resource_system_path', public_path . 'admin/resource_system/');
if(!defined('resource_system_url')) define('resource_system_url', site_url . 'admin/resource_system/');
if(!defined('resource_uploads_path')) define('resource_uploads_path', resource_system_path . 'uploads/');

// Specific upload directories
if(!defined('warranty_uploads_directory')) define('warranty_uploads_directory', resource_uploads_path . 'warranties/');
if(!defined('domain_uploads_directory')) define('domain_uploads_directory', resource_uploads_path . 'domains/');
```

**Update all files to use:**
- `resource_system_path` instead of hardcoded paths
- `resource_system_url` instead of hardcoded URLs
- Upload directory constants

### Phase 3: Add Components Include to All Pages

All pages using `generate_breadcrumbs()` need:
```php
<?php
require 'assets/includes/admin_config.php';
include_once '../assets/includes/components.php';
```

### Phase 4: Create Resource System Dashboard

**Create:** `resource_system/index.php` (dashboard)

**Dashboard Data Points:**
- Total Domains (count, expiring soon count)
- Active Dev Projects (count, overdue count)
- Client Projects (active count, pending count)
- SaaS Subscriptions (count, renewal alerts)
- Financial Accounts (count)
- Warranties (active count, expiring count)
- Error Logs (count last 24h, count last 7 days)
- Quick access cards to each resource type

### Phase 5: Update Admin Menu Navigation

**In:** `admin/assets/includes/main.php`

Update resource system menu to have dashboard + submenus:

```php
<li class="has-dropdown">
    <a href="resource_system/index.php">
        <i class="fa-solid fa-database"></i>
        <span>Resource System</span>
    </a>
    <ul class="dropdown">
        <li><a href="resource_system/index.php">Dashboard</a></li>
        <li class="divider"></li>
        <li><a href="resource_system/domains.php"><i class="fa-solid fa-globe"></i> Domains</a></li>
        <li><a href="resource_system/dev-projects.php"><i class="fa-solid fa-code"></i> Dev Projects</a></li>
        <li><a href="resource_system/client-projects.php"><i class="fa-solid fa-diagram-project"></i> Client Projects</a></li>
        <li><a href="resource_system/sass-accounts.php"><i class="fa-solid fa-cloud"></i> SaaS Accounts</a></li>
        <li><a href="resource_system/financial-institutions.php"><i class="fa-solid fa-credit-card"></i> Financial</a></li>
        <li><a href="resource_system/warranties.php"><i class="fa-solid fa-shield-halved"></i> Warranties</a></li>
        <li><a href="resource_system/access_resources.php"><i class="fa-solid fa-key"></i> Access Resources</a></li>
        <li><a href="resource_system/error-logs.php"><i class="fa-solid fa-triangle-exclamation"></i> Error Logs</a></li>
        <li><a href="resource_system/project-types.php"><i class="fa-solid fa-layer-group"></i> Project Types</a></li>
    </ul>
</li>
```

---

## Implementation Checklist

### Phase 1: Complete Warranty System
- [x] Fix warranty.php syntax errors (UPDATE query, missing $ signs)
- [x] Add components.php include to warranty.php
- [x] Update warranty.php buttons to match standards (btn-secondary, btn-success, btn-danger)
- [x] Fix warranties.php "Create Warranty Record" button link (was pointing to dev-project.php)
- [x] Create warranty-view.php (full details with status badges and date calculations)
- [x] Create warranty-use.php (quick reference with visual status card)
- [ ] Test warranty CRUD functionality
- [ ] Review barb-resources files for useful features
- [ ] Delete barb-resources/ subdirectory after testing

### Phase 2: Update Path References

#### Replace Hardcoded Paths with Constants

**In config.php, ensure these are defined:**
```php
// Resource System Paths
if(!defined('resource_system_path')) define('resource_system_path', public_path . 'admin/resource_system/');
if(!defined('resource_system_url')) define('resource_system_url', site_url . 'admin/resource_system/');
if(!defined('resource_uploads_path')) define('resource_uploads_path', resource_system_path . 'uploads/');

// Specific upload directories (if needed in future)
if(!defined('warranty_uploads_directory')) define('warranty_uploads_directory', resource_uploads_path . 'warranties/');
if(!defined('domain_uploads_directory')) define('domain_uploads_directory', resource_uploads_path . 'domains/');
```

**Update all files to use:**
- `resource_system_path` instead of hardcoded paths
- `resource_system_url` instead of hardcoded URLs
- Upload directory constants (when file upload features are added)

### Phase 3: Verify Components Include

All pages using `generate_breadcrumbs()` need:
```php
<?php
require 'assets/includes/admin_config.php';
include_once '../assets/includes/components.php';
```

✅ Already completed for most files in previous session
- [ ] Verify warranty.php has components include
- [ ] Ensure new warranty-view.php includes it
- [ ] Ensure new warranty-use.php includes it

### Phase 4: Create Resource System Dashboard
- [ ] Create `resource_system/index.php` dashboard
- [ ] Add data queries for all resource counts
- [ ] Add alert cards (expiring domains, overdue projects, etc.)
- [ ] Add Font Awesome icons
- [ ] Add breadcrumbs
- [ ] Apply modern styling

### Navigation Updates
- [ ] Update `admin/assets/includes/main.php` menu
- [ ] Add dropdown menu structure
- [ ] Add resource system dashboard link
- [ ] Add submenu items for each resource type
- [ ] Add Font Awesome icons to menu items
- [ ] Test menu functionality

### Testing
- [ ] Test each resource type list page loads
- [ ] Test create/edit forms work
- [ ] Test file uploads work in new location
- [ ] Test breadcrumbs display correctly
- [ ] Test all buttons work with new styling
- [ ] Test navigation menu shows/hides correctly
- [ ] Test dashboard displays all data

### Cleanup
- [ ] Remove empty `barb-resources/` directory
- [ ] Remove unused config files
- [ ] Update AI documentation
- [ ] Commit changes with descriptive message

---

## Database Tables Reference

### Warranty System Tables
- `warranty_types` - Product/warranty categories
- `warranty_tickets` - Main warranty records
- `warranty_tickets_uploads` - File attachments

### Other Resource Tables
- `domains` - Domain registrations
- `dev_projects` - Development projects  
- `client_projects` - Client project tracking
- `sass_accounts` - SaaS subscriptions
- `financial_institutions` - Bank/credit accounts
- `access_resources` - Login credentials/access
- `error_logs` - System error tracking
- `project_types` - Project categories

---

## Path Constants Summary

**Already Defined:**
- `public_path` - Base public_html path
- `site_url` - Base site URL
- `warranty_uploads_directory` - Warranty uploads (needs path update)

**Need to Add:**
- `resource_system_path` - Resource system directory path
- `resource_system_url` - Resource system URL
- `resource_uploads_path` - General resource uploads path

**Need to Update:**
- `warranty_uploads_directory` - Change from `'warranty-ticket-uploads/'` to `resource_uploads_path . 'warranties/'`

---

## Notes

- Keep medication tracking files separate (meds-*.php) as they're personal tracking tools
- Consider if `accounts.php` in resource_system duplicates `client_accounts/` - may need to consolidate or rename
- Warranty responses page might be better as a tab in warranties.php rather than separate page
- All upload directories should be under `resource_system/uploads/` for consistency
- Dashboard should be the default landing page when clicking "Resource System" in menu

---

## Success Criteria

✅ All resource files in single directory
✅ Consistent naming across all resource types  
✅ All paths use config constants
✅ Dashboard provides overview of all resources
✅ Navigation menu has proper structure with submenus
✅ All functionality works after migration
✅ Upload files accessible in new locations
✅ Breadcrumbs work correctly
✅ Buttons styled consistently

---

## Next Steps

1. **Review this spec** - Confirm approach is correct
2. **Execute Phase 1** - Move warranty files
3. **Execute Phase 2** - Update paths
4. **Execute Phase 3** - Ensure all have components include
5. **Execute Phase 4** - Build dashboard
6. **Execute Phase 5** - Update navigation
7. **Test thoroughly**
8. **Clean up and document**
