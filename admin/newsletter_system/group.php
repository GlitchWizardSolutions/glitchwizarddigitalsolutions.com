<?php
require 'assets/includes/admin_config.php';
include_once '../assets/includes/components.php';
// Default group values
$group = [
    'title' => '',
    'description' => '',
    'submit_date' => date('Y-m-d H:i:s')
];
// Check if the ID param exists
if (isset($_GET['id'])) {
    // Retrieve the group from the database
    $stmt = $pdo->prepare('SELECT * FROM `groups` WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $group = $stmt->fetch(PDO::FETCH_ASSOC);
    // ID param exists, edit an existing group
    $page = 'Edit';
    if (isset($_POST['submit'])) {
        // Update the group
        $stmt = $pdo->prepare('UPDATE groups SET title = ?, description = ?, submit_date = ? WHERE id = ?');
        $stmt->execute([ $_POST['title'], $_POST['description'], $_POST['submit_date'], $_GET['id'] ]);
        header('Location: groups.php?success_msg=2');
        exit;
    }
    if (isset($_POST['delete'])) {
        // Redirect and delete group
        header('Location: groups.php?delete=' . $_GET['id']);
        exit;
    }
} else {
    // Create a new group
    $page = 'Create';
    if (isset($_POST['submit'])) {
        // Insert the group
        $stmt = $pdo->prepare('INSERT INTO groups (title,description,submit_date) VALUES (?,?,?)');
        $stmt->execute([ $_POST['title'], $_POST['description'], $_POST['submit_date'] ]);
        header('Location: groups.php?success_msg=1');
        exit;
    }
}
?>
<?=template_admin_header($page . ' Group', 'subscribers', 'group')?>

<?=generate_breadcrumbs([
    ['label' => 'Groups', 'url' => 'groups.php'],
    ['label' => $page . ' Group']
])?>

<div class="content-title mb-3">
    <div class="icon alt"><?=svg_icon_newsletter()?></div>
    <div class="txt">
        <h2><?=$page?> Group</h2>
        <p class="subtitle"><?=$page == 'Edit' ? 'Modify subscriber group' : 'Create new subscriber group'?></p>
    </div>
</div>

<form method="post">
    <div class="form-professional">
        
        <div class="form-section">
            <h3 class="section-title">Group Details</h3>

            <div class="form-group">
                <label for="title">Title <span class="required">*</span></label>
                <input id="title" type="text" name="title" placeholder="Title" value="<?=htmlspecialchars($group['title'], ENT_QUOTES)?>" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4" placeholder="Description"><?=htmlspecialchars($group['description'], ENT_QUOTES)?></textarea>
            </div>

            <div class="form-group">
                <label for="submit_date">Submit Date</label>
                <input id="submit_date" type="datetime-local" name="submit_date" value="<?=date('Y-m-d\TH:i', strtotime($group['submit_date']))?>">
            </div>
        </div>
        
        <div class="form-actions">
            <a href="groups.php" class="btn btn-secondary">Cancel</a>
            <?php if ($page == 'Edit'): ?>
            <input type="submit" name="delete" value="Delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this group?')">
            <?php endif; ?>
            <input type="submit" name="submit" value="Save" class="btn btn-success">
        </div>

    </div>
</form>

<?=template_admin_footer()?>