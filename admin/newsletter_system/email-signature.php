<?php
require 'assets/includes/admin_config.php';
include_once '../assets/includes/components.php';

// Page security - admin only
$selected = 'newsletters';
$selected_child = 'signature';

// Handle form submission
if (isset($_POST['submit'])) {
    try {
        // First, deactivate all signatures
        $pdo->exec('UPDATE email_signatures SET is_active = 0');
        
        // Then activate the selected one
        $stmt = $pdo->prepare('UPDATE email_signatures SET is_active = 1, updated_by = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$_SESSION['id'], $_POST['active_signature_id']]);
        
        $_SESSION['success'] = 'Active signature template updated successfully!';
        header('Location: email-signature.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error updating active signature: ' . $e->getMessage();
    }
}

// Handle save/update signature
if (isset($_POST['save_signature'])) {
    try {
        $id = $_POST['signature_id'];
        $template_name = $_POST['template_name'];
        $signature_html = $_POST['signature_html'];
        $signature_text = $_POST['signature_text'];
        $include_do_not_reply = isset($_POST['include_do_not_reply']) ? 1 : 0;
        $do_not_reply_text = $_POST['do_not_reply_text'];
        
        if ($id == 'new') {
            // Create new signature
            $stmt = $pdo->prepare('INSERT INTO email_signatures (template_name, signature_html, signature_text, include_do_not_reply, do_not_reply_text, updated_by) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$template_name, $signature_html, $signature_text, $include_do_not_reply, $do_not_reply_text, $_SESSION['id']]);
            $_SESSION['success'] = 'New signature template created successfully!';
        } else {
            // Update existing signature
            $stmt = $pdo->prepare('UPDATE email_signatures SET template_name = ?, signature_html = ?, signature_text = ?, include_do_not_reply = ?, do_not_reply_text = ?, updated_by = ?, updated_at = NOW() WHERE id = ?');
            $stmt->execute([$template_name, $signature_html, $signature_text, $include_do_not_reply, $do_not_reply_text, $_SESSION['id'], $id]);
            $_SESSION['success'] = 'Signature template updated successfully!';
        }
        
        header('Location: email-signature.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error saving signature: ' . $e->getMessage();
    }
}

// Handle delete signature
if (isset($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare('DELETE FROM email_signatures WHERE id = ?');
        $stmt->execute([$_GET['delete']]);
        $_SESSION['success'] = 'Signature template deleted successfully!';
        header('Location: email-signature.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error deleting signature: ' . $e->getMessage();
    }
}

// Handle test email
if (isset($_POST['send_test'])) {
    require_once(__DIR__ . '/../../lib/email-system.php');
    
    $signature_id = $_POST['test_signature_id'];
    $test_email = $_POST['test_email'];
    
    // Get the signature
    $stmt = $pdo->prepare('SELECT * FROM email_signatures WHERE id = ?');
    $stmt->execute([$signature_id]);
    $signature = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Create test email
    $subject = 'Test Email - Signature Preview';
    $message_html = '<p>This is a test email to preview your email signature.</p><p>If you can read this message, your email system is working correctly!</p>';
    $message_text = "This is a test email to preview your email signature.\n\nIf you can read this message, your email system is working correctly!";
    
    // Append signature
    $full_message_html = $message_html . "\n\n" . $signature['signature_html'];
    $full_message_text = $message_text . "\n\n" . $signature['signature_text'];
    
    // Add do-not-reply notice if needed
    if ($signature['include_do_not_reply']) {
        $notice_html = '<p style="font-size: 11px; color: #999; margin-top: 10px;"><em>' . htmlspecialchars($signature['do_not_reply_text']) . '</em></p>';
        $notice_text = "\n\n" . $signature['do_not_reply_text'];
        $full_message_html .= $notice_html;
        $full_message_text .= $notice_text;
    }
    
    // Send test email
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        configure_smtp_mail($mail);
        
        $mail->setFrom(mail_from, mail_name);
        $mail->addAddress($test_email);
        
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $full_message_html;
        $mail->AltBody = $full_message_text;
        
        $mail->send();
        $_SESSION['success'] = 'Test email sent successfully to ' . htmlspecialchars($test_email) . '!';
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error sending test email: ' . $mail->ErrorInfo;
    }
    
    header('Location: email-signature.php');
    exit();
}

// Get all signatures
$signatures = $pdo->query('SELECT * FROM email_signatures ORDER BY is_active DESC, template_name ASC')->fetchAll(PDO::FETCH_ASSOC);

// Get active signature
$active_signature = $pdo->query('SELECT * FROM email_signatures WHERE is_active = 1 LIMIT 1')->fetch(PDO::FETCH_ASSOC);

// Get editing signature if specified
$editing_signature = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM email_signatures WHERE id = ?');
    $stmt->execute([$_GET['edit']]);
    $editing_signature = $stmt->fetch(PDO::FETCH_ASSOC);
}

?>
<?=template_admin_header('Email Signature Management', 'newsletters', 'signature')?>

<style>
.signature-card {
    background: #fff;
    border: 2px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    position: relative;
}
.signature-card.active {
    border-color: #6610f2;
    background: #f8f7ff;
}
.signature-card h3 {
    margin-top: 0;
    color: #333;
}
.signature-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    background: #6610f2;
    color: white;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
}
.signature-preview {
    border: 1px solid #ddd;
    padding: 15px;
    background: #fafafa;
    border-radius: 4px;
    margin: 15px 0;
    min-height: 100px;
}
.signature-actions {
    margin-top: 15px;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    font-size: 14px;
}
.btn-primary {
    background: #6610f2;
    color: white;
}
.btn-success {
    background: #28a745;
    color: white;
}
.btn-warning {
    background: #ffc107;
    color: #000;
}
.btn-danger {
    background: #dc3545;
    color: white;
}
.btn-secondary {
    background: #6c757d;
    color: white;
}
.form-group {
    margin-bottom: 20px;
}
.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
    color: #333;
}
.form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}
textarea.form-control {
    resize: vertical;
    font-family: monospace;
}
.checkbox-group {
    display: flex;
    align-items: center;
    gap: 10px;
}
.checkbox-group input[type="checkbox"] {
    width: auto;
}
.editor-section {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}
.preview-toggle {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
}
.preview-tab {
    padding: 8px 20px;
    background: #e9ecef;
    border: none;
    cursor: pointer;
    border-radius: 4px 4px 0 0;
}
.preview-tab.active {
    background: #6610f2;
    color: white;
}
.preview-content {
    display: none;
}
.preview-content.active {
    display: block;
}
</style>

