<?php
// 2024-12-09 Production.
// 2025-06-15 Reworked. VERIFIED
include_once 'assets/includes/admin_config.php';

// Retrieve all the templates from the templates directory and sort alphabetically
$templates = glob('../../client-invoices/templates/*', GLOB_ONLYDIR);
sort($templates);
// Search
$search = isset($_GET['search_query']) ? $_GET['search_query'] : '';
if ($search != '') {
    $templates = array_filter($templates, function($template) use ($search) {
        return strpos($template, $search) !== false;
    });
}
// Delete template
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $file = '../../client-invoices/templates/' . $_GET['delete'];
    if (is_dir($file)) {
        // Delete the directory
        array_map('unlink', glob($file . '/*.*'));
        rmdir($file);
        header('Location: invoice_templates.php?success_msg=3');
        exit;
    }
}
// Duplicate template
if (isset($_GET['duplicate']) && !empty($_GET['duplicate'])) {
    $source ='../../client-invoices/templates/' . $_GET['duplicate'];
    if (is_dir($source)) {
        // determine destination and append copy number if necessary
        $destination = $source . '_copy';
        $i = 1;
        while (is_dir($destination)) {
            $destination = $source . '_copy' . $i++;
        }
        // copy the directory
        copy_directory($source, $destination);
        header('Location: invoice_templates.php?success_msg=4');
        exit;
    }
}
// Handle success messages
if (isset($_GET['success_msg'])) {
    if ($_GET['success_msg'] == 1) {
        $success_msg = 'Invoice template created successfully!';
    }
    if ($_GET['success_msg'] == 2) {
        $success_msg = 'Invoice template updated successfully!';
    }
    if ($_GET['success_msg'] == 3) {
        $success_msg = 'Invoice template deleted successfully!';
    }
    if ($_GET['success_msg'] == 4) {
        $success_msg = 'Invoice template duplicated successfully!';
    }    
}
// Create URL
$url = 'invoice_templates.php?search_query=' . $search;
?>
<?=template_admin_header('Invoice Templates', 'invoices', 'templates')?>

<div class="content-title mb-3">
    <div class="title">
        <div class="icon">
            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M17 2H2V17H4V4H17V2M21 22L18.5 20.32L16 22L13.5 20.32L11 22L8.5 20.32L6 22V6H21V22M10 10V12H17V10H10M15 14H10V16H15V14Z" /></svg>
        </div>
        <div class="txt">
            <h2>Invoice Templates</h2>
            <p>View and manage invoice templates.</p>
        </div>
    </div>
</div>

<?php if (isset($success_msg)): ?>
<div class="msg success">
    <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM369 209L241 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L335 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/></svg>
    <p><?=$success_msg?></p>
    <svg class="close" width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg>
</div>
<?php endif; ?>

<div class="content-header responsive-flex-column pad-top-5">
    <a href="invoice_template.php" class="btn btn-primary">
        + Template
    </a>
    <form action="" method="get">
        <input type="hidden" name="page" value="invoices">
       
        <div class="search">
            <label for="search_query">
                <input id="search_query" type="text" name="search_query" placeholder="Search template..." value="<?=htmlspecialchars($search, ENT_QUOTES)?>" class="responsive-width-100">
                <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"/></svg>
            </label>
        </div>
    </form>
</div>

<div class="filter-list">
    <?php if ($search != ''): ?>
    <div class="filter">
        <a href="<?=remove_url_param($url, 'search_query')?>"><svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free --><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg></a>
        Search : <?=htmlspecialchars($search, ENT_QUOTES)?>
    </div>
    <?php endif; ?>   
</div>

<div class="content-block">
    <div class="invoice-templates">
        <?php if (empty($templates)): ?>
        <p class="empty">No templates found.</p>
        <?php else: ?>
        <?php foreach ($templates as $template): ?>
        <div class="template">
            <?php if (file_exists($template . '/preview.png')): ?>
            <div class="preview">
                <img src="<?='../../client-invoices/templates/' . basename($template) . '/preview.png'?>" alt="<?=basename($template)?>">
            </div>
            <?php else: ?>
            <div class="preview">
                <svg width="150" height="150" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M22 20.7L3.3 2L2 3.3L3 4.3V19C3 20.1 3.9 21 5 21H19.7L20.7 22L22 20.7M5 19V6.3L12.6 13.9L11.1 15.8L9 13.1L6 17H15.7L17.7 19H5M8.8 5L6.8 3H19C20.1 3 21 3.9 21 5V17.2L19 15.2V5H8.8" /></svg>
            </div>
            <?php endif; ?>
            <div class="name" title="<?=htmlspecialchars(ucwords(str_replace('_', ' ', basename($template))), ENT_QUOTES)?>"><?=htmlspecialchars(ucwords(str_replace('_', ' ', basename($template))), ENT_QUOTES)?></div>
            <div class="actions">
                <a href="invoice_templates.php?duplicate=<?=basename($template)?>" title="Duplicate">
                    <svg width="15" height="15" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M11,17H4A2,2 0 0,1 2,15V3A2,2 0 0,1 4,1H16V3H4V15H11V13L15,16L11,19V17M19,21V7H8V13H6V7A2,2 0 0,1 8,5H19A2,2 0 0,1 21,7V21A2,2 0 0,1 19,23H8A2,2 0 0,1 6,21V19H8V21H19Z" /></svg>
                </a>
                <a href="invoice_template.php?id=<?=basename($template)?>" title="Edit">
                    <svg width="15" height="15" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M20.71,7.04C21.1,6.65 21.1,6 20.71,5.63L18.37,3.29C18,2.9 17.35,2.9 16.96,3.29L15.12,5.12L18.87,8.87M3,17.25V21H6.75L17.81,9.93L14.06,6.18L3,17.25Z" /></svg>
                </a>
                <a href="invoice_templates.php?delete=<?=basename($template)?>" title="Delete" onclick="return confirm('Are you sure you want to delete this template?')">
                    <svg width="15" height="15" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19,4H15.5L14.5,3H9.5L8.5,4H5V6H19M6,19A2,2 0 0,0 8,21H16A2,2 0 0,0 18,19V7H6V19Z" /></svg>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?=template_admin_footer()?>