<?php
require 'assets/includes/admin_config.php';
include_once 'assets/includes/components.php';
// If submit form, send mail to the specified recipient
if (isset($_POST['subject'])) {
    include 'functions.php';
    $response = send_mail($_POST['from'], $_POST['recipient'], $_POST['subject'], $_POST['content']);
    exit($response);
}
// Retrieve subscribers from the database
$stmt = $pdo->prepare('SELECT * FROM subscribers WHERE status = "Subscribed" AND confirmed = 1 ORDER BY email ASC');
$stmt->execute();
$subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?=template_admin_header('Send Mail', 'newsletters', 'sendmail')?>

<?=generate_breadcrumbs([
    ['label' => 'Send Mail']
])?>

<div class="content-title mb-3">
    <div class="icon alt"><?=svg_icon_email()?></div>
    <div class="txt">
        <h2>Send Mail</h2>
        <p class="subtitle">Send bulk emails to subscribers</p>
    </div>
</div>
<form action="" method="post" class="send-mail-form">

    <div class="content-title responsive-flex-wrap responsive-pad-bot-3">
       
        <input type="submit" name="submit" value="Send" class="btn btn-success">
    </div>

    <div class="content-block">

        <div class="form responsive-width-100">

            <label for="subject"><i class="required">*</i> Subject</label>
            <input id="subject" type="text" name="subject" placeholder="Subject" required>

            <label for="from"><i class="required">*</i> From</label>
            <input id="from" type="text" name="from" placeholder="From" value="<?=mail_from?>" required>

            <label for="recipients"><i class="required">*</i> Recipients</label>
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

            <label for="additional_recipients">Additional Recipients</label>
            <input id="additional_recipients" type="text" name="additional_recipients" placeholder="Comma-separated list of emails">

            <label for="content"><i class="required">*</i> HTML Email Template</label>
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
                <div class="preview-btn">
                    <a href="#"><i class="fa-solid fa-eye"></i>Preview</a>
                </div>
            </div>
            <textarea id="content" name="content" placeholder="Enter your HTML template..." wrap="off" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"></textarea>
        </div>

    </div>

</form>

<?=template_admin_footer()?>