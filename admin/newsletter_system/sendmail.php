<?php
require 'assets/includes/admin_config.php';
include_once '../assets/includes/components.php';

// Handle image uploads for newsletter editor
if (isset($_FILES['newsletter_image'])) {
    $upload_dir = 'uploads/';
    
    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file = $_FILES['newsletter_image'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
    
    if (!in_array($file['type'], $allowed_types)) {
        header('Content-Type: application/json');
        exit(json_encode(['error' => 'Invalid file type. Only images are allowed.']));
    }
    
    if ($file['error'] === 0) {
        $fileInfo = pathinfo($file['name']);
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $fileInfo['filename']);
        $extension = isset($fileInfo['extension']) ? '.' . strtolower($fileInfo['extension']) : '';
        $path = $upload_dir . $filename . $extension;
        $counter = 1;
        
        // Handle duplicate filenames
        while (file_exists($path)) {
            $path = $upload_dir . $filename . '_(' . $counter . ')' . $extension;
            $counter++;
        }
        
        // Resize image if it's not SVG (SVG is vector and doesn't need resizing)
        if ($extension !== '.svg') {
            // Get original image dimensions
            list($orig_width, $orig_height) = getimagesize($file['tmp_name']);
            
            // Maximum dimensions for email images (800px width is good for most emails)
            $max_width = 800;
            $max_height = 1200;
            
            // Calculate new dimensions while maintaining aspect ratio
            $resize_needed = false;
            if ($orig_width > $max_width || $orig_height > $max_height) {
                $resize_needed = true;
                $ratio = min($max_width / $orig_width, $max_height / $orig_height);
                $new_width = round($orig_width * $ratio);
                $new_height = round($orig_height * $ratio);
            } else {
                $new_width = $orig_width;
                $new_height = $orig_height;
            }
            
            // Create image resource based on type
            switch ($file['type']) {
                case 'image/jpeg':
                    $source = imagecreatefromjpeg($file['tmp_name']);
                    break;
                case 'image/png':
                    $source = imagecreatefrompng($file['tmp_name']);
                    break;
                case 'image/gif':
                    $source = imagecreatefromgif($file['tmp_name']);
                    break;
                case 'image/webp':
                    $source = imagecreatefromwebp($file['tmp_name']);
                    break;
                default:
                    // Fallback to original upload if type not supported
                    if (move_uploaded_file($file['tmp_name'], $path)) {
                        header('Content-Type: application/json');
                        exit(json_encode(['location' => $path]));
                    }
                    header('Content-Type: application/json');
                    exit(json_encode(['error' => 'Failed to upload image']));
            }
            
            // Create new image with resized dimensions
            $destination = imagecreatetruecolor($new_width, $new_height);
            
            // Preserve transparency for PNG and GIF
            if ($file['type'] === 'image/png' || $file['type'] === 'image/gif') {
                imagealphablending($destination, false);
                imagesavealpha($destination, true);
                $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
                imagefilledrectangle($destination, 0, 0, $new_width, $new_height, $transparent);
            }
            
            // Copy and resize
            imagecopyresampled($destination, $source, 0, 0, 0, 0, $new_width, $new_height, $orig_width, $orig_height);
            
            // Save the resized image with quality optimization
            switch ($file['type']) {
                case 'image/jpeg':
                    imagejpeg($destination, $path, 85); // 85% quality is good balance
                    break;
                case 'image/png':
                    imagepng($destination, $path, 8); // Compression level 8 (0-9)
                    break;
                case 'image/gif':
                    imagegif($destination, $path);
                    break;
                case 'image/webp':
                    imagewebp($destination, $path, 85);
                    break;
            }
            
            // Free memory
            imagedestroy($source);
            imagedestroy($destination);
            
            // Use BASE_URL for absolute URL
            $base_url = defined('BASE_URL') ? BASE_URL : 'https://glitchwizarddigitalsolutions.com/';
            
            header('Content-Type: application/json');
            exit(json_encode([
                'location' => $base_url . 'admin/newsletter_system/' . $path,
                'resized' => $resize_needed,
                'original_size' => $orig_width . 'x' . $orig_height,
                'new_size' => $new_width . 'x' . $new_height
            ]));
        } else {
            // SVG - just move it without processing
            if (move_uploaded_file($file['tmp_name'], $path)) {
                $base_url = defined('BASE_URL') ? BASE_URL : 'https://glitchwizarddigitalsolutions.com/';
                header('Content-Type: application/json');
                exit(json_encode(['location' => $base_url . 'admin/newsletter_system/' . $path]));
            }
        }
    }
    
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Failed to upload image']));
}

