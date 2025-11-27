<?php
require 'assets/includes/admin_config.php';
include_once '../assets/includes/components.php';
// Default newsletter values
$newsletter = [
    'title' => '',
    'content' => '',
    'attachments' => '',
    'submit_date' => date('Y-m-d H:i:s')
];
// Get all the newsletter templates
$newsletters = $pdo->query('SELECT id, title FROM newsletters ORDER BY title ASC')->fetchAll(PDO::FETCH_ASSOC);
// Get all placeholders
$placeholders = $pdo->query('SELECT placeholder_text FROM custom_placeholders')->fetchAll(PDO::FETCH_ASSOC);
// Attachments
$attachments = [];
if (isset($_FILES['attachments']) && is_array($_FILES['attachments'])) {
    foreach ($_FILES['attachments']['name'] as $key => $name) {
        if ($_FILES['attachments']['error'][$key] == 0) {
            $tmp_name = $_FILES['attachments']['tmp_name'][$key];
            $directory = '../attachments/';
            $fileInfo = pathinfo($name);
            $filename = $fileInfo['filename'];
            $extension = isset($fileInfo['extension']) ? '.' . $fileInfo['extension'] : '';
            $path = $directory . $filename . $extension;
            $counter = 1;
            while (file_exists($path)) {
                $path = $directory . $filename . '-' . $counter . $extension;
                $counter++;
            }
            move_uploaded_file($tmp_name, $path);
            $attachments[] = str_replace('../', '', $path);
        }
    }
}
// Check if the ID param exists
if (isset($_GET['id'])) {
    // Retrieve the newsletter from the database
    $stmt = $pdo->prepare('SELECT * FROM newsletters WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $newsletter = $stmt->fetch(PDO::FETCH_ASSOC);
    // ID param exists, edit an existing newsletter
    $page = 'Edit';
    if (isset($_POST['submit'])) {
        // Update the newsletter
        $current_attachments = isset($_POST['current_attachments']) ? $_POST['current_attachments'] : [];
        $all_attachments = array_merge($current_attachments, $attachments);
        $all_attachments = $all_attachments ? implode(',', $all_attachments) : null;
        $stmt = $pdo->prepare('UPDATE newsletters SET title = ?, content = ?, attachments = ? WHERE id = ?');
        $stmt->execute([ $_POST['title'], $_POST['content'], $all_attachments, $_GET['id'] ]);
        header('Location: newsletters.php?success_msg=2');
        exit;
    }
    if (isset($_POST['delete'])) {
        // Delete the newsletter
        header('Location: newsletters.php?delete=' . $_GET['id']);
        exit;
    }
} else {
    // Create a new newsletter
    $page = 'Create';
    if (isset($_POST['submit'])) {
        $current_attachments = isset($_POST['current_attachments']) ? $_POST['current_attachments'] : [];
        $all_attachments = array_merge($current_attachments, $attachments);
        $all_attachments = $all_attachments ? implode(',', $all_attachments) : null;
        $stmt = $pdo->prepare('INSERT INTO newsletters (title,content,attachments,submit_date) VALUES (?,?,?,?)');
        $stmt->execute([ $_POST['title'], $_POST['content'], $all_attachments, date('Y-m-d H:i:s') ]);
        header('Location: newsletters.php?success_msg=1');
        exit;
    }
}
// If copying an existing newsletter
if (isset($_GET['copy'])) {
    // Retrieve the newsletter from the database
    $stmt = $pdo->prepare('SELECT * FROM newsletters WHERE id = ?');
    $stmt->execute([ $_GET['copy'] ]);
    $newsletter = $stmt->fetch(PDO::FETCH_ASSOC);
    // Update title
    if ($newsletter) {
        $newsletter['title'] .= ' Copy';
    }
}
?>
<?=template_admin_header($page . ' Newsletter', 'newsletters', 'manage')?>

<?=generate_breadcrumbs([
    ['label' => 'Newsletter System', 'url' => 'index.php'],
    ['label' => 'Newsletters', 'url' => 'newsletters.php'],
    ['label' => $page . ' Newsletter']
])?>

<div class="content-title mb-3">
    <div class="title">
        <i class="fa-solid fa-newspaper"></i>
        <div class="txt">
            <h2><?=$page?> Newsletter</h2>
            <p><?=$page == 'Edit' ? 'Modify newsletter template' : 'Create new newsletter template'?></p>
        </div>
    </div>
</div>

<form method="post" enctype="multipart/form-data">

    <div class="form-professional">
        
        <div class="form-section">
            <h3 class="section-title">Newsletter Details</h3>

            <div class="form-group">
                <label for="title"><span class="required">*</span> Title</label>
                <input id="title" type="text" name="title" placeholder="Title" value="<?=htmlspecialchars($newsletter['title'], ENT_QUOTES)?>" required>
            </div>

            <div class="form-group">
                <label for="attachments">Attachments</label>
            <div class="attachments">
                <?php foreach(explode(',', $newsletter['attachments'] ?? '') as $attachment): ?>
                <?php if (!$attachment) continue; ?>
                <div class="attachment-wrapper">   
                    <label class="attachment">
                        <svg width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M384 480l48 0c11.4 0 21.9-6 27.6-15.9l112-192c5.8-9.9 5.8-22.1 .1-32.1S555.5 224 544 224l-400 0c-11.4 0-21.9 6-27.6 15.9L48 357.1 48 96c0-8.8 7.2-16 16-16l117.5 0c4.2 0 8.3 1.7 11.3 4.7l26.5 26.5c21 21 49.5 32.8 79.2 32.8L416 144c8.8 0 16 7.2 16 16l0 32 48 0 0-32c0-35.3-28.7-64-64-64L298.5 96c-17 0-33.3-6.7-45.3-18.7L226.7 50.7c-12-12-28.3-18.7-45.3-18.7L64 32C28.7 32 0 60.7 0 96L0 416c0 35.3 28.7 64 64 64l23.7 0L384 480z"/></svg>
                        <span><?=htmlspecialchars(basename($attachment), ENT_QUOTES)?></span>
                        <input type="file" name="attachments[]">
                        <input type="hidden" name="current_attachments[]" value="<?=$attachment?>">
                    </label>
                </div>
                <?php endforeach; ?>     
                <div class="attachment-wrapper">              
                    <label class="attachment">
                        <svg width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M384 480l48 0c11.4 0 21.9-6 27.6-15.9l112-192c5.8-9.9 5.8-22.1 .1-32.1S555.5 224 544 224l-400 0c-11.4 0-21.9 6-27.6 15.9L48 357.1 48 96c0-8.8 7.2-16 16-16l117.5 0c4.2 0 8.3 1.7 11.3 4.7l26.5 26.5c21 21 49.5 32.8 79.2 32.8L416 144c8.8 0 16 7.2 16 16l0 32 48 0 0-32c0-35.3-28.7-64-64-64L298.5 96c-17 0-33.3-6.7-45.3-18.7L226.7 50.7c-12-12-28.3-18.7-45.3-18.7L64 32C28.7 32 0 60.7 0 96L0 416c0 35.3 28.7 64 64 64l23.7 0L384 480z"/></svg>
                        <span>Select File</span>
                        <input type="file" name="attachments[]">
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label for="content">Content <span class="required">*</span></label>
            </div>
        </div>

        <?php if (template_editor == 'tinymce'): ?>
        <div class="pad-x-4 pad-bot-5" style="max-width:1040px">
            <textarea id="content" name="content" style="width:100%;height:600px;" wrap="off" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"><?=htmlspecialchars($newsletter['content'], ENT_QUOTES)?></textarea>
        </div>
        <?php else: ?>
        <div class="newsletter-editor">
            <div class="header">
                <div class="format-btns">
                    <span>Insert Tag</span>
                    <a href="#" class="format-btn div">Div</a>
                    <a href="#" class="format-btn heading">Heading</a>
                    <a href="#" class="format-btn paragraph">Paragraph</a>
                    <a href="#" class="format-btn strong">Strong</a>
                    <a href="#" class="format-btn italic">Italic</a>
                    <a href="#" class="format-btn image">Image</a>
                </div>
                <div class="preview-btn">
                    <a href="#" class="btn"><svg class="icon-left" width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12,9A3,3 0 0,0 9,12A3,3 0 0,0 12,15A3,3 0 0,0 15,12A3,3 0 0,0 12,9M12,17A5,5 0 0,1 7,12A5,5 0 0,1 12,7A5,5 0 0,1 17,12A5,5 0 0,1 12,17M12,4.5C7,4.5 2.73,7.61 1,12C2.73,16.39 7,19.5 12,19.5C17,19.5 21.27,16.39 23,12C21.27,7.61 17,4.5 12,4.5Z" /></svg>Preview</a>
                </div>
            </div>
            <textarea id="content" name="content" placeholder="Enter your HTML template..." wrap="off" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"><?=htmlspecialchars($newsletter['content'], ENT_QUOTES)?></textarea>
        </div>
        <?php endif; ?>
        
        <div class="form-actions">
            <a href="newsletters.php" class="btn btn-secondary">Cancel</a>
            <?php if ($page == 'Edit'): ?>
            <input type="submit" name="delete" value="Delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this newsletter?')">
            <?php endif; ?>
            <input type="submit" name="submit" value="Save" class="btn btn-success">
        </div>

    </div>
</form>

<?php if (template_editor == 'tinymce'): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/7.3.0/tinymce.min.js" integrity="sha512-RUZ2d69UiTI+LdjfDCxqJh5HfjmOcouct56utQNVRjr90Ea8uHQa+gCxvxDTC9fFvIGP+t4TDDJWNTRV48tBpQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
const newsletters = <?=json_encode($newsletters)?>;
tinymce.init({
    selector: '#content',
    plugins: 'image table lists media link code',
    toolbar: 'undo redo | insert_template insert_meta | blocks | formatselect | bold italic forecolor | align | outdent indent | numlist bullist | table image link | code',
    menubar: 'edit view insert format tools table',
    valid_elements: '*[*]',
    extended_valid_elements: '*[*]',
    valid_children: '+body[style]',
    content_css: false,
    height: 600,
    branding: false,
    promotion: false,
    automatic_uploads: true,
    images_upload_url: 'sendmail.php',
    // Link settings - preserve full URLs and avoid protocol stripping
    link_default_protocol: 'https',
    link_assume_external_targets: false,
    allow_unsafe_link_target: true,
    convert_urls: false,
    relative_urls: false,
    remove_script_host: false,
    image_title: true,
    image_description: true,
    license_key: 'gpl',
    setup: function (editor) {
        editor.ui.registry.addMenuButton('insert_meta', {
            icon: 'addtag',
            tooltip: 'Insert Meta Tag',
            fetch: function (callback) {
                const items = [
                    {
                        type: 'menuitem',
                        text: 'Insert Open Tracking Code',
                        onAction: function () {
                            editor.insertContent('%open_tracking_code%');
                        }
                    },
                    {
                        type: 'menuitem',
                        text: 'Insert Click Link',
                        onAction: function () {
                            editor.insertContent('%click_link%');
                        }
                    },
                    {
                        type: 'menuitem',
                        text: 'Insert Unsubscribe Link',
                        onAction: function () {
                            editor.insertContent('%unsubscribe_link%');
                        }
                    },
                    {
                        type: 'menuitem',
                        text: 'Insert Website URL',
                        onAction: function () {
                            editor.insertContent('%website_url%');
                        }
                    },
                    {
                        type: 'menuitem',
                        text: 'Insert Name',
                        onAction: function () {
                            editor.insertContent('%name%');
                        }
                    },
                    {
                        type: 'menuitem',
                        text: 'Insert Current Date',
                        onAction: function () {
                            editor.insertContent('%date%');
                        }
                    },
                    {
                        type: 'menuitem',
                        text: 'Insert Current Time',
                        onAction: function () {
                            editor.insertContent('%time%');
                        }
                    },
                    {
                        type: 'menuitem',
                        text: 'Insert Year',
                        onAction: function () {
                            editor.insertContent('%year%');
                        }
                    },
                    {
                        type: 'menuitem',
                        text: 'Insert Month',
                        onAction: function () {
                            editor.insertContent('%month%');
                        }
                    },
                    {
                        type: 'menuitem',
                        text: 'Insert Day',
                        onAction: function () {
                            editor.insertContent('%day%');
                        }
                    }
                    <?php if ($placeholders): ?>
                    ,{
                        type: 'nestedmenuitem',
                        text: 'Custom Placeholders',
                        getSubmenuItems: function () {
                            return <?=json_encode($placeholders)?>.map(function(placeholder) {
                                return {
                                    type: 'menuitem',
                                    text: placeholder.placeholder_text,
                                    onAction: function () {
                                        editor.insertContent(placeholder.placeholder_text);
                                    }
                                };
                            });
                        }
                    }
                    <?php endif; ?>
                ];
                callback(items);
            }
        });
        editor.ui.registry.addMenuButton('insert_template', {
            icon: 'template',
            tooltip: 'Use Existing Template',
            fetch: function (callback) {
                const items = newsletters.map(function(newsletter) {
                    return {
                        type: 'menuitem',
                        text: newsletter.title,
                        onAction: function () {
                            fetch('sendmail.php?newsletter=' + newsletter.id).then(response => response.json()).then(data => {
                                editor.setContent(data.content);
                            });
                        }
                    };
                });
                callback(items);
            }
        });
    }
});
</script>
<?php endif; ?>

<?=template_admin_footer()?>