<div class="content-block">
    <div class="content-header">
        <h2>Email Signature Management</h2>
        <div style="display: flex; gap: 10px;">
            <a href="?create=new" class="btn btn-success">+ Create New Template</a>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
            <?=$_SESSION['success']?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
            <?=$_SESSION['error']?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($_GET['edit']) || isset($_GET['create'])): ?>
        <!-- EDITOR VIEW -->
        <div class="editor-section">
            <h3><?=isset($_GET['create']) ? 'Create New Signature Template' : 'Edit Signature Template'?></h3>
            
            <form method="post" action="">
                <input type="hidden" name="signature_id" value="<?=isset($_GET['create']) ? 'new' : $editing_signature['id']?>">
                
                <div class="form-group">
                    <label for="template_name">Template Name</label>
                    <input type="text" name="template_name" id="template_name" class="form-control" 
                           value="<?=isset($editing_signature) ? htmlspecialchars($editing_signature['template_name']) : ''?>" 
                           placeholder="e.g., Default Signature, Holiday Signature, Special Event" required>
                </div>

                <div class="form-group">
                    <label for="signature_html">HTML Signature</label>
                    <textarea name="signature_html" id="signature_html" class="form-control" rows="10"><?=isset($editing_signature) ? htmlspecialchars($editing_signature['signature_html']) : ''?></textarea>
                    <small style="color: #666; display: block; margin-top: 5px;">Use the visual editor above to design your signature</small>
                </div>

                <div class="form-group">
                    <label for="signature_text">Plain Text Signature</label>
                    <textarea name="signature_text" id="signature_text" class="form-control" rows="8"><?=isset($editing_signature) ? htmlspecialchars($editing_signature['signature_text']) : ''?></textarea>
                    <small style="color: #666; display: block; margin-top: 5px;">This is used for email clients that don't support HTML</small>
                </div>

                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" name="include_do_not_reply" id="include_do_not_reply" 
                               <?=isset($editing_signature) && $editing_signature['include_do_not_reply'] ? 'checked' : 'checked'?>>
                        <label for="include_do_not_reply" style="margin: 0;">Include "Do Not Reply" notice for emails from noreply@</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="do_not_reply_text">"Do Not Reply" Notice Text</label>
                    <input type="text" name="do_not_reply_text" id="do_not_reply_text" class="form-control" 
                           value="<?=isset($editing_signature) ? htmlspecialchars($editing_signature['do_not_reply_text']) : 'This is an automated message. Please do not reply to this email, as the mailbox is not monitored.'?>" 
                           maxlength="500">
                </div>

                <div class="form-group">
                    <h4>Preview</h4>
                    <div class="preview-toggle">
                        <button type="button" class="preview-tab active" onclick="showPreview('html')">HTML Preview</button>
                        <button type="button" class="preview-tab" onclick="showPreview('text')">Plain Text Preview</button>
                    </div>
                    <div id="preview-html" class="preview-content active signature-preview"></div>
                    <div id="preview-text" class="preview-content signature-preview" style="white-space: pre-wrap; font-family: monospace;"></div>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="save_signature" class="btn btn-success">Save Template</button>
                    <button type="button" class="btn btn-primary" onclick="updatePreview()">Update Preview</button>
                    <a href="email-signature.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    <?php else: ?>
        <!-- LIST VIEW -->
        <div class="content-wrapper">
            <?php if ($active_signature): ?>
                <div style="background: #e7f3ff; padding: 15px; border-radius: 4px; margin-bottom: 25px; border-left: 4px solid #6610f2;">
                    <strong>Active Signature:</strong> <?=htmlspecialchars($active_signature['template_name'])?> 
                    <span style="color: #666; font-size: 13px;">(Last updated: <?=date('M d, Y g:i A', strtotime($active_signature['updated_at']))?>)</span>
                </div>
            <?php else: ?>
                <div style="background: #fff3cd; padding: 15px; border-radius: 4px; margin-bottom: 25px; border-left: 4px solid #ffc107;">
                    <strong>Warning:</strong> No active signature template selected. Emails will be sent without a signature.
                </div>
            <?php endif; ?>

            <?php foreach ($signatures as $signature): ?>
                <div class="signature-card <?=$signature['is_active'] ? 'active' : ''?>">
                    <?php if ($signature['is_active']): ?>
                        <span class="signature-badge">ACTIVE</span>
                    <?php endif; ?>
                    
                    <h3><?=htmlspecialchars($signature['template_name'])?></h3>
                    
                    <div style="font-size: 13px; color: #666; margin-bottom: 15px;">
                        <div>Created: <?=date('M d, Y g:i A', strtotime($signature['created_at']))?></div>
                        <?php if ($signature['updated_at'] != $signature['created_at']): ?>
                            <div>Last Updated: <?=date('M d, Y g:i A', strtotime($signature['updated_at']))?></div>
                        <?php endif; ?>
                        <div>Do Not Reply Notice: <?=$signature['include_do_not_reply'] ? 'Enabled' : 'Disabled'?></div>
                    </div>

                    <div class="signature-preview">
                        <strong style="display: block; margin-bottom: 10px; color: #666; font-size: 12px;">HTML Preview:</strong>
                        <?=$signature['signature_html']?>
                        <?php if ($signature['include_do_not_reply']): ?>
                            <p style="font-size: 11px; color: #999; margin-top: 10px;"><em><?=htmlspecialchars($signature['do_not_reply_text'])?></em></p>
                        <?php endif; ?>
                    </div>

                    <div class="signature-actions">
                        <?php if (!$signature['is_active']): ?>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="active_signature_id" value="<?=$signature['id']?>">
                                <button type="submit" name="submit" class="btn btn-primary">Set as Active</button>
                            </form>
                        <?php endif; ?>
                        
                        <a href="?edit=<?=$signature['id']?>" class="btn btn-warning">Edit</a>
                        
                        <button type="button" class="btn btn-secondary" onclick="showTestEmailModal(<?=$signature['id']?>, '<?=htmlspecialchars($signature['template_name'], ENT_QUOTES)?>')">Send Test Email</button>
                        
                        <?php if (!$signature['is_active'] && count($signatures) > 1): ?>
                            <a href="?delete=<?=$signature['id']?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this signature template?')">Delete</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($signatures)): ?>
                <div style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 8px;">
                    <h3>No signature templates found</h3>
                    <p style="color: #666;">Create your first email signature template to get started.</p>
                    <a href="?create=new" class="btn btn-success">Create Signature Template</a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Test Email Modal -->
