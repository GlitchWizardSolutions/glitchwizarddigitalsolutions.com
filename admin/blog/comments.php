<?php
require 'assets/includes/admin_config.php';

if (isset($_GET['delete-id'])) {
    $id = (int) $_GET["delete-id"];
    $stmt = $blog_pdo->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: comments.php');
    exit;
}

if (isset($_GET['edit-id'])) {
    $id = (int) $_GET["edit-id"];
    $stmt = $blog_pdo->prepare("SELECT * FROM comments WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (empty($id) || !$row) {
        echo '<meta http-equiv="refresh" content="0; url=comments.php">';
		exit;
    }
    
    $author = $row['username'] ?? 'Guest'; // Use username from comments table
    $avatar = 'assets/img/avatar.png';
    
    if ($row['guest'] == 'No' && $row['user_id'] > 0) {
        // Get avatar from accounts table
        $stmt = $pdo->prepare("SELECT avatar, username FROM accounts WHERE id = ? LIMIT 1");
        $stmt->execute([$row['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $avatar = !empty($user['avatar']) ? $user['avatar'] : 'assets/img/avatar.png';
            $author = $user['username'];
        }
    }
	
	if (isset($_POST['submit'])) {
        $approved = $_POST['approved'];
        $stmt = $blog_pdo->prepare("UPDATE comments SET approved = ? WHERE id = ?");
        $stmt->execute([$approved, $id]);
		
        header('Location: comments.php');
        exit;
    }
}
?>
<?=template_admin_header('Blog Comments', 'dashboard')?>

<div class="content-title">
    <div class="title">
        <div class="txt">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../index.php">Admin Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="blog_dash.php">Blog</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Comments</li>
                </ol>
            </nav>
            <h2>Blog Comments</h2>
        </div>
    </div>
</div>

<?php if (isset($_GET['edit-id'])): ?>
            <div class="content-block">
              <h3>Edit Comment</h3>         
					<form action="" method="post" style="max-width: 800px;">
						<p>
						  <label>Author</label><br />
						  <input type="text" value="<?= htmlspecialchars($author) ?>" style="width: 100%;" disabled>
						</p>
						<p>
						  <label>Avatar</label><br />
						  <img src="<?= htmlspecialchars($avatar) ?>" width="50px" height="50px" class="rounded-circle" style="object-fit: cover;" /><br />
						</p>
						<p>
						  <label>Approved</label><br />
						  <select name="approved" style="width: 100%;" required>
							<option value="Yes" <?= $row['approved'] == "Yes" ? 'selected' : '' ?>>Yes</option>
							<option value="No" <?= $row['approved'] == "No" ? 'selected' : '' ?>>No</option>
						  </select>
						</p>
						<p>
						  <label>Comment</label>
						  <textarea name="comment" rows="6" style="width: 100%;" disabled><?= htmlspecialchars($row['comment']) ?></textarea>
						</p>
						
						<input type="submit" class="btn" name="submit" value="Update" />
					  </form>
              </div>
<?php endif; ?>
			
			<div class="content-block">
              <h3>All Comments</h3>
              <div class="table">         

            <table id="dt-basic" width="100%">
                <thead>
				<tr>
                    <td colspan="2">Author</td>
                    <td class="responsive-hidden">Date</td>
					<td>Approved</td>
					<td class="responsive-hidden">Post</td>
					<td class="align-center">Action</td>
                </tr>
				</thead>
                <tbody>
<?php
$stmt = $blog_pdo->query("SELECT * FROM comments ORDER BY id DESC");
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($comments as $row) {
    $author = $row['username'] ?? 'Guest';
    $badge = '';
    $avatar = 'assets/img/avatar.png';
    
    if ($row['guest'] == 'No' && $row['user_id'] > 0) {
        // Get avatar from accounts table
        $stmt = $pdo->prepare("SELECT avatar, username FROM accounts WHERE id = ? LIMIT 1");
        $stmt->execute([$row['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $avatar = !empty($user['avatar']) ? $user['avatar'] : 'assets/img/avatar.png';
            $author = $user['username'];
        }
    } else {
        $badge = ' <span class="badge bg-info"><i class="fas fa-user"></i> Guest</span>';
    }
    
    // Get post title
    $stmt2 = $blog_pdo->prepare("SELECT title FROM posts WHERE id = ?");
    $stmt2->execute([$row['post_id']]);
    $post = $stmt2->fetch(PDO::FETCH_ASSOC);
    $post_title = $post ? htmlspecialchars($post['title']) : 'Unknown Post';
    
    $approved_badge = $row['approved'] == "Yes" ? '<span class="badge green">Yes</span>' : '<span class="badge red">No</span>';
    
    echo '
                <tr>
                    <td class="img">
                        <img src="' . htmlspecialchars($avatar) . '" width="45px" height="45px" style="border-radius: 50%; object-fit: cover;" />
                    </td>
	                <td>' . htmlspecialchars($author) . $badge . '</td>
	                <td class="responsive-hidden" data-sort="' . strtotime($row['date']) . '">' . date($settings['date_format'], strtotime($row['date'])) . '</td>
					<td>' . $approved_badge . '</td>
                    <td class="responsive-hidden">' . $post_title . '</td>
					<td class="actions">
					    <div class="table-dropdown">
                            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M8 256a56 56 0 1 1 112 0A56 56 0 1 1 8 256zm160 0a56 56 0 1 1 112 0 56 56 0 1 1 -112 0zm216-56a56 56 0 1 1 0 112 56 56 0 1 1 0-112z"/></svg>
                            <div class="table-dropdown-items">
                                <a href="?edit-id=' . $row['id'] . '">
                                    <span class="icon">
                                        <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M471.6 21.7c-21.9-21.9-57.3-21.9-79.2 0L362.3 51.7l97.9 97.9 30.1-30.1c21.9-21.9 21.9-57.3 0-79.2L471.6 21.7zm-299.2 220c-6.1 6.1-10.8 13.6-13.5 21.9l-29.6 88.8c-2.9 8.6-.6 18.1 5.8 24.6s15.9 8.7 24.6 5.8l88.8-29.6c8.2-2.7 15.7-7.4 21.9-13.5L437.7 172.3 339.7 74.3 172.4 241.7zM96 64C43 64 0 107 0 160V416c0 53 43 96 96 96H352c53 0 96-43 96-96V320c0-17.7-14.3-32-32-32s-32 14.3-32 32v96c0 17.7-14.3 32-32 32H96c-17.7 0-32-14.3-32-32V160c0-17.7 14.3-32 32-32h96c17.7 0 32-14.3 32-32s-14.3-32-32-32H96z"/></svg>
                                    </span>
                                    Edit
                                </a>
                                <a class="red" href="?delete-id=' . $row['id'] . '" onclick="return confirm(\'Are you sure you want to delete this comment?\');">
                                    <span class="icon">
                                        <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z"/></svg>
                                    </span>    
                                    Delete
                                </a>
                            </div>
                        </div>
					</td>
                </tr>
';
}
echo '</tbody></table>';
?>
              </div>
              </div>

<?=template_admin_footer('
<script>
$(document).ready(function() {
	if (typeof $.fn.dataTable !== "undefined") {
		$("#dt-basic").dataTable({
			"responsive": true,
			"order": [[ 1, "desc" ]],
			"language": {
				"paginate": {
				  "previous": "<i class=\"fa fa-angle-left\"></i>",
				  "next": "<i class=\"fa fa-angle-right\"></i>"
				}
			}
		});
	}
});
</script>
')?>