<?php
require 'assets/includes/admin_config.php';

// User authentication for blog admin
if (isset($_SESSION['sec-username'])) {
    $uname = $_SESSION['sec-username'];
    $stmt = $blog_pdo->prepare("SELECT * FROM `users` WHERE username = ? AND (role = 'Admin' OR role = 'Editor')");
    $stmt->execute([$uname]);
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        header('Location: ../../blog/login');
        exit;
    }
} else {
    header('Location: ../../blog/login');
    exit;
}

// Check if the ID param exists
if (isset($_GET['id'])) {
    // Retrieve the blog template from the database
    $stmt = $blog_pdo->prepare('SELECT * FROM blog_templates WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);

    // ID param exists, edit an existing template
    $page = 'Edit';
    if (isset($_POST['submit'])) {
        // Update the template
        $title = trim($_POST['title']);
        $content = $_POST['content'];
        $category_id = (int) $_POST['category_id'];
        $active = $_POST['active'];

        $stmt = $blog_pdo->prepare('UPDATE blog_templates SET title = ?, content = ?, category_id = ?, active = ? WHERE id = ?');
        $stmt->execute([$title, $content, $category_id, $active, $_GET['id']]);

        header('Location: blog_templates.php?success_msg=2');
        exit;
    }
} else {
    header('Location: blog_templates.php');
    exit;
}
?>
<?=template_admin_header('Edit Blog Template', 'blog')?>

<?=generate_breadcrumbs([
    ['title' => 'Admin Dashboard', 'url' => '../index.php'],
    ['title' => 'Blog', 'url' => 'blog_dash.php'],
    ['title' => 'Templates', 'url' => 'blog_templates.php'],
    ['title' => 'Edit Template', 'url' => '']
])?>

<div class="content-block">
    <h3>Edit Blog Template</h3>
    <form action="" method="post">
        <p>
            <label>Title</label><br />
            <input type="text" name="title" class="form-control" value="<?=htmlspecialchars($template['title'], ENT_QUOTES)?>" required>
        </p>
        <p>
            <label>Category (Optional)</label><br />
            <select name="category_id" class="form-select">
                <option value="">No Category</option>
<?php
$stmt = $blog_pdo->query("SELECT * FROM `categories` ORDER BY category ASC");
while ($rw = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $selected = ($rw['id'] == $template['category_id']) ? 'selected' : '';
    echo '
                <option value="' . $rw['id'] . '" ' . $selected . '>' . htmlspecialchars($rw['category']) . '</option>
                ';
}
?>
            </select>
        </p>
        <p>
            <label>Status</label><br />
            <select name="active" class="form-select" required>
                <option value="Yes" <?=$template['active'] == 'Yes' ? 'selected' : ''?>>Active</option>
                <option value="No" <?=$template['active'] == 'No' ? 'selected' : ''?>>Inactive</option>
            </select>
        </p>
        <p>
            <label>Content</label>
            <textarea class="form-control" id="summernote" rows="12" name="content" required><?=$template['content']?></textarea>
        </p>

        <input type="submit" name="submit" class="btn btn-primary col-12" value="Update Template" />
    </form>
</div>

<?=template_admin_footer('
<script>
$(document).ready(function() {
    $("#summernote").summernote({height: 400});

    var noteBar = $(".note-toolbar");
        noteBar.find("[data-toggle]").each(function() {
        $(this).attr("data-bs-toggle", $(this).attr("data-toggle")).removeAttr("data-toggle");
    });
});
</script>
')?>