<div id="testEmailModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 10000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 8px; padding: 30px; max-width: 500px; width: 90%;">
        <h3 style="margin-top: 0;">Send Test Email</h3>
        <form method="post">
            <input type="hidden" name="test_signature_id" id="test_signature_id">
            <div class="form-group">
                <label for="test_email">Send to Email Address:</label>
                <input type="email" name="test_email" id="test_email" class="form-control" 
                       value="<?=htmlspecialchars($_SESSION['email'] ?? '')?>" required>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" name="send_test" class="btn btn-success">Send Test</button>
                <button type="button" class="btn btn-secondary" onclick="closeTestEmailModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function showTestEmailModal(signatureId, templateName) {
    document.getElementById('test_signature_id').value = signatureId;
    document.getElementById('testEmailModal').style.display = 'flex';
}

function closeTestEmailModal() {
    document.getElementById('testEmailModal').style.display = 'none';
}

function showPreview(type) {
    // Update tab styles
    document.querySelectorAll('.preview-tab').forEach(tab => tab.classList.remove('active'));
    event.target.classList.add('active');
    
    // Show appropriate preview
    document.querySelectorAll('.preview-content').forEach(content => content.classList.remove('active'));
    document.getElementById('preview-' + type).classList.add('active');
}

function updatePreview() {
    const htmlContent = tinymce.get('signature_html').getContent();
    const textContent = document.getElementById('signature_text').value;
    const includeNotice = document.getElementById('include_do_not_reply').checked;
    const noticeText = document.getElementById('do_not_reply_text').value;
    
    // Update HTML preview
    let htmlPreview = htmlContent;
    if (includeNotice) {
        htmlPreview += '<p style="font-size: 11px; color: #999; margin-top: 10px;"><em>' + noticeText + '</em></p>';
    }
    document.getElementById('preview-html').innerHTML = htmlPreview;
    
    // Update text preview
    let textPreview = textContent;
    if (includeNotice) {
        textPreview += '\n\n' + noticeText;
    }
    document.getElementById('preview-text').textContent = textPreview;
}
</script>

