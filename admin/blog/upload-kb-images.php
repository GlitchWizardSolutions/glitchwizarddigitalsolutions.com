<?php
// Knowledge Base Image Upload Form
require '../assets/includes/admin_config.php';
include_once '../assets/includes/components.php';

// Define required images with descriptions
$required_images = [
    // Article 1: How to Use the Knowledge Base
    'kb-overview-menu.png' => [
        'title' => 'Knowledge Base Menu Item',
        'description' => 'Screenshot showing the Knowledge Base link in the main navigation menu',
        'width' => 800,
        'height' => 600
    ],
    'kb-overview-homepage.png' => [
        'title' => 'Knowledge Base Homepage',
        'description' => 'Screenshot of the Knowledge Base homepage showing featured articles and recent posts',
        'width' => 1200,
        'height' => 800
    ],
    'kb-overview-categories.png' => [
        'title' => 'Category Navigation',
        'description' => 'Screenshot showing how to navigate categories and filter articles',
        'width' => 800,
        'height' => 600
    ],
    
    // Article 2: How to Use the Ticketing System
    'ticket-submit-menu.png' => [
        'title' => 'Submit Ticket Menu',
        'description' => 'Screenshot showing the Communication > Submit Ticket menu item',
        'width' => 800,
        'height' => 600
    ],
    'ticket-submit-form.png' => [
        'title' => 'Ticket Submission Form',
        'description' => 'Screenshot of the complete ticket submission form with all fields visible',
        'width' => 1200,
        'height' => 900
    ],
    'ticket-submit-button.png' => [
        'title' => 'Submit Ticket Button',
        'description' => 'Close-up of the Submit Ticket button with arrow pointing to it',
        'width' => 600,
        'height' => 400
    ],
    'ticket-view-list.png' => [
        'title' => 'My Tickets List',
        'description' => 'Screenshot of the My Tickets page showing list of tickets',
        'width' => 1200,
        'height' => 800
    ],
    'ticket-view-detail.png' => [
        'title' => 'Ticket Detail View',
        'description' => 'Screenshot of a single ticket showing conversation and reply box',
        'width' => 1200,
        'height' => 900
    ],
    
    // Article 3: How to Use the Documentation System
    'docs-menu.png' => [
        'title' => 'Documents Menu',
        'description' => 'Screenshot showing the Documents menu item in navigation',
        'width' => 800,
        'height' => 600
    ],
    'docs-upload-button.png' => [
        'title' => 'Upload Document Button',
        'description' => 'Screenshot highlighting the Upload button with arrow',
        'width' => 800,
        'height' => 600
    ],
    'docs-upload-form.png' => [
        'title' => 'Document Upload Form',
        'description' => 'Screenshot of the document upload interface',
        'width' => 1000,
        'height' => 700
    ],
    'docs-file-list.png' => [
        'title' => 'Documents List',
        'description' => 'Screenshot showing list of uploaded documents with download buttons',
        'width' => 1200,
        'height' => 800
    ],
    'docs-download-button.png' => [
        'title' => 'Download Button',
        'description' => 'Close-up of download button with arrow pointing to it',
        'width' => 600,
        'height' => 400
    ],
    
    // Article 4: How to View and Pay Invoices
    'invoice-notification.png' => [
        'title' => 'Invoice Notification',
        'description' => 'Screenshot of the invoice notification bell in the header',
        'width' => 800,
        'height' => 600
    ],
    'invoice-list.png' => [
        'title' => 'Invoice List',
        'description' => 'Screenshot showing list of invoices with statuses',
        'width' => 1200,
        'height' => 800
    ],
    'invoice-detail.png' => [
        'title' => 'Invoice Detail View',
        'description' => 'Screenshot of a complete invoice showing all line items',
        'width' => 1000,
        'height' => 1200
    ],
    'invoice-pay-button.png' => [
        'title' => 'Pay Invoice Button',
        'description' => 'Close-up of the Pay with PayPal button with arrow',
        'width' => 600,
        'height' => 400
    ],
    'invoice-payment-complete.png' => [
        'title' => 'Payment Confirmation',
        'description' => 'Screenshot of the payment success message',
        'width' => 800,
        'height' => 600
    ],
    
    // Article 5: How to Update Your Profile
    'profile-menu.png' => [
        'title' => 'Profile Menu',
        'description' => 'Screenshot showing profile dropdown menu in header',
        'width' => 600,
        'height' => 400
    ],
    'profile-edit-form.png' => [
        'title' => 'Edit Profile Form',
        'description' => 'Screenshot of the complete profile editing form',
        'width' => 1200,
        'height' => 1000
    ],
    'profile-business-section.png' => [
        'title' => 'Business Information Section',
        'description' => 'Screenshot of business profile fields',
        'width' => 1000,
        'height' => 800
    ],
    'profile-save-button.png' => [
        'title' => 'Save Profile Button',
        'description' => 'Close-up of Save button with arrow',
        'width' => 600,
        'height' => 400
    ],
    
    // Article 6: How to Change Username/Password
    'password-change-form.png' => [
        'title' => 'Change Password Form',
        'description' => 'Screenshot of the password change interface',
        'width' => 800,
        'height' => 600
    ],
    'password-requirements.png' => [
        'title' => 'Password Requirements',
        'description' => 'Screenshot showing password requirements/strength indicator',
        'width' => 600,
        'height' => 400
    ],
    'username-change-form.png' => [
        'title' => 'Change Username Form',
        'description' => 'Screenshot of username change interface',
        'width' => 800,
        'height' => 600
    ]
];

