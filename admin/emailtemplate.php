<?php
require 'assets/includes/admin_config.php';
include_once 'assets/includes/components.php';
// Save the email templates
if (isset($_POST['activation_email_template'])) {
    file_put_contents('../activation-email-template.html', $_POST['activation_email_template']);
    header('Location: emailtemplate.php?success_msg=1');
    exit;
}
if (isset($_POST['twofactor_email_template'])) {
    file_put_contents('../twofactor.html', $_POST['twofactor_email_template']);
    header('Location: emailtemplate.php?success_msg=1');
    exit;
}
if (isset($_POST['resetpass_email_template'])) {
    file_put_contents('../resetpass-email-template.html', $_POST['resetpass_email_template']);
    header('Location: emailtemplate.php?success_msg=1');
    exit;
}
// Read the activation email template HTML file
if (file_exists('../activation-email-template.html')) {
    $activation_email_template = file_get_contents('../activation-email-template.html');
}
// Read the two-factor email template
if (file_exists('../twofactor.html')) {
    $twofactor_email_template = file_get_contents('../twofactor.html');
}
// Read the reset password email template HTML file
if (file_exists('../resetpass-email-template.html')) {
    $resetpass_email_template = file_get_contents('../resetpass-email-template.html');
}
// Handle success messages
if (isset($_GET['success_msg'])) {
    if ($_GET['success_msg'] == 1) {
        $success_msg = 'Email template updated successfully!';
    }
}
?>
<?=template_admin_header('Email Templates', 'emailtemplate')?>

<?=generate_breadcrumbs([
    ['label' => 'Email Templates']
])?>

<div class="content-title">
    <div class="title">
        <div class="icon alt">
            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M20,8L12,13L4,8V6L12,11L20,6M20,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V6C22,4.89 21.1,4 20,4Z" /></svg>
        </div>
        <div class="txt">
            <h2 class="responsive-width-100">Email Templates</h2>
            <p>Manage system email templates</p>
        </div>
    </div>
</div>

<?php if (isset($success_msg)): ?>
<div class="msg success">
    <i class="fas fa-check-circle"></i>
    <p><?=$success_msg?></p>
    <i class="fas fa-times"></i>
</div>
<?php endif; ?>

<form action="" method="post" enctype="multipart/form-data">
    <div class="form-professional">
        
        <div class="form-section">
            <h3 class="section-title">Account Email Templates</h3>
            
            <?php if (isset($activation_email_template)): ?>
            <div class="form-group">
                <label for="activation_email_template">Activation Email Template</label>
                <textarea id="activation_email_template" name="activation_email_template" rows="10" placeholder="HTML email template for account activation"><?=$activation_email_template?></textarea>
            </div>
            <?php endif; ?>

            <?php if (isset($twofactor_email_template)): ?>
            <div class="form-group">
                <label for="twofactor_email_template">Two-Factor Email Template</label>
                <textarea id="twofactor_email_template" name="twofactor_email_template" rows="10" placeholder="HTML email template for two-factor authentication"><?=$twofactor_email_template?></textarea>
            </div>
            <?php endif; ?>

            <?php if (isset($resetpass_email_template)): ?>
            <div class="form-group">
                <label for="resetpass_email_template">Reset Password Email Template</label>
                <textarea id="resetpass_email_template" name="resetpass_email_template" rows="10" placeholder="HTML email template for password reset"><?=$resetpass_email_template?></textarea>
            </div>
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <input type="submit" name="submit" value="Save Templates" class="btn btn-primary">
        </div>

    </div>
</form>

<?=template_admin_footer()?>