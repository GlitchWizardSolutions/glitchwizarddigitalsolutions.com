<?php
require 'assets/includes/admin_config.php';
include_once '../assets/includes/components.php';
// Default placeholder values
$custom_placeholder = [
    'placeholder_text' => '',
    'placeholder_value' => ''
];
// Check if the ID param exists
if (isset($_GET['id'])) {
    // Retrieve the placeholder from the database
    $stmt = $pdo->prepare('SELECT * FROM custom_placeholders WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $custom_placeholder = $stmt->fetch(PDO::FETCH_ASSOC);
    // ID param exists, edit an existing placeholder
    $page = 'Edit';
    if (isset($_POST['submit'])) {
        // Update the placeholder
        $stmt = $pdo->prepare('UPDATE custom_placeholders SET placeholder_text = ?, placeholder_value = ? WHERE id = ?');
        $stmt->execute([ $_POST['placeholder_text'], $_POST['placeholder_value'], $_GET['id'] ]);
        header('Location: custom_placeholders.php?success_msg=2');
        exit;
    }
    if (isset($_POST['delete'])) {
        // Redirect and delete placeholder
        header('Location: custom_placeholders.php?delete=' . $_GET['id']);
        exit;
    }
} else {
    // Create a new placeholder
    $page = 'Create';
    if (isset($_POST['submit'])) {
        // Insert the placeholder
        $stmt = $pdo->prepare('INSERT INTO custom_placeholders (placeholder_text,placeholder_value) VALUES (?,?)');
        $stmt->execute([ $_POST['placeholder_text'], $_POST['placeholder_value'] ]);
        header('Location: custom_placeholders.php?success_msg=1');
        exit;
    }
}
?>
<?=template_admin_header($page . ' Placeholder', 'settings', 'custom_placeholders')?>

<?=generate_breadcrumbs([
    ['label' => 'Custom Placeholders', 'url' => 'custom_placeholders.php'],
    ['label' => $page . ' Placeholder']
])?>

<div class="content-title mb-3">
    <div class="icon alt"><?=svg_icon_settings()?></div>
    <div class="txt">
        <h2><?=$page?> Placeholder</h2>
        <p class="subtitle"><?=$page == 'Edit' ? 'Modify custom placeholder' : 'Create new custom placeholder'?></p>
    </div>
</div>

<form method="post">
    <div class="form-professional">
        
        <div class="form-section">
            <h3 class="section-title">Placeholder Details</h3>

            <div class="form-group">
                <label for="placeholder_text">Placeholder <span class="required">*</span></label>
                <input id="placeholder_text" type="text" name="placeholder_text" placeholder="%example%" value="<?=htmlspecialchars($custom_placeholder['placeholder_text'], ENT_QUOTES)?>" required>
            </div>

            <div class="form-group">
                <label for="placeholder_value">Value <span class="required">*</span></label>
                <textarea id="placeholder_value" name="placeholder_value" rows="4" placeholder="Enter the replacement value..." required><?=htmlspecialchars($custom_placeholder['placeholder_value'], ENT_QUOTES)?></textarea>
            </div>
        </div>
        
        <div class="form-actions">
            <a href="custom_placeholders.php" class="btn btn-secondary">Cancel</a>
            <?php if ($page == 'Edit'): ?>
            <input type="submit" name="delete" value="Delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this placeholder?')">
            <?php endif; ?>
            <input type="submit" name="submit" value="Save" class="btn btn-success">
        </div>

    </div>
</form>

<?=template_admin_footer()?>