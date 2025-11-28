<?php
require 'assets/includes/admin_config.php';

if (isset($_POST['add'])) {
    $title = trim($_POST['title']);
    $content = $_POST['content'];
    $category_id = (int) $_POST['category_id'];
    $active = $_POST['active'];

    $created_date = date('Y-m-d');
    $created_time = date('H:i:s');

    // Get current user ID
    $author = $uname;
    $stmt = $blog_pdo->prepare("SELECT id FROM `users` WHERE username = ? LIMIT 1");
    $stmt->execute([$author]);
    $auth = $stmt->fetch(PDO::FETCH_ASSOC);
    $created_by = $auth ? $auth['id'] : 0;

    $stmt = $blog_pdo->prepare("INSERT INTO `blog_templates` (title, content, category_id, created_by, created_date, created_time, active)
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$title, $content, $category_id, $created_by, $created_date, $created_time, $active]);

    header('Location: blog_templates.php?success_msg=1');
    exit;
}
?>
<?=template_admin_header('Add Blog Template', 'blog')?>

<?=generate_breadcrumbs([
    ['title' => 'Admin Dashboard', 'url' => '../index.php'],
    ['title' => 'Blog', 'url' => 'blog_dash.php'],
    ['title' => 'Templates', 'url' => 'blog_templates.php'],
    ['title' => 'Add Template', 'url' => '']
])?>

<div class="content-block">
    <h3>Add Blog Template</h3>
    <form action="" method="post">
        <p>
            <label>Title</label><br />
            <input type="text" name="title" class="form-control" placeholder="Template title..." required>
        </p>
        <p>
            <label>Category (Optional)</label><br />
            <select name="category_id" class="form-select">
                <option value="">No Category</option>
<?php
$stmt = $blog_pdo->query("SELECT * FROM `categories` ORDER BY category ASC");
while ($rw = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo '
                <option value="' . $rw['id'] . '">' . htmlspecialchars($rw['category']) . '</option>
                ';
}
?>
            </select>
        </p>
        <p>
            <label>Status</label><br />
            <select name="active" class="form-select" required>
                <option value="Yes">Active</option>
                <option value="No">Inactive</option>
            </select>
        </p>
        <p>
            <label>Content</label>
            <textarea class="form-control" id="summernote" rows="12" name="content" required></textarea>
        </p>

        <input type="submit" name="add" class="btn btn-primary col-12" value="Create Template" />
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