// Get list of uploaded images for the image browser
if (isset($_GET['list_images'])) {
    $upload_dir = 'uploads/';
    $images = [];
    
    if (is_dir($upload_dir)) {
        $files = scandir($upload_dir);
        $base_url = defined('BASE_URL') ? BASE_URL : 'https://glitchwizarddigitalsolutions.com/';
        
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $filepath = $upload_dir . $file;
                if (is_file($filepath) && preg_match('/\.(jpg|jpeg|png|gif|webp|svg)$/i', $file)) {
                    $images[] = [
                        'title' => $file,
                        'value' => $base_url . 'admin/newsletter_system/' . $filepath,
                        'modified' => filemtime($filepath)
                    ];
                }
            }
        }
    }
    
    // Sort by modified date, newest first
    usort($images, function($a, $b) {
        return $b['modified'] - $a['modified'];
    });
    
    header('Content-Type: application/json');
    exit(json_encode($images));
}

// Get all placeholders
$placeholders = $pdo->query('SELECT * FROM custom_placeholders')->fetchAll(PDO::FETCH_ASSOC);
// If submit form, send mail to the specified recipients
if (isset($_POST['subject'])) {
    include_once 'functions.php';
    
    // Validate recipients
    if (!isset($_POST['recipients']) || !is_array($_POST['recipients']) || empty($_POST['recipients'])) {
        exit('Error: No recipients selected');
    }
    
    // Get attachments
    $attachments = isset($_POST['attachments']) ? $_POST['attachments'] : [];
    $attachments = array_map(function($attachment) {
        return '../' . $attachment;
    }, $attachments);
    
    // Send email to each recipient
    $success_count = 0;
    $failed_count = 0;
    $errors = [];
    
    foreach ($_POST['recipients'] as $recipient_email) {
        // Replace placeholders in the content for each recipient
        $content = $_POST['content'];
        
        // First replace custom placeholders
        foreach ($placeholders as $placeholder) {
            $content = str_replace($placeholder['placeholder_text'], $placeholder['placeholder_value'], $content);
        }
        
        // Get subscriber from database
        $stmt = $pdo->prepare('SELECT * FROM subscribers WHERE email = ?');
        $stmt->execute([ $recipient_email ]);
        $subscriber = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Use BASE_URL instead of broken website_url
        $base_url = defined('BASE_URL') ? BASE_URL : 'https://glitchwizarddigitalsolutions.com/';
        
        if ($subscriber) {
            // Generate unique tracking code for this subscriber
            $tracking_code = sha1($subscriber['id'] . $subscriber['email'] . time());
            $unsubscribe_link = $base_url . 'unsubscribe.php?id=' . sha1($subscriber['id'] . $subscriber['email']);
            
            // Replace tracking placeholders with actual tracking codes (PUBLIC URLs, not in /admin/)
            $content = str_replace('%open_tracking_code%', '<img src="' . $base_url . 'tracking.php?action=open&id=' . $tracking_code . '" width="1" height="1" alt="">', $content);
            $content = str_replace('%click_link%', $base_url . 'tracking.php?action=click&id=' . $tracking_code . '&url=', $content);
            $content = str_replace('%unsubscribe_link%', $unsubscribe_link, $content);
        } else {
            // For non-subscribers (custom emails), remove tracking codes
            $content = str_replace('%open_tracking_code%', '', $content);
            $content = str_replace('%click_link%', '', $content);
            $content = str_replace('%unsubscribe_link%', '', $content);
        }
        
        // Convert relative image URLs to absolute URLs for email compatibility
        $content = preg_replace('/src="uploads\//', 'src="' . $base_url . 'admin/newsletter_system/uploads/', $content);
        $content = preg_replace('/src="\.\.\/uploads\//', 'src="' . $base_url . 'admin/newsletter_system/uploads/', $content);
        
        // Send the mail
        $response = admin_sendmail($_POST['from'], $_POST['from_name'], $recipient_email, $_POST['subject'], $content, $attachments);
        
        if ($response === 'success') {
            $success_count++;
        } else {
            $failed_count++;
            $errors[] = $recipient_email . ': ' . $response;
        }
    }
    
    // Return results as JSON
    header('Content-Type: application/json');
    if ($failed_count == 0) {
        exit(json_encode([
            'status' => 'success',
            'message' => 'Successfully sent to ' . $success_count . ' recipient' . ($success_count != 1 ? 's' : '') . '!'
        ]));
    } else {
        exit(json_encode([
            'status' => 'partial',
            'message' => 'Sent to ' . $success_count . ' recipient(s), but ' . $failed_count . ' failed.',
            'errors' => $errors
        ]));
    }
}
// iterate attachments and move files to the attachments directory
if (isset($_FILES['attachments']) && is_array($_FILES['attachments']['name'])) {
    $attachments = [];
    $directory = '../attachments/';
    
    // Create directory if it doesn't exist
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    
    foreach ($_FILES['attachments']['name'] as $key => $name) {
        if (!empty($name) && $_FILES['attachments']['error'][$key] == 0) {
            $tmp_name = $_FILES['attachments']['tmp_name'][$key];
            $fileInfo = pathinfo($name);
            $filename = $fileInfo['filename'];
            $extension = isset($fileInfo['extension']) ? '.' . $fileInfo['extension'] : '';
            $path = $directory . $filename . $extension;
            $counter = 1;
            while (file_exists($path)) {
                $path = $directory . $filename . '-' . $counter . $extension;
                $counter++;
            }
            if (move_uploaded_file($tmp_name, $path)) {
                $attachments[] = str_replace('../', '', $path);
            }
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
<?=template_admin_header('Send Newsletter', 'newsletters', 'sendmail')?>

<?=generate_breadcrumbs([
    ['label' => 'Newsletter System', 'url' => 'index.php'],
    ['label' => 'Send Newsletter']
])?>

<div class="content-title mb-3">
    <div class="title">
        <i class="fa-solid fa-envelope"></i>
        <div class="txt">
            <h2>Send Newsletter</h2>
            <p>Send bulk emails to subscribers</p>
        </div>
    </div>
</div>

<form method="post" enctype="multipart/form-data" id="send-newsletter-form">

    <div class="form-professional">
        
        <div class="form-section">
            <h3 class="section-title">Email Details</h3>

            <div class="form-group">
                <label for="subject"><span class="required">*</span> Subject</label>
                <input id="subject" type="text" name="subject" placeholder="Subject" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="from_name"><span class="required">*</span> From Name</label>
                    <input id="from_name" type="text" name="from_name" placeholder="From Name" value="<?=htmlspecialchars(mail_from_name, ENT_QUOTES)?>" required>
                </div>
                <div class="form-group">
                    <label for="from"><span class="required">*</span> From Email</label>
                    <input id="from" type="email" name="from" placeholder="From Email" value="<?=mail_from?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="recipients"><span class="required">*</span> Recipients</label>
                <p style="margin: 0 0 10px 0; color: #666; font-size: 14px;">Select subscribers from the list below or add custom email addresses</p>
                
                <div class="recipients-section">
                    <div class="recipients-controls">
                        <div class="search-box">
                            <i class="fa-solid fa-search"></i>
                            <input type="text" id="recipient-search" placeholder="Search subscribers... (type to filter in real-time)">
                        </div>
                        <div class="action-buttons">
                            <button type="button" class="btn btn-secondary btn-sm" id="select-all-recipients">
                                <i class="fa-solid fa-check-double"></i> Select All
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" id="deselect-all-recipients">
                                <i class="fa-solid fa-times"></i> Deselect All
                            </button>
                            <button type="button" class="btn btn-primary btn-sm add-additional-recipients">
                                <i class="fa-solid fa-plus"></i> Add Custom Email
                            </button>
                        </div>
                    </div>
                    
                    <div class="recipients-table-wrapper">
                        <table class="recipients-table">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">
                                        <input type="checkbox" id="check-all-recipients" title="Select/Deselect All">
                                    </th>
                                    <th>Email Address</th>
                                    <th style="width: 100px;">Source</th>
                                </tr>
                            </thead>
                            <tbody id="recipients-list">
                                <?php foreach ($subscribers as $subscriber): ?>
                                <tr class="recipient-row">
                                    <td>
                                        <input type="checkbox" id="checkbox-<?=$subscriber['id']?>" name="recipients[]" value="<?=$subscriber['email']?>">
                                    </td>
                                    <td>
                                        <label for="checkbox-<?=$subscriber['id']?>" style="cursor: pointer; margin: 0;">
                                            <?=htmlspecialchars($subscriber['email'], ENT_QUOTES)?>
                                        </label>
                                    </td>
                                    <td>
                                        <span class="badge badge-primary">Subscriber</span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="recipients-summary">
                        <span id="selected-count">0</span> recipient(s) selected
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="attachments">Attachments</label>
                <div class="attachments">
                    <div class="attachment-wrapper">
                        <label class="attachment">
                            <svg width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M384 480l48 0c11.4 0 21.9-6 27.6-15.9l112-192c5.8-9.9 5.8-22.1 .1-32.1S555.5 224 544 224l-400 0c-11.4 0-21.9 6-27.6 15.9L48 357.1 48 96c0-8.8 7.2-16 16-16l117.5 0c4.2 0 8.3 1.7 11.3 4.7l26.5 26.5c21 21 49.5 32.8 79.2 32.8L416 144c8.8 0 16 7.2 16 16l0 32 48 0 0-32c0-35.3-28.7-64-64-64L298.5 96c-17 0-33.3-6.7-45.3-18.7L226.7 50.7c-12-12-28.3-18.7-45.3-18.7L64 32C28.7 32 0 60.7 0 96L0 416c0 35.3 28.7 64 64 64l23.7 0L384 480z"/></svg>
                            <span>Select File</span>
                            <input type="file" name="attachments[]">
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="content"><span class="required">*</span> <?=template_editor == 'tinymce'?'':'HTML '?>Email Template</label>
                <?php if (template_editor == 'tinymce'): ?>
                <div class="info-box" style="background: #e7f3ff; border-left: 4px solid #2196F3; padding: 12px; margin-bottom: 15px; border-radius: 4px;">
                    <p style="margin: 0; font-size: 13px; color: #0d47a1;"><strong><i class="fa-solid fa-info-circle"></i> Image Tips:</strong></p>
                    <ul style="margin: 8px 0 0 20px; font-size: 13px; color: #0d47a1;">
                        <li><strong>Alternative Description</strong> - Describes the image for accessibility (goes in alt="" attribute). Shows when image fails to load.</li>
                        <li><strong>Class</strong> - Use "Responsive (Recommended)" for images that auto-fit to email width, or "Full Width" for banner images.</li>
                        <li><strong>Automatic Optimization:</strong> Images larger than 800px wide are automatically resized and compressed for faster email loading!</li>
                        <li>You can still drag corners to resize images visually in the editor if needed.</li>
                        <li>Original aspect ratio is always preserved during resizing.</li>
                        <li>SVG images are not resized (they're vector-based and scale perfectly).</li>
                    </ul>
                </div>
                <div class="info-box" style="background: #fff3e0; border-left: 4px solid #ff9800; padding: 12px; margin-bottom: 15px; border-radius: 4px;">
                    <p style="margin: 0; font-size: 13px; color: #e65100;"><strong><i class="fa-solid fa-code"></i> Available Placeholders:</strong></p>
                    <ul style="margin: 8px 0 0 20px; font-size: 13px; color: #e65100;">
                        <li><strong>%unsubscribe_link%</strong> - Automatically filled with unique unsubscribe URL for each subscriber</li>
                        <li><strong>%open_tracking_code%</strong> - Invisible 1x1 pixel image that tracks when emails are opened (subscribers only)</li>
                        <li><strong>%click_link%</strong> - For click tracking:<br>
                            &nbsp;&nbsp;• Relative URL: <code>&lt;a href="%click_link%/accessibility.php"&gt;Click&lt;/a&gt;</code><br>
                            &nbsp;&nbsp;• Full URL: <code>&lt;a href="%click_link%https://yoursite.com/page.php"&gt;Click&lt;/a&gt;</code></li>
                        <li>Custom placeholders and subscriber-specific data will be replaced automatically</li>
                        <li><em>Note: Images are automatically converted to full URLs for email compatibility</em></li>
                        <li><em>Tracking codes only work for subscribers in the database, not custom email addresses</em></li>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
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
            </div>
            <textarea id="content" name="content" placeholder="Enter your HTML template..." wrap="off" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"></textarea>
        </div>
        <?php endif; ?>
        
        <div class="form-actions">
            <a href="index.php" class="btn btn-secondary">Cancel</a>
            <input type="submit" name="submit" value="Send" class="btn btn-success">
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
    
    // Image settings for email compatibility
    image_dimensions: false,  // Hide width/height input fields
    image_class_list: [
        {title: 'Responsive (Recommended)', value: 'responsive-image'},
        {title: 'Full Width', value: 'full-width-image'},
        {title: 'None', value: ''}
    ],
    
    // Default image settings
    image_advtab: true,
    image_description: true,
    image_title: false,  // Disable title field (not needed for emails)
    
    // Content style for responsive images
    content_style: `
        .responsive-image {
            max-width: 100%;
            height: auto;
            display: block;
        }
        .full-width-image {
            width: 100%;
            height: auto;
            display: block;
        }
    `,
    
    images_upload_handler: function (blobInfo, progress) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'sendmail.php', true);
            
            const formData = new FormData();
            formData.append('newsletter_image', blobInfo.blob(), blobInfo.filename());
            
            xhr.upload.onprogress = (e) => {
                progress(e.loaded / e.total * 100);
            };
            
            xhr.onload = () => {
                if (xhr.status === 200) {
                    const json = JSON.parse(xhr.responseText);
                    if (json.error) {
                        reject(json.error);
                    } else {
                        resolve(json.location);
                    }
                } else {
                    reject('HTTP Error: ' + xhr.status);
                }
            };
            
            xhr.onerror = () => {
                reject('Image upload failed');
            };
            
            xhr.send(formData);
        });
    },
    file_picker_callback: function(callback, value, meta) {
        if (meta.filetype === 'image') {
            // Create a custom image browser modal
            const input = document.createElement('input');
            input.setAttribute('type', 'file');
            input.setAttribute('accept', 'image/*');
            
            input.onchange = function() {
                const file = this.files[0];
                const reader = new FileReader();
                
                reader.onload = function() {
                    // Upload the file
                    const formData = new FormData();
                    formData.append('newsletter_image', file);
                    
                    fetch('sendmail.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            alert(data.error);
                        } else {
                            // Return with default responsive class and alt text
                            callback(data.location, { 
                                alt: file.name.replace(/\.[^/.]+$/, ''),
                                class: 'responsive-image'
                            });
                        }
                    })
                    .catch(error => {
                        alert('Upload failed: ' + error);
                    });
                };
                
                reader.readAsDataURL(file);
            };
            
            // Also show option to browse existing images
            if (confirm('Click OK to upload a new image, or Cancel to browse existing images')) {
                input.click();
            } else {
                // Browse existing images
                fetch('sendmail.php?list_images=1')
                    .then(response => response.json())
                    .then(images => {
                        if (images.length === 0) {
                            alert('No images uploaded yet. Please upload an image first.');
                            input.click();
                            return;
                        }
                        
                        // Create a simple image selector
                        let html = '<div style="padding: 20px; max-height: 400px; overflow-y: auto;">';
                        html += '<h3 style="margin-top: 0;">Select an Image</h3>';
                        html += '<p style="color: #666; font-size: 13px; margin-bottom: 15px;">Images will automatically be responsive in email templates</p>';
                        html += '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px;">';
                        
                        images.forEach((img, index) => {
                            html += `
                                <div style="border: 2px solid #ddd; border-radius: 8px; padding: 10px; cursor: pointer; text-align: center;" 
                                     onclick="selectImage('${img.value}', '${img.title}')" 
                                     onmouseover="this.style.borderColor='#6b46c1'" 
                                     onmouseout="this.style.borderColor='#ddd'">
                                    <img src="${img.value}" style="width: 100%; height: 100px; object-fit: cover; border-radius: 4px;">
                                    <div style="margin-top: 5px; font-size: 11px; color: #666; overflow: hidden; text-overflow: ellipsis;">${img.title}</div>
                                </div>
                            `;
                        });
                        
                        html += '</div>';
                        html += '<div style="margin-top: 20px; text-align: center;">';
                        html += '<button onclick="closeImageBrowser()" style="padding: 8px 20px; background: #6b46c1; color: white; border: none; border-radius: 4px; cursor: pointer;">Cancel</button>';
                        html += '</div>';
                        html += '</div>';
                        
                        const modal = document.createElement('div');
                        modal.id = 'image-browser-modal';
                        modal.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 10000; display: flex; align-items: center; justify-content: center;';
                        
                        const content = document.createElement('div');
                        content.style.cssText = 'background: white; border-radius: 8px; max-width: 800px; width: 90%; max-height: 80vh; overflow: hidden;';
                        content.innerHTML = html;
                        
                        modal.appendChild(content);
                        document.body.appendChild(modal);
                        
                        // Add global functions for image selection
                        window.selectImage = function(src, alt) {
                            callback(src, { 
                                alt: alt.replace(/\.[^/.]+$/, ''),
                                class: 'responsive-image'
                            });
                            closeImageBrowser();
                        };
                        
                        window.closeImageBrowser = function() {
                            document.getElementById('image-browser-modal').remove();
                        };
                    });
            }
        }
    },
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

