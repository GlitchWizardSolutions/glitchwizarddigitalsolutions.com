<?php
require 'assets/includes/admin_config.php';

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $stmt = $blog_pdo->prepare("DELETE FROM messages WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: messages.php');
    exit;
}
?>
<?=template_admin_header('Messages', 'blog')?>

<?=generate_breadcrumbs([
    ['title' => 'Admin Dashboard', 'url' => '../index.php'],
    ['title' => 'Blog', 'url' => 'blog_dash.php'],
    ['title' => 'Messages', 'url' => '']
])?>

<div class="content-title mb-3">
    <div class="title">
       <i class="fa-solid fa-envelope"></i>
        <div class="txt">
            <h2>Messages</h2>
            <p>Manage contact messages</p>
        </div>
    </div>
</div>

            <div class="card">
              <h6 class="card-header">Messages</h6>         
                  <div class="card-body">
                    <table class="table table-border table-hover" width="100%">
                          <thead>
                              <tr>
                                  <th>Name</th>
                                  <th>E-Mail</th>
                                  <th>Date</th>
								  <th>Actions</th>
                              </tr>
                          </thead>
                          <tbody>
<?php
$stmt = $blog_pdo->query("SELECT * FROM messages ORDER by id DESC");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo '
                            <tr>
                                <td>' . htmlspecialchars($row['name']) . ' ';
	if($row['viewed'] == 'No') {
		echo '<span class="badge bg-primary">Unread</span>';
	}
	echo '
								</td>
                                <td>' . htmlspecialchars($row['email']) . '</td>
								<td data-sort="' . strtotime($row['date']) . '">' . date($settings['date_format'], strtotime($row['date'])) . ', ' . htmlspecialchars($row['time']) . '</td>
                                <td>
                                    <a class="btn btn-success btn-sm" href="read_message.php?id=' . $row['id'] . '">
                                        <i class="fa fa-eye"></i> View
                                    </a>
                                    <a class="btn btn-danger btn-sm" href="?id=' . $row['id'] . '" onclick="return confirm(\'Are you sure you want to delete this message?\')">
                                        <i class="fa fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
';
    }
?>
                          </tbody>
                     </table>
                  </div>
            </div>


 
