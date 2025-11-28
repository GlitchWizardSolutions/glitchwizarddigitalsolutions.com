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

// Delete the blog template
if (isset($_GET['delete'])) {
    // Delete the blog template
    $stmt = $blog_pdo->prepare('DELETE FROM blog_templates WHERE id = ?');
    $stmt->execute([ $_GET['delete'] ]);
    header('Location: blog_templates.php?success_msg=3');
    exit;
}

// Retrieve the GET request parameters (if specified)
$pagination_page = isset($_GET['pagination_page']) ? $_GET['pagination_page'] : 1;
$search = isset($_GET['search_query']) ? $_GET['search_query'] : '';

// Order by column
$order = isset($_GET['order']) && $_GET['order'] == 'DESC' ? 'DESC' : 'ASC';

// Add/remove columns to the whitelist array
$order_by_whitelist = ['id','title','category_id','created_date','active'];
$order_by = isset($_GET['order_by']) && in_array($_GET['order_by'], $order_by_whitelist) ? $_GET['order_by'] : 'id';

// Number of results per pagination page
$results_per_page = 10;

// Declare query param variables
$param1 = ($pagination_page - 1) * $results_per_page;
$param2 = $results_per_page;
$param3 = '%' . $search . '%';

// SQL where clause
$where = '';
$where .= $search ? 'WHERE (t.title LIKE :search OR t.content LIKE :search) ' : '';

// Filters
$category_filter = isset($_GET['category_id']) ? $_GET['category_id'] : '';
if ($category_filter) {
    $where .= ($where ? 'AND ' : 'WHERE ') . 't.category_id = :category_id ';
}

// Retrieve the total number of blog templates
$stmt = $blog_pdo->prepare('SELECT COUNT(*) AS total FROM blog_templates t ' . $where);
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
if ($category_filter) $stmt->bindParam('category_id', $category_filter, PDO::PARAM_INT);
$stmt->execute();
$blog_templates_total = $stmt->fetchColumn();

// SQL query to get all blog templates from the "blog_templates" table
$stmt = $blog_pdo->prepare('SELECT
    t.*,
    c.category as category_name,
    u.username as creator_name
    FROM blog_templates t
    LEFT JOIN categories c ON t.category_id = c.id
    LEFT JOIN users u ON t.created_by = u.id ' .
    $where . ' ORDER BY ' . $order_by . ' ' . $order . ' LIMIT :start_results,:num_results'
);

// Bind params
$stmt->bindParam('start_results', $param1, PDO::PARAM_INT);
$stmt->bindParam('num_results', $param2, PDO::PARAM_INT);
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
if ($category_filter) $stmt->bindParam('category_id', $category_filter, PDO::PARAM_INT);
$stmt->execute();

// Retrieve query results
$blog_templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter dropdown
$categories = $blog_pdo->query('SELECT id, category FROM categories ORDER BY category ASC')->fetchAll(PDO::FETCH_ASSOC);

// Handle success messages
if (isset($_GET['success_msg'])) {
    if ($_GET['success_msg'] == 1) {
        $success_msg = 'Blog template created successfully!';
    }
    if ($_GET['success_msg'] == 2) {
        $success_msg = 'Blog template updated successfully!';
    }
    if ($_GET['success_msg'] == 3) {
        $success_msg = 'Blog template deleted successfully!';
    }
}

// Determine the URL
$url = 'blog_templates.php?search_query=' . $search . (isset($_GET['category_id']) ? '&category_id=' . $_GET['category_id'] : '');
?>
<?=template_admin_header('Blog Templates', 'blog')?>

<?=generate_breadcrumbs([
    ['title' => 'Admin Dashboard', 'url' => '../index.php'],
    ['title' => 'Blog', 'url' => 'blog_dash.php'],
    ['title' => 'Templates', 'url' => '']
])?>

<div class="content-title mb-3">
    <div class="title">
        <i class="fa-solid fa-file-alt"></i>
        <div class="txt">
            <h2>Blog Templates</h2>
            <p>Manage reusable blog post templates</p>
        </div>
    </div>
</div>

<?php if (isset($success_msg)): ?>
<div class="msg success">
    <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM369 209L241 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L335 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/></svg>
    <p><?=$success_msg?></p>
    <svg class="close" width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg>
</div>
<?php endif; ?>