// Handle image upload
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image']) && isset($_POST['image_key'])) {
    $image_key = $_POST['image_key'];
    
    if (!isset($required_images[$image_key])) {
        $error_msg = 'Invalid image key.';
    } else {
        $image_config = $required_images[$image_key];
        $upload_dir = __DIR__ . '/../../client-dashboard/blog/assets/img/kb/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file = $_FILES['image'];
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error_msg = 'Upload failed with error code: ' . $file['error'];
        } else {
            // Validate image
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mime_type, $allowed_types)) {
                $error_msg = 'Invalid file type. Please upload a JPG, PNG, GIF, or WebP image.';
            } else {
                // Resize and save image
                $source = imagecreatefromstring(file_get_contents($file['tmp_name']));
                if ($source === false) {
                    $error_msg = 'Failed to process image.';
                } else {
                    $orig_width = imagesx($source);
                    $orig_height = imagesy($source);
                    
                    // Calculate new dimensions maintaining aspect ratio
                    $max_width = $image_config['width'];
                    $max_height = $image_config['height'];
                    
                    $ratio = min($max_width / $orig_width, $max_height / $orig_height);
                    $new_width = intval($orig_width * $ratio);
                    $new_height = intval($orig_height * $ratio);
                    
                    // Create resized image
                    $resized = imagecreatetruecolor($new_width, $new_height);
                    
                    // Preserve transparency for PNG and GIF
                    if ($mime_type === 'image/png' || $mime_type === 'image/gif') {
                        imagealphablending($resized, false);
                        imagesavealpha($resized, true);
                        $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
                        imagefilledrectangle($resized, 0, 0, $new_width, $new_height, $transparent);
                    }
                    
                    imagecopyresampled($resized, $source, 0, 0, 0, 0, $new_width, $new_height, $orig_width, $orig_height);
                    
                    // Save image
                    $target_path = $upload_dir . $image_key;
                    $saved = false;
                    
                    if ($mime_type === 'image/png') {
                        $saved = imagepng($resized, $target_path, 9);
                    } elseif ($mime_type === 'image/gif') {
                        $saved = imagegif($resized, $target_path);
                    } else {
                        $saved = imagejpeg($resized, $target_path, 90);
                    }
                    
                    imagedestroy($source);
                    imagedestroy($resized);
                    
                    if ($saved) {
                        $success_msg = "Image '{$image_key}' uploaded successfully! (Resized to {$new_width}x{$new_height})";
                    } else {
                        $error_msg = 'Failed to save image.';
                    }
                }
            }
        }
    }
}

// Check which images are already uploaded
$upload_dir = __DIR__ . '/../../client-dashboard/blog/assets/img/kb/';
$uploaded_images = [];
if (is_dir($upload_dir)) {
    foreach ($required_images as $filename => $config) {
        if (file_exists($upload_dir . $filename)) {
            $uploaded_images[] = $filename;
        }
    }
}

