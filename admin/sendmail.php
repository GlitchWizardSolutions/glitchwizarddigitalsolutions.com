<?php
require 'assets/includes/admin_config.php';
include_once 'assets/includes/components.php';
include 'functions.php';

// If submit form, send mail to the specified recipient
if (isset($_POST['subject']) && isset($_POST['from']) && isset($_POST['content'])) {
    // Validate and sanitize inputs
    $from = filter_var(trim($_POST['from']), FILTER_SANITIZE_EMAIL);
    $subject = trim($_POST['subject']);
    $content = $_POST['content']; // HTML content, sanitized in send_mail function
    
    // Validate email format
    if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
        exit(json_encode(['success' => false, 'message' => 'Invalid sender email address']));
    }
    
    // Get recipients (using 'recipient' or 'recipients[]' depending on form structure)
    $recipient = $_POST['recipient'] ?? '';
    
    $response = send_mail($from, $recipient, $subject, $content);
    exit($response);
}
// Retrieve subscribers from the database
$stmt = $pdo->prepare('SELECT * FROM subscribers WHERE status = "Subscribed" AND confirmed = 1 ORDER BY email ASC');
$stmt->execute();
$subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?=template_admin_header('Send Mail', 'newsletters', 'sendmail')?>

<?=generate_breadcrumbs([
    ['label' => 'Newsletter System', 'url' => 'newsletter_system/index.php'],
    ['label' => 'Send Mail']
])?>

<div class="content-title mb-3">
    <div class="title">
        <i class="fa-solid fa-envelope"></i>
        <div class="txt">
            <h2>Send Mail</h2>
            <p>Send bulk emails to subscribers</p>
        </div>
    </div>
</div>

<form action="" method="post">

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
                    <input id="from_name" type="text" name="from_name" placeholder="From Name" value="<?=mail_from_name ?? ''?>" required>
                </div>
                <div class="form-group">
                    <label for="from"><span class="required">*</span> From Email</label>
                    <input id="from" type="email" name="from" placeholder="From Email" value="<?=mail_from?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="recipients"><span class="required">*</span> Recipients</label>
                <div class="multi-checkbox">
                    <div class="item check-all">
                        <input id="check-all" type="checkbox">
                        <input type="text" placeholder="Search...">
                    </div>
                    <div class="con" style="height:150px">
                        <?php foreach ($subscribers as $subscriber): ?>
                        <div class="item">
                            <input id="checkbox-<?=$subscriber['id']?>" type="checkbox" name="recipients[]" value="<?=$subscriber['email']?>">
                            <label for="checkbox-<?=$subscriber['id']?>"><?=$subscriber['email']?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="additional_recipients">Additional Recipients</label>
                <input id="additional_recipients" type="text" name="additional_recipients" placeholder="Comma-separated list of emails">
            </div>

            <div class="form-group">
                <label for="content"><span class="required">*</span> HTML Email Template</label>
            </div>
        </div>

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
        
        <div class="form-actions">
            <a href="newsletter_system/index.php" class="btn btn-secondary">Cancel</a>
            <input type="submit" name="submit" value="Send" class="btn btn-success">
        </div>

    </div>

</form>

<?=template_admin_footer()?>