<div class="content-header responsive-flex-column pad-top-5">
    <a href="add_blog_template.php" class="btn btn-success">
        <svg class="icon-left" width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 80c0-17.7-14.3-32-32-32s-32 14.3-32 32V224H48c-17.7 0-32 14.3-32 32s14.3 32 32 32H192V432c0 17.7 14.3 32 32 32s32-14.3 32-32V288H400c17.7 0 32-14.3 32-32s-14.3-32-32-32H256V80z"/></svg>
        Create Template
    </a>
    <form action="blog_templates.php" method="get">
        <div class="filters">
            <a href="#">
                <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M0 416c0 17.7 14.3 32 32 32l54.7 0c12.3 28.3 40.5 48 73.3 48s61-19.7 73.3-48L480 448c17.7 0 32-14.3 32-32s-14.3-32-32-32l-246.7 0c-12.3-28.3-40.5-48-73.3-48s-61 19.7-73.3 48L32 384c-17.7 0-32 14.3-32 32zm128 0a32 32 0 1 1 64 0 32 32 0 1 1 -64 0zM320 256a32 32 0 1 1 64 0 32 32 0 1 1 -64 0zm32-80c-32.8 0-61 19.7-73.3 48L32 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l246.7 0c12.3 28.3 40.5 48 73.3 48s61-19.7 73.3-48l54.7 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-54.7 0c-12.3-28.3-40.5-48-73.3-48zM192 128a32 32 0 1 1 0-64 32 32 0 1 1 0 64zm73.3-64C253 35.7 224.8 16 192 16s-61 19.7-73.3 48L32 64C14.3 64 0 78.3 0 96s14.3 32 32 32l86.7 0c12.3 28.3 40.5 48 73.3 48s61-19.7 73.3 48L480 128c17.7 0 32-14.3 32-32s-14.3-32-32-32L265.3 64z"/></svg>
                Filters
            </a>
            <div class="list">
                <label for="category_id">Category</label>
                <select name="category_id" id="category_id">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                    <option value="<?=$category['id']?>" <?=$category_filter == $category['id'] ? 'selected' : ''?>><?=$category['category']?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Apply</button>
            </div>
        </div>
        <div class="search">
            <label for="search_query">
                <input id="search_query" type="text" name="search_query" placeholder="Search templates..." value="<?=htmlspecialchars($search, ENT_QUOTES)?>" class="responsive-width-100">
                <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"/></svg>
            </label>
        </div>
    </form>
</div>

<div class="filter-list">
    <?php if ($search != ''): ?>
    <div class="filter">
        <a href="<?=remove_url_param($url, 'search_query')?>"><svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free --><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg></a>
        Search : <?=htmlspecialchars($search, ENT_QUOTES)?>
    </div>
    <?php endif; ?>
    <?php if ($category_filter != ''): ?>
    <div class="filter">
        <a href="<?=remove_url_param($url, 'category_id')?>"><svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free --><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg></a>
        Category : <?=$categories[array_search($category_filter, array_column($categories, 'id'))]['category'] ?? 'Unknown'?>
    </div>
    <?php endif; ?>
</div>

<div class="content-block no-pad">
    <div class="table">
        <table>
            <thead>
                <tr>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=id'?>">#<?=$order_by=='id' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=title'?>">Title<?=$order_by=='title' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=category_id'?>">Category<?=$order_by=='category_id' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td>Created By</td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=created_date'?>">Created<?=$order_by=='created_date' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=active'?>">Status<?=$order_by=='active' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td>Actions</td>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($blog_templates)): ?>
                <tr>
                    <td colspan="7" class="no-results">There are no blog templates.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($blog_templates as $template): ?>
                <tr>
                    <td class="responsive-hidden alt"><?=$template['id']?></td>
                    <td><?=htmlspecialchars($template['title'], ENT_QUOTES)?></td>
                    <td><?=$template['category_name'] ?: 'No Category'?></td>
                    <td><?=$template['creator_name'] ?: 'Unknown'?></td>
                    <td class="responsive-hidden"><?=date('M j, Y', strtotime($template['created_date']))?></td>
                    <td>
                        <span class="badge <?=$template['active'] == 'Yes' ? 'bg-success' : 'bg-secondary'?>">
                            <?=$template['active']?>
                        </span>
                    </td>
                    <td>
                        <a href="edit_blog_template.php?id=<?=$template['id']?>" class="btn btn-primary btn-sm">
                            <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M471.6 21.7c-21.9-21.9-57.3-21.9-79.2 0L362.3 51.7l97.9 97.9 30.1-30.1c21.9-21.9 21.9-57.3 0-79.2L471.6 21.7zm-299.2 220c-6.1 6.1-10.8 13.6-13.5 21.9l-29.6 88.8c-2.9 8.6-.6 18.1 5.8 24.6s15.9 8.7 24.6 5.8l88.8-29.6c8.2-2.7 15.7-7.4 21.9-13.5L437.7 172.3 339.7 74.3 172.4 241.7zM96 64C43 64 0 107 0 160V416c0 53 43 96 96 96H352c53 0 96-43 96-96V320c0-17.7-14.3-32-32-32s-32 14.3-32 32v96c0 17.7-14.3 32-32 32H96c-17.7 0-32-14.3-32-32V160c0-17.7 14.3-32 32-32h96c17.7 0 32-14.3 32-32s-14.3-32-32-32H96z"/></svg>
                            Edit
                        </a>
                        <a href="blog_templates.php?delete=<?=$template['id']?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this template?')">
                            <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z"/></svg>
                            Delete
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($blog_templates_total > $results_per_page): ?>
    <div class="pagination">
        <?php $total_pages = ceil($blog_templates_total / $results_per_page); ?>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <a href="<?=$url . '&pagination_page=' . $i?>" class="<?=$pagination_page == $i ? 'active' : ''?>"><?=$i?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?=template_admin_footer()?>