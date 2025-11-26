<?php
require 'assets/includes/admin_config.php';
// Get all placeholders
$placeholders = $pdo->query('SELECT * FROM custom_placeholders')->fetchAll(PDO::FETCH_ASSOC);
// If submit form, send mail to the specified recipient
if (isset($_POST['subject'])) {
    include_once 'functions.php';
    // Get attachments
    $attachments = isset($_POST['attachments']) ? $_POST['attachments'] : [];
    $attachments = array_map(function($attachment) {
        return '../' . $attachment;
    }, $attachments);
    // Replace placeholders in the content
    $content = $_POST['content'];
    foreach ($placeholders as $placeholder) {
        $content = str_replace($placeholder['placeholder_text'], $placeholder['placeholder_value'], $content);
    }
    // Get subscriber from database
    $stmt = $pdo->prepare('SELECT * FROM subscribers WHERE email = ?');
    $stmt->execute([ $_POST['recipient'] ]);
    $subscriber = $stmt->fetch(PDO::FETCH_ASSOC);
    $unsubscribe_link = $subscriber ? website_url . 'unsubscribe.php?id=' . sha1($subscriber['id'] . $subscriber['email']) : '';
    // Update unsubscribe link
    $content = str_replace('%unsubscribe_link%', $unsubscribe_link, $content);
    // Remove unnecessary placeholders
    $content = str_replace('%open_tracking_code%', '', $content);
    $content = str_replace('%click_link%', '', $content);
    // Send the mail
    $response = admin_sendmail($_POST['from'], $_POST['from_name'], $_POST['recipient'], $_POST['subject'], $content, $attachments);
    exit($response);
}
// iterate attachments and move files to the attachments directory
if (isset($_FILES['attachments']) && is_array($_FILES['attachments'])) {
    $attachments = [];
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
    // output as json
    header('Content-Type: application/json');
    exit(json_encode($attachments));
}
// Get newsletter by ID
if (isset($_GET['newsletter'])) {
    $stmt = $pdo->prepare('SELECT content FROM newsletters WHERE id = ?');
    $stmt->execute([ $_GET['newsletter'] ]);
    $newsletter = $stmt->fetch(PDO::FETCH_ASSOC);
    // output as json
    header('Content-Type: application/json');
    exit(json_encode($newsletter));
}
// Retrieve subscribers from the database
$stmt = $pdo->prepare('SELECT * FROM subscribers WHERE status = "Subscribed" AND confirmed = 1 ORDER BY email ASC');
$stmt->execute();
$subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Get all the newsletter templates
$newsletters = $pdo->query('SELECT id, title FROM newsletters ORDER BY title ASC')->fetchAll(PDO::FETCH_ASSOC);
?>
<?=template_admin_header('Send Mail', 'sendmail')?>

<div class="content-title mb-3">
    <div class="title">
        <div class="icon">
            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M17 2H2V17H4V4H17V2M21 22L18.5 20.32L16 22L13.5 20.32L11 22L8.5 20.32L6 22V6H21V22M10 10V12H17V10H10M15 14H10V16H15V14Z" /></svg>
        </div>
        <div class="txt">
            <h2>Send Email</h2>
            <p>Send Email to Subscribers.</p>
        </div>
    </div>
             
</div>
<br><br>
<form method="post" class="send-mail-form" enctype="multipart/form-data">

 <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
       
    <div class="content-title mb-3">
        <div class="btns">
            <input type="submit" name="submit" value="Submit" class="btn green">
        </div>
    </div>

    <div class="content-block">

        <div class="form responsive-width-100">

            <label for="subject"><span class="required">*</span> Subject</label>
            <input id="subject" type="text" name="subject" placeholder="Subject" required>

            <div class="group">
                <div class="item">
                    <label for="from"><span class="required">*</span> From Email</label>
                    <input id="from" type="email" name="from" placeholder="From Email" value="<?=mail_from?>" required>
                </div>
                <div class="item">
                    <label for="from_name"><span class="required">*</span> From Name</label>
                    <input id="from_name" type="text" name="from_name" placeholder="From Name" value="<?=htmlspecialchars(mail_from_name, ENT_QUOTES)?>" required>
                </div>
            </div>

            <label for="recipients"><span class="required">*</span> Recipients</label>
            <div class="multi-checkbox recipients-multi-checkbox">
                <div class="item check-all">
                    <input id="check-all" type="checkbox">
                    <input type="text" placeholder="Search...">
                </div>
                <div class="con">
                    <?php foreach ($subscribers as $subscriber): ?>
                    <div class="item">
                        <input id="checkbox-<?=$subscriber['id']?>" type="checkbox" name="recipients[]" value="<?=$subscriber['email']?>">
                        <label for="checkbox-<?=$subscriber['id']?>"><?=$subscriber['email']?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
         <!--    <a href="#" class="add-link add-additional-recipients">+ Add Custom Recipients</a> -->

            <label for="attachments">Attachments</label>
            <div class="attachments">
                <div class="attachment-wrapper">
                    <label class="attachment">
                        <svg width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M384 480l48 0c11.4 0 21.9-6 27.6-15.9l112-192c5.8-9.9 5.8-22.1 .1-32.1S555.5 224 544 224l-400 0c-11.4 0-21.9 6-27.6 15.9L48 357.1 48 96c0-8.8 7.2-16 16-16l117.5 0c4.2 0 8.3 1.7 11.3 4.7l26.5 26.5c21 21 49.5 32.8 79.2 32.8L416 144c8.8 0 16 7.2 16 16l0 32 48 0 0-32c0-35.3-28.7-64-64-64L298.5 96c-17 0-33.3-6.7-45.3-18.7L226.7 50.7c-12-12-28.3-18.7-45.3-18.7L64 32C28.7 32 0 60.7 0 96L0 416c0 35.3 28.7 64 64 64l23.7 0L384 480z"/></svg>
                        <span>Select File</span>
                        <input type="file" name="attachments[]">
                    </label>
                    <a href="#" class="remove">&times;</a>
                </div>
            </div>

            <label for="content"><span class="required">*</span> <?=template_editor == 'tinymce'?'':'HTML '?>Email Template</label>
        </div>

        <?php if (template_editor == 'tinymce'): ?>
        <div class="pad-x-4 pad-bot-5" style="max-width:1040px">
            <textarea id="content" name="content" style="width:100%;height:600px;" wrap="off" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"></textarea>
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
            <textarea id="content" name="content" placeholder="Enter your HTML template..." wrap="off" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"></textarea>
        </div>
        <?php endif; ?>

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
    automatic_uploads: false,
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