<style>
.recipients-section {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
}

.recipients-controls {
    padding: 15px;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.search-box {
    position: relative;
    flex: 1;
    min-width: 250px;
}

.search-box i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
}

.search-box input {
    width: 100%;
    padding: 8px 12px 8px 35px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 14px;
}

.action-buttons {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 13px;
}

.recipients-table-wrapper {
    max-height: 400px;
    overflow-y: auto;
}

.recipients-table {
    width: 100%;
    border-collapse: collapse;
}

.recipients-table thead {
    position: sticky;
    top: 0;
    background: white;
    z-index: 10;
}

.recipients-table th {
    padding: 12px;
    text-align: left;
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
    background: #f8f9fa;
}

.recipients-table td {
    padding: 10px 12px;
    border-bottom: 1px solid #f0f0f0;
}

.recipient-row {
    transition: background-color 0.2s;
}

.recipient-row:hover {
    background-color: #f8f9fa;
}

.recipient-row.custom-email {
    background-color: #fff3cd;
}

.recipient-row.custom-email:hover {
    background-color: #ffe69c;
}

.recipients-table input[type="checkbox"] {
    cursor: pointer;
    width: 18px;
    height: 18px;
}

.badge {
    display: inline-block;
    padding: 3px 8px;
    font-size: 11px;
    font-weight: 600;
    border-radius: 12px;
    text-transform: uppercase;
}

