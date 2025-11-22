<?php
require 'assets/includes/admin_config.php';

$id = (int) $_GET['id'];
$stmt = $blog_pdo->prepare("SELECT * FROM `messages` WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (empty($id) || !$row) {
    header('Location: messages.php');
	exit;
}

// Mark as viewed
$stmt = $blog_pdo->prepare("UPDATE `messages` SET viewed = 'Yes' WHERE id = ?");
$stmt->execute([$id]);
?>
<?=template_admin_header('Read Message', 'blog')?>

<?=generate_breadcrumbs([
    ['title' => 'Admin Dashboard', 'url' => '../index.php'],
    ['title' => 'Blog', 'url' => 'blog_dash.php'],
    ['title' => 'Messages', 'url' => 'messages.php'],
    ['title' => 'Read Message', 'url' => '']
])?>

<div class="content-title">
    <div class="title">
       <i class="fa-solid fa-envelope"></i>
        <div class="txt">
            <h2>Read Message</h2>
            <p>View message details</p>
        </div>
    </div>
</div>

	  <div class="card">
		  <h6 class="card-header">Message</h6>
		  <div class="card-body">
				  
<?php
echo '
			<a href="messages.php" class="btn btn-secondary col-12 btn-sm">
				<i class="fa fa-arrow-left"></i> Back to Messages
			</a><br />
			
			<i class="fa fa-user"></i> Sender: <b>' . $row['name'] . '</b><br>
			<i class="fa fa-envelope"></i> E-Mail Address: <b>' . $row['email'] . '</b><br>
			<i class="fa fa-calendar-alt"></i> Date: <b>' . date($settings['date_format'], strtotime($row['date'])) . ', ' . $row['time'] . '</b><br><br />
			<i class="fa fa-file"></i> Message:<br><b>' . $row['content'] . '</b><br><hr>
			  
			<div class="row">
				<div class="col-md-6">
					<a href="mailto:' . $row['email'] . '" class="btn btn-primary btn-sm col-12" target="_blank">
						<i class="fa fa-reply"></i> Reply
					</a>
				</div>
				<div class="col-md-6">
					<a href="messages.php?id=' . $row['id'] . '" class="btn btn-danger col-12 btn-sm">
						<i class="fa fa-trash"></i> Delete
					</a>
				</div>
			</div>
';
?>
		  </div>
	  </div>
<?=template_admin_footer()?>