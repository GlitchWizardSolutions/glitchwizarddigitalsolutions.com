<?php
require 'assets/includes/admin_config.php';
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

<form method="post">

    <div class="content-title">
        <h2><?=$page?> Placeholder</h2>
        <div class="btns">
            <a href="custom_placeholders.php" class="btn alt mar-right-1">Cancel</a>
            <?php if ($page == 'Edit'): ?>
            <input type="submit" name="delete" value="Delete" class="btn red mar-right-1" onclick="return confirm('Are you sure you want to delete this placeholder?')">
            <?php endif; ?>
            <input type="submit" name="submit" value="Save" class="btn">
        </div>
    </div>

    <div class="content-block">
        
        <div class="form responsive-width-100">

            <label for="placeholder_text"><span class="required">*</span> Placeholder</label>
            <input id="placeholder_text" type="text" name="placeholder_text" placeholder="%example%" value="<?=htmlspecialchars($custom_placeholder['placeholder_text'], ENT_QUOTES)?>" required>

            <label for="placeholder_value"><span class="required">*</span> Value</label>
            <textarea id="placeholder_value" name="placeholder_value" placeholder="Enter the replacement value..." required><?=htmlspecialchars($custom_placeholder['placeholder_value'], ENT_QUOTES)?></textarea>

        </div>
    
    </div>

</form>

<?=template_admin_footer()?>