.badge-primary {
    background-color: #e7e3f5;
    color: #6b46c1;
}

.badge-warning {
    background-color: #fff3cd;
    color: #856404;
}

.recipients-summary {
    padding: 12px 15px;
    background: #f8f9fa;
    border-top: 1px solid #dee2e6;
    font-weight: 600;
    color: #495057;
}

#selected-count {
    color: #6b46c1;
    font-size: 16px;
}

.remove-recipient {
    color: #dc3545;
    cursor: pointer;
    font-size: 16px;
    padding: 4px 8px;
}

.remove-recipient:hover {
    color: #bd2130;
}
</style>

<script>
// Recipients table functionality
document.addEventListener('DOMContentLoaded', function() {
    const recipientsList = document.getElementById('recipients-list');
    const checkAllCheckbox = document.getElementById('check-all-recipients');
    const searchInput = document.getElementById('recipient-search');
    const selectedCountSpan = document.getElementById('selected-count');
    let customEmailCounter = 0;
    
    // Update selected count
    function updateSelectedCount() {
        const checkedCount = document.querySelectorAll('input[name="recipients[]"]:checked').length;
        selectedCountSpan.textContent = checkedCount;
    }
    
    // Check all functionality
    checkAllCheckbox.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.recipient-row:not([style*="display: none"]) input[type="checkbox"]');
        checkboxes.forEach(cb => cb.checked = this.checked);
        updateSelectedCount();
    });
    
    // Select all button
    document.getElementById('select-all-recipients').addEventListener('click', function() {
        const checkboxes = document.querySelectorAll('.recipient-row:not([style*="display: none"]) input[type="checkbox"]');
        checkboxes.forEach(cb => cb.checked = true);
        checkAllCheckbox.checked = true;
        updateSelectedCount();
    });
    
    // Deselect all button
    document.getElementById('deselect-all-recipients').addEventListener('click', function() {
        const checkboxes = document.querySelectorAll('input[name="recipients[]"]');
        checkboxes.forEach(cb => cb.checked = false);
        checkAllCheckbox.checked = false;
        updateSelectedCount();
    });
    
    // Search functionality
    searchInput.addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('.recipient-row');
        
        rows.forEach(row => {
            const email = row.querySelector('label').textContent.toLowerCase();
            if (email.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
    
    // Prevent Enter key from submitting form when in search box
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
        }
    });
    
    // Update count when individual checkboxes change
    document.addEventListener('change', function(e) {
        if (e.target.name === 'recipients[]') {
            updateSelectedCount();
        }
    });
    
    // Add custom email functionality
    document.querySelector('.add-additional-recipients').addEventListener('click', function() {
        const email = prompt('Enter email address:');
        if (email && email.trim()) {
            // Validate email
            const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            if (!emailRegex.test(email.trim())) {
                alert('Please enter a valid email address');
                return;
            }
            
            // Check if email already exists
            const existingEmails = Array.from(document.querySelectorAll('input[name="recipients[]"]'))
                .map(cb => cb.value.toLowerCase());
            
            if (existingEmails.includes(email.trim().toLowerCase())) {
                alert('This email address is already in the list');
                return;
            }
            
            // Add the custom email to the table
            customEmailCounter++;
            const newRow = document.createElement('tr');
            newRow.className = 'recipient-row custom-email';
            newRow.innerHTML = `
                <td>
                    <input type="checkbox" id="checkbox-custom-${customEmailCounter}" name="recipients[]" value="${email.trim()}" checked>
                </td>
                <td>
                    <label for="checkbox-custom-${customEmailCounter}" style="cursor: pointer; margin: 0;">
                        ${email.trim()}
                    </label>
                </td>
                <td>
                    <span class="badge badge-warning">Custom</span>
                    <i class="fa-solid fa-trash remove-recipient" title="Remove"></i>
                </td>
            `;
            
            recipientsList.appendChild(newRow);
            
            // Add remove functionality
            newRow.querySelector('.remove-recipient').addEventListener('click', function() {
                if (confirm('Remove this email address?')) {
                    newRow.remove();
                    updateSelectedCount();
                }
            });
            
            updateSelectedCount();
        }
    });
    
    // Initialize count
    updateSelectedCount();
    
    // Handle form submission
    const form = document.getElementById('send-newsletter-form');
    const submitButton = form.querySelector('input[type="submit"]');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Check if TinyMCE is being used and save content
        if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
            tinymce.get('content').save();
        }
        
        // Validate recipients
        const checkedRecipients = document.querySelectorAll('input[name="recipients[]"]:checked').length;
        if (checkedRecipients === 0) {
            alert('Please select at least one recipient!');
            return;
        }
        
        // Disable submit button and show loading state
        submitButton.disabled = true;
        submitButton.value = 'Sending...';
        submitButton.style.opacity = '0.6';
        
        // Submit form via AJAX
        const formData = new FormData(form);
        
        fetch('sendmail.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // Re-enable submit button
            submitButton.disabled = false;
            submitButton.value = 'Send';
            submitButton.style.opacity = '1';
            
            if (data.status === 'success') {
                // Show success message
                alert('✓ ' + data.message);
                
                // Optionally clear the form or redirect
                // form.reset();
                // if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                //     tinymce.get('content').setContent('');
                // }
            } else if (data.status === 'partial') {
                // Show partial success message
                let errorDetails = '';
                if (data.errors && data.errors.length > 0) {
                    errorDetails = '\\n\\nErrors:\\n' + data.errors.join('\\n');
                }
                alert('⚠ ' + data.message + errorDetails);
            } else {
                alert('Error: ' + (data.message || 'Unknown error occurred'));
            }
        })
        .catch(error => {
            // Re-enable submit button
            submitButton.disabled = false;
            submitButton.value = 'Send';
            submitButton.style.opacity = '1';
            
            alert('Error sending newsletter: ' + error);
            console.error('Error:', error);
        });
    });
});
</script>

<?=template_admin_footer()?>