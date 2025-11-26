<?php
// 2024-12-09 Production.
// 2025-06-15 Reworked. VERIFIED.
// THIS CREATES A NEW TEMPLATE.
include_once 'assets/includes/admin_config.php';

// Default template values
$template = [
    'name' => '',
    'html' => '',
    'pdf' => '',
    'preview' => ''
];
// Check if the ID param exists
if (isset($_GET['id'])) {
    // Retrieve the html file
    $template['html'] = file_exists(base_path . 'templates/' . $_GET['id'] . '/template.php') ? file_get_contents(base_path . 'templates/' . $_GET['id'] . '/template.php') : '';
    // Retrieve the pdf file
    $template['pdf'] = file_exists(base_path . 'templates/' . $_GET['id'] . '/template-pdf.php') ? file_get_contents(base_path . 'templates/' . $_GET['id'] . '/template-pdf.php') : '';
    // Get the template name
    $template['name'] = $_GET['id'];
    // Get the preview image
    $template['preview'] = file_exists(base_path . 'templates/' . $_GET['id'] . '/preview.png') ? base_url . 'templates/' . $_GET['id'] . '/preview.png' : '';
    // ID param exists, edit an existing account
    $page = 'Edit';
    if (isset($_POST['submit'])) {
        // Check if the template name has changed
        if ($_GET['id'] != $_POST['name']) {
            // check if the new name already exists
            if (file_exists(base_path . 'templates/' . $_POST['name'])) {
                $error_msg = 'Template name already exists!';
            } else {
                // Rename the directory
                if (!rename(base_path . 'templates/' . $_GET['id'], base_path . 'templates/' . $_POST['name'])) {
                    $error_msg = 'Error renaming template directory! Please set the correct permissions!';
                } else {
                    // update invoices in the database
                    $stmt = $pdo->prepare('UPDATE invoices SET invoice_template = ? WHERE invoice_template = ?');
                    $stmt->execute([ $_POST['name'], $_GET['id'] ]);
                }
            }
        }
        // Update the html file
        if ($_POST['html'] && !file_put_contents(base_path . 'templates/' . $_POST['name'] . '/template.php', $_POST['html'])) {
            $error_msg = 'Error updating template file! Please set the correct permissions!';
        }
        // Update the pdf file
        if ($_POST['pdf'] && !file_put_contents(base_path . 'templates/' . $_POST['name'] . '/template-pdf.php', $_POST['pdf'])) {
            $error_msg = 'Error updating template file! Please set the correct permissions!';
        }
        // Update the preview image
        if (isset($_FILES['preview']) && $_FILES['preview']['size'] > 0) {
            // Check if the file is an image
            if (exif_imagetype($_FILES['preview']['tmp_name']) == IMAGETYPE_PNG) {
                // Save the image
                move_uploaded_file($_FILES['preview']['tmp_name'], base_path . 'templates/' . $_POST['name'] . '/preview.png');
            } else {
                $error_msg = 'Preview image must be a PNG file!';
            }
        }
        // Redirect if successful
        if (!isset($error_msg)) {
            header('Location: invoice_templates.php?success_msg=2');
            exit;
        } else {
            // Save the submitted values
            $template = [
                'name' => $_POST['name'],
                'html' => $_POST['html'],
                'pdf' => $_POST['pdf']
            ];
        }
    }
    if (isset($_POST['delete'])) {
        // Redirect and delete template
        header('Location: invoice_templates.php?delete=' . $_GET['id']);
        exit;
    }
} else {
    // Create a new template
    $page = 'Create';
    if (isset($_POST['submit'])) {
        // Check if the template name already exists
        if (file_exists(base_path . 'templates/' . $_POST['name'])) {
            $error_msg = 'Template name already exists!';
        } else {
            // Create the directory
            if (!mkdir(base_path . 'templates/' . $_POST['name'])) {
                $error_msg = 'Error creating template directory! Please set the correct permissions!';
            }
            // Create the html file
            if ($_POST['html'] && !file_put_contents(base_path . 'templates/' . $_POST['name'] . '/template.php', $_POST['html'])) {
                $error_msg = 'Error creating template file! Please set the correct permissions!';
            }
            // Create the pdf file
            if ($_POST['pdf'] && !file_put_contents(base_path . 'templates/' . $_POST['name'] . '/template-pdf.php', $_POST['pdf'])) {
                $error_msg = 'Error creating template file! Please set the correct permissions!';
            }
            // Save the preview image
            if (isset($_FILES['preview']) && $_FILES['preview']['size'] > 0) {
                // Check if the file is an image
                if (exif_imagetype($_FILES['preview']['tmp_name']) == IMAGETYPE_PNG) {
                    // Save the image
                    move_uploaded_file($_FILES['preview']['tmp_name'], base_path . 'templates/' . $_POST['name'] . '/preview.png');
                } else {
                    $error_msg = 'Preview image must be a PNG file!';
                }
            }
        }
        // Redirect if successful
        if (!isset($error_msg)) {
            header('Location: invoice_templates.php?success_msg=1');
            exit;
        } else {
            // Save the submitted values
            $template = [
                'name' => $_POST['name'],
                'html' => $_POST['html'],
                'pdf' => $_POST['pdf']
            ];
        }
    }
}
?>
<?=template_admin_header($page . ' Invoice Template', 'invoices', 'templates')?>

<div class="content-title mb-3">
    <div class="title">
        <div class="icon">
            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M17 2H2V17H4V4H17V2M21 22L18.5 20.32L16 22L13.5 20.32L11 22L8.5 20.32L6 22V6H21V22M10 10V12H17V10H10M15 14H10V16H15V14Z" /></svg>
        </div>
        <div class="txt">
                 <h2 class="responsive-width-100"><?=$page?> Invoice Template</h2>
        </div>
    </div>
</div>
<br><br>
<form action="" method="post" enctype="multipart/form-data">

    <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
  
        <a href="invoice_templates.php" class="btn alt mar-right-2">Cancel</a>
        <?php if ($page == 'Edit'): ?>
        <input type="submit" name="delete" value="Delete" class="btn btn-danger mar-right-2" onclick="return confirm('Are you sure you want to delete this invoice?')">
        <?php endif; ?>
        <input type="submit" name="submit" value="Save" class="btn btn-success">
    </div>

    <?php if (isset($error_msg)): ?>
    <div class="mar-top-4">
        <div class="msg error">
            <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zm0-384c13.3 0 24 10.7 24 24V264c0 13.3-10.7 24-24 24s-24-10.7-24-24V152c0-13.3 10.7-24 24-24zM224 352a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z"/></svg>
            <p><?=$error_msg?></p>
            <svg class="close" width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg>
        </div>
    </div>
    <?php endif; ?>

    <div class="content-block">
        
        <div class="form responsive-width-100">

            <label for="name"><span class="required">*</span> Name</label>
            <input type="text" id="name" name="name" value="<?=$template['name']?>" placeholder="Template Name" required>

            <label for="html">HTML</label>
            <textarea id="html" name="html" placeholder="Your HTML template code here..."><?=$template['html']?></textarea>

            <label for="pdf">PDF</label>
            <textarea id="pdf" name="pdf" placeholder="Your PDF generation code here..."><?=$template['pdf']?></textarea>

            <label for="preview">Preview Image</label>
            <input type="file" id="preview" name="preview" accept="image/png">

        </div>
    
    </div>

</form>

<?=template_admin_footer()?>