?>
<?=template_admin_header('Knowledge Base Image Upload', 'blog', 'kb-images')?>

<?=generate_breadcrumbs([
    ['label' => 'Blog System', 'url' => 'blog_dash.php'],
    ['label' => 'KB Image Upload']
])?>

<div class="content-title">
    <div class="title">
        <i class="fa-solid fa-images"></i>
        <div class="txt">
            <h2>Knowledge Base Image Upload</h2>
            <p>Upload screenshots for Knowledge Base articles</p>
        </div>
    </div>
</div>

<?php if ($success_msg): ?>
<div class="msg success">
    <i class="fas fa-check-circle"></i>
    <p><?=$success_msg?></p>
    <i class="fas fa-times"></i>
</div>
<?php endif; ?>

<?php if ($error_msg): ?>
<div class="msg error">
    <i class="fas fa-exclamation-circle"></i>
    <p><?=$error_msg?></p>
    <i class="fas fa-times"></i>
</div>
<?php endif; ?>

<div class="content-block">
    <div class="table">
        <p class="mb-3"><strong>Upload Progress:</strong> <?=count($uploaded_images)?> of <?=count($required_images)?> images uploaded</p>
        
        <table>
            <thead>
                <tr>
                    <td style="width: 60px;">Status</td>
                    <td style="width: 200px;">Filename</td>
                    <td>Description</td>
                    <td style="width: 100px;">Size</td>
                    <td style="width: 200px;">Upload</td>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($required_images as $filename => $config): ?>
                <tr>
                    <td>
                        <?php if (in_array($filename, $uploaded_images)): ?>
                            <span class="green" title="Uploaded"><i class="fas fa-check-circle"></i></span>
                        <?php else: ?>
                            <span class="orange" title="Not uploaded"><i class="fas fa-exclamation-triangle"></i></span>
                        <?php endif; ?>
                    </td>
                    <td><code><?=$filename?></code></td>
                    <td>
                        <strong><?=htmlspecialchars($config['title'])?></strong><br>
                        <small class="text-muted"><?=htmlspecialchars($config['description'])?></small>
                    </td>
                    <td><?=$config['width']?>x<?=$config['height']?></td>
                    <td>
                        <?php if (in_array($filename, $uploaded_images)): ?>
                            <form method="post" enctype="multipart/form-data" style="display: inline-block;">
                                <input type="hidden" name="image_key" value="<?=$filename?>">
                                <input type="file" name="image" accept="image/*" required style="display: none;" id="file-<?=$filename?>" onchange="this.form.submit()">
                                <label for="file-<?=$filename?>" class="btn" style="background: #6c757d; color: white; cursor: pointer; margin: 0;">
                                    <i class="fas fa-sync"></i> Replace
                                </label>
                            </form>
                        <?php else: ?>
                            <form method="post" enctype="multipart/form-data" style="display: inline-block;">
                                <input type="hidden" name="image_key" value="<?=$filename?>">
                                <input type="file" name="image" accept="image/*" required style="display: none;" id="file-<?=$filename?>" onchange="this.form.submit()">
                                <label for="file-<?=$filename?>" class="btn" style="background: #007bff; color: white; cursor: pointer; margin: 0;">
                                    <i class="fas fa-upload"></i> Upload
                                </label>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="content-block">
    <h3>Instructions</h3>
    <ol>
        <li>Take a screenshot of the specified area using Windows Snipping Tool (Win+Shift+S) or Snagit</li>
        <li>Add arrows, highlights, or annotations as needed using your screenshot tool</li>
        <li>Save the screenshot (doesn't matter what name you give it)</li>
        <li>Click the "Upload" button next to the corresponding image in the table above</li>
        <li>Select your screenshot file</li>
        <li>The image will automatically be resized and saved with the correct filename</li>
    </ol>
    
    <p><strong>Tips:</strong></p>
    <ul>
        <li>Use PNG format for screenshots with text (better quality)</li>
        <li>JPG is fine for photos or screenshots without much text</li>
        <li>Add red arrows or circles to highlight important buttons/fields</li>
        <li>Make sure text in screenshots is readable</li>
        <li>You can replace images anytime by clicking "Replace"</li>
    </ul>
</div>

<?=template_admin_footer()?>