<?php if (isset($_GET['edit']) || isset($_GET['create'])): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/7.3.0/tinymce.min.js" integrity="sha512-RUZ2d69UiTI+LdjfDCxqJh5HfjmOcouct56utQNVRjr90Ea8uHQa+gCxvxDTC9fFvIGP+t4TDDJWNTRV48tBpQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
tinymce.init({
    selector: '#signature_html',
    plugins: 'table lists link code preview',
    toolbar: 'undo redo | blocks | formatselect | bold italic forecolor backcolor | align | outdent indent | numlist bullist | table link | code preview',
    menubar: 'edit view insert format tools table',
    valid_elements: '*[*]',
    extended_valid_elements: '*[*]',
    valid_children: '+body[style]',
    content_css: false,
    height: 400,
    branding: false,
    promotion: false,
    link_default_protocol: 'https',
    link_assume_external_targets: false,
    convert_urls: false,
    relative_urls: false,
    license_key: 'gpl',
    setup: function(editor) {
        editor.on('change keyup', function() {
            // Auto-update preview on changes
            updatePreview();
        });
        editor.on('init', function() {
            // Initial preview
            updatePreview();
        });
    }
});

// Also update preview when text area changes
document.getElementById('signature_text').addEventListener('input', updatePreview);
document.getElementById('include_do_not_reply').addEventListener('change', updatePreview);
document.getElementById('do_not_reply_text').addEventListener('input', updatePreview);
</script>
<?php endif; ?>

<?=template_admin